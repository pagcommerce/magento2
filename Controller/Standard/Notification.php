<?php

namespace Pagcommerce\Payment\Controller\Standard;

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
use Magento\Framework\App\Request\InvalidRequestException;


class Notification implements HttpPostActionInterface,  CsrfAwareActionInterface
{


    protected InvoiceSender $invoiceSender;

    protected Logger $logger;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /** @var OrderFactory  */
    protected $_orderFactory;

    /** @var TransactionApi  */
    protected $transactionApi;

    /** @var JsonFactory  */
    protected $resultJsonFactory;

    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepository;

    /** @var HelperData  */
    protected $helperData;

    /** @var OrderRepository  */
    protected $orderRepository;



    /**
     * @param RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        InvoiceSender $invoiceSender,
        Logger $logger,
        RequestInterface $request,
        OrderFactory $factory,
        TransactionApi $transactionApi,
        JsonFactory $resultJsonFactory,
        InvoiceRepository $invoiceRepository,
        HelperData $helperData,
        OrderRepository $orderRepository
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
    }

    public function execute()
    {
        $data = $this->_request->getParams();

        if(!$data){
            $data = file_get_contents('php://input');
            if($data){
                $data = json_decode($data, true);
            }
        }
        if(isset($data['id']) && isset($data['transaction_type']) && isset($data['reference_id'])){
            $order = $this->_orderFactory->create()->loadByIncrementId($data['reference_id']);
            if($order && $order->getId()){
                if($data['status'] == 'approved'){
                    $transaction = $this->transactionApi->getTransaction($data['id']);
                    if(is_array($transaction)){
                        if(isset($transaction['id']) && $transaction['id'] == $data['id']){
                            if($transaction['status'] == 'approved'){
                                $this->confirmPayment($order, $data);
                            }
                        }
                    }
                }else{
                    return $this->createResult(200, [
                        'message' => 'Pedido não aprovado'
                    ]);
                }
            }else{
                return $this->createResult(200, [
                    'message' => 'Pedido não encontrado'
                ]);
            }
        }

        return $this->createResult(200, [
            'message' => 'Notificação finalizada'
        ]);
    }

    public function createResult($statusCode, $data)
    {
        /** @var JsonFactory $resultPage */
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


    private function confirmPayment(?\Magento\Sales\Model\Order $order = null, $paymentData = array())
    {
        if ($order->canInvoice()) {
            $payment = $order->getPayment();

            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            $invoice->pay();
            !$invoice->getTransactionId() ? $invoice->setTransactionId($paymentData['id']) : null;
            $this->invoiceRepository->save($invoice);

            // envia o e-mail
            try {
                $this->invoiceSender->send($invoice);
                $invoice->setEmailSent(true);
                $this->invoiceRepository->save($invoice);
            } catch (\Exception $e) {
                $this->logger->debug('Erro ao enviar e-mail de fatura: '.$e->getMessage());
            }

            $payment->setAdditionalInformation('captured', true);
            $payment->setAdditionalInformation('captured_date', date('Y-m-d h:i:s'));
            $paidStatus = $this->helperData->getConfig('paid_order_status', $payment->getMethod()) ?: null;
            $order->addCommentToStatusHistory(__('Pagamento confirmado automaticamente'), $paidStatus);
            $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
            $this->orderRepository->save($order);
        }
    }
}

