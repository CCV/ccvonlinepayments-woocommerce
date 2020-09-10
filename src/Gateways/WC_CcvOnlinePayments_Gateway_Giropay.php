<?php
class WC_CcvOnlinePayments_Gateway_Giropay extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("giropay");
    }

    public function getDefaultTitle() {
        return __("Giropay", 'ccvonlinepayments');
    }
}
