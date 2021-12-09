<?php
/*
 * Plugin Name: CCV Online Payments for Woocommerce
 * Plugin URI: https://github.com/CCV/ccvonlinepayments-woocommerce
 * Description: Official CCV Payment Services plugin for WooCommerce
 * Author: CCV Online Payments
 * Author URI: https://www.ccv.eu/nl/betaaloplossingen/betaaloplossingen-online/ccv-online-payments/
 * Version: 1.4.0
 * Requires at least: 5.4
 * Tested up to: 5.6
 * WC requires at least: 4.2
 * WC tested up to: 5.2
 */

const CCVONLINEPAYMENTS_MIN_PHP_VERSION  = "7.2.0";
const CCVONLINEPAYMENTS_DATABASE_VERSION = "3";
const CCVONLINEPAYMENTS_DATABASE_VERSION_PARAMETER_NAME = "ccvonlinepayments-db-version";

register_activation_hook(__FILE__, 'ccvonlinepayments_install');
function ccvonlinepayments_install() {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta('
        CREATE TABLE `'.$wpdb->prefix.'ccvonlinepayments_payments` (
            `payment_id`        INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `payment_reference` VARCHAR(64)  NULL,
            `order_number`      VARCHAR(64),
            `status`            VARCHAR(24),
            `method`            VARCHAR(24),
            `transaction_type`  VARCHAR(16) NULL,
            `capture_reference` VARCHAR(64) NULL
        ) DEFAULT CHARSET=utf8;
    ', true);

    update_option(CCVONLINEPAYMENTS_DATABASE_VERSION_PARAMETER_NAME, CCVONLINEPAYMENTS_DATABASE_VERSION);
}

add_filter( 'woocommerce_payment_gateways', 'ccvonlinepayments_add_gateway_class' );
function ccvonlinepayments_add_gateway_class( $gateways ) {
    foreach(\CCVOnlinePayments\Lib\CcvOnlinePaymentsApi::getSortedMethodIds(get_option( 'woocommerce_default_country')) as $gateway) {
        $gateways[] = 'WC_CcvOnlinePayments_Gateway_'.ucwords($gateway,"_");
    }

    return $gateways;
}

add_filter("woocommerce_payment_gateways_settings", 'ccvonlinepayments_add_generic_settings');
function ccvonlinepayments_add_generic_settings($settings) {
    $apiKeyStyle = "width: 400px";
    return array_merge($settings, array(
        array(
            'id'        => 'ccvonlinepayments_title',
            'title'     => __('CCV Online Payments Settings', 'ccvonlinepayments'),
            'type'      => 'title'
        ),
        array(
            'id'        => 'ccvonlinepayments_api_key',
            'title'     => __('API key', 'ccvonlinepayments'),
            'default'   => '',
            'type'      => 'text',
            'css'       => $apiKeyStyle,
        ),
        array(
            'id'        => 'ccvonlinepayments_sectionend',
            'type'      => 'sectionend',
        )
    ));
}

add_action("woocommerce_api_ccvonlinepayments_webhook", array(WC_CCVOnlinePayments::class, "doWebhook"));
add_action("woocommerce_api_ccvonlinepayments_return", array(WC_CCVOnlinePayments::class, "doReturn"));

add_action( 'plugins_loaded', 'ccvonlinepayments_init' );
function ccvonlinepayments_init() {
    if(version_compare(PHP_VERSION, CCVONLINEPAYMENTS_MIN_PHP_VERSION, '<')) {
        add_action('admin_notices', 'ccvonlinepayments_incompatible_php_version');
        return;
    }

    if(!extension_loaded('curl')) {
        add_action('admin_notices', 'ccvonlinepayments_missing_curl');
        return;
    }

    if(get_option(CCVONLINEPAYMENTS_DATABASE_VERSION_PARAMETER_NAME, '') != CCVONLINEPAYMENTS_DATABASE_VERSION){
        ccvonlinepayments_install();
    }

    global $woocommerce;
    if(!isset($woocommerce->version)) {
        add_action('admin_notices', 'ccvonlinepayments_woocommerce_not_installed');
        return;
    }

    add_action('init', function() {
        load_plugin_textdomain('ccvonlinepayments', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    });

    if(file_exists(__DIR__."/vendor/autoload.php")) {
        require __DIR__ . "/vendor/autoload.php";
    }else{
        require __DIR__ . "/../vendor/autoload.php";
    }
    require __DIR__."/WC_CCVOnlinePayments_Cache.php";
    require __DIR__."/WC_CCVOnlinePayments_Logger.php";
    require __DIR__."/WC_CCVOnlinePayments.php";

    WC_CCVOnlinePayments::initSingleton();

    require __DIR__."/Gateways/WC_CcvOnlinePayments_Gateway.php";
    foreach(\CCVOnlinePayments\Lib\CcvOnlinePaymentsApi::getSortedMethodIds() as $gateway) {
        require __DIR__."/Gateways/WC_CcvOnlinePayments_Gateway_".ucwords($gateway,"_").".php";
    }

    add_filter('woocommerce_payment_gateways', 'ccvonlinepayments_disable_gateways', 20);

    // Cancel order
    add_action( 'woocommerce_order_status_cancelled', 'ccvonlinepayments_cancel_order' );

    // Capture order
    add_action( 'woocommerce_order_status_completed', 'ccvonlinepayments_capture_order' );
}

function ccvonlinepayments_incompatible_php_version() {
    if(!is_admin()) {
        return false;
    }

    echo '<div class="error"><p>';
    echo esc_html__(
            'CCV OnlinePayments requires PHP '.CCVONLINEPAYMENTS_MIN_PHP_VERSION.' or higher. Your PHP version is outdated. Upgrade your PHP version.'
        );
    echo '</p></div>';

    return false;
}

function ccvonlinepayments_missing_curl() {
    if(!is_admin()) {
        return false;
    }

    echo '<div class="error"><p>';
    echo esc_html__(
        'CCV OnlinePayments requires the curl php extension.'
        );
    echo '</p></div>';

    return false;
}

function ccvonlinepayments_woocommerce_not_installed() {
    if(!is_admin()) {
        return false;
    }

    echo '<div class="error"><p>';
    echo esc_html__(
        'CCV OnlinePayments requires WooCommerce to function.'
    );
    echo '</p></div>';

    return false;
}

function ccvonlinepayments_disable_gateways(array $gateways) {
    $api = WC_CCVOnlinePayments::get()->getApi();

    $methodsAllowed = [];
    if($api->isKeyValid()) {
        foreach($api->getMethods() as $method) {
            if($method->isCurrencySupported(get_woocommerce_currency())) {
                $methodsAllowed[$method->getId()] = true;
            }
        }
    }

    foreach($gateways as $key => $gateway) {
        if(is_string($gateway) && strpos($gateway, "WC_CcvOnlinePayments_Gateway_") === 0) {
            $methodId = strtolower(str_replace("WC_CcvOnlinePayments_Gateway_","", $gateway));

            if(!isset($methodsAllowed[$methodId])) {
                unset($gateways[$key]);
            }
        }
    }

    return $gateways;
}

function ccvonlinepayments_get_orderlines_by_order($order) {
    $orderLines = [];
    foreach($order->get_items(['line_item', 'shipping', 'fee']) as $orderItem) {
        $orderLine = new \CCVOnlinePayments\Lib\OrderLine();
        if($orderItem instanceof \WC_Order_Item_Product) {
            $orderLine->setType(\CCVOnlinePayments\Lib\OrderLine::TYPE_PHYSICAL);

            if ( $orderItem->get_variation_id() ) {
                $product = wc_get_product( $orderItem->get_variation_id() );
            } else {
                $product = wc_get_product( $orderItem->get_product_id() );
            }

            if($product !== false) {
                if($product->is_virtual()) {
                    $orderLine->setType(\CCVOnlinePayments\Lib\OrderLine::TYPE_DIGITAL);
                }
            }
        }elseif($orderItem instanceof \WC_Order_Item_Shipping) {
            $orderLine->setType(\CCVOnlinePayments\Lib\OrderLine::TYPE_SHIPPING_FEE);
        }elseif($orderItem instanceof \WC_Order_Item_Fee) {
            $orderLine->setType(\CCVOnlinePayments\Lib\OrderLine::TYPE_SURCHARGE);
        }else{
            throw new \Exception("OrderLine not supported");
        }

        $orderLine->setName($orderItem->get_name());
        $orderLine->setQuantity($orderItem->get_quantity());
        $orderLine->setUnitPrice(($orderItem->get_total() + $orderItem->get_total_tax())/$orderItem->get_quantity());
        $orderLine->setTotalPrice($orderItem->get_total() + $orderItem->get_total_tax());
        $orderLine->setVat($orderItem->get_total_tax());
        $orderLines[] = $orderLine;
    }

    return $orderLines;
}

function ccvonlinepayments_cancel_order($order_id) {
    global $wpdb;

    $order = new WC_Order( $order_id );

    if(strpos($order->get_payment_method(),"ccvonlinepayments_") !== 0) {
        return;
    }

    $payment = $wpdb->get_row( $wpdb->prepare(
        'SELECT payment_reference, transaction_type FROM '.$wpdb->prefix.'ccvonlinepayments_payments WHERE order_number=%s', $order->get_order_number())
    );
    if($payment->transaction_type !== \CCVOnlinePayments\Lib\PaymentRequest::TRANSACTION_TYPE_AUTHORIZE) {
        return;
    }

    $api = WC_CCVOnlinePayments::get()->getApi();
    $paymentStatus = $api->getPaymentStatus($payment->payment_reference);
    if($paymentStatus->getStatus() === \CCVOnlinePayments\Lib\PaymentStatus::STATUS_SUCCESS) {
        $reversalRequest = new \CCVOnlinePayments\Lib\ReversalRequest();
        $reversalRequest->setReference($payment->payment_reference);
        $api->createReversal($reversalRequest);
    }
}

function ccvonlinepayments_capture_order($order_id) {
    global $wpdb;

    $order = new WC_Order( $order_id );

    if(strpos($order->get_payment_method(),"ccvonlinepayments_") !== 0) {
        return;
    }

    $payment = $wpdb->get_row( $wpdb->prepare(
        'SELECT payment_reference, transaction_type FROM '.$wpdb->prefix.'ccvonlinepayments_payments WHERE order_number=%s', $order->get_order_number())
    );
    if($payment->transaction_type !== \CCVOnlinePayments\Lib\PaymentRequest::TRANSACTION_TYPE_AUTHORIZE) {
        return;
    }

    $method = WC_CCVOnlinePayments::get()->getMethodById($order->get_payment_method());

    $api = WC_CCVOnlinePayments::get()->getApi();
    $paymentStatus = $api->getPaymentStatus($payment->payment_reference);
    if($paymentStatus->getStatus() === \CCVOnlinePayments\Lib\PaymentStatus::STATUS_SUCCESS) {
        $captureRequest = new \CCVOnlinePayments\Lib\CaptureRequest();
        $captureRequest->setReference($payment->payment_reference);
        $captureRequest->setAmount($order->get_total());

        if($method->isOrderLinesRequired()) {
            $captureRequest->setOrderLines(ccvonlinepayments_get_orderlines_by_order($order));
        }

        $captureResponse = $api->createCapture($captureRequest);

        $wpdb->update(
            $wpdb->prefix."ccvonlinepayments_payments",[
            "capture_reference" => $captureResponse->getReference(),
        ],[
            "order_number" => $order->get_order_number()
        ]);
    }
}
