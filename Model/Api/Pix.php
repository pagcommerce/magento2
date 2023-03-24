<?php
namespace Pagcommerce\Payment\Model\Api;
class Pix extends AbstractApi
{
    public function getPixResponse(\Magento\Sales\Model\Order $order){
        $data = $this->getBaseData($order);
        $response = $this->sendRequest('payment-pix', $data);
        if(isset($response['validation_messages'])){
            foreach($response['validation_messages'] as $key => $value){
                $message = $key.': ';
                foreach($value as $error){
                    $message.= $error;
                }
                $this->addErros($message);
            }
        }else{
            return $response;

        }
        return false;

    }


}
