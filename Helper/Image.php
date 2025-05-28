<?php
namespace Pagcommerce\Payment\Helper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Helper\Context;

class Image extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context                $context,
        StoreManagerInterface  $storeManager
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }


    public function convertBase64ToImage($b64, \Magento\Sales\Model\Order $order){
        $b64 = str_replace('data:image/png;base64,', '', $b64);
        $bin = base64_decode($b64);
        $im = imageCreateFromString($bin);
        if (!$im) {
            return false;
        }

        $imgName = $order->getId().'-'.$order->getIncrementId().'-'.$order->getCustomerId().'.png';

        $imageSavePath = 'media'.DIRECTORY_SEPARATOR.'pagcommerce_payment';
        if(!is_dir($imageSavePath)){
            mkdir($imageSavePath);
        }

        $imageSavePath = $imageSavePath.DIRECTORY_SEPARATOR.$imgName;
        if(!is_file($imageSavePath)){
            imagepng($im, $imageSavePath, 0);
        }

        // UrlInterface::URL_TYPE_MEDIA ⇒ aponta para pub/media/
        $mediaBase = $this->storeManager
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return $mediaBase . 'pagcommerce_payment/' .$imgName;
    }

}


