<?php
namespace Pagcommerce\Payment\Model\Method;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Payment\Model\Method;
use Magento\Framework\Exception\CouldNotSaveException;
use function PHPUnit\Framework\throwException;


class Boleto extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = 'pagcommerce_payment_boleto';

    /**
     * @var string
     */
    protected $_infoBlockType = \Pagcommerce\Payment\Block\Info\Boleto::class;

    /** @return \Magento\Framework\App\ObjectManager */
    private function getObjectManager(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        return $objectManager;
    }

    public function initialize($paymentAction, $stateObject){
        //return parent::initialize($paymentAction, $stateObject);
        /** @var \Magento\Sales\Model\Order $order */
        $order   = $this->getInfoInstance()->getOrder();

        /** @var \Pagcommerce\Payment\Model\Api\Boleto $api */
        $api =  $this->getObjectManager()->create(\Pagcommerce\Payment\Model\Api\Boleto::class);

        $boletoResponse = $api->getBoletoResponse($order);
        if($boletoResponse){

            if (isset($pixResponse['payment_data'])) {
                $order->getPayment()->setAdditionalInformation($pixResponse['payment_data']);
                $order->getPayment()->setTransactionId($pixResponse['id'] . '-authorization')
                    ->setTxnType(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID)
                    ->setIsTransactionClosed(false)
                    ->setIsTransactionPending(true);
            } else {
                $message = $pixResponse['detail'] ?? 'Ocorreu um erro ao gerar o Boleto: '.$api->getErrors();
                throw new CouldNotSaveException(__($message));
            }

        }else{
            throw new CouldNotSaveException(
                __('Ocorreu um erro ao gerar o Boleto: '.$api->getErrors())
            );
        }

    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $infoInstance = $this->getInfoInstance();
        $currentData = $data->getAdditionalData();
        return $this;
    }

    public function isInitializeNeeded(){
        return true;
    }
}
