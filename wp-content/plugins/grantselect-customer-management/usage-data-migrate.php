<?php

require_once('../../../wp-load.php');

// To execute migration, use the following commands at the shell level:
// cd /home/u56-qtqqkmieskhe/www/grantselect.com/public_html/wp-content/plugins/grantselect-customer-management/ && php ./usage-data-migrate.php

class GS_Usage_Data_Migrate {
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
    public function migrate_usage_data($limit='all'){
        global $wpdb;
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        $count_of_subscr = 0;
        $count_alerts_for_subsc = 0;

        $this->table_subscriber_logs_orig           = $wpdb->prefix . 'gs_subscriber_logs_orig';
        $this->table_convert_subscriber             = $wpdb->prefix . 'gs_convert_subscriber';
        $this->table_pms_member_subscriptionmeta    = $wpdb->prefix . 'pms_member_subscriptionmeta';
        $this->table_subscriber_logs                = $wpdb->prefix . 'gs_subscriber_logs';

        echo "BEGIN MIGRATION...\n";

        $subscr_orig_records = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_subscriber_logs_orig}"));

        foreach ($subscr_orig_records as $subscr_orig_record) {

            $orig_subscr_id = $subscr_orig_record->subscr_id;
            $orig_user = $subscr_orig_record->user;
            $orig_ip = $subscr_orig_record->ip;
            $orig_referer = $subscr_orig_record->referer;
            $orig_datetime = $subscr_orig_record->date_time;

            echo "\n\nOSID: $orig_subscr_id";

            if ($subscr_orig_record->action == "ip" OR $subscr_orig_record->action == "referer" OR $subscr_orig_record->action == "url") {

                //SELECT convert.new_uid, convert.new_subscriber_id FROM qpl_gs_convert_subscriber (convert) WHERE convert.old_subscriber_id=orig.subscr_id  LIMIT 1
                $convert_records = $wpdb->get_results($wpdb->prepare("SELECT new_uid, new_subscriber_id FROM {$this->table_convert_subscriber} WHERE old_subscriber_id = %d LIMIT 1", $orig_subscr_id));
                $new_uid = $convert_records[0]->new_uid;
                $new_subscriber_id = $convert_records[0]->new_subscriber_id;

                echo " | NSID: $new_subscriber_id";

                if (!empty($new_subscriber_id)) {
                    //SELECT meta_value AS manager_name FROM qpl_pms_member_subscriptionmeta WHERE member_subscription_id = convert.new_subscriber_id AND meta_key="pms_group_name"  LIMIT 1
                    $sql_stmt = $wpdb->prepare("SELECT meta_value FROM {$this->table_pms_member_subscriptionmeta} WHERE member_subscription_id = %d AND meta_key = 'pms_group_name' LIMIT 1", $new_subscriber_id);
                    $manager_names = $wpdb->get_results($sql_stmt);
                    $manager_name = $manager_names[0]->meta_value;

                    //SET log.manager_id = convert.new_uid
                    //SET log.manager_name = manager_name
                    //SET log.user_id = 0
                    //SET log.user_name = orig.user (ok if this is empty)
                    //SET log.ip = orig.ip
                    //SET log.url = orig.referer
                    //SET log.sid = 0
                    //SET log.status = 0
                    //SET log.content = "login"
                    //SET log.created_at = orig.date_time
                    echo " | ";
                    echo "NUID: $new_uid | ";
                    echo "MN: $manager_name | ";
                    echo "OU: $orig_user | ";
                    echo "OIP: $orig_ip | ";
                    echo "ORF: $orig_referer | ";
                    echo "ODT: $orig_datetime";

                    $row = [];
                    $row['manager_id']      = $new_uid;
                    $row['manager_name']    = $manager_name;
                    if (!empty($orig_user)) {
                        $row['user_name']   = $orig_user;
                    }
                    if (!empty($orig_ip)) {
                        $row['ip']          = $orig_ip;
                    }
                    if (!empty($orig_referer)) {
                        $row['url']         = $orig_referer;
                    }
                    $row['content']         = "login";
                    $row['created_at']      = $orig_datetime;
//                    $result = $wpdb->insert(
//                        $this->table_subscriber_logs,
//                        $row
//                    );
//                    echo "\nR: $result\n";

//                    exit;
                }
            } elseif ($subscr_orig_record->action == "username") {
                //SELECT convert.new_uid, convert.new_subscriber_id FROM qpl_gs_convert_subscriber (convert) WHERE convert.old_subscriber_id=orig.subscr_id  LIMIT 1
                $convert_records = $wpdb->get_results($wpdb->prepare("SELECT new_uid, new_subscriber_id FROM {$this->table_convert_subscriber} WHERE old_subscriber_id = %d LIMIT 1", $orig_subscr_id));
                $new_uid = $convert_records[0]->new_uid;
//                $new_subscriber_id = $convert_records[0]->new_subscriber_id;

                if (!empty($new_uid)) {
                    //SET log.manager_id = convert.new_uid
                    //SET log.user_id = convert.new_uid
                    //SET log.user_name = orig.user
                    //SET log.ip = orig.ip
                    //SET log.url = orig.referer
                    //SET log.sid = 0
                    //SET log.status = 0
                    //SET log.content = "login"
                    //SET log.created_at = orig.date_time
                    echo " | ";
                    echo "NUID: $new_uid | ";
                    echo "OU: $orig_user | ";
                    echo "OIP: $orig_ip | ";
                    echo "ORF: $orig_referer | ";
                    echo "ODT: $orig_datetime";

                    $row = [];
                    $row['manager_id'] = $new_uid;
                    $row['user_id'] = $new_uid;
                    if (!empty($orig_user)) {
                        $row['user_name'] = $orig_user;
                    }
                    if (!empty($orig_ip)) {
                        $row['ip'] = $orig_ip;
                    }
                    if (!empty($orig_referer)) {
                        $row['url'] = $orig_referer;
                    }
                    $row['content'] = "login";
                    $row['created_at'] = $orig_datetime;
//                $result = $wpdb->insert(
//                    $this->table_subscriber_logs,
//                    $row
//                );
//                echo "\nR: $result\n";

//                exit;
                }
            }
        }

        echo "END MIGRATION...\n";
    }
}
$result = new GS_Usage_Data_Migrate();
$result->migrate_usage_data();
