<?php
namespace Pagcommerce\Payment\Model\Api;

abstract class AbstractApi{
    private $_key = '';
    private $_secret = '';

    private $_erros = array();
    private $_environment = '';

    /** @var \Pagcommerce\Payment\Logger\Logger  */
    private $logger;

    /** @var \Magento\Store\Model\StoreManagerInterface  */
    private $storeManager;

    public function __construct(\Pagcommerce\Payment\Logger\Logger $logger, \Magento\Store\Model\StoreManagerInterface $storeManager){
        $this->_environment = $this->getCoreConfig('payment/pagcommerce_payment/enviroment');
        $this->_key = $this->getCoreConfig('payment/pagcommerce_payment/api_key');
        $this->_secret = $this->getCoreConfig('payment/pagcommerce_payment/api_secret');
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }


    public function getCoreConfig($valor)
    {
        $scopeConfig = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\Config\ScopeConfigInterface');
        return $scopeConfig->getValue($valor, \Magento\Store\Model\ScopeInterface::SCOPE_STORES);
    }


    public function sendRequest($uri, $data, $method = 'POST'){
        if($this->hasCredentials()){
            if($this->getEnvironment()){
                $requestData = [];
                if($method == 'POST'){
                    if(!$data){
                        $this->addErros('É necessário informar o POSTDATA');
                        return false;
                    }else{
                        if(is_array($data) || is_object($data) && $method == 'POST'){
                            $data = json_encode($data);
                            $requestData = json_decode($data, true);
                        }
                    }
                }

                $ch = curl_init($this->getApiUrl().$uri);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . base64_encode($this->getKey().':'.$this->getSecret())
                ));

                switch ($method){
                    case 'POST':
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        break;
                }

                $logEnabled = $this->getCoreConfig('payment/pagcommerce_payment/enable_log');
                if($logEnabled){
                    $this->writeLog("REQUEST", $requestData);
                }

                $response = curl_exec($ch);
                $error = curl_error($ch);
                curl_close($ch);

                if(!$error && $response){
                    $response = json_decode($response, true);
                    if($logEnabled){
                        $this->writeLog("RESPONSE", $response);
                    }
                    return $response;
                }else{
                    $this->addErros($error);
                    if(is_array($error)){
                        $this->writeLog("RESPONSE ERROR", $error);
                    }
                }
            }else{
                $this->addErros('É necessário setar o ambiente da API pelo método set environmente passando constantes ENV_* dessa classe');
            }
        }else{
            $this->addErros('É necessário informar as credenciais de acesso a API pelos métodos setKey, setSecret, setPartnerId e setBusinessUnitId');
        }
        return false;
    }

    /** @return boolean */
    private function hasCredentials(){
        return $this->getKey() && $this->getSecret();
    }

    /** @return string */
    private function getApiUrl(){
        return $this->getEnvironment() == \Pagcommerce\Payment\Model\Config\Source\Environment::TEST ? $this->getCoreConfig('payment/pagcommerce_payment/sandbox_api_url').'/' : 'https://api.pagcommerce.com.br/';
    }


    /** @return $this */
    protected function addErros($errorMessage){
        $this->_erros[] = $errorMessage;
        return $this;
    }

    /** @return string */
    public function getErrors(){
        if($this->hasErros()){
            return implode('<br>', $this->_erros);
        }
        return '';
    }

    /** @return boolean */
    public function hasErros(){
        return sizeof($this->_erros) > 1;
    }

    /**
     * @return string
     */
    protected function getEnvironment()
    {
        return  $this->getCoreConfig('payment/pagcommerce_payment/enviroment');
    }

    /**
     * @return string
     */
    protected function getKey()
    {
        return $this->_key;
    }

    /**
     * @return string
     */
    protected function getSecret()
    {
        return $this->_secret;
    }


    /**
     * @return string
     */
    protected function formatCpfCnpj($cpfCnpj){
        $cpfCnpj = trim($cpfCnpj);
        $cpfCnpj = str_replace(array('-', '.', '/'), array('', '', ''), $cpfCnpj);
        return $cpfCnpj;
    }


    protected function formatTelephone($phoneNumber){
        return str_replace(array('(', ')', ' ', '-'), '', $phoneNumber);
    }

    protected function formatCEP($cep){
        return str_replace(array('(', ')', ' ', '-'), '', $cep);
    }

    public function getBaseData(\Magento\Sales\Model\Order $order){
        $address = $order->getIsVirtual() ? $order->getBillingAddress() : $order->getShippingAddress();
        $telephone = $address->getTelephone();

        $configStret = $this->getCoreConfig('payment/pagcommerce_payment/address_logradouro');
        $configNumber = $this->getCoreConfig('payment/pagcommerce_payment/address_numero');
        $configDistrict = $this->getCoreConfig('payment/pagcommerce_payment/address_bairro');
        $configStretComplement = $this->getCoreConfig('payment/pagcommerce_payment/address_complemento');


        $data = array(
            'customer_name' => $order->getCustomerName(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_type' => strlen($order->getCustomerTaxvat()) > 12 ? 'PJ' : 'PF',
            'customer_taxvat' => $this->formatCpfCnpj($order->getCustomerTaxvat()),
            'customer_phone'  => $this->formatTelephone($telephone),
            'customer_address' => array(
                'postalcode' => $address->getPostcode(),
                'street' => $address->getStreetLine($configStret),
                'number' =>  $address->getStreetLine($configNumber),
                'complement' => $address->getStreetLine($configStretComplement),
                'district' =>$address->getStreetLine($configDistrict),
                'city' => $address->getCity(),
                'uf' => $address->getRegionCode(),
                'country' => $address->getCountryId(),
            ),
            'reference_id' => $order->getIncrementId(),
            'order_total' => $this->formatCurrency($order->getGrandTotal()),
            'due_date' => date('Y-m-d'),
        );

        $items = array();

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        foreach($order->getAllVisibleItems() as $orderItem){
            $items[] = array(
                'id' => $orderItem->getProduct()->getSku(),
                'name' => $orderItem->getName(),
                'qty' => (int)$orderItem->getQtyOrdered(),
                'unit_price' =>  $this->formatCurrency($orderItem->getPrice()),
                'total' =>  $this->formatCurrency($orderItem->getRowTotal())
            );
        }

        $data['order_items'] = $items;
        $data['shipment'] = array(
            'shipment_price' => $order->getIsVirtual() ? '0' : $this->formatCurrency($order->getShippingAmount()),
            'shipment_address' => array(
                'postalcode' => $this->formatCEP($address->getPostcode()),
                'street' => $address->getStreetLine($configStret),
                'number' =>  $address->getStreetLine($configNumber),
                'complement' => $address->getStreetLine($configStretComplement),
                'district' =>$address->getStreetLine($configDistrict),
                'city' => $address->getCity(),
                'uf' => $address->getRegionCode(),
                'country' => $address->getCountryId(),
            )
        );
        $notificationUrl = $this->storeManager->getStore()->getUrl('pagcommerce/standard/notification');

        $data['notification_url'] = $notificationUrl;
        return $data;
    }

    public function formatCurrency($amount){
        return number_format($amount, 2, '.', '');
    }

    public function writeLog($title, $data = []){
        $this->logger->debug($title, $data);
    }
}
