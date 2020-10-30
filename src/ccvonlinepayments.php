<?php
/*
 * Plugin Name: CCV Online Payments for Woocommerce
 * Plugin URI: https://github.com/CCV-Group-B-V/ccvonlinepayments-wordpress
 * Description: WooCommerce plugin for CCV Online Payments
 * Author: CCV Online Payments
 * Author URI: https://www.ccvshop.nl/ccv-online-payments
 * Version: 1.0.2
 * Requires at least: 5.4
 * Tested up to: 5.5
 * WC requires at least: 4.2
 * WC tested up to: 4.4
 */

const CCVONLINEPAYMENTS_MIN_PHP_VERSION = "7.2.0";

register_activation_hook(__FILE__, 'ccvonlinepayments_install');
function ccvonlinepayments_install() {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta('
        CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'ccvonlinepayments_payments` (
            `payment_id`        INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `payment_reference` VARCHAR(64)  NULL,
            `order_number`      VARCHAR(64),
            `status`            VARCHAR(24),
            `method`            VARCHAR(24)
        ) DEFAULT CHARSET=utf8;
    ', true);
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

    global $woocommerce;
    if(!isset($woocommerce->version)) {
        add_action('admin_notices', 'ccvonlinepayments_woocommerce_not_installed');
        return;
    }

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
        if(strpos($gateway, "WC_CcvOnlinePayments_Gateway_") === 0) {
            $methodId = strtolower(str_replace("WC_CcvOnlinePayments_Gateway_","", $gateway));

            if(!isset($methodsAllowed[$methodId])) {
                unset($gateways[$key]);
            }
        }
    }

    return $gateways;
}
