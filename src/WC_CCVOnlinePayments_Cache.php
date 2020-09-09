<?php

class WC_CCVOnlinePayments_Cache extends CCVOnlinePayments\Lib\Cache {

    private $keyPrefix;

    public function __construct(string $keyPrefix = "")
    {
        $this->keyPrefix = $keyPrefix;
    }

    public function set(string $key, string $value, int $timeout): void
    {
        set_transient($this->keyPrefix.$key, $value, $timeout);
    }

    public function get(string $key): ?string
    {
        $value = get_transient($this->keyPrefix.$key);
        if($value === false) {
            return null;
        }else{
            return $value;
        }
    }

}
