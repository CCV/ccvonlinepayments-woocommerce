<?php

if ( class_exists('CCVPaymentsSettingsPage', false) ) {
    return;
}

class CCVPaymentsSettingsPage extends WC_Settings_Page {
    public function __construct() {
        $this->id = "ccvonlinepayments_settings";
        $this->label = __('CCV Payments', 'ccvonlinepayments');

        parent::__construct();
    }

    public function output() : void {
        try {
            $api = WC_CCVOnlinePayments::get()->getApi();
            $isKeyValid = $api->isKeyValid();
        }catch(\CCVOnlinePayments\Lib\Exception\ApiException $apiException) {
            if($apiException->getHttpStatusCode() === 403) {
                echo '<div id="ccvOnlinePaymentsApiKeyInvalidMessage" class="error inline"><p><strong>';
                echo esc_html(__('Your ip has been blocked. Please contact CCV.', 'ccvonlinepayments'));
                echo '</strong></p></div>';
            }else{
                echo '<div id="ccvOnlinePaymentsApiKeyInvalidMessage" class="error inline"><p><strong>';
                $errorString = __('An error (Http %d error) has occurred. Please try again later or contact CCV.', 'ccvonlinepayments');
                $errorString = sprintf($errorString, $apiException->getHttpStatusCode());
                echo esc_html($errorString);
                echo '</strong></p></div>';
            }

            parent::output();
            return;
        }

        if($isKeyValid) {
            echo '<div id="ccvOnlinePaymentsApiKeyValidMessage" class="updated inline"><p><strong>';
            echo esc_html(__('The api key is correct.', 'ccvonlinepayments'));
            echo '</strong></p></div>';
        }else{
            echo '<div id="ccvOnlinePaymentsApiKeyInvalidMessage" class="error inline"><p><strong>';
            echo esc_html(__('The api key is invalid.', 'ccvonlinepayments'));
            echo '</strong></p></div>';
        }

        parent::output();
    }

    /**
     * @return array<array<string,string>>
     */
    public function get_settings() : array {
        return array(
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
                'css'       => "width: 400px",
            ),
            array(
                'id'        => 'ccvonlinepayments_sectionend',
                'type'      => 'sectionend',
            )
        );
    }

}
