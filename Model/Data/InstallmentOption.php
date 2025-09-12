<?php
namespace Pagcommerce\Payment\Model\Data;

use Pagcommerce\Payment\Api\Data\InstallmentOptionInterface;

class InstallmentOption implements InstallmentOptionInterface
{
    private $value;
    private $label;

    public function __construct($value, $label)
    {
        $this->value = $value;
        $this->label = $label;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getLabel()
    {
        return $this->label;
    }
}
