<?php
namespace Pagcommerce\Payment\Model\Api;
class Boleto extends AbstractApi
{
    public function getBoletoResponse(\Magento\Sales\Model\Order $order){
        $data = $this->getBaseData($order);

        $scopeConfig = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Config\ScopeConfigInterface');
        $qtyDays =  $scopeConfig->getValue('payment/pagcommerce_payment_boleto/days', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
        $dueDate = strtotime(date('Y-m-d').' + '.$qtyDays.' days');
        $dueDate = date('Y-m-d', $dueDate);

        $data['due_date'] = $dueDate;
        $data['additional_information'] = $scopeConfig->getValue('payment/pagcommerce_payment_boleto/comments', \Magento\Store\Model\ScopeInterface::SCOPE_STORES);

        $response = $this->sendRequest('payment-boleto', $data);
        if(isset($response['validation_messages'])){
            foreach($response['validation_messages'] as $key => $value){

                if ($key == 'customer_taxvat') {
                    $message = 'CPF/CNPJ inválido!';
                }else{
                    $message = $key.': ';
                    foreach($value as $error){
                        $message.= $error;
                    }
                }

                $this->addErros($message);
            }
        }else{
            return $response;

        }
        return false;
    }
}
