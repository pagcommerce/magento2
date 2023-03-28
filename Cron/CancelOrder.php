<?php

namespace Pagcommerce\Payment\Cron;
use Pagcommerce\Payment\Logger\Logger;
class CancelOrder
{

    /** @var Logger  */
    private $logger;

    protected $_orderCollectionFactory;
    protected $_orderManagement;

    public function __construct(
         Logger $logger,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
         \Magento\Sales\Api\OrderManagementInterface $orderManagement
    ){
        $this->logger = $logger;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderManagement = $orderManagement;
    }

    public function execute()
    {
        $this->logger->debug('Teste de CRON');

        $scopeConfig = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Config\ScopeConfigInterface');

        $canCancel = $scopeConfig->getValue('payment/pagcommerce_payment_pix/cancel_orders', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        if($canCancel){
            $days =  $scopeConfig->getValue('payment/pagcommerce_payment_pix/days', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            $days = $days + 2;
            $status  =  $scopeConfig->getValue('payment/pagcommerce_payment_pix/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            $validDate = strtotime(date('Y-m-d').' -'.$days.' days');
            $validDate = date('Y-m-d', $validDate);
            $this->logger->debug('Data Para PIX:'.$validDate.' - STATUS '.$status);
            $orders = $this->getOrderCollectionPaymentMethod('pagcommerce_payment_pix', $status, $validDate);
            $this->logger->debug('Total PIX '.$orders->count());

            if($orders->count()){
                /** @var \Magento\Sales\Model\Order $order */
                foreach($orders as $order){
                    if($order->canCancel()){
                        $order->addCommentToStatusHistory('Pedido cancelado automaticamente. Data limite de pagamento excedida');
                        $order->save();

                        $this->_orderManagement->cancel($order->getId());
                        $this->logger->debug($order->getId().' cancelada');


                    }
                }
            }
        }


        $canCancel = $scopeConfig->getValue('payment/pagcommerce_payment_boleto/cancel_orders', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        if($canCancel){
            $days =  $scopeConfig->getValue('payment/pagcommerce_payment_boleto/days', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            $days = $days + 2;
            $status  =  $scopeConfig->getValue('payment/pagcommerce_payment_boleto/order_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
            $validDate = strtotime(date('Y-m-d').' -'.$days.' days');
            $validDate = date('Y-m-d', $validDate);
            $this->logger->debug('Data Para Boleto:'.$validDate.' - STATUS '.$status);
            $orders = $this->getOrderCollectionPaymentMethod('pagcommerce_payment_boleto', $status, $validDate);
            $this->logger->debug('Total Boleto '.$orders->count());

            if($orders->count()){
                /** @var \Magento\Sales\Model\Order $order */
                foreach($orders as $order){
                    if($order->canCancel()){
                        $order->addCommentToStatusHistory('Pedido cancelado automaticamente. Data limite de pagamento excedida');
                        $order->save();

                        $this->_orderManagement->cancel($order->getId());
                        $this->logger->debug($order->getId().' cancelada');

                    }
                }
            }
        }
        return $this;

    }

    /** @return \Magento\Sales\Model\ResourceModel\Order\Collection */
    public function getOrderCollectionPaymentMethod($paymentMethod, $status, $from)
    {

        $statuses = [$status];
        $fromDate = $from.' 00:00:00';
        $toDate = $from.' 23:59:59';
        $collection = $this->_orderCollectionFactory->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter('created_at',
                ['gteq' => $fromDate]
            )
            ->addFieldToFilter('created_at',
                ['lteq' => $toDate]
            )
            ->addFieldToFilter('status',
                ['in' => $statuses]
            )
        ;

        /* join with payment table */
        $collection->getSelect()
            ->join(
                ["sop" => "sales_order_payment"],
                'main_table.entity_id = sop.parent_id',
                array('method')
            )
            ->where('sop.method = ?',$paymentMethod);

        $collection->setOrder(
            'created_at',
            'desc'
        );
        return $collection;
    }



}
