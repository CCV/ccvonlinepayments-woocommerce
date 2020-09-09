<?php
class WC_CcvOnlinePayments_Gateway_Payconiq extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("payconiq");
    }

    public function getDefaultTitle() {
        return __("Payconiq", 'ccvonlinepayments');
    }
}
