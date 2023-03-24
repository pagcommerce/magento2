<?php
namespace Pagcommerce\Payment\Model\Api\Payment;
class Transaction extends \Pagcommerce\Payment\Model\Api\AbstractApi
{
    public function getTransaction($transactionId){
        $uri = 'payment-transaction/'.$transactionId;
        $response = $this->sendRequest($uri, array(), 'GET');
        return $response;
    }
}
