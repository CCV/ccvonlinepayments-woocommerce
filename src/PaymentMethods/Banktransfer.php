<?php namespace CCVOnlinePayments\PaymentMethods;

class Banktransfer extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_banktransfer";

    public function getDefaultTitle() : string {
        return __("Bank Transfer", 'ccvonlinepayments');
    }
}
