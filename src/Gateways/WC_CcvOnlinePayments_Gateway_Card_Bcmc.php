<?php
class WC_CcvOnlinePayments_Gateway_Card_Bcmc extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("card_bcmc");
        $this->has_fields = false;
    }

    public function getDefaultTitle() {
        return __("Bancontact", 'ccvonlinepayments');
    }
}
