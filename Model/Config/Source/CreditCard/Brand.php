<?php
namespace Pagcommerce\Payment\Model\Config\Source\CreditCard;

class Brand implements \Magento\Framework\Option\ArrayInterface
{

    const TYPE_VISA = 'visa';
    const TYPE_MASTERCARD = 'mastercard';
    const TYPE_AMEX = 'americanexpress';
    const TYPE_DINERSCLUB = 'dinersclub';
    const TYPE_DISCOVER = 'discover';
    const TYPE_JCB = 'jcb';
    const TYPE_MAESTRO = 'maestro';
    const TYPE_ELO = 'elo';
    const TYPE_AURA= 'aura';
    const TYPE_HIPERCARD = 'hipercard';
    const TYPE_HIPER = 'hiper';


    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $data = [];
        foreach($this->toArray() as $key => $value) {
            $data[] = ['value' => $key, 'label' => $value];
        }
        return $data;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::TYPE_VISA => 'Visa',
            self::TYPE_MASTERCARD =>  'Marstercard',
            self::TYPE_AMEX =>  'American Express',
            self::TYPE_DINERSCLUB =>  'Diners Club',
            self::TYPE_DISCOVER =>  'Discover',
            self::TYPE_JCB =>  'JCB',
            self::TYPE_MAESTRO =>  'Maestro',
            self::TYPE_ELO =>  'Elo',
            self::TYPE_AURA =>  'Aura',
            self::TYPE_HIPERCARD =>  'Hipercard',
            self::TYPE_HIPER =>  'Hiper',
        ];
    }
}
