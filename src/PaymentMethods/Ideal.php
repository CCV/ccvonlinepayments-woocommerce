<?php namespace CCVOnlinePayments\PaymentMethods;

class Ideal extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_ideal";

    public function getDefaultTitle() {
        return __("iDeal", 'ccvonlinepayments');
    }
}
