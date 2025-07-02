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
register_deactivation_hook( __FILE__, 'gs_lea_uninstall');

function gs_lea_install() {
    global $wpdb;

    // evergreen_login_rewrite_rule();
    add_rewrite_rule(
        '^login-evergreen/([^/]*)/?',
        'index.php?pagename=login-evergreen&library_number=$matches[1]',
        'top'
    );
    flush_rewrite_rules();

    // Get database charset collation
    $charset_collate = $wpdb->get_charset_collate();
    // Create a gs_library_cards table if it doesn't exist
    $create_table_query = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "gs_library_cards` (
                                                        `ID` bigint(20) NOT NULL AUTO_INCREMENT,
                                                        `library_number` bigint(20) NOT NULL default 0,
                                                        `card_number` varchar(30) DEFAULT '' NOT NULL,
                                                        `expired_at` bigint(20) NOT NULL,
                                                        PRIMARY KEY (`ID`)
                                                        ) $charset_collate;";

    $wpdb->query($create_table_query);
}

function gs_lea_uninstall() {
    global $wpdb;

    flush_rewrite_rules();
    $drop_table_query = "DROP TABLE IF EXISTS `" . $wpdb->prefix . "gs_library_cards`;";
    $wpdb->query($drop_table_query);
}

// function evergreen_login_rewrite_rule() {
//     add_rewrite_rule(
//         '^login-evergreen/([^/]*)/?',
//         'index.php?pagename=login-evergreen&library_number=$matches[1]',
//         'top'
//     );
// }
// add_action('init', 'evergreen_login_rewrite_rule', 10, 0);

function add_query_vars($vars) {
    $vars[] = 'library_number';
    return $vars;
}
add_filter('query_vars', 'add_query_vars');

Class Grantselect_Library_Evergreen_Auth {

    public function __construct() {
        global $wpdb;
        $this->table_subscriptionmeta   = $wpdb->prefix . "pms_member_subscriptionmeta";
        $this->table_subscriptions      = $wpdb->prefix . "pms_member_subscriptions";
        $this->table_owner_statistics   = $wpdb->prefix . "gs_owner_statistics";
        $this->table_subscriber_logs    = $wpdb->prefix . 'gs_subscriber_logs';
        $this->table_library_cards      = $wpdb->prefix . "gs_library_cards";
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

        $library_number = get_query_var('library_number');
        if (empty($library_number)) {
            return "<p>Please select the proper library ID.</p>";
        }

        $member = pms_get_member($library_number);   // $library_number is equal to $user_id here
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

        $query = "select meta_key, meta_value from {$wpdb->prefix}usermeta where user_id={$library_number} AND meta_key IN ('evergreen-sip2-domain', 'evergreen-sip2-username', 'evergreen-sip2-password')";
        $sip2_rows = $wpdb->get_results($query);
        if (count($sip2_rows) == 0) {
            return "<p>This library does not contain Evergreen information.</p>";
        }

        $card_number = isset($_POST['libcardnum']) ? sanitize_text_field($_POST['libcardnum']) : '';
        if (!empty($card_number)) {
            if (
                isset($_SESSION['card_number']) &&
                ($_SESSION['card_number'] == $card_number) &&
                isset($_SESSION['library_number']) &&
                ($_SESSION['library_number'] == $library_number)
            ) {
                $time_now = time();
                $query = "select * from {$this->table_library_cards} where library_number={$library_number} AND card_number={$card_number} AND expired_at > {$time_now}";
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
                $card_number,
                $sip2_credentials['evergreen-sip2-domain'],
                GS_LEA_EVERGREEN_PORT,
                $sip2_credentials['evergreen-sip2-username'],
                $sip2_credentials['evergreen-sip2-password']
            );

            if (!empty($result['error'])) {
                return "<p>{$result['error']}</p>";
            }

            $where = [
                'library_number'    => $library_number,
                'card_number'       => $card_number
            ];
            $wpdb->delete($this->table_library_cards, $where, ['%d', '%s']);

            $row = $where;
            $row['expired_at'] = time() + 86400;
            $wpdb->insert(
                $this->table_library_cards,
                $row
            );

            // add the login library log
            $this->add_library_login_log($library_number);

            $_SESSION['library_number']     = $library_number;
            $_SESSION['card_number']        = $card_number;

            wp_redirect(home_url("/access/"));
            exit;
        }

        ob_start();
        if (file_exists(GS_LEA_PLUGIN_DIR_PATH . 'templates/gs_library_login_form.php')){
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

    public function add_library_login_log($library_number) {
        global $wpdb;
        
        $sid = 0;
        $subscription_id = 0;
        $subscription = null;
        $member = pms_get_member($library_number);
        if ($member->subscriptions) {
            foreach($member->subscriptions as $subscript) {
                $plan = pms_get_subscription_plan($subscript['subscription_plan_id']);
                if ($plan->type != 'group')
                    continue;
                $subscription = pms_get_member_subscription($subscript['id']);
                $subscription_id = $subscript['id'];
                break;
            }
        }
        if ($subscription_id != 0) {
            if (!pms_gm_is_group_owner($subscription_id)) {
                $owner_subscribermeta = $wpdb->get_row(
                    $wpdb->prepare("SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_owner' AND member_subscription_id=%s", array($subscription_id))
                );
                $owner_subscriber_id = $owner_subscribermeta->meta_value;
            } else {
                $owner_subscriber_id = $subscription_id;
            }

            $row = [];
            $owner_subscriber = $wpdb->get_row(
                $wpdb->prepare("SELECT user_id FROM {$this->table_subscriptions} WHERE id=%s", array($owner_subscriber_id))
            );

            $row['manager_id']      = $owner_subscriber->user_id;
            $row['manager_name']    = pms_get_member_subscription_meta($owner_subscriber_id, 'pms_group_name', true);

            $row['user_id']         = 0;
            $row['user_name']       = "";

            $row['ip']              = $this->get_the_user_ip();
            $row['created_at']      = date("Y-m-d H:i:s");
            $row['url']             = home_url($_SERVER['REQUEST_URI']);

            $row['status']          = 0;
            $row['sid']             = $sid;
            $row['content']         = 'login';

            $wpdb->insert(
                $this->table_subscriber_logs,
                $row
            );

            $owner_statistics = $wpdb->get_row(
                $wpdb->prepare("SELECT id, count FROM {$this->table_owner_statistics} WHERE owner_sid=%d", array($owner_subscriber_id))
            );
            if ($owner_statistics == null) {
                $wpdb->insert(
                    $this->table_owner_statistics,
                    array(
                        'owner_sid' => $owner_subscriber_id,
                        'owner_name'=> $row['manager_name'],
                        'count'     => 1
                    )
                );
            } else {
                $wpdb->update(
                    $this->table_owner_statistics,
                    array(
                        'owner_name'=> $row['manager_name'],
                        'count'     => $owner_statistics->count + 1
                    ),
                    array(
                        'owner_sid' => $owner_subscriber_id,
                    )
                );
            }
        } else {
            if ($member->subscriptions) {
                foreach ($member->subscriptions as $subscript) {
                    $plan = pms_get_subscription_plan($subscript['subscription_plan_id']);
                    $subscription = pms_get_member_subscription($subscript['id']);
                    $subscription_id = $subscript['id'];
                    break;
                }
            }

            $owner_subscriber_id = $subscription_id;
            $row = [];
            $owner_subscriber = $wpdb->get_row(
                $wpdb->prepare("SELECT user_id FROM {$this->table_subscriptions} WHERE id=%s", array($owner_subscriber_id))
            );

            $row['manager_id']      = $owner_subscriber->user_id;
            $row['manager_name']    = pms_get_member_subscription_meta($owner_subscriber_id, 'pms_group_name', true);
            $row['user_id']         = 0;
            $row['user_name']       = "";

            $row['ip']              = $this->get_the_user_ip();
            $row['created_at']      = date("Y-m-d H:i:s");
            $row['url']             = home_url($_SERVER['REQUEST_URI']);

            $row['status']          = 0;
            $row['sid']             = $sid;
            $row['content']         = 'login';
            
            $wpdb->insert(
                $this->table_subscriber_logs,
                $row
            );
        }
    }

    public function get_the_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}

new Grantselect_Library_Evergreen_Auth;

