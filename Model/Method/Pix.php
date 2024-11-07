<?php
namespace Pagcommerce\Payment\Model\Method;
use Magento\Payment\Model\Method;
use Magento\Framework\Exception\CouldNotSaveException;

class Pix extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = 'pagcommerce_payment_pix';

    /**
     * @var string
     */
    protected $_infoBlockType = \Pagcommerce\Payment\Block\Info\Pix::class;

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
            $order->getPayment()->setAdditionalInformation($pixResponse['payment_data']);
            $order->getPayment()->setTransactionId($pixResponse['id'] . '-authorization')
                ->setTxnType(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID)
                ->setIsTransactionClosed(false)
                ->setIsTransactionPending(true);
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


}
