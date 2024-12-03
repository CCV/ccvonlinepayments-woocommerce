<?php namespace CCVOnlinePayments\PaymentMethods;

class Applepay extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_applepay";

    public function getDefaultTitle() {
        return __("Apple Pay", 'ccvonlinepayments');
    }
}
