<?php
namespace Pagcommerce\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Filesystem\DirectoryList;

class Image extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    protected DirectoryList $directoryList;

    public function __construct(
        Context                $context,
        StoreManagerInterface  $storeManager,
        DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
    }


    /**
     * @throws NoSuchEntityException
     * @throws FileSystemException
     */
    public function convertBase64ToImage($b64, \Magento\Sales\Model\Order $order){
        $b64 = str_replace('data:image/png;base64,', '', $b64);
        $bin = base64_decode($b64);
        $im = imageCreateFromString($bin);
        if (!$im) {
            return false;
        }

        $imgName = $order->getId().'-'.$order->getIncrementId().'-'.$order->getCustomerId().'.png';

        // Caminho absoluto até pub/media/pagcommerce_payment
        $mediaDir = $this->directoryList->getPath(DirectoryList::MEDIA);
        $imageSavePath  = $mediaDir . DIRECTORY_SEPARATOR . 'pagcommerce_payment';

        if (!is_dir($imageSavePath)) {
            mkdir($imageSavePath, 0775, true); // cria com permissão e recursivo
        }

        //$imageSavePath = 'media'.DIRECTORY_SEPARATOR.'pagcommerce_payment';
        //if(!is_dir($imageSavePath)){
            //mkdir($imageSavePath);
        //}

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


