<?php
class WC_CcvOnlinePayments_Gateway_Paypal extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("paypal");
    }

    public function getDefaultTitle() {
        return __("Paypal", 'ccvonlinepayments');
    }
}
