<?php

require_once('../../../wp-load.php');
require_once('cron-email-alert.php');

class GS_Cron_Daily_Email_Alert extends GS_Cron_Email_Alert{
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

//        echo "TEST TEST TEST<br><br>"; //TEMP FOR DEV (PETER)

        global $wpdb;
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        $count_of_subscr = 0;
        $count_alerts_for_subsc = 0;
        $subscr_ids = $this->get_all_active_subscr();

//        echo "SID:<br>";
//        echo $subscr_ids . "<br>";

        $update_status = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}gs_subscriber_email_alerts SET status='A' WHERE subscr_id in ({$subscr_ids})"));
        $newsletter_plan_ids = implode(",", array_keys(GS_NEWSLETTER_SUBSCRIPTION_PLANS));

//        echo "NPID:<br>";
//        echo $newsletter_plan_ids . "<br>";

        $ea_id = 0;
        //if (get_option("home") == "https://gs.adenium5.com"){
        //    $ea_id = 1050;
        //}else if (get_option("home") == "https://gs.adenium5.net"){
        //    $ea_id = 936;
        //}

        // FOR TESTING ONLY
        //$ea_id = 1050; //TEMP FOR DEV (PETER)
        $ea_id = 1071; //TEMP FOR DEV (PETER)

        if ($limit != 'all'){
            $sql = "SELECT a.ID, a.subscr_id, a.email, a.form_entry_id, a.user_id FROM {$wpdb->prefix}gs_subscriber_email_alerts a left join {$wpdb->prefix}pms_member_subscriptions m on m.id=a.subscr_id WHERE a.ID > {$ea_id} and a.status='A' AND a.alert_type=0 and m.subscription_plan_id not in ({$newsletter_plan_ids}) AND a.subscr_id IN($subscr_ids) LIMIT $limit";
        }else{
            $sql = "SELECT a.ID, a.subscr_id, a.email, a.form_entry_id, a.user_id FROM {$wpdb->prefix}gs_subscriber_email_alerts a left join {$wpdb->prefix}pms_member_subscriptions m on m.id=a.subscr_id WHERE a.ID > {$ea_id} and a.status='A' AND a.alert_type=0 and m.subscription_plan_id not in ({$newsletter_plan_ids}) AND a.subscr_id IN($subscr_ids)";
        }

//        echo "SQL: $sql" . "<br><br>";

        $data = $wpdb->get_results($wpdb->prepare($sql));
//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

//        echo "SENDING...<br>";
        $this->send_email($data);
//        echo "DONE SENDING!<br>";

    }
}
$daily = new GS_Cron_Daily_Email_Alert();
$daily->send();
