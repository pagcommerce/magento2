<?php
namespace Pagcommerce\Payment\Api;

interface InstallmentManagementInterface
{
    /**
     * Retorna as parcelas possíveis baseado no total
     *
     * @param float $total
     * @return \Pagcommerce\Payment\Api\Data\InstallmentOptionInterface[]
     */
    public function getInstallments($total);
}
