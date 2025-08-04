<?php namespace CCVOnlinePayments\PaymentMethods;

class Card_Eps extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_eps";

    public function getDefaultTitle() : string {
        return __("Eps", 'ccvonlinepayments');
    }
}
