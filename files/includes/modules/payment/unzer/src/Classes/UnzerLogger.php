<?php

use Gambio\Core\Logging\LoggerBuilder;
use Psr\Log\LoggerInterface;

class UnzerLogger
{
    protected LoggingManager $logger;

    public function __construct(){
        require_once(DIR_FS_CATALOG.'includes/classes/class.logger.php');
        $this->logger =  new LoggingManager(DIR_FS_LOG.'mod_unzer.%s.log', 'unzer', 'debug');
    }
    public function debug($msg, array $data = []): void
    {
        $this->log('debug', $msg, $data);
    }

    public function warning($msg, array $data = []): void
    {
        $this->log('warning', $msg, $data);
    }

    public function error($msg, array $data = []): void
    {
        $this->log('error', $msg, $data);
    }

    public function log($level, $message, array $context = [])
    {
        $this->logger->log($level, $message, $context);
    }
}