<?php

class WC_CCVOnlinePayments_Logger implements \Psr\Log\LoggerInterface {

    /** @var WC_Logger */
    private $logger;

    public function __construct()
    {
        $this->logger = wc_get_logger();
    }

    public function emergency($message, array $context = array())
    {
        $this->logger->emergency($this->contextToMessage($message, $context), []);
    }

    public function alert($message, array $context = array())
    {
        $this->logger->alert($this->contextToMessage($message, $context), []);
    }

    public function critical($message, array $context = array())
    {
        $this->logger->critical($this->contextToMessage($message, $context), []);
    }

    public function error($message, array $context = array())
    {
        $this->logger->error($this->contextToMessage($message, $context), []);
    }

    public function warning($message, array $context = array())
    {
        $this->logger->warning($this->contextToMessage($message, $context), []);
    }

    public function notice($message, array $context = array())
    {
        $this->logger->notice($this->contextToMessage($message, $context), []);
    }

    public function info($message, array $context = array())
    {
        $this->logger->info($this->contextToMessage($message, $context), []);
    }

    public function debug($message, array $context = array())
    {
        $this->logger->debug($this->contextToMessage($message, $context), []);
    }

    public function log($level, $message, array $context = array())
    {
        $this->logger->log($level, $this->contextToMessage($message, $context), []);
    }

    private function contextToMessage($message, $context) {
        foreach($context as $key => $value) {
            $message .= "\n$key: ".json_encode($value);
        }
        return $message;
    }


}
