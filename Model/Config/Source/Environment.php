<?php
namespace Pagcommerce\Payment\Model\Config\Source;

class Environment implements \Magento\Framework\Option\ArrayInterface
{
    const PRODUCTION = 'production';
    const TEST = 'development';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::TEST, 'label' => __('Teste (Homologação)')],
            ['value' => self::PRODUCTION, 'label' => __('Produção')],
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
            self::TEST => __('Teste (Homologação)'),
            self::PRODUCTION =>  __('Produção')];
    }
}
