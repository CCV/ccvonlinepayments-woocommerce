<?php namespace CCVOnlinePayments\PaymentMethods;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

abstract class CCVPaymentMethod extends AbstractPaymentMethodType {

    public function initialize() : void {
        $this->settings = get_option($this->name."_settings");
    }

    public abstract function getDefaultTitle() : string;

    public function getMethodShortName() :string {
        return str_replace("ccvonlinepayments_", "", $this->name);
    }

    public function is_active() : bool {
        return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * @return array<string>
     */
    public function get_payment_method_script_handles() : array {
        wp_register_script(
            $this->name,
            '/wp-content/plugins/ccvonlinepayments/js/'.$this->getMethodShortName().'.js',
            [],
            '1.0.0',
            true
        );
        return [ $this->name ];
    }

    /**
     * @return array<string,mixed>
     */
    public function get_payment_method_data() : array {
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
