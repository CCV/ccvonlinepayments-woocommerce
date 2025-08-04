<?php
class WC_CcvOnlinePayments_Gateway_Ideal extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("ideal");
    }

    public function getDefaultTitle() : string {
        return __("iDeal", 'ccvonlinepayments');
    }
}
