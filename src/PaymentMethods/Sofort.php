<?php namespace CCVOnlinePayments\PaymentMethods;

class Sofort extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_sofort";

    public function getDefaultTitle() : string {
        return __("Sofort", 'ccvonlinepayments');
    }
}
