<?php
class WC_CcvOnlinePayments_Gateway_Card_Bcmc extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("card_bcmc");
    }

    public function getDefaultTitle() : string {
        return __("Bancontact", 'ccvonlinepayments');
    }
}
