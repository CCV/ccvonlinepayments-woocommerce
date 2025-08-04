<?php namespace CCVOnlinePayments\PaymentMethods;

class Card_Bcmc extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_card_bcmc";

    public function getDefaultTitle() : string {
        return __("Bancontact", 'ccvonlinepayments');
    }
}
