<?php namespace CCVOnlinePayments\PaymentMethods;

class Card_Maestro extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_card_maestro";

    public function getDefaultTitle() {
        return __("Maestro", 'ccvonlinepayments');
    }
}
