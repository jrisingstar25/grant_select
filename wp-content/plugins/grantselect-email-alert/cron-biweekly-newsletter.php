<?php

require_once('../../../wp-load.php');
require_once('cron-newsletter.php');

class GS_Cron_Daily_Newsletter extends GS_Cron_Newsletter{
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
        $newsletter_plan_ids = implode(",", array_keys(GS_NEWSLETTER_SUBSCRIPTION_PLANS));
        $ea_id = 0;
        if (get_option("home") == "https://gs.adenium5.com"){
            $ea_id = 1050;
        }else if (get_option("home") == "https://gs.adenium5.net"){
            $ea_id = 936;
        }
        if ($limit != 'all'){
            $sql = "SELECT a.ID, a.subscr_id, a.email, a.form_entry_id, a.user_id FROM {$wpdb->prefix}gs_subscriber_email_alerts a left join {$wpdb->prefix}pms_member_subscriptions m on m.id=a.subscr_id WHERE a.ID > {$ea_id} and a.status='A' AND a.alert_type=0 and m.subscription_plan_id in ({$newsletter_plan_ids}) AND a.subscr_id IN($subscr_ids) LIMIT $limit";
        }else{
            $sql = "SELECT a.ID, a.subscr_id, a.email, a.form_entry_id, a.user_id FROM {$wpdb->prefix}gs_subscriber_email_alerts a left join {$wpdb->prefix}pms_member_subscriptions m on m.id=a.subscr_id WHERE a.ID > {$ea_id} and a.status='A' AND a.alert_type=0 and m.subscription_plan_id in ({$newsletter_plan_ids}) AND a.subscr_id IN($subscr_ids)";
        }
        $data = $wpdb->get_results($wpdb->prepare($sql));
        $this->send_email($data);
    }
}
$daily = new GS_Cron_Daily_Newsletter();
$daily->send();
