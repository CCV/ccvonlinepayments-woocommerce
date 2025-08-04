<?php namespace CCVOnlinePayments\PaymentMethods;

class Googlepay extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_googlepay";

    public function getDefaultTitle() : string {
        return __("Google Pay", 'ccvonlinepayments');
    }
}
