<?php

namespace Pagcommerce\Payment\Controller\Standard;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Pagcommerce\Payment\Logger\Logger as Logger;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\OrderFactory;
use Pagcommerce\Payment\Model\Api\Payment\Transaction as TransactionApi;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order\Invoice;
use Pagcommerce\Payment\Helper\Data as HelperData;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\App\Request\InvalidRequestException;
use Exception;


class Notification implements HttpPostActionInterface,  CsrfAwareActionInterface
{

    protected InvoiceSender $invoiceSender;

    protected Logger $logger;

    protected RequestInterface $_request;

    protected OrderFactory $_orderFactory;

    protected TransactionApi $transactionApi;

    protected JsonFactory $resultJsonFactory;

    protected InvoiceRepository $invoiceRepository;

    protected HelperData $helperData;

    protected OrderRepository $orderRepository;

    protected State $appState;

    public function __construct(
        Context $context,
        InvoiceSender $invoiceSender,
        Logger $logger,
        RequestInterface $request,
        OrderFactory $factory,
        TransactionApi $transactionApi,
        JsonFactory $resultJsonFactory,
        InvoiceRepository $invoiceRepository,
        HelperData $helperData,
        OrderRepository $orderRepository,
        State $appState
    )
    {
        $this->invoiceSender = $invoiceSender;
        $this->logger = $logger;
        $this->_request = $request;
        $this->_orderFactory = $factory;
        $this->transactionApi = $transactionApi;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->helperData = $helperData;
        $this->orderRepository = $orderRepository;
        $this->appState = $appState;
    }

    /**
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws LocalizedException
     */
    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        // obtem os dados enviados na request.
        $data = $this->_request->getParams();
        if(!$data) {
            $data = file_get_contents('php://input');
            if($data) $data = json_decode($data, true);
        }

        if(isset($data['id']) && isset($data['transaction_type']) && isset($data['reference_id'])) {

            $order = $this->_orderFactory->create()->loadByIncrementId($data['reference_id']);
            if($order && $order->getId()) {

                if($data['status'] == 'approved') {
                    $transaction = $this->transactionApi->getTransaction($data['id']);

                    if(is_array($transaction)) {
                        if(isset($transaction['id']) && $transaction['id'] == $data['id']) {
                            if($transaction['status'] == 'approved') {
                                $this->confirmPayment($order, $data);
                                return $this->createResult(200, [
                                    'message' => 'Notificação finalizada'
                                ]);
                            }
                        }
                    }
                }else{
                    return $this->createResult(403, [
                        'message' => 'Pedido não aprovado'
                    ]);
                }
            }else{
                return $this->createResult(404, [
                    'message' => 'Pedido não encontrado'
                ]);
            }
        }

        return $this->createResult(500, [
            'message' => 'Parâmetros inválidos'
        ]);
    }

    public function createResult($statusCode, $data): \Magento\Framework\Controller\Result\Json
    {
        $resultPage = $this->resultJsonFactory->create();
        $resultPage->setHttpResponseCode($statusCode);
        $resultPage->setData($data);
        return $resultPage;
    }


    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws InputException
     */
    private function confirmPayment(?Order $order = null, array $paymentData = []): void
    {
        if (!$order || !$order->getId()) return;

        // essencial para o envio do e-mail, caso contrário não consegue obter o template de e-mail.
        try {
            $this->appState->setAreaCode(Area::AREA_FRONTEND);
        } catch (LocalizedException $e) {
            // já estava setado.
        }

        // cria ou recupera a invoice já criada.
        if ($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            $invoice->pay();

            if (!$invoice->getTransactionId() && isset($paymentData['id'])) $invoice->setTransactionId($paymentData['id']);

            $this->invoiceRepository->save($invoice);
            $this->orderRepository->save($order);
        } else {
            $invoice = $order->getInvoiceCollection()->getFirstItem();
        }

        if (!$invoice || !$invoice->getId()) {
            $this->logger->debug('Nenhuma fatura encontrada para pedido #' . $order->getIncrementId());
            return;
        }

        // atualiza o status do pedido.
        $payment = $order->getPayment();
        $payment->setAdditionalInformation('captured', true);
        $payment->setAdditionalInformation('captured_date', date('Y-m-d H:i:s'));

        $paidStatus = $this->helperData->getConfig('paid_order_status', $payment->getMethod())
            ?: Order::STATE_PROCESSING;

        $order->addCommentToStatusHistory(__('Pagamento confirmado automaticamente'), $paidStatus);
        $order->setState(Order::STATE_PROCESSING);
        $this->orderRepository->save($order);

        // envia o e-mail da fatura do pedido.
        try {
            if (!$invoice->getEmailSent()) {
                $this->invoiceSender->send($invoice, true);
                $invoice->setEmailSent(true);
                $this->invoiceRepository->save($invoice);
                $this->logger->debug('E-mail da fatura enviado para o pedido #' . $order->getIncrementId());
            }
        } catch (Exception $e) {
            $this->logger->debug(
                'Erro ao enviar e-mail da fatura **automaticamente** do pedido #' .
                $order->getIncrementId() . ': ' . $e->getMessage()
            );
        }
    }
}
