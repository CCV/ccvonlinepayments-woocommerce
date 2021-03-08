<?php

use CCVOnlinePayments\Lib\Exception\ApiException;

abstract class WC_CcvOnlinePayments_Gateway extends WC_Payment_Gateway {

    public $methodId;

    public function __construct($method) {
        $this->methodId             = $method;
        $this->plugin_id            = '';
        $this->id                   = "ccvonlinepayments_".$method;

        if(file_exists(__DIR__."/../images/methods/".$method.".png")) {
            $this->icon             = plugin_dir_url(__DIR__)."images/methods/".$method.".png";
        }

        $title = $this->get_option('title', $this->getDefaultTitle());
        $this->has_fields           = false;
        $this->method_title         = "CCV Online Payments: ".$title;
        $this->method_description   = "";
        $this->enabled              = 'yes';
        $this->supports             = array('products', 'refunds');

        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $title;
        $this->description  = $this->get_option('description', $this->getDefaultDescription());

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    public function init_form_fields(){
        $this->form_fields = array(
            'title' => array(
                'title'       => __('Title', 'ccvonlinepayments'),
                'type'        => 'text',
                'default'     => "",
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'ccvonlinepayments'),
                'type'        => 'textarea',
                'default'     => "",
                'desc_tip'    => true,
            ),
        );
    }

    public function payment_fields()
    {
        parent::payment_fields();

        $method = WC_CCVOnlinePayments::get()->getMethodById($this->id);
        if($method === null) {
            return;
        }

        $html  = "<input type='hidden' name='ccvonlinepayments_issuerkey_".$this->methodId."' value='".esc_html($method->getIssuerKey())."'>";

        if($method->getIssuers() !== null) {
            $html .= "<select name='ccvonlinepayments_issuer_".$this->methodId."'>";

            if($method->getId() === "ideal") {
                $html .= "<option value=''>" . esc_html(__("Choose your bank...", "ccvonlinepayments")) . "</option>";
            }else{
                $html .= "<option></option>";
            }

            foreach($method->getIssuers() as $issuer) {
                $html .= "<option value='".esc_attr($issuer->getId())."'>".esc_html($issuer->getDescription())."</option>";
            }
        }

        $html .= "</select>";

        echo $html;
    }


    public function validate_fields() {

    }

    public function process_payment( $order_id ) {
        global $woocommerce, $wpdb;

        $order = new WC_Order( $order_id );
        $order->update_status('on-hold');

        $wpdb->show_errors(true);
        $wpdb->insert(
            $wpdb->prefix."ccvonlinepayments_payments",[
                "payment_reference" => null,
                "order_number"      => $order->get_order_number(),
                "status"            => \CCVOnlinePayments\Lib\PaymentStatus::STATUS_PENDING,
                "method"            => $this->methodId
            ]
        );
        $paymentId = $wpdb->insert_id;

        $paymentRequest = new \CCVOnlinePayments\Lib\PaymentRequest();

        $paymentRequest->setAmount($order->get_total());
        $paymentRequest->setCurrency($order->get_currency());
        $paymentRequest->setMerchantOrderReference("Order ".$order->get_order_number());

        $paymentRequest->setReturnUrl(add_query_arg(array(
            'order' => $order->get_order_number(),
            'key'   => $order->get_order_key(),
            'payment_id' => $paymentId
        ), WC()->api_request_url("ccvonlinepayments_return")));

        $paymentRequest->setWebhookUrl(add_query_arg(array(
            'order'      => $order->get_order_number(),
            'key'        => $order->get_order_key(),
            'payment_id' => $paymentId
        ), WC()->api_request_url("ccvonlinepayments_webhook")));

        $language = "eng";
        switch(explode("_", get_locale())[0]) {
            case "nl":  $language = "nld"; break;
            case "de":  $language = "deu"; break;
            case "fr":  $language = "fra"; break;
        }
        $paymentRequest->setLanguage($language);


        $paymentRequest->setMethod($this->methodId);
        $issuerKey = isset($_POST['ccvonlinepayments_issuerkey_'.$this->methodId]) ? $_POST['ccvonlinepayments_issuerkey_'.$this->methodId] : "";
        $issuer    = isset($_POST['ccvonlinepayments_issuer_'.$this->methodId]) ? $_POST['ccvonlinepayments_issuer_'.$this->methodId] : "";

        if($issuerKey === "issuerid") {
            $paymentRequest->setIssuer($issuer);
        }elseif($issuerKey === "brand") {
            $paymentRequest->setBrand($issuer);
        }

        $paymentRequest->setScaReady(true);

        $billingAddress = $order->get_address('billing');
        $paymentRequest->setBillingAddress($billingAddress['address_1']);
        $paymentRequest->setBillingCity($billingAddress['city']);
        $paymentRequest->setBillingPostalCode($billingAddress['postcode']);
        $paymentRequest->setBillingCountry($billingAddress['country']);
        $paymentRequest->setBillingState($billingAddress['state'] != "" ? $billingAddress['state'] : null);
        $paymentRequest->setBillingPhoneNumber($billingAddress['phone']);

        $shippingAddress = $order->get_address('billing');
        $paymentRequest->setShippingAddress($shippingAddress['address_1']);
        $paymentRequest->setShippingCity($shippingAddress['city']);
        $paymentRequest->setShippingPostalCode($shippingAddress['postcode']);
        $paymentRequest->setShippingCountry($shippingAddress['country']);
        $paymentRequest->setShippingState($shippingAddress['state'] != "" ? $shippingAddress['state'] : null);

        /** @var WP_User $userData */
        $userData = get_userdata($order->get_customer_id());
        $paymentRequest->setAccountInfoAccountIdentifier($order->get_customer_id());
        $paymentRequest->setAccountInfoAccountCreationDate(DateTime::createFromFormat('Y-m-d H:i:s', $userData->user_registered));
        $paymentRequest->setAccountInfoEmail($userData->user_email);
        $paymentRequest->setAccountInfoHomePhoneNumber($billingAddress['phone']);

        $paymentRequest->setBrowserFromServer();
        $paymentRequest->setBrowserUserAgent($order->get_customer_user_agent());
        $paymentRequest->setBrowserIpAddress($order->get_customer_ip_address());

        try {
            $paymentResponse = WC_CCVOnlinePayments::get()->getApi()->createPayment($paymentRequest);
        }catch(ApiException $apiException) {
            wc_add_notice("There was an unexpected error processing your payment" , 'error' );
            return [
                'result' => 'failure'
            ];
        }

        $wpdb->update(
            $wpdb->prefix."ccvonlinepayments_payments",[
                "payment_reference" => $paymentResponse->getReference(),
            ],
            [
                "payment_id" => $paymentId
            ]
        );

        $woocommerce->cart->empty_cart();

        return array(
            'result'   => 'success',
            'redirect' => $paymentResponse->getPayUrl()
        );
    }

    public function can_refund_order(WC_Order $order) : bool{
        $method = WC_CCVOnlinePayments::get()->getMethodById($this->id);
        if($method === null) {
            return false;
        }

        if(!$method->isRefundSupported()) {
            return false;
        }

        $paymentReference = $this->getPaymentReference($order);
        if($paymentReference === false) {
            return false;
        }

        return true;
    }

    public function process_refund(int $order_id, ?float $amount = null, string $reason = '') {
        $order = wc_get_order($order_id);

        if(!$order) {
            return false;
        }

        $paymentReference = $this->getPaymentReference($order);
        if($paymentReference === false) {
            return false;
        }

        $refundRequest = new \CCVOnlinePayments\Lib\RefundRequest();
        $refundRequest->setReference($paymentReference);

        if($amount !== null) {
            $refundRequest->setAmount($amount);
        }

        if($reason !== null) {
            $refundRequest->setDescription($reason);
        }

        try {
            $refundRequest = WC_CCVOnlinePayments::get()->getApi()->createRefund($refundRequest);
        }catch(ApiException $apiException) {
            return new WP_Error( 1, $apiException->getMessage() );
        }

        if($refundRequest->getReference()) {
            return true;
        }else{
            return false;
        }
    }

    private function getPaymentReference(WC_Order $order) {
        global $wpdb;
        $payment = $wpdb->get_row( $wpdb->prepare(
            'SELECT payment_reference FROM '.$wpdb->prefix.'ccvonlinepayments_payments WHERE order_number=%s', $order->get_order_number())
        );

        if($payment === null) {
            return false;
        }

        return $payment->payment_reference;
    }

    public abstract function getDefaultTitle();
    public function getDefaultDescription() {
        return "";
    }
}
