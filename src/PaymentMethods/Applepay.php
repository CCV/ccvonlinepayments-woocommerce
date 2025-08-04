<?php namespace CCVOnlinePayments\PaymentMethods;

class Applepay extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_applepay";

    public function getDefaultTitle() : string {
        return __("Apple Pay", 'ccvonlinepayments');
    }
}
