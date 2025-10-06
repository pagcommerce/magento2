<?php
namespace Pagcommerce\Payment\Model;

use Pagcommerce\Payment\Api\InstallmentManagementInterface;
use Pagcommerce\Payment\Api\Data\InstallmentOptionInterface;
use Pagcommerce\Payment\Model\Data\InstallmentOption;
use Pagcommerce\Payment\Helper\Data as Helper;

class InstallmentManagement implements InstallmentManagementInterface
{
    /**
     * @var Helper
     */
    private $helper;

    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getInstallments($total)
    {
        $installments = $this->helper->getInterestsByTotal($total);

        $result = [];
        foreach ($installments as $key => $data) {
            $result[] = new InstallmentOption($data['parcel'], $data['label']);
        }

        return $result;
    }
}
