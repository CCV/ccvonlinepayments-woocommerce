<?php namespace CCVOnlinePayments\PaymentMethods;

class Ideal extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_ideal";

    public function getDefaultTitle() : string {
        return __("iDeal", 'ccvonlinepayments');
    }
}
