<?php

use CCVOnlinePayments\Lib\Exception\ApiException;

class WC_CCVOnlinePayments {

    private ?string $apiKey = null;
    private \CCVOnlinePayments\Lib\CcvOnlinePaymentsApi $api;

    /**
     * @var array<\CCVOnlinePayments\Lib\Method>|null
     */
    private ?array $cachedMethods = null;

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void {
        $this->apiKey = get_option("ccvonlinepayments_api_key");
        $this->api = new \CCVOnlinePayments\Lib\CcvOnlinePaymentsApi(
            new WC_CCVOnlinePayments_Cache($this->getPluginVersion()),
            new WC_CCVOnlinePayments_Logger(),
            $this->apiKey
        );

        global $wp_version, $woocommerce;
        $this->api->setMetadata([
            "CCVOnlinePayments" => $this->getPluginVersion(),
            "Wordpress"         => $wp_version,
            "Woocommerce"       => $woocommerce->version
        ]);
    }

    public function reconnectOnApiKeyChange(): void {
        if($this->apiKey !== get_option("ccvonlinepayments_api_key")) {
            $this->connect();
        }
    }

    private function getPluginVersion(): string {
        $pluginData = get_file_data(__DIR__."/ccvonlinepayments.php", ["Version" => "Version"]);
        return $pluginData['Version'];
    }

    /**
     * @param string $methodId
     * @return \CCVOnlinePayments\Lib\Method|null
     */
    public function getMethodById(string $methodId): ?\CCVOnlinePayments\Lib\Method {
        if($this->cachedMethods === null) {
            $this->cachedMethods = $this->api->getMethods();
        }

        foreach($this->cachedMethods as $method) {
            if("ccvonlinepayments_".$method->getId() === $methodId) {
                return $method;
            }
        }

        return null;
    }

    /**
     * @return \CCVOnlinePayments\Lib\CcvOnlinePaymentsApi
     */
    public function getApi(): \CCVOnlinePayments\Lib\CcvOnlinePaymentsApi {
        return $this->api;
    }

    private static ?WC_CCVOnlinePayments $ccvOnlinePaymentsSingleton = null;
    public static function initSingleton(): void {
        self::$ccvOnlinePaymentsSingleton = new WC_CCVOnlinePayments();
    }

    public static function get(): WC_CCVOnlinePayments {
        $singleton = self::$ccvOnlinePaymentsSingleton;
        if($singleton === null) {
            throw new RuntimeException("You have not initialized the singleton");
        }

        return $singleton;
    }

    public static function doWebhook(): void {
        self::handleCallback();
    }

    public static function doReturn(): void {
        list($order,$payment) = self::getOrderAndPayment();

        $gateway = wc_get_payment_gateway_by_order($order);

        if($gateway instanceof WC_Payment_Gateway) {
            $returnUrl = $gateway->get_return_url($order);
            $returnUrl = add_query_arg('ccvPaymentId', $payment->payment_id, $returnUrl);
            wp_safe_redirect($returnUrl);
        }
    }

    public static function doCatchThankYouPage(string $content): string {
        if(is_wc_endpoint_url('order-received') && !empty($_GET['ccvPaymentId'])) {
            $order_id = wc_get_order_id_by_order_key( $_GET[ 'key' ] );

            $order = wc_get_order( $order_id );

            if(!($order instanceof WC_Order)) {
                return $content;
            }

            global $wpdb;
            $payment = $wpdb->get_row( $wpdb->prepare(
                'SELECT payment_id, payment_reference, order_number FROM '.$wpdb->prefix.'ccvonlinepayments_payments WHERE payment_id=%s', $_GET['ccvPaymentId'])
            );

            if($payment !== null) {
                if ($payment->order_number != $order->get_order_number()) {
                    throw new \Exception("Invalid order number");
                }

                $retry = isset($_GET['ccvRetry']) ? intval($_GET['ccvRetry']) : 1;
                if($retry < 4 && !self::isOrderStatusCurrent($order, $payment)) {
                    return self::renderWaitingPage($retry);
                }
            }
        }

        return $content;
    }

    public static function renderWaitingPage(int $retry): string {
        $str  = "<h2>".esc_html(__("Please wait", 'ccvonlinepayments'))."</h2>";
        $str .= "<p>".esc_html(__("Your payment status is being processed.", 'ccvonlinepayments'))."</p>";

        global $wp;
        $redirectUrl = $_SERVER['REQUEST_URI'];
        $redirectUrl = add_query_arg("ccvRetry", $retry+1, $redirectUrl);
        $str .= "<script>setTimeout(function(){document.location=".wp_json_encode($redirectUrl).";},5000)</script>";

        return $str;
    }

    private static function isOrderStatusCurrent(WC_Order $order, stdClass $payment): bool {
        try {
            $paymentStatus = self::get()->getApi()->getPaymentStatus($payment->payment_reference);
        }catch(ApiException $apiException) {
            return true;
        }

        switch($paymentStatus->getStatus()) {
            case \CCVOnlinePayments\Lib\Enum\PaymentStatus::FAILED:
                return $order->get_status() === 'failed';
            case \CCVOnlinePayments\Lib\Enum\PaymentStatus::MANUAL_INTERVENTION:
                return $order->get_status() === 'on-hold';
            case \CCVOnlinePayments\Lib\Enum\PaymentStatus::SUCCESS:
                return $order->is_paid();
        }
        return true;
    }

    /**
     * @return array{0: WC_Order, 1: stdClass}
     */
    private static function getOrderAndPayment(): array {
        global $wpdb;

        $orderId = $_GET['order'];
        $order = wc_get_order($orderId);
        if(!($order instanceof WC_Order) || !$order->key_is_valid($_GET['key'])) {
            throw new \Exception("Invalid key");
        }
        $orderNumber = $order->get_order_number();

        $payment = $wpdb->get_row( $wpdb->prepare(
            'SELECT payment_id, payment_reference, order_number FROM '.$wpdb->prefix.'ccvonlinepayments_payments WHERE payment_id=%s', $_GET['payment_id'])
        );

        if($payment === null) {
            throw new \Exception("Payment not found");
        }

        if($payment->order_number != $orderNumber) {
            throw new \Exception("Invalid order number");
        }

        return [$order,$payment];
    }

    private static function handleCallback(): \WC_Order {
        global $wpdb;
        list($order,$payment) = self::getOrderAndPayment();

        $paymentStatus = self::get()->getApi()->getPaymentStatus($payment->payment_reference);
        switch($paymentStatus->getStatus()) {
            case \CCVOnlinePayments\Lib\Enum\PaymentStatus::FAILED:
                if(!$order->is_paid()) {
                    if($paymentStatus->getFailureCode() === \CCVOnlinePayments\Lib\Enum\PaymentFailureCode::CANCELLED) {
                        self::setNewStatus($order, 'failed', __("Payment was cancelled.", "ccvonlinepayments"));
                    }else{
                        self::setNewStatus($order, 'failed');
                    }
                }
                break;
            case \CCVOnlinePayments\Lib\Enum\PaymentStatus::MANUAL_INTERVENTION:
                if(!$order->is_paid()) {
                    self::setNewStatus($order, 'on-hold');
                }
                break;
            case \CCVOnlinePayments\Lib\Enum\PaymentStatus::SUCCESS:
                if(!$order->is_paid()) {
                    $order->payment_complete($payment->payment_reference);
                }
                break;
        }

        $wpdb->update(
            $wpdb->prefix."ccvonlinepayments_payments",
            [
                "status" => $paymentStatus->getStatus()?->value
            ],[
                "payment_reference" => $payment->payment_reference
            ]
        );

        return $order;
    }

    private static function setNewStatus(\WC_Order $order, string $newStatus, string $note = ''): void {
        if($order->get_status() === $newStatus) {
            return;
        }

        $order->update_status($newStatus, $note);
    }
}
