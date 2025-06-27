<?php
defined( 'ABSPATH' ) or die();


/**
 * GranSelect USAGE Management Class
 *
 * @copyright   Copyright (c) 2020-2021, GrantSelect
 * @since       1.0
 */

Class Grantselect_Usage_Management {
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
        $this->table_subscriber_logs    = $wpdb->prefix . "gs_subscriber_logs";
        $this->per_pages = [10, 20, 50, 100];
        $this->init();
    }
    private function init(){
        //get usage logs list
        add_shortcode("gs-usage-logs", array($this, "get_gs_usage_logs"));
        add_shortcode("gs-manager-usage", array($this, "get_manager_usage"));

        //ajax paginate
        add_action( 'wp_ajax_usage_list', array($this, 'get_usage_list') );
        add_action( 'wp_ajax_nopriv_usage_list', array($this, 'get_usage_list') ); 

        add_action( 'wp_ajax_usage_statistic', array($this, 'get_usage_statistic') );
        
        add_action('parse_request', array($this, 'download_report'));
    }
    public function get_gs_usage_logs($atts){
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'usage', GS_CM_PLUGIN_DIR_URL . 'js/usage.js', array('jquery'), GS_CUSTOMER_MANAGEMENT, true );
        global $wpdb;
        $gs_type = strtolower($atts['type']);
        $subscribers = null;
        switch ($gs_type){
            case 'gs':
                $subscribers = $wpdb->get_results( 
                    $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='active' AND subscription_plan_id=%d AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )", array(GS_ACCOUNT_SUBSCRIPTION_PLAN))
                );
                break;
            case 'trial':
                $subscribers = $wpdb->get_results( 
                    $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='active' AND subscription_plan_id=%d AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='')", array(GS_ACCOUNT_TRIAL_PLAN))
                );
                break;
            case 'expired':
                $subscribers = $wpdb->get_results( 
                    $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='expired' AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                );
                break;
            case 'pending':
                $subscribers = $wpdb->get_results( 
                    $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='pending' AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                );
                break;
            case 'suspended':
                $subscribers = $wpdb->get_results( 
                    $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='suspended' AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                );
                break;
            case 'abandoned':
                $subscribers = $wpdb->get_results( 
                    $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='abandoned' AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                );
                break;
            case 'all';
                $subscribers = $wpdb->get_results( 
                    $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                );
                break;
        }
        $sorted_subscribers = [];
        foreach ($subscribers as $s){
            $sorted_subscribers[strtolower(pms_get_member_subscription_meta( $s->id, 'pms_group_name', true ))] = ['sid'=>$s->id, 'name'=>pms_get_member_subscription_meta( $s->id, 'pms_group_name', true )];
        }
        
        ksort($sorted_subscribers);
        ob_start();
        if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_usage_lists.php' ) ){
            include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_usage_lists.php';
        }
        $content = ob_get_clean();
        return $content;
    }
    public function get_usage_list(){
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
            $owner_ids = [];
            switch ($gs_type){
                case 'gs':
                    $subscribers = $wpdb->get_results( 
                        $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='active' AND subscription_plan_id=%d AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='')", array(GS_ACCOUNT_SUBSCRIPTION_PLAN))
                    );
                    foreach ($subscribers as $s){
                        array_push($owner_ids, $s->user_id);
                    }
                    break;
                case 'trial':
                    $subscribers = $wpdb->get_results( 
                        $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='active' AND subscription_plan_id=%d AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )", array(GS_ACCOUNT_TRIAL_PLAN))
                    );
                    
                    foreach ($subscribers as $s){
                        array_push($owner_ids, $s->user_id);
                    }
                    break;
                case 'pending':
                    $subscribers = $wpdb->get_results( 
                        $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='pending' AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                    );
                    foreach ($subscribers as $s){
                        array_push($owner_ids, $s->user_id);
                    }
                    break;
                case 'suspended':
                    $subscribers = $wpdb->get_results( 
                        $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='suspended' AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                    );
                    foreach ($subscribers as $s){
                        array_push($owner_ids, $s->user_id);
                    }
                    break;
                case 'expired':
                    $subscribers = $wpdb->get_results( 
                        $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='expired' AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                    );
                    foreach ($subscribers as $s){
                        array_push($owner_ids, $s->user_id);
                    }
                    break;
                case 'abandoned':
                    $subscribers = $wpdb->get_results( 
                        $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE `status`='abandoned' AND id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                    );
                    foreach ($subscribers as $s){
                        array_push($owner_ids, $s->user_id);
                    }
                    break;
                case 'all':
                    $subscribers = $wpdb->get_results( 
                        $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                    );
                    foreach ($subscribers as $s){
                        array_push($owner_ids, $s->user_id);
                    }
                    break;
                default:
                    $subscribers = $wpdb->get_results( 
                        $wpdb->prepare("SELECT id, user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE id IN (SELECT DISTINCT(member_subscription_id) FROM {$wpdb->prefix}pms_member_subscriptionmeta WHERE meta_key='pms_group_name' AND meta_value!='' )")
                    );
                    foreach ($subscribers as $s){
                        array_push($owner_ids, $s->user_id);
                    }
                    break;
            }
            $where = "";
            //From date
            if (isset($_POST['from_date']) && $_POST['from_date'] != ""){
                $fdate = new DateTime($_POST['from_date']);
                $where .= ' and created_at >= "' . $fdate->format("Y-m-d H:i:s") . '" ';
            }
            //To date
            if (isset($_POST['to_date']) && $_POST['to_date'] != ""){
                $tdate = new DateTime($_POST['to_date'] . "23:59:59");
                $where .= ' and created_at <= "' .  $tdate->format("Y-m-d H:i:s") . '" ';
            }
            if (isset($_POST['status']) && $_POST['status'] != ""){
                $where .= ' and status=' . $_POST['status'];
            }
            //Subscriber id of owner
            if (isset($_POST['subscribers']) && $_POST['subscribers'] != ""){
                if ($gs_type != "all"){
                    $owner_subscriber = $wpdb->get_row( 
                        $wpdb->prepare("SELECT user_id FROM {$this->table_subscriptions} WHERE id=%s", array($_POST['subscribers']) )
                    );
                    $where .= ' and manager_id = ' . $owner_subscriber->user_id;
                }
                
            }else{
                
                if (empty($owner_ids)){
                    $where .= ' and manager_id=0';     
                }else{
                    $where .= ' and manager_id in (' . implode(",", $owner_ids) . ')';
                }
                
            }
            //process search value
            if (isset($_POST['search_val']) && $_POST['search_val'] != ""){
                $where .= ' and (manager_name like "%%' . $_POST['search_val']. '%%" or user_name like "%%' . $_POST['search_val']. '%%" or ip like "%%' . $_POST['search_val']. '%%" or url like "%%' . $_POST['search_val']. '%%")';
            }
            
            // Query the necessary posts
            if ($gs_type != "all"){
                $subscriber_logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_subscriber_logs} where 1=1 {$where} ORDER BY created_at DESC LIMIT %d, %d", array($start, $per_page)));
                $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$this->table_subscriber_logs} where 1=1 {$where}"));
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
            }else{
                $visit_total = 0;
                if (isset($_GET['sid'])){
                    $subscriber_logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_subscriber_logs} where 1=1 {$where} ORDER BY manager_name DESC LIMIT %d, %d", array($start, $per_page)));
                    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$this->table_subscriber_logs} where 1=1 {$where}"));
                }else{
                    $where = '';
                    if (isset($_POST['subscribers']) && $_POST['subscribers'] != ""){
                        $where = ' and owner_sid = ' . $_POST['subscribers'];
                    }
                    $subscriber_logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_owner_statistics} where 1=1 {$where} ORDER BY owner_name LIMIT %d, %d", array($start, $per_page)));
                    foreach ($subscriber_logs as $s){
                        $owner_user_id = $wpdb->get_var( 
                            $wpdb->prepare("SELECT user_id FROM {$this->table_subscriptions} WHERE id=%d", array($s->owner_sid) )
                        );

                        $sql = "SELECT ifnull(COUNT(DISTINCT(sid)),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=1 ";
                        $result = $wpdb->get_row($wpdb->prepare($sql, array($owner_user_id)));
                        $s->search_cnt = $result->cnt;
                        
                        $sql = "SELECT ifnull(COUNT(sid), 0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=5 ";
                        $result = $wpdb->get_row($wpdb->prepare($sql, array($owner_user_id)));
                        $s->search_cnt += $result->cnt;

                        $sql = "SELECT ifnull(COUNT(sid), 0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=6 ";
                        $sql_prep = $wpdb->prepare($sql, array($owner_user_id));
                        $result = $wpdb->get_row($sql_prep);
                        $s->search_cnt += $result->cnt;

                        $sql = "SELECT ifnull(COUNT(id),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=2 ";
                        $sql_prep = $wpdb->prepare($sql, array($owner_user_id));
                        $result = $wpdb->get_row($sql_prep);
                        $s->emailalert_cnt = $result->cnt;

                        $sql = "SELECT ifnull(COUNT(url),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=3 ";
                        $result = $wpdb->get_row($wpdb->prepare($sql, array($owner_user_id)));
                        $s->gdetail_cnt = $result->cnt;

                        // $sql = "SELECT COUNT(distinct(user_id)) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and user_id!=0 and status=0 ";
                        // $visit_total += $wpdb->get_var($wpdb->prepare($sql, array($owner_user_id)));
                        // $sql = "SELECT COUNT(distinct(ip)) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and user_id=0 and status=0 ";
                        // $visit_total += $wpdb->get_var($wpdb->prepare($sql, array($owner_user_id)));

                    }
                    $sql = "select sum(count) cnt from {$this->table_owner_statistics} where 1=1 {$where} ";
                    $visit_total = $wpdb->get_var($wpdb->prepare($sql, []));

                    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$this->table_owner_statistics} where 1=1 {$where}"));
                }
                
                
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
            }
            
            
            

            ob_start();
            if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_usage_ajax_table.php' ) ){
                include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_usage_ajax_table.php';
            }
            $paginate_html = ob_get_clean();
        }
        echo $paginate_html;
        exit(0);
    }
    public function get_manager_usage($atts){
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'manager-usage', GS_CM_PLUGIN_DIR_URL . 'js/manager_usage.js', array('jquery'), GS_CUSTOMER_MANAGEMENT, true );
        global $wpdb;
        ob_start();
        if( file_exists( GS_CM_PLUGIN_DIR_PATH . 'templates/gs_manager_usage.php' ) ){
            include GS_CM_PLUGIN_DIR_PATH . 'templates/gs_manager_usage.php';
        }
        $content = ob_get_clean();
        return $content;
    }
    public function get_usage_statistic(){
        global $wpdb;
        $user_id = pms_get_current_user_id();
        $from = $_POST['from_date'];
        if ($from != ""){
            $from = date("Y-m-d", strtotime($from));
            $from .= " 00:00:00";
            $from = " AND created_at>='" . $from . "' ";
        }
        $to = $_POST['to_date'];
        if ($to != ""){
            $to = date("Y-m-d", strtotime($to));
            $to .= " 23:59:59";
            $to = " AND created_at<='" . $to . "' ";
        }

        $sql = "SELECT status,ifnull(COUNT(status), 0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=0 {$from} {$to} GROUP BY status";
        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
        $login_cnt = $result->cnt==null?0:$result->cnt;
        
        $sql = "SELECT ifnull(COUNT(DISTINCT(sid)), 0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=1 {$from} {$to} ";
        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
        $search_cnt = $result->cnt;
        $sql = "SELECT ifnull(COUNT(sid),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=5 {$from} {$to} ";
        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
        $search_cnt += $result->cnt;
        $sql = "SELECT ifnull(COUNT(sid), 0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=6 {$from} {$to} ";
        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
        $search_cnt += $result->cnt;

        $sql = "SELECT ifnull(COUNT(id),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=2 {$from} {$to} ";
        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
        $email_cnt = $result->cnt;
        
        $sql = "SELECT ifnull(COUNT(url),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=3 {$from} {$to} ";
        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
        $detail_cnt = $result->cnt;

        echo json_encode(['success'=>true, 'result'=>['login_cnt'=>$login_cnt, 'search_cnt'=>$search_cnt, 'email_cnt'=>$email_cnt, 'detail_cnt'=>$detail_cnt], 'total_rows' => $total_rows, 'search_rows' => $search_rows, "sql1"=>$sql_1, "sql2"=>$sql_2]);
        exit;
    }
    //download the usage report with excel formt
    public function download_report(){
        global $wpdb;
        if (false === strpos( $_SERVER[ 'REQUEST_URI' ], 'wp-admin/admin-ajax.php' )){
            if(false !== strpos($_SERVER["REQUEST_URI"], 'account/usage/report')) {
                $user_id = pms_get_current_user_id();
                $member          = pms_get_member( $user_id );
                foreach( $member->subscriptions as $subscript ){
                    if ($subscript['status'] != 'active')
                        continue;
                    $plan = pms_get_subscription_plan( $subscript['subscription_plan_id'] );
                    if( $plan->type == 'group' ){
                        $owner_subscription_id = $subscript['id'];
                    }
                }
                if (isset($_GET['from'])){
                    $from = date("Y-m-d", strtotime($_GET['from']));
                }else{
                    $from = date("Y-m-01");
                }
                if (isset($_GET['to'])){
                    $to = date("Y-m-d", strtotime($_GET['to']));
                }else{
                    $to = date("Y-m-d");
                }

                if ($owner_subscription_id != 0){
                    if (pms_gm_is_group_owner( $owner_subscription_id )){
                        $filename = "GrantSelect-Platform-Usage.xlsx";
                        ob_end_clean();
                        include_once(WP_PLUGIN_DIR ."/grantselect-search/lib/xlsx/xlsxwriter.class.php");
                        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                        header('Content-Disposition: attachment;filename="'.$filename.'"');
                        header('Cache-Control: max-age=0');

                        //report main info
                        $data = [];
                        //array_push($data, ["Report_Name", "Platform Usage"]);
                        array_push($data, ["Report_ID", "PR_P1"]);
                        array_push($data, ["Release", 5]);
                        array_push($data, ["Institution_Name", pms_gm_get_group_name($owner_subscription_id)]);
                        array_push($data, ["Institution_ID", ""]);
                        array_push($data, ["Metric_Types", "Visits_Platform; Searches_Platform; Email_Alerts_Platform; Total_Item_Requests"]);
                        array_push($data, ["Report_Filters", "Access_Type=Controlled; Access_Method=Regular"]);
                        array_push($data, ["Report Attributes", ""]);
                        array_push($data, ["Exceptions", ""]);
                        array_push($data, ["Reporting_Period", $from . " to " . $to]);
                        array_push($data, ["Created", date("Y-m-d")]);
                        array_push($data, ["Created_By", "GrantSelect"]);
                        array_push($data, ["", ""]);
                        
                        
                        $period = new DatePeriod(
                            new DateTime($from),
                            new DateInterval('P1M'),
                            new DateTime($to)
                        );
                        $months = [];
                        $month_header = [];
                        foreach ($period as $key => $value) {
                            $months[] = $value->format('Y-m');
                            $month_header[] = $value->format("M-Y");
                        }
                        if (date("Y-m", strtotime($months[count($months) - 1])) != date("Y-m", strtotime($to))){
                            $months[] = date("Y-m", strtotime($to));
                            $month_header[] = date("M-Y", strtotime($to));
                        }
                        $from_day = date("d", strtotime($from));
                        $to_day = date("d", strtotime($to));

                        $froms = [];
                        $tos =[];
                        foreach ($months as $key => $m){
                            if ($key == 0){
                                $froms[] = $m . "-".$from_day." 00:00:00";
                            }else{
                                $froms[] = $m . "-01 00:00:00";
                            }
                            if ($key == count($months) - 1){
                                $tos[] = $m. "-".$to_day." 23:59:59";
                            }else{
                                $tos[] = $m. "-31 23:59:59";
                            }
                            
                        }
                        $row = ["Platform", "Metric_Type", "Reporting_Period_Total"];
                        foreach ($month_header as $mh){
                            array_push($row, $mh);
                        }
                        array_push($data, $row);

                        $row1 = [];//visit_platform
                        $row2 = [];//searches_platform
                        $row3 = [];//email_alerts_platform
                        $row4 = [];//total_items_requests
                        
                        $from_d = $from . " 00:00:00";
                        $from_d = " AND created_at>='" . $from_d . "' ";
                        $to_d = $to . " 23:59:59";
                        $to_d = " AND created_at<='" . $to_d . "' ";
                        
                        $sql = "SELECT status,ifnull(COUNT(status),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=0 {$from_d} {$to_d} GROUP BY status";
                        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                        $login_cnt = $result->cnt==null?0:$result->cnt;
                        
                        $sql = "SELECT ifnull(COUNT(DISTINCT(sid)),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=1 {$from_d} {$to_d} ";
                        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                        $search_cnt = $result->cnt;
                        
                        $sql = "SELECT ifnull(COUNT(sid), 0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=5 {$from_d} {$to_d} ";
                        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                        $search_cnt += $result->cnt;

                        $sql = "SELECT ifnull(COUNT(sid), 0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=6 {$from_d} {$to_d} ";
                        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                        $search_cnt += $result->cnt;


                        $sql = "SELECT ifnull(COUNT(id), 0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=2 {$from_d} {$to_d} ";
                        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                        $email_cnt = $result->cnt;
                        
                        $sql = "SELECT ifnull(COUNT(url), 0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=3 {$from_d} {$to_d} ";
                        $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                        $detail_cnt = $result->cnt;
                        
                        $row1[0] = $login_cnt;
                        $row2[0] = $search_cnt;
                        $row3[0] = $email_cnt;
                        $row4[0] = $detail_cnt;
                        
                        for ($i = 0; $i < count($froms); $i++){
                            $row1[$i + 1] = 0;
                            $row2[$i + 1] = 0;
                            $row3[$i + 1] = 0;
                            $row4[$i + 1] = 0;
                            $from_d = $froms[$i];
                            $from_d = " AND created_at>='" . $from_d . "' ";
                            $to_d = $tos[$i];
                            $to_d = " AND created_at<='" . $to_d . "' ";
                            
                            $sql = "SELECT status,ifnull(COUNT(status), 0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=0 {$from_d} {$to_d} GROUP BY status";
                            $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                            $login_cnt = $result->cnt==null?0:$result->cnt;
                            
                            $sql = "SELECT ifnull(COUNT(DISTINCT(sid)),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=1 {$from_d} {$to_d} ";
                            $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                            $search_cnt = $result->cnt;
                            $sql = "SELECT ifnull(COUNT(sid),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=5 {$from_d} {$to_d} ";
                            $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                            $search_cnt += $result->cnt;
                            $sql = "SELECT ifnull(COUNT(sid),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and sid!=0 and status=6 {$from_d} {$to_d} ";
                            $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                            $search_cnt += $result->cnt;

                            $sql = "SELECT ifnull(COUNT(id),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=2 {$from_d} {$to_d} ";
                            $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                            $email_cnt = $result->cnt;
                            
                            $sql = "SELECT ifnull(COUNT(url),0) cnt FROM {$wpdb->prefix}gs_subscriber_logs WHERE manager_id=%d and status=3 {$from_d} {$to_d} ";
                            $result = $wpdb->get_row($wpdb->prepare($sql, array($user_id)));
                            $detail_cnt = $result->cnt;
                            $row1[$i + 1] = $login_cnt;
                            $row2[$i + 1] = $search_cnt;
                            $row3[$i + 1] = $email_cnt;
                            $row4[$i + 1] = $detail_cnt;
                        }
                        
                        $report1 = [];
                        $report1[] = "GrantSelect";
                        $report1[] = "Visits_Platform";
                        foreach ($row1 as $r){
                            array_push($report1, $r);
                        }
                        array_push($data, $report1);

                        $report2 = [];
                        $report2[] = "GrantSelect";
                        $report2[] = "Searches_Platform";
                        foreach ($row2 as $r){
                            array_push($report2, $r);
                        }
                        array_push($data, $report2);

                        $report3 = [];
                        $report3[] = "GrantSelect";
                        $report3[] = "Email_Alerts_Platform";
                        foreach ($row3 as $r){
                            array_push($report3, $r);
                        }
                        array_push($data, $report3);

                        $report4 = [];
                        $report4[] = "GrantSelect";
                        $report4[] = "Total_Items_Requests";
                        foreach ($row4 as $r){
                            array_push($report4, $r);
                        }
                        array_push($data, $report4);
                        
                        $writer = new XLSXWriter();
                        $writer->writeSheetHeader('Sheet1', ['Report_Name'=>"string", 'Platform Usage'=>"string"], $col_options = ['widths'=>[20,40,15]]);
                        $writer->writeSheet($data,'Sheet1');

                        $writer->writeToStdOut();
                    }
                }
                
                
                exit;
            }
        }
    }
}
new Grantselect_Usage_Management;