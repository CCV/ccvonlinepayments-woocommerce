<?php
class WC_CcvOnlinePayments_Gateway_Googlepay extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("googlepay");
    }

    public function getDefaultTitle() {
        return __("Google Pay", 'ccvonlinepayments');
    }
}
