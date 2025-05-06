<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Pagcommerce\Payment\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Pagcommerce\Payment\Gateway\Http\Client\ClientMock;

use Pagcommerce\Payment\Model\Config\Source\Transaction\Status as TransactionStatus;
/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'pagcommerce_payment_cc';

    /** @var \Magento\Payment\Model\CcConfig  */
    private $ccConfig;

    private $_helper;

    public function __construct(
        \Magento\Payment\Model\CcConfig $ccConfig,
        \Pagcommerce\Payment\Helper\Data $helper
    )
    {
        $this->ccConfig = $ccConfig;
        $this->_helper = $helper;
    }



    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $objStatus = new TransactionStatus();
        $status = $objStatus->toArray();

        /** @var \Magento\Checkout\Model\Session\Interceptor $session */
        $session = $objectManager->get(\Magento\Checkout\Model\Session::class);
        $quote = $session->getQuote();

        $installments = $this->_helper->getInterestsByTotal($quote->getGrandTotal());

        /** @var \Magento\Customer\Model\Session $customerSession */
        $customerSession = $objectManager->get(\Magento\Customer\Model\Session::class);
        $taxVat = '';
        if($customerSession->isLoggedIn()){
            $taxVat = $customerSession->getCustomer()->getTaxvat();
        }

        $returnData  = [
            'payment' => [
                self::CODE => [
                    'installments' => $installments,
                    'months' => $this->ccConfig->getCcMonths(),
                    'years' => $this->ccConfig->getCcYears(),
                    'hasVerification' => $this->ccConfig->hasVerification(),
                    'taxvat' => $taxVat,
//                    'is_saved_card' => false,
//                    'enabled_saved_cards' => false,
//                    'tds_active' => false,
//                    'order_with_tds_refused' => false,
//                    'tds_min_amount' => 100.00,
//                    'cards' => array(),
//                    'selected_card' => array(),
//                    'size_credit_card' => '18',
//                    'number_credit_card' => 'null',
//                    'data_credit_card' => ''
                ]
            ]
        ];

        return $returnData;
    }
}
