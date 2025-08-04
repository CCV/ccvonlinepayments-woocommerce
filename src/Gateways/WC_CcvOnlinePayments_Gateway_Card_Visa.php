<?php
class WC_CcvOnlinePayments_Gateway_Card_Visa extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("card_visa");
    }

    public function getDefaultTitle() : string {
        return __("Visa", 'ccvonlinepayments');
    }
}
