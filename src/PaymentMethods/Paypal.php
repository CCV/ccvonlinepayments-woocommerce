<?php namespace CCVOnlinePayments\PaymentMethods;

class Paypal extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_paypal";

    public function getDefaultTitle() {
        return __("Paypal", 'ccvonlinepayments');
    }
}
