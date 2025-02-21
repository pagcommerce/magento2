<?php

namespace Pagcommerce\Payment\Plugin\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigProviderPlugin
{
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, $result): array
    {
        $result['pagcommercePaymentPix']['paymentDescription'] = $this->scopeConfig->getValue('payment/pagcommerce_payment_pix/payment_description', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $result['pagcommercePaymentBoleto']['paymentDescription'] = $this->scopeConfig->getValue('payment/pagcommerce_payment_boleto/payment_description', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $result;
    }
}
