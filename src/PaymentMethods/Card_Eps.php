<?php namespace CCVOnlinePayments\PaymentMethods;

class Card_Eps extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_eps";

    public function getDefaultTitle() {
        return __("Eps", 'ccvonlinepayments');
    }
}
