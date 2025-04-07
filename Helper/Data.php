<?php
namespace Pagcommerce\Payment\Helper;

use Magento\Framework\App\Config\Initial;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Model\Config;
use Magento\Payment\Model\Method\Factory;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Payment\Helper\Data
{
    public function __construct(Context $context, LayoutFactory $layoutFactory, Factory $paymentMethodFactory, Emulation $appEmulation, Config $paymentConfig, Initial $initialConfig)
    {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
    }

    /**
     * @param $config
     * @param string $group
     * @return mixed
     */
    public function getConfig($config, string $group = 'pagcommerce_payment')
    {
        return $this->scopeConfig->getValue(
            'payment/' . $group . '/' . $config,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getInterestsByTotal($orderTotal){

        $installmentsData = false;
        $configInstallments = $this->getConfig('installments', 'pagcommerce_payment_cc');
        if ($configInstallments !== '' && !is_null($configInstallments)){
            if (is_array($configInstallments)){
                $installmentsData = $configInstallments;
            }else{
                $installmentsData = json_decode($configInstallments, true);
            }
        }

        $installments = [];
        if($installmentsData){
            $parcelsAllowed = array();

            foreach($installmentsData as $installment){
                if($orderTotal >=  $installment['price_of'] && $orderTotal <=  $installment['price_up_to']){
                    $parcelsAllowed[$installment['installments']] = $installment;
                }
            }


            if($parcelsAllowed){
                $labelParcelWithFee =  $this->getConfig('label_install', 'pagcommerce_payment_cc');
                $labelParcelNoFee = $this->getConfig('label_nofee', 'pagcommerce_payment_cc');
                $feeType = $this->getConfig('fee_type', 'pagcommerce_payment_cc');

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                /** @var \Magento\Framework\Pricing\Helper\Data $currency */
                $currency = $objectManager->get(\Magento\Framework\Pricing\Helper\Data::class);

                foreach($parcelsAllowed as $key => $value){
                    $juros = (float)$value['fees'];
                    if($juros > 0){
                        //tem juros
                        if($feeType == \Pagcommerce\Payment\Model\Config\Source\Fee\Type::TYPE_PARCEL){
                            $jurosByParcel = ($orderTotal * ($juros/100)) * $value['fees'];
                        }else{
                            $jurosByParcel = ($orderTotal * ($juros/100));
                        }

                        $totalWithJuros = $orderTotal + $jurosByParcel;
                        $priceByParcel =  $totalWithJuros/$value['installments'];

                        $formated = $currency->currency($priceByParcel, true, false);

                        $label = $key.__('x de ').$formated;
                        if($labelParcelWithFee){
                            $tempValue = $currency->currency($totalWithJuros, true, false);
                            $labelParcel = sprintf(' '.$labelParcelWithFee, $tempValue);
                            $label.= $labelParcel;
                        }

                        $installments[$key] = array(
                            'parcel' => $key,
                            'price_byparcel' => $currency->currency($priceByParcel, true, false),
                            'label' => $label,
                            'total_with_interest' => $currency->currency($totalWithJuros, true, false)
                        );
                    }else{

                        //não tem juros
                        $priceByParcel =  $orderTotal/$key;
                        $formated = $currency->currency($priceByParcel, true, false);
                        $label = $key.__('x de ').$formated;

                        if($labelParcelNoFee && $key > 1){
                            $labelParcel = sprintf(' '.$labelParcelNoFee, $currency->currency($orderTotal, true, false));
                            $label.= $labelParcel;
                        }

                        $installments[$key] = array(
                            'parcel' => $key,
                            'price_byparcel' => $currency->currency($priceByParcel, true, false),
                            'label' => $label,
                            'total_with_interest' => $currency->currency($orderTotal, true, false)
                        );
                    }
                }
            }
        }
        return $installments;
    }
}


