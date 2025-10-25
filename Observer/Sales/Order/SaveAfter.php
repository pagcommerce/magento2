<?php

namespace Pagcommerce\Payment\Observer\Sales\Order;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pagcommerce\Payment\Model\Api\PaymentCancel;

class SaveAfter implements ObserverInterface
{
    protected PaymentCancel $paymentCancel;

    public function __construct(
        PaymentCancel $paymentCancel
    ) {
        $this->paymentCancel = $paymentCancel;
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getData('order') ?? false;
        if ($order) {
            $payment = $order->getPayment();
            $paymentMethod = $payment->getMethodInstance()->getCode();
            if ($paymentMethod == 'pagcommerce_payment_pix' || $paymentMethod == 'pagcommerce_payment_boleto' || $paymentMethod == 'pagcommerce_payment_cc') {
                //$this->paymentCancel->cancelPayment($order);
            }
        }
    }
}
