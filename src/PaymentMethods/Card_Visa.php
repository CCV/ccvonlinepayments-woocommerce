<?php namespace CCVOnlinePayments\PaymentMethods;

class Card_Visa extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_card_visa";

    public function getDefaultTitle() : string {
        return __("Visa", 'ccvonlinepayments');
    }
}
