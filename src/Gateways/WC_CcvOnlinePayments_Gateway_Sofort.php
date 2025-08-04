<?php
class WC_CcvOnlinePayments_Gateway_Sofort extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("sofort");
    }

    public function getDefaultTitle() : string {
        return __("Sofort", 'ccvonlinepayments');
    }
}
