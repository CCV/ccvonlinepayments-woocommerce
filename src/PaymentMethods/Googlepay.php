<?php namespace CCVOnlinePayments\PaymentMethods;

class Googlepay extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_googlepay";

    public function getDefaultTitle() {
        return __("Google Pay", 'ccvonlinepayments');
    }
}
