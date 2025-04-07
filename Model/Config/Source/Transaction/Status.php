<?php
namespace Pagcommerce\Payment\Model\Config\Source\Transaction;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{

    const STATUS_PENDING     = 'pending_payment';
    const STATUS_IN_ANALYSIS    = 'in_analysis';
    const STATUS_APPROVED       = 'approved';
    const STATUS_NOT_PAID   = 'notpaid';
    const STATUS_CANCELLED  = 'canceled';
    const STATUS_REFUNDED   = 'refunded';
    const STATUS_IN_DISPUT  = 'in_disput';
    const STATUS_DENIED     = 'denied';
    const STATUS_DENIED_FRAUDE     = 'denied_risk';
    const STATUS_CHARGEBACK     = 'chargeback';
    const STATUS_BLOCKED     = 'blocked';


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
        $data[self::STATUS_PENDING] = 'Aguardando Pagamento';
        $data[self::STATUS_IN_ANALYSIS] = 'Em Análise (Antifraude)';
        $data[self::STATUS_APPROVED] = 'Pagamento Confirmado';
        $data[self::STATUS_NOT_PAID] = 'Não pago';
        $data[self::STATUS_CANCELLED] = 'Cancelado';
        $data[self::STATUS_REFUNDED] = 'Pagamento estornado';
        $data[self::STATUS_IN_DISPUT] = 'Pagamento em Disputa (Bloqueio)';
        $data[self::STATUS_DENIED] = 'Pagamento não aprovado';
        $data[self::STATUS_DENIED_FRAUDE] = 'Recusado pelo Antifraude';
        $data[self::STATUS_CHARGEBACK] = 'Estornada por Chargeback';
        $data[self::STATUS_BLOCKED] = 'Bloqueada';
        return $data;
    }
}
