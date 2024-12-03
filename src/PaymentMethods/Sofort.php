<?php namespace CCVOnlinePayments\PaymentMethods;

class Sofort extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_sofort";

    public function getDefaultTitle() {
        return __("Sofort", 'ccvonlinepayments');
    }
}
