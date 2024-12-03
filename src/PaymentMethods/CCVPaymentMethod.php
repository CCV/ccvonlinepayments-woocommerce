<?php namespace CCVOnlinePayments\PaymentMethods;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

abstract class CCVPaymentMethod extends AbstractPaymentMethodType {

    public function initialize() {
        $this->settings = get_option($this->name."_settings");
    }

    public abstract function getDefaultTitle();

    public function getMethodShortName() {
        return str_replace("ccvonlinepayments_", "", $this->name);
    }

    public function is_active() {
        return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            $this->name,
            '/wp-content/plugins/ccvonlinepayments/js/'.$this->getMethodShortName().'.js',
            [],
            '1.0.0',
            true
        );
        return [ $this->name ];
    }

    public function get_payment_method_data() {
        $method = $this->getMethodShortName();

        $icon = null;
        if(file_exists(__DIR__."/../images/methods/".$method.".png")) {
            $icon = plugin_dir_url(__DIR__)."images/methods/".$method.".png";
        }

        $title = empty($this->settings["title"]) ? $this->getDefaultTitle() : $this->settings["title"];

        return [
            'title'       => $title,
            'description' => $this->get_setting( 'description' ),
            'supports'    => $this->get_supported_features(),
            'icon'        => $icon,
        ];
    }
}
