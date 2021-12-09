<?php
class WC_CcvOnlinePayments_Gateway_Klarna extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("klarna");
    }

    public function getDefaultTitle() {
        return __("Klarna", 'ccvonlinepayments');
    }
}
