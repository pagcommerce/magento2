<?php

namespace Pagcommerce\Payment\Model\Api;

class PaymentCancel extends AbstractApi
{
    public function cancelPayment(\Magento\Sales\Model\Order $order): mixed
    {
        $paymentStatus = $this->getPaymentStatus($order->getPayment());
        if ($paymentStatus && $paymentStatus !== 'canceled') {
            return $this->sendRequest($this->getTransactionUriByStatus($paymentStatus),
                ['transaction_id' => $order->getPayment()->getData('last_trans_id')],
                'POST'
            );
        }
        return [];
    }
}
