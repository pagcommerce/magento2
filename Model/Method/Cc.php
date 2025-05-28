<?php
namespace Pagcommerce\Payment\Model\Method;
use Magento\Payment\Model\Method;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;


class Cc extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $invoiceRepository;
    protected $orderRepository;
    protected $helperData;

    protected $_code = 'pagcommerce_payment_cc';

    /**
     * @var string
     */
    protected $_infoBlockType = \Pagcommerce\Payment\Block\Info\Cc::class;


    protected $_isGateway           = true;
    protected $_canRefund           = true;
    protected $_canCapture          = true;


    protected $_canCapturePartial           = false;
    protected $_canRefundInvoicePartial     = false;


    /** @return \Magento\Framework\App\ObjectManager */
    private function getObjectManager(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager;
    }

    public function initialize($paymentAction, $stateObject){
        /** @var \Magento\Sales\Model\Order $order */
        $order   = $this->getInfoInstance()->getOrder();

        /** @var \Pagcommerce\Payment\Model\Api\Cc $api */
        $api =  $this->getObjectManager()->create(\Pagcommerce\Payment\Model\Api\Cc::class);

        $response = $api->sendOrder($order, $this->getInfoInstance()->getAdditionalInformation());
        if($response  && isset($response['id'])){
            $this->helperData = $this->getObjectManager()->create(\Pagcommerce\Payment\Helper\Data::class);

            $payment =  $order->getPayment();
            $payment->setAdditionalInformation('transaction_id', $response['id']);

            $additionalInformation = $this->getInfoInstance()->getAdditionalInformation();
            /** @var \Pagcommerce\Payment\Helper\Data $helper */
            $helper = $this->getObjectManager()->create(\Pagcommerce\Payment\Helper\Data::class);
            $installments = $helper->getInterestsByTotal($order->getGrandTotal());
            $currentInstallment = $installments[$additionalInformation['installment']];

            $paymentInformation = array();
            $paymentInformation['pagcommerce_transaction_id'] = $response['id'];
            $paymentInformation['installment'] = $currentInstallment;
            $paymentInformation['payment_data'] = $response['payment_data'];
            $payment->setAdditionalInformation($paymentInformation);

            switch ($response['status']){
                case 'denied':
                    throw new CouldNotSaveException(__('Pagamento não aprovado. Por favor tente novamente com outro cartão'));
                case 'denied_risk':
                    throw new CouldNotSaveException(__('Pagamento não aprovado. Por favor tente novamente com outro cartão ou utilize outro dispositivo'));
                    break;
                case 'approved':

                    $paidStatus = $this->helperData->getConfig('paid_order_status', $payment->getMethod()) ?: null;
                    $stateObject->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $stateObject->setStatus($paidStatus);
                    $stateObject->setIsNotified(true);

                    $payment
                        ->setIsTransactionClosed(false)
                        ->setShouldCloseParentTransaction(false)
                        ->setTransactionId($response['id'])
                        ->registerCaptureNotification(
                            $order->getGrandTotal(),
                            true
                        );
                    $order->save();
                    return $this;
                    break;
                case 'in_analysis':
                    $order->getPayment()->setTransactionId($response['id'] . '-analysis')
                        ->setTxnType(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID)
                        ->setIsTransactionClosed(false)
                        ->setIsTransactionPending(true);
                    break;
            }
        }else{
            throw new CouldNotSaveException(
                __('Ocorreu um erro ao processar seu pagamento: '.$api->getErrors())
            );
        }

    }

    public function isInitializeNeeded(){
        return true;
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $currentData = $data->getAdditionalData();

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation($currentData);

        return $this;
    }



    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $pagcommerceTransactionId = $payment->getParentTransactionId();
        if (isset($pagcommerceTransactionId)) {
            /** @var \Pagcommerce\Payment\Model\Api\Pix $api */
            $api = $this->getObjectManager()->create(\Pagcommerce\Payment\Model\Api\Pix::class);

            $transaction = $api->sendRequest('payment-transaction/' . $pagcommerceTransactionId, array(), 'GET');
            if ($transaction && isset($transaction['id'])) {
                if ($this->getStatus($transaction['status']) == 'approved' || $transaction['status'] == 'in_analysis') {
                    if ($transaction['transaction_type'] == 'cc') {
                        $endPoint = $transaction['status'] == 'approved' ? 'payment-refund' : 'payment-cancel';
                        $response = $api->sendRequest($endPoint, array('transaction_id' => $transaction['id']));

                        if($endPoint == 'payment-cancel'){
                            if ($response && isset($response['canceled'])) {
                                if ($response['canceled']) {
                                    return $this;
                                } else {
                                    throw new \Magento\Framework\Exception\LocalizedException(
                                        __('Pagcommerce: A transação não pode ser cancelada.')
                                    );
                                }
                            }else{
                                throw new \Magento\Framework\Exception\LocalizedException(
                                    __('Pagcommerce: Ocorreu um erro ao cancelar a autorização de pagamento. Por favor tente novamente.')
                                );
                            }
                        }else{
                            if ($response && isset($response['refunded'])) {
                                if ($response['refunded']) {
                                    return $this;
                                } else {
                                    throw new \Magento\Framework\Exception\LocalizedException(
                                        __('Pagcommerce: A transação não pode ser estornada.')
                                    );
                                }
                            }else{
                                throw new \Magento\Framework\Exception\LocalizedException(
                                    __('Pagcommerce: Ocorreu um erro ao estornar o pagamento. Por favor tente novamente.')
                                );
                            }
                        }

                    } else {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('Pagcommerce: Transação não é cartão de crédito.')
                        );
                    }
                } else {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Pagcommerce: Transação não foi aprovada/recebida')
                    );
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Pagcommerce: Transação não encontrada')
                );
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Não é possível estornar o Pix. ID transação Pagcommerce inválido')
            );
        }

    }

    /**
     * @param $status
     * @return mixed
     */
    public function getStatus($status)
    {
        return $status;
    }

}
