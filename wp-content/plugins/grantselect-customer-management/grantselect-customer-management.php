<?php
defined( 'ABSPATH' ) or die();
/*
Plugin Name: Grantselect Customer Management
Plugin URI: https://www.magimpact.com/
Description: Functions related to manage the GS Account and GS Trail Account, etc.
Version: 1.2.1
Author: magIMPACT
Author URI: https://www.magimpact.com/
*/
define( 'GS_CUSTOMER_MANAGEMENT', '1.2.1' );
define("GS_ACCOUNT_TRIAL_PLAN", 567);
define("GS_ACCOUNT_SUBSCRIPTION_PLAN", 568);
define("GS_ACCOUNT_ONEYEAR_INDIVIDUAL_PLAN", 560);
define("GS_ACCOUNT_THREEMONTH_INDIVIDUAL_PLAN", 561);
const GS_INDIVIDUAL_SUBSCRIPTION_PLANS = array(
    GS_ACCOUNT_ONEYEAR_INDIVIDUAL_PLAN,
    GS_ACCOUNT_THREEMONTH_INDIVIDUAL_PLAN
);
const GS_GRANTSEARCH_SUBSCRIPTION_PLANS = array(
    GS_ACCOUNT_TRIAL_PLAN,
    GS_ACCOUNT_SUBSCRIPTION_PLAN,
    GS_ACCOUNT_ONEYEAR_INDIVIDUAL_PLAN,
    GS_ACCOUNT_THREEMONTH_INDIVIDUAL_PLAN
);
const GS_NEWSLETTER_SUBSCRIPTION_PLANS = array(
    '1509' => 9,//Newsletter Subscription
    '1548' => 3,
    '1549' => 20,
    '1550' => 6,
    '1551' => 2/*,
    '1486' => 9,//Local Newsletter Subscription
    '1513' => 3,
    '1514' => 20,
    '1515' => 6,
    '1516' => 2*/
);

define( 'GS_CM_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GS_CM_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'GS_ADMIN', 'gs_admin' );
define( 'GS_PAID', 'paid');
define( 'GS_TRIAL', 'trial');
define( 'GS_DEFAULT_PAYMENT', 'stripe');
//register_activation_hook( __FILE__, 'gscm_install' );  //Uncomment this to turn on user migration functions
function is_valid($val){
    if ($val != "" && $val != NULL){
        return true;
    }else{
        return false;
    }
}
function gscm_install(){
    global $wpdb;
    $create_tables_query = array();

    // User Table Alter
    $charset_collate = $wpdb->get_charset_collate();
    //subscriber logs
    $create_tables_query[0] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "gs_subscriber_logs` (
                                                        `ID` bigint(20) NOT NULL AUTO_INCREMENT,
                                                        `manager_id` bigint(20) NOT NULL default 0,
                                                        `manager_name` varchar(64) DEFAULT '' NOT NULL,
                                                        `user_id` bigint(20) NOT NULL default 0,
                                                        `user_name` varchar(64) DEFAULT '' NOT NULL,
                                                        `ip` varchar(64) DEFAULT '' NOT NULL,
                                                        `url` varchar(512) DEFAULT '' NOT NULL,
                                                        `status` tinyint(1) NOT NULL default 0,
                                                        `content` varchar(512) DEFAULT '' NOT NULL,
                                                        `created_at` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,	
                                                        `sid` bigint(20) NOT NULL default 0,
                                                        PRIMARY KEY (`ID`)
                                                        ) $charset_collate;";
    //subscriber log statistics
    $create_tables_query[1] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "gs_owner_statistics` (
        `ID` bigint(20) NOT NULL AUTO_INCREMENT,
        `owner_sid` bigint(20) NOT NULL default 0,
        `owner_name` varchar(64) DEFAULT '' NOT NULL,
        `count` bigint(20) NOT NULL default 0,
        PRIMARY KEY (`ID`)
        ) $charset_collate;";
    //convert old subscriber to new subscriber
    $create_tables_query[2] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "gs_convert_subscriber` (
        `ID` bigint(20) NOT NULL AUTO_INCREMENT,
        `old_uid` bigint(20) NOT NULL default 0,
        `old_subscriber_id` bigint(20) NOT NULL default 0,
        `new_uid` bigint(20) NOT NULL default 0,
        `new_subscriber_id` bigint(20) NOT NULL default 0,
        PRIMARY KEY (`ID`)
        ) $charset_collate;";
    //convert old subscriber alert to new subscriber email alert
    $create_tables_query[3] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "gs_subscriber_email_alerts` (
        `ID` bigint(20) NOT NULL AUTO_INCREMENT,
        `subscr_id` bigint(20) NOT NULL default 0,
        `user_id` bigint(20) NOT NULL default 0,
        `email` varchar(256) DEFAULT '' NOT NULL,
        `pwd` varchar(256) DEFAULT '' NOT NULL,
        `first_name` varchar(256) DEFAULT '' NOT NULL,
        `last_name` varchar(256) DEFAULT '' NOT NULL,
        `form_entry_id` bigint(20) NOT NULL default 0,
        `alert_type` tinyint(1) NOT NULL default 0,
        `status` varchar(8) DEFAULT '' NOT NULL,
        `updated_at` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,	
        `created_at` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,	
        PRIMARY KEY (`ID`)
        ) $charset_collate;";


    foreach ($create_tables_query as $create_table_query) {
        $wpdb->query($create_table_query);
    }

    //first step:loading grantselect users
    $prev_subscriber_id = 0;
    $gs_users_logins = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gs_subscriber_access_logins ORDER BY id"));
    $gs_users_ips = $wpdb->get_results($wpdb->prepare("SELECT sai.id as id, sai.subscr_id as subscr_id, concat(s.first_name, \" \", s.last_name) as user, LEFT(MD5(RAND()), 12) as pwd, s.first_name as first_name, s.last_name as last_name, s.email as email, \"yes\" as iptable FROM {$wpdb->prefix}gs_subscriber_access_ips AS sai LEFT JOIN {$wpdb->prefix}gs_subscribers AS s ON sai.subscr_id=s.subscr_id GROUP BY sai.subscr_id ORDER BY sai.id"));

    $gs_users = array_merge($gs_users_logins, $gs_users_ips);

//    foreach ($gs_users as $gs_user){
//        //check if user exists
//        if (!is_valid($gs_user->user) || username_exists($gs_user->user)){
//            continue;
//        }
//
//        if ($gs_user->iptable == "yes") {
//            $login_ip_match = 0;
//            #check if it exists in the logins array, and if so ignore it (continue)
//            foreach ($gs_users_logins as $gs_user_login) {
//                if ($gs_user_login->subscr_id == $gs_user->subscr_id) {
//                    $login_ip_match = 1;
//                    break;
//                }
//            }
//            if ($login_ip_match == 1) {
//                continue;
//            }
//        }
//
//        //create wp user
//        if (is_valid($gs_user->email)){
//            $status = wp_create_user( $gs_user->user, $gs_user->pwd, $gs_user->email );
//        }else{
//            $status = wp_create_user( $gs_user->user, $gs_user->pwd );
//        }
//        if( is_wp_error($status) ){
//            continue;
//        }else{
//            $gs_user_id = $status;
//            if (is_valid($gs_user->first_name)){
//                update_user_meta($gs_user_id, "first_name", $gs_user->first_name);
//            }
//            if (is_valid($gs_user->last_name)){
//                update_user_meta($gs_user_id, "last_name", $gs_user->last_name);
//            }
//
//            //check if the gs_subscriber is processed
//            $gs_convert_subscriber = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gs_convert_subscriber where old_subscriber_id=%d ", array($gs_user->subscr_id)));
//            if ($gs_convert_subscriber && $gs_convert_subscriber->new_subscriber_id != 0) { // they are a Group User
//
//                $wpdb->insert(
//                    $wpdb->prefix . "gs_convert_subscriber",
//                    [
//                        'old_uid'           => $gs_user->id,
//                        'old_subscriber_id' => $gs_user->subscr_id,
//                        'new_uid'           => $gs_user_id,
//                        'new_subscriber_id' => $gs_convert_subscriber->new_subscriber_id
//                    ]
//                );
//
//                //GS Child User
//                $gc_user = new WP_User($gs_user_id); //group child
//                $gc_user->add_role('gs_child');
//                $gc_user->remove_role("subscriber");
//                $pms_member_subscriber = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pms_member_subscriptions where id=%d ", array($gs_convert_subscriber->new_subscriber_id)));
//                $wpdb->insert(
//                    $wpdb->prefix . "pms_member_subscriptions",
//                    [
//                        'user_id'               => $gs_user_id,
//                        'subscription_plan_id'  => $pms_member_subscriber->subscription_plan_id,
//                        'start_date'            => $pms_member_subscriber->start_date,
//                        'expiration_date'       => $pms_member_subscriber->expiration_date,
//                        'status'                => $pms_member_subscriber->status,
//                        'payment_gateway'       => "",
//                        'billing_amount'        => 0
//                    ]
//                );
//                $member_sub_id = $wpdb->insert_id;
//                $wpdb->insert(
//                    $wpdb->prefix . "pms_member_subscriptionmeta",
//                    [
//                        'member_subscription_id'    => $pms_member_subscriber->id,
//                        'meta_key'                  => 'pms_group_subscription_member',
//                        'meta_value'                => $member_sub_id
//                    ]
//                );
//                $wpdb->insert(
//                    $wpdb->prefix . "pms_member_subscriptionmeta",
//                    [
//                        'member_subscription_id'    => $member_sub_id,
//                        'meta_key'                  => 'pms_group_subscription_owner',
//                        'meta_value'                => $pms_member_subscriber->id
//                    ]
//                );
//            } else {
//                $gs_subscriber = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gs_subscribers where subscr_id=%d and status!='D' ", array($gs_user->subscr_id)));
//                if (!$gs_subscriber) {
//                    continue;
//                }
//                $wpdb->insert(
//                    $wpdb->prefix . "gs_convert_subscriber",
//                    [
//                        'old_uid'           => $gs_user->id,
//                        'old_subscriber_id' => $gs_user->subscr_id,
//                        'new_uid'           => $gs_user_id
//                    ]
//                );
//                $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(subscr_id) FROM {$wpdb->prefix}gs_subscriber_access_logins WHERE subscr_id=%d", array($gs_user->subscr_id)));
//                //check if the gs_subscriber is institutional, trial, standard, professional, other
//                $group_types = ['Institutional Subscription'];
//                $individual_year_types = ['Professional - $495 / year'];
//
//                if (array_search($gs_subscriber->subscription_type, $group_types) || is_valid($gs_subscriber->organization) || $count > 1){
//
//                    //Institutional Subscription
//                    $gm_user = new WP_User($gs_user_id); //group manager
//                    $gm_user->add_role('gs_manager');
//                    $gm_user->remove_role("subscriber");
//
//                    $status = "expired";
//
//                    $expire = $gs_subscriber->expire;
//                    if (is_valid($expire) && $expire != '0000-00-00 00:00:00'){
//                        $expire = date_format(date_create($expire), "Y-m-d 23:59:59");
//                        $now = date("Y-m-d H:i:s");
//
//                        if ($now < $expire){
//                            $status = "active";
//                        }
//                        if ($gs_subscriber->status == "SS" || $gs_subscriber->status == "TS"){
//                            $status = "suspended";
//                        }
//                    }else{
//                        $expire = date('Y-m-d 00:00:00',strtotime("-1 days"));
//                    }
//                    $start_date = $gs_subscriber->start_date;
//                    if (is_valid($start_date) && $start_date != '0000-00-00 00:00:00'){
//                        $start_date = date_format(date_create($start_date), "Y-m-d 00:00:00");
//                    }else{
//                        $start_date = date('Y-m-d 00:00:00',strtotime("-1 years"));
//                    }
//
//                    //determine if it's a trial account or a paid subscription
//                    if ($gs_subscriber->status == "TA" || $gs_subscriber->status == "TE" || $gs_subscriber->status == "TS"){
//                        $spid = GS_ACCOUNT_TRIAL_PLAN;
//                    } else {
//                        $spid = GS_ACCOUNT_SUBSCRIPTION_PLAN;
//                    }
//
//                    $wpdb->insert(
//                        $wpdb->prefix . "pms_member_subscriptions",
//                        [
//                            'user_id'               => $gs_user_id,
//                            'subscription_plan_id'  => $spid,
//                            'start_date'            => $start_date,
//                            'expiration_date'       => $expire,
//                            'status'                => $status,
//                            'payment_gateway'       => GS_DEFAULT_PAYMENT,
//                            'billing_amount'        => 0,
//                            'billing_next_payment'  => '0000-00-00 00:00:00',
//                            'billing_last_payment'  => $start_date,
//                            'trial_end'             => '0000-00-00 00:00:00'
//                        ]
//                    );
//                    $member_sub_id = $wpdb->insert_id;
//                    $wpdb->insert(
//                        $wpdb->prefix . "pms_member_subscriptionmeta",
//                        [
//                            'member_subscription_id'=> $member_sub_id,
//                            'meta_key'              => 'pms_group_name',
//                            'meta_value'            => $gs_subscriber->organization
//                        ]
//                    );
//                    $wpdb->insert(
//                        $wpdb->prefix . "pms_member_subscriptionmeta",
//                        [
//                            'member_subscription_id'=> $member_sub_id,
//                            'meta_key'              => 'pms_group_description',
//                            'meta_value'            => $gs_subscriber->comment
//                        ]
//                    );
//
//                    $wpdb->update($wpdb->prefix . "gs_convert_subscriber",
//                        [
//                            'new_subscriber_id'	=> $member_sub_id
//                        ],
//                        [
//                            'old_subscriber_id'	=> $gs_subscriber->subscr_id
//                        ]
//                    );
//                    update_gs_user_meta($gs_user_id, $gs_user, $member_sub_id, $gs_subscriber);
//
//                    }else if (array_search($gs_subscriber->subscription_type, $individual_year_types)){
//                    //Professional - $495 / year
//                    $gi_user = new WP_User($gs_user_id); //gs individual
//                    $gi_user->add_role('gs_individual');
//                    $gi_user->remove_role("subscriber");
//
//                    $status = "expired";
//                    $expire = $gs_subscriber->expire;
//                    if (is_valid($expire) && $expire != '0000-00-00 00:00:00'){
//                        $expire = date_format(date_create($expire), "Y-m-d 23:59:59");
//                        $now = date("Y-m-d H:i:s");
//
//                        if ($now < $expire){
//                            $status = "active";
//                        }
//                    }else{
//                        $expire = date('Y-m-d 00:00:00',strtotime("-1 days"));
//                    }
//                    $start_date = $gs_subscriber->start_date;
//                    if (is_valid($start_date) && $start_date != '0000-00-00 00:00:00'){
//                        $start_date = date_format(date_create($start_date), "Y-m-d 00:00:00");
//                    }else{
//                        $start_date = date('Y-m-d 00:00:00',strtotime("-1 years"));
//                    }
//
//                    $wpdb->insert(
//                        $wpdb->prefix . "pms_member_subscriptions",
//                        [
//                            'user_id'               => $gs_user_id,
//                            'subscription_plan_id'  => GS_ACCOUNT_ONEYEAR_INDIVIDUAL_PLAN,
//                            'start_date'            => $start_date,
//                            'expiration_date'       => '0000-00-00 00:00:00',
//                            'status'                => $status,
//                            'payment_gateway'       => GS_DEFAULT_PAYMENT,
//                            'billing_amount'        => 0,
//                            'billing_duration'      => 12,
//                            'billing_duration_unit' => 'month',
//                            'billing_next_payment'  => $expire,
//                            'billing_last_payment'  => $start_date,
//                            'trial_end'             => '0000-00-00 00:00:00'
//                        ]
//                    );
//                    $member_sub_id = $wpdb->insert_id;
//                    $wpdb->insert(
//                        $wpdb->prefix . "pms_member_subscriptionmeta",
//                        [
//                            'member_subscription_id'=> $member_sub_id,
//                            'meta_key'              => 'pms_group_name',
//                            'meta_value'            => $gs_subscriber->organization
//                        ]
//                    );
//                    $wpdb->insert(
//                        $wpdb->prefix . "pms_member_subscriptionmeta",
//                        [
//                            'member_subscription_id'=> $member_sub_id,
//                            'meta_key'              => 'pms_group_description',
//                            'meta_value'            => $gs_subscriber->comment
//                        ]
//                    );
//                    update_user_meta($gs_user_id, 'special_instructions', $gs_subscriber->comment);
//                    $wpdb->update($wpdb->prefix . "gs_convert_subscriber",
//                        [
//                            'new_subscriber_id'	=> $member_sub_id
//                        ],
//                        [
//                            'old_subscriber_id'	=> $gs_subscriber->subscr_id
//                        ]
//                    );
//                    update_gs_user_meta($gs_user_id, $gs_user, $member_sub_id, $gs_subscriber);
//                }else{
//                    //Standard - $150 / 3 months
//                    $gi_user = new WP_User($gs_user_id); //gs individual
//                    $gi_user->add_role('gs_individual');
//                    $gi_user->remove_role("subscriber");
//
//                    $status = "expired";
//                    $expire = $gs_subscriber->expire;
//                    if (is_valid($expire) && $expire != '0000-00-00 00:00:00'){
//                        $expire = date_format(date_create($expire), "Y-m-d 23:59:59");
//                        $now = date("Y-m-d H:i:s");
//
//                        if ($now < $expire){
//                            $status = "active";
//                        }
//                    }else{
//                        $expire = date('Y-m-d 00:00:00',strtotime("-1 days"));
//                    }
//                    $start_date = $gs_subscriber->start_date;
//                    if (is_valid($start_date) && $start_date != '0000-00-00 00:00:00'){
//                        $start_date = date_format(date_create($start_date), "Y-m-d 00:00:00");
//                    }else{
//                        $start_date = date('Y-m-d 00:00:00',strtotime("-3 months"));
//                    }
//
//                    $wpdb->insert(
//                        $wpdb->prefix . "pms_member_subscriptions",
//                        [
//                            'user_id'               => $gs_user_id,
//                            'subscription_plan_id'  => GS_ACCOUNT_THREEMONTH_INDIVIDUAL_PLAN,
//                            'start_date'            => $start_date,
//                            'expiration_date'       => '0000-00-00 00:00:00',
//                            'status'                => $status,
//                            'payment_gateway'       => GS_DEFAULT_PAYMENT,
//                            'billing_amount'        => 0,
//                            'billing_duration'      => 3,
//                            'billing_duration_unit' => 'month',
//                            'billing_next_payment'  => $expire,
//                            'billing_last_payment'  => $start_date,
//                            'trial_end'             => '0000-00-00 00:00:00'
//                        ]
//                    );
//                    $member_sub_id = $wpdb->insert_id;
//                    $wpdb->insert(
//                        $wpdb->prefix . "pms_member_subscriptionmeta",
//                        [
//                            'member_subscription_id'=> $member_sub_id,
//                            'meta_key'              => 'pms_group_name',
//                            'meta_value'            => $gs_subscriber->organization
//                        ]
//                    );
//                    $wpdb->insert(
//                        $wpdb->prefix . "pms_member_subscriptionmeta",
//                        [
//                            'member_subscription_id'=> $member_sub_id,
//                            'meta_key'              => 'pms_group_description',
//                            'meta_value'            => $gs_subscriber->comment
//                        ]
//                    );
//                    update_user_meta($gs_user_id, 'special_instructions', $gs_subscriber->comment);
//                    $wpdb->update($wpdb->prefix . "gs_convert_subscriber",
//                        [
//                            'new_subscriber_id'	=> $member_sub_id
//                        ],
//                        [
//                            'old_subscriber_id'	=> $gs_subscriber->subscr_id
//                        ]
//                    );
//                    update_gs_user_meta($gs_user_id, $gs_user, $member_sub_id, $gs_subscriber);
//                }
//            }
//
//
//        }
//    }





//    //Set # of IPs meta data for IP access users
//    //step 1: get list of subscr_ids from subscriber_access_ips table
//    $gs_subscr_ids_ips_distinct = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT subscr_id FROM {$wpdb->prefix}gs_subscriber_access_ips ORDER BY subscr_id"));
//
//    foreach ($gs_subscr_ids_ips_distinct as $ip_subscr_id) {
//        //step 2: find user_id of subscr_id (gs_convert_subscriber table, old_subscriber_id => new_uid)
//        $gs_user_id_ips = $wpdb->get_results($wpdb->prepare("SELECT new_uid FROM {$wpdb->prefix}gs_convert_subscriber WHERE old_subscriber_id=%d ORDER BY ID", array($ip_subscr_id->subscr_id)));
//
//        if ($gs_user_id_ips) {
//            //step 3: count number of IP entries in usermeta table where user_id=new_uid and meta_key=ip-range*
//            $gs_user_id_ips_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}usermeta WHERE `user_id` = %d AND `meta_key` LIKE '%ip-range%'", array($gs_user_id_ips[0]->new_uid)));
//
//            //step 4: subtract 1 from the number of entries
//            $gs_user_id_ips_final_count = $gs_user_id_ips_count - 1;
//
//            // step 5: create/update entry for this user_id in usermeta table with meta-key="wppb_repeater_field_ip-authentication_extra_groups_count"
//            $sql = "INSERT INTO {$wpdb->prefix}usermeta (user_id,meta_key,meta_value) VALUES (%d,%s,%d) ON DUPLICATE KEY UPDATE meta_value = %d";
//            $sql = $wpdb->prepare($sql,$gs_user_id_ips[0]->new_uid,"wppb_repeater_field_ip-authentication_extra_groups_count",$gs_user_id_ips_final_count,$gs_user_id_ips_final_count);
//            $wpdb->query($sql);
//        }
//    }





//    //convert old gs subscriber alert to new gs subscriber email alert
//    $gs_convert_subscribers = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT(old_subscriber_id) old_s_id, new_subscriber_id new_s_id, new_uid FROM {$wpdb->prefix}gs_convert_subscriber where new_subscriber_id!=0"));
//    $gs_convert_arr = [];
//    $gs_convert_uarr = [];
//    foreach ($gs_convert_subscribers as $gs_cs){
//        $gs_convert_arr[$gs_cs->old_s_id] = $gs_cs->new_s_id;
//        $gs_convert_uarr[$gs_cs->old_s_id] = $gs_cs->new_uid;
//    }
//    $gs_old_subscriber_alerts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gs_subscriber_alerts order by id"));
//    foreach ($gs_old_subscriber_alerts as $gs_osa){
//        if (array_key_exists($gs_osa->subscr_id, $gs_convert_arr) && is_email($gs_osa->email)){
//            $gs_new_subscriber_alert = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gs_subscriber_email_alerts where subscr_id=% and email=%s", array($gs_convert_arr[$gs_osa->subscr_id], $gs_osa->email)));
//            if (!$gs_new_subscriber_alert){
//                $wpdb->insert(
//                    $wpdb->prefix . "gs_subscriber_email_alerts",
//                    [
//                        'ID'            => $gs_osa->id,
//                        'subscr_id'     => $gs_convert_arr[$gs_osa->subscr_id],
//                        'email'         => $gs_osa->email,
//                        'user_id'       => $gs_convert_uarr[$gs_osa->subscr_id],
//                        'first_name'    => get_user_meta($gs_convert_uarr[$gs_osa->subscr_id], 'first_name', true),
//                        'last_name'     => get_user_meta($gs_convert_uarr[$gs_osa->subscr_id], 'last_name', true),
//                        'pwd'           => $gs_osa->pwd,
//                        'status'        => $gs_osa->status,
//                        'updated_at'    => is_valid($gs_osa->updated_at)?$gs_osa->updated_at:'0000-00-00',
//                        'created_at'    => is_valid($gs_osa->created_at)?$gs_osa->created_at:'0000-00-00'
//                    ]
//                );
//
//                $geo_location_keys = get_geo_location_keys("Domestic");
//                $geo_location_values = get_geo_location_values("Domestic");
//                $subject_keys = get_subject_keys();
//                $subject_values = get_subject_values();
//                $program_type_keys = get_program_type_keys();
//                $program_type_values = get_program_type_values();
//
//                $id = $gs_osa->id;
//                $ea_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gs_subscriber_alert_mappings where alert_id=%d", array($id)));
//
//                $ea_form_id = 14; //email alert form id
//                $entry = [];
//                $entry['form_id'] = $ea_form_id;
//                $entry['26'] = $gs_osa->email;
//                $entry['24'] = $gs_osa->pwd;
//                $entry['25'] = $gs_osa->pwd;
//                $entry['27'] = 0;
//                $geo_prefix = "2";
//                $subject_prefix = "5";
//                $program_prefix = "29";
//                $entry_checkboxes = [];
//                foreach ($ea_fields as $ea_field){
//                    switch ($ea_field->type){
//                        case 'geo':
//                            if (is_numeric($ea_field->criteria)){
//                                $field_ind = array_search($ea_field->criteria, $geo_location_keys);
//                                if ($field_ind){
//                                    $entry_checkboxes[$geo_prefix . "." . $field_ind] = $ea_field->criteria;
//                                }
//                            }else{
//                                $field_ind = array_search($ea_field->criteria, $geo_location_values);
//                                if ($field_ind){
//                                    $entry_checkboxes[$geo_prefix . "." . $field_ind] = $ea_field->criteria;
//                                }
//                            }
//                            break;
//                        case 'subject':
//                            if (is_numeric($ea_field->criteria)){
//                                $field_ind = array_search($ea_field->criteria, $subject_keys);
//                                if ($field_ind){
//                                    $entry_checkboxes[$subject_prefix . "." . $field_ind] = $ea_field->criteria;
//                                }
//                            }else{
//                                $field_ind = array_search($ea_field->criteria, $subject_values);
//                                if ($field_ind){
//                                    $entry_checkboxes[$subject_prefix . "." . $field_ind] = $ea_field->criteria;
//                                }
//                            }
//                            break;
//                        case 'program':
//                            if (is_numeric($ea_field->criteria)){
//                                $field_ind = array_search($ea_field->criteria, $subject_keys);
//                                if ($field_ind){
//                                    $entry_checkboxes[$program_prefix . "." . $field_ind] = $ea_field->criteria;
//                                }
//                            }else{
//                                $field_ind = array_search($ea_field->criteria, $subject_values);
//                                if ($field_ind){
//                                    $entry_checkboxes[$program_prefix . "." . $field_ind] = $ea_field->criteria;
//                                }
//                            }
//                            break;
//                        case 'keyword':
//                            $entry['1'] = trim($ea_field->criteria);
//
//                            break;
//                        default:
//                            break;
//
//                    }
//                }
//
//                $e_id = GFAPI::add_entry($entry);
//                foreach ($entry_checkboxes as $key=>$ec){
//                    gform_update_meta( $e_id, $key, $ec, $ea_form_id);
//                }
//                $wpdb->update(
//                    $wpdb->prefix . "gs_subscriber_email_alerts",
//                    [
//                        'form_entry_id'=>$e_id
//                    ],
//                    [
//                        'id'=>$id
//                    ]
//                );
//
//
//            }
//
//
//        }
//    }
}
/*
 *Get key for geo_location
 */
function get_geo_location_keys($geo_locale){
    global $wpdb;
    $result = [];
    $where = "";
    if ($geo_locale){
        $where = " and geo_locale='" . $geo_locale . "' ";
    }
    $rows = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}gs_grant_geo_locations where geo_location !='' AND geo_location !='All Countries' AND geo_location !='All States' {$where} order by geo_location ASC"));
    $result[1] = 1;//all us states
    $input_id = 2;
    foreach ($rows as $key => $r){
        if ( $input_id % 10 == 0 ) {
            $input_id++;
        }
        $result[$input_id] = $r->id;
        $input_id++;
    }
    return $result;
}
/*
 *Get value for geo_location
 */
function get_geo_location_values($geo_locale){
    global $wpdb;
    $result = [];
    $where = "";
    if ($geo_locale != ""){
        $where = " and geo_locale='" . $geo_locale . "' ";
    }
    $rows = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}gs_grant_geo_locations where geo_location !='' AND geo_location !='All Countries' AND geo_location !='All States' {$where} order by geo_location ASC"));
    $result[1] = "All U.S. States and Territories";
    $input_id = 2;
    foreach ($rows as $key => $r){
        if ( $input_id % 10 == 0 ) {
            $input_id++;
        }
        $result[$input_id] = $r->geo_location;
        $input_id++;
    }
    return $result;
}

/*
 *Get keys for subjects
 */
function get_subject_keys(){
    global $wpdb;
    $result = [];
    $rows = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}gs_grant_subjects order by subject_title ASC"));

    $input_id = 1;
    foreach ($rows as $r){
        if ( $input_id % 10 == 0 ) {
            $input_id++;
        }
        $result[$input_id] = $r->id;
        $input_id++;
    }
    return $result;
}
/*
 *Get values for subjects
 */
function get_subject_values(){
    global $wpdb;
    $result = [];
    $rows = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}gs_grant_subjects order by subject_title ASC"));

    $input_id = 1;
    foreach ($rows as $r){
        if ( $input_id % 10 == 0 ) {
            $input_id++;
        }
        $result[$input_id] = $r->subject_title;
        $input_id++;
    }
    return $result;
}
/*
 *Get key for program types
 */
function get_program_type_keys(){
    global $wpdb;
    $result = [];
    $rows = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}gs_grant_programs order by program_title ASC"));

    $input_id = 1;
    foreach ($rows as $r){
        if ( $input_id % 10 == 0 ) {
            $input_id++;
        }
        $result[$input_id] = $r->id;
        $input_id++;
    }
    return $result;
}
/*
 *Get value for program types
 */
function get_program_type_values(){
    global $wpdb;
    $result = [];
    $rows = $wpdb->get_results($wpdb->prepare("select * from {$wpdb->prefix}gs_grant_programs order by program_title ASC"));

    $input_id = 1;
    foreach ($rows as $r){
        if ( $input_id % 10 == 0 ) {
            $input_id++;
        }
        $result[$input_id] = $r->program_title;
        $input_id++;
    }
    return $result;
}
function update_gs_user_meta($gs_user_id, $gs_user, $member_sub_id, $gs_subscriber){
    global $wpdb;
    $country_array = array(
        'AF'=>'AFGHANISTAN',
        'AL'=>'ALBANIA',
        'DZ'=>'ALGERIA',
        'AS'=>'AMERICAN SAMOA',
        'AD'=>'ANDORRA',
        'AO'=>'ANGOLA',
        'AI'=>'ANGUILLA',
        'AQ'=>'ANTARCTICA',
        'AG'=>'ANTIGUA AND BARBUDA',
        'AR'=>'ARGENTINA',
        'AM'=>'ARMENIA',
        'AW'=>'ARUBA',
        'AU'=>'AUSTRALIA',
        'AT'=>'AUSTRIA',
        'AZ'=>'AZERBAIJAN',
        'BS'=>'BAHAMAS',
        'BH'=>'BAHRAIN',
        'BD'=>'BANGLADESH',
        'BB'=>'BARBADOS',
        'BY'=>'BELARUS',
        'BE'=>'BELGIUM',
        'BZ'=>'BELIZE',
        'BJ'=>'BENIN',
        'BM'=>'BERMUDA',
        'BT'=>'BHUTAN',
        'BO'=>'BOLIVIA',
        'BA'=>'BOSNIA AND HERZEGOVINA',
        'BW'=>'BOTSWANA',
        'BV'=>'BOUVET ISLAND',
        'BR'=>'BRAZIL',
        'IO'=>'BRITISH INDIAN OCEAN TERRITORY',
        'BN'=>'BRUNEI DARUSSALAM',
        'BG'=>'BULGARIA',
        'BF'=>'BURKINA FASO',
        'BI'=>'BURUNDI',
        'KH'=>'CAMBODIA',
        'CM'=>'CAMEROON',
        'CA'=>'CANADA',
        'CV'=>'CAPE VERDE',
        'KY'=>'CAYMAN ISLANDS',
        'CF'=>'CENTRAL AFRICAN REPUBLIC',
        'TD'=>'CHAD',
        'CL'=>'CHILE',
        'CN'=>'CHINA',
        'CX'=>'CHRISTMAS ISLAND',
        'CC'=>'COCOS (KEELING) ISLANDS',
        'CO'=>'COLOMBIA',
        'KM'=>'COMOROS',
        'CG'=>'CONGO',
        'CD'=>'CONGO, THE DEMOCRATIC REPUBLIC OF THE',
        'CK'=>'COOK ISLANDS',
        'CR'=>'COSTA RICA',
        'CI'=>'COTE D IVOIRE',
        'HR'=>'CROATIA',
        'CU'=>'CUBA',
        'CY'=>'CYPRUS',
        'CZ'=>'CZECH REPUBLIC',
        'DK'=>'DENMARK',
        'DJ'=>'DJIBOUTI',
        'DM'=>'DOMINICA',
        'DO'=>'DOMINICAN REPUBLIC',
        'TP'=>'EAST TIMOR',
        'EC'=>'ECUADOR',
        'EG'=>'EGYPT',
        'SV'=>'EL SALVADOR',
        'GQ'=>'EQUATORIAL GUINEA',
        'ER'=>'ERITREA',
        'EE'=>'ESTONIA',
        'ET'=>'ETHIOPIA',
        'FK'=>'FALKLAND ISLANDS (MALVINAS)',
        'FO'=>'FAROE ISLANDS',
        'FJ'=>'FIJI',
        'FI'=>'FINLAND',
        'FR'=>'FRANCE',
        'GF'=>'FRENCH GUIANA',
        'PF'=>'FRENCH POLYNESIA',
        'TF'=>'FRENCH SOUTHERN TERRITORIES',
        'GA'=>'GABON',
        'GM'=>'GAMBIA',
        'GE'=>'GEORGIA',
        'DE'=>'GERMANY',
        'GH'=>'GHANA',
        'GI'=>'GIBRALTAR',
        'GR'=>'GREECE',
        'GL'=>'GREENLAND',
        'GD'=>'GRENADA',
        'GP'=>'GUADELOUPE',
        'GU'=>'GUAM',
        'GT'=>'GUATEMALA',
        'GN'=>'GUINEA',
        'GW'=>'GUINEA-BISSAU',
        'GY'=>'GUYANA',
        'HT'=>'HAITI',
        'HM'=>'HEARD ISLAND AND MCDONALD ISLANDS',
        'VA'=>'HOLY SEE (VATICAN CITY STATE)',
        'HN'=>'HONDURAS',
        'HK'=>'HONG KONG',
        'HU'=>'HUNGARY',
        'IS'=>'ICELAND',
        'IN'=>'INDIA',
        'ID'=>'INDONESIA',
        'IR'=>'IRAN, ISLAMIC REPUBLIC OF',
        'IQ'=>'IRAQ',
        'IE'=>'IRELAND',
        'IL'=>'ISRAEL',
        'IT'=>'ITALY',
        'JM'=>'JAMAICA',
        'JP'=>'JAPAN',
        'JO'=>'JORDAN',
        'KZ'=>'KAZAKSTAN',
        'KE'=>'KENYA',
        'KI'=>'KIRIBATI',
        'KP'=>'KOREA DEMOCRATIC PEOPLES REPUBLIC OF',
        'KR'=>'KOREA REPUBLIC OF',
        'KW'=>'KUWAIT',
        'KG'=>'KYRGYZSTAN',
        'LA'=>'LAO PEOPLES DEMOCRATIC REPUBLIC',
        'LV'=>'LATVIA',
        'LB'=>'LEBANON',
        'LS'=>'LESOTHO',
        'LR'=>'LIBERIA',
        'LY'=>'LIBYAN ARAB JAMAHIRIYA',
        'LI'=>'LIECHTENSTEIN',
        'LT'=>'LITHUANIA',
        'LU'=>'LUXEMBOURG',
        'MO'=>'MACAU',
        'MK'=>'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF',
        'MG'=>'MADAGASCAR',
        'MW'=>'MALAWI',
        'MY'=>'MALAYSIA',
        'MV'=>'MALDIVES',
        'ML'=>'MALI',
        'MT'=>'MALTA',
        'MH'=>'MARSHALL ISLANDS',
        'MQ'=>'MARTINIQUE',
        'MR'=>'MAURITANIA',
        'MU'=>'MAURITIUS',
        'YT'=>'MAYOTTE',
        'MX'=>'MEXICO',
        'FM'=>'MICRONESIA, FEDERATED STATES OF',
        'MD'=>'MOLDOVA, REPUBLIC OF',
        'MC'=>'MONACO',
        'MN'=>'MONGOLIA',
        'MS'=>'MONTSERRAT',
        'MA'=>'MOROCCO',
        'MZ'=>'MOZAMBIQUE',
        'MM'=>'MYANMAR',
        'NA'=>'NAMIBIA',
        'NR'=>'NAURU',
        'NP'=>'NEPAL',
        'NL'=>'NETHERLANDS',
        'AN'=>'NETHERLANDS ANTILLES',
        'NC'=>'NEW CALEDONIA',
        'NZ'=>'NEW ZEALAND',
        'NI'=>'NICARAGUA',
        'NE'=>'NIGER',
        'NG'=>'NIGERIA',
        'NU'=>'NIUE',
        'NF'=>'NORFOLK ISLAND',
        'MP'=>'NORTHERN MARIANA ISLANDS',
        'NO'=>'NORWAY',
        'OM'=>'OMAN',
        'PK'=>'PAKISTAN',
        'PW'=>'PALAU',
        'PS'=>'PALESTINIAN TERRITORY, OCCUPIED',
        'PA'=>'PANAMA',
        'PG'=>'PAPUA NEW GUINEA',
        'PY'=>'PARAGUAY',
        'PE'=>'PERU',
        'PH'=>'PHILIPPINES',
        'PN'=>'PITCAIRN',
        'PL'=>'POLAND',
        'PT'=>'PORTUGAL',
        'PR'=>'PUERTO RICO',
        'QA'=>'QATAR',
        'RE'=>'REUNION',
        'RO'=>'ROMANIA',
        'RU'=>'RUSSIAN FEDERATION',
        'RW'=>'RWANDA',
        'SH'=>'SAINT HELENA',
        'KN'=>'SAINT KITTS AND NEVIS',
        'LC'=>'SAINT LUCIA',
        'PM'=>'SAINT PIERRE AND MIQUELON',
        'VC'=>'SAINT VINCENT AND THE GRENADINES',
        'WS'=>'SAMOA',
        'SM'=>'SAN MARINO',
        'ST'=>'SAO TOME AND PRINCIPE',
        'SA'=>'SAUDI ARABIA',
        'SN'=>'SENEGAL',
        'SC'=>'SEYCHELLES',
        'SL'=>'SIERRA LEONE',
        'SG'=>'SINGAPORE',
        'SK'=>'SLOVAKIA',
        'SI'=>'SLOVENIA',
        'SB'=>'SOLOMON ISLANDS',
        'SO'=>'SOMALIA',
        'ZA'=>'SOUTH AFRICA',
        'GS'=>'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS',
        'ES'=>'SPAIN',
        'LK'=>'SRI LANKA',
        'SD'=>'SUDAN',
        'SR'=>'SURINAME',
        'SJ'=>'SVALBARD AND JAN MAYEN',
        'SZ'=>'SWAZILAND',
        'SE'=>'SWEDEN',
        'CH'=>'SWITZERLAND',
        'SY'=>'SYRIAN ARAB REPUBLIC',
        'TW'=>'TAIWAN, PROVINCE OF CHINA',
        'TJ'=>'TAJIKISTAN',
        'TZ'=>'TANZANIA, UNITED REPUBLIC OF',
        'TH'=>'THAILAND',
        'TG'=>'TOGO',
        'TK'=>'TOKELAU',
        'TO'=>'TONGA',
        'TT'=>'TRINIDAD AND TOBAGO',
        'TN'=>'TUNISIA',
        'TR'=>'TURKEY',
        'TM'=>'TURKMENISTAN',
        'TC'=>'TURKS AND CAICOS ISLANDS',
        'TV'=>'TUVALU',
        'UG'=>'UGANDA',
        'UA'=>'UKRAINE',
        'AE'=>'UNITED ARAB EMIRATES',
        'GB'=>'UNITED KINGDOM',
        'US'=>'UNITED STATES',
        'UM'=>'UNITED STATES MINOR OUTLYING ISLANDS',
        'UY'=>'URUGUAY',
        'UZ'=>'UZBEKISTAN',
        'VU'=>'VANUATU',
        'VE'=>'VENEZUELA',
        'VN'=>'VIET NAM',
        'VG'=>'VIRGIN ISLANDS, BRITISH',
        'VI'=>'VIRGIN ISLANDS, U.S.',
        'WF'=>'WALLIS AND FUTUNA',
        'EH'=>'WESTERN SAHARA',
        'YE'=>'YEMEN',
        'YU'=>'YUGOSLAVIA',
        'ZM'=>'ZAMBIA',
        'ZW'=>'ZIMBABWE',
    );
    $organization_types = array(
        'academic-college-or-university'                    =>'Academic - College or University',
        'academic-community-college'                        =>'Academic - Community College ',
        'academic-elementary-secondary-schools'             =>'Academic - Elem. - Secondary Schools',
        'research-institution-or-foundation-not-for-profit' =>'Research Inst. or Foundation (Not-for profit)',
        'public-library'                                    =>'Public Library',
        'other-not-for-profit-or-government-sponsored'      =>'Other (Not-for-profit or Government sponsored)',
        'corporate-for-profit'                              =>'Corporate / For-Profit',
        'hospital-medical-center'                           =>'Hospital / Medical Center',
        'for-profit-academic-institution'                   =>'For-Profit Academic Institution'
    );
    if (!is_valid($gs_user->first_name) && is_valid($gs_subscriber->first_name)){
        update_user_meta($gs_user_id, "first_name", $gs_subscriber->first_name);
    }
    if (!is_valid($gs_user->last_name) && is_valid($gs_subscriber->last_name)){
        update_user_meta($gs_user_id, "last_name", $gs_subscriber->last_name);
    }
    update_user_meta($gs_user_id, 'address_street_1', $gs_subscriber->address1);
    update_user_meta($gs_user_id, 'address_street_2', $gs_subscriber->address2);
    update_user_meta($gs_user_id, 'address_city', $gs_subscriber->city);
    update_user_meta($gs_user_id, 'address_state', $gs_subscriber->state);
    update_user_meta($gs_user_id, 'address_zip', $gs_subscriber->zip);
    $country_code = array_search(strtoupper($gs_subscriber->country), $country_array);
    if ($country_code === false){
        $country_code = "US";
    }
    update_user_meta($gs_user_id, 'address_country', $country_code);
    update_user_meta($gs_user_id, 'contact_number', $gs_subscriber->phone);
    //update_user_meta($gs_user_id, 'billing_email', $gs_subscriber->email);
    update_user_meta($gs_user_id, 'email_alerts', $gs_subscriber->e_alerts);
    update_user_meta($gs_user_id, 'bill_address_street_1', $gs_subscriber->bill_street_addr1);
    update_user_meta($gs_user_id, 'bill_address_street_2', $gs_subscriber->bill_street_addr2);
    update_user_meta($gs_user_id, 'bill_address_city', $gs_subscriber->bill_addr_city);
    update_user_meta($gs_user_id, 'bill_address_state', $gs_subscriber->bill_state);
    update_user_meta($gs_user_id, 'bill_address_zip', $gs_subscriber->bill_zip);

    $bill_country_code = array_search(strtoupper($gs_subscriber->bill_country), $country_array);
    if ($bill_country_code === false){
        $bill_country_code = "US";
    }
    update_user_meta($gs_user_id, 'bill_address_country', $gs_subscriber->bill_country);
    update_user_meta($gs_user_id, 'fte_population', $gs_subscriber->fte);

    update_user_meta($gs_user_id, 'technical_first_name', $gs_subscriber->technical_contact_f_name);
    update_user_meta($gs_user_id, 'technical_last_name', $gs_subscriber->technical_contact_l_name);
    update_user_meta($gs_user_id, 'technial_phone', $gs_subscriber->technical_contact_phone);
    update_user_meta($gs_user_id, 'technical_contact_email', $gs_subscriber->technical_contact_email);

    update_user_meta($gs_user_id, 'billing_email', $gs_subscriber->bill_contact_email);
    update_user_meta($gs_user_id, 'billing_first_name', $gs_subscriber->bill_contact_f_name);
    update_user_meta($gs_user_id, 'billing_last_name', $gs_subscriber->bill_contact_l_name);
    $organization_type = array_search(strtoupper($gs_subscriber->organization_type), $organization_types);
    if ($organization_type === false){
        $organization_type = "";
    }
    update_user_meta($gs_user_id, 'organization-type', $organization_type);

    //convert gs_subscriber_segments_mappings
    $gs_subscriber_segments_mappings = $wpdb->get_results($wpdb->prepare("SELECT segment_id FROM {$wpdb->prefix}gs_subscriber_segments_mappings where subscr_id=%d", array($gs_subscriber->id)));
    $customer_segments = [];
    foreach ($gs_subscriber_segments_mappings as $gss_mapping){
        array_push($customer_segments, $gss_mapping->segment_id);
    }
    if (count($customer_segments) > 0){
        update_user_meta($gs_user_id, 'customer_segments', implode(",", $customer_segments));
    }

    //convert gs_subscriber_referer_urls
    $gs_subscriber_referer_urls = $wpdb->get_results($wpdb->prepare("SELECT referer_url FROM {$wpdb->prefix}gs_subscriber_referer_urls where subscr_id=%d", array($gs_subscriber->subscr_id)));
    foreach ($gs_subscriber_referer_urls as $key => $gss_referer_url){
        if ($key == 0){
            update_user_meta($gs_user_id, 'referer-urls', $gss_referer_url->referer_url);
        }else{
            update_user_meta($gs_user_id, 'referer-urls_' . $key, $gss_referer_url->referer_url);
        }
    }

    //convert gs_subscriber_access_ips
    $gs_subscriber_access_ips = $wpdb->get_results($wpdb->prepare("SELECT ip FROM {$wpdb->prefix}gs_subscriber_access_ips where subscr_id=%d", array($gs_subscriber->subscr_id)));
    foreach ($gs_subscriber_access_ips as $key => $gsa_ips){
        if ($key == 0){
            update_user_meta($gs_user_id, 'ip-range', $gsa_ips->ip);
        }else{
            update_user_meta($gs_user_id, 'ip-range_' . $key, $gsa_ips->ip);
        }
    }
}
function get_subscription_id($user_id){
    global $wpdb;
    $sql = "select id from {$wpdb->prefix}pms_member_subscriptions where user_id=%d order by id desc";
    $row = $wpdb->get_row($wpdb->prepare($sql, [$user_id]));
    if ($row){
        return $row->id;
    }else{
        return 0;
    }
}
Class Grantselect_Customer_Management {
    private $table_subscriptionmeta;
    private $table_subscriptions;
    private $table_owner_statistics;
    private $table_subscriber_logs;
    private $per_pages;
    public function __construct(){
        global $wpdb;
        $this->table_subscriptionmeta   = $wpdb->prefix . "pms_member_subscriptionmeta";
        $this->table_subscriptions      = $wpdb->prefix . "pms_member_subscriptions";
        $this->table_owner_statistics   = $wpdb->prefix . "gs_owner_statistics";
        $this->table_subscriber_logs    = $wpdb->prefix . 'gs_subscriber_logs';
        $this->table_library_cards      = $wpdb->prefix . "gs_library_cards";
        $this->per_pages = [10, 20, 50, 100];
        $this->init();
    }

    private function init(){
        add_action('init', array($this, 'init_process'));
        add_filter('body_class', array($this, 'newsletter_body_class'), 10, 1);
        //url filter
        add_filter( 'page_link', array($this, 'check_page_url'), 10, 3 );
        add_filter("login_redirect", array($this, "my_login_redirect"), 10, 3 );
        add_filter("pms_member_subscription_statuses", array($this, "add_pms_statuses"), 10, 1);
        add_action( 'wp_enqueue_scripts', array($this, 'gs_load_scripts'));
        add_action('parse_request', array($this, 'download_gs_accounts_usage'));

        //shortcode for multi subscription
        add_shortcode("gs-subscription", array($this, "get_gs_subscription"));

        add_shortcode("gs-paid-accounts", array($this, "get_gs_paid_accounts"));
        add_shortcode("gs-trial-accounts", array($this, "get_gs_trial_accounts"));
        add_shortcode("gs-accounts-usage-download", array($this, "get_gs_accounts_usage_download"));
        add_shortcode("gs-nl-accounts", array($this, "get_gs_nl_accounts"));
        add_shortcode( 'gs-restrict', array($this, 'gs_content_restriction_shortcode'));
        add_shortcode( 'gs-referer-info', array($this, 'gs_referer_info'));
        add_shortcode( 'grantselect-new-subscriptions-ytd', array($this, 'gs_new_subscriptions_ytd'));
        add_shortcode( 'grantselect-paid-subscribers', array($this, 'gs_paid_subscribers'));
        add_shortcode( 'grantselect-trial-subscribers', array($this, 'gs_trial_subscribers'));
        add_shortcode("gs-monthly-visitor-count", array($this, "get_monthly_visitor_count"));
        add_shortcode("gs-expiring-subscriptions", array($this, "get_expiring_subscriptions"));
        add_shortcode("gs-pms-update", array($this, "render_gs_pms_update"));

        add_action( 'gform_after_submission_1', array($this, 'update_entry_user_info'), 10, 2 );
        add_action( 'gform_after_submission_2', array($this, 'update_entry_user_info'), 10, 2 );

        //ajax paginate
        add_action( 'wp_ajax_gs_account_list', array($this, 'get_gs_account_list') );
        //remove a gs account
        add_action( 'wp_ajax_gs_account_remove', array($this, 'remove_gs_account') );
        //remove selected gs accounts
        add_action( 'wp_ajax_gs_account_removes', array($this, 'remove_gs_accounts') );
        //update per_page in gs accounts
        add_action( 'wp_ajax_gs_per_page', array($this, 'update_gs_per_page') );

        //ajax newsletter accounts paginate
        add_action( 'wp_ajax_gs_nl_account_list', array($this, 'get_gs_nl_account_list') );
        //remove a gs newsletter
        add_action( 'wp_ajax_gs_nl_remove', array($this, 'remove_gs_nl_account') );
        //remove selected gs newsletter
        add_action( 'wp_ajax_gs_nl_removes', array($this, 'remove_gs_nl_accounts') );

        //process after account info update
        add_action( 'wppb_edit_profile_success', array($this, 'update_wppb_profile'), 20, 3 );

        //login for editor admin
        add_shortcode("gs-editor-admin", array($this, "get_gs_editor_admin"));
        //get sponsor detail info
        add_action( 'wp_ajax_gs_sponsor_detail', array($this, 'get_sponsor_detail') );
        //remove sponsor info
        add_action( 'wp_ajax_gs_sponsor_remove', array($this, 'remove_sponsor') );
        //add subject heading info
        add_action( 'wp_ajax_gs_subject_save', array($this, 'save_subject') );
        //remove sponsor info
        add_action( 'wp_ajax_gs_subject_remove', array($this, 'remove_subject') );
        //remove sponsor info
        add_action( 'wp_ajax_gs_sponsor_search', array($this, 'search_sponsors') );

        //remove sponsor info
        add_action( 'wp_ajax_gs_update_pms', array($this, 'update_pms') );

        if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'includes/class-usage.php' ) ){
            include GS_CM_PLUGIN_DIR_PATH . 'includes/class-usage.php';
        }
    }
    //add newsletter class in body
    public function newsletter_body_class($class) {
        if (is_user_logged_in()){
            $current_user = get_userdata(get_current_user_id());
            $user_roles = $current_user->roles;
            if (in_array("inactive_user", $user_roles)){
                wp_redirect(home_url("/"));exit;
            }
            $member          = pms_get_member( get_current_user_id() );
            $newsletter_subscript_plan = true;
            if ($member->subscriptions == null){
                $newsletter_subscript_plan = false;
            }else{
                foreach( $member->subscriptions as $subscript ){
                    $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                    if (!array_key_exists($plan->id, GS_NEWSLETTER_SUBSCRIPTION_PLANS)){
                        $newsletter_subscript_plan = false;
                        break;
                    }
                }
            }


            if (count($user_roles) == 1 && $newsletter_subscript_plan){
                $class[] = "newsletter-page";
            }
        }
        return $class;
    }
    public function gs_referer_info(){
        return "<pre>" . $_GET['referer'] . "</pre>";
    }
    /**
     * get gs new subscriptions for this year
     */
    public function gs_new_subscriptions_ytd($atts){
        global $wpdb;
        $current_user = get_userdata(get_current_user_id());
        $user_roles = $current_user->roles;
        if (in_array("gs_admin", $user_roles) || in_array("administrator", $user_roles)){
            $sql = "SELECT COUNT(DISTINCT(user_id)) cnt FROM {$wpdb->prefix}pms_member_subscriptions WHERE start_date LIKE '%d-%'";
            $count = $wpdb->get_var($wpdb->prepare($sql, array(date("Y"))));
            $title = "New Subscriptions YTD: ";
            if (isset($atts['title'])){
                $title = $atts['title'];
            }
            return "<p class='daily-dashboard'><span>" . $title . "</span>" . $count . "</p>";
        }else{
            return "";
        }
    }
    /**
     * get gs paid subscribers for gs admin or administrator
     */
    public function gs_paid_subscribers($atts){
        global $wpdb;
        $current_user = get_userdata(get_current_user_id());
        $user_roles = $current_user->roles;
        if (in_array("gs_admin", $user_roles) || in_array("administrator", $user_roles)){
            $sql = "SELECT COUNT(DISTINCT(user_id)) cnt FROM {$wpdb->prefix}pms_member_subscriptions WHERE STATUS='active' and subscription_plan_id!=%d";
            $count = $wpdb->get_var($wpdb->prepare($sql, array(GS_ACCOUNT_TRIAL_PLAN)));
            $title = "Paid Subscribers: ";
            if (isset($atts['title'])){
                $title = $atts['title'];
            }
            return "<p class='daily-dashboard'><span>" . $title . "</span>" . $count . "</p>";
        }else{
            return "";
        }
    }
    /**
     * get gs trial subscribers for gs admin or administrator
     */
    public function gs_trial_subscribers($atts){
        global $wpdb;
        $current_user = get_userdata(get_current_user_id());
        $user_roles = $current_user->roles;
        if (in_array("gs_admin", $user_roles) || in_array("administrator", $user_roles)){
            $sql = "SELECT COUNT(DISTINCT(user_id)) cnt FROM {$wpdb->prefix}pms_member_subscriptions WHERE STATUS='active' and subscription_plan_id=%d";
            $count = $wpdb->get_var($wpdb->prepare($sql, array(GS_ACCOUNT_TRIAL_PLAN)));
            $title = "Trial Subscribers: ";
            if (isset($atts['title'])){
                $title = $atts['title'];
            }
            return "<p class='daily-dashboard'><span>" . $title . "</span>" . $count . "</p>";
        }else{
            return "";
        }
    }
    /**
     * get monthly visistor count for access pages
     */
    public function get_monthly_visitor_count($atts)
    {
        global $wpdb;
        $user = wp_get_current_user();
        $sql = "";
        if (in_array("gs_admin", (array)$user->roles) || in_array("administrator", (array)$user->roles)) {
            $sql = "SELECT logs.day, COUNT(logs.day) cnt FROM (SELECT user_id, SUBSTR(created_at, 1, 10) day FROM {$wpdb->prefix}gs_subscriber_logs WHERE status=0 and created_at LIKE '%d-%d%') logs GROUP BY day order by day";
        } else if (in_array("gs_manager", (array)$user->roles)) {
            $sql = "SELECT logs.day, COUNT(logs.day) cnt FROM (SELECT user_id, SUBSTR(created_at, 1, 10) day FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=" . $user->ID . " and status=0 and created_at LIKE '%d-%d%') logs GROUP BY day order by day";
        } else {
            return "";
        }

//        if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//            echo "SQL: " . $sql . "<br>";
//            echo "SQL: " . $wpdb->prepare($sql, array(date("Y"), date("m"))) . "<br>";
//        }

        $result = $wpdb->get_results($wpdb->prepare($sql, array(date("Y"), date("m"))));
        $date = new DateTime(date("Y-m-d"));
        $date->modify('last day of this month');
        $last_day = $date->format('j');
        $today = date("j");
        $month_days = [];
        for ($i = 1; $i <= $last_day; $i++){
            if ($i <= $today){
                $month_days[$i] = 0;
            }else{
                $month_days[$i] = null;
            }

        }
        $chart_rows = '';
        foreach ($result as $day){
            $d = new DateTime(date($day->day));
            $month_days[$d->format("j")] = $day->cnt;
        }
        for ($i = 1; $i <= $last_day; $i++){
            $chart_rows .= "['" . $i . "', " . $month_days[$i] . ", 'Day:".$i . " Visitors:" .$month_days[$i]."'],";
        }
        $chart_height = 350;
        $output = <<<EOF
                    <script type="text/javascript">
                    google.charts.load('current', {'packages':['corechart', 'bar', 'line']});
                    google.charts.setOnLoadCallback(drawChart);
                    function drawChart() {
                        var monthlyVisitorCntData = new google.visualization.DataTable();
                        monthlyVisitorCntData.addColumn("string", "");
                        monthlyVisitorCntData.addColumn("number", "");
                        monthlyVisitorCntData.addColumn({ type: 'string', role: 'tooltip', 'p': {'html': true} });
                        monthlyVisitorCntData.addRows([
EOF;
        $output .= $chart_rows;
        $output .= <<<EOF
                            ]);
                        var monthlyViewCntOptions = {
EOF;
        $output .= "title: '" .date("F") . " Visitors" . "', ";
        $output .= <<<EOF
                            width: "100%",
                            height: 300,
                            tooltip: {isHtml: true},
                            vAxis: {
                                interpolateNulls: false,
                                gridlines:{
                                    count: 5,
                                },
                                legend: { position: 'bottom' }
                            },
                        };

                        var monthlyViewCntChart = new google.visualization.LineChart(document.getElementById('monthly_vistor_cnt_chart_div'));
                        monthlyViewCntChart.draw(monthlyVisitorCntData, monthlyViewCntOptions);

                    }
                    </script>

EOF;
        $output .= '<div id="monthly_vistor_cnt_chart_div" style="width: 100%;height:' . $chart_height . 'px"></div>';
        return $output;
    }
    /**
     * get expiring subscriptions for this month or next month
     */
    public function get_expiring_subscriptions($atts){
        global $wpdb;
        $user = wp_get_current_user();
        $sql = "";
        if ( !in_array("gs_admin", (array) $user->roles ) &&  !in_array( "administrator", (array) $user->roles )) {
            return "";
        }
        $month = "";
        $sql = "";
        if ($atts['month'] == "this"){
            $month = date("F");
            $this_month = date("Y-m-");
            $sql = "SELECT id, user_id, subscription_plan_id, expiration_date, billing_next_payment FROM {$wpdb->prefix}pms_member_subscriptions WHERE (subscription_plan_id!=%d and expiration_date LIKE '%s' OR billing_next_payment LIKE '%s') AND (status='active' OR status='expired') ORDER BY expiration_date ASC";
            $result = $wpdb->get_results($wpdb->prepare($sql, array(GS_ACCOUNT_TRIAL_PLAN, $this_month . "%", $this_month . "%")));
        }else if ($atts['month'] == "next"){
            $month = date('F',strtotime('first day of +1 month'));
            $next_month = date('Y-m-',strtotime('first day of +1 month'));
            $sql = "SELECT id, user_id, subscription_plan_id, expiration_date, billing_next_payment FROM {$wpdb->prefix}pms_member_subscriptions WHERE (subscription_plan_id!=%d and expiration_date LIKE '%s' OR billing_next_payment LIKE '%s') AND (status='active' OR status='expired') ORDER BY expiration_date ASC";
            $result = $wpdb->get_results($wpdb->prepare($sql, array(GS_ACCOUNT_TRIAL_PLAN, $next_month . "%", $next_month . "%")));
        }

        ob_start();
        if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_expiring_subscriptions.php' ) ){
            include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_expiring_subscriptions.php';
        }
        $content = ob_get_clean();
        return $content;
    }
    /**
     * update subscription status and expiration date
     */
    public function update_pms(){
        global $wpdb;
        $ids = $_POST['ids'];
        $start_dates = $_POST['start_dates'];
        $expiration_dates = $_POST['expiration_dates'];
        $statuses = $_POST['statuses'];
        for ($i = 0; $i < count($ids); $i++){
            $wpdb->update(
                $wpdb->prefix . "pms_member_subscriptions",
                [
                    'start_date'=>date("Y-m-d h:i:s", strtotime($start_dates[$i])),
                    'expiration_date'=>date("Y-m-d h:i:s", strtotime($expiration_dates[$i])),
                    'status'=>$statuses[$i]
                ],
                [
                    'id'=>$ids[$i]
                ]
            );

            // get list of ids of all members of this group
            $sql_query = $wpdb->prepare( "
                SELECT member_subscription_id FROM qpl_pms_member_subscriptionmeta 
                WHERE meta_value = %d AND meta_key=\"pms_group_subscription_owner\" ",
                $ids[$i]
            );
            $group_members = $wpdb->get_results( $sql_query );

            // loop through these ids and update the start date, expiration_date and status of each one
            if (count($group_members) > 0) {
                foreach ($group_members as $group_member) {
                    $wpdb->update(
                        $wpdb->prefix . "pms_member_subscriptions",
                        [
                            'start_date'=>date("Y-m-d h:i:s", strtotime($start_dates[$i])),
                            'expiration_date'=>date("Y-m-d h:i:s", strtotime($expiration_dates[$i])),
                            'status'=>$statuses[$i]
                        ],
                        [
                            'id'=>$group_member->member_subscription_id
                        ]
                    );
                }
            }

        }

        echo json_encode(['success'=>true]);
        exit;
    }
    /**
     * get the form for subscription
     */
    public function render_gs_pms_update($atts){
        global $wpdb;
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'gs-pms-render', GS_CM_PLUGIN_DIR_URL . 'js/pms_render.js', array('jquery'), GS_CUSTOMER_MANAGEMENT, true );

        $sql = "select * from {$wpdb->prefix}pms_member_subscriptions where id=%d";
        $subscription = $wpdb->get_row($wpdb->prepare($sql, [$atts['id']]));
        $sql = "select * from {$wpdb->prefix}pms_member_subscriptions where user_id=%d";
        $result = $wpdb->get_results($wpdb->prepare($sql, $subscription->user_id));
        ob_start();
        if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_subscription.php' ) ){
            include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_subscription.php';
        }
        $content = ob_get_clean();
        return $content;
    }
    public function init_process() {
        global $wpdb;

        if( !session_id() ){
            session_start();
        }

//echo $_SERVER['REMOTE_ADDR'] . "<br>";
// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177' || $_SERVER['REMOTE_ADDR'] == '107.77.208.168') {
//     echo "init<br>";
// 	// echo "<pre>";
// 	// print_r ($_SERVER);
// 	// echo "</pre>";
// 	echo $_SERVER['HTTP_REFERER'] . "<br>";
// 	//exit;
// }

        //add the subscriber log
        add_action( 'gs_add_subscriber_log', array($this, 'add_subscriber_log'), 10, 4);

        //list of pages that users authenticated by ip/url have access to
        $search_pages = [
            161, //welcome
            163, //quick search
            165, //advanced
            212, //search result
            167, //EA Register
            169, //EA Modify
            171, //EA Cancel
        ];
        $permission_urls = [
            (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'] . '/access'
        ];

        $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $page_id = url_to_postid($url);
        $user_id = 0;
        $is_allow_guest = false;

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "purl: " . $permission_urls[0] . "<br>";
//     echo "url: " . $url . "<br>";
//     echo "pid: " . $page_id . "<br>";
// }

        //check if current page is in list of allowed pages
        if (array_search($page_id, $search_pages) !== false){
            $is_allow_guest = true;
        }else{
            foreach ($permission_urls as $purl){
                if (strpos($url, $purl) !== false){
                    $is_allow_guest = true;
                    break;
                }
            }
        }

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "IAG:" . $is_allow_guest . "<br>";
//     //exit;
// }

        if (is_user_logged_in()){

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "User Logged In #####<br>";
// }

            $this->set_user_role(get_current_user_id());
            $register_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'] . '/register';
            if (strpos($url, $register_url) !== false){
                $current_user = get_userdata(get_current_user_id());
                $user_roles = $current_user->roles;
                if (in_array("gs_child", $user_roles)){
                    wp_redirect(home_url("/subscriber/edit-gs-account"));exit;
                }
            }
        }
        if ( $is_allow_guest ){

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "Is Allow Guest #####<br>";
// }

            $access_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'] . '/access';
            $access_result_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'] . '/access/search-results';
            $access_detail_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'] . '/access/grant-details';
            $url_log = false;
            if (strpos($url, $access_url) !== false && strpos($url, $access_result_url) === false && strpos($url, $access_detail_url) === false){
                $url_log = true;
            }

            if (is_user_logged_in()){
                $user_id = get_current_user_id();
                $current_user = get_userdata(get_current_user_id());
                $user_roles = $current_user->roles;
                if (in_array("inactive_user", $user_roles)){
                    wp_redirect(home_url("/"));exit;
                }
                $member          = pms_get_member( get_current_user_id() );
                $newsletter_subscript_plan = true;
                foreach( $member->subscriptions as $subscript ){
                    if ($subscript['status'] == 'suspended'){
                        wp_redirect(home_url("/suspended-account"));exit;
                    }
                }
                if ($member->subscriptions == null){
                    $newsletter_subscript_plan = false;
                }else{
                    $cnt = 0;
                    foreach( $member->subscriptions as $subscript ){
                        if ($subscript['status'] != 'active')
                            continue;
                        $cnt++;
                        $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                        if (!array_key_exists($plan->id, GS_NEWSLETTER_SUBSCRIPTION_PLANS)){
                            $newsletter_subscript_plan = false;
                            break;
                        }
                    }
                }
                if (count($user_roles) == 1 && $cnt > 0 && $newsletter_subscript_plan){
                    $_SESSION['guest_subscriber_id']    = 0;
                    wp_redirect(home_url("/newsletter"));exit;
                    return;
                }
                if ($cnt == 0 && !in_array("administrator", $user_roles)){
                    $sql = "select * from {$wpdb->prefix}gs_ip_url_auth where user_id=%d and pms_group_id is null";
                    $iua = $wpdb->get_row($wpdb->prepare($sql, [$user_id]));
                    if ($iua){
                        wp_redirect(home_url("/account/profile/"));
                    }else{
                        wp_redirect(home_url("/account/subscriptions/"));
                    }
                    
                    exit;
                }

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "TEST <br>";
//     echo "<pre>";
//     print_r($user_roles);
//     echo "</pre>";
//     if (in_array("gs_child", $user_roles)){
//         echo "<pre>";
//         print_r($member->subscriptions);
//         echo "</pre>";
//     }
//     exit;
// }

                if ($url_log){
                    do_action("gs_add_subscriber_log", $user_id, 4, "access", $url);
                }
            }

            /*if (isset($_SESSION['guest_user_id'])){
                if (is_user_logged_in()){
                    $_SESSION['guest_user_id']          = get_current_user_id();
                    $pms_subscription = $wpdb->get_row($wpdb->prepare("select id from {$wpdb->prefix}pms_member_subscriptions where user_id=%d", array(get_current_user_id())));
                    if ($pms_subscription){
                        $_SESSION['guest_subscriber_id']    = $pms_subscription->id;
                    }else{
                        $_SESSION['guest_subscriber_id']    = 0;
                    }
                }
                return;
            }*/

            if (!is_user_logged_in()){

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "NOT Is User Logged In - 1531 #####<br>";
// }

                $ip = $this->get_the_user_ip();
                $ip_row = $wpdb->get_row($wpdb->prepare("select user_id from {$wpdb->prefix}usermeta where meta_key LIKE 'ip-range%' AND meta_value!='' AND %s LIKE CONCAT(meta_value, '%')", array($ip)));
                $user_id = 0;
                $user_ids = [];
                if ($ip_row){
                    $user_id = $ip_row->user_id;
                    array_push($user_ids, $user_id);
                }
                $ip_rows = $wpdb->get_results("select user_id, meta_value from {$wpdb->prefix}usermeta where meta_key LIKE 'ip-range%' AND meta_value like '%-%' ORDER BY umeta_id DESC");    
                foreach ($ip_rows as $ipr){
                    $ip_ranges = explode("-", $ipr->meta_value);
                    if (count($ip_ranges) == 2){
                        if (ip2long(trim($ip_ranges[0])) && ip2long(trim($ip_ranges[1]))){
                            $ip_range_min = min(ip2long(trim($ip_ranges[0])), ip2long(trim($ip_ranges[1])));
                            $ip_range_max = max(ip2long(trim($ip_ranges[0])), ip2long(trim($ip_ranges[1])));
                            $ip_long = ip2long(trim($ip));
                            if ($ip_range_min && $ip_range_max && $ip_long && $ip_range_min <= $ip_long && $ip_range_max >= $ip_long ){
                                $user_id = $ipr->user_id;
                                array_push($user_ids, $user_id);
                            }
                        }
                    }
                }
                
// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "IP: " . $ip . "<br>";
//     //exit;
// }

                if (count($user_ids) > 0){
// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
                    $user_id = 0;
                    $active_account = false;
                    foreach ($user_ids as $u_id){
                        $member = pms_get_member( $u_id );
                        // echo "IP Row<br>";
                        // echo "UID: $user_id<br>";
                        // echo "CM:" . count($member->subscriptions) . "<br>";
                        
                        foreach( $member->subscriptions as $subscript ){
                            // echo "ST: " . $subscript['status'] . "<br>";
                            $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                            //echo "P: $plan<br>";
                            if ( $subscript['status'] == 'active' && in_array($plan->id, GS_GRANTSEARCH_SUBSCRIPTION_PLANS) ) {
                                $active_account = true;
                                $user_id = $u_id;
                                break;
                            }
                        }
                        if ($active_account){
                            break;
                        }
                    }
                    // echo "AA: " . $active_account . "<br>";
                    if (!$active_account) {
                        wp_redirect(home_url("/plans/"));
                        exit;
                    }
                    // exit;
// }


// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "UID: " . $user_id . "<br>";
//     //exit;
// }

                    if (!isset($_SESSION['LAST_ACTIVITY']) || (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 600))) {

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "NOT LAST_ACTIVITY<br>";
//     //exit;
// }

                        // first request, or last request was more than 10 minutes ago
                        session_unset();     // unset $_SESSION variable for the run-time
                        session_destroy();   // destroy session data in storage
                        session_start();    // start new session

                        // log this as a new session login
                        do_action("gs_add_subscriber_log", $user_id, 0, "login");
                    }
                    $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
                    
                    

                }else{
                    if (isset($_SESSION['referer_url'])){
                        $referer_url = $_SESSION['referer_url'];
                    }else{
                        $referer_url = getenv("HTTP_REFERER");
                    }

                    $sqlquery = $wpdb->prepare("select user_id from {$wpdb->prefix}usermeta where meta_key LIKE 'referer-urls%' AND meta_value!='' AND %s LIKE CONCAT(meta_value, '%')", array($referer_url));
                    $url_row = $wpdb->get_row($sqlquery);

                    if ($url_row){

                        $user_id = $url_row->user_id;

                        $member = pms_get_member( $user_id );
                        $active_account = false;
                        foreach( $member->subscriptions as $subscript ){
                            $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                            if ( $subscript['status'] == 'active' && in_array($plan->id, GS_GRANTSEARCH_SUBSCRIPTION_PLANS) ) {
                                $active_account = true;
                                break;
                            }
                        }
                        if (!$active_account) {
                            wp_redirect(home_url("/plans/"));
                            exit;
                        }

                        if (!isset($_SESSION['LAST_ACTIVITY']) || ((isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 600))) || !isset($_SESSION['referer_url'])) {
                            // first request, or last request was more than 10 minutes ago, or this is the first request from the referer URL (start of new session)
                            session_unset();     // unset $_SESSION variable for the run-time
                            session_destroy();   // destroy session data in storage
                            session_start();    // start new session

                            // log this as a new session login
                            do_action("gs_add_subscriber_log", $user_id, 0, "login", $referer_url);
                        }
                        $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
                        $_SESSION['referer_url'] = $referer_url;
                    } else {
                        // check if card session exists
                        if (isset($_SESSION['sip2_auth']) && isset($_SESSION['sip2_lib']) && isset($_SESSION['sip2_card'])) {
                            // check if library_number/card_number was expired
                            $library_number     = $_SESSION['sip2_lib'];
                            $card_number        = $_SESSION['sip2_card'];
                            $time_now           = time();

                            $query = "select * from {$this->table_library_cards} where library_number={$library_number} AND card_number={$card_number} AND expired_at > {$time_now}";
                            $card_rows = $wpdb->get_results($query);
                            if (count($card_rows) == 0) {
                                // unset card sessions
                                unset($_SESSION['sip2_auth']);
                                unset($_SESSION['sip2_lib']);
                                unset($_SESSION['sip2_card']);

                                // redirect to log-evergreen url
                                wp_redirect(home_url("/login-evergreen/{$library_number}"));
                                exit;
                            }

                            $user_id = $library_number;
                        }
                    }
                }
                if ($user_id == 0){

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "UID == 0<br>";
//     //exit;
// }

                    unset($_SESSION['guest_user_id']);
                    unset($_SESSION['guest_subscriber_id']);
                    $temp_referer = getenv("HTTP_REFERER");
                    if (isset($_GET['mode']) && $_GET['mode'] == "test" && $temp_referer != "" && parse_url($temp_referer, PHP_URL_HOST) != parse_url(home_url(), PHP_URL_HOST)){
                        wp_redirect(home_url("/site-referer-info?mode=test&referer=" . $temp_referer));exit;
                    }else{

// log state that caused redirect to login screen
                        $logdata  = "Timestamp: " . date("Y-m-d H:i:s",time()) . "\r\n";
                        $logdata .= "Session Referer URL: " . $_SESSION['referer_url'] . "\r\n";
                        $logdata .= "HTTP Referer: " . getenv("HTTP_REFERER") . "\r\n";
                        $logdata .= "referer_url: " . $referer_url . "\r\n";
                        $logdata .= "IP: " . $ip . "\r\n\r\n";
//$logdata .= "SQL: " . $sqlquery . "\r\n\r\n";
                        $file = plugin_dir_path( __FILE__ ) . '/login_redirect.txt';
                        $open = fopen( $file, "a" );
                        $write = fputs( $open, $logdata );
                        fclose( $open );

                        wp_redirect(home_url("/login"));exit;
                    }

                }else{

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "GUID: " . $_SESSION['guest_user_id'] . "<br>";
//     //exit;
// }

                    $_SESSION['guest_user_id']          = $user_id;
                    if ($url_log){
                        do_action("gs_add_subscriber_log", $user_id, 4, "access", $url);
                    }
                    $pms_subscription = $wpdb->get_row($wpdb->prepare("select id from {$wpdb->prefix}pms_member_subscriptions where user_id=%d", array($user_id)));
                    if ($pms_subscription){
                        $_SESSION['guest_subscriber_id']    = $pms_subscription->id;
                    }else{
                        $_SESSION['guest_subscriber_id']    = 0;
                    }
                }

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "GSID: " . $_SESSION['guest_subscriber_id'] . "<br>";
//     //exit;
// }

            }

        }

// if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//     echo "END OF init_process<br>";
//     //exit;
// }

    }
    

    function gs_content_restriction_shortcode( $atts, $content = null ) {
        global $wpdb;

        $args = shortcode_atts(
            array(
                'user_roles'    => array(),
                'display_to'    => '',
                'message'       => ''
            ),
            $atts
        );

        // Message to replace the content of checks do not match
        if( ! empty( $args['message'] ) ) {
            $message = '<span class="wppb-shortcode-restriction-message">' . $args['message'] . '</span>';
        } else {
            $type = ( is_user_logged_in() ? 'logged_in' : 'logged_out' );
            $message = wpautop( wppb_get_restriction_content_message( $type ) );
        }

        /*
         * Filter the message
         *
         * @param string $message   - the current message, whether it is the default one from the settings or
         *                            the one set in the shortcode attributes
         * @param array  $args      - the shortcode attributes
         *
         */
        $message = apply_filters( 'wppb_content_restriction_shortcode_message', $message, $args );
        $guest_user = false;
        if (!is_user_logged_in()){
            if (isset($_SESSION['guest_user_id'])){
                $guest_user = true;
            }else{
                $ip = $this->get_the_user_ip();
                $ip_row = $wpdb->get_row($wpdb->prepare("select user_id from {$wpdb->prefix}usermeta where meta_key LIKE 'ip-range%' AND meta_value!='' AND %s LIKE CONCAT(meta_value, '%')", array($ip)));
                $user_id = 0;
                if ($ip_row){
                    $user_id = $ip_row->user_id;
                }else{
                    $ip_rows = $wpdb->get_results("select user_id, meta_value from {$wpdb->prefix}usermeta where meta_key LIKE 'ip-range%' AND meta_value like '%-%' ORDER BY umeta_id DESC");    
                    foreach ($ip_rows as $ipr){
                        $ip_ranges = explode("-", $ipr->meta_value);
                        if (count($ip_ranges) == 2){
                            if (ip2long(trim($ip_ranges[0])) && ip2long(trim($ip_ranges[1]))){
                                $ip_range_min = min(ip2long(trim($ip_ranges[0])), ip2long(trim($ip_ranges[1])));
                                $ip_range_max = max(ip2long(trim($ip_ranges[0])), ip2long(trim($ip_ranges[1])));
                                $ip_long = ip2long(trim($ip));
                                if ($ip_range_min && $ip_range_max && $ip_long && $ip_range_min <= $ip_long && $ip_range_max >= $ip_long ){
                                    $user_id = $ipr->user_id;
                                    break;                                    
                                }
                            }
                        }
                    }
                }
                if ($user_id == 0){
                    $referer_url = getenv("HTTP_REFERER");
                    $url_row = $wpdb->get_row($wpdb->prepare("select user_id from {$wpdb->prefix}usermeta where meta_key LIKE 'referer-urls%' AND meta_value!='' AND %s LIKE CONCAT(meta_value, '%')", array($referer_url)));
                    if ($url_row){
                        $user_id = $url_row->user_id;
                    } else {
                        if (isset($_SESSION['sip2_auth']) && isset($_SESSION['sip2_lib']) && isset($_SESSION['sip2_card'])) {
                            $library_number     = $_SESSION['sip2_lib'];
                            $card_number        = $_SESSION['sip2_card'];
                            $time_now           = time();

                            $query = "select * from {$this->table_library_cards} where library_number={$library_number} AND card_number={$card_number} AND expired_at > {$time_now}";
                            $card_rows = $wpdb->get_results($query);
                            if (count($card_rows) > 0) {
                                $user_id = $library_number;
                            }
                        }
                    }
                }
                if ($user_id != 0){
                    $guest_user = true;
                }
            }

        }
        if( is_user_logged_in() || $guest_user ) {
            // Show for administrators
            if( current_user_can( 'manage_options' ) ) {
                return do_shortcode( $content );
            }

            if( $args['display_to'] == 'not_logged_in' ) {
                return $message;
            }

            if( ! empty( $args['user_roles'] ) ) {
                $user_roles = array_map( 'trim', explode( ',', $args['user_roles'] ) );
                $user_data = get_userdata( get_current_user_id() );

                if( ! empty( $user_data->roles ) ) {
                    $common_user_roles = array_intersect( $user_roles, $user_data->roles );

                    if( ! empty( $common_user_roles ) ) {
                        return do_shortcode( $content );
                    } else {
                        return $message;
                    }
                }
            } else {
                return do_shortcode( $content );
            }
        } else {
            if( $args['display_to'] == 'not_logged_in' ) {
                return do_shortcode( $content );
            } else {
                return $message;
            }
        }

    }

    public function gs_load_scripts() {
        wp_enqueue_script( 'jquery-ui-autocomplete'); //same as above
        wp_enqueue_style( 'gs-customer', GS_CM_PLUGIN_DIR_URL . 'css/gs-customer-management.css', array(),  GS_CUSTOMER_MANAGEMENT);
        if (is_user_logged_in()){
            $user_id = get_current_user_id();

            $user = get_userdata($user_id);

            $only_gs_admin = false;
            $has_gs_admin = false;
            $has_administrator = false;
            foreach( $user->roles as $role ) {
                if ($role == GS_ADMIN){
                    $has_gs_admin = true;
                }
                if ($role == 'administrator'){
                    $has_administrator = true;
                }
            }
            if ($has_gs_admin && !$has_administrator){
                wp_enqueue_style('gs_admin_fe_css', GS_CM_PLUGIN_DIR_URL . '/css/gs-admin.css', array(),  GS_CUSTOMER_MANAGEMENT);
            }
        }

    }

    public function get_the_user_ip() {
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
    public function set_user_role($user_id){
        $subscription_plan_ids = [];
        $owner_subscription_id = 0;
        $user = get_userdata($user_id);
        $member          = pms_get_member( $user_id );
        $subscript_cnt = 0;
        $has_individual_role = false;
        $has_newsletter_role = false;
        foreach( $member->subscriptions as $subscript ){
            if ($subscript['status'] == 'suspended'){
                return;
            }
        }
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
        if ($subscript_cnt != 0 ){
            if ($has_newsletter_role){
                $user->add_role("newsletter");
            }
            if ($owner_subscription_id != 0){
                if (pms_gm_is_group_owner( $owner_subscription_id )){
                    $user->add_role("gs_manager");
                    $user->remove_role("gs_child");
                }else{
                    $user->add_role("gs_child");
                    $user->remove_role("gs_manager");
                }
            }
            if ($has_individual_role){
                $user->add_role("gs_individual");
            }

            //$user->remove_role("subscriber");
        }else{
            $user->remove_role("newsletter");
            $user->remove_role("gs_manager");
            $user->remove_role("gs_child");
            $user->remove_role("gs_individual");
            //$user->add_role("subscriber");
        }
    }
    public function my_login_redirect($url, $request, $user){
        global $wpdb;
        if (isset( $user->roles )){
            $user_id = $user->ID;
            if ($user_id == 0)
                return $url;

            $this->set_user_role($user_id);
            if (in_array("gs_admin", $user->roles)){
                return home_url( '/subscriber' );
            }

            $this->add_subscriber_log_directly($user_id, 0, "login");
            if (count($user->roles) == 1 && in_array("subscriber", $user->roles)){
                $url = home_url("/plans/grant-watch/");
            }
        }else{
            $user_id = $user->ID;

            if ($user_id != NULL && $user_id != 0){
                $user = get_userdata($user_id);
                //$user->add_role("subscriber");
            }
            $this->add_subscriber_log_directly($user_id, 0, "login");
        }
        return $url;
    }
    public function add_subscriber_log_directly($user_id, $status, $content, $url=""){
        // Get member
        global $wpdb, $wp_session;
        $sid = 0;
        if (strpos($status, "_") !== false){
            $status_info = explode("_", $status);
            $status = $status_info[0];
            $sid = $status_info[1];
        }

        $subscription_id = 0;
        $subscription = null;
        $user = get_userdata($user_id);
        $member          = pms_get_member( $user_id );

        if ($member->subscriptions){
            foreach( $member->subscriptions as $subscript ){

                $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                if( $subscript['subscription_plan_id'] == GS_ACCOUNT_TRIAL_PLAN){
                    $sql = "select id, user_id, subscription_plan_id, start_date, expiration_date from {$wpdb->prefix}pms_member_subscriptions where id=%d";
                    $row = $wpdb->get_row($wpdb->prepare($sql, [$subscript['id']]));

                    $user = get_userdata($row->user_id);
                    $email = $user->user_email;

                    if ($row->subscription_plan_id == GS_ACCOUNT_TRIAL_PLAN && preg_match('/^\w+@\w+\.edu$/i', $email) > 0){
                        $expiration_date = $row->start_date;
                        $expiration_date = strtotime($expiration_date);
                        $expiration_date = strtotime("+14 day", $expiration_date);
                        $expiration_date = date("Y-m-d H:i:s", $expiration_date);
                        $wpdb->update(
                            $wpdb->prefix ."pms_member_subscriptions",
                            ['expiration_date'=>$expiration_date],
                            ['id'=>$subscript['id']]
                        );
                    }

                }
            }
            foreach( $member->subscriptions as $subscript ){
                $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                if( $plan->type != 'group' )
                    continue;
                $subscription = pms_get_member_subscription($subscript['id']);
                $subscription_id = $subscript['id'];
                break;
            }
        }
        if ($subscription_id != 0){
            if (!pms_gm_is_group_owner( $subscription_id )){
                $owner_subscribermeta = $wpdb->get_row(
                    $wpdb->prepare("SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_owner' AND member_subscription_id=%s", array($subscription_id) )
                );
                $owner_subscriber_id = $owner_subscribermeta->meta_value;
            }else{
                $owner_subscriber_id = $subscription_id;
            }

            $row = [];
            $owner_subscriber = $wpdb->get_row(
                $wpdb->prepare("SELECT user_id FROM {$this->table_subscriptions} WHERE id=%s", array($owner_subscriber_id) )
            );
            $row['manager_id']      = $owner_subscriber->user_id;
            $row['manager_name']    = pms_get_member_subscription_meta( $owner_subscriber_id, 'pms_group_name', true );
            $row['user_id']         = $user_id;
            $row['user_name']       = $user->user_login;
            $row['ip']              = $this->get_the_user_ip();
            $row['created_at']      = date("Y-m-d H:i:s");
            if ($url == ""){
                $row['url']         = home_url($_SERVER['REQUEST_URI']);
            }else{
                $row['url']         = $url;
            }
            $row['status']          = $status;
            $row['sid']             = $sid;
            $row['content']         = $content;
            $wpdb->insert(
                $this->table_subscriber_logs,
                $row
            );

            if ($status == 0){
                $owner_statistics = $wpdb->get_row(
                    $wpdb->prepare("SELECT id, count FROM {$this->table_owner_statistics} WHERE owner_sid=%d", array($owner_subscriber_id) )
                );
                if ($owner_statistics == null){
                    $wpdb->insert(
                        $this->table_owner_statistics,
                        array(
                            'owner_sid' => $owner_subscriber_id,
                            'owner_name'=> $row['manager_name'],
                            'count'     => 1
                        )
                    );
                }else{
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
            }

        }else{
            if ($member->subscriptions){
                foreach( $member->subscriptions as $subscript ){
                    $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                    $subscription = pms_get_member_subscription($subscript['id']);
                    $subscription_id = $subscript['id'];
                    break;
                }
            }

            $owner_subscriber_id = $subscription_id;
            $row = [];
            $owner_subscriber = $wpdb->get_row(
                $wpdb->prepare("SELECT user_id FROM {$this->table_subscriptions} WHERE id=%s", array($owner_subscriber_id) )
            );

            $row['manager_id']      = $owner_subscriber->user_id;
            $row['manager_name']    = pms_get_member_subscription_meta( $owner_subscriber_id, 'pms_group_name', true );
            $row['user_id']         = $user_id;
            $row['user_name']       = $user->user_login;
            $row['ip']              = $this->get_the_user_ip();
            $row['created_at']      = date("Y-m-d H:i:s");
            if ($url == ""){
                $row['url']         = home_url($_SERVER['REQUEST_URI']);
            }else{
                $row['url']         = $url;
            }

            $row['status']          = $status;
            $row['sid']             = $sid;
            $row['content']         = $content;
            $wpdb->insert(
                $this->table_subscriber_logs,
                $row
            );
        }
    }
    public function add_subscriber_log($user_id, $status, $content, $url=""){
        // Get member
        global $wpdb, $wp_session;
        $sid = 0;
        if (strpos($status, "_") !== false){
            $status_info = explode("_", $status);
            $status = $status_info[0];
            $sid = $status_info[1];
        }

        $subscription_id = 0;
        $subscription = null;
        $user = get_userdata($user_id);
        $member          = pms_get_member( $user_id );
        if ($member->subscriptions){
            foreach( $member->subscriptions as $subscript ){
                $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                if( $plan->type != 'group' )
                    continue;
                $subscription = pms_get_member_subscription($subscript['id']);
                $subscription_id = $subscript['id'];
                break;
            }
        }
        if ($subscription_id != 0){
            if (!pms_gm_is_group_owner( $subscription_id )){
                $owner_subscribermeta = $wpdb->get_row(
                    $wpdb->prepare("SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_owner' AND member_subscription_id=%s", array($subscription_id) )
                );
                $owner_subscriber_id = $owner_subscribermeta->meta_value;
            }else{
                $owner_subscriber_id = $subscription_id;
            }

            $row = [];
            $owner_subscriber = $wpdb->get_row(
                $wpdb->prepare("SELECT user_id FROM {$this->table_subscriptions} WHERE id=%s", array($owner_subscriber_id) )
            );

            $row['manager_id']      = $owner_subscriber->user_id;
            $row['manager_name']    = pms_get_member_subscription_meta( $owner_subscriber_id, 'pms_group_name', true );

            if (is_user_logged_in()){
                $row['user_id']         = $user_id;
                $row['user_name']       = $user->user_login;
            }else{
                $row['user_id']         = 0;
                $row['user_name']       = "";
            }

            $row['ip']              = $this->get_the_user_ip();
            $row['created_at']      = date("Y-m-d H:i:s");
            if ($url == ""){
                $row['url']         = home_url($_SERVER['REQUEST_URI']);
            }else{
                $row['url']         = $url;
            }

            $row['status']          = $status;
            $row['sid']             = $sid;
            $row['content']         = $content;

            $wpdb->insert(
                $this->table_subscriber_logs,
                $row
            );
            if ($status == 0){
                $owner_statistics = $wpdb->get_row(
                    $wpdb->prepare("SELECT id, count FROM {$this->table_owner_statistics} WHERE owner_sid=%d", array($owner_subscriber_id) )
                );
                if ($owner_statistics == null){
                    $wpdb->insert(
                        $this->table_owner_statistics,
                        array(
                            'owner_sid' => $owner_subscriber_id,
                            'owner_name'=> $row['manager_name'],
                            'count'     => 1
                        )
                    );
                }else{
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
            }

        }else{
            if ($member->subscriptions){
                foreach( $member->subscriptions as $subscript ){
                    $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                    $subscription = pms_get_member_subscription($subscript['id']);
                    $subscription_id = $subscript['id'];
                    break;
                }
            }

            $owner_subscriber_id = $subscription_id;
            $row = [];
            $owner_subscriber = $wpdb->get_row(
                $wpdb->prepare("SELECT user_id FROM {$this->table_subscriptions} WHERE id=%s", array($owner_subscriber_id) )
            );

            $row['manager_id']      = $owner_subscriber->user_id;
            $row['manager_name']    = pms_get_member_subscription_meta( $owner_subscriber_id, 'pms_group_name', true );
            if (is_user_logged_in()){
                $row['user_id']         = $user_id;
                $row['user_name']       = $user->user_login;
            }else{
                $row['user_id']         = 0;
                $row['user_name']       = "";
            }

            $row['ip']              = $this->get_the_user_ip();
            $row['created_at']      = date("Y-m-d H:i:s");
            if ($url == ""){
                $row['url']         = home_url($_SERVER['REQUEST_URI']);
            }else{
                $row['url']         = $url;
            }

            $row['status']          = $status;
            $row['sid']             = $sid;
            $row['content']         = $content;
            $wpdb->insert(
                $this->table_subscriber_logs,
                $row
            );
        }
    }
    public function add_pms_statuses($statuses){
        $statuses['suspended'] = 'Suspended';
        return $statuses;
    }
    public function download_csv($results, $filename, $delimiter=","){
        global $wpdb;
        ob_end_clean();
        // tell the browser it's going to be a csv file
        header('Content-Type: application/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        $f = fopen('php://output', 'w');
        fputcsv(
            $f,
            array(
                "user_id",
                "subscr_id",
                "organization",
                "first_name",
                "last_name",
                "address1",
                "addresss2",
                "city",
                "state",
                "zip",
                "country",
                "phone",
                "email",
                "comment",
                "satus",
                "start_date",
                "expire",
                "updated_at",
                "created_at"
            ),
            $delimiter
        );
        foreach ($results as $key => $r){
            $value = json_decode(json_encode($r), true);
            $row = [];
            $user_info = get_userdata($value['user_id']);
            $row[0] = $value['user_id'];
            $row[1] = $value['id'];
            $pms_meta = $wpdb->get_row(
                $wpdb->prepare(
                    "select meta_value from {$wpdb->prefix}pms_member_subscriptionmeta where meta_key='pms_group_name' AND member_subscription_id=%id",
                    array($value['id'])
                ));
            if ($pms_meta != null){
                $row[2] = $pms_meta->meta_value;
            }else{
                $row[2] = "";
            }

            $row[3] = get_user_meta($value['user_id'], 'first_name', true);
            $row[4] = get_user_meta($value['user_id'], 'last_name', true);
            $row[5] = get_user_meta($value['user_id'], 'address_street_1', true);
            $row[6] = get_user_meta($value['user_id'], 'address_street_2', true);
            $row[7] = get_user_meta($value['user_id'], 'address_city', true);
            $row[8] = get_user_meta($value['user_id'], 'address_state', true);
            $row[9] = get_user_meta($value['user_id'], 'address_zip', true);
            $row[10] = get_user_meta($value['user_id'], 'address_country', true);
            $row[11] = get_user_meta($value['user_id'], 'contact_number', true);
            $row[12] = $user_info->user_email;
            $row[13] = "";
            $row[14] = $value['status'];
            $row[15] = $value['start_date'];
            $row[16] = $value['expiration_date'];
            $row[17] = "";
            $row[18] = "";
            fputcsv($f, $row, $delimiter);
        }
        fclose( $f );
        // flush buffer
        ob_flush();
        exit();
    }
    //get gs subscription
    public function get_gs_subscription($atts){
        global $wpdb;
        if (is_user_logged_in()){
            $user_id = get_current_user_id();
            if (isset($atts['plan_id']) && $atts['plan_id'] != ""){
                $style = "<style>";
                $style .= ".pms-account-subscription-details-table{display:none;}";
                $sids = explode(",", $atts['plan_id']);
                foreach ($sids as $sid){
                    $row = $wpdb->get_row($wpdb->prepare("select id from {$wpdb->prefix}pms_member_subscriptions where user_id=%d and subscription_plan_id=%d", array($user_id, $sid)));
                    if ($row){
                        $style .= '.pms-account-subscription-details-table__' . $sid . "{display:block;}";
                    }
                }
                if (isset($atts['plan_id']) && count($sids) == 1){
                    $style .= '.pms-subscription-plan-name{background:#072362;display:inline-block;padding:1rem;border-radius:6px;width:100%;}.pms-subscription-plan-price{margin: 0 15px;}';
                }
                $style .= "</style>";
            }

            return $style . do_shortcode("[pms-subscriptions subscription_plans='".$atts['plan_id']."']");
        }else{
            $subscription_post = get_post($atts['plan_id']);
            return do_shortcode("[wppb-register form_name='".$atts['form_name']."']");
        }
    }
    //get gs paid account list
    public function get_gs_paid_accounts($atts){
        global $wpdb;
        wp_enqueue_script( 'gs-customer', GS_CM_PLUGIN_DIR_URL . 'js/customer.js', array('jquery'), GS_CUSTOMER_MANAGEMENT, true );

        $sql_stmt = $wpdb->prepare("SELECT id, user_id, subscription_plan_id, `start_date`, expiration_date, trial_end, `status` FROM {$this->table_subscriptions} WHERE id NOT IN (SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_member') AND subscription_plan_id IN (%d, %d, %d)", array(GS_ACCOUNT_SUBSCRIPTION_PLAN, GS_ACCOUNT_ONEYEAR_INDIVIDUAL_PLAN, GS_ACCOUNT_THREEMONTH_INDIVIDUAL_PLAN));

//        if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//            echo "SQL: " . $sql_stmt . "<br>";
//            //echo "SQL: " . $wpdb->prepare($sql, array(date("Y"), date("m"))) . "<br>";
//        }

        $results = $wpdb->get_results($sql_stmt);

//        if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//            echo "<pre>";
//            print_r($results);
//            echo "</pre>";
//        }

        if ($_GET['download'] == "csv"){
            $this->download_csv($results, "paid_subscribers_" . Date("Y_m_d") . ".csv");
            return "";
        }
        $gs_type = GS_PAID;
        if ($results == null){
            return "<p>No accounts found</p>";
        }else{

            ob_start();
            if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_accounts.php' ) ){
                include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_accounts.php';
            }
            $content = ob_get_clean();

//            if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//                echo "BEGIN CONTENT #####################################################<br>";
//                echo $content;
//                echo "END CONTENT #####################################################<br>";
//            }

            return $content;
        }

    }
    //get gs trial accounts
    public function get_gs_trial_accounts($atts){
        global $wpdb;
        wp_enqueue_script( 'gs-customer', GS_CM_PLUGIN_DIR_URL . 'js/customer.js', array('jquery'), GS_CUSTOMER_MANAGEMENT, true );
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT id, user_id, subscription_plan_id, expiration_date, `status` FROM {$this->table_subscriptions} WHERE id NOT IN (SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_member') AND subscription_plan_id=%d", array(GS_ACCOUNT_TRIAL_PLAN))
        );
        if ($_GET['download'] == "csv"){
            $this->download_csv($results, "trial_subscribers_" . Date("Y_m_d") . ".csv");
            return "";
        }
        $gs_type = GS_TRIAL;
        if ($results == null){
            return "<p>No trial accounts found.</p>";
        }else{
            ob_start();
            if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_accounts.php' ) ){
                include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_accounts.php';
            }
            $content = ob_get_clean();
            return $content;
        }

    }
    //get gs newsletter accounts
    public function get_gs_nl_accounts($atts){
        global $wpdb;
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'gs-newsletter', GS_CM_PLUGIN_DIR_URL . 'js/newsletter.js', array('jquery'), GS_CUSTOMER_MANAGEMENT, true );
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT id, user_id, subscription_plan_id, `start_date`, expiration_date, trial_end, `status` FROM {$this->table_subscriptions} WHERE id NOT IN (SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_member') AND subscription_plan_id in (".implode(',', array_keys(GS_NEWSLETTER_SUBSCRIPTION_PLANS)).")")
        );
        $category = [];
        $sql = "select * from " . $wpdb->prefix . "gs_grant_segments";
        $cat_result = $wpdb->get_results($wpdb->prepare($sql));
        foreach ($cat_result as $cat){
            $category[$cat->id] = $cat->segment_title;
        }
        if ($results == null){
            return "<p>No newsletter accounts found.</p>";
        }else{

            ob_start();
            if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_nl_accounts.php' ) ){
                include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_nl_accounts.php';
            }
            $content = ob_get_clean();
            return $content;
        }

    }
    public function download_usage_csv($results, $filename, $delimiter=","){
        ob_end_clean();
        // tell the browser it's going to be a csv file
        header('Content-Type: application/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        $f = fopen('php://output', 'w');
        fputcsv(
            $f,
            array(
                "IP/User",
                "DateTime",
                "Ip Address",
                "User ID",
                "Referer URL",
                "Action"
            ),
            $delimiter
        );
        if ($results != null){
            foreach ($results as $key => $r){
                $value = json_decode(json_encode($r), true);
                $row = [];
                $row[0] = $value['user_id'] == 0?"IP":"User";
                $row[1] = $value['created_at'];
                $row[2] = $value['ip'];
                $row[3] = $value['user_name'];
                $row[4] = $value['url'];
                $row[5] = $value['content'];
                fputcsv($f, $row, $delimiter);
            }
        }
        fclose( $f );
        // flush buffer
        ob_flush();
        exit();
    }
    public function download_gs_accounts_usage(){
        global $wpdb;
        if(false !== strpos($_SERVER["REQUEST_URI"], 'subscriber/account-usage-download')) {
            if ($_GET['download'] == "csv"){
                if ($_GET['edit_user']){
                    $user = get_userdata( $_GET['edit_user'] );
                }else{
                    $user = wp_get_current_user();
                }

                $subscription_id = 0;
                $subscription = null;

                $member          = pms_get_member( $user->id );
                foreach( $member->subscriptions as $subscript ){
                    $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                    if( $plan->type != 'group' )
                        continue;
                    $subscription = pms_get_member_subscription($subscript['id']);
                    $subscription_id = $subscript['id'];
                    break;
                }
                if ($subscription_id == 0){
                    $subscription_id = get_subscription_id($user->id);
                }
                $where = " and manager_id=" . $user->id;
                $subscriber_logs = null;
                if ($subscription_id != 0){
                    if (pms_gm_is_group_owner( $subscription_id ) || isset($_GET['edit_user'])){
                        $subscriber_logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_subscriber_logs} where 1=1 {$where} ORDER BY created_at DESC"));

                    }
                }
                $subscr_name = pms_get_member_subscription_meta( $subscription_id, 'pms_group_name', true ) . '_';
                if ($subscr_name == '_') {
                    $subscr_name = get_userdata($user->id)->display_name . '_';
                }
                if ($subscr_name == '_') {
                    $subscr_name = '';
                }
                $this->download_usage_csv($subscriber_logs, "gs_account_usage_statistics_" . $subscr_name . Date("Y_m_d") . ".csv");
                return "";
            }
        }
    }
    public function get_gs_accounts_usage_download($atts){
        global $wpdb;
        if ($_GET['download'] == "csv"){
            if ($_GET['edit_user']){
                $user = get_userdata( $_GET['edit_user'] );
            }else{
                $user = wp_get_current_user();
            }
            $subscription_id = 0;
            $subscription = null;

            $member          = pms_get_member( $user->id );
            foreach( $member->subscriptions as $subscript ){
                $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                if( $plan->type != 'group' )
                    continue;
                $subscription = pms_get_member_subscription($subscript['id']);
                $subscription_id = $subscript['id'];
                break;
            }
            if ($subscription_id == 0){
                $subscription_id = get_subscription_id($user->id);
            }
            $where = " and manager_id=" . $user->id;
            $subscriber_logs = null;
            if ($subscription_id != 0){
                if (pms_gm_is_group_owner( $subscription_id ) || isset($_GET['edit_user'])){
                    $subscriber_logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_subscriber_logs} where 1=1 {$where} ORDER BY created_at DESC"));
                }
            }
            $this->download_usage_csv($subscriber_logs, "gs_account_usage_statistic" . Date("Y_m_d") . ".csv");
            return "";
        }
        return "";

    }
    //get sponsor list
    public function get_gs_editor_admin($atts){
        global $wpdb;
        //wp_enqueue_script( 'autocompletejs', GS_CM_PLUGIN_DIR_URL . 'js/autocomplete.js', array('autocompletejs'), GS_CUSTOMER_MANAGEMENT, true );
        wp_enqueue_script( 'ssearch', GS_CM_PLUGIN_DIR_URL . 'js/editor_admin.js', array('jquery'), GS_CUSTOMER_MANAGEMENT, true );
        $sponsors = $wpdb->get_results($wpdb->prepare("SELECT id, sponsor_name FROM {$wpdb->prefix}gs_grant_sponsors WHERE STATUS='A' ORDER BY sponsor_name "));

        $sql_query = "SELECT id, subject_title FROM " . $wpdb->prefix . "gs_grant_subjects
                        WHERE subject_title !=''
                        ORDER BY subject_title ASC";
        $sql = $wpdb->prepare( $sql_query );
        $subjects_list = $wpdb->get_results( $sql, 'OBJECT_K' );

        //update gs old user role

        /*$sql = "select ID from {$wpdb->prefix}users where ID>=454 and ID<=882";
        $rows = $wpdb->get_results($wpdb->prepare($sql));
        foreach ($rows as $old_user){
            $user_id = $old_user->ID;
            $subscription_plan_ids = [];
            $owner_subscription_id = 0;
            $user = get_userdata($user_id);
            $member          = pms_get_member( $user_id );
            $subscript_cnt = 0;
            $has_individual_role = false;
            $has_newsletter_role = false;
            $user->remove_role("subscriber");
            foreach( $member->subscriptions as $subscript ){
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
            if ($subscript_cnt != 0 ){
                if ($has_newsletter_role){
                    $user->add_role("newsletter");
                }
                if ($owner_subscription_id != 0){
                    if (pms_gm_is_group_owner( $owner_subscription_id )){
                        $user->add_role("gs_manager");
                    }else{
                        $user->add_role("gs_child");
                    }
                }
                if ($has_individual_role){
                    $user->add_role("gs_individual");
                }

                //$user->remove_role("subscriber");
            }else{
                $user->remove_role("newsletter");
                $user->remove_role("gs_manager");
                $user->remove_role("gs_child");
                $user->remove_role("gs_individual");
                //$user->add_role("subscriber");
            }
        }
        */



        ob_start();
        if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_editor_admin.php' ) ){
            include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_editor_admin.php';
        }
        $content = ob_get_clean();
        return $content;
    }
    //get sponsor detail info by sponsor's id
    public function get_sponsor_detail(){
        global $wpdb;
        $user = wp_get_current_user();
        $id = $_POST['id'];
        if ( in_array( "administrator", (array) $user->roles )) {
            $sponsor = $wpdb->get_row($wpdb->prepare("select * from {$wpdb->prefix}gs_grant_sponsors where id=%d", array($id)));
            echo json_encode(['success'=>true, 'sponsor'=>$sponsor]);
        }
        exit;
    }
    //remove sponsor by sponsor's id
    public function remove_sponsor(){
        global $wpdb;
        $user = wp_get_current_user();
        $id = $_POST['id'];
        if ( in_array( "administrator", (array) $user->roles )) {
            $wpdb->update(
                $wpdb->prefix . "gs_grant_sponsors",
                [
                    'status'=> 'D'
                ],
                [
                    'id'    => $id
                ]
            );
            echo json_encode(['success'=>true]);
        }
        exit;
    }
    //search sponsor by sponsor name
    public function search_sponsors(){
        global $wpdb;
        $user = wp_get_current_user();
        $name = $_POST['search'];
        if ( in_array( "administrator", (array) $user->roles )) {
            $sponsors = $wpdb->get_results($wpdb->prepare("select id, sponsor_name from {$wpdb->prefix}gs_grant_sponsors where status='A' and sponsor_name like '{$name}%'", array()));
            $response = [];
            foreach ($sponsors as $sponsor){
                $response[] = array("value"=>$sponsor->id,"label"=>$sponsor->sponsor_name);
            }
            echo json_encode($response);
        }
        exit;
    }
    //save subject heading by id
    public function save_subject(){
        global $wpdb;
        $user = wp_get_current_user();
        $id = $_POST['id'];
        if ( in_array("editor", (array) $user->roles ) ||  in_array( "administrator", (array) $user->roles )) {
            if ($id == 0){
                $wpdb->insert(
                    $wpdb->prefix . "gs_grant_subjects",
                    [
                        'subject_title'=> $_POST['subject_title'],
                        'updated_at'   => date("Y-m-d h:i:s"),
                        'created_at'   => date("Y-m-d h:i:s")
                    ]
                );
            }else{
                $wpdb->update(
                    $wpdb->prefix . "gs_grant_subjects",
                    [
                        'subject_title'=> $_POST['subject_title'],
                        'updated_at'   => date("Y-m-d h:i:s")
                    ],
                    [
                        'id'            => $id
                    ]
                );
            }
            $sql_query = "SELECT id, subject_title FROM " . $wpdb->prefix . "gs_grant_subjects
                        WHERE subject_title !=''
                        ORDER BY subject_title ASC";
            $sql = $wpdb->prepare( $sql_query );
            $subjects_list = $wpdb->get_results( $sql, 'OBJECT_K' );
            ob_start();
            if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_editor_subject_headings.php' ) ){
                include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_editor_subject_headings.php';
            }
            $content = ob_get_clean();
            echo json_encode(['success'=>true, 'content' => $content]);
        }
        exit;
    }
    //remove subject heading by id
    public function remove_subject(){
        global $wpdb;
        $user = wp_get_current_user();
        $id = $_POST['id'];
        if ( in_array("editor", (array) $user->roles ) ||  in_array( "administrator", (array) $user->roles )) {
            $wpdb->delete(
                $wpdb->prefix . "gs_grant_subject_mappings",
                [
                    'subject_id'            => $id
                ]
            );
            $wpdb->delete(
                $wpdb->prefix . "gs_grant_subjects",
                [
                    'id'            => $id
                ]
            );

            $sql_query = "SELECT id, subject_title FROM " . $wpdb->prefix . "gs_grant_subjects
            WHERE subject_title !=''
            ORDER BY subject_title ASC";
            $sql = $wpdb->prepare( $sql_query );
            $subjects_list = $wpdb->get_results( $sql, 'OBJECT_K' );
            ob_start();
            if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_editor_subject_headings.php' ) ){
                include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_editor_subject_headings.php';
            }
            $content = ob_get_clean();
            echo json_encode(['success'=>true, 'content' => $content]);
        }
        exit;
    }
    public function update_gs_per_page(){
        if (isset($_POST['per_page'])){
            update_user_meta(get_current_user_id(), 'gs_per_page', $_POST['per_page']);
        }
        echo json_encode(['success'=>true]);
        exit(0);
    }
    //get gs account list with gs type
    public function get_gs_account_list(){
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
            if (isset($_POST['email_alert']) && $_POST['email_alert'] != ""){
                $where .= " and s.user_id IN (SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key='email_alerts' AND meta_value='" . $_POST['email_alert'] . "') ";
            }
            if (isset($_POST['status']) && $_POST['status'] != ""){
                $where .= ' and s.status="' . $_POST['status']. '"';
            }
            if (isset($_POST['sval']) && $_POST['sval'] != ""){
                $where .= ' and (m.meta_value LIKE "%%' . $_POST['sval']. '%%" OR (SELECT display_name FROM ' . $wpdb->prefix . 'users WHERE ID=s.user_id LIMIT 1) LIKE "%%' . $_POST['sval']. '%%" )';
            }

            //sort by selected column
            $sort_by_column = $_POST['sort_by_column'];
            $sort_by_direction = $_POST['sort_by_direction'];
            if ( empty($sort_by_direction) && !empty($sort_by_column) ) {
                $sort_by_direction = 'ASC';
            }
            switch ($sort_by_column) {
                case 'status':
                    $sort_clause = "s.status";
                    $sort_clause .= " $sort_by_direction";
                    $sort_clause .= ", IFNULL (customer_subscriber_name, (SELECT display_name FROM {$wpdb->prefix}users WHERE ID=s.user_id LIMIT 1) ) ASC"; // secondary sort by Subscriber name
                    break;
                case 'expiration':
                    $sort_clause = "s.expiration_date";
                    $sort_clause .= " $sort_by_direction";
                    $sort_clause .= ", IFNULL (customer_subscriber_name, (SELECT display_name FROM {$wpdb->prefix}users WHERE ID=s.user_id LIMIT 1) ) ASC"; // secondary sort by Subscriber name
                    break;
                default:
                    //sort by Subscriber
                    $sort_clause = "IFNULL (customer_subscriber_name, (SELECT display_name FROM {$wpdb->prefix}users WHERE ID=s.user_id LIMIT 1) )";
                    $sort_clause .= " $sort_by_direction";
                    break;
            }

            //process search value
            if ($gs_type == GS_PAID){
                $sub_plans = array(GS_ACCOUNT_SUBSCRIPTION_PLAN, GS_ACCOUNT_ONEYEAR_INDIVIDUAL_PLAN, GS_ACCOUNT_THREEMONTH_INDIVIDUAL_PLAN);
                $sql = "select id, user_id from {$this->table_subscriptions} where subscription_plan_id IN (".implode(",", $sub_plans).")";
                $ids_results = $wpdb->get_results($wpdb->prepare($sql));
                $ids = [];
                foreach ($ids_results as $id_info){
                    $ids[$id_info->user_id] = $id_info->id;
                }

                $sql_stmt = $wpdb->prepare(
                    "
                        SELECT s.id, s.user_id, m.meta_value as customer_subscriber_name, s.subscription_plan_id, s.start_date, s.expiration_date, s.trial_end, s.status
                        FROM {$this->table_subscriptions} s
                        LEFT JOIN (SELECT member_subscription_id, meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name') m ON m.member_subscription_id=s.id
                        WHERE id NOT IN (SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_member')  {$where}  AND s.id in (".implode(",", $ids).")
                        ORDER BY $sort_clause LIMIT %d, %d
                        ",
                    array($start, $per_page)
                );

//                if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//                    echo $sql_stmt;
//                }

                $results = $wpdb->get_results($sql_stmt);

//                if ($_SERVER['REMOTE_ADDR'] == '24.12.63.177') {
//                    echo "<pre>";
//                    print_r($results);
//                    echo "</pre>";
//                }

                $count = $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT count(s.id)
                        FROM {$this->table_subscriptions} s 
                        LEFT JOIN (SELECT member_subscription_id, meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name') m ON m.member_subscription_id=s.id
                        WHERE id NOT IN (SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_member') {$where} AND s.id in (".implode(",", $ids).")  
                        ",
                        array()
                    )
                );
            }elseif ($gs_type == GS_TRIAL){
                $sql = "select id, user_id from {$this->table_subscriptions} where subscription_plan_id=%d";
                $ids_results = $wpdb->get_results($wpdb->prepare($sql, array(GS_ACCOUNT_TRIAL_PLAN)));
                $ids = [];
                foreach ($ids_results as $id_info){
                    $ids[$id_info->user_id] = $id_info->id;
                }
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "
                        SELECT s.id, s.user_id, m.meta_value as customer_subscriber_name, s.subscription_plan_id, s.start_date, s.expiration_date, s.trial_end, s.status 
                        FROM {$this->table_subscriptions} s 
                        LEFT JOIN (SELECT member_subscription_id, meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name') m ON m.member_subscription_id=s.id
                        WHERE id NOT IN (SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_member') {$where} AND s.id in (".implode(",", $ids).")
                        ORDER BY $sort_clause LIMIT %d, %d
                        ",
                        array($start, $per_page)
                    )
                );
                $count = $wpdb->get_var(
                    $wpdb->prepare(
                        "
                        SELECT count(s.id)
                        FROM {$this->table_subscriptions} s 
                        LEFT JOIN (SELECT member_subscription_id, meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name') m ON m.member_subscription_id=s.id
                        WHERE id NOT IN (SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_member') {$where} AND s.id in (".implode(",", $ids).")
                        ",
                        array(GS_ACCOUNT_TRIAL_PLAN)
                    )
                );
            }

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
            if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_accounts_ajax_table.php' ) ){
                include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_accounts_ajax_table.php';
            }
            $paginate_html = ob_get_clean();
        }
        echo $paginate_html;
        exit(0);
    }
    //remove gs account
    public function remove_gs_account(){
        global $wpdb;
        $user = wp_get_current_user();
        if ( in_array( GS_ADMIN, (array) $user->roles ) ||  in_array( "administrator", (array) $user->roles )) {
            $sid = $_POST['sid'];
            $result = $wpdb->query(
                $wpdb->prepare(
                    "
                    DELETE FROM {$this->table_subscriptions} 
                    WHERE id=%d OR id IN (SELECT member_subscription_id FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_owner' AND meta_value=%d)
                    ",
                    array($sid, $sid)
                )
            );
            $result = $wpdb->query(
                $wpdb->prepare(
                    "
                    DELETE FROM {$this->table_subscriptionmeta} 
                    WHERE member_subscription_id=%d
                    ",
                    array($sid)
                )
            );
            $result = $wpdb->query(
                $wpdb->prepare(
                    "
                    DELETE FROM {$this->table_subscriptionmeta} 
                    WHERE WHERE meta_key='pms_group_subscription_owner' AND meta_value=%d
                    ",
                    array($sid)
                )
            );
            echo json_encode(['success'=>true]);
        }else{
            echo json_encode(['success'=>false, 'error'=>'You have not permission to remove gs account']);
        }
        exit(0);
    }
    //remove selected gs accounts
    public function remove_gs_accounts(){
        global $wpdb;
        $user = wp_get_current_user();
        if ( in_array( GS_ADMIN, (array) $user->roles ) ||  in_array( "administrator", (array) $user->roles )) {
            $sids = $_POST['sids'];
            foreach ($sids as $sid){
                $result = $wpdb->query(
                    $wpdb->prepare(
                        "
                        DELETE FROM {$this->table_subscriptions} 
                        WHERE id=%d OR id IN (SELECT member_subscription_id FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_owner' AND meta_value=%d)
                        ",
                        array($sid, $sid)
                    )
                );
                $result = $wpdb->query(
                    $wpdb->prepare(
                        "
                        DELETE FROM {$this->table_subscriptionmeta} 
                        WHERE member_subscription_id=%d
                        ",
                        array($sid)
                    )
                );
                $result = $wpdb->query(
                    $wpdb->prepare(
                        "
                        DELETE FROM {$this->table_subscriptionmeta} 
                        WHERE WHERE meta_key='pms_group_subscription_owner' AND meta_value=%d
                        ",
                        array($sid)
                    )
                );
            }

            echo json_encode(['success'=>true]);
        }else{
            echo json_encode(['success'=>false, 'error'=>'You have not permission to remove gs account']);
        }
        exit(0);
    }
    public function update_wppb_profile( $http_request, $form_name, $user_id ){

    }
    public function check_page_url( $url, $post, $leavename=false ) {
        return $url;
    }
    //get gs newsletter account list with gs type
    public function get_gs_nl_account_list(){
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

            //process search value
            $where = "";
            if (isset($_POST['from_date']) && $_POST['from_date'] != ""){
                $fdate = new DateTime($_POST['from_date']);
                $where .= ' and s.billing_next_payment>="' . $fdate->format("Y-m-d H:i:s") . '" ';
            }
            if (isset($_POST['to_date']) && $_POST['to_date'] != ""){
                $tdate = new DateTime($_POST['to_date'] . " 23:59:59");
                $where .= ' and s.billing_next_payment<="' . $tdate->format("Y-m-d H:i:s") . '" ';
            }
            if (isset($_POST['status']) && $_POST['status'] != ""){
                $where .= ' and s.status="' . $_POST['status'] . '" ';
            }
            if (isset($_POST['nl_category']) && $_POST['nl_category'] != ""){
                $where .= ' and um.meta_value="' . $_POST['nl_category'] . '" ';
            }
            $category = [];
            $sql = "select * from " . $wpdb->prefix . "gs_grant_segments";
            $cat_result = $wpdb->get_results($wpdb->prepare($sql));
            foreach ($cat_result as $cat){
                $category[$cat->id] = $cat->segment_title;
            }

            //process search value
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT s.id, s.user_id, m.meta_value as customer_subscriber_name, s.subscription_plan_id, s.start_date, s.expiration_date, s.billing_next_payment, s.trial_end, s.status 
                    FROM {$this->table_subscriptions} s 
                    LEFT JOIN (SELECT member_subscription_id, meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name') m ON m.member_subscription_id=s.id
                    left join (SELECT user_id,meta_value FROM {$wpdb->prefix}usermeta WHERE meta_key='nl_category') um on um.user_id=s.user_id
                    WHERE id NOT IN (SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_member')  {$where}  AND subscription_plan_id in (".implode(',', array_keys(GS_NEWSLETTER_SUBSCRIPTION_PLANS)).")
                    ORDER BY m.meta_value ASC LIMIT %d, %d
                    ",
                    array($start, $per_page)
                )
            );
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT count(s.id)
                    FROM {$this->table_subscriptions} s 
                    LEFT JOIN (SELECT member_subscription_id, meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name') m ON m.member_subscription_id=s.id
                    left join (SELECT user_id,meta_value FROM {$wpdb->prefix}usermeta WHERE meta_key='nl_category') um on um.user_id=s.user_id
                    WHERE id NOT IN (SELECT meta_value FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_member') {$where} AND subscription_plan_id in (".implode(',', array_keys(GS_NEWSLETTER_SUBSCRIPTION_PLANS)).")
                    "
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
            if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_nl_accounts_ajax_table.php' ) ){
                include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_nl_accounts_ajax_table.php';
            }
            $paginate_html = ob_get_clean();
        }
        echo $paginate_html;
        exit(0);
    }
    //remove gs account
    public function remove_gs_nl_account(){
        global $wpdb;
        $user = wp_get_current_user();
        if ( in_array( "administrator", (array) $user->roles )) {
            $nid = $_POST['nid'];
            $result = $wpdb->query(
                $wpdb->prepare(
                    "
                    DELETE FROM {$this->table_subscriptions} 
                    WHERE id=%d OR id IN (SELECT member_subscription_id FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_owner' AND meta_value=%d)
                    ",
                    array($nid, $nid)
                )
            );
            $result = $wpdb->query(
                $wpdb->prepare(
                    "
                    DELETE FROM {$this->table_subscriptionmeta} 
                    WHERE member_subscription_id=%d
                    ",
                    array($nid)
                )
            );
            $result = $wpdb->query(
                $wpdb->prepare(
                    "
                    DELETE FROM {$this->table_subscriptionmeta} 
                    WHERE WHERE meta_key='pms_group_subscription_owner' AND meta_value=%d
                    ",
                    array($nid)
                )
            );
            echo json_encode(['success'=>true]);
        }else{
            echo json_encode(['success'=>false, 'error'=>'You have not permission to remove gs account']);
        }
        exit(0);
    }
    //remove selected gs accounts
    public function remove_gs_nl_accounts(){
        global $wpdb;
        $user = wp_get_current_user();
        if ( in_array( "administrator", (array) $user->roles )) {
            $nids = $_POST['nids'];
            foreach ($nids as $nid){
                $result = $wpdb->query(
                    $wpdb->prepare(
                        "
                        DELETE FROM {$this->table_subscriptions} 
                        WHERE id=%d OR id IN (SELECT member_subscription_id FROM {$this->table_subscriptionmeta} WHERE meta_key='pms_group_subscription_owner' AND meta_value=%d)
                        ",
                        array($nid, $nid)
                    )
                );
                $result = $wpdb->query(
                    $wpdb->prepare(
                        "
                        DELETE FROM {$this->table_subscriptionmeta} 
                        WHERE member_subscription_id=%d
                        ",
                        array($nid)
                    )
                );
                $result = $wpdb->query(
                    $wpdb->prepare(
                        "
                        DELETE FROM {$this->table_subscriptionmeta} 
                        WHERE WHERE meta_key='pms_group_subscription_owner' AND meta_value=%d
                        ",
                        array($nid)
                    )
                );
            }

            echo json_encode(['success'=>true]);
        }else{
            echo json_encode(['success'=>false, 'error'=>'You have not permission to remove gs account']);
        }
        exit(0);
    }
    /**
     * Function update_entry_user_info
     * update entry user info
     *
     * @return void
     */
    public function update_entry_user_info( $entry, $form ) {
        global $wpdb, $post;
        if (!is_user_logged_in() && isset($_SESSION['guest_user_id'])){
            $wpdb->update(
                $wpdb->prefix . "gf_entry",
                [
                    'created_by'=> $_SESSION['guest_user_id']
                ],
                [
                    'id'    => $entry['id']
                ]
            );
        }
    }
}
new Grantselect_Customer_Management;

add_action( 'wp_ajax_register_action', 'gs_user_registration');
add_action( 'wp_ajax_nopriv_register_action', 'gs_user_registration');

function gs_user_registration(){
    global $wpdb;
    if( $_POST['action'] == 'register_action' ) {

        $error = '';

        $uname = trim( $_POST['username'] );
        $email = trim( $_POST['mail_id'] );
        $fname = trim( $_POST['firname'] );
        $lname = trim( $_POST['lasname'] );
        $pswrd = $_POST['passwrd'];

        if( empty( $_POST['username'] ) )
            $error .= '<p class="error">Enter Username</p>';

        if( empty( $_POST['mail_id'] ) )
            $error .= '<p class="error">Enter Email Id</p>';
        elseif( !filter_var($email, FILTER_VALIDATE_EMAIL) )
            $error .= '<p class="error">Enter Valid Email</p>';

        if( empty( $_POST['passwrd'] ) )
            $error .= '<p class="error">Password should not be blank</p>';

        if( empty( $_POST['firname'] ) )
            $error .= '<p class="error">Enter First Name</p>';
        elseif( !preg_match("/^[a-zA-Z'-]+$/",$fname) )
            $error .= '<p class="error">Enter Valid First Name</p>';

        if( empty( $_POST['lasname'] ) )
            $error .= '<p class="error">Enter Last Name</p>';
        elseif( !preg_match("/^[a-zA-Z'-]+$/",$lname) )
            $error .= '<p class="error">Enter Valid Last Name</p>';

        if( empty( $error ) ){

            $status = wp_create_user( $uname, $pswrd ,$email );

            if( is_wp_error($status) ){

                $msg = '';

                foreach( $status->errors as $key=>$val ){

                    foreach( $val as $k=>$v ){

                        $msg = '<p class="error">'.$v.'</p>';
                    }
                }

                echo json_encode(['success'=>true, 'tbody' => '', 'msg' =>$msg]);

            }else{
                //add child of gs account.
                $user_id = $status;
                update_user_meta($user_id, "first_name", $_POST['firname']);
                update_user_meta($user_id, "last_name", $_POST['lasname']);
                $user = new WP_User($user_id);
                $user->add_role('gs_child');

                $wppb_general_settings = get_option( 'wppb_general_settings', 'false' );

                if ( wppb_get_admin_approval_option_value() == 'yes' ){
                    $user_data = get_userdata( $user_id );
                    $status = 'approved';
                    $status = apply_filters( 'new_user_approve_default_status', $status, $user_id );
                    update_user_meta( $user_id, 'pw_user_status', $status );

                    wp_set_object_terms( $user_id, array( 'unapproved' ), 'user_status', false);
                    wp_delete_object_term_relationships( $user_id, 'unapproved' );
                    clean_object_term_cache( $user_id, 'user_status' );

                    $table_terms                     = $wpdb->prefix . 'terms';
                    $table_term_taxonomy             = $wpdb->prefix . 'term_taxonomy';
                    $table_term_relationships        = $wpdb->prefix . 'term_relationships';
                    $term = $wpdb->get_row(
                        $wpdb->prepare("SELECT term_id FROM {$table_terms} WHERE slug='unapproved'" )
                    );
                    $term_taxonomy = $wpdb->get_row(
                        $wpdb->prepare("SELECT term_taxonomy_id, count FROM {$table_term_taxonomy} WHERE term_id=%s and taxonomy='user_status' ", array($term->term_id) )
                    );
                    $wpdb->update(
                        $table_term_taxonomy,
                        array(
                            'count'=>$term_taxonomy->count - 1
                        ),
                        array(
                            'id'=> $term_taxonomy->term_taxonomy_id
                        )
                    );
                    $term_taxonomy = $wpdb->get_row(
                        $wpdb->prepare("SELECT term_taxonomy_id, count FROM {$table_term_taxonomy} WHERE term_id=%s and taxonomy='user_status' ", array($term->term_id) )
                    );
                    $wpdb->delete(
                        $table_term_relationships,
                        array(
                            'object_id' => $user_id,
                            'term_taxonomy_id'=> $term_taxonomy->term_taxonomy_id
                        )
                    );


                    $bloginfo = get_bloginfo( 'name' );
                    $user_login = ( ( isset( $wppb_general_settings['loginWith'] ) && ( $wppb_general_settings['loginWith'] == 'email' ) ) ? trim( $email ) : trim( $uname ) );
                    wppb_notify_user_registration_email($bloginfo, $user_login, $email, 'sending', $pswrd, wppb_get_admin_approval_option_value() );

                }

                $subscription_id = $_POST['pms_subscription_id'];
                if (!pms_gm_is_group_owner($subscription_id)){
                    $msg = '<p class="error">You don\'t have permission to add member.</p>';
                }else{
                    $owner_subscription = pms_get_member_subscription( $subscription_id );

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

                    $meta_id = pms_gm_get_meta_id_by_value( $owner_subscription->id, $data['email'] );

                    pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails_' . $meta_id, $data['pms_key'] );
                    pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails', $data['email'] );

                    if( function_exists( 'pms_add_member_subscription_log' ) )
                        pms_add_member_subscription_log( $subscription->id, 'group_user_accepted_invite' );

                    $msg = '<p class="success">Registration Successful.</p>';

                    $members_list = pms_gm_get_group_members( $subscription_id );
                    $members_list_sorts = [];
                    $owners = [];
                    $members = [];
                    $invited = [];
                    foreach( $members_list as $member_reference ){
                        $row = array();
                        $i = 0;
                        if( is_numeric( $member_reference ) ){
                            $member_user_id = pms_gm_get_member_subscription_user_id( $member_reference );

                            $row['email']   = pms_gm_get_email_by_user_id( $member_user_id );
                            $first_name = get_user_meta(  $member_user_id, 'first_name', true );
                            $row['first_name'] = "";
                            if( !empty( $first_name ) ){
                                $row['first_name'] = $first_name;
                            }
                            $last_name = get_user_meta(  $member_user_id, 'last_name', true );
                            $row['last_name'] = "";
                            if( !empty( $last_name ) ){
                                $row['last_name'] = $last_name;
                            }
                            $row['status']  = pms_gm_is_group_owner( $member_reference ) ? esc_html__( 'Owner', 'paid-member-subscriptions' ) : esc_html__( 'Registered', 'paid-member-subscriptions' );
                            $row['actions'] = '<a class="remove" data-reference="'.$member_reference.'" data-subscription="'.$subscription_id.'" href="#">'. esc_html__( 'Remove', 'paid-member-subscriptions' ) .'</a>';
                            $row['actions'] = '<nobr>' . $row['actions'] . '</nobr>';

                            if (pms_gm_is_group_owner( $member_reference )){
                                $owners = array_merge($owners, [strtolower(str_pad($row['last_name'], 32) . str_pad($row['first_name'], 32) . str_pad(explode("@",$row['email'])[0], 32) . explode("@",$row['email'])[1])=>$row]);
                            }else{
                                $members = array_merge($members, [strtolower(str_pad($row['last_name'], 32) . str_pad($row['first_name'], 32) . str_pad(explode("@",$row['email'])[0], 32) . explode("@",$row['email'])[1])=>$row]);
                            }
                        } else {
                            $row['email']   = $member_reference;
                            $row['first_name']    = '';
                            $row['last_name']     = '';
                            $row['status']  = esc_html__( 'Invited', 'paid-member-subscriptions' );
                            $row['actions'] = $pms_gm->get_members_row_actions( $member_reference, $subscription_id );
                            $invited = array_merge($invited, [strtolower(str_pad($row['last_name'], 32) . str_pad($row['first_name'], 32) . str_pad(explode("@",$row['email'])[0], 32) . explode("@",$row['email'])[1])=>$row]);
                        }
                    }

                    ksort($owners, SORT_STRING);
                    ksort($members, SORT_STRING);
                    ksort($invited, SORT_STRING);

                    ob_start();
                    if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_subscriber_member_list.php' ) ){
                        include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_subscriber_member_list.php';
                    }
                    $tbody = ob_get_contents();
                    ob_end_clean();
                }
                echo json_encode(['success'=>true, 'tbody' => $tbody, 'msg' =>$msg]);;
            }

        }else{

            echo json_encode(['success'=>true, 'tbody' => '', 'msg' =>$error]);;
        }
        die(1);
    }
}
add_action( 'wp_ajax_ea_remove_action', 'remove_email_alert');
add_action( 'wp_ajax_nopriv_ea_remove_action', 'remove_email_alert');
function remove_email_alert(){
    global $wpdb;
    $id = $_POST['id'];
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $is_delete = false;
    foreach( $user->roles as $role ) {
        if ($role == GS_ADMIN || $role == 'administrator'){
            $is_delete = true;
            break;
        }
    }
    $subscription_id = 0;
    $pms_subscriber =  $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pms_member_subscriptions where user_id=%d order by id desc", array($user_id)));
    $ea_subscriber = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gs_subscriber_email_alerts where id=%d", array($id)));
    if ($pms_subscriber && $ea_subscriber){
        if ($pms_subscriber->id == $ea_subscriber->subscr_id){
            $is_delete = true;
        }
    }
    if ($is_delete){
        $wpdb->delete(
            $wpdb->prefix . "gs_subscriber_email_alerts",
            array(
                'ID' => $id
            )
        );
        echo json_encode(['success'=>true ]);
    }else{
        echo json_encode(['success'=>false]);
    }
    exit;
}
function gs_admin_enqueue($hook) {
    // Only add to the edit.php admin page.
    // See WP docs.
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $only_gs_admin = false;
    $has_gs_admin = false;
    $has_administrator = false;
    foreach( $user->roles as $role ) {
        if ($role == GS_ADMIN){
            $has_gs_admin = true;
        }
        if ($role == 'administrator'){
            $has_administrator = true;
        }
    }
    // $has_gs_admin = true;
    // $has_administrator = false;
    if ($has_gs_admin && !$has_administrator && (in_array($hook, ['edit.php', 'post.php', 'post-new.php']))){
        wp_enqueue_script('gs_admin_script', GS_CM_PLUGIN_DIR_URL . '/js/gs_admin.js', array('jquery'), GS_CUSTOMER_MANAGEMENT, true);
        wp_enqueue_style('gs_admin_css', GS_CM_PLUGIN_DIR_URL . '/css/gs-admin.css', array(),  GS_CUSTOMER_MANAGEMENT);
    }else{
        return;
    }

}

add_action('admin_enqueue_scripts', 'gs_admin_enqueue');

