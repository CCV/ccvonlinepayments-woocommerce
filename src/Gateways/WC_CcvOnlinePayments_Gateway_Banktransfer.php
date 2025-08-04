<?php
class WC_CcvOnlinePayments_Gateway_Banktransfer extends WC_CcvOnlinePayments_Gateway {

    public function __construct()
    {
        parent::__construct("banktransfer");
    }

    public function getDefaultTitle() : string {
        return __("Bank Transfer", 'ccvonlinepayments');
    }
}
