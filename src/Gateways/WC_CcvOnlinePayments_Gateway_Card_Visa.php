<?php
class WC_CcvOnlinePayments_Gateway_Card_Visa extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("card_visa");
        $this->has_fields = false;
    }

    public function getDefaultTitle() {
        return __("Visa", 'ccvonlinepayments');
    }
}
