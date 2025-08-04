<?php namespace CCVOnlinePayments\PaymentMethods;

class Paypal extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_paypal";

    public function getDefaultTitle() : string {
        return __("Paypal", 'ccvonlinepayments');
    }
}
