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
define( 'GS_LEA_EVERGREEN_PORT', 6001 );
define( 'GS_LEA_EXPIRY_HOUR', 1 );
define( 'GS_LEA_INACTIVE_ACCOUNT_HOURS', 24 );
define( 'GS_LEA_CURRENT_URL', (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

const GS_LEA_SIP2_RES_CODE = array(
    1 => 'Success!',
    2 => 'Sorry, your card number is invalid. <a href="'.GS_LEA_CURRENT_URL.'">Please try again',
    3 => 'The registered SIP2 auth information seems incorrect.',
    4 => 'Sorry, GrantSelect failed to connect to SIP2 server. Please try again later.'
);

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

function add_query_vars($vars) {
    $vars[] = 'library_number';
    return $vars;
}
add_filter('query_vars', 'add_query_vars');

add_action('wp', function() {
    if (!wp_next_scheduled('gs_lea_cron_hook')) {
        wp_schedule_event(time(), 'daily', 'gs_lea_cron_hook');
    }
});

add_action('gs_lea_cron_hook', 'remove_inactive_accounts');

function remove_inactive_accounts() {
    global $wpdb;

    $last_used_timestamp = time() - GS_LEA_INACTIVE_ACCOUNT_HOURS * 86400;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}gs_library_cards WHERE expired_at < %s",
            $last_used_timestamp
        )
    );
}

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
        add_filter( 'wppb_output_fields_filter', array($this, 'add_library_login_url'), 10, 1 );
        add_action('wp_enqueue_scripts', array($this, 'user_notice_message_enqueue_scripts'));
        wp_enqueue_style('gs_admin_fe_css', GS_LEA_PLUGIN_DIR_URL . '/css/message-render.css');
    }

    public function add_library_login_url($output) {
        global $wpdb;

        $subscription_id = 0;
        $active_status = false;
        $user_id = get_current_user_id();
        if ($_GET['edit_user']) {
            $user_id = $_GET['edit_user'];
        }
        $member = pms_get_member($user_id);
        if ($member->subscriptions != NULL) {
            foreach ($member->subscriptions as $subscript) {
                if ($subscript['status'] == 'active')
                    $active_status = true;

                $plan = pms_get_subscription_plan($subscript['subscription_plan_id']);
                if ($plan->type != 'group')
                    continue;

                $subscription = pms_get_member_subscription($subscript['id']);
                $subscription_id = $subscript['id'];
                break;
            }
        }

        $query = "select meta_key, meta_value from {$wpdb->prefix}usermeta where user_id={$user_id} AND meta_key LIKE 'evergreen-sip2%'";
        $sip2_rows = $wpdb->get_results($query);

        if (($subscription_id != 0) && $active_status && (count($sip2_rows) > 0)) {
            if (pms_gm_is_group_owner($subscription_id)) {
                $doc = new DOMDocument();
                libxml_use_internal_errors(true); // suppress parsing warnings for malformed HTML
                $doc->loadHTML($output);
                libxml_clear_errors();

                $body = $doc->getElementsByTagName('body')->item(0);
                $new_output = '';
                foreach ($body->childNodes as $node) {
                    if ($node->nodeType === XML_ELEMENT_NODE) {
                        if ($node->nodeName == 'li') {
                            $firstChildNode = $node->childNodes[0];
                            $childNodeName = $firstChildNode->nodeName;
                            if ($childNodeName == 'h4') {
                                $value = $firstChildNode->nodeValue;
                                if (strpos($value, 'Evergreen API Credentials') !== false) {
                                    $login_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST']."/login-evergreen/{$user_id}/";
                                    $new_output .= $doc->saveHTML($node);
                                    $new_output .= '<li class="wppb-form-field"><label>Library Login URL</label>';
                                    $new_output .= "<p style='margin: 0 0 0 .5rem'>{$login_url}</p>";
                                    $new_output .= '</li>';
                                    continue;
                                }
                            }
                        }
                        $new_output .= $doc->saveHTML($node);
                    }
                }
                return $new_output;
            } else {
                // RESERVED: gs_child can be handled here.
            }
        }
        
        return $output;
    }
    
    public function gs_evergreen_login($atts) {
        global $wpdb;

        if (!session_id()) {
            session_start();
        }

        if (is_user_logged_in() && strpos($_SERVER['REQUEST_URI'], '/wp-admin') === false) {
            wp_redirect(home_url("/access/"));
            exit;
        }

        $library_number = get_query_var('library_number');
        if (empty($library_number)) {
            return "<p>Please select the proper library ID.</p>";
        }

        if (isset($_SESSION['sip2_response'])) {
            $sip2_code = $_SESSION['sip2_response'];
            unset($_SESSION['sip2_response']);

            if ($sip2_code > 1) {
                $error_msg = GS_LEA_SIP2_RES_CODE[$sip2_code];
                return "<p>{$error_msg}</p>";
            }

            $where = [
                'library_number'    => $library_number,
                'card_number'       => $_SESSION['sip2_card']
            ];
            $wpdb->delete($this->table_library_cards, $where, ['%d', '%s']);

            $row = $where;
            $row['expired_at'] = time() + GS_LEA_EXPIRY_HOUR * 86400;
            $wpdb->insert(
                $this->table_library_cards,
                $row
            );

            // // add the login library log
            $this->add_library_login_log($library_number);     // Log 'login' entry

            $_SESSION['sip2_auth'] = '1';
            wp_redirect(home_url('/access/'));
            exit;
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

        $card_number = isset($_POST['card_number']) ? sanitize_text_field($_POST['card_number']) : '';
        if (!empty($card_number)) {
            if (
                isset($_SESSION['sip2_auth']) &&
                ($_SESSION['sip2_auth'] == 1) &&
                isset($_SESSION['sip2_card']) &&
                ($_SESSION['sip2_card'] == $card_number) &&
                isset($_SESSION['sip2_lib']) &&
                ($_SESSION['sip2_lib'] == $library_number)
            ) {
                $time_now = time();
                $query = "select * from {$this->table_library_cards} where library_number={$library_number} AND card_number={$card_number} AND expired_at > {$time_now}";
                $card_rows = $wpdb->get_results($query);
                if (count($card_rows) > 0) {
                    wp_redirect(home_url("/access/"));
                    exit;
                }
            }

            $sip2_credentials = [];
            foreach ($sip2_rows as $sip2r) {
                $sip2_credentials[$sip2r->meta_key] = $sip2r->meta_value;
            }

            $_SESSION['sip2_host'] = $sip2_credentials['evergreen-sip2-domain'];
            $_SESSION['sip2_user'] = $sip2_credentials['evergreen-sip2-username'];
            $_SESSION['sip2_pass'] = $sip2_credentials['evergreen-sip2-password'];
            $_SESSION['sip2_card'] = $card_number;
            $_SESSION['sip2_lib'] = $library_number;

            $sip2_auth_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST']."/sip2connect.php";
            header("Location: {$sip2_auth_url}");
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

    function user_notice_message_enqueue_scripts() {
        wp_enqueue_script(
            'user_notice_message',
            plugin_dir_url(__FILE__) . 'js/message_render.js',
            array(),
            false,
            true
        );
    }
}

new Grantselect_Library_Evergreen_Auth;

