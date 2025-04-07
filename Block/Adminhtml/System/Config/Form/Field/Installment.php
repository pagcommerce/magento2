<?php


namespace Pagcommerce\Payment\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;

class Installment extends AbstractFieldArray
{
    protected $_elementFactory;

    public function __construct(
        Context $context,
        Factory $elementFactory,
        array $data = []
    )
    {
        $this->_elementFactory  = $elementFactory;
        parent::__construct($context,$data);
    }

    protected function _construct()
    {
        $this->addColumn('price_of',
            [
                'label' => __('Valor pedido de'),
                'class' => 'required-entry'
            ]
        );
        $this->addColumn('price_up_to',
            [
                'label' => __('Valor pedido até'),
                'class' => 'required-entry'
            ]
        );
        $this->addColumn(
            'installments',
            [
                'label' => __('Parcela(s)'),
                'class' => 'required-entry'
            ]
        );
        $this->addColumn(
            'fees',
            [
                'label' => __('Juros (%)'),
                'class' => 'required-entry'
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Adicionar');
        parent::_construct();
    }
}
