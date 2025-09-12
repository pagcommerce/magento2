<?php
namespace Pagcommerce\Payment\Api\Data;

interface InstallmentOptionInterface
{
    /**
     * Valor identificador (ex: "1", "2", "3")
     *
     * @return string
     */
    public function getValue();

    /**
     * Label formatado (ex: "3x de R$ 100,00 sem juros")
     *
     * @return string
     */
    public function getLabel();
}
