<?php
class WC_CcvOnlinePayments_Gateway_Applepay extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("applepay");
    }

    public function getDefaultTitle() {
        return __("Apple Pay", 'ccvonlinepayments');
    }
}
