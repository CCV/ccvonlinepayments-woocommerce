<?php

use CCVOnlinePayments\Lib\Exception\ApiException;

abstract class WC_CcvOnlinePayments_Gateway extends WC_Payment_Gateway {

    public string $methodId;

    public function __construct(string $method) {
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

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'updateOptions' ) );
    }

    public function init_form_fields() : void {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'woocommerce' ),
                'type' => 'checkbox',
                'label' => __( 'Enable', 'woocommerce' )." ".$this->getDefaultTitle(),
                'default' => 'yes'
            ),
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

    public function payment_fields() : void
    {
        parent::payment_fields();

        $method = WC_CCVOnlinePayments::get()->getMethodById($this->id);
        if($method === null) {
            return;
        }

        $html  = "<input type='hidden' name='ccvonlinepayments_issuerkey_".$this->methodId."' value='".esc_html($method->getIssuerKey()??"")."'>";

        if($method->getId() !== "ideal" && $method->getIssuers() !== null) {
            $html .= "<select name='ccvonlinepayments_issuer_".$this->methodId."'>";

            if($method->getId() === "ideal") {
                $html .= "<option value=''>" . esc_html(__("Choose your bank...", "ccvonlinepayments")) . "</option>";
            }else{
                $html .= "<option></option>";
            }

            foreach($method->getIssuers() as $issuer) {
                $html .= "<option value='".esc_attr($issuer->getId())."'>".esc_html($issuer->getDescription()??"")."</option>";
            }
        }

        $html .= "</select>";

        echo $html;
    }


    public function validate_fields() : bool {
        return true;
    }

    /**
     * @param int $order_id
     * @return array<string|int, mixed>
     */
    public function process_payment($order_id) : array {
        global $wpdb;

        $order = new WC_Order( $order_id );

        if($order->get_status() !== "pending") {
            $order->update_status("pending");
        }

        $method = WC_CCVOnlinePayments::get()->getMethodById($this->id);
        if($method === null) {
            wc_add_notice("There was an unexpected error processing your payment" , 'error' );
            return [
                'result' => 'failure'
            ];
        }

        if($method->isTransactionTypeSaleSupported()) {
            $transactionType = \CCVOnlinePayments\Lib\Enum\TransactionType::SALE;
        }elseif($method->isTransactionTypeAuthoriseSupported()){
            $transactionType = \CCVOnlinePayments\Lib\Enum\TransactionType::AUTHORIZE;
        }else{
            throw new \Exception("No transaction types supported");
        }

        $wpdb->show_errors(true);
        $wpdb->insert(
            $wpdb->prefix."ccvonlinepayments_payments",[
                "payment_reference" => null,
                "order_number"      => $order->get_order_number(),
                "status"            => \CCVOnlinePayments\Lib\Enum\PaymentStatus::PENDING,
                "method"            => $this->methodId,
                "transaction_type"  => $transactionType->value
            ]
        );
        $paymentId = $wpdb->insert_id;

        $paymentRequest = new \CCVOnlinePayments\Lib\PaymentRequest();

        $paymentRequest->setTransactionType($transactionType);
        $paymentRequest->setAmount($order->get_total());
        $paymentRequest->setCurrency($order->get_currency());
        $paymentRequest->setMerchantOrderReference("Order ".$order->get_order_number());

        $paymentRequest->setReturnUrl(add_query_arg(array(
            'order' => $order->get_id(),
            'key'   => $order->get_order_key(),
            'payment_id' => $paymentId
        ), WC()->api_request_url("ccvonlinepayments_return")));

        $paymentRequest->setWebhookUrl(add_query_arg(array(
            'order'      => $order->get_id(),
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

        /** @var ?array<string|int, mixed> $billingAddress */
        $billingAddress = $order->get_address('billing');
        if($billingAddress !== null) {
            $paymentRequest->setBillingAddress($billingAddress['address_1']);
            $paymentRequest->setBillingCity($billingAddress['city']);
            $paymentRequest->setBillingPostalCode($billingAddress['postcode']);
            $paymentRequest->setBillingCountry($billingAddress['country'] != "" ? $billingAddress['country'] : null);
            $paymentRequest->setBillingEmail($billingAddress['email']);
            $paymentRequest->setBillingState($billingAddress['state'] != "" ? $billingAddress['state'] : null);
            $paymentRequest->setBillingPhoneNumber($billingAddress['phone']);
            $paymentRequest->setBillingFirstName($billingAddress['first_name']);
            $paymentRequest->setBillingLastName($billingAddress['last_name']);
        }

        /** @var ?array<string|int, mixed> $shippingAddress */
        $shippingAddress = $order->get_address('shipping');
        if($shippingAddress !== null) {
            $paymentRequest->setShippingAddress($shippingAddress['address_1']);
            $paymentRequest->setShippingCity($shippingAddress['city']);
            $paymentRequest->setShippingPostalCode($shippingAddress['postcode']);
            $paymentRequest->setShippingCountry($shippingAddress['country'] != "" ? $shippingAddress['country'] : null);
            $paymentRequest->setShippingState($shippingAddress['state'] != "" ? $shippingAddress['state'] : null);
            $paymentRequest->setShippingEmail($shippingAddress['email'] ?? $paymentRequest->getBillingEmail());
            $paymentRequest->setShippingFirstName($shippingAddress['first_name']);
            $paymentRequest->setShippingLastName($shippingAddress['last_name']);
        }

        if($order->get_customer_id() > 0) {
            $paymentRequest->setAccountInfoAccountIdentifier(strval($order->get_customer_id()));

            /** @var false|WP_User $userData */
            $userData = get_userdata($order->get_customer_id());
            if($userData !== false) {
                $userRegistered = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $userData->user_registered);
                if($userRegistered !== false) {
                    $paymentRequest->setAccountInfoAccountCreationDate($userRegistered);
                }
                $paymentRequest->setAccountInfoEmail($userData->user_email);
            }
        }

        if($billingAddress !== null) {
            $paymentRequest->setAccountInfoHomePhoneNumber($billingAddress['phone'] ?? null);
        }

        $paymentRequest->setBrowserFromServer();
        $paymentRequest->setBrowserUserAgent($order->get_customer_user_agent());
        $paymentRequest->setBrowserIpAddress($order->get_customer_ip_address());

        if($method->isOrderLinesRequired()) {
            $paymentRequest->setOrderLines(ccvonlinepayments_get_orderlines_by_order($order));
        }

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

        return array(
            'result'   => 'success',
            'redirect' => $paymentResponse->getPayUrl()
        );
    }

    public function can_refund_order($order) : bool{
        $method = WC_CCVOnlinePayments::get()->getMethodById($this->id);
        if($method === null) {
            return false;
        }

        if(!$method->isRefundSupported()) {
            return false;
        }

        $paymentReference = $this->getReferenceForRefund($order);
        if($paymentReference === false) {
            return false;
        }

        return true;
    }

    /**
     * @param int $order_id
     * @param ?float $amount
     * @param string $reason
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '' ) {
        $order = wc_get_order($order_id);

        if(!($order instanceof WC_Order)) {
            return false;
        }

        $paymentReference = $this->getReferenceForRefund($order);
        if($paymentReference === false) {
            return false;
        }

        $method = WC_CCVOnlinePayments::get()->getMethodById($this->id);
        if($method === null) {
            return false;
        }

        if($method->isOrderLinesRequired() && $amount != $order->get_total()) {
            return false;
        }

        $refundRequest = new \CCVOnlinePayments\Lib\RefundRequest();
        $refundRequest->setReference($paymentReference);

        if($amount !== null) {
            $refundRequest->setAmount($amount);
        }
        $refundRequest->setDescription($reason);

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

    private function getReferenceForRefund(WC_Order $order) : string|false {
        global $wpdb;
        $payment = $wpdb->get_row( $wpdb->prepare(
            'SELECT payment_reference, transaction_type, capture_reference FROM '.$wpdb->prefix.'ccvonlinepayments_payments WHERE status="success" AND order_number=%s', $order->get_order_number())
        );

        if($payment === null) {
            return false;
        }

        if($payment->transaction_type === \CCVOnlinePayments\Lib\Enum\TransactionType::AUTHORIZE->value) {
            if($payment->capture_reference) {
                return $payment->capture_reference;
            }else{
                return false;
            }
        }else {
            return $payment->payment_reference;
        }
    }

    public abstract function getDefaultTitle() : string;
    public function getDefaultDescription() : string{
        return "";
    }

    public function updateOptions() : void {
        parent::process_admin_options();
    }
}
