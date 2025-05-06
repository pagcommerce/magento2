<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Pagcommerce\Payment\Block\Info;

class Cc extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_payableTo;

    /**
     * @var string
     */
    protected $_mailingAddress;

    /**
     * @var string
     */
    protected $_template = 'Pagcommerce_Payment::info/cc.phtml';


    /**
     * @return string
     */
    public function toPdf()
    {
        return parent::toPdf();
//        $this->setTemplate('Magento_OfflinePayments::info/pdf/checkmo.phtml');
//        return $this->toHtml();
    }

    public function getPagcommerceUrlDomain(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Pagcommerce\Payment\Helper\Data $helper */
        $helper = $objectManager->get(\Pagcommerce\Payment\Helper\Data::class);

        $environment = $helper->getConfig('environment');
        if($environment == \Pagcommerce\Payment\Model\Config\Source\Environment::PRODUCTION){
            return 'https://pagcommerce.com.br';
        }else{
            return 'https://sandbox.pagcommerce.com.br';
        }
    }

    public function getCreditCardBrandName($brandCode){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Pagcommerce\Payment\Model\Config\Source\CreditCard\Brand $source */
        $source = $objectManager->create(\Pagcommerce\Payment\Model\Config\Source\CreditCard\Brand::class);
        $options = $source->toArray();

        return isset($options[$brandCode]) ? $options[$brandCode] : '';
    }
}
