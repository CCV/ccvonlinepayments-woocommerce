<?php namespace CCVOnlinePayments\PaymentMethods;

class Card_Mastercard extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_card_mastercard";

    public function getDefaultTitle() {
        return __("Mastercard", 'ccvonlinepayments');
    }
}
