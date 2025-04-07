<?php
namespace Pagcommerce\Payment\Model\Config\Source\Fee;

class Type implements \Magento\Framework\Data\OptionSourceInterface
{

    const TYPE_PARCEL = 'parcel';
    const TYPE_TOTAL = 'total';


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
        $data = [];
        $data[self::TYPE_PARCEL] = 'Por Parcela';
        $data[self::TYPE_TOTAL] = 'Pelo Total do Pedido';
        return $data;
    }
}
