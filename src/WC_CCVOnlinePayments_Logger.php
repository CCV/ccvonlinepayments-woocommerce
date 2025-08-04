<?php

class WC_CCVOnlinePayments_Logger implements \Psr\Log\LoggerInterface {

    private WC_Logger_Interface $logger;

    public function __construct()
    {
        $this->logger = wc_get_logger();
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->logger->emergency($this->contextToMessage($message, $context), []);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->logger->alert($this->contextToMessage($message, $context), []);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->logger->critical($this->contextToMessage($message, $context), []);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->logger->error($this->contextToMessage($message, $context), []);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->logger->warning($this->contextToMessage($message, $context), []);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->logger->notice($this->contextToMessage($message, $context), []);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->logger->info($this->contextToMessage($message, $context), []);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->logger->debug($this->contextToMessage($message, $context), []);
    }

    public function log(mixed $level, Stringable|string $message, array $context = []): void
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
