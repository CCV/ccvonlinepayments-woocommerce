<?php namespace CCVOnlinePayments\PaymentMethods;

class Card_Maestro extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_card_maestro";

    public function getDefaultTitle() : string {
        return __("Maestro", 'ccvonlinepayments');
    }
}
