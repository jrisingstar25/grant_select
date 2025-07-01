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

register_activation_hook(__FILE__, 'flush_evergreen_login_rewrite_rules');

function flush_evergreen_login_rewrite_rules() {
    evergreen_login_rewrite_rule();
    flush_rewrite_rules();
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
    public function __construct(){
        global $wpdb;
        $this->init();
    }

    private function init(){
        add_shortcode("gs-evergreen-login", array($this, "gs_evergreen_login"));
    }
    
    public function gs_evergreen_login($atts) {
        global $wpdb;

        $content = '';
        
        $lib_id = get_query_var('lib_id');
        $lib_card_number = isset($_POST['libcardnum']) ? sanitize_text_field($_POST['libcardnum']) : '';
        if (!empty($lib_id) && !empty($lib_card_number)) {
            $query = "select meta_key, meta_value from {$wpdb->prefix}usermeta where user_id={$lib_id} AND meta_key IN ('evergreen-sip2-domain', 'evergreen-sip2-institution', 'evergreen-sip2-password')";
            // echo($wpdb->prepare("select meta_key, meta_value from {$wpdb->prefix}usermeta where user_id=%s AND meta_key IN ('evergreen-sip2-domain', 'evergreen-sip2-institution', 'evergreen-sip2-password')", $inst_id));
            $sip2_rows = $wpdb->get_results($query);
            if (count($sip2_rows) == 0) {
                $content .= "<p>You can't login using this library ID.</p>";
            } else {
                $sip2_credentials = [];
                foreach ($sip2_rows as $sip2r) {
                    $sip2_credentials[$sip2r->meta_key] = $sip2r->meta_value;
                }
                $sip2_credentials['evergreen-sip2-username'] = $lib_card_number;

                // Let's handle the signed-in user on the assumption that the user login is successful.
                $_SESSION['guest_user_card_number'] = $lib_card_number;

                // wp_redirect(home_url("/login-evergreen/"));exit;
                // Output the extracted credentials for test purposes only
                foreach ($sip2_credentials as $key => $value) {
                    $content .= "<p>{$key} : {$value}</p>";
                }
            }
        }
        
        ob_start();
        if( file_exists( GS_LEA_PLUGIN_DIR_PATH . 'templates/gs_library_login_form.php' ) ){
            include GS_LEA_PLUGIN_DIR_PATH . 'templates/gs_library_login_form.php';
        }
        $form_body = ob_get_contents();
        ob_end_clean();

        $content .= $form_body;

        return $content;
    }
}

new Grantselect_Library_Evergreen_Auth;

