<?php
namespace Pagcommerce\Payment\Model\Api;
class Cc extends AbstractApi
{
    public function sendOrder(\Magento\Sales\Model\Order $order, $postData = array()){
        $data = $this->getBaseData($order);
        unset($data['due_date']);

        $customerIp = $_SERVER['REMOTE_ADDR'];
        if(isset($_SERVER["HTTP_CF_CONNECTING_IP"])){
            $customerIp = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }


        $data['installments'] = $postData['installment'];
        $data['capture'] = '1';
        $data['card_holder_name'] = $postData['cc_owner_name'];
        $data['card_expiration_month'] = $postData['cc_expiration_month'];
        $data['card_expiration_year'] = $postData['cc_expiration_year'];

        $data['card_number'] = $postData['cc_number'];
        $data['card_number'] = preg_replace('/[^0-9]/', '', $data['card_number']);

        $data['card_security_code'] = $postData['cc_cvv'];
        $data['save_credit_card	'] = '0';
        $data['customer_ip'] = $customerIp;
        $data['card_taxvat'] = $postData['customertaxvat'];

        if((int)$data['installments'] > 1){
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            /** @var \Pagcommerce\Payment\Helper\Data $helper */
            $helper = $objectManager->create(\Pagcommerce\Payment\Helper\Data::class);
            $installments = $helper->getInterestsByTotal($data['order_total']);

            $currentInstallment = $installments[$data['installments']];
            $total = $currentInstallment['total_with_interest'];
            $total = preg_replace("/[^0-9]/", "", $total);
            $data['order_total'] = $total/100;
        }

        $response = $this->sendRequest('payment-credit-card', $data);
        if(isset($response['validation_messages'])){
            foreach($response['validation_messages'] as $key => $value){
                if ($key == 'customer_taxvat') {
                    $message = 'CPF/CNPJ inválido!';
                }else{
                    if ($key == 'card_number') {
                        $message = 'Número de cartão de crédito inválido';
                    }else{
                        $message = $key.': ';
                        foreach($value as $error){
                            $message.= $error;
                        }
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
