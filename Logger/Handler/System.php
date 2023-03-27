<?php

namespace Pagcommerce\Payment\Logger\Handler;
use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Logger;

class System extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/pagcommerce.log';

    public function __construct(DriverInterface $filesystem, ?string $filePath = null, ?string $fileName = null)
    {
        $today = date('d-m-Y');
        $this->fileName = '/var/log/pagcommerce-'.$today.'.log';
        parent::__construct($filesystem, $filePath, $fileName);
    }
}

