<?php
/*
 * This plugin was modified by Revmakx                      
 * Copyright (c) 2012 Revmakx                               
 * www.revmakx.com                                          
 *  

/**
 * Taken from WordPress's automatic updater skin, which was added in version 3.7.
 *
 * @see Automatic_Upgrader_Skin
 */
class IWP_Updater_TraceableUpdaterSkin
{

    public $options = array(
        'url'     => '',
        'nonce'   => '',
        'title'   => '',
        'context' => false,
    );

    public $upgrader;

    public $result;

    public $done_header = false;

    protected $messages = array();

    private $startedImplicit = false;

    public function request_filesystem_credentials($error = false, $context = '', $allow_relaxed_file_ownership = false)
    {
        if ($error instanceof WP_Error) {
            return array('error' => $error->get_error_message(), 'error_code' => 'upgrade_plugins_wp_error');
        }

        if ($context) {
            $this->options['context'] = $context;
        }

        // file.php and template.php are documented to be required; the rest are there to match
        // the list in the MMB_Installer class.
        require_once ABSPATH.'wp-admin/includes/file.php';
        require_once ABSPATH.'wp-admin/includes/plugin.php';
        require_once ABSPATH.'wp-admin/includes/theme.php';
        require_once ABSPATH.'wp-admin/includes/misc.php';
        require_once ABSPATH.'wp-admin/includes/template.php';
        require_once ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
        // This will output a credentials form in event of failure; we don't want that, so just hide with a buffer.
        ob_start();
        /** @handled function */
        $result = request_filesystem_credentials('', '', $error, $context, null, $allow_relaxed_file_ownership);
        ob_end_clean();

        return $result;
    }

    public function get_upgrade_messages()
    {
        return $this->messages;
    }

    /**
     * @param string|array|WP_Error $data
     */
    public function feedback($data)
    {
        if (!$this->startedImplicit) {
            $this->startedImplicit = true;
            @ob_implicit_flush(true);
        }

        echo ' ';
        @ob_flush();

        if (is_wp_error($data)) {
            $string = $data->get_error_message();
        } else {
            if (is_array($data)) {
                return;
            } else {
                $string = $data;
            }
        }

        if (!empty($this->upgrader->strings[$string])) {
            $string = $this->upgrader->strings[$string];
        }

        if (strpos($string, '%') !== false) {
            $args = func_get_args();
            $args = array_splice($args, 1);
            if (!empty($args)) {
                $string = vsprintf($string, $args);
            }
        }

        $string = trim($string);

        /** @handled function */
        // Only allow basic HTML in the messages, as it'll be used in emails/logs rather than direct browser output.
        $string = wp_kses($string, array(
            'a'      => array(
                'href' => true
            ),
            'br'     => true,
            'em'     => true,
            'strong' => true,
        ));

        if (empty($string)) {
            return;
        }

        $this->messages[] = array(
            'message' => $string,
            'key'     => $data,
            'args'    => isset($args) ? $args : array(),
        );
    }

    public function header()
    {
        ob_start();
    }

    public function footer()
    {
        $output = ob_get_contents();
        if (!empty($output)) {
            $this->feedback($output);
        }
        ob_end_clean();
    }

    public function bulk_header()
    {
    }

    public function bulk_footer()
    {
    }

    public function before()
    {
    }

    public function after()
    {
    }

    // Below was taken from WP_Upgrader_Skin, so we don't autoload it and cause trouble.
    public function decrement_update_count()
    {
    }

    public function error($errors)
    {
        if (is_string($errors)) {
            $this->feedback($errors);

            return;
        }

        if (!$errors instanceof WP_Error || !$errors->get_error_code()) {
            return;
        }

        foreach ($errors->get_error_messages() as $message) {
            if ($errors->get_error_data() && is_string($errors->get_error_data())) {
                $this->feedback($message.' '.esc_html(strip_tags($errors->get_error_data())));
            } else {
                $this->feedback($message);
            }
        }
    }

    /**
     * @param WP_Upgrader $upgrader
     */
    public function set_upgrader($upgrader)
    {
        if (is_object($upgrader)) {
            $this->upgrader = $upgrader;
        }
        $this->add_strings();
    }

    public function add_strings()
    {
    }

    public function set_result($result)
    {
        $this->result = $result;
    }
}
