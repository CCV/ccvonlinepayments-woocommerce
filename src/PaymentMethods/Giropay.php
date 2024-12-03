<?php namespace CCVOnlinePayments\PaymentMethods;

class Giropay extends CCVPaymentMethod {

    protected $name = "ccvonlinepayments_giropay";

    public function getDefaultTitle() {
        return __("Giropay", 'ccvonlinepayments');
    }
}
