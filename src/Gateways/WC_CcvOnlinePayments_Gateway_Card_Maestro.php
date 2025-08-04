<?php
class WC_CcvOnlinePayments_Gateway_Card_Maestro extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("card_maestro");
    }

    public function getDefaultTitle() : string {
        return __("Maestro", 'ccvonlinepayments');
    }
}
