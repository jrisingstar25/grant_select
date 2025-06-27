<?php

require_once('../../../wp-load.php');
require_once('cron-email-alert.php');

class GS_Cron_Weekly_Email_Alert extends GS_Cron_Email_Alert{
    public function __construct(){
        $this->init();
    }
    /**
     * Init for sending email alert.
     *
     * @return void
     */
    private function init(){

    }
    /**
     * Send email alerts to subscribers.
     * @params $limit
     * @return void
     */
    public function send($limit='all'){
        global $wpdb;
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        $count_of_subscr = 0;
        $count_alerts_for_subsc = 0;
        $subscr_ids = $this->get_all_active_subscr();
        if ($limit != 'all'){
            $sql = "SELECT ID, subscr_id, email, form_entry_id FROM {$wpdb->prefix}gs_subscriber_email_alerts WHERE ID > 1050 and status='A' AND alert_type=1 AND `subscr_id` IN($subscr_ids) LIMIT $limit";
        }else{
            $sql = "SELECT ID, subscr_id, email, form_entry_id FROM {$wpdb->prefix}gs_subscriber_email_alerts WHERE ID > 1050 and status='A' AND alert_type=1 AND `subscr_id` IN($subscr_ids)";
        }
        $data = $wpdb->get_results($wpdb->prepare($sql));
        $this->send_email($data);
    }
}
$weekly = new GS_Cron_Weekly_Email_Alert();
$weekly->send();
