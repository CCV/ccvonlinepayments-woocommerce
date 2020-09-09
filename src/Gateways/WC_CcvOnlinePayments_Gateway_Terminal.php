<?php
class WC_CcvOnlinePayments_Gateway_Terminal extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("terminal");
    }

    public function getDefaultTitle() {
        return __("Terminal (instore solution)", 'ccvonlinepayments');
    }
}
