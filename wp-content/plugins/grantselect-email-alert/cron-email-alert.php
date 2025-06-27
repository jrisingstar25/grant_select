<?php

require_once('../../../wp-load.php');
define( 'GS_EA_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
class GS_Cron_Email_Alert{
    private $limit       = 'all';
    private $about       = '';
    private $subject     = 'Funding Opportunities from GrantSelect.com';
    private $report      = '';
    public $yesterday_alerts = "";
    public function __construct(){
        $this->about = home_url('/about');
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
     * Function get_yesterday_alerts. Searching yesterday alerts
     * @params 
     * @return $res
     */
    function get_yesterday_alerts( )
    {
        global $wpdb;
        $res = array(); //initialize
        $date  = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d'))));
        $sql = null;
        $sql_query = "SELECT  `id` FROM " . $wpdb->prefix . "gs_grants WHERE email_alerts=1 and updated_at>'" . $date . " 00:00:00' and updated_at<'".$date." 23:59:59' AND status='A' ORDER BY updated_at DESC";
        $sql = $wpdb->prepare( $sql_query, $keyword_str, $keyword_str );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );
        $res = array_keys( $data );
        return implode(",", $res);
    }
    public function send_email($data){
        global $wpdb;
        $this->yesterday_alerts = $this->get_yesterday_alerts();
        if ($this->yesterday_alerts == ""){
            return;
        }
        if ($data) {
            $count_of_subscr = 0;
            
            foreach ($data as $ea) {
                $entry = GFAPI::get_entry( $ea->form_entry_id );
                //var_dump($entry);
                if ( is_wp_error($entry) ) {
                    continue;
                }
                $count_alerts_for_subsc = 0;
                $search_type = 'advanced';
                //generate array of ticked checkboxes
                $entry_checkbox_selections = array();   //initialize
                $entry_ids = array_keys( $entry );
                foreach ( $entry_ids as $eid ) {
                    if ( strpos( $eid, '.' ) ) {
                        $entry_id_parts = explode( '.', $eid );
                        if ( !empty( $entry[$eid] ) ) {
                            $entry_checkbox_selections[ $entry_id_parts[0] ][ $entry_id_parts[1] ] = $entry[$eid];
                        }
                    }
                }
                $out_result = array();  //initialize output
                $sort = 'up';   //default sort order
                
                if ( !empty( $entry['1'] ) ) {    // field: keywords
                    $out_result = $this->search_by_keyword( $entry['1'] );
                }
                
                if ( !empty( $entry_checkbox_selections[2] ) ) {   // field: geolocation-domestic
                    if ( !empty( $out_result ) ) {
                        if (!empty($this->search_by_geo_location( $entry_checkbox_selections[2] ))){
                            $out_result = $this->get_all_res( $out_result, $this->search_by_geo_location( $entry_checkbox_selections[2] ) );
                        }
                    }
                    else if (empty($out_result)) {
                        $out_result = $this->search_by_geo_location( $entry_checkbox_selections[2] );
                    }
                }
                
                if (!empty( $entry_checkbox_selections[5] )) {  // field: subject headings
                    if (!empty($out_result)) {
                        $out_result = $this->get_all_res( $out_result, $this->search_by_subject_title( $entry_checkbox_selections[5] ) );
                    }
                    else if (empty($out_result)) {
                        $out_result = $this->search_by_subject_title( $entry_checkbox_selections[5] );
                    }
                }
                
                if (!empty( $entry_checkbox_selections[29] )) {  // field: program type
                    if (!empty($out_result)) {
                        $out_result = $this->get_all_res( $out_result, $this->search_by_program_type( $entry_checkbox_selections[29] ) );
                    }
                    else if (empty($out_result)) {
                        $out_result = $this->search_by_program_type( $entry_checkbox_selections[29] );
                    }
                }
                //$out_result = $this->match_group_segment( $out_result, $ea->subscr_id, $ea->user_id );
                if (isset($out_result[0]) && $out_result[0] != '') {

                    $table_head_class = array(  //initialize
                        'id' => '',
                        'gt' => '',
                        'sp' => '',
                        'dl' => '',
                        'am' => '',
                        'ud' => '',
                        'ca' => ''
                    );
                    $sort_by_raw = 'ud';
                    //sort_by
                    $sort_dir['current'] = 'ASC';
                    $sort_dir['id']      = 'ASC';
                    $sort_dir['gt']      = 'ASC';
                    $sort_dir['sp']      = 'ASC';
                    $sort_dir['dl']      = 'ASC';
                    $sort_dir['am']      = 'ASC';
                    $sort_dir['ud']      = 'ASC';
                    $sort_dir['ca']      = 'ASC';
                    $table_head_class[$sort_by_raw] .= ' up';
                    
                    $sort_by     = 'gs.updated_at';
                    
                    $table_head_class['ud'] = 'sorted-by';
                    
                    $sort_dir[$sort_by_raw] = 'DESC';
                    $table_head_class[$sort_by_raw] .= ' up';
                    $order_by = 'ORDER BY ' . $sort_by . ' ' . $sort_dir['current'];
        
                    $out_result = $this->get_grant_sponsor_info( $out_result, $order_by, 0, 'access' );
                    $out_result = $this->add_all_deadlines( $out_result );
                    $grant_info_out = "";
                    $count_alerts_for_subsc = 0;
                    foreach ($out_result as $r){
                        $grant_info = json_decode(json_encode($r), true);
                        
                        $title = stripslashes($grant_info['title']);
                        $desc  = stripslashes($grant_info['description']);
                        $grant_info_out .= '<p style="margin-top:30px">';
                        $grant_info_out .= '<div style="color:#003078; font-size:14px;"><b>' . $title . '</b></div><br />';
                        $grant_info['description'] = htmlspecialchars($grant_info['description'], ENT_QUOTES);
                        $grant_info_out .= nl2br($grant_info['description']) . '<br /><br />';
                        if ($grant_info['requirements'] != '') {
                            $grant_info['requirements'] = htmlspecialchars($grant_info['requirements'], ENT_QUOTES);
                            $grant_info_out .= '<em style="color:#003078">Requirements:</em><br />' . $grant_info['requirements'] . '<br /><br />';
                        }
                        if ($grant_info['restrictions'] != '') {
                            $grant_info['restrictions'] = htmlspecialchars($grant_info['restrictions'], ENT_QUOTES);
                            $grant_info_out .= '<em style="color:#003078">Restrictions:</em><br />' . $grant_info['restrictions'] . '<br /><br />';
                        }
                        if ($grant_info['geo_focus'] != '') {
                            $grant_info_out .= '<em style="color:#003078">Geographic Focus</em>: ' . $grant_info['geo_focus'] . '<br />';
                        }
                        if (trim($grant_info['dead_lines']) == '') $grant_info['dead_lines']='Ongoing';
                        $grant_info_out .= '<em style="color:#003078">Date(s) Application is Due:</em>&nbsp; ' . $grant_info['dead_lines'] . '<br />';
                        $amounts = '';
                        if ($grant_info['amount_min'] != '' && $grant_info['amount_min'] != 0.00) {
                            $amounts = number_format($grant_info['amount_min']);
                        }
                        if ($grant_info['amount_min'] != '' && $grant_info['amount_min'] == 0.00) {
                            $amounts = 'Up to&nbsp;';
                        }
                        if ($grant_info['amount_max'] != '' && $grant_info['amount_max'] != 0.00) {
                            if ($amounts != '' && $amounts != 'Up to&nbsp;') {
                                $amounts .= ' - ' . number_format($grant_info['amount_max']);
                            }
                            else if ($amounts != '' && $amounts == 'Up to&nbsp;') {
                                $amounts .= number_format($grant_info['amount_max']);
                            }
                            else {
                                $amounts = number_format($grant_info['amount_max']);
                            }
                        }
                        if ($amounts != '') {
                            $grant_info_out .= '<em style="color:#003078">Amount of Grant:</em>&nbsp; ' . $amounts . ' ' . $grant_info['amount_currency'] . '<br />';
                        }
                        if ($grant_info['samples'] != '') {
                            $grant_info_out .= '<em style="color:#003078">Samples:</em><br />' . $grant_info['samples'] . '<br />';
                        }
                        
                        $contact_info = $this->get_contact_info($grant_info['id']);
                        if ($contact_info != '') {
                            $grant_info_out .= '<em style="color:#003078">Contact:</em> ' . nl2br($contact_info) . '<br />';
                        }
                        if ($grant_info['grant_url_1'] != '') {
                            $grant_info_out .= '<em style="color:#003078">Internet:</em> ' . $grant_info['grant_url_1'] . '<br />';
                        }
                        $sponsor_info = $this->get_sponsor_info($grant_info['id']);
                        if ($sponsor_info != '') {
                            $grant_info_out .= '<em style="color:#003078">Sponsor:</em> ' . $sponsor_info . '<br />';
                        }
                        $grant_info_out .= '<br /><img width="100%" height="20" style="border:0" alt="" src="' . GS_EA_PLUGIN_DIR_URL . 'images/breaker.jpg">';
                        
                        $grant_info_out .= '</p>';
                        $count_alerts_for_subsc++;
                        
                    }
                    if (!empty($grant_info_out)){
                        $this->send_mail_to_subscr($grant_info_out, $ea->email);
                    }
                    if ($count_alerts_for_subsc > 0) {
                        $this->report .= 'Subscriber: ' . '<a href=mailto:"' . $ea->email . '">' . $ea->email . '</a>  ' . $count_alerts_for_subsc . ' records Email sent for <a href=mailto:"' . $ea->email . '">' . $ea->email . '</a> today <br />';
                    }
                    else {
                        $this->report .= 'Subscriber: ' . '<a href=mailto:"' . $ea->email . '">' . $ea->email . '</a>  No records for <a href=mailto:"' . $ea->email . '">' . $ea->email . '</a> today <br />';
                    }
                    $count_alerts_for_subsc = 0;
                }
                $count_of_subscr++;
                
                $subscr_id = $ea->subscr_id;

                $sql_prep = $wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}pms_member_subscriptions WHERE id=%d", array($subscr_id) );
                $owner_user_id = $wpdb->get_var(
                    $sql_prep
                );

//                echo "SID: $subscr_id | OUID: $owner_user_id | $sql_prep\n";

                $user_id = $ea->user_id;
                $wpdb->insert(
                    $wpdb->prefix . "gs_subscriber_logs",
                    [
                        "manager_id"    => $owner_user_id,
                        "manager_name"  => pms_gm_get_group_name($subscr_id),
                        "user_id"       => $user_id,
                        "user_name"     => $ea->email,
                        "status"        => 2,
                        "content"       => "email alert",
                        "created_at"    => date("Y-m-d h:i:s")
                    ]
                );
            }
            $this->report = 'Total subscribers examined: ' . $count_of_subscr . '<br />' . $this->report;
            $this->send_mail_to_subscr($this->report, get_bloginfo('admin_email'));

        }
    }
    /**
     * Get all active subscribers info.
     *
     * @return $active_subscribers
     */
    public function get_all_active_subscr(){
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$wpdb->prefix}pms_member_subscriptions WHERE STATUS=%s", array("active")));
        $active_subscribers = "";
        foreach ($rows as $r){
            if ($active_subscribers == ""){
                $active_subscribers = $r->id;
            }else{
                $active_subscribers .= "," . $r->id;
            }
        }
        return $active_subscribers;
    }
    /**
     * Function search_by_keyword. Searching by title
     * @params $keyword
     * @return $res
     */
    function search_by_keyword( $keyword )
    {
        global $wpdb;
        $res = array(); //initialize
        $keyword_str = '';
        $keywords = explode(' ', $keyword);
        if (count($keywords) > 1) {
            foreach ($keywords as $key) {
                if (strlen($key) > 3) {
                    $keyword_str .= '+' . $key . '*' . ' ';
                }
            }
        }
        else {
            $keyword_str = '+' . $keywords[0] . '*';
        }
        $date  = date('Y-m-d', strtotime('-1 day', strtotime(date('Y-m-d'))));
        $sql = null;
        $sql_query = "SELECT  `id`, MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
            AGAINST (%s IN NATURAL LANGUAGE MODE) AS score
            FROM " . $wpdb->prefix . "gs_grants WHERE MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
            AGAINST (%s IN BOOLEAN MODE) AND id in ($this->yesterday_alerts)  ORDER BY score DESC";

        $sql = $wpdb->prepare( $sql_query, $keyword_str, $keyword_str );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );
        $res = array_keys( $data );
        return $res;
    }
    /**
     * Function get_all_res
     * @params $main, $adv
     * @return $res_array
     *
     * Removes items from $main that aren't included in $adv
     */
    function get_all_res(&$main, &$adv)  // $Main=existing out_result, $adv=new additional results
    {
        $res_array = array();
        foreach ($main as $m) {
            foreach ($adv as $a) {
                if ($m == $a) {
                    $res_array[] = $a;
                }
            }
        }
        return $res_array;
    }
    /**
     * Function search_by_geo_location
     * @params $geo_id
     * @return $res
     */
    function search_by_geo_location( $geo_ids )
    {
        global $wpdb;

        $geo_ids          = implode(",", $geo_ids);
        $all_states_id    = 1;
        $all_countries_id = 247;

        $sql_query = "SELECT geo_locale FROM " . $wpdb->prefix . "gs_grant_geo_locations WHERE id IN($geo_ids)";
        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        empty( $data['Domestic'] ) ? $domestic = 0 : $domestic = 1;
        empty( $data['Foreign'] ) ? $foreign = 0 : $foreign = 1;

        $geo_ids = explode(",", $geo_ids);
        if ($domestic == 1) {
            if ( !in_array( $all_states_id , $geo_ids ) ) {
                $geo_ids[] = $all_states_id;
            }
        }
        if ($foreign == 1) {
            if ( !in_array( $all_countries_id, $geo_ids ) ) {
                $geo_ids[] = $all_countries_id;
            }
        }
        $geo_ids = implode(",", $geo_ids);

        $sql_query = "SELECT grant_id FROM " . $wpdb->prefix . "gs_grant_geo_mappings as ggm
                        JOIN " . $wpdb->prefix . "gs_grants as gg ON ggm.grant_id = gg.id
                        WHERE geo_id IN($geo_ids) AND gg.id in ($this->yesterday_alerts)";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );
        return $res;
    }
    /**
     * Function  search_by_subject_title
     * @params $subject_title_ids
     * @return $res
     */
    function search_by_subject_title( $subject_title_ids )
    {
        global $wpdb;

        $subject_title_ids = implode(",", $subject_title_ids);

        $sql_query = "SELECT grant_id FROM " . $wpdb->prefix . "gs_grant_subject_mappings as gsm
                        JOIN " . $wpdb->prefix . "gs_grants as gg ON gsm.grant_id = gg.id
                        WHERE subject_id IN($subject_title_ids) AND gg.id in ($this->yesterday_alerts)";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }
    /**
     * Function search_by_program_type
     * @params $program_ids
     * @return $res
     */
    function search_by_program_type( $program_ids )
    {
        global $wpdb;
        $program_ids = implode(",", $program_ids);
        $sql_query = "SELECT grant_id FROM " . $wpdb->prefix . "gs_grant_program_mappings as gpm
                        JOIN " . $wpdb->prefix . "gs_grants as gg ON gpm.grant_id = gg.id
                        WHERE program_id IN ($program_ids) AND gg.id in ($this->yesterday_alerts)";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }

    /**
     * Function get_grant_sponsor_info
     * @params &$ids, $order, $if_sort_deadline
     * @return $res
     */
    function get_grant_sponsor_info( &$ids, $order, $if_sort_deadline, $search_mode="access" )
    {
        global $wpdb;

        if ( empty (trim($order)) ) $order='ORDER BY gs.updated_at DESC'; //default order

        switch ($search_mode) {
            default:
                $status_clause = "AND gs.status='A'";   //grab only active records
                break;
        }

        $res = array();

        $ids = array_unique($ids);
        $ids = implode(",", $ids);

        $sql_query = "SELECT
                          gs.id, gs.title, gs.description, gs.requirements, gs.restrictions, gs.samples, gs.grant_url_1, gs.amount_currency, gs.amount_min,
                          gs.amount_max, gscm.sponsor_id, gsp.sponsor_name,
                          df_deadline, dl_deadline, gs.updated_at, gs.created_at
                      FROM
                          " . $wpdb->prefix . "gs_grants AS gs
                      LEFT JOIN
                          " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings AS gscm
                      ON
                          gs.id = gscm.grant_id
                      LEFT JOIN
                          " . $wpdb->prefix . "gs_grant_sponsors AS gsp
                      ON
                          gscm.sponsor_id = gsp.id
                      LEFT JOIN
                          (SELECT grant_id, MIN( CONCAT(LPAD(month,2,'0'),'-',LPAD(date,2,'0')) ) as df_deadline,  MAX( CONCAT(LPAD(month,2,'0'),'-',LPAD(date,2,'0')) ) as dl_deadline FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE date_title='deadline' GROUP BY grant_id ORDER BY month, date) as dlt
                      ON
                          dlt.grant_id = gs.id
                      WHERE
                          gs.id IN ($ids)
                          $status_clause
                      GROUP BY
                          gs.id
                      $order";

        $sql = $wpdb->prepare( $sql_query, $ids );
        $data = $wpdb->get_results( $sql );
        $count = 0;
        foreach ( $data as $data_row ) {
            $dead_lines = $this->get_dead_lines($data_row->id);
            $res[$count]['id']              = $data_row->id;
            $res[$count]['title']           = $data_row->title;
            $res[$count]['description']     = $data_row->description;
            $res[$count]['requirements']    = $data_row->requirements;
            $res[$count]['restrictions']    = $data_row->restrictions;
            $res[$count]['geo_focus']       = $this->get_sort_geo_locations($data_row->id);
            $res[$count]['dead_lines']      = $dead_lines;
            $res[$count]['samples']         = $data_row->samples;
            $res[$count]['grant_url_1']     = $data_row->grant_url_1;
            $res[$count]['amount_currency'] = $data_row->amount_currency;
            $res[$count]['amount_min']      = $data_row->amount_min;
            $res[$count]['amount_max']      = $data_row->amount_max;
            $res[$count]['sponsor_name']    = $data_row->sponsor_name;
            $res[$count]['deadline_first']  = strtotime(date('Y') . '-' . $data_row->df_deadline);
            $res[$count]['deadline_last']   = strtotime(date('Y') . '-' . $data_row->dl_deadline);
            $res[$count]['created_at']      = $data_row->created_at;
            $res[$count]['updated_at']      = $data_row->updated_at;
            $count++;
        }

        return $res;
    }
    /**
     * Function add_all_deadlines
     * @params $grant_sponsor_info
     * @return $grant_sponsor_info
     */
    function add_all_deadlines( $grant_sponsor_info )
    {
        $count = 0;
        foreach ( $grant_sponsor_info as $key => $value ) {
            $grant_sponsor_info[$count]['deadline'] = $this->get_deadline( $value['id'] );
            $count++;
        }
        return $grant_sponsor_info;
    }
     /**
     * Function get_dead_lines
     * @params $grant_id
     * @return $res 
     */
    private function get_dead_lines($grant_id)
    {
        global $wpdb;
        $res = '';
        $months = array(
                          '1' => 'Jan', '2'  => 'Feb', '3'  => 'Mar', '4'  => 'Apr',
                          '5' => 'May', '6'  => 'Jun', '7'  => 'Jul', '8'  => 'Aug',
                          '9' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
                      );
        $sql = "SELECT month, date FROM {$wpdb->prefix}gs_grant_key_dates WHERE grant_id=$grant_id AND date_title='deadline'";
        $data = $wpdb->get_results($wpdb->prepare($sql));
        if(!empty($data)) {
          foreach($data as $record) {
            $temp_month = $months[$record->month];
            if ($res != '') {
                $res .= '; ' . $temp_month . ' ' . $record->date; 
            }
            else {
                $res = $temp_month . ' ' . $record->date;
            }
            $temp_month = '';
          }
        }
        return $res;
    }
    /**
     * Function get_deadline_ids
     * @params $grant_id
     * @return $deadline_ids
     */
    function get_deadline_ids( $grant_id )
    {
        global $wpdb;

        $sql_query = "SELECT id FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE grant_id=%d AND date_title=%s";
        $sql = $wpdb->prepare( $sql_query, $grant_id, 'deadline' );
        $data = $wpdb->get_results( $sql, 'ARRAY_A' );

        $deadline_ids = $data;

        return $deadline_ids;
    }
    /**
     * Function get_dead_line
     * @params $grant_id
     * @return $dead_line
     */
    function get_deadline($grant_id)
    {
        global $wpdb;

        $sql_query = "SELECT month, date FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE grant_id=%d AND date_title='deadline'";
        $sql = $wpdb->prepare( $sql_query, $grant_id );
        $data = $wpdb->get_results( $sql );

        $deadline = ''; //initialize
        foreach ( $data as $data_item ) {
            if ($deadline == '') {
                $deadline = $data_item->month . '-' . $data_item->date;
            }
            else {
                $deadline .= ',' . $data_item->month . '-' . $data_item->date;
            }
        }

        return $deadline;
    }
    /**
     * Function get_contact_info.
     * @params $grant_id
     * @return $out_info
     */
    public function get_contact_info($grant_id)
    {
        global $wpdb;
        $ids        = $this->get_sponsor_contact_ids($grant_id);
        $res        = array();
        $out_info   = '';
        foreach ($ids as $key => $value) {
            $contact_id = $value['contact_id'];   

            $data = $wpdb->get_results($wpdb->prepare("SELECT contact_name, contact_title, contact_phone_1, contact_phone_2, contact_fax, contact_email_1, contact_email_2  
                                     FROM {$wpdb->prefix}gs_grant_contacts WHERE id=%d", array($contact_id)));
            if(!empty($data)) {
              foreach ($data as $record){
                $res[] = array(
                             'contact_name'    => $record->contact_name,
                             'contact_title'   => $record->contact_title,
                             'contact_phone_1' => $record->contact_phone_1,
                             'contact_phone_2' => $record->contact_phone_2,
                             'contact_fax'     => $record->contact_fax,
                             'contact_email_1' => $record->contact_email_1,
                             'contact_email_2' => $record->contact_email_2
                          );
              }
            }
        }
        foreach ($res as $key => $value) {
            if ($value['contact_name'] != '') {
                $out_info .= $value['contact_name'] . ', ';
            }
            if ($value['contact_title'] != '') {
                $out_info .= $value['contact_title'] . '; ';
            }
            if ($value['contact_phone_1'] != '' and $value['contact_phone_2'] != '') {
                $out_info .= $value['contact_phone_1'] . ' or ' . $value['contact_phone_2'] . '; ';
            }
            else if ($value['contact_phone_1'] != '' and $value['contact_phone_2'] == '') {
                $out_info .= $value['contact_phone_1'] . '; ';
            }
            else if ($value['contact_phone_1'] == '' and $value['contact_phone_2'] != '') {
                $out_info .= $value['contact_phone_2'] . '; ';
            }
            if ($value['contact_fax'] != '') {
                $out_info .= "fax " . $value['contact_fax'] . '; ';
            }
            if ($value['contact_email_1'] != '' and $value['contact_email_2'] != '') {
                $out_info .= $value['contact_email_1'] . ' or ' . $value['contact_email_2'] . '; ';
            }
            else if ($value['contact_email_1'] != '' and $value['contact_email_2'] == '') {
                $out_info .= $value['contact_email_1'] . '; ';
            }
            else if ($value['contact_email_1'] == '' and $value['contact_email_2'] != '') {
                $out_info .= $value['contact_email_2'] . '; ';
            }
            $out_info = substr($out_info,0,-2);
            $out_info .= "\n";
        }
        //$out_info = substr($out_info,0,-1);
        return $out_info;
    }
    /**
     * Function get_sponsor_contact_ids
     * @params $grant_id
     * @return $res(array with sponsor_id and contact_id for this grant)
     */
    private function get_sponsor_contact_ids($grant_id)
    {
        global $wpdb;
        $res = array();

        $data = $wpdb->get_results($wpdb->prepare("SELECT sponsor_id, contact_id FROM {$wpdb->prefix}gs_grant_sponsor_contact_mappings WHERE grant_id=%d", array($grant_id)));
        if(!empty($data)) {
          foreach ($data as $record){
            $res[] = array(
                              'sponsor_id' => $record->sponsor_id,
                              'contact_id' => $record->contact_id
                          );
          }
        }
        return $res;
    }
    /**
     * Function get_sponsor_info.
     * @params $grant_id
     * @return $out_info 
     */
    private function get_sponsor_info($grant_id) 
    {
        global $wpdb;
        $ids        = $this->get_sponsor_contact_ids($grant_id);
        $res        = array();
        $out_info   = '';
        $sponsor_id = $ids[0]['sponsor_id'];
        $data = $wpdb->get_results($wpdb->prepare("SELECT sponsor_name, sponsor_address, sponsor_city, sponsor_state, sponsor_zip, sponsor_country
                                 FROM {$wpdb->prefix}gs_grant_sponsors WHERE id=%d", array($sponsor_id)));
        if(!empty($data)) {
          foreach ($data as $record){
            $res[] = array(
                'sponsor_name'    => $record->sponsor_name,
                'sponsor_address' => $record->sponsor_address,
                'sponsor_city'    => $record->sponsor_city,
                'sponsor_state'   => $record->sponsor_state,
                'sponsor_zip'     => $record->sponsor_zip,
                'sponsor_country' => $record->sponsor_country
            );
          }
        }
        foreach ($res as $key => $value) {
            if ($value['sponsor_name'] != '') {
                $out_info .= $value['sponsor_name'] . '<br />';
            }
            if ($value['sponsor_address'] != '') {
                $out_info .= $value['sponsor_address'] . '<br />';
            }
            if ($value['sponsor_city'] != '') {
                $out_info .= $value['sponsor_city'] . ', ';
            }
            if ($value['sponsor_state'] != '') {
                $out_info .= $value['sponsor_state'] . ' ';
            }
            if ($value['sponsor_zip'] != '') {
                $out_info .= $value['sponsor_zip'] . '<br />';
            }
            if ($value['sponsor_country'] != '') {
                $out_info .= $value['sponsor_country'];
            }
        }
        return $out_info;
    }
    /**
     * Function get_sort_geo_locations
     * @params $grant_id
     * @return $location_names
     * @desc getting all geo locations name of grant by grant id 
     */
    private function get_sort_geo_locations($grant_id)
    {
        global $wpdb;
        $location_names = '';
        $domestic = array();
        
        $sql = "SELECT * FROM {$wpdb->prefix}gs_grant_geo_locations WHERE id IN (SELECT geo_id FROM {$wpdb->prefix}gs_grant_geo_mappings WHERE `grant_id`=$grant_id)";
        $data = $wpdb->get_results($wpdb->prepare($sql));
        if(!empty($data)) {
          foreach ($data as $record){
            if ($record->geo_locale == 'Domestic') {
                $domestic[] = $record->geo_location;
            }
          }
        }
        sort($domestic);
        $d = implode(", ", $domestic);
        $d = preg_replace("/ALL STATES/", 'All States', $d);
        $location_names = $d;
        return $location_names;
    }
    /**
     * Function match user's group segment to out array.
     * @params $out_array, $subscr_id, $user_id
     * @return $out_array 
     */
    private function match_group_segment($main, $subscr_id, $user_id) 
    {
        global $wpdb;
        if (empty($main)){
            return $main;
        }
        $res_array = array();
        
        $group_segments = [];
        if ($subscr_id != 0){
            $group_info = $wpdb->get_row($wpdb->prepare("select user_id from {$wpdb->prefix}pms_member_subscriptions where id=%d", array($subscr_id)));
            $group_segments = explode(",", get_user_meta($group_info->user_id, "customer_segments", true));
        }
        $user_segments = [];
        if ($user_id != 0){
            $user_segments = explode(",", get_user_meta($user_id, "customer_segments", true));
        }
        $segments_arr = array_unique(array_merge($group_segments, $user_segments));
        $segments = implode(",", $segments_arr);
        $grant_ids = implode(",", $main);
        $result = $wpdb->get_results($wpdb->prepare("select grant_id from {$wpdb->prefix}gs_grant_segment_mappings where segment_id in ($segments) and grant_id in ($grant_ids)"));
        foreach ($result as $seg_mapping){
            array_push($res_array, $seg_mapping->grant_id);
        }
        return $res_array;
    }

     /**
     * Function send_mail_to_subscr
     * @params grant_info 
     */
    private function send_mail_to_subscr($grant_info, $subscr_email)
    {
        //$email = $subscr_email;
        $email_body = $this->make_email_body($grant_info);
        $admin_name = wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );
        $admin_email = get_bloginfo('admin_email');
        $headers[] = "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";
	    $headers[] = 'From: ' . $admin_name . ' <' . $admin_email . ">\r\n";
        //echo $email_body;
        $result = wp_mail($subscr_email, $this->subject, $email_body, $headers);
        echo $subscr_email . " - " . $result . "\n";
    }
    /**
     * Function make_email_body 
     * @params $title, $desc
     */
    private function make_email_body($grant_info)
    {
        $base_url = substr(GS_EA_PLUGIN_DIR_URL ,0,-1);
        $about = $this->about;
        $from_name = wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );
        $admin_email = get_bloginfo('admin_email');
        $home_url = home_url();
        $email_body = <<< EndOfMail
        <head>
	
	<title>GrantSelect Email Alerts</title>
	
	<meta content="text/html; charset=iso-8859-1" http-equiv="Content-Type" />
	<style type="text/css">
	.list a {color: #9E3B00; text-transform: uppercase; font-family: Verdana; font-size: 11px; text-decoration: none;}

	</style>
	
	
</head>
<body marginheight="0" topmargin="0" marginwidth="0" bgcolor="#c5c5c5" leftmargin="0">
<table cellspacing="0" border="0" style="background-image: url($base_url/images/bg_alerts.gif); background-color: #c5c5c5;" cellpadding="0" width="100%">
	
	<tr>
		
		<td valign="top">
			<table cellspacing="0" border="0" align="center" style="background: #fff; border-right: 1px solid #ccc; border-left: 1px solid #ccc;" cellpadding="0" width="600">
				<tr>
					<td valign="top">
            <br />
						<!-- header -->
						<table cellspacing="0" border="0" cellpadding="0" width="600">
							<tr>
								<td class="main-title" height="13" valign="top" style="padding: 0 20px; font-size: 25px; font-family: Georgia; font-style: italic;" width="600" colspan="2">
								<img src="$base_url/images/GS-gear.png" height="25" alt="GrantSelect logo" style="border: 0;" />
									GrantSelect Email Alerts
								</td>
							</tr>
							<tr>
								<td height="20" valign="middle" width="600" colspan="2">
                  <img src="$base_url/images/breaker.jpg" height="20" alt="" style="border: 0;" width="600" />
								</td>
							</tr>
							<tr>
								<td class="header-bar" valign="top" style="color: #999; font-family: Verdana; font-size: 10px; text-transform: uppercase; padding: 0 20px" width="600">
									The Practical Online Grants Resource | <a href="$home_url" style="text-decoration:none;color:#999">GrantSelect.com</a>
								</td>
              </tr>
							<tr>
								<td valign="top" width="600" colspan="2">
									<img src="$base_url/images/breaker.jpg" height="20" alt="" style="border: 0;" width="600" />
								</td>
							</tr>
						</table>
						<!-- / header -->
					</td>
				</tr>
				<tr>
					<td>
						<!-- content -->
                        <table cellspacing="0" border="0" cellpadding="0" width="600">
                            <tr>
								<td class="content-copy" valign="top" style="padding: 0 20px 30px; color: #000; font-size: 12px; font-family: Georgia; line-height: 20px;" colspan="2">
									$grant_info
								</td>
							</tr>
                        </table>
						<!--  / content -->
					</td>
				</tr>
				<tr>
					<td valign="top" width="600">
						<!-- footer -->
						<table cellspacing="0" border="0" height="202" cellpadding="0" width="600">
							<tr>
								<td height="20" valign="top" width="600" colspan="2">
									<img src="$base_url/images/breaker.jpg" height="20" alt="" style="border: 0;" width="600" />
								</td>
							</tr>
							<tr>
								<td valign="top" style="padding: 20px; color: #999; font-size: 14px; font-family: Georgia; line-height: 20px;" width="305">
								<p><span style="padding: 20px 0; color: #90B72E; font-family: Georgia; font-size: 14px; font-weight: bold;">Modify Alert Settings</span><br />
								Want more alerts? You can modify your settings by logging on to <a href="$home_url/login" style="color: #9E3B00; text-decoration: none;">GrantSelect</a> and clicking on "Change My Alerts". Choose as many or as few subjects as needed in order to be alerted of any new or updated grants in those areas.</p>
								<p><span style="padding: 20px 0; color: #90B72E; font-family: Georgia; font-size: 14px; font-weight: bold;">Search Tips</span><br>
								If you need more help with how to search for funding for your specific program send an email to the <a href="mailto:$admin_email" style="color: #9E3B00; text-decoration: none;">info@grantselect.com</a> for tips and hints. Please be concise. Our Chief Editor will respond to your queries by email.</p>
								</td>
							</tr>
							<tr>
								<td valign="top" colspan="2">
									<img src="$base_url/images/breaker.jpg" height="20" alt="" style="border: 0;" width="600" />
								</td>
							</tr>
							<tr>
								<td class="copyright" height="100" align="center" valign="top" style="padding: 0 20px; color: #999; font-family: Verdana; font-size: 9px; text-transform: uppercase; line-height: 15px;" width="600" colspan="2">
									GrantSelect and the GrantSelect logo are registered trademarks of Schoolhouse Partners LLC<br />
									Schoolhouse Partners - 1281 Win Hentschel Blvd., West Lafayette, IN 47906. Ph 765-237-3390
								</td>
							</tr>
						</table>
						<!-- / end footer -->
					</td>
				</tr>
			</table>
			
		</td>
	
	</tr>
	
</table>

</body>
EndOfMail;
        return $email_body;
    }
}
$cron = new GS_Cron_Email_Alert();
// $entry = GFAPI::get_entry( 3879 );
// var_dump($entry);
