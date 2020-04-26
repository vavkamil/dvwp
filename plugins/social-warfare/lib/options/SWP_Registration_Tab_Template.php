<?php

class SWP_Registration_Tab_Template extends SWP_Option {
    public $key = '';
    public $license_key = '';
    public $product_id = 0;
    public $registered = 0;

    public function __construct( $addon ) {
        parent::__construct( $addon->name, $addon->key );
        $this->display_name = $addon->name;
        $this->key = $addon->key;
        $this->license_key = $this->get_license_key();
        $this->product_id = $addon->product_id;
        $this->version = $addon->version;
    }


    public function render_HTML() {
        if ( !empty( $this->license_key ) ) :
            $this->registered = 1;
        endif;

        $html = '<div class="registration-wrapper '. $this->key . '" registration="' . $this->registered . '">';
            $html .= '<h2>' . __($this->name . ' Registration', 'social-warfare') . '</h2>';

            //* Print both types of HTML. Javascript determines which to display.
            $html .= $this->not_registered_HTML();
            $html .= $this->is_registered_HTML();

        $html .= '</div>';

        $this->html = $html;

        return $html;
    }

    public function get_license_key() {
        $license = $this->key . '_license_key';

        if ( isset( $this->user_options[$license] ) ) {
            return $this->user_options[$license];
        }

        return '';
    }


    /**
     * Pass ajax responses to a remote HTTP request.
     *
     * @since  2.0.0
     * @return void
     */
    function ajax_passthrough() {
    	if ( ! check_ajax_referer( 'swp_plugin_registration', 'security', false ) ) {
    		wp_send_json_error( esc_html__( 'Security failed.', 'social-warfare' ) );
    		die;
    	}

    	$data = wp_unslash( $_POST ); // Input var okay.

    	if ( ! isset( $data['activity'], $data['email'] ) ) {
    		wp_send_json_error( esc_html__( 'Required fields missing.', 'social-warfare' ) );
    		die;
    	}

    	if ( 'register' === $data['activity'] ) {
    		$response = $this->register_plugin( $data['email'], SWP_Utility::get_site_url() );

    		if ( ! $response ) {
    			wp_send_json_error( esc_html__( 'Plugin could not be registered.', 'social-warfare' ) );
    			die;
    		}

    		$response['message'] = esc_html__( 'Plugin successfully registered!', 'social-warfare' );
    	}

    	if ( 'unregister' === $data['activity'] && isset( $data['key'] ) ) {
    		$response = $this->unregister_plugin( $data['email'], $data['key'] );

    		if ( ! $response ) {
    			wp_send_json_error( esc_html__( 'Plugin could not be unregistered.', 'social-warfare' ) );
    			die;
    		}

    		$response['message'] = esc_html__( 'Plugin successfully unregistered!', 'social-warfare' );
    	}

    	wp_send_json_success( $response );

    	die;
    }


    protected function not_registered_HTML() {
        $html = '<div class="sw-grid sw-col-940 swp_is_not_registered">';

            $html .= '<div class="sw-red-notice">';
                $html .=  __( 'This copy of '. $this->name .' is NOT registered. <a target="_blank" href="https://warfareplugins.com">Click here</a> to purchase a license or add your account info below.' , 'social-warfare' );
            $html .= '</div>';

            $html .= '<p class="sw-subtitle sw-registration-text">';
                $html .= __( 'Enter your registration key for '. $this->name .' and then click Register Plugin.' , 'social-warfare' );
            $html .= '</p>';

            $html .= '<div class="sw-grid sw-col-300">';
                $html .= '<p class="sw-input-label">';
                    $html .= __( $this->name . ' License Key' , 'social-warfare' );
                $html .= '</p>';
            $html .= '</div>';

            $html .= '<div class="sw-grid sw-col-300">';
                $html .= '<input name="' . $this->key . '_license_key" type="text" class="sw-admin-input" placeholder="License Key" value="' . $this->license_key . '" />';
            $html .= '</div>';

            $html .= '<div class="sw-grid sw-col-300 sw-fit register_button_grid">';
                $html .= '<a href="#" class="register-plugin button sw-navy-button" swp-addon="' . $this->key . '"  swp-item-id="' . $this->product_id . '">';
                    $html .= __( 'Register Plugin' , 'social-warfare' );
                $html .= '</a>';
            $html .= '</div>';

            $html .= '<div class="sw-clearfix"></div>';
        $html .= '</div>';

        return $html;
    }

    protected function is_registered_HTML() {
        ob_start();

        ?>

        <div class="sw-grid sw-col-940 swp_is_registered">

            <div class="sw-green-notice">
                <?php _e( 'This copy of '. $this->name .' is registered. Wah-hoo!', 'social-warfare' ); ?>
            </div>

            <p class="sw-subtitle sw-registration-text">
                <?php _e( 'To unregister your license click the button below to free it up for use on another domain.' , 'social-warfare' ); ?>
            </p>

            <div class="sw-grid sw-col-300">
                <p class="sw-authenticate-label">
                    <?php _e( 'Deactivate Registration' , 'social-warfare' ); ?>
                </p>
            </div>

            <div class="sw-grid sw-col-300">
                <a href="#" class="unregister-plugin button sw-navy-button" swp-addon="<?php echo $this->key ?>"  swp-item-id="<?php echo $this->product_id; ?>">
                    <?php _e( 'Unregister Plugin' , 'social-warfare' ); ?>
                </a>
            </div>
            <div class="sw-grid sw-col-300 sw-fit"></div>

        </div>

        <?php

        $html = ob_get_clean();

        return $html;
    }
}
