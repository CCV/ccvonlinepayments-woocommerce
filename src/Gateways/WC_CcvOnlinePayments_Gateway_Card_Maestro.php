<?php
class WC_CcvOnlinePayments_Gateway_Card_Maestro extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("card_maestro");
        $this->has_fields = false;
    }

    public function getDefaultTitle() {
        return __("Maestro", 'ccvonlinepayments');
    }
}
