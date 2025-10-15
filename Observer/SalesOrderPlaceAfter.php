<?php

namespace Pagcommerce\Payment\Observer;


use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Pagcommerce\Payment\Logger\Logger as Logger;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\App\ObjectManager;
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


class SalesOrderPlaceAfter implements ObserverInterface
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

    public function __construct(
        InvoiceSender $invoiceSender,
        Logger $logger,
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
        $this->_orderFactory = $factory;
        $this->transactionApi = $transactionApi;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->helperData = $helperData;
        $this->orderRepository = $orderRepository;
    }


    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {

        return $this;
        if (!$this->moduleIsEnable()) {
            return $this;
        }

        $event = $observer->getEvent();
        $order = $event->getOrder();
        $payment = $order->getPayment();

        if ($payment->getMethod() != 'pagcommerce_payment_cc') {
            return $this;
        }

        if ( $order->canInvoice() && $order->getState() == \Magento\Sales\Model\Order::STATE_PROCESSING) {
            $additionalInformation = $order->getPayment()->getAdditionalInformation();
            $additionalInformation['id'] = $additionalInformation['pagcommerce_transaction_id'];
            $this->confirmPayment($order, $additionalInformation);
        }
        return $this;
    }

    public function moduleIsEnable()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var \Pagcommerce\Payment\Helper\Data $helper */
        $helper = $objectManager->get(\Pagcommerce\Payment\Helper\Data::class);
        return (bool)$helper->getConfig('active', 'pagcommerce_payment_cc');
    }

    private function confirmPayment(?\Magento\Sales\Model\Order $order = null, $paymentData = array())
    {
        if ($order->canInvoice()) {
            $payment = $order->getPayment();

            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            $invoice = $order->prepareInvoice();
            $invoice->setOrder($order);
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


    public function createInvoice($order)
    {
        $payment = $order->getPayment();
        $payment
            ->setIsTransactionClosed(true)
            ->registerCaptureNotification(
                $order->getGrandTotal(),
                true
            );
        $order->save();


        $invoice = $payment->getCreatedInvoice();
        if ($invoice && !$order->getEmailSent()) {
            $order->addStatusHistoryComment(
                'PGM - ' .
                __(
                    'Notified customer about invoice #%1.',
                    $invoice->getIncrementId()
                )
            )->setIsCustomerNotified(true)
                ->save();
        }

        return true;
    }


}
