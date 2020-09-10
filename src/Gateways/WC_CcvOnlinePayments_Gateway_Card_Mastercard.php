<?php
class WC_CcvOnlinePayments_Gateway_Card_Mastercard extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("card_mastercard");
    }

    public function getDefaultTitle() {
        return __("Mastercard", 'ccvonlinepayments');
    }
}
