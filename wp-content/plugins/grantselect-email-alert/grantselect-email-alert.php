<?php
defined( 'ABSPATH' ) or die();
/*
Plugin Name: Grantselect Email Alert
Plugin URI: https://www.magimpact.com/
Description: Functions related to manage the GS Email Alert.
Version: 1.0.8
Author: magIMPACT
Author URI: https://www.magimpact.com/
*/
define( 'GS_EMAIL_ALERT', '1.0.8' );

define( 'GS_EA_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GS_EA_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

register_activation_hook( __FILE__, 'gs_ea_install' );
function gs_ea_install(){
    
}

Class GS_Email_Alert {
    private $per_pages;
    public function __construct(){
        $this->per_pages = [10, 20, 50, 100];
        $this->init();
    }
    private function init(){
        add_action('init', array($this, 'init_process'));
        add_filter( 'gform_validation_14', array($this, 'custom_validation') );
        add_action( 'wp_enqueue_scripts', array($this, 'gs_load_scripts'));
        add_action( 'gform_after_submission_14', array($this, 'update_email_alerts_info'), 10, 2 );

        add_shortcode("gs-register-email-alert", array($this, "get_register_email_alert"));
        add_shortcode("gs-modify-email-alert", array($this, "get_modify_email_alert"));
        add_shortcode("gs-remove-email-alert", array($this, "get_remove_email_alert"));
        add_shortcode("gs-email-alert-list", array($this, "get_email_alert_list"));
        add_shortcode("gs-newsletter-setting", array($this, "get_newsletter_setting"));
        add_shortcode("gs-newsletter-article", array($this, "get_newsletter_article"));

        //ajax ea login
        add_action( 'wp_ajax_gs_ea_login', array($this, 'auth_ea_login') );
        add_action( 'wp_ajax_nopriv_gs_ea_login', array($this, 'auth_ea_login') ); 

        //ajax search agent list paginate
        add_action( 'wp_ajax_gs_search_agent_list', array($this, 'get_search_agent_list') );
        add_action( 'wp_ajax_nopriv_gs_search_agent_list', array($this, 'get_search_agent_list') );

        //ajax apply search agent
        add_action( 'wp_ajax_gs_ea_apply_search_agent', array($this, 'apply_search_agent') );
        add_action( 'wp_ajax_nopriv_gs_ea_apply_search_agent', array($this, 'apply_search_agent') );

        //ajax remove email alert
        add_action( 'wp_ajax_gs_ea_remove', array($this, 'remove_email_alert') );
        add_action( 'wp_ajax_nopriv_gs_ea_remove', array($this, 'remove_email_alert') );

        
        //ajax forgot email password
        add_action( 'wp_ajax_gs_ea_forgot_pwd', array($this, 'send_recovery_email') );
        add_action( 'wp_ajax_nopriv_gs_ea_forgot_pwd', array($this, 'send_recovery_email') );

        //ajax email alert list 
        add_action( 'wp_ajax_gs_ea_list', array($this, 'get_email_alert_ajax') );
        add_action( 'wp_ajax_nopriv_gs_ea_list', array($this, 'get_email_alert_ajax') );

        //after save gs account setting
        add_action( 'wppb_edit_profile_success', array($this, 'update_email_account'), 20, 3 );
        //ajax ea newsletter loading
        add_action( 'wp_ajax_gs_newsletter_loading', array($this, 'get_newsletter_info') );
        add_action( 'wp_ajax_nopriv_gs_newsletter_loading', array($this, 'get_newsletter_info') );

        //ajax ea newsletter article save
        add_action( 'wp_ajax_gs_ns_article_save', array($this, 'save_newsletter_article') );


    }
    /**
     * Function init_process
     * Init process for plugin
     *
     * @return void
     */
    public function init_process(){
        if( !session_id() ){
            session_start();
        }
    }

    /**
     * Function gs_load_scripts
     * Load css and js for email alert
     *
     * @return $validation_result
     */
    public function gs_load_scripts(){
        wp_enqueue_style( 'gs-ea-css', GS_EA_PLUGIN_DIR_URL . 'css/email-alert.css', array(),  GS_EMAIL_ALERT);
        wp_enqueue_script( 'gs-ea-sagent', GS_EA_PLUGIN_DIR_URL . 'js/ea-search-agent.js', array('jquery'), GS_EMAIL_ALERT, true );
    }
    /**
     * Function custom_validation
     * email exist, pwd and comfirm pwd compare
     *
     * @return $out_result
     */
    public function custom_validation($validation_result){
        $form = $validation_result['form'];
        $pwd = "";
        $confirm_pwd = "";
        $email = "";
        //finding Field with ID of 1 and marking it as failed validation
        foreach( $form['fields'] as &$field ) {
    
            //NOTE: replace 1 with the field you would like to validate
            if ( $field->id == '24' ) {
                $pwd = rgpost( 'input_' . $field->id);
            }
            if ( $field->id == '25' ) {
                $confirm_pwd = rgpost( 'input_' . $field->id);
            }
            if ( $field->id == '26' ) {
                $email = rgpost( 'input_' . $field->id);
            }
        }
        foreach( $form['fields'] as &$field ) {
    
            //NOTE: replace 1 with the field you would like to validate
            if ( $field->id == '25' && $pwd != $confirm_pwd ) {
                $field->failed_validation = true;
                $field->validation_message = 'Please retry confirm password!';
                $validation_result['is_valid'] = false;
            }
            if ( $field->id == '26' && $this->exist_email($email, $pwd) && $_SESSION['email_alert_id'] == 0 ) {
                $field->failed_validation = true;
                $field->validation_message = 'This email has been registered already!';
                $validation_result['is_valid'] = false;
            }
        }
        //Assign modified $form object back to the validation result
        $validation_result['form'] = $form;
        return $validation_result;
    }

    /**
     * Function exist_email
     * check if email exists
     *
     * @return bool
     */
    public function exist_email($email, $pwd){
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT ID FROM {$wpdb->prefix}gs_subscriber_email_alerts WHERE email=%s", array($email) ));
        
        if ($row!=null){
            return true;
        }else{
            return false;
        }
    }
    /**
     * Function update_email_alerts_info
     * update email alert info.
     *
     * @return void
     */
    public function update_email_alerts_info( $entry, $form ) {
        global $wpdb, $post;
        
        $email = "";
        $pwd = "";
        $alert_type = 0;
        $first_name = '';
        $last_name = '';

        foreach ( $form['fields'] as $field ) {
            if ( $field->id == '24' ) {
                $pwd = rgpost( 'input_' . $field->id);
            }
            if ( $field->id == '26' ) {
                $email = rgpost( 'input_' . $field->id);
            }
            // if ( $field->id == '27' ) {
            //     $alert_type = rgpost( 'input_' . $field->id);
            // }
            if (strpos($field->id, "30") !== false){
                $inputs = $field->get_entry_inputs();
                if ( is_array( $inputs ) ) {
                    foreach ( $inputs as $key => $input ) {
                        if ($input['id'] == "30.3"){
                            $first_name = rgar( $entry, (string) $input['id'] );
                        }else if ($input['id'] == "30.6"){
                            $last_name = rgar( $entry, (string) $input['id'] );
                        }
                    }
                }
            }
        }
        $referer_url = getenv("HTTP_REFERER");
        if (is_user_logged_in()){
            $user_id = get_current_user_id();
            $subscription_id = 0;
            $subscription = null;
            
            if (strpos($referer_url, "/newsletter") !== false){
                $owner_subscriber_id = 0;
                $subscription_id = 0;
                if (isset($_SESSION['newsletter_subscription_id'])){
                    $subscription_id = $_SESSION['newsletter_subscription_id'];
                }
                
            }else{
                $user = get_userdata($user_id);
                $member          = pms_get_member( $user_id );
                foreach( $member->subscriptions as $subscript ){
                    $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                    $subscription = pms_get_member_subscription($subscript['id']);
                    $subscription_id = $subscript['id'];

                    if( $plan->type == 'group' )
                        break;
                }
                $owner_subscriber_id = 0;
                if ($subscription_id != 0 ){
                    if (pms_gm_is_group_owner( $subscription_id )){
                        $owner_subscriber_id = $subscription_id;
                    }else{
                        $owner_subscribermeta = $wpdb->get_row( 
                            $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_subscription_owner' AND member_subscription_id=%s", array($subscription_id) )
                        );
                        $owner_subscriber_id = $owner_subscribermeta->meta_value;
                    }
                }
            }
        }else{
            if (isset($_SESSION['guest_subscriber_id'])){
                $owner_subscriber_id = $_SESSION['guest_subscriber_id'];
            }else{
                $owner_subscriber_id = 0;
            }
            $subscription_id = $owner_subscriber_id;
            $user_id = 0;
        }
        if ($subscription_id == 0 && $owner_subscriber_id == 0){
            return; 
        }
        $email_alerts = "A";
        if ($owner_subscriber_id != 0){
            $owner_user_id = $wpdb->get_var( 
                $wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE id=%s", array($owner_subscriber_id) )
            );
            
            //$email_alerts = get_user_meta($owner_user_id, "email_alerts", true);
            // if (!$email_alerts)
            //     $email_alerts = "S";

            if ($_SESSION['email_alert_id'] == 0){
                $wpdb->insert(
                    $wpdb->prefix . "gs_subscriber_email_alerts",
                    [
                        'subscr_id'     => $owner_subscriber_id,
                        'email'         => $email,
                        'user_id'       => $user_id,
                        'first_name'    => $first_name,
                        'last_name'     => $last_name,
                        'pwd'           => $pwd,
                        'form_entry_id' => $entry['id'],
                        'alert_type'    => $alert_type,
                        'status'        => $email_alerts,
                        'created_at'    => date("Y-m-d H:i:s"),
                        'updated_at'    => date("Y-m-d H:i:s")
                    ]
                );
            }else{
                GFAPI::delete_entry( $_SESSION['eamil_alert_entry_id'] );
                $wpdb->update(
                    $wpdb->prefix . "gs_subscriber_email_alerts",
                    [
                        'subscr_id'     => $owner_subscriber_id,
                        'email'         => $email,
                        'user_id'       => $user_id,
                        'first_name'    => $first_name,
                        'last_name'     => $last_name,
                        'pwd'           => $pwd,
                        'form_entry_id' => $entry['id'],
                        'alert_type'    => $alert_type,
                        'status'        => $email_alerts,
                        'created_at'    => date("Y-m-d H:i:s"),
                        'updated_at'    => date("Y-m-d H:i:s")
                    ],
                    [
                        'ID'            => $_SESSION['email_alert_id']
                    ]
                );
            }
            
        }else{
            //$email_alerts = get_user_meta($user_id, "email_alerts", true);
            if ($_SESSION['email_alert_id'] == 0){    
                $wpdb->insert(
                    $wpdb->prefix . "gs_subscriber_email_alerts",
                    [
                        'subscr_id'     => $subscription_id,
                        'email'         => $email,
                        'user_id'       => $user_id,
                        'first_name'    => $first_name,
                        'last_name'     => $last_name,
                        'pwd'           => $pwd,
                        'form_entry_id' => $entry['id'],
                        'alert_type'    => $alert_type,
                        'status'        => $email_alerts,
                        'created_at'    => date("Y-m-d H:i:s"),
                        'updated_at'    => date("Y-m-d H:i:s")
                    ]
                );

            }else{
                GFAPI::delete_entry( $_SESSION['eamil_alert_entry_id'] );
                $wpdb->update(
                    $wpdb->prefix . "gs_subscriber_email_alerts",
                    [
                        'subscr_id'     => $subscription_id,
                        'email'         => $email,
                        'user_id'       => $user_id,
                        'first_name'    => $first_name,
                        'last_name'     => $last_name,
                        'pwd'           => $pwd,
                        'form_entry_id' => $entry['id'],
                        'alert_type'    => $alert_type,
                        'status'        => $email_alerts,
                        'created_at'    => date("Y-m-d H:i:s"),
                        'updated_at'    => date("Y-m-d H:i:s")
                    ],
                    [
                        'ID'            => $_SESSION['email_alert_id']
                    ]
                );
            }
            
        }

        if (is_user_logged_in()){
            $user_id = get_current_user_id();
            $rows = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$wpdb->prefix}pms_member_subscriptions WHERE STATUS=%s", array("active")));
            $active_subscribers = "";
            foreach ($rows as $r){
                if ($active_subscribers == ""){
                    $active_subscribers = $r->id;
                }else{
                    $active_subscribers .= "," . $r->id;
                }
            }
            $subscr_ids = $active_subscribers;
            $newsletter_plan_ids = implode(",", array_keys(GS_NEWSLETTER_SUBSCRIPTION_PLANS));
            $sql = "SELECT ifnull(count(a.ID), 0) cnt FROM {$wpdb->prefix}gs_subscriber_email_alerts a left join {$wpdb->prefix}pms_member_subscriptions m on m.id=a.subscr_id WHERE a.user_id={$user_id} and a.status='A' AND a.alert_type=0 and m.subscription_plan_id not in ({$newsletter_plan_ids}) AND a.subscr_id IN($subscr_ids)";
            $cnt = $wpdb->get_var($sql);
            if ($cnt != null && $cnt > 0){
                update_user_meta($user_id, "email_alerts", "A");
            }else{
                update_user_meta($user_id, "email_alerts", "S");
            }
        }
        //echo $post->ID;

    }
    /**
     * Function get_register_email_alert
     * echo form 14 for email alert register
     *
     * @return void
     */
    public function get_register_email_alert(){
        global $wpdb;
        $_SESSION['email_alert_id'] = 0;
        $register = true;
        $user_id = get_current_user_id();
        $current_user = get_userdata(get_current_user_id());
        $user_roles = $current_user->roles;
        ob_start();
        if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_search_agent.php' ) ){
            include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_search_agent.php';
        }
        $content = ob_get_clean();

        $sql = "select count(id) cnt from {$wpdb->prefix}gf_entry where form_id=14 and created_by=%d";
        $cnt = $wpdb->get_var($wpdb->prepare($sql, [$user_id]));
        if ($cnt > 3 && in_array("gs_individual", $user_roles)){
            $content .= "<p class='notice-text'>Your subscription allows you to register a maximum of three email alerts. To register an additional one, please cancel one of the others.</p>";
        }else{
            $content .= '<div class="'. (is_user_logged_in()?"use-sa":"not-use-sa").'">';
            $content .= do_shortcode( '[gravityform id="14" title="false" description="false" ajax="true" ]');
            $content .= '</div>';
        }
        
        return $content;
    }
    /**
     * Function get_register_email_alert
     * echo form 14 for email alert register
     *
     * @return void
     */
    public function get_modify_email_alert(){
        global $wpdb;
        wp_enqueue_script( 'gs-ea-modify', GS_EA_PLUGIN_DIR_URL . 'js/ea-modify.js', array('jquery'), GS_EMAIL_ALERT, true );
        $register = false;
        $token = "";
        $recovery = false;
        $email = "";
        if (isset($_GET['token'])){
            $token = $_REQUEST['token'];
            $token_info = json_decode( base64_decode( $token ), true );
            $user_id = get_current_user_id();
            $subscription_id = 0;
            $subscription = null;
            $user = get_userdata($user_id);
            $member          = pms_get_member( $user_id );
            foreach( $member->subscriptions as $subscript ){
                $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                $subscription = pms_get_member_subscription($subscript['id']);
                $subscription_id = $subscript['id'];
                if( $plan->type == 'group' )
                    break;
            }
            $owner_subscriber_id = 0;
            if ($subscription_id != 0 ){
                if (pms_gm_is_group_owner( $subscription_id )){
                    $owner_subscriber_id = $subscription_id;
                }else{
                    $owner_subscribermeta = $wpdb->get_row( 
                        $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_subscription_owner' AND member_subscription_id=%s", array($subscription_id) )
                    );
                    $owner_subscriber_id = $owner_subscribermeta->meta_value;
                }
            }
            if ($subscription_id == $token_info['subscription_id'] && $owner_subscriber_id == $token_info['owner_subscriber_id']){
                $recovery = true;
                $email = $token_info['email'];
            }
        }

        $_SESSION['email_alert_id'] = 0;
        ob_start();
        if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_login.php' ) ){
            include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_login.php';
        }
        if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_search_agent.php' ) ){
            include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_search_agent.php';
        }
        $content = ob_get_clean();
        $content .= '<div class="ea-modify-section hide">';
        $content .= do_shortcode( '[gravityform id="14" title="false" description="false" ajax="true" ]');
        $content .= '</div>';
        return $content;
    }
    /**
     * Function auth_ea_login
     * echo auth if ea email is valid
     *
     * @return void
     */
    public function auth_ea_login(){
        global $wpdb;
        $email = $_POST['email'];
        $pwd = $_POST['pwd'];
        if (is_user_logged_in()){
            $user_id = get_current_user_id();
            $subscription_id = 0;
            $subscription = null;
            $user = get_userdata($user_id);
            $member          = pms_get_member( $user_id );
            foreach( $member->subscriptions as $subscript ){
                $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                $subscription = pms_get_member_subscription($subscript['id']);
                $subscription_id = $subscript['id'];
                if( $plan->type == 'group' )
                    break;
            }
            $owner_subscriber_id = 0;
            if ($subscription_id != 0 ){
                if (pms_gm_is_group_owner( $subscription_id )){
                    $owner_subscriber_id = $subscription_id;
                }else{
                    $owner_subscribermeta = $wpdb->get_row( 
                        $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_subscription_owner' AND member_subscription_id=%s", array($subscription_id) )
                    );
                    if ($owner_subscribermeta){
                        $owner_subscriber_id = $owner_subscribermeta->meta_value;
                    }else{
                        $owner_subscriber_id = $subscription_id;
                    }
                    
                }
            }
        }else{
            if (isset($_SESSION['guest_subscriber_id'])){
                $owner_subscriber_id = $_SESSION['guest_subscriber_id'];
            }else{
                $owner_subscriber_id = 0;
            }
            $subscription_id = $owner_subscriber_id;
            $user_id = 0;
        }

        $token = $_REQUEST['token'];
        if ($token != ""){
            $token_info = json_decode( base64_decode( $token ), true );
            
            if ($email == $token_info['email'] && $subscription_id == $token_info['subscription_id'] && $owner_subscriber_id == $token_info['owner_subscriber_id']){
                $recovery = true;
                $row = $wpdb->get_row($wpdb->prepare("SELECT ID, form_entry_id FROM {$wpdb->prefix}gs_subscriber_email_alerts WHERE email=%s", array($email) ));
                $this->update_email_pwd($email, $pwd, $row->form_entry_id);
                $html = "";
                $_SESSION['email_alert_id'] = $row->ID;
                $_SESSION['eamil_alert_entry_id'] = $row->form_entry_id;
                $entry = GFAPI::get_entry( $row->form_entry_id );
                $entry_arr = [];
                foreach ($entry as $key => $val){
                    if (is_numeric($key) || strpos($key, ".") !== false){
                        $entry_arr[$key] = $val;
                    }
                }
                echo json_encode(['success'=>true, 'html'=>$html, 'entry'=>$entry_arr]);
                exit;
            }else{
                $_SESSION['email_alert_id'] = 0;
                $html = __("Token is invalid. Please click the forgot password link again.", "gs_ea");
                echo json_encode(['success'=>false, 'html'=>$html]);
                exit;
            }
        }else{
            $row = $wpdb->get_row($wpdb->prepare("SELECT ID, subscr_id, form_entry_id FROM {$wpdb->prefix}gs_subscriber_email_alerts WHERE email=%s AND pwd=%s AND subscr_id=%d ", array($email, $pwd, $owner_subscriber_id) ));
            $html = "";
            if ($row!=null){
                $content = "";
                // ob_start();
                // if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_search_agent.php' ) ){
                //     include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_search_agent.php';
                // }
                // $content = ob_get_clean();
                // $content .= do_shortcode( '[gravityform id="14" title="false" description="false" ajax="true" ]');

                $html = $content;
                $_SESSION['email_alert_id'] = $row->ID;
                $_SESSION['eamil_alert_entry_id'] = $row->form_entry_id;
                $entry = GFAPI::get_entry( $row->form_entry_id );
                $entry_arr = [];
                foreach ($entry as $key => $val){
                    if (is_numeric($key) || strpos($key, ".") !== false){
                        $entry_arr[$key] = $val;
                    }
                }
                echo json_encode(['success'=>true, 'html'=>$html, 'entry'=>$entry_arr]);
                exit;
            }else{
                $_SESSION['email_alert_id'] = 0;
                $html = __("Please input the correct email and password", "gs_ea");
                echo json_encode(['success'=>false, 'html'=>$html]);
                exit;
            }
        }
        
    }
    /**
     * Function get_email_alert_ajax
     * echo get email alert list of group
     *
     * @return alert list html
     */
    public function get_email_alert_ajax($atts){
        global $wpdb;
        $paginate_html = '';
        if(isset($_POST['page'])){
            // Sanitize the received page   
            $page = sanitize_text_field($_POST['page']);
            $per_page = $this->per_pages[0];
            if (isset($_POST['per_page'])){
                $per_page = sanitize_text_field($_POST['per_page']);
                update_user_meta(get_current_user_id(), 'gs_per_page', $per_page, true);
            }
            if (!get_user_meta(get_current_user_id(), 'gs_per_page', true)){
                add_user_meta(get_current_user_id(), 'gs_per_page', $per_page, true);
            }else{
                $per_page = get_user_meta(get_current_user_id(), 'gs_per_page', true);
            }

            $cur_page = $page;
            $page -= 1;
            // Set the number of results to display
            
            $previous_btn = true;
            $next_btn = true;
            $first_btn = true;
            $last_btn = true;
            $start = $page * $per_page;

            $gs_type = $_POST['gs_type'];
            
            $where = "";
            //Search Title
            if (isset($_POST['search_val']) && $_POST['search_val'] != ""){
                $where .= ' and (ea.email like "%' . $_POST['search_val'] . '%" or ea.last_name like "%' . $_POST['search_val'] . '%" or ea.first_name like "%' . $_POST['search_val'] . '%")';
            }
            if ($_POST['subscription_id'] != 0 || $_POST['subscription_id'] != "" ){
                $results = $wpdb->get_results(
                        $wpdb->prepare(
                            "
                            SELECT ea.* FROM {$wpdb->prefix}gs_subscriber_email_alerts ea
                            where ea.subscr_id=%d {$where} 
                            ORDER BY last_name, first_name, email
                            LIMIT %d, %d
                            ",
                            array($_POST['subscription_id'], $start, $per_page)
                        )
                    );
                $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->prefix}gs_subscriber_email_alerts ea where subscr_id=%d {$where} ", array($_POST['subscription_id'])));
            }else{
                
                $results = $wpdb->get_results(
                        $wpdb->prepare(
                            "
                            SELECT ea.* FROM {$wpdb->prefix}gs_subscriber_email_alerts ea
                            where ea.user_id=%d {$where} 
                            ORDER BY last_name, first_name, email
                            LIMIT %d, %d
                            ",
                            array(get_current_user_id(), $start, $per_page)
                        )
                    );
                $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->prefix}gs_subscriber_email_alerts ea where user_id=%d {$where} ", array(get_current_user_id())));
            }
            
            $total_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->prefix}gs_subscriber_email_alerts ea where subscr_id=%d ", array($_POST['subscription_id'])));
            $received_email_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->prefix}gs_subscriber_email_alerts ea where status='A' and subscr_id=%d ", array($_POST['subscription_id'])));
            // This is where the magic happens
            $no_of_paginations = ceil($count / $per_page);

            if ($cur_page >= 7) {
                $start_loop = $cur_page - 3;
                if ($no_of_paginations > $cur_page + 3)
                    $end_loop = $cur_page + 3;
                else if ($cur_page <= $no_of_paginations && $cur_page > $no_of_paginations - 6) {
                    $start_loop = $no_of_paginations - 6;
                    $end_loop = $no_of_paginations;
                } else {
                    $end_loop = $no_of_paginations;
                }
            } else {
                $start_loop = 1;
                if ($no_of_paginations > 7)
                    $end_loop = 7;
                else
                    $end_loop = max(1, $no_of_paginations);
            }
            ob_start();
            if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_ajax_table.php' ) ){
                include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_ajax_table.php';
            }
            $paginate_html = ob_get_clean();
        }
        echo $paginate_html;
        exit(0);
        
    }
    /**
     * Function get_email_alert_list
     * echo get email alert list of group
     *
     * @return alert list html
     */
    public function get_email_alert_list($atts){
        global $wpdb;
        wp_enqueue_script( 'gs-ea-list', GS_EA_PLUGIN_DIR_URL . 'js/ea-list.js', array('jquery'), GS_EMAIL_ALERT, true );
        $results = $wpdb->get_results( 
            $wpdb->prepare("SELECT ID, email, pwd, `status` FROM {$wpdb->prefix}gs_subscriber_email_alerts WHERE subscr_id=%d", array($atts['subscription_id']))
        );
        $registered_user_cnt = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) cnt FROM {$wpdb->prefix}gs_subscriber_email_alerts where subscr_id=%d and status='A'", array($atts['subscription_id'])));
        $subscription_id = $atts['subscription_id'];
        ob_start();
        if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_list.php' ) ){
            include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_list.php';
        }
        $content = ob_get_clean();
        
        return $content;
        
    }

    /**
     * Function get_search_agent_list
     * echo get search agent list
     *
     * @return search_agent_list
     */
    public function get_search_agent_list(){
        global $wpdb;
        $paginate_html = '';
        if(isset($_POST['page'])){
            // Sanitize the received page   
            $page = sanitize_text_field($_POST['page']);
            $per_page = $this->per_pages[0];
            if (isset($_POST['per_page'])){
                $per_page = sanitize_text_field($_POST['per_page']);
                update_user_meta(get_current_user_id(), 'gs_per_page', $per_page, true);
            }
            if (!get_user_meta(get_current_user_id(), 'gs_per_page', true)){
                add_user_meta(get_current_user_id(), 'gs_per_page', $per_page, true);
            }else{
                $per_page = get_user_meta(get_current_user_id(), 'gs_per_page', true);
            }

            $cur_page = $page;
            $page -= 1;
            // Set the number of results to display
            
            $previous_btn = true;
            $next_btn = true;
            $first_btn = true;
            $last_btn = true;
            $start = $page * $per_page;

            $gs_type = $_POST['gs_type'];
            
            $where = "";
            //Search Title
            if (isset($_POST['search_val']) && $_POST['search_val'] != ""){
                $where .= ' and search_title like "%' . $_POST['search_val'] . '%" ';
            }
            $user_id = get_current_user_id();
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gs_save_searchresult where type=%d and is_agent=%d and user_id=%d {$where} ORDER BY created_at DESC LIMIT %d, %d", array($gs_type, $_REQUEST['is_agent'], $user_id, $start, $per_page)));
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->prefix}gs_save_searchresult where type=%d and is_agent=%d and user_id=%d {$where}", array($gs_type, $_REQUEST['is_agent'], $user_id)));
            // This is where the magic happens
            $no_of_paginations = ceil($count / $per_page);

            if ($cur_page >= 7) {
                $start_loop = $cur_page - 3;
                if ($no_of_paginations > $cur_page + 3)
                    $end_loop = $cur_page + 3;
                else if ($cur_page <= $no_of_paginations && $cur_page > $no_of_paginations - 6) {
                    $start_loop = $no_of_paginations - 6;
                    $end_loop = $no_of_paginations;
                } else {
                    $end_loop = $no_of_paginations;
                }
            } else {
                $start_loop = 1;
                if ($no_of_paginations > 7)
                    $end_loop = 7;
                else
                    $end_loop = max(1, $no_of_paginations);
            }
            ob_start();
            if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_search_agent_ajax_table.php' ) ){
                include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_search_agent_ajax_table.php';
            }
            $paginate_html = ob_get_clean();
        }
        echo $paginate_html;
        exit(0);
    }
    /**
     * Function apply_search_agent
     * echo get search agent form entity and set the values into email alert
     *
     * @return search_agent_list
     */
    public function apply_search_agent(){
        $id = $_POST['id'];
        $entry = GFAPI::get_entry( $id );
        $entry_arr = [];
        foreach ($entry as $key => $val){
            if (is_numeric($key) || strpos($key, ".") !== false){
                $entry_arr[$key] = $val;
            }
        }
        echo json_encode(['success'=>true, 'entry'=>$entry_arr, 'form_id'=>$entry['form_id']]);
        exit;
    }

    /**
     * Function show page for remove_email_alert
     * show page for remove email alert
     *
     * @return void
     */
    public function get_remove_email_alert(){
        global $wpdb;
        wp_enqueue_script( 'gs-ea-remove', GS_EA_PLUGIN_DIR_URL . 'js/ea-remove.js', array('jquery'), GS_EMAIL_ALERT, true );
        $register = false;
        $token = "";
        $recovery = false;
        $email = "";
        if (isset($_GET['token'])){
            $token = $_REQUEST['token'];
            $token_info = json_decode( base64_decode( $token ), true );
            $user_id = get_current_user_id();
            $subscription_id = 0;
            $subscription = null;
            $user = get_userdata($user_id);
            $member          = pms_get_member( $user_id );
            foreach( $member->subscriptions as $subscript ){
                $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                $subscription = pms_get_member_subscription($subscript['id']);
                $subscription_id = $subscript['id'];
                if( $plan->type == 'group' )
                    break;
            }
            $owner_subscriber_id = 0;
            if ($subscription_id != 0 ){
                if (pms_gm_is_group_owner( $subscription_id )){
                    $owner_subscriber_id = $subscription_id;
                }else{
                    $owner_subscribermeta = $wpdb->get_row( 
                        $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_subscription_owner' AND member_subscription_id=%s", array($subscription_id) )
                    );
                    $owner_subscriber_id = $owner_subscribermeta->meta_value;
                }
            }
            if ($subscription_id == $token_info['subscription_id'] && $owner_subscriber_id == $token_info['owner_subscriber_id']){
                $recovery = true;
                $email = $token_info['email'];
            }
        }

        $_SESSION['email_alert_id'] = 0;
        ob_start();
        if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_login.php' ) ){
            include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_login.php';
        }
        $content = ob_get_clean();
        return $content;
    }
    /**
     * Function remove_email_alert
     * remove email alert
     *
     * @return void
     */
    public function remove_email_alert(){
        global $wpdb;

        $email = $_POST['email'];
        $pwd = $_POST['pwd'];
        $token = $_REQUEST['token'];
        if ($token != ""){
            $token_info = json_decode( base64_decode( $token ), true );
            $user_id = get_current_user_id();
            $subscription_id = 0;
            $subscription = null;
            $user = get_userdata($user_id);
            $member          = pms_get_member( $user_id );
            foreach( $member->subscriptions as $subscript ){
                $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                $subscription = pms_get_member_subscription($subscript['id']);
                $subscription_id = $subscript['id'];
                if( $plan->type == 'group' )
                    break;
            }
            $owner_subscriber_id = 0;
            if ($subscription_id != 0 ){
                if (pms_gm_is_group_owner( $subscription_id )){
                    $owner_subscriber_id = $subscription_id;
                }else{
                    $owner_subscribermeta = $wpdb->get_row( 
                        $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_subscription_owner' AND member_subscription_id=%s", array($subscription_id) )
                    );
                    //$owner_subscriber_id = $owner_subscribermeta->meta_value;
                    if ($owner_subscribermeta){
                        $owner_subscriber_id = $owner_subscribermeta->meta_value;
                    }else{
                        $owner_subscriber_id = $subscription_id;
                    }

                }
            }
            if ($email == $token_info['email'] && $subscription_id == $token_info['subscription_id'] && $owner_subscriber_id == $token_info['owner_subscriber_id']){
                $row = $wpdb->get_row($wpdb->prepare("SELECT ID, form_entry_id FROM {$wpdb->prefix}gs_subscriber_email_alerts WHERE email=%s", array($email) ));
                $_SESSION['email_alert_id'] = 0;
                $_SESSION['eamil_alert_entry_id'] = 0;
                GFAPI::delete_entry( $row->form_entry_id );
                $html = __("You have removed the email alert successfully", "gs_ea");
                $wpdb->delete(
                    $wpdb->prefix . "gs_subscriber_email_alerts",
                    array(
                        'ID' => $row->ID
                    )
                );
                echo json_encode(['success'=>true, 'html'=>$html]);
            }else{
                $_SESSION['email_alert_id'] = 0;
                $html = __("This is invalid token. Please click the forgot password link again", "gs_ea");
                echo json_encode(['success'=>false, 'html'=>$html]);
            }
        }else{
            $row = $wpdb->get_row($wpdb->prepare("SELECT ID, form_entry_id FROM {$wpdb->prefix}gs_subscriber_email_alerts WHERE email=%s AND pwd=%s", array($email, $pwd) ));
            $html = "";
            if ($row!=null){
                GFAPI::delete_entry( $row->form_entry_id );
                $html = __("Email alert has been removed successfully.", "gs_ea");
                $wpdb->delete(
                    $wpdb->prefix . "gs_subscriber_email_alerts",
                    array(
                        'ID' => $row->ID
                    )
                );
                echo json_encode(['success'=>true, 'html'=>$html]);
            }else{
                $_SESSION['email_alert_id'] = 0;
                $html = __("Please input the correct email and password", "gs_ea");
                echo json_encode(['success'=>false, 'html'=>$html]);
            }
        }
        if (is_user_logged_in()){
            $user_id = get_current_user_id();
            $rows = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$wpdb->prefix}pms_member_subscriptions WHERE STATUS=%s", array("active")));
            $active_subscribers = "";
            foreach ($rows as $r){
                if ($active_subscribers == ""){
                    $active_subscribers = $r->id;
                }else{
                    $active_subscribers .= "," . $r->id;
                }
            }
            $subscr_ids = $active_subscribers;
            $newsletter_plan_ids = implode(",", array_keys(GS_NEWSLETTER_SUBSCRIPTION_PLANS));
            $sql = "SELECT ifnull(count(a.ID), 0) cnt FROM {$wpdb->prefix}gs_subscriber_email_alerts a left join {$wpdb->prefix}pms_member_subscriptions m on m.id=a.subscr_id WHERE a.user_id={$user_id} and a.status='A' AND a.alert_type=0 and m.subscription_plan_id not in ({$newsletter_plan_ids}) AND a.subscr_id IN($subscr_ids)";
            $cnt = $wpdb->get_var($sql);
            if ($cnt != null && $cnt > 0){
                update_user_meta($user_id, "email_alerts", "A");
            }else{
                update_user_meta($user_id, "email_alerts", "S");
            }
        }
        exit;
    }
    /**
     * Function send_recovery_email
     * update email pwd
     *
     * @return void
     */
    public function update_email_pwd($email, $pwd, $form_entry_id){
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . "gs_subscriber_email_alerts",
            [
                'pwd'=>$pwd
            ],
            [
                'email'=>$email
            ]
        );
        gform_update_meta($form_entry_id, "24", $pwd);
        gform_update_meta($form_entry_id, "25", $pwd);
    }
    /**
     * Function send_recovery_email
     * send email alert
     *
     * @return void
     */
    public function send_recovery_email(){
        global $wpdb;
        $user_id = get_current_user_id();
        $subscription_id = 0;
        $subscription = null;
        $user = get_userdata($user_id);
        $member          = pms_get_member( $user_id );
        foreach( $member->subscriptions as $subscript ){
            $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
            $subscription = pms_get_member_subscription($subscript['id']);
            $subscription_id = $subscript['id'];
            if( $plan->type == 'group' )
                break;
        }
        $owner_subscriber_id = 0;
        if ($subscription_id != 0 ){
            if (pms_gm_is_group_owner( $subscription_id )){
                $owner_subscriber_id = $subscription_id;
            }else{
                $owner_subscribermeta = $wpdb->get_row( 
                    $wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_subscription_owner' AND member_subscription_id=%s", array($subscription_id) )
                );

                //$owner_subscriber_id = $owner_subscribermeta->meta_value;
                if ($owner_subscribermeta){
                    $owner_subscriber_id = $owner_subscribermeta->meta_value;
                }else{
                    $owner_subscriber_id = $subscription_id;
                }

            }
        }

        $token = base64_encode( 
                    json_encode(
                        [
                        'subscription_id'       => $subscription_id, 
                        'owner_subscriber_id'   => $owner_subscriber_id,
                        'email'                 => $_POST['email'],
                        'date'                  => date("Y-m-d")
                        ]
                    )
                );
        $redirect_url = $_REQUEST['redirect_url'] . "?token=" . $token;
        
        $body = __("Please click the link below to reset your email alerts password.", "gs-ea")."<br><br>";
        $body .= "<a href='".$redirect_url."' style='background: #072362;color: #fff;padding: 8px 16px;border-radius:20px;text-align: center;width: 150px;height:20px;display:block;'>" . __("Reset my password", "gs-ea") . "</a>";
        
        $to = $_POST['email'];
        $from_name = wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );
	    $from_email = get_bloginfo('admin_email');
        $headers[] = "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";
	    $headers[] = 'From: ' . $from_name . ' <' . $from_email . ">\r\n";

        $subject = __("Email Alert Password Reset", "gs_ea");
        wp_mail( $to, $subject, $body, $headers);
        $response['success'] = true;
        $response['html'] = __("Recovery Email has been sent successfully. Please check your email.", "gs_ea");
        $response['redirect'] = $redirect_url;
        echo json_encode($response);
        exit;
    }
    /**
     * Function update_email_account
     * update email alert status
     *
     * @return void
     */
    public function update_email_account($http_request, $form_name, $user_id){
        global $wpdb;
        if (isset($http_request['email_alerts'])){
            $row = $wpdb->get_row($wpdb->prepare("select id from {$wpdb->prefix}pms_member_subscriptions where user_id=%d", array($user_id)));
            if ($row != null){
                $wpdb->update(
                    $wpdb->prefix . "gs_subscriber_email_alerts",
                    [
                        'status'=> $http_request['email_alerts']
                    ],
                    [
                        'subscr_id'=>$row->id
                    ]
                );
            }else{
                $wpdb->update(
                    $wpdb->prefix . "gs_subscriber_email_alerts",
                    [
                        'status'=> $http_request['email_alerts']
                    ],
                    [
                        'user_id'=>$user_id
                    ]
                );
            }
        }
        
    }

    /**
     * Function get_newsletter_setting
     * echo form 14 for email alert register
     *
     * @return void
     */
    public function get_newsletter_setting(){
        global $wpdb;
        $_SESSION['email_alert_id'] = 0;
        $register = false;
        $cu_id = get_current_user_id();
        
        $result = $wpdb->get_results($wpdb->prepare("select subscription_plan_id from {$wpdb->prefix}pms_member_subscriptions where user_id=%d", array($cu_id)));
        $user_plans = [];
        foreach ($result as $r){
            $user_plans[] = $r->subscription_plan_id;
        }
        $ns_plans = array_keys(GS_NEWSLETTER_SUBSCRIPTION_PLANS);
        $user_ns_plans = array_intersect($user_plans, $ns_plans);
        $customer_segments = get_user_meta($cu_id, "customer_segments", true);
        $user_ns_tabs = [];
        $customer_segments_arr = [];
        if ($customer_segments != ""){
            
            if ($customer_segments != ""){
                $customer_segments_arr = explode(",", $customer_segments);
            }
            
            foreach ($user_ns_plans as $unp){
                if (array_search(GS_NEWSLETTER_SUBSCRIPTION_PLANS[$unp], $customer_segments_arr) === false){
                    array_push($customer_segments_arr, GS_NEWSLETTER_SUBSCRIPTION_PLANS[$unp]);
                }
            }
            sort($customer_segments_arr, SORT_NUMERIC);
            $customer_segments_str = implode(",", $customer_segments_arr);
            update_user_meta($cu_id, "customer_segments", $customer_segments_str);
        }else{
            foreach ($user_ns_plans as $unp){
                if (array_search(GS_NEWSLETTER_SUBSCRIPTION_PLANS[$unp], $customer_segments_arr) === false){
                    array_push($customer_segments_arr, GS_NEWSLETTER_SUBSCRIPTION_PLANS[$unp]);
                }
            }
            if (!empty($user_ns_plans))
                add_user_meta($cu_id, "customer_segments", implode(",", $customer_segments_arr));
        }
        foreach ($user_ns_plans as $unp){
            $user_ns_tabs[$unp] = get_the_title($unp);
        }
        if (isset($_GET['subscription_plan'])){
            $active_plan_id = $_GET['subscription_plan'];
        }else{
            $active_plan_id = $user_ns_plans[0];
        }
        ob_start();
        wp_enqueue_script( 'gs-ea-newsletter', GS_EA_PLUGIN_DIR_URL . 'js/ea-newsletter.js', array('jquery'), GS_EMAIL_ALERT, true );     
        $nl_plan['id'] = $active_plan_id;
        wp_localize_script( 'gs-ea-newsletter', 'NL_PLAN_ID', $nl_plan );
        if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_newsletter.php' ) ){
            include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_newsletter.php';
        }
        
        $content = ob_get_clean();
        $content .= '<div class="not-use-sa newsletter-subscription">';
        $content .= do_shortcode( '[gravityform id="14" title="false" description="false" ajax="true" ]');
        $content .= '</div>';
        return $content;
    }
    /**
     * Function get_newsletter_article
     *
     * @return void
     */
    public function get_newsletter_article(){
        global $wpdb;
        $cu_id = get_current_user_id();
        $article = get_option("ns_article");
        $test_emails = get_option("ns_test_emails");
        ob_start();
        wp_enqueue_script( 'gs-ea-newsletter', GS_EA_PLUGIN_DIR_URL . 'js/ea-article.js', array('jquery'), GS_EMAIL_ALERT, true );     
        
        if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_newsletter_article.php' ) ){
            include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_newsletter_article.php';
        }
        $content = ob_get_clean();

        return $content;
    }
    /**
     * Function get_newsletter_info
     * load form 14 for newsletter
     *
     * @return void
     */
    public function get_newsletter_info(){
        global $wpdb;
        $plan = $_POST['plan_id'];

        $subscription_id = $wpdb->get_var($wpdb->prepare("select id from {$wpdb->prefix}pms_member_subscriptions where user_id=%d and subscription_plan_id=%d order by id desc", array(get_current_user_id(), $plan)));
        
        $row = $wpdb->get_row($wpdb->prepare("SELECT ID, subscr_id, form_entry_id FROM {$wpdb->prefix}gs_subscriber_email_alerts where user_id=%d and subscr_id=%d ", array(get_current_user_id(), $subscription_id) ));
        $html = "";
        $_SESSION['newsletter_subscription_id'] = $subscription_id;

        if ($row!=null){
            $content = "";
            // ob_start();
            // if( file_exists( GS_EA_PLUGIN_DIR_PATH . 'templates/ea_search_agent.php' ) ){
            //     include GS_EA_PLUGIN_DIR_PATH . 'templates/ea_search_agent.php';
            // }
            // $content = ob_get_clean();
            // $content .= do_shortcode( '[gravityform id="14" title="false" description="false" ajax="true" ]');

            $html = $content;
            $_SESSION['email_alert_id'] = $row->ID;
            $_SESSION['eamil_alert_entry_id'] = $row->form_entry_id;
            $entry = GFAPI::get_entry( $row->form_entry_id );
            $entry_arr = [];
            foreach ($entry as $key => $val){
                if (is_numeric($key) || strpos($key, ".") !== false){
                    $entry_arr[$key] = $val;
                }
            }
            echo json_encode(['success'=>true, 'html'=>$html, 'entry'=>$entry_arr]);
            exit;
        }
        echo json_encode(['success'=>false]);
        exit;
    }
    function save_newsletter_article(){
        $content = $_POST['content'];
        if (get_option("ns_article")!==false){
            update_option("ns_article", $content);
        }else{
            add_option("ns_article", $content);
        }
        if (get_option("ns_test_emails")!==false){
            update_option("ns_test_emails", $_POST['test_emails']);
        }else{
            add_option("ns_test_emails", $_POST['test_emails']);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}
new GS_Email_Alert;