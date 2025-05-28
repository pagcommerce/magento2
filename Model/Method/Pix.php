<?php
namespace Pagcommerce\Payment\Model\Method;
use _PHPStan_ce0aaf2bf\Nette\Neon\Exception;
use Magento\Payment\Model\Method;
use Magento\Framework\Exception\CouldNotSaveException;

class Pix extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = 'pagcommerce_payment_pix';

    /**
     * @var string
     */
    protected $_infoBlockType = \Pagcommerce\Payment\Block\Info\Pix::class;

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

        /** @var \Pagcommerce\Payment\Model\Api\Pix $api */
        $api =  $this->getObjectManager()->create(\Pagcommerce\Payment\Model\Api\Pix::class);

        $pixResponse = $api->getPixResponse($order);
        if($pixResponse){

            if (isset($pixResponse['payment_data'])) {
                $order->getPayment()->setAdditionalInformation($pixResponse['payment_data']);
//                $order->getPayment()->setTransactionId($pixResponse['id'] . '-authorization')
//                    ->setTxnType(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID)
//                    ->setIsTransactionClosed(false)
//                    ->setIsTransactionPending(true);

                $payment = $order->getPayment();
                $payment->setTransactionId($pixResponse['id'])
                    ->setIsTransactionClosed(false)
                    ->setShouldCloseParentTransaction(false);
                $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
//                $payment->save();

            } else {
                $message = $pixResponse['detail'] ?? 'Ocorreu um erro ao gerar o QR Code PIX: '.$api->getErrors();
                throw new CouldNotSaveException(__($message));
            }

        }else{
            throw new CouldNotSaveException(
                __('Ocorreu um erro ao gerar o QR Code PIX: '.$api->getErrors())
            );
        }

    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $infoInstance = $this->getInfoInstance();
        $currentData = $data->getAdditionalData();

//        $info = $this->getInfoInstance();
//        $info->setAdditionalInformation('method', $infoForm['method']);

        return $this;
    }

    public function isInitializeNeeded(){
        return true;
    }


    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $pagcommerceTransactionId = $payment->getParentTransactionId();
        if (isset($pagcommerceTransactionId)) {
            /** @var \Pagcommerce\Payment\Model\Api\Pix $api */
            $api = $this->getObjectManager()->create(\Pagcommerce\Payment\Model\Api\Pix::class);

            $transaction = $api->sendRequest('payment-transaction/' . $pagcommerceTransactionId, array(), 'GET');
            if ($transaction && isset($transaction['id'])) {
                if ($transaction['status'] == 'approved') {
                    if ($transaction['transaction_type'] == 'pix') {
                        $response = $api->sendRequest('payment-refund', array('transaction_id' => $transaction['id']));
                        if ($response && isset($response['refunded'])) {
                            if ($response['refunded']) {
                                return $this;
                            } else {
                                throw new \Magento\Framework\Exception\LocalizedException(
                                    __('Pagcommerce: A transação não pode ser estornada.')
                                );
                            }
                        }
                    } else {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('Pagcommerce: Transação não é Pix')
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



}
