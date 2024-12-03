<?php namespace CCVOnlinePayments\PaymentMethods;

class Card_Amex extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_card_amex";

    public function getDefaultTitle() {
        return __("American Express", 'ccvonlinepayments');
    }
}
