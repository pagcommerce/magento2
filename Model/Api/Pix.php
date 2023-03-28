<?php
namespace Pagcommerce\Payment\Model\Api;
class Pix extends AbstractApi
{
    public function getPixResponse(\Magento\Sales\Model\Order $order){
        $data = $this->getBaseData($order);

        $scopeConfig = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $qtyDays =  $scopeConfig->getValue('payment/pagcommerce_payment_pix/days', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $dueDate = strtotime(date('Y-m-d').' + '.$qtyDays.' days');
        $dueDate = date('Y-m-d', $dueDate);

        $data['due_date'] = $dueDate;
        $data['additional_information'] = '';

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
