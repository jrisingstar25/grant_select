<?php
defined( 'ABSPATH' ) or die();
/*
Plugin Name: Grantselect IP or URL Auth
Plugin URI: https://www.magimpact.com/
Description: Create an Account for IP or URL Auth
Version: 1.0.0
Author: magIMPACT
Author URI: https://www.magimpact.com/
*/
define( 'GS_IP_URL_AUTH', '1.0.0' );
define( 'GS_IPURLAUTH_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GS_IPURLAUTH_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

define("ERROR_NONE", 0);
define("ERROR_OVERSEAT", 1);
define("ERROR_MULTIGROUP", 2);
define("ERROR_OTHER", 3);

register_activation_hook( __FILE__, 'gs_ip_url_auth_install' );
function gs_ip_url_auth_install(){
    global $wpdb;
    $create_tables_query = array();

    // User Table Alter
    $charset_collate = $wpdb->get_charset_collate();
    //subscriber logs
    $create_tables_query[0] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "gs_ip_url_auth` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `url` varchar(512) DEFAULT NULL,
        `ip` varchar(128) DEFAULT NULL,
        `pms_group_id` int(11) DEFAULT NULL,
        `ecode` int(11) DEFAULT NULL,
        `updated_at` datetime DEFAULT NULL,
        PRIMARY KEY (`ID`)
    ) $charset_collate;";
    foreach ($create_tables_query as $create_table_query) {
        $wpdb->query($create_table_query);
    }
}
Class Grantselect_IP_URL_Auth {
    private $per_pages;
    public function __construct(){
        $this->per_pages = [10, 20, 50, 100];
        $this->init();
    }

    private function init(){
        add_action('wp_login', array($this, 'remove_guest_info'));
        add_shortcode("gs-create-account-for-ip-url-auth", array($this, "display_create_account_for_ip_url_auth"));
        add_shortcode("gs-display-for-ip-url-auth-request", array($this, "display_for_ip_url_auth_request"));
        //ajax paginate
        add_action( 'wp_ajax_gs_iua_account_list', array($this, 'get_gs_iua_account_list') ); //iua: ip/url auth
        //ajax get account info
        add_action( 'wp_ajax_gs_iua_account_info', array($this, 'get_gs_iua_account_info') );
        //ajax apply group info into child account
        add_action( 'wp_ajax_gs_apply_group', array($this, 'apply_group') );
        
    }
    public function remove_guest_info() {
        if (isset($_SESSION['guest_user_id'])){
            unset($_SESSION['guest_user_id']);
        }
        if (isset($_SESSION['guest_subscriber_id'])){
            unset($_SESSION['guest_subscriber_id']);
        }
        if (isset($_SESSION['referer_url'])){
            unset($_SESSION['referer_url']);
        }
        if (isset($_SESSION['LAST_ACTIVITY'])){
            unset($_SESSION['LAST_ACTIVITY']);
        }
    }
    
    public function display_create_account_for_ip_url_auth(){
        if (isset($_SESSION['guest_user_id'])){
            return do_shortcode("[wppb-register form_name='create-account-for-ip-url-auth']");
        }else{
            return "You are not a user for ip & url auth.";
        }
    }

    public function display_for_ip_url_auth_request(){
        wp_enqueue_script( 'gs-iua', GS_IPURLAUTH_PLUGIN_DIR_URL . 'js/ip_auth.js', array('jquery'), GS_IP_URL_AUTH, true );
        ob_start();
        if( file_exists( GS_IPURLAUTH_PLUGIN_DIR_PATH . 'view/accounts.php' ) ){
            include GS_IPURLAUTH_PLUGIN_DIR_PATH . 'view/accounts.php';
        }
        $content = ob_get_clean();
        return $content;
    }
    public function get_gs_iua_account_list(){
        global $wpdb;
        $paginate_html = '';
        if(isset($_POST['page'])){
            // Sanitize the received page
            $page = sanitize_text_field($_POST['page']);
            $per_page = $this->per_pages[0];
            if (isset($_POST['per_page'])){
                $per_page = sanitize_text_field($_POST['per_page']);
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

            //process search value
            $where = "";
            if (isset($_POST['sval']) && $_POST['sval'] != ""){
                $where .= ' and (u.user_login LIKE "%%' . $_POST['sval']. '%%" OR u.user_email LIKE "%%' . $_POST['sval']. '%%")';
            }

            //sort by selected column
            $sort_by_column = $_POST['sort_by_column'];
            $sort_by_direction = $_POST['sort_by_direction'];
            if ( empty($sort_by_direction) && !empty($sort_by_column) ) {
                $sort_by_direction = 'ASC';
            }
            switch ($sort_by_column) {
                case 'user_login':
                    $sort_clause = "u.user_login";
                    $sort_clause .= " $sort_by_direction";
                    break;
                case 'user_email':
                    $sort_clause = "u.user_email";
                    $sort_clause .= " $sort_by_direction";
                    break;
                default:
                    //sort by Subscriber
                    $sort_clause = "u.user_login";
                    $sort_clause .= " $sort_by_direction";
                    break;
            }

            //process search value
            $sql_stmt = $wpdb->prepare(
                "
                    SELECT a.*, u.user_login, u.user_email
                    FROM {$wpdb->prefix}gs_ip_url_auth a 
                    LEFT JOIN {$wpdb->prefix}users u ON a.user_id=u.ID
                    WHERE u.ID is not null {$where}
                    ORDER BY $sort_clause LIMIT %d, %d
                    ",
                array($start, $per_page)
            );
            $results = $wpdb->get_results($sql_stmt);
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT count(a.id)
                    FROM {$wpdb->prefix}gs_ip_url_auth a 
                    LEFT JOIN {$wpdb->prefix}users u ON a.user_id=u.ID
                    WHERE u.ID is not null {$where}
                    ",
                    array()
                )
            );

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
            if( file_exists( GS_IPURLAUTH_PLUGIN_DIR_PATH . 'view/accounts_ajax_table.php' ) ){
                include GS_IPURLAUTH_PLUGIN_DIR_PATH . 'view/accounts_ajax_table.php';
            }
            $paginate_html = ob_get_clean();
        }
        echo $paginate_html;
        exit(0);
    }
    public function get_gs_iua_account_info(){
        global $wpdb;
        $id = $_POST['id'];
        $sql = "select * from {$wpdb->prefix}gs_ip_url_auth where ID=%d";
        $iua = $wpdb->get_row($wpdb->prepare($sql, [$id]));
        $groups = [];
        if ($iua->pms_group_id != null){
            $subscription_id = $iua->pms_group_id;
            $group = [];
            if ($subscription_id != 0){
                $group['subscription_id'] = $subscription_id;
                $group['name'] = pms_get_member_subscription_meta( $subscription_id, 'pms_group_name', true );
                $owner_subscription = pms_get_member_subscription( $subscription_id );
                $group['total_seats'] = pms_gm_get_total_seats($owner_subscription);
                $group['used_seats'] = pms_gm_get_used_seats($subscription_id);
                $group['ecode'] = ERROR_NONE;
            }
            array_push($groups, $group);
        }else{
            $url_rows_cnt = 0;
            $ip_rows_cnt = 0;
            $referer_url = $iua->url;
            if ($referer_url != null || $referer_url != ""){
                $sqlquery = $wpdb->prepare("select user_id from {$wpdb->prefix}usermeta where meta_key LIKE 'referer-urls%' AND meta_value!='' AND %s LIKE CONCAT(meta_value, '%')", array($referer_url));
                $url_rows = $wpdb->get_results($sqlquery);
                $url_rows_cnt = $wpdb->num_rows;
                foreach ($url_rows as $ur){
                    $group_owner_id = $ur->user_id;
                    $member          = pms_get_member( $group_owner_id );
                    $subscript_cnt = 0;
                    $owner_subscription_id = 0;
                    foreach( $member->subscriptions as $subscript ){
                        if ($subscript['status'] != 'active')
                            continue;
                        $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                        if (in_array($plan->id, GS_INDIVIDUAL_SUBSCRIPTION_PLANS)){
                            $has_individual_role = true;
                        }else if (array_key_exists($plan->id, GS_NEWSLETTER_SUBSCRIPTION_PLANS)){
                            $has_newsletter_role = true;
                        }else if( $plan->type == 'group' ){
                            $owner_subscription_id = $subscript['id'];
                        }else{
                            $subscription_plan_ids[] = $plan->id;
                        }
                        $subscript_cnt++;
                    }
                    $subscription_id = $owner_subscription_id;
                    if ($subscription_id != 0){
                        $group = [];
                        $group['subscription_id'] = $subscription_id;
                        $group['name'] = pms_get_member_subscription_meta( $subscription_id, 'pms_group_name', true );
                        $owner_subscription = pms_get_member_subscription( $subscription_id );
                        $group['total_seats'] = pms_gm_get_total_seats($owner_subscription);
                        $group['used_seats'] = pms_gm_get_used_seats($subscription_id);
                        $group['ecode'] = $group['total_seats'] < $group['used_seats'] ? ERROR_OVERSEAT : ERROR_MULTIGROUP;
                        array_push($groups, $group);
                    }
                }
            }

            $user_ids = [];
            $ip = $iua->ip;
            $ip_rows = $wpdb->get_results($wpdb->prepare("select user_id from {$wpdb->prefix}usermeta where meta_key LIKE 'ip-range%' AND meta_value!='' AND %s LIKE CONCAT(meta_value, '%')", array($ip)));
            foreach ($ip_rows as $ir){
                if (!in_array($ir->user_id, $user_ids)){
                    array_push($user_ids, $ir->user_id);
                }
            }
            $ip_rows_cnt = $wpdb->num_rows;
            $ip_rows = $wpdb->get_results("select user_id, meta_value from {$wpdb->prefix}usermeta where meta_key LIKE '%' AND meta_value like '%-%' ORDER BY umeta_id DESC");    
            foreach ($ip_rows as $ipr){
                $ip_ranges = explode("-", $ipr->meta_value);
                if (count($ip_ranges) == 2){
                    if (ip2long(trim($ip_ranges[0])) && ip2long(trim($ip_ranges[1]))){
                        $ip_range_min = min(ip2long(trim($ip_ranges[0])), ip2long(trim($ip_ranges[1])));
                        $ip_range_max = max(ip2long(trim($ip_ranges[0])), ip2long(trim($ip_ranges[1])));
                        $ip_long = ip2long(trim($ip));
                        if ($ip_range_min && $ip_range_max && $ip_long && $ip_range_min <= $ip_long && $ip_range_max >= $ip_long ){
                            $user_id = $ipr->user_id;
                            if (!in_array($user_id, $user_ids)){
                                array_push($user_ids, $user_id);
                            }
                            
                        }
                    }
                }
            }
            foreach ($user_ids as $u_id){
                $group_owner_id = $u_id;

                $member          = pms_get_member( $group_owner_id );
                $subscript_cnt = 0;
                $owner_subscription_id = 0;
                foreach( $member->subscriptions as $subscript ){
                    if ($subscript['status'] != 'active')
                        continue;
                    $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                    if (in_array($plan->id, GS_INDIVIDUAL_SUBSCRIPTION_PLANS)){
                        $has_individual_role = true;
                    }else if (array_key_exists($plan->id, GS_NEWSLETTER_SUBSCRIPTION_PLANS)){
                        $has_newsletter_role = true;
                    }else if( $plan->type == 'group' ){
                        $owner_subscription_id = $subscript['id'];
                    }else{
                        $subscription_plan_ids[] = $plan->id;
                    }
                    $subscript_cnt++;
                }
                $subscription_id = $owner_subscription_id;
                if ($subscription_id != 0){
                    $group = [];
                    $group['subscription_id'] = $subscription_id;
                    $group['name'] = pms_get_member_subscription_meta( $subscription_id, 'pms_group_name', true );
                    $owner_subscription = pms_get_member_subscription( $subscription_id );
                    $group['total_seats'] = pms_gm_get_total_seats($owner_subscription);
                    $group['used_seats'] = pms_gm_get_used_seats($subscription_id);
                    $group['ecode'] = $group['total_seats'] < $group['used_seats'] ? ERROR_OVERSEAT : ERROR_MULTIGROUP;
                    array_push($groups, $group);
                }
            }
        }
        ob_start();
        if( file_exists( GS_IPURLAUTH_PLUGIN_DIR_PATH . 'view/group_table.php' ) ){
            include GS_IPURLAUTH_PLUGIN_DIR_PATH . 'view/group_table.php';
        }
        $iua_html = ob_get_clean();
        echo $iua_html;
        exit(0);
    }
    public function apply_group(){
        global $wpdb;
        $id = $_POST['id'];
        $subscription_id = $_POST['gid'];
        $sql = "select * from {$wpdb->prefix}gs_ip_url_auth where ID=%d and pms_group_id is null";
        $iua = $wpdb->get_row($wpdb->prepare($sql, [$id]));
        $user_id = $iua->user_id;
        $owner_subscription = pms_get_member_subscription( $subscription_id );
        $used_seats = pms_gm_get_used_seats($subscription_id);
        $total_seats = pms_gm_get_total_seats($owner_subscription);
        if ($used_seats < $total_seats){
            $expiration_date = $owner_subscription->expiration_date;
            if($owner_subscription->subscription_plan_id == GS_ACCOUNT_TRIAL_PLAN && preg_match('/^\w+@\w+\.edu$/i', $email) > 0){
                $expiration_date = $owner_subscription->start_date;
                $expiration_date = strtotime($expiration_date);
                $expiration_date = strtotime("+14 day", $expiration_date);
                $expiration_date = date("Y-m-d H:i:s", $expiration_date);
            }
            $subscription_data = array(
                'user_id'              => $iua->user_id,
                'subscription_plan_id' => $owner_subscription->subscription_plan_id,
                'start_date'           => $owner_subscription->start_date,
                'expiration_date'      => $expiration_date,
                'status'               => 'active',
            );
            $subscription = new PMS_Member_Subscription();
            $subscription->insert( $subscription_data );
            pms_add_member_subscription_meta( $subscription->id, 'pms_group_subscription_owner', $owner_subscription->id );
            pms_add_member_subscription_meta( $owner_subscription->id, 'pms_group_subscription_member', $subscription->id );
            $wpdb->update(
                $wpdb->prefix . "gs_ip_url_auth",
                [
                    'pms_group_id'  => $subscription_id
                ],
                [
                    'ID'            => $id
                ]
            );
            $user = new WP_User($user_id);
            $user->add_role('gs_child');
            $user->remove_role("subscriber");
            $user->remove_role("gs_manager");
        }
        echo json_encode(['success'=>true]);
        exit(0);
    }
}
new Grantselect_IP_URL_Auth;

add_filter( 'wp_nav_menu_objects', 'ravs_add_menu_parent_class' );
function ravs_add_menu_parent_class( $items ) {
	$title = "";
    foreach ( $items as $item ) {
        $title = $item->title;//print each menu item an get your parent menu item-id
		break;
    }
	if ($title == "Welcome" && !is_user_logged_in() && isset($_SESSION['guest_user_id'])){
		$link = array (
			'title'            => 'Create Account',
			'menu_item_parent' => '',
			'ID'               => '9999',
			'url'              => '/access/create-account/',
            'classes'            => 'bellows-menu-item bellows-item-level-0'
		);
		$items[] = (object) $link;	
	}
	return $items;
}
function get_customer_ip() {
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}
add_action( 'wppb_register_success', 'gs_register_success', 10, 3 );
function gs_register_success($request, $form_name, $user_id){
    global $wpdb;
    if (isset($_SESSION['guest_user_id'])){
        $url_rows_cnt = 0;
        $ip_rows_cnt = 0;
        $referer_url = "";
        $user_ids = [];
        if (isset($_SESSION['referer_url'])){
            $referer_url = $_SESSION['referer_url'];
            $sqlquery = $wpdb->prepare("select user_id from {$wpdb->prefix}usermeta where meta_key LIKE 'referer-urls%' AND meta_value!='' AND %s LIKE CONCAT(meta_value, '%')", array($referer_url));
            $url_rows = $wpdb->get_results($sqlquery);
            unset($_SESSION['referer_url']);
            $url_rows_cnt = $wpdb->num_rows;
            if ($user_rows_cnt == 1){
                array_push($user_ids, $url_rows[0]->user_id);
            }
        }
        $ip = get_customer_ip();
        $ip_rows = $wpdb->get_results($wpdb->prepare("select distinct user_id from {$wpdb->prefix}usermeta where meta_key LIKE 'ip-range%' AND meta_value!='' AND %s LIKE CONCAT(meta_value, '%')", array($ip)));
        $ip_rows_cnt = $wpdb->num_rows;
        if ($ip_rows_cnt == 1){
            array_push($user_ids, $ip_rows[0]->user_id);
        }
        $ip_rows = $wpdb->get_results("select user_id, meta_value from {$wpdb->prefix}usermeta where meta_key LIKE '%' AND meta_value like '%-%' ORDER BY umeta_id DESC");    
        $group_manager_id = 0;
        foreach ($ip_rows as $ipr){
            $ip_ranges = explode("-", $ipr->meta_value);
            if (count($ip_ranges) == 2){
                if (ip2long(trim($ip_ranges[0])) && ip2long(trim($ip_ranges[1]))){
                    $ip_range_min = min(ip2long(trim($ip_ranges[0])), ip2long(trim($ip_ranges[1])));
                    $ip_range_max = max(ip2long(trim($ip_ranges[0])), ip2long(trim($ip_ranges[1])));
                    $ip_long = ip2long(trim($ip));
                    if ($ip_range_min && $ip_range_max && $ip_long && $ip_range_min <= $ip_long && $ip_range_max >= $ip_long ){
                        array_push($user_ids, $ipr->user_id);
                        if ($group_manager_id == 0){
                            $group_manager_id = $ipr->user_id;
                            $ip_rows_cnt++;
                        }
                        if ($group_manager_id != $ipr->user_id){
                            $ip_rows_cnt++;
                        }
                    }
                }
            }
        }
        if ($url_rows_cnt + $ip_rows_cnt == 1){
            //create the child account of group directly
            $group_owner_id = 0;
            if ($url_rows_cnt == 1){
                $group_owner_id = $user_ids[0];
            }else if ($ip_rows_cnt == 1){
                $group_owner_id = $user_ids[0];
            }

            
            $member          = pms_get_member( $group_owner_id );
            $subscript_cnt = 0;
            $owner_subscription_id = 0;
            foreach( $member->subscriptions as $subscript ){
                if ($subscript['status'] != 'active')
                    continue;
                $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                if (in_array($plan->id, GS_INDIVIDUAL_SUBSCRIPTION_PLANS)){
                    $has_individual_role = true;
                }else if (array_key_exists($plan->id, GS_NEWSLETTER_SUBSCRIPTION_PLANS)){
                    $has_newsletter_role = true;
                }else if( $plan->type == 'group' ){
                    $owner_subscription_id = $subscript['id'];
                }else{
                    $subscription_plan_ids[] = $plan->id;
                }
                $subscript_cnt++;
            }
            $subscription_id = $owner_subscription_id;
            if ($subscription_id != 0){

                $owner_subscription = pms_get_member_subscription( $subscription_id );
                $used_seats = pms_gm_get_used_seats($subscription_id);
                $total_seats = pms_gm_get_total_seats($owner_subscription);
                if ($used_seats < $total_seats){
                    $expiration_date = $owner_subscription->expiration_date;
                    if($owner_subscription->subscription_plan_id == GS_ACCOUNT_TRIAL_PLAN && preg_match('/^\w+@\w+\.edu$/i', $email) > 0){
                        $expiration_date = $owner_subscription->start_date;
                        $expiration_date = strtotime($expiration_date);
                        $expiration_date = strtotime("+14 day", $expiration_date);
                        $expiration_date = date("Y-m-d H:i:s", $expiration_date);
                    }
                    $subscription_data = array(
                        'user_id'              => $user_id,
                        'subscription_plan_id' => $owner_subscription->subscription_plan_id,
                        'start_date'           => $owner_subscription->start_date,
                        'expiration_date'      => $expiration_date,
                        'status'               => 'active',
                    );
                    $subscription = new PMS_Member_Subscription();
                    $subscription->insert( $subscription_data );
                    pms_add_member_subscription_meta( $subscription->id, 'pms_group_subscription_owner', $owner_subscription->id );
                    pms_add_member_subscription_meta( $owner_subscription->id, 'pms_group_subscription_member', $subscription->id );
                    echo "<div class='wppb-success alert'>You became a member of group.</div>";
                    $user = new WP_User($user_id);
                    $user->add_role('gs_child');
                    $user->remove_role("subscriber");
                }else{
                    echo "<div class='wppb-warning alert'>There is a system issue preventing you from self-registering. Please contact your administrator for assistance.</div>";
                    $wpdb->insert(
                        $wpdb->prefix . "gs_ip_url_auth",
                        [
                            'user_id'       => $user_id,
                            'url'           => $referer_url,
                            'ip'            => $ip,
                            'pms_group_id'  => null,
                            'ecode'         => ERROR_OVERSEAT,
                            'updated_at'    => date("Y-m-d h:i:s")
                        ]
                    );
                }
                
            }
            
        }else{
            //discuss to create the child account with admin
            echo "<div class='wppb-warning alert'>There is a system issue preventing you from self-registering. Please contact your administrator for assistance.</div>";
            $wpdb->insert(
                $wpdb->prefix . "gs_ip_url_auth",
                [
                    'user_id'       => $user_id,
                    'url'           => $referer_url,
                    'ip'            => $ip,
                    'pms_group_id'  => null,
                    'ecode'         => ERROR_MULTIGROUP,
                    'updated_at'    => date("Y-m-d h:i:s")
                ]
            );
        }
        unset($_SESSION['guest_user_id']);
        if (isset($_SESSION['guest_subscriber_id'])){
            unset($_SESSION['guest_subscriber_id']);
        }
        if (isset($_SESSION['referer_url'])){
            unset($_SESSION['referer_url']);
        }
        if (isset($_SESSION['LAST_ACTIVITY'])){
            unset($_SESSION['LAST_ACTIVITY']);
        }
    }
}



