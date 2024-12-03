<?php namespace CCVOnlinePayments\PaymentMethods;

class Banktransfer extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_banktransfer";

    public function getDefaultTitle() {
        return __("Bank Transfer", 'ccvonlinepayments');
    }
}
