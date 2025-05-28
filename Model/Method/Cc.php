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
                        ->setIsTransactionClosed(true)
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
        throw new \Magento\Framework\Exception\LocalizedException(
            __('We can\'t issue a refund transaction because there is no capture transaction.')
        );

        if($this->canRefund()){

            $order = $payment->getOrder();
            /** @var \Pagcommerce\Payment\Model\Api\Cc $apiCc */
            $apiCc = $this->getObjectManager()->create(\Pagcommerce\Payment\Model\Api\Cc::class);
            try{
                $paymentData = $payment->getAdditionalInformation();
                $pagcommerceTransactionId = $paymentData['pagcommerce_transaction_id'];
                try{
                    $response = $apiCc->refundOrder($pagcommerceTransactionId);
                    if($response){
                        /** @var Mage_Admin_Model_Session $session */
                        $session = Mage::getModel('admin/session');
                        /** @var Mage_Admin_Model_User $user */
                        $user = $session->getUser();
                        $order->addStatusHistoryComment('Transação estornada pelo usuário '.$user->getName().' - '.$user->getEmail());
                    }
                    return $this;
                }catch (\Exception $e){
                    throw new \Exception($e->getMessage());
                }

            }catch (\Exception $e){
                throw new \Exception($e->getMessage());
            }
        }


    }



}
