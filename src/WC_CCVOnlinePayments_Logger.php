<?php

class WC_CCVOnlinePayments_Logger implements \Psr\Log\LoggerInterface {

    private WC_Logger_Interface $logger;

    public function __construct()
    {
        $this->logger = wc_get_logger();
    }

    /**
     * @param Stringable|string $message
     */
    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($this->contextToMessage($message, $context), []);
    }

    /**
     * @param Stringable|string $message
     */
    public function alert($message, array $context = []): void
    {
        $this->logger->alert($this->contextToMessage($message, $context), []);
    }

    /**
     * @param Stringable|string $message
     */
    public function critical($message, array $context = []): void
    {
        $this->logger->critical($this->contextToMessage($message, $context), []);
    }

    /**
     * @param Stringable|string $message
     */
    public function error($message, array $context = []): void
    {
        $this->logger->error($this->contextToMessage($message, $context), []);
    }

    /**
     * @param Stringable|string $message
     */
    public function warning($message, array $context = []): void
    {
        $this->logger->warning($this->contextToMessage($message, $context), []);
    }

    /**
     * @param Stringable|string $message
     */
    public function notice($message, array $context = []): void
    {
        $this->logger->notice($this->contextToMessage($message, $context), []);
    }

    /**
     * @param Stringable|string $message
     */
    public function info($message, array $context = []): void
    {
        $this->logger->info($this->contextToMessage($message, $context), []);
    }

    /**
     * @param Stringable|string $message
     */
    public function debug($message, array $context = []): void
    {
        $this->logger->debug($this->contextToMessage($message, $context), []);
    }

    /**
     * @param Stringable|string $message
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->logger->log($level, $this->contextToMessage($message, $context), []);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function contextToMessage(string $message, array $context) : string{
        foreach($context as $key => $value) {
            $message .= "\n$key: ".json_encode($value);
        }
        return $message;
    }


}
