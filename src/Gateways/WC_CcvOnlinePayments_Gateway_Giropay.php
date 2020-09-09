<?php
class WC_CcvOnlinePayments_Gateway_Giropay extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("giropay");
        $this->has_fields = true;
    }

    public function getDefaultTitle() {
        return __("Giropay", 'ccvonlinepayments');
    }
}
