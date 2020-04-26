<?php

final class IWP_MMB_IThemes_Security extends IWP_MMB_Core {

    private static $available_modules;
    private static $confirmations = array();

    /**
     * @return array
     */
    function securityCheck() {
        self::checkIThemesModules();
        return array('security_check'=> self::$confirmations);
                self::checkIThemesModules();
    }

    function getLogCounts($from = false, $to = false ){
         if (!$from && !$to) {
            $from = strtotime('yesterday');
            $to = time();
        }
        $from = date('Y-m-d h:i:s', $from);
        $to = date('Y-m-d H:i:s', $to);
        $return['itheme_file_change'] = self::getFileChangeHistory($from, $to);
        $return['itheme_four_oh_four'] = self::getFourOhFourHistory($from, $to);
        $return['itheme_brute_force'] = self::getBruteForceHistory($from, $to);
        return $return;
    }

    /**
     * function for iThemes module security check
     * @return json
     */
    private static function checkIThemesModules() {
        global $itsec_globals;
        if (isset($itsec_globals['plugin_dir'])) {
            include_once $itsec_globals['plugin_dir'] . 'core/modules/security-check/scanner.php';
            self::$available_modules = ITSEC_Modules::get_available_modules();
            $settings = ITSEC_Modules::get_settings('network-brute-force');
            if (!empty($settings['api_key']) && !empty($settings['api_secret'])) {
                self::enforce_activation('network-brute-force', __('Network Brute Force Protection', 'better-wp-security'));
            } else {
                self::$confirmations['network-brute-force']['text'] = 'Network Brute Force Protection is enabled but not configured fully.';
                self::$confirmations['network-brute-force']['status'] = 'incomplete';
            }
            self::enforce_activation('ban-users', __('Banned Users', 'better-wp-security'));
            self::enforce_activation('backup', __('Database Backups', 'better-wp-security'));
            self::enforce_activation('brute-force', __('Local Brute Force Protection', 'better-wp-security'));
            self::enforce_activation('malware-scheduling', __('Malware Scan Scheduling', 'better-wp-security'));
            self::enforce_activation('strong-passwords', __('Strong Password Enforcement', 'better-wp-security'));
            self::enforce_activation('two-factor', __('Two-Factor Authentication', 'better-wp-security'));
            self::enforce_activation('user-logging', __('User Logging', 'better-wp-security'));
            self::enforce_activation('wordpress-tweaks', __('WordPress Tweaks', 'better-wp-security'));
        }
    }

    /**
     * @return void
     */
    private static function enforce_activation($module, $name) {
        if (!in_array($module, self::$available_modules)) {
            return;
        }

        if (ITSEC_Modules::is_active($module)) {
            /* Translators: 1: feature name */
            $text = __('%1$s is enabled as recommended.', 'better-wp-security');
            $status = 'active';
        } else {
            $text = __('%1$s is disabled.', 'better-wp-security');
            $status = 'inactive';
        }

        ob_start();
        echo sprintf($text, $name);
        self::$confirmations[$module]['text'] = ob_get_clean();
        self::$confirmations[$module]['status'] = $status;
    }

    /**
     * @return json
     */
    private static function getFileChangeHistory($from = null, $to = null) {
        $logs = self::get_logs('file_change', array(), null, null, null, null, $from, $to);
        return sizeof($logs);
    }

    /**
     * @return json
     */
    private static function getFourOhFourHistory($from = null, $to = null) {
        $logs = self::get_logs('four_oh_four', array(), null, null, null, null, $from, $to);
        return sizeof($logs);
    }

    /**
     * @return json
     */
    private static function getLockoutsHistory($from = null, $to = null) {
        $logs = self::get_logs('lockout', array(), null, null, null, null, $from, $to);
        return sizeof($logs);
    }

    /**
     * @return json
     */
    private static function getBruteForceHistory($from = null, $to = null) {
        $logs = self::get_logs('brute_force', array(), null, null, null, null, $from, $to);
        return sizeof($logs);
    }

    /**
     * Gets events from the logs for a specified module
     *
     * @param string $module    module or type of events to fetch
     * @param array  $params    array of extra query parameters
     * @param int    $limit     the maximum number of rows to retrieve
     * @param int    $offset    the offset of the data
     * @param string $order     order by column
     * @param bool   $direction false for descending or true for ascending
     * @param string $datefrom  date range start
     * @param string $dateto  date range end
     *
     * @return bool|mixed false on error, null if no events or array of events
     */
    private static function get_logs($module, $params = array(), $limit = null, $offset = null, $order = null, $direction = false, $datefrom = null, $dateto = null) {

        global $wpdb;

        if (isset($module) !== true || strlen($module) < 1) {
            return array('error' => 'The Module ' . $module . ' is not enabled', 'error_code' => 'requested_module_not_enabled');
        }

        if (sizeof($params) > 0 || $module != 'all' || isset($datefrom) || isset($dateto)) {
            $where = " WHERE ";
        } else {
            $where = '';
        }

        $param_search = '';

        if ($module == 'all') {

            $module_sql = '';
            $and = '';
        } else {

            $module_sql = "`module` = '" . esc_sql($module) . "' AND code != 'scan'";
            $and = ' AND ';
        }

        if ($direction === false) {

            $order_direction = ' DESC';
        } else {

            $order_direction = ' ASC';
        }

        if ($order !== null) {

            $order_statement = ' ORDER BY `' . esc_sql($order) . '`';
        } else {

            $order_statement = ' ORDER BY `id`';
        }

        if ($limit !== null) {

            if ($offset !== null) {

                $result_limit = ' LIMIT ' . absint($offset) . ', ' . absint($limit);
            } else {

                $result_limit = ' LIMIT ' . absint($limit);
            }
        } else {

            $result_limit = '';
        }

        if (sizeof($params) > 0) {

            foreach ($params as $field => $value) {

                if (gettype($value) != 'integer') {
                    $param_search .= $and . "`" . esc_sql($field) . "`='" . esc_sql($value) . "'";
                } else {
                    $param_search .= $and . "`" . esc_sql($field) . "`=" . esc_sql($value) . "";
                }
            }
        }
        $range_search = '';
        if (isset($datefrom) || isset($dateto)) {
            if (isset($datefrom)) {
                $range_search .= $and . " `init_timestamp` between '" . esc_sql($datefrom) . "'";
            }
            if (isset($dateto)) {
                $range_search .= $and . " '" . esc_sql($dateto) . "'";
            }
        }

        $items = $wpdb->get_results("SELECT * FROM `" . $wpdb->base_prefix . "itsec_logs`" . $where . $module_sql . $param_search . $range_search . $order_statement . $order_direction . $result_limit . ";", ARRAY_A);
        return $items;
    }

}
