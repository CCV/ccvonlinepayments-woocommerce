<?php
class WC_CcvOnlinePayments_Gateway_Eps extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("eps");
    }

    public function getDefaultTitle() : string {
        return __("Eps", 'ccvonlinepayments');
    }
}
