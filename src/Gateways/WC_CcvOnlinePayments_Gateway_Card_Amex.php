<?php
class WC_CcvOnlinePayments_Gateway_Card_Amex extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("card_amex");
        $this->has_fields = false;
    }

    public function getDefaultTitle() {
        return __("American Express", 'ccvonlinepayments');
    }
}
