<?php namespace CCVOnlinePayments\PaymentMethods;

class Klarna extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_klarna";

    public function getDefaultTitle() {
        return __("Klarna", 'ccvonlinepayments');
    }
}
