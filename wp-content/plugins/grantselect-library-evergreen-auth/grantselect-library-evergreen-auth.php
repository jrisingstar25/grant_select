<?php
defined( 'ABSPATH' ) or die();
/*
Plugin Name: Grantselect Library Evergreen Auth
Plugin URI: https://www.magimpact.com/
Description: Functions related to allow the library to login automatically using Evergreen Auth API.
Version: 1.0.0
Author: magIMPACT
Author URI: https://www.magimpact.com/
*/
define( 'GS_LEA_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GS_LEA_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'GS_LEA_EVERGREEN_PORT', 6001);

register_activation_hook(__FILE__, 'gs_lea_install');

function gs_lea_install() {
    global $wpdb;

    evergreen_login_rewrite_rule();
    flush_rewrite_rules();

    // Get database charset collation
    $charset_collate = $wpdb->get_charset_collate();
    // Create a gs_library_cards table if it doesn't exist
    $create_table_query = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "gs_library_cards` (
                                                        `ID` bigint(20) NOT NULL AUTO_INCREMENT,
                                                        `user_id` bigint(20) NOT NULL default 0,
                                                        `card_number` varchar(30) DEFAULT '' NOT NULL,
                                                        `expired_at` bigint(20) NOT NULL,
                                                        PRIMARY KEY (`ID`)
                                                        ) $charset_collate;";

    $wpdb->query($create_table_query);
}

function evergreen_login_rewrite_rule() {
    add_rewrite_rule(
        '^login-evergreen/([^/]*)/?',
        'index.php?pagename=login-evergreen&lib_id=$matches[1]',
        'top'
    );
}
add_action('init', 'evergreen_login_rewrite_rule', 10, 0);

function add_query_vars($vars) {
    $vars[] = 'lib_id';
    return $vars;
}
add_filter('query_vars', 'add_query_vars');

Class Grantselect_Library_Evergreen_Auth {

    public function __construct() {
        global $wpdb;
        $this->table_library_cards   = $wpdb->prefix . "gs_library_cards";
        $this->init();
    }

    private function init() {
        add_shortcode("gs-evergreen-login", array($this, "gs_evergreen_login"));
    }
    
    public function gs_evergreen_login($atts) {
        global $wpdb;

        if (!session_id()) {
            session_start();
        }

        if (is_user_logged_in()) {
            wp_redirect(home_url("/access/"));
            exit;
        }

        $user_id = get_query_var('lib_id');
        if (empty($user_id)) {
            return "<p>Please select the proper library ID.</p>";
        }

        $member = pms_get_member($user_id);
        $active_status = false;
        foreach ($member->subscriptions as $subscript) {
            if ($subscript['status'] == 'active') {
                $active_status = true;
                break;
            }
        }

        if (!$active_status) {
            return "<p>This library's subscription is not active.</p>";
        }

        $query = "select meta_key, meta_value from {$wpdb->prefix}usermeta where user_id={$user_id} AND meta_key IN ('evergreen-sip2-domain', 'evergreen-sip2-institution', 'evergreen-sip2-username', 'evergreen-sip2-password')";
        // echo($wpdb->prepare("select meta_key, meta_value from {$wpdb->prefix}usermeta where user_id=%s AND meta_key IN ('evergreen-sip2-domain', 'evergreen-sip2-institution', 'evergreen-sip2-password')", $inst_id));
        $sip2_rows = $wpdb->get_results($query);
        if (count($sip2_rows) == 0) {
            return "<p>This library does not contain Evergreen information.</p>";
        }

        $lib_card_number = isset($_POST['libcardnum']) ? sanitize_text_field($_POST['libcardnum']) : '';
        if (!empty($lib_card_number)) {
            if (
                isset($_SESSION['library_card_expired_at']) &&
                isset($_SESSION['library_card_num']) &&
                ($_SESSION['library_card_num'] == $lib_card_number) &&
                isset($_SESSION['library_user_id']) &&
                ($_SESSION['library_user_id'] == $user_id)
            ) {
                $expired_at = $_SESSION['library_card_expired_at'];
                $query = "select * from {$this->table_library_cards} where user_id={$user_id} AND card_number={$lib_card_number} AND expired_at >= {$expired_at}";
                $card_rows = $wpdb->get_results($query);
                if (count($card_rows) > 0) {
                    $_SESSION['library_user_action'] = 'access';
                    wp_redirect(home_url("/access/"));
                    exit;
                }
            }

            $sip2_credentials = [];
            foreach ($sip2_rows as $sip2r) {
                $sip2_credentials[$sip2r->meta_key] = $sip2r->meta_value;
            }

            $result = $this->validate_sip2_card(
                $lib_card_number,
                $sip2_credentials['evergreen-sip2-domain'],
                GS_LEA_EVERGREEN_PORT,
                $sip2_credentials['evergreen-sip2-username'],
                $sip2_credentials['evergreen-sip2-password']
            );

            if (!empty($result['error'])) {
                return "<p>{$result['error']}</p>";
            }

            $where = [
                'user_id'       => $user_id,
                'card_number'   => $lib_card_number
            ];
            $wpdb->delete($this->table_library_cards, $where, ['%d', '%s']);

            $row = $where;
            $expired_at = time() + 86400;
            $row['expired_at'] = $expired_at;
            $wpdb->insert(
                $this->table_library_cards,
                $row
            );

            $_SESSION['library_card_num'] = $lib_card_number;
            $_SESSION['library_user_id'] = $user_id;
            $_SESSION['library_user_action'] = 'login';
            $_SESSION['library_card_expired_at'] = $expired_at;

            wp_redirect(home_url("/access/"));
            exit;
        }

        ob_start();
        if( file_exists( GS_LEA_PLUGIN_DIR_PATH . 'templates/gs_library_login_form.php' ) ){
            include GS_LEA_PLUGIN_DIR_PATH . 'templates/gs_library_login_form.php';
        }
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function validate_sip2_card($card_number, $sip_host, $sip_port, $login_user = '', $login_pass = '') {
        $socket = fsockopen($sip_host, $sip_port, $errno, $errstr, 10);
        if (!$socket) {
            return ['error' => "Connection to Evergreen domain failed."];
        }

        // Example login message (9300 = login message, CN=login user, CO=login password)
        $login_msg = "9300CN{$login_user}|CO{$login_pass}|\r";
        fwrite($socket, $login_msg);
        $login_response = fgets($socket); // Read login response

        if ($login_response == 941) {   // 941 response is a successful login to the SIP2 server

            // Create Patron Status message (23)
            // Format: 23<language><transaction date>AO<login username>|AA<patron identifier>|AC<terminal password>|
            $transaction_date = date('Ymd    His');  // the 4 spaces between "Ymd" and "His" are important
            $msg = "23000" . $transaction_date . "AO{$login_user}|AA{$card_number}|AC{$login_pass}|\r";

            fwrite($socket, $msg);
            $card_valid_response = fgets($socket);
            fclose($socket);
        } else {
            fclose($socket);
            return ['error' => 'Login username or password is incorrect.'];
        }


        // SIP2 response starts with 24 if successful Patron Status response
        if (strpos($card_valid_response, '|BLY|') !== false) {
            // "|BLY|" in the response indicates that the card number matches an existing card number
            return ['error' => null];
        }

        return ['error' => 'You are using a bad card.'];
    }
}

new Grantselect_Library_Evergreen_Auth;

