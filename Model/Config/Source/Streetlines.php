<?php
namespace Pagcommerce\Payment\Model\Config\Source;

class Streetlines implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 1, 'label' => __('Linha 1')],
            ['value' => 2, 'label' => __('Linha 2')],
            ['value' => 3, 'label' => __('Linha 3')],
            ['value' => 4, 'label' => __('Linha 4')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            '1' => __('Linha 1'),
            '2' => __('Linha 2'),
            '3' => __('Linha 3'),
            '4' => __('Linha 4')
            ];
    }
}
