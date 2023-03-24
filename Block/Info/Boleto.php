<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Pagcommerce\Payment\Block\Info;

class Boleto extends \Magento\Payment\Block\Info
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
    protected $_template = 'Pagcommerce_Payment::info/boleto.phtml';


    /**
     * @return string
     */
    public function toPdf()
    {
        return parent::toPdf();
//        $this->setTemplate('Magento_OfflinePayments::info/pdf/checkmo.phtml');
//        return $this->toHtml();
    }
}
