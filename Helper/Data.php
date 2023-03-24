<?php
namespace Pagcommerce\Payment\Helper;

use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Payment\Helper\Data
{
    public function __construct(Context $context, LayoutFactory $layoutFactory, Factory $paymentMethodFactory, Emulation $appEmulation, Config $paymentConfig, Initial $initialConfig)
    {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
    }

    /**
     * @param $config
     * @param string $group
     * @return mixed
     */
    public function getConfig($config, string $group = 'pagcommerce_payment')
    {
        return $this->scopeConfig->getValue(
            'payment/' . $group . '/' . $config,
            ScopeInterface::SCOPE_STORE
        );
    }
}


