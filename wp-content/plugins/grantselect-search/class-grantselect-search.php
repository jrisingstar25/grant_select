<?php

// Make sure Gravity Forms is active and already loaded.
if (!class_exists('GFForms')) {
    die();
}
require_once 'lib/dompdf/autoload.inc.php';
// reference the Dompdf namespace
use Dompdf\Dompdf;

GFForms::include_feed_addon_framework();
//clean string.
function cleanData(&$str)
{
    $str = preg_replace("/\t/", "\\t", $str);
    $str = preg_replace("/\r?\n/", "\\n", $str);
    //if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
}
define("SEARCH", 1);
/**
 * GrantSelectSearchAddOn
 *
 * @copyright   Copyright (c) 2020-2021, GrantSelect
 * @since       1.0
 */
class GrantSelectSearchAddOn extends GFFeedAddOn {

    protected $_version = GF_GRANTSELECT_SEARCH_ADDON_VERSION;
    protected $_min_gravityforms_version = '2.4.20';
    protected $_slug = 'grantselect-search';
    protected $_path = 'grantselect-search/grantselect-search.php';
    protected $_full_path = __FILE__;
    protected $_title = 'GrantSelect Search Functionality';
    protected $_short_title = 'GrantSelect Search Functionality';
    
    /**
     * @var object|null $_instance If available, contains an instance of this class.
     */
    private static $_instance = null;

    /**
     * Returns an instance of this class, and stores it in the $_instance property.
     *
     * @return object $_instance An instance of this class.
     */
    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @param $feed
     * @param $entry
     * @param $form
     */
    public function process_feed($feed, $entry, $form )
    {
        // this is for adding meta-data to the entry
        return $entry;
    }
    // function for logged in user and guest user id
    public function get_current_user_and_guest_id(){
        if (is_user_logged_in()){
            return get_current_user_id();
        }else if (isset($_SESSION['guest_user_id'])){
            return $_SESSION['guest_user_id'];
        }else{
            return 0;
        }
    }
    // function for search results shortcode
    public function grantselect_search_display_results( $atts ) {
        
        //this is used for AJAX calls for mass edits
        $ajax_nonce = wp_create_nonce( "shpgs-mass-edits-x8q9z" );

        $entry_id = absint( $_GET['sid'] );
        $sort_by = '';

        $page = absint( $_GET['pn'] );  //display this page of the results
        if ( empty($page) ) $page = 1;
        
        $perpage = absint( $_GET['pp'] );  //display result per page of the results
        if ( empty($perpage) && !get_user_meta(self::get_current_user_and_guest_id(), 'gs_per_page', true)){
            add_user_meta(get_current_user_id(), 'gs_per_page', 10, true);
        } 
        $perpage = get_user_meta(self::get_current_user_and_guest_id(), 'gs_per_page', true);

        //this needs to be here to handle issues with IP-authenticaed and Referer URL-authenticated users
        //(who do not logged in to user accounts)
        if (empty($perpage)) {
            if ( absint( $_GET['pp'] ) ) {
                $perpage=absint( $_GET['pp'] );
            } else {
                $perpage=10;
            }
        }

        $current_user = self::get_current_user_and_guest_id();
        if ($current_user == 0){
            return "";
        }
        $display_content = "";  //initialize
        $display_content .= "<div class='grantselect-search-results'>";
        $display_content .= self::search_results( $current_user, $entry_id, $page, $sort_by, $perpage );
        $display_content .= "</div>";

        return $display_content;
    }

    /**
     * Function search_results
     * $user_id         = ID of user to whom the results belong
     * $entry_id        = Gravity Forms entry_id for the submitted search form
     * $page            = Which page of results should be displayed
     * $sort_by         = Column to sort by. Valid options: "title","amount","deadline","sponsor"
     * $search_mode     = "access" or "editor" -- displays pages differently based on what type of user is accessing them
     *
     * @return $out_result
     */
    function search_results( $user_id, $entry_id, $num_of_page, $sort_by, $perpage, $search_mode='access' )
    {
        global $wpdb;
        if ( empty($entry_id) ) {
            return false;
        }
        
        if (isset($_GET['saved']) && $_GET['saved'] == "true"){
            if (isset($_GET['pn']) && $_GET['pn'] > 0) {
                do_action("gs_add_subscriber_log", self::get_current_user_and_guest_id(), SEARCH_STATUS_PN . "_" . $entry_id, "call search page");
            } else {
                do_action("gs_add_subscriber_log", self::get_current_user_and_guest_id(), SAVED_SEARCH . "_" . $entry_id, "call search page");
            }
            $ss_id = $wpdb->get_var($wpdb->prepare("select ID from {$wpdb->prefix}gs_save_searchresult where entry_id=%d and is_agent=0", array($entry_id)));
            $entry = GFAPI::get_entry( $entry_id );
            $created_by = 'xXxXx';
            if ( !is_wp_error($entry) ) {
                $created_by = $entry['created_by'];
            }

            if ($created_by != null && $user_id != $created_by ) {
                $out_result = array(
                    'error'    => true,
                    'error_msg' => "<h2>Access denied</h2><p>You are not logged in to the appropriate account for accessing this content.</p>",
                );
                $content = '<div class="search-error">';
                $content .= $out_result['error_msg'];
                $content .= '</div>';

                return $content;
            }

            $search_type = '';
            switch ( $entry['form_id'] ) {
                case '1':   //access quick search
                    $search_type = 'quick';
                    break;
                case '2':   //access advanced search
                    $search_type = 'advanced';
                    break;
                case '6':   //editor quick search
                    $search_type = 'quick';
                    break;
                case '7':   //editor title search
                    $search_type = 'title';
                    break;
                case '8':   //editor sponsor search
                    $search_type = 'sponsor';
                    break;
            }
            $out_result = self::get_saved_grant_sponsor_info( $ss_id, $search_mode );

        }else{
            if (isset($_GET['agent']) && $_GET['agent'] == "true"){
                if (isset($_GET['pn']) && $_GET['pn'] > 0) {
                    do_action("gs_add_subscriber_log", self::get_current_user_and_guest_id(), SEARCH_STATUS_PN . "_" . $entry_id, "call agent page");
                } else {
                    do_action("gs_add_subscriber_log", self::get_current_user_and_guest_id(), SEARCH_AGENT . "_" . $entry_id, "call agent page");
                }
            }else{
                if (isset($_GET['pn']) && $_GET['pn'] > 0) {
                    do_action("gs_add_subscriber_log", self::get_current_user_and_guest_id(), SEARCH_STATUS_PN . "_" . $entry_id, "call search page");
                } else {
                    do_action("gs_add_subscriber_log", self::get_current_user_and_guest_id(), SEARCH_STATUS . "_" . $entry_id, "call search page");
                }
            }
            
            $entry = GFAPI::get_entry( $entry_id );
            $created_by = 'xXxXx';
            if ( !is_wp_error($entry) ) {
                $created_by = $entry['created_by'];
            }

            if ($created_by != null && $user_id != $created_by ) {
                $out_result = array(
                    'error'    => true,
                    'error_msg' => "<h2>Access denied</h2><p>You are not logged in to the appropriate account for accessing this content.</p>",
                );
                $content = '<div class="search-error">';
                $content .= $out_result['error_msg'];
                $content .= '</div>';

                return $content;
            }

            $search_type = '';
            switch ( $entry['form_id'] ) {
                case '1':   //access quick search
                    $search_type = 'quick';
                    break;
                case '2':   //access advanced search
                    $search_type = 'advanced';
                    break;
                case '6':   //editor quick search
                    $search_type = 'quick';
                    break;
                case '7':   //editor title search
                    $search_type = 'title';
                    break;
                case '8':   //editor sponsor search
                    $search_type = 'sponsor';
                    break;
            }

    //        echo "ST:$search_type<br>";

            switch ( $entry['form_id'] ) {
                case '7':   //editor title search
                    $title_search_param = $entry[9];
                    $id_search_param = $entry[10];
                    break;
                case '8':   //editor sponsor search
                    $sponsor_search_param = $entry[8];
                    break;
                case '2':   //access advanced search
                    $title_search_param       = $entry[9];
                    $sponsor_search_param     = $entry[8];
                    $description_search_param = $entry[10];
                    break;
            }

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
            switch ( $search_type ) {
                case 'advanced':
                    if ( !empty( $entry['1'] ) ) {    // field: keywords
                        $out_result = self::search_by_keyword( $entry['1'] );
                    }else{
                        if (isset($_GET['search']) && $_GET['search'] != ""){
                            $out_result = self::search_by_keyword( $_GET['search'] );
                        }
                    }
                    if ( !empty( $entry_checkbox_selections[2] ) ) {   // field: geolocation-domestic
                        if ( !empty( $out_result ) ) {
                            $out_result = self::get_all_res( $out_result, self::search_by_geo_location( $entry_checkbox_selections[2] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_geo_location( $entry_checkbox_selections[2] );
                        }
                    }
                    if ( !empty( $entry_checkbox_selections[3] ) ) {   // field: geolocation-foreign
                        if ( !empty( $out_result ) ) {
                            $out_result = self::get_all_res( $out_result, self::search_by_geo_location( $entry_checkbox_selections[3] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_geo_location( $entry_checkbox_selections[3] );
                        }
                    }
                    if (!empty( $entry_checkbox_selections[5] )) {  // field: subject headings
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_subject_title( $entry_checkbox_selections[5] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_subject_title( $entry_checkbox_selections[5] );
                        }
                    }
                    if (!empty( $entry[6] )) {  // field: program type
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_program_type( $entry[6] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_program_type( $entry[6] );
                        }
                    }
                    if (!empty( $entry[7] )) {  // field: sponsor type
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_sponsor_type( $entry[7] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_sponsor_type( $entry[7] );
                        }
                    }
                    if (!empty( $sponsor_search_param )) {  // field: sponsor name
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_sponsor_name( $sponsor_search_param ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_sponsor_name( $sponsor_search_param );
                        }
                    }
                    if (!empty( $title_search_param )) {  // field: title of grant
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_title( $title_search_param ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_title( $title_search_param );
                        }
                    }
                    if (!empty( $description_search_param )) {  // field: description
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_description( $description_search_param ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_description( $description_search_param );
                        }
                    }
                    if (!empty( $entry[11] )) {  // field: requirements
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_requirements( $entry[11] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_requirements( $entry[11] );
                        }
                    }
                    if ( $entry_checkbox_selections[16] == TRUE) { // fields: deadline
                        $start_month = $entry[12];
                        $start_date  = $entry[13];
                        $end_month   = $entry[14];
                        $end_date    = $entry[15];
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res($out_result, self::search_by_deadlines($start_month, $start_date, $end_month, $end_date));
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_deadlines($start_month, $start_date, $end_month, $end_date);
                        }
                    }
                    if (!empty( $entry[17] )) {  // field: restrictions
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_restrictions( $entry[17] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_restrictions( $entry[17] );
                        }
                    }
                    if ( !empty( $id_search_param ) ) {    // field: id
                        $out_result[] = $id_search_param;
                    }
                    break;
                case 'sponsor':
                    if (!empty( $sponsor_search_param )) {  // field: sponsor name
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_sponsor_name( $sponsor_search_param, 'editor' ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_sponsor_name( $sponsor_search_param, 'editor' );
                        }
                    }
                    break;
                case 'title':
                    if (!empty( $title_search_param )) {  // field: title of grant
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_title( $title_search_param ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_title( $title_search_param );
                        }
                    }
                    if ( !empty( $id_search_param ) ) {    // field: id
                        $out_result[] = $id_search_param;
                    }
                    break;
                case 'quick':
                    if ( !empty( $entry['1'] ) ) {    // field: keywords
                        $out_result = self::search_by_keyword( $entry['1'] );
                    }else{
                        if (isset($_GET['search']) && $_GET['search'] != ""){
                            $out_result = self::search_by_keyword( $_GET['search'] );
                        }
                    }
                    if ( !empty( $entry_checkbox_selections[2] ) ) {   // field: geolocation-domestic
                        if ( !empty( $out_result ) ) {
                            $out_result = self::get_all_res( $out_result, self::search_by_geo_location( $entry_checkbox_selections[2] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_geo_location( $entry_checkbox_selections[2] );
                        }
                    }
                    if (!empty( $entry_checkbox_selections[3] )) {  // field: subject headings
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_subject_title( $entry_checkbox_selections[3] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_subject_title( $entry_checkbox_selections[3] );
                        }
                    }
                    break;
            }
            if ($out_result[0] != '') {

                $table_head_class = array(  //initialize
                    'id' => '',
                    'gt' => '',
                    'sp' => '',
                    'dl' => '',
                    'am' => '',
                    'ud' => '',
                    'ca' => ''
                );
    
                //sort_by
                if ( empty($_GET['sd']) ) {
                    $sort_dir['current'] = 'ASC';
                    $sort_dir['id']      = 'ASC';
                    $sort_dir['gt']      = 'ASC';
                    $sort_dir['sp']      = 'ASC';
                    $sort_dir['dl']      = 'ASC';
                    $sort_dir['am']      = 'ASC';
                    $sort_dir['ud']      = 'ASC';
                    $sort_dir['ca']      = 'ASC';
                    $table_head_class[$sort_by_raw] .= ' up';
                } else {
                    $sort_dir['current'] = filter_var ( $_GET['sd'], FILTER_SANITIZE_STRIPPED );
                    $sort_dir['id']      = $sort_dir['current'];
                    $sort_dir['gt']      = $sort_dir['current'];
                    $sort_dir['sp']      = $sort_dir['current'];
                    $sort_dir['dl']      = $sort_dir['current'];
                    $sort_dir['am']      = $sort_dir['current'];
                    $sort_dir['ud']      = $sort_dir['current'];
                    $sort_dir['ca']      = $sort_dir['current'];
                };

                if ( empty($_GET['sb']) ) {
                    $sort_by     = 'gs.updated_at';
                    $sort_by_raw = 'ud';
                    $table_head_class['ud'] = 'sorted-by';
                    $sort_dir['current'] = 'DESC'; //ensure most recently updated records are on top by default
                } else {
                    switch ($_GET['sb']) {
                        case 'id':
                            $sort_by     = 'gs.id';
                            $sort_by_raw = 'id';
                            $table_head_class['id'] = 'sorted-by';
                            break;
                        case 'gt':
                            $sort_by     = 'title';
                            $sort_by_raw = 'gt';
                            $table_head_class['gt'] = 'sorted-by';
                            break;
                        case 'sp':
                            $sort_by     = 'sponsor_name';
                            $sort_by_raw = 'sp';
                            $table_head_class['sp'] = 'sorted-by';
                            break;
                        case 'dl':
                            if ($sort_dir['current'] == 'DESC') {
                                $sort_by     = 'dl_deadline';
                            } else {
                                $sort_by     = 'df_deadline';
                            }
                            $sort_by_raw = 'dl';
                            $table_head_class['dl'] = 'sorted-by';
                            break;
                        case 'am':
                            if ($sort_dir['current'] == 'DESC') {
                                $sort_by     = 'amount_max';
                            } else {
                                $sort_by     = 'amount_min';
                            }
                            $sort_by_raw = 'am';
                            $table_head_class['am'] = 'sorted-by';
                            break;
                        case 'ud':
                            $sort_by     = 'gs.updated_at';
                            $sort_by_raw = 'ud';
                            $table_head_class['ud'] = 'sorted-by';
                            break;
                        case 'ca':
                            $sort_by     = 'gs.created_at';
                            $sort_by_raw = 'ca';
                            $table_head_class['ca'] = 'sorted-by';
                            break;
                    }
                };

                if ( $sort_dir['current'] == 'ASC' ) {
                    $sort_dir[$sort_by_raw] = 'DESC';
                    $table_head_class[$sort_by_raw] .= ' up';
                } else {
                    $sort_dir[$sort_by_raw] = 'ASC';
                    $table_head_class[$sort_by_raw] .= ' down';
                }

                if ($sort_by == 'title' || $sort_by == 'sponsor_name') {
                    $order_by = 'ORDER BY TRIM(' . $sort_by . ') ' . $sort_dir['current'];
                } else {
                    $order_by = 'ORDER BY ' . $sort_by . ' ' . $sort_dir['current'];
                }

                switch ($sort_by) {
                    case 'title':
                        $order_by .= ', TRIM(sponsor_name) ASC';
                        break;
                    case 'sponsor_name':
                        $order_by .= ', TRIM(title) ASC';
                        break;
                    case 'df_deadline':
                        $order_by .= ', TRIM(title) ASC, TRIM(sponsor_name) ASC';
                        break;
                    case 'dl_deadline':
                        $order_by .= ', TRIM(title) ASC, TRIM(sponsor_name) ASC';
                        break;
                    case 'amount_min':
                        $order_by .= ', amount_max ' . $sort_dir['current'] . ', TRIM(title) ASC, TRIM(sponsor_name) ASC';
                        break;
                    case 'amount_max':
                        $order_by .= ', amount_min ' . $sort_dir['current'] . ', TRIM(title) ASC, TRIM(sponsor_name) ASC';
                        break;
                }

                $out_result = self::get_grant_sponsor_info( $out_result, $order_by, 0, $search_mode );
                    
            }
        }
        
        $out_result = self::add_all_deadlines( $out_result );
    
        if (isset($_GET['print'])){
            if ($_GET['print'] == "results" || $_GET['print'] == "editor" ){
                $html = self::print_result($out_result, $search_mode);
                echo json_encode(['success'=>true, 'html'=>$html]);
                exit;
            }
        }
        if (isset($_REQUEST['sharing'])){
            if ($_REQUEST['sharing'] == "results" || $_REQUEST['sharing'] == "editor" ){
                $html = self::email_result($out_result, $search_mode, 100);
                $to = $_REQUEST['to'];
                $subject = 'Results from grant search';
                $body = "";
                $body .= '<div style="font-size:18px;font-family:Helvetica, Arial, sans-serif;color:#333333;">';
                
                if ($_REQUEST['sharing_content'] != ""){
                    $sc = nl2br($_REQUEST['sharing_content']);
                    $body .= $sc . "<br><br>" . $html;
                }else{
                    $body .= $html;
                }
                $body .= '</div>';
                $content = "<html><head></head><body>" . $body . "</body></html>";
                $cu_display_name = "";
                $cu_email = "";
                $user_id = 0;
                if (is_user_logged_in()){
                    $current_user = wp_get_current_user();
                    $user_id = $current_user->ID;
                    $cu_display_name = $current_user->display_name;
                    $cu_email = $current_user->user_email;
                }else{
                    $user_id = $_SESSION['guest_user_id'];
                    $cu_display_name = "GrantSelect";
                    $admin_email = explode("@", get_bloginfo('admin_email'));
                    $cu_email = "noreply@" . $admin_email[1];

                }
                    
                $headers = array('Content-Type: text/html; charset=UTF-8','From: '.$cu_display_name.' <'.$cu_email.'>');
                wp_mail( $to, $subject, $body, $headers );
                do_action("gs_add_subscriber_log", $user_id, EMAILALERT_STATUS, "send search result with email alert.");
                echo json_encode(['success'=>true]);
                exit;
            }
        }
        if (isset($_GET['download'])){
            switch ($_GET['download']){
                case 'csv':
                    self::download_csv($out_result, $search_mode);
                    exit;
                    break;
                case 'xlsx':
                    self::download_xlsx($out_result, $search_mode);
                    exit;
                    break;
                case 'pdf':
                    self::download_pdf($out_result, $search_mode);
                    exit;
                    break;
                default:
                    self::download_txt($out_result, $search_mode);
                    exit;
                    break;
            }

        }

        $size_of_result = sizeof($out_result);
        $show_from = $num_of_page * $perpage - $perpage + 1;
        $show_to   = $num_of_page * $perpage;
        if ($show_to > (int)$size_of_result) {
            $show_to = $size_of_result;
        }
        $offset = ( $num_of_page - 1 ) * $perpage;

        $out_result = array_slice($out_result, $offset, $perpage);

        $page_menu = self::paginate( $entry_id, $perpage, $num_of_page, (int)$size_of_result, '<span>&#171;</span>', '<span>&#187;</span>', $search_type, $search_mode );
        if ( $search_mode == 'editor' ) {
            return  self::generate_content_editor($out_result, $page_menu, $num_of_page, $size_of_result, $show_from, $show_to, $sort_dir, $entry_id, $search_type, $table_head_class);
        } else {
            return  self::generate_content($out_result, $page_menu, $num_of_page, $size_of_result, $show_from, $show_to, $sort_dir, $entry_id, $search_type, $table_head_class);
        }

    }
    
    public function save_search_result(){
        global $wpdb;
        $ss_result_id = 0;
        try{

            $entry_id = $_GET['sid'];
        
            $entry = GFAPI::get_entry( $entry_id );
            $created_by = 'xXxXx';
            if ( !is_wp_error($entry) ) {
                $created_by = $entry['created_by'];
            }

            $search_type = '';
            switch ( $entry['form_id'] ) {
                case '1':   //access quick search
                    $search_type = 'quick';
                    break;
                case '2':   //access advanced search
                    $search_type = 'advanced';
                    break;
                case '6':   //editor quick search
                    $search_type = 'quick';
                    break;
                case '7':   //editor title search
                    $search_type = 'title';
                    break;
                case '8':   //editor sponsor search
                    $search_type = 'sponsor';
                    break;
            }

            switch ( $entry['form_id'] ) {
                case '7':   //editor title search
                    $title_search_param = $entry[9];
                    $id_search_param = $entry[10];
                    break;
                case '8':   //editor sponsor search
                    $sponsor_search_param = $entry[8];
                    break;
                case '2':   //access advanced search
                    $title_search_param       = $entry[9];
                    $sponsor_search_param     = $entry[8];
                    $description_search_param = $entry[10];
                    break;
            }
            
        
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

            switch ( $search_type ) {
                case 'advanced':
                    if ( !empty( $entry['1'] ) ) {    // field: keywords
                        $out_result = self::search_by_keyword( $entry['1'] );
                    }else{
                        if (isset($_GET['search']) && $_GET['search'] != ""){
                            $out_result = self::search_by_keyword( $_GET['search'] );
                        }
                    }
                    if ( !empty( $entry_checkbox_selections[2] ) ) {   // field: geolocation-domestic
                        if ( !empty( $out_result ) ) {
                            $out_result = self::get_all_res( $out_result, self::search_by_geo_location( $entry_checkbox_selections[2] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_geo_location( $entry_checkbox_selections[2] );
                        }
                    }
                    if ( !empty( $entry_checkbox_selections[3] ) ) {   // field: geolocation-foreign
                        if ( !empty( $out_result ) ) {
                            $out_result = self::get_all_res( $out_result, self::search_by_geo_location( $entry_checkbox_selections[3] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_geo_location( $entry_checkbox_selections[3] );
                        }
                    }
                    if (!empty( $entry_checkbox_selections[5] )) {  // field: subject headings
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_subject_title( $entry_checkbox_selections[5] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_subject_title( $entry_checkbox_selections[5] );
                        }
                    }
                    if (!empty( $entry[6] )) {  // field: program type
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_program_type( $entry[6] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_program_type( $entry[6] );
                        }
                    }
                    if (!empty( $entry[7] )) {  // field: sponsor type
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_sponsor_type( $entry[7] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_sponsor_type( $entry[7] );
                        }
                    }
                    if (!empty( $sponsor_search_param )) {  // field: sponsor name
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_sponsor_name( $sponsor_search_param ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_sponsor_name( $sponsor_search_param );
                        }
                    }
                    if (!empty( $title_search_param )) {  // field: title of grant
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_title( $title_search_param ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_title( $title_search_param );
                        }
                    }
                    if (!empty( $description_search_param )) {  // field: description
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_description( $description_search_param ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_description( $description_search_param );
                        }
                    }
                    if (!empty( $entry[11] )) {  // field: requirements
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_requirements( $entry[11] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_requirements( $entry[11] );
                        }
                    }
                    if ( $entry_checkbox_selections[16] == TRUE) { // fields: deadline
                        $start_month = $entry[12];
                        $start_date  = $entry[13];
                        $end_month   = $entry[14];
                        $end_date    = $entry[15];
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res($out_result, self::search_by_deadlines($start_month, $start_date, $end_month, $end_date));
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_deadlines($start_month, $start_date, $end_month, $end_date);
                        }
                    }
                    if (!empty( $entry[17] )) {  // field: restrictions
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_restrictions( $entry[17] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_restrictions( $entry[17] );
                        }
                    }
                    if ( !empty( $id_search_param ) ) {    // field: id
                        $out_result[] = $id_search_param;
                    }
                    break;
                case 'sponsor':
                    if (!empty( $sponsor_search_param )) {  // field: sponsor name
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_sponsor_name( $sponsor_search_param, 'editor' ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_sponsor_name( $sponsor_search_param, 'editor' );
                        }
                    }
                    break;
                case 'title':
                    if (!empty( $title_search_param )) {  // field: title of grant
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_title( $title_search_param ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_title( $title_search_param );
                        }
                    }
                    if ( !empty( $id_search_param ) ) {    // field: id
                        $out_result[] = $id_search_param;
                    }
                    break;
                case 'quick':
                    if ( !empty( $entry['1'] ) ) {    // field: keywords
                        $out_result = self::search_by_keyword( $entry['1'] );
                    }else{
                        if (isset($_GET['search']) && $_GET['search'] != ""){
                            $out_result = self::search_by_keyword( $_GET['search'] );
                        }
                    }
                    if ( !empty( $entry_checkbox_selections[2] ) ) {   // field: geolocation-domestic
                        if ( !empty( $out_result ) ) {
                            $out_result = self::get_all_res( $out_result, self::search_by_geo_location( $entry_checkbox_selections[2] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_geo_location( $entry_checkbox_selections[2] );
                        }
                    }
                    if (!empty( $entry_checkbox_selections[3] )) {  // field: subject headings
                        if (!empty($out_result)) {
                            $out_result = self::get_all_res( $out_result, self::search_by_subject_title( $entry_checkbox_selections[3] ) );
                        }
                        else if (empty($out_result)) {
                            $out_result = self::search_by_subject_title( $entry_checkbox_selections[3] );
                        }
                    }
                    break;
            }

            if ($out_result[0] != '') {
                $table_head_class = array(  //initialize
                    'id' => '',
                    'gt' => '',
                    'sp' => '',
                    'dl' => '',
                    'am' => '',
                    'ud' => '',
                    'ca' => ''
                );

                //sort_by
                if ( empty($_GET['sd']) ) {
                    $sort_dir['current'] = 'ASC';
                    $sort_dir['id']      = 'ASC';
                    $sort_dir['gt']      = 'ASC';
                    $sort_dir['sp']      = 'ASC';
                    $sort_dir['dl']      = 'ASC';
                    $sort_dir['am']      = 'ASC';
                    $sort_dir['ud']      = 'ASC';
                    $sort_dir['ca']      = 'ASC';
                    $table_head_class[$sort_by_raw] .= ' up';
                } else {
                    $sort_dir['current'] = filter_var ( $_GET['sd'], FILTER_SANITIZE_STRIPPED );
                    $sort_dir['id']      = $sort_dir['current'];
                    $sort_dir['gt']      = $sort_dir['current'];
                    $sort_dir['sp']      = $sort_dir['current'];
                    $sort_dir['dl']      = $sort_dir['current'];
                    $sort_dir['am']      = $sort_dir['current'];
                    $sort_dir['ud']      = $sort_dir['current'];
                    $sort_dir['ca']      = $sort_dir['current'];
                };

                if ( empty($_GET['sb']) ) {
                    $sort_by     = 'gs.updated_at';
                    $sort_by_raw = 'ud';
                    $table_head_class['ud'] = 'sorted-by';
                } else {
                    switch ($_GET['sb']) {
                        case 'id':
                            $sort_by     = 'gs.id';
                            $sort_by_raw = 'id';
                            $table_head_class['id'] = 'sorted-by';
                            break;
                        case 'gt':
                            $sort_by     = 'title';
                            $sort_by_raw = 'gt';
                            $table_head_class['gt'] = 'sorted-by';
                            break;
                        case 'sp':
                            $sort_by     = 'sponsor_name';
                            $sort_by_raw = 'sp';
                            $table_head_class['sp'] = 'sorted-by';
                            break;
                        case 'dl':
                            if ($sort_dir['current'] == 'DESC') {
                                $sort_by     = 'dl_deadline';
                            } else {
                                $sort_by     = 'df_deadline';
                            }
                            $sort_by_raw = 'dl';
                            $table_head_class['dl'] = 'sorted-by';
                            break;
                        case 'am':
                            if ($sort_dir['current'] == 'DESC') {
                                $sort_by     = 'amount_max';
                            } else {
                                $sort_by     = 'amount_min';
                            }
                            $sort_by_raw = 'am';
                            $table_head_class['am'] = 'sorted-by';
                            break;
                        case 'ud':
                            $sort_by     = 'gs.updated_at';
                            $sort_by_raw = 'ud';
                            $table_head_class['ud'] = 'sorted-by';
                            break;
                        case 'ca':
                            $sort_by     = 'gs.created_at';
                            $sort_by_raw = 'ca';
                            $table_head_class['ca'] = 'sorted-by';
                            break;
                    }
                };

                if ( $sort_dir['current'] == 'ASC' ) {
                    $sort_dir[$sort_by_raw] = 'DESC';
                    $table_head_class[$sort_by_raw] .= ' up';
                } else {
                    $sort_dir[$sort_by_raw] = 'ASC';
                    $table_head_class[$sort_by_raw] .= ' down';
                }

                if ($sort_by == 'title' || $sort_by == 'sponsor_name') {
                    $order_by = 'ORDER BY TRIM(' . $sort_by . ') ' . $sort_dir['current'];
                } else {
                    $order_by = 'ORDER BY ' . $sort_by . ' ' . $sort_dir['current'];
                }
                switch ($sort_by) {
                    case 'title':
                        $order_by .= ', TRIM(sponsor_name) ASC';
                        break;
                    case 'sponsor_name':
                        $order_by .= ', TRIM(title) ASC';
                        break;
                    case 'df_deadline':
                        $order_by .= ', TRIM(title) ASC, TRIM(sponsor_name) ASC';
                        break;
                    case 'dl_deadline':
                        $order_by .= ', TRIM(title) ASC, TRIM(sponsor_name) ASC';
                        break;
                    case 'amount_min':
                        $order_by .= ', amount_max ' . $sort_dir['current'] . ', TRIM(title) ASC, TRIM(sponsor_name) ASC';
                        break;
                    case 'amount_max':
                        $order_by .= ', amount_min ' . $sort_dir['current'] . ', TRIM(title) ASC, TRIM(sponsor_name) ASC';
                        break;
                }

                $out_result = self::get_grant_sponsor_info( $out_result, $order_by, 0, $search_mode );

                $out_result = self::add_all_deadlines( $out_result );
                if (isset($_GET['sid'])){
                    $wpdb->insert(
                        $wpdb->prefix . "gs_save_searchresult",
                        [
                            'user_id'       => get_current_user_id(),
                            'entry_id'     => absint($_GET['sid']),
                            'search_title'  => $_GET['search_title'],
                            'type'          => $_GET['type'],
                            'is_agent'      => $_GET['is_agent'],
                            'created_at'    => date("Y-m-d H:i:s")
                        ]
                    );
                    $ss_result_id = $wpdb->insert_id;
                    //echo $wpdb->last_query;
                    if (!isset($_GET['is_agent']) || $_GET['is_agent'] == 0){
                        foreach ($out_result as $grant){
                            $wpdb->insert(
                                $wpdb->prefix . "gs_save_searchresult_ids",
                                [
                                    "ss_id"     => $ss_result_id,
                                    "grant_id"  => $grant['id']
                                ]
                            );
                        }
                    }
                    echo json_encode(['success'=>true]);
                }else{
                    echo json_encode(['success'=>false]);
                }
            }else{
                echo json_encode(['success'=>false]);
            }
        }catch(Exception $e){
            echo json_encode(['success'=>false]);
        }
        exit(0);
    }
    /**
     * Function download csv.
     * @params $result of query, $filename, $delimiter
     */
    public function download_csv($result, $search_mode = "access", $filename = "result.csv", $delimiter=","){
        ob_end_clean();
        
        // tell the browser it's going to be a csv file
        header('Content-Type: application/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachment; filename="'.$filename.'";');

        $f = fopen('php://output', 'w'); 
        
        switch ($search_mode){
            case 'access':
                foreach ($result as $key => $r){
                    if ($key == 0){
                        fputcsv($f, array("Grant Link", "Grant Title", "Description", "Sponsor", "Deadlines", "Amount"), $delimiter);
                    }
                    $value = json_decode(json_encode($r), true);
                    $description = explode(' ', stripslashes($value['description']));
                    if (count($description) > 30) {
                        $temp = '';
                        for ($i = 0; $i < 30; $i++) {
                            $temp .= $description[$i] . ' ';
                        }
                        $value['description'] = $temp . '...';
                    }
                    $row = [];
                    //$row[0] = '<a class="listing" href="' . self::get_link(false) . '/access/grant-details/?gid=' . $value['id'] . '">' . $value['title'] . '</a>';
                    $row[0] = self::get_link(false) . '/access/grant-details/?gid=' . $value['id'];
                    $row[1] = stripslashes($value['title']);
                    $row[2] = stripslashes($value['description']);
                    $row[3] = stripslashes($value['sponsor_name']);
                    if ($value['deadline'] == '') {
                        $row[4] = '(not specified)';
                    }
                    else if ($value['deadline'] != '') {
                        $deadline = $value['deadline'];
                        $deadline = self::represent_deadline($deadline);
                        $row[4] = $deadline;
                    }
                    if ($value['amount_min'] == 0 and $value['amount_max'] > 0) {
                        $row[5] = 'Up to ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'];
                    }
                    else if ($value['amount_min'] > 0 and $value['amount_max'] == 0) {
                        $row[5] = number_format($value['amount_min']) . ' - (not specified) ' . $value['amount_currency'];
                    }
                    else if ($value['amount_min'] == 0 and $value['amount_max'] == 0) {
                        $row[5] = '(not specified)';
                    }
                    else {
                        $row[5] = number_format($value['amount_min']) . ' - ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'];
                    }
            
                    fputcsv($f, $row, $delimiter);
                }
                break;
            case 'editor':
                foreach ($result as $key => $r){
                    if ($key == 0){
                        fputcsv($f, array("ID", "Link", "Title", "Sponsor", "Updated", "Created"), $delimiter);
                    }
                    $value = json_decode(json_encode($r), true);
                    $row = [];
                    $row[0] = $value['id'];
                    $row[1] = self::get_link(false) . '/editor/records/view/?gid=' . $value['id'];
                    $row[2] = stripslashes($value['title']);
                    $row[3] = stripslashes($value['sponsor_name']);
                    $row[4] = $value['updated_at'];
                    $row[5] = $value['created_at'];
                    fputcsv($f, $row, $delimiter);
                }
                break;
            default:
                foreach ($result as $key => $r){
                    if ($key == 0){
                        fputcsv($f, array_keys($r), $delimiter);
                    }
                    
                    // generate csv lines from the inner arrays
                    fputcsv($f, json_decode(json_encode($r), true), $delimiter);
                }
                break;
        }
        
        fclose( $f );
        // flush buffer
        ob_flush();
        exit();
    }

    /**
     * Function download text file.
     * @params $result of query, $filename
     */
    public function download_txt($result, $search_mode = "access", $filename = "result.txt"){
        ob_end_clean();
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        
        $data = [];
        switch ($search_mode){
            case 'access':
                foreach ($result as $key => $r){
                    if ($key == 0){
                        echo implode("\t", array("Grant Link", "Grant Title", "Description", "Sponsor", "Deadlines", "Amount")) . "\r\n";
                    }
                    $value = json_decode(json_encode($r), true);
                    $description = explode(' ', stripslashes($value['description']));
                    if (count($description) > 30) {
                        $temp = '';
                        for ($i = 0; $i < 30; $i++) {
                            $temp .= $description[$i] . ' ';
                        }
                        $value['description'] = $temp . '...';
                    }
                    $row = [];
                    //$row[0] = '<a class="listing" href="' . self::get_link(false) . '/access/grant-details/?gid=' . $value['id'] . '">' . $value['title'] . '</a>';
                    $row[0] = self::get_link(false) . '/access/grant-details/?gid=' . $value['id'];
                    $row[1] = stripslashes($value['title']);
                    $row[2] = stripslashes($value['description']);
                    $row[3] = stripslashes($value['sponsor_name']);
                    if ($value['deadline'] == '') {
                        $row[4] = '(not specified)';
                    }
                    else if ($value['deadline'] != '') {
                        $deadline = $value['deadline'];
                        $deadline = self::represent_deadline($deadline);
                        $row[4] = $deadline;
                    }
                    if ($value['amount_min'] == 0 and $value['amount_max'] > 0) {
                        $row[5] = 'Up to ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'];
                    }
                    else if ($value['amount_min'] > 0 and $value['amount_max'] == 0) {
                        $row[5] = number_format($value['amount_min']) . ' - (not specified) ' . $value['amount_currency'];
                    }
                    else if ($value['amount_min'] == 0 and $value['amount_max'] == 0) {
                        $row[5] = '(not specified)';
                    }
                    else {
                        $row[5] = number_format($value['amount_min']) . ' - ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'];
                    }

                    array_walk($row, __NAMESPACE__ . '\cleanData');
                    echo implode("\t", array_values($row)) . "\r\n";
                    
                }
                break;
            case 'editor':
                foreach ($result as $key => $r){
                    if ($key == 0){
                        echo implode("\t", array("ID", "Link", "Title", "Sponsor", "Updated", "Created")) . "\r\n";
                    }
                    $value = json_decode(json_encode($r), true);
                    $row = [];
                    $row[0] = $value['id'];
                    $row[1] = self::get_link(false) . '/editor/records/view/?gid=' . $value['id'];
                    $row[2] = stripslashes($value['title']);
                    $row[3] = stripslashes($value['sponsor_name']);
                    $row[4] = $value['updated_at'];
                    $row[5] = $value['created_at'];
                    array_walk($row, __NAMESPACE__ . '\cleanData');
                    echo implode("\t", array_values($row)) . "\r\n";
                    
                }
                break;
            default:
                foreach ($result as $key => $r){
                    if ($key == 0){
                        echo implode("\t", array_keys($r)) . "\r\n";
                    }
                    $row = json_decode(json_encode($r), true);
                    array_walk($row, __NAMESPACE__ . '\cleanData');
                    echo implode("\t", array_values($row)) . "\r\n";
                    
                }
                break;
        }
        exit;
    }

    /**
     * Function download pdf.
     * @params $result of query, $filename
     */
    public function download_pdf($result, $search_mode = "access", $filename = "result.pdf"){
        ob_end_clean();
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $html = '<html><head></head><body>';
        $table = '<table border="1">';
        $odd_even = 0;
        $content = "";
        switch ($search_mode){
            case 'access':
                foreach ($result as $key => $r){
                    if ($key == 0){
                        $table .= '<tr>';
                        $keys = array("Grant Title", "Sponsor", "Deadlines", "Amount");
                        foreach ($keys as $header){
                            $table .= '<td>' . $header .'</td>';
                        }
                        $table .= '</tr>';
                    }
                    
                    $value = json_decode(json_encode($r), true);
                    
                    if ($odd_even % 2 != 0) {
                        $content .= '<tr class="odd">';
                    }
                    else {
                        $content .= '<tr>';
                    }
                    $description = explode(' ', stripslashes($value['description']));
                    if (count($description) > 30) {
                        $temp = '';
                        for ($i = 0; $i < 30; $i++) {
                            $temp .= $description[$i] . ' ';
                        }
                        $value['description'] = $temp . '...';
                    }
                    $content .= '<td><a class="listing" href="' . self::get_link(false) . '/access/grant-details/?gid=' . $value['id'] . '"><strong>' . $value['title'] . '</strong></a><br />' . $value['description'] . '</td>';
                    $content .= '<td>' . $value['sponsor_name'] . '</td>';
                    if ($value['deadline'] == '') {
                        $content .= '<td>(not specified)</td>';
                    }
                    else if ($value['deadline'] != '') {
                        $deadline = $value['deadline'];
                        $deadline = self::represent_deadline($deadline);
                        $content .= '<td>' . $deadline . '</td>';
                    }
                    if ($value['amount_min'] == 0 and $value['amount_max'] > 0) {
                        $content .= '<td>Up to ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'] . '</td>';
                    }
                    else if ($value['amount_min'] > 0 and $value['amount_max'] == 0) {
                        $content .= '<td>' . number_format($value['amount_min']) . ' - (not specified) ' . $value['amount_currency'] . '</td>';
                    }
                    else if ($value['amount_min'] == 0 and $value['amount_max'] == 0) {
                        $content .= '<td>(not specified)</td>';
                    }
                    else {
                        $content .= '<td>' . number_format($value['amount_min']) . ' - ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'] . '</td>';
                    }
                    $content .= '</tr>';
                    $odd_even++;


                    
                }
                break;
            case 'editor':
                foreach ($result as $key => $r){
                    if ($key == 0){
                        $table .= '<tr>';
                        $keys = array("ID", "Title", "Sponsor", "Updated", "Created");
                        foreach ($keys as $header){
                            $table .= '<td>' . $header .'</td>';
                        }
                        $table .= '</tr>';
                    }
                    
                    $value = json_decode(json_encode($r), true);
                    $content .= '<tr>';
                    $content .= '<td>' . $value['id'] . '</td>';
                    $content .= '<td>' . '<a href="'. self::get_link(false) . '/editor/records/view/?gid=' . $value['id'] . '">' . stripslashes($value['title']) . '</a>' . '</td>';
                    $content .= '<td>' . stripslashes($value['sponsor_name']) . '</td>';
                    $content .= '<td>' . $value['updated_at'] . '</td>';
                    $content .= '<td>' . $value['created_at'] . '</td>';
                    $content .= '</tr>';
                }
                break;
        }
        $table .= $content;
        $table .= '</table>';
        $html .= $table;
        $html .= '</body>';
        $html .= '</html>';
        
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser
        $dompdf->stream();
        exit;
    }

    /**
     * Function download xlsx.
     * @params $result of query, $filename, $delimiter
     */
    public function download_xlsx($result, $search_mode = "access", $filename = "result.xlsx"){
        ob_end_clean();
        include_once(plugin_dir_path(__FILE__) ."/lib/xlsx/xlsxwriter.class.php");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
       
        $data = [];
        switch ($search_mode){
            case 'access':
                foreach ($result as $key => $r){
                    if ($key == 0){
                        array_push($data, array("Grant Link", "Grant Title", "Description", "Sponsor", "Deadlines", "Amount"));
                    }
                    $value = json_decode(json_encode($r), true);
                    $description = explode(' ', stripslashes($value['description']));
                    if (count($description) > 30) {
                        $temp = '';
                        for ($i = 0; $i < 30; $i++) {
                            $temp .= $description[$i] . ' ';
                        }
                        $value['description'] = $temp . '...';
                    }
                    $row = [];
                    $row[0] = self::get_link(false) . '/access/grant-details/?gid=' . $value['id'];
                    $row[1] = stripslashes($value['title']);
                    $row[2] = stripslashes($value['description']);
                    $row[3] = stripslashes($value['sponsor_name']);
                    if ($value['deadline'] == '') {
                        $row[4] = '(not specified)';
                    }
                    else if ($value['deadline'] != '') {
                        $deadline = $value['deadline'];
                        $deadline = self::represent_deadline($deadline);
                        $row[4] = $deadline;
                    }
                    if ($value['amount_min'] == 0 and $value['amount_max'] > 0) {
                        $row[5] = 'Up to ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'];
                    }
                    else if ($value['amount_min'] > 0 and $value['amount_max'] == 0) {
                        $row[5] = number_format($value['amount_min']) . ' - (not specified) ' . $value['amount_currency'];
                    }
                    else if ($value['amount_min'] == 0 and $value['amount_max'] == 0) {
                        $row[5] = '(not specified)';
                    }
                    else {
                        $row[5] = number_format($value['amount_min']) . ' - ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'];
                    }
                    array_push($data, $row);
                }
                
                break;
            case 'editor':
                foreach ($result as $key => $r){
                    if ($key == 0){
                        array_push($data, array("ID", "Link", "Title", "Sponsor", "Updated", "Created"));
                    }
                    $value = json_decode(json_encode($r), true);
                    $row = [];
                    $row[0] = $value['id'];
                    $row[1] = self::get_link(false) . '/editor/records/view/?gid=' . $value['id'];
                    $row[2] = stripslashes($value['title']);
                    $row[3] = stripslashes($value['sponsor_name']);
                    $row[4] = $value['updated_at'];
                    $row[5] = $value['created_at'];
                    array_push($data, $row);
                }
                break;
            default:
                foreach ($result as $key => $r){
                    if ($key == 0){
                        array_push($data, array_keys($r));
                    }
                    array_push($data, json_decode(json_encode($r), true));
                    
                }
                break;
        }
        $writer = new XLSXWriter();
        $writer->writeSheet($data,'Sheet1');
        $writer->writeToStdOut();

        exit;
    }
    
    /**
     * Function print html.
     * @params $result of query, $filename
     */
    public function print_result($result, $search_mode = "access", $limit = 0){
        ob_end_clean();
        $html = '';
        $table = '<table border="1">';
        $odd_even = 0;
        $content = "";
        switch ($search_mode){
            case 'access':
                foreach ($result as $key => $r){
                    if ($limit != 0 && $limit <= $key){
                        break;
                    }
                    if ($key == 0){
                        $table .= '<tr>';
                        $keys = array("GRANT TITLE", "SPONSOR", "DEADLINES", "AMOUNT");
                        foreach ($keys as $header){
                            $table .= '<td>' . $header .'</td>';
                        }
                        $table .= '</tr>';
                    }
                    
                    $value = json_decode(json_encode($r), true);
                    
                    if ($odd_even % 2 != 0) {
                        $content .= '<tr class="odd">';
                    }
                    else {
                        $content .= '<tr>';
                    }
                    $description = explode(' ', stripslashes($value['description']));
                    if (count($description) > 30) {
                        $temp = '';
                        for ($i = 0; $i < 30; $i++) {
                            $temp .= $description[$i] . ' ';
                        }
                        $value['description'] = $temp . '...';
                    }
                    $content .= '<td><a class="listing" href="' . self::get_link(false) . '/access/grant-details/?gid=' . $value['id'] . '"><strong>' . stripslashes($value['title']) . '</strong></a><br />' . stripslashes($value['description']) . '</td>';
                    $content .= '<td>' . stripslashes($value['sponsor_name']) . '</td>';
                    if ($value['deadline'] == '') {
                        $content .= '<td>(not specified)</td>';
                    }
                    else if ($value['deadline'] != '') {
                        $deadline = $value['deadline'];
                        $deadline = self::represent_deadline($deadline);
                        $content .= '<td>' . $deadline . '</td>';
                    }
                    if ($value['amount_min'] == 0 and $value['amount_max'] > 0) {
                        $content .= '<td>Up to ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'] . '</td>';
                    }
                    else if ($value['amount_min'] > 0 and $value['amount_max'] == 0) {
                        $content .= '<td>' . number_format($value['amount_min']) . ' - (not specified) ' . $value['amount_currency'] . '</td>';
                    }
                    else if ($value['amount_min'] == 0 and $value['amount_max'] == 0) {
                        $content .= '<td>(not specified)</td>';
                    }
                    else {
                        $content .= '<td>' . number_format($value['amount_min']) . ' - ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'] . '</td>';
                    }
                    $content .= '</tr>';
                    $odd_even++;
                    
                }
                break;
            case 'editor':
                foreach ($result as $key => $r){
                    if ($limit != 0 && $limit <= $key){
                        break;
                    }
                    if ($key == 0){
                        $table .= '<tr>';
                        $keys = array("ID", "TITLE", "SPONSOR", "UPDATED", "CREATED");
                        foreach ($keys as $header){
                            $table .= '<td>' . $header .'</td>';
                        }
                        $table .= '</tr>';
                    }
                    
                    $value = json_decode(json_encode($r), true);
                    $content .= '<tr>';
                    $content .= '<td>' . $value['id'] . '</td>';
                    $content .= '<td>' . '<a href="'. self::get_link(false) . '/editor/records/view/?gid=' . $value['id'] . '">' . stripslashes($value['title']) . '</a>' . '</td>';
                    $content .= '<td>' . stripslashes($value['sponsor_name']) . '</td>';
                    $content .= '<td>' . stripslashes($value['updated_at']) . '</td>';
                    $content .= '<td>' . $value['created_at'] . '</td>';
                    $content .= '</tr>';
                    
                }
                break;
        }
        $table .= $content;
        $table .= '</table>';
        $html .= $table;
        return $html;
    }
    /**
     * Function email html.
     * @params $result of query, $filename
     */
    public function email_result($result, $search_mode = "access", $limit = 0){
        ob_end_clean();
        $html = '';
        $table = '<table style="box-shadow: 0 15px 26px -10px rgb(0 0 0 / 20%);text-align: left;border-collapse: collapse;border-spacing: 0;line-height: 2;width: 100%;margin-bottom: 36px;border: 1px solid #e6e6e6;">';
        $odd_even = 0;
        $content = "";
        switch ($search_mode){
            case 'access':
                foreach ($result as $key => $r){
                    if ($limit != 0 && $limit <= $key){
                        break;
                    }
                    if ($key == 0){
                        $table .= '<tr>';
                        $keys = array("GRANT TITLE", "SPONSOR", "DEADLINES", "AMOUNT");
                        foreach ($keys as $k=>$header){
                            $table .= '<th style="'.($k==0?'width: 46%;':'').'padding: 11px 0px 11px 20px;font-weight: 700;text-transform: uppercase;font-size: 14px;line-height: 1.2;background-color: #e5eaf6;">' . $header .'</th>';
                        }
                        $table .= '</tr>';
                    }
                    
                    $value = json_decode(json_encode($r), true);
                    
                    if ($odd_even % 2 != 0) {
                        $content .= '<tr class="odd">';
                    }
                    else {
                        $content .= '<tr>';
                    }
                    $description = explode(' ', stripslashes($value['description']));
                    if (count($description) > 30) {
                        $temp = '';
                        for ($i = 0; $i < 30; $i++) {
                            $temp .= $description[$i] . ' ';
                        }
                        $value['description'] = $temp . '...';
                    }
                    $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;"><a class="listing" href="' . self::get_link(false) . '/access/grant-details/?gid=' . $value['id'] . '"><strong>' . stripslashes($value['title']) . '</strong></a><br />' . stripslashes($value['description']) . '</td>';
                    $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">' . stripslashes($value['sponsor_name']) . '</td>';
                    if ($value['deadline'] == '') {
                        $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">(not specified)</td>';
                    }
                    else if ($value['deadline'] != '') {
                        $deadline = $value['deadline'];
                        $deadline = self::represent_deadline($deadline);
                        $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">' . $deadline . '</td>';
                    }
                    if ($value['amount_min'] == 0 and $value['amount_max'] > 0) {
                        $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">Up to ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'] . '</td>';
                    }
                    else if ($value['amount_min'] > 0 and $value['amount_max'] == 0) {
                        $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">' . number_format($value['amount_min']) . ' - (not specified) ' . $value['amount_currency'] . '</td>';
                    }
                    else if ($value['amount_min'] == 0 and $value['amount_max'] == 0) {
                        $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">(not specified)</td>';
                    }
                    else {
                        $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">' . number_format($value['amount_min']) . ' - ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'] . '</td>';
                    }
                    $content .= '</tr>';
                    $odd_even++;
                    

                    
                }
                break;
            case 'editor':
                foreach ($result as $key => $r){
                    if ($limit != 0 && $limit >= $key){
                        break;
                    }
                    if ($key == 0){
                        $table .= '<tr>';
                        $keys = array("ID", "TITLE", "SPONSOR", "UPDATED", "CREATED");
                        foreach ($keys as $k=>$header){
                            $table .= '<th style="'.($k==0?'width: 46%;':'').'padding: 11px 0px 11px 20px;font-weight: 700;text-transform: uppercase;font-size: 14px;line-height: 1.2;background-color: #e5eaf6;">' . $header .'</th>';
                        }
                        $table .= '</tr>';
                    }
                    
                    $value = json_decode(json_encode($r), true);
                    $content .= '<tr>';
                    $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">' . $value['id'] . '</td>';
                    $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">' . '<a href="'. self::get_link(false) . '/editor/records/view/?gid=' . $value['id'] . '">' . stripslashes($value['title']) . '</a>' . '</td>';
                    $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">' . stripslashes($value['sponsor_name']) . '</td>';
                    $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">' . $value['updated_at'] . '</td>';
                    $content .= '<td style="padding: 28px 20px;}table td {line-height: 1.5;border-bottom: 1px solid #e1e9fd;border-top: 0;">' . $value['created_at'] . '</td>';
                    $content .= '</tr>';
                    
                }
                break;
        }
        $table .= $content;
        $table .= '</table>';
        $html .= $table;
        return $html;
    }
    /**
     * Function doPaginate. Paginate result of search(DO PAGINATE MENU)
     * @params $items_per_page, $page, $total_items
     */
    function paginate ( $search_id, $items_per_page, $page, $total_items, $prev_page_pin, $next_page_pin, $search_type='advanced', $search_mode='access' )
    {
        $normal_page_style = 'page_link_btn';
        $next_page_style   = 'page_link_btn';
        $prev_page_style   = 'page_link_btn';
        $curr_page_style   = 'page_link_btn selected';

        switch ($search_mode) {
            case 'access':
                if ($search_type == 'advanced') {
                    $result_page = 'search-results/?sid=' . $search_id;
                    if (isset($_GET['saved'])){
                        $result_page .= '&saved=true';
                    }
                }
                else if ($search_type == 'quick') {
                    //$result_page = 'quick-search-results/?sid=' . $search_id;
                    $result_page = 'search-results/?sid=' . $search_id;
                    if (isset($_GET['saved'])){
                        $result_page .= '&saved=true';
                    }
                }
                break;
            case 'editor':
                $result_page = 'search/results/?sid=' . $search_id;
                if (isset($_GET['saved'])){
                    $result_page .= '&saved=true';
                }
                break;
        }

        $query_string = '';
        if (!empty($_GET['sb'])) {
            $query_string .= '&sb=' . $_GET['sb'];
        }
        if (!empty($_GET['sd'])) {
            $query_string .= '&sd=' . $_GET['sd'];
        }

        $total = intval(($total_items - 1) / $items_per_page) + 1;
        if ($total > 5) {
            $last_page = '...' . '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . $total . '" class="' . $normal_page_style . '">' . $total . '</a>';
        }
        else {
            $last_page = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . $total . '" class="' . $normal_page_style . '">' . $total . '</a>';
        }
        if ($page + 2 >= $total) {
            $last_page = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . $total . '" class="' . $normal_page_style . '">' . $total . '</a>';
        }
        $first_page = '';
        $page = intval($page);
        if (empty($page) or $page < 0) {
            $page = 1;
        }
        if ($page >= $total) {
            $page = $total;
            $last_page = '';
        }
        if ($page - 3 > 1) {
            $first_page = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . 1 . '" class="' . $normal_page_style . '">' . 1 . '</a>' . '...';
        }
        if ($page - 3 == 0 or $page - 3 == 1) {
            $first_page = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . 1 . '" class="' . $normal_page_style . '">' . 1 . '</a>';
        }
        if ($page != 1) {
            $prev_page = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . ($page - 1) . '" class="' . $prev_page_style . '">' . $prev_page_pin . '</a> ';
        }
        if ($page != $total) {
            $next_page = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . ($page + 1) .  '" class="' . $next_page_style . '">' . $next_page_pin . '</a> ';
        }
        if ($page - 3 == 1) {
            $page2left = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . ($page - 2) . '" class="' . $normal_page_style . '">' . ($page - 2) . '</a> ';
        }
        if ($page - 3 > 1) {
            $page2left = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . ($page - 3) . '" class="' . $normal_page_style . '">' . ($page - 3) . '</a> ';
        }
        if ($page - 2 > 2) {
            $page2left = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . ($page - 2) . '" class="' . $normal_page_style . '">' . ($page - 2) . '</a> ';
        }
        if ($page - 1 > 0) {
            $page1left = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . ($page - 1) . '" class="' . $normal_page_style . '">' . ($page - 1) . '</a>';
        }
        if ($page + 1 < $total) {
            $page1right = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . ($page + 1) . '" class="' . $normal_page_style . '">' . ($page + 1) .'</a>';
        }
        if ($page + 2 < $total) {
            $page2right = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . ($page + 2) . '" class="' . $normal_page_style . '">' . ($page + 2) .'</a>';
        }
        if ($page + 3 < $total and $page < 3) {
            $page3right = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . ($page + 3) . '" class="' . $normal_page_style . '">' . ($page + 3) .'</a>';
        }
        if ($page + 4 < $total and $page < 2) {
            $page4right = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . ($page + 4) . '" class="' . $normal_page_style . '">' . ($page + 4) .'</a>';
        }
        if ($page + 1 == $total) {
            $page1right = '';
        }
        $page = '<a href="/' . $search_mode . '/' . $result_page . $query_string . '&pn=' . $page . '" class="' . $curr_page_style . '">' . $page . '</a>';
        $paginate_menu = $prev_page . $first_page . $page2left . $page1left . $page . $page1right . $page2right . $page3right. $page4right . $last_page . $next_page;

        return $paginate_menu;
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
        $sql = null;
        if (isset($_GET['search']) && trim($_GET['search']) != ""){
            //var_dump($_GET['search']);exit;
            $sql_query = "SELECT  `id`, MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
            AGAINST (%s IN NATURAL LANGUAGE MODE) AS score
            FROM " . $wpdb->prefix . "gs_grants WHERE MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
            AGAINST (%s IN BOOLEAN MODE) AND MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
            AGAINST (%s IN BOOLEAN MODE) AND status='A' ORDER BY score DESC";

  //        echo "SQLQ: $sql_query<br>";

            $sql = $wpdb->prepare( $sql_query, $keyword_str, $keyword_str, $_GET['search'] );
        }else{
            $sql_query = "SELECT  `id`, MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
              AGAINST (%s IN NATURAL LANGUAGE MODE) AS score
              FROM " . $wpdb->prefix . "gs_grants WHERE MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
              AGAINST (%s IN BOOLEAN MODE) AND status='A' ORDER BY score DESC";

    //        echo "SQLQ: $sql_query<br>";

            $sql = $wpdb->prepare( $sql_query, $keyword_str, $keyword_str );
            
        }
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

//        echo "<pre>";   //debug
//        print_r($data);
//        echo "</pre>";

        $res = array_keys( $data );

//        echo "<pre>";   //debug
//        print_r($res);
//        echo "</pre>";

        return $res;
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
//        echo "SQLQ458: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

//        echo "<pre>";   //debug
//        print_r($data);
//        echo "</pre>";

        empty( $data['Domestic'] ) ? $domestic = 0 : $domestic = 1;
        empty( $data['Foreign'] ) ? $foreign = 0 : $foreign = 1;

//        echo "D:$domestic<br>"; //debug
//        echo "F:$foreign<br>";  //debug

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
                        WHERE geo_id IN($geo_ids) AND gg.status = 'A'";
//        echo "SQLQ458: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

//        echo "<pre>";   //debug
//        print_r($data);
//        echo "</pre>";

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
                        WHERE subject_id IN($subject_title_ids) AND gg.status = 'A'";
//        echo "SQLQ458: $sql_query<br>";


        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }


    /**
     * Function search_by_program_type
     * @params $program_id
     * @return $res
     */
    function search_by_program_type( $program_id )
    {
        global $wpdb;

        $sql_query = "SELECT grant_id FROM " . $wpdb->prefix . "gs_grant_program_mappings as gpm
                        JOIN " . $wpdb->prefix . "gs_grants as gg ON gpm.grant_id = gg.id
                        WHERE program_id = %s AND gg.status = 'A'";
//        echo "SQLQ458: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query, $program_id );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }


    /**
     * Function search_by_sponsor_type
     * @params $sponsor_type_id
     * @return $res
     */
    function search_by_sponsor_type( $sponsor_type_id )
    {
        global $wpdb;

        $sql_query = "SELECT grant_id FROM " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings WHERE sponsor_id IN
                      ( SELECT gp.id FROM " . $wpdb->prefix . "gs_grant_sponsors as gp WHERE gp.grant_sponsor_type_id=%s and gp.status='A' )";
//        echo "SQLQ458: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query, $sponsor_type_id );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }


    /**
     * Function search_by_sponsor_name
     * @params $sponsor_name
     * @return $res
     */
    function search_by_sponsor_name( $sponsor_name, $search_mode="access" )
    {
        global $wpdb;

        $sponsor_name = sanitize_text_field( $sponsor_name );
        $search_mode = sanitize_text_field( $search_mode );

        $keyword_str = '';
        // $keywords = explode(' ', $sponsor_name);
        // if (count($keywords) > 0) {
        //     foreach ($keywords as $key) {
        //         if (strlen($key) > 3) {
        //             $keyword_str .= '+' . $key . '*' . ' ';
        //         }
        //     }
        // }
        // else {
        //     $keyword_str = '+' . $keywords[0] . '*';
        // }
        

        // $sql_query = "SELECT `id`, MATCH (`sponsor_name`)
        //               AGAINST (%s IN NATURAL LANGUAGE MODE) AS score
        //               FROM " . $wpdb->prefix . "gs_grant_sponsors WHERE MATCH (`sponsor_name`)
        //               AGAINST (%s IN BOOLEAN MODE) ORDER BY score DESC";

        $keyword_str = '%' . $sponsor_name . '%';
        $sql_query = "SELECT `id` from " . $wpdb->prefix . "gs_grant_sponsors WHERE sponsor_name LIKE %s";
        $sql = $wpdb->prepare( $sql_query, $keyword_str );

        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $sponsor_ids = array_keys( $data );

        $res = array();
        if ( count( $sponsor_ids) > 0 ) {
            $ids_str = join( ',', $sponsor_ids );

            if ($search_mode == 'editor') {
                $sql_query = "SELECT grant_id FROM " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings as gscm
                        JOIN " . $wpdb->prefix . "gs_grants as gg ON gscm.grant_id = gg.id
                        WHERE sponsor_id IN($ids_str)";
            } else {
                $sql_query = "SELECT grant_id FROM " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings as gscm
                        JOIN " . $wpdb->prefix . "gs_grants as gg ON gscm.grant_id = gg.id
                        WHERE sponsor_id IN($ids_str) AND gg.status = 'A'";
            }
//        echo "SQLQ628: $sql_query<br>";

            $sql = $wpdb->prepare( $sql_query, $keyword_str, $keyword_str );
            $data = $wpdb->get_results( $sql, "OBJECT_K" );
        }

        $res = array_keys( $data );

        return $res;
    }


    /**
     * Function search_by_title
     * @params $title
     * @return $res
     */
    function search_by_title( $title )
    {
        global $wpdb;

        $title = sanitize_text_field( $title );

        $keyword_str = '';
        $keywords = explode(' ', $title);
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

        $sql_query = "SELECT  `id`, MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
                      AGAINST (%s IN NATURAL LANGUAGE MODE) AS score
                      FROM " . $wpdb->prefix . "gs_grants WHERE MATCH (`title`)
                      AGAINST (%s IN BOOLEAN MODE) AND status='A' ORDER BY score DESC";
//        echo "SQLQ668: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query, $keyword_str, $keyword_str );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

//        echo "SPS:<br>";
//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $res = array_keys( $data );

        return $res;
    }


    /**
     * Function search_by_description
     * @params $description
     * @return $res
     */
    function search_by_description($description)
    {
        global $wpdb;

        $description = sanitize_text_field( $description );

        $keyword_str = '';
        $keywords = explode( ' ', stripslashes($description) );
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

        $sql_query = "SELECT  `id`, MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
                      AGAINST (%s IN NATURAL LANGUAGE MODE) AS score
                      FROM " . $wpdb->prefix . "gs_grants WHERE MATCH (`description`)
                      AGAINST (%s IN BOOLEAN MODE) AND status='A' ORDER BY score DESC";
//        echo "SQLQ668: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query, $keyword_str, $keyword_str );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }


    /**
     * Function search_by_requirements
     * @params $requirements
     * @return $res
     */
    function search_by_requirements( $requirements )
    {
        global $wpdb;

        $requirements = sanitize_text_field( $requirements );

        $keyword_str = '';
        $keywords = explode(' ', stripslashes($requirements));
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

        $sql_query = "SELECT  `id`, MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
                      AGAINST (%s IN NATURAL LANGUAGE MODE) AS score
                      FROM " . $wpdb->prefix . "gs_grants WHERE MATCH (`requirements`)
                      AGAINST (%s IN BOOLEAN MODE) AND status='A' ORDER BY score DESC";
//        echo "SQLQ668: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query, $keyword_str, $keyword_str );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }


    /**
     * Function search_by_deadlines
     * @params $requirements
     * @return $res
     */
    function search_by_deadlines( $start_month, $start_date, $end_month, $end_date )
    {
        global $wpdb;

        $sql_query = "SELECT gkd.grant_id FROM `" . $wpdb->prefix . "gs_grant_key_dates` as gkd, `" . $wpdb->prefix . "gs_grants` as g
                      WHERE gkd.date_title='deadline'
                      AND ( gkd.month >= %d AND gkd.date >= %d )
                      AND ( gkd.month <= %d AND gkd.date <= %d )
                      AND gkd.grant_id = g.id  and g.status='A' ";
//        echo "SQLQ668: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query, $start_month, $start_date, $end_month, $end_date );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }


    /**
     * Function search_by_restrictions
     * @params $restrictions
     * @return $res
     */
    function search_by_restrictions( $restrictions )
    {
        global $wpdb;

        $restrictions = sanitize_text_field( $restrictions );

        $keyword_str = '';
        $keywords = explode(' ', stripslashes($restrictions));
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

        $sql_query = "SELECT  `id`, MATCH (`title` ,  `description`, `requirements` ,  `restrictions` ,  `samples`)
                      AGAINST (%s IN NATURAL LANGUAGE MODE) AS score
                      FROM " . $wpdb->prefix . "gs_grants WHERE MATCH (`restrictions`)
                      AGAINST (%s IN BOOLEAN MODE) AND status='A' ORDER BY score DESC";
//        echo "SQLQ668: $sql_query<br>";

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
     * Function grant_exists
     * @params $grant_id
     * @return true if grant exists, false if not
     */
    function grant_exists( $grant_id )
    {
        global $wpdb;

        $sql_query = "SELECT title FROM " . $wpdb->prefix . "gs_grants WHERE id=%d";
        $sql = $wpdb->prepare( $sql_query, $grant_id );
        $data = $wpdb->get_results( $sql );

        if ( empty($data[0]) ) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Function get_grant_sponsor
     * @params $grant_id
     * @return $sponsor_id
     */
    function get_grant_sponsor( $grant_id )
    {
        global $wpdb;

        $sql_query = "SELECT sponsor_id FROM " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings WHERE grant_id=%d";
        $sql = $wpdb->prepare( $sql_query, $grant_id );
        $data = $wpdb->get_results( $sql );

        $sponsor_id = $data[0]->sponsor_id;

        return $sponsor_id;
    }


    /**
     * Function get_sponsor_name
     * @params $grant_id
     * @return $sponsor_name
     */
    function get_sponsor_name( $sponsor_id )
    {
        global $wpdb;

        $sql_query = "SELECT sponsor_name FROM " . $wpdb->prefix . "gs_grant_sponsors WHERE id=%d";
        $sql = $wpdb->prepare( $sql_query, $sponsor_id );
        $data = $wpdb->get_results( $sql );

        $sponsor_name = $data[0]->sponsor_name;

        return $sponsor_name;
    }


    /**
     * Function get_sponsor_data
     * @params $sponsor_id
     * @return array $sponsor_data
     */
    function get_sponsor_data( $sponsor_id )
    {
        global $wpdb;

        $sql_query = "SELECT sponsor_name, sponsor_department, sponsor_address, sponsor_address2, sponsor_city, sponsor_state,
                             sponsor_zip, sponsor_country, sponsor_url, grant_sponsor_type_id, status
                      FROM " . $wpdb->prefix . "gs_grant_sponsors WHERE id=%d";
        $sql = $wpdb->prepare( $sql_query, $sponsor_id );
        $data = $wpdb->get_results( $sql );

        $sponsor_data = $data[0];

        return $sponsor_data;
    }


    /**
     * Function get_contact_ids
     * @params $grant_id
     * @return $contact_ids
     */
    function get_contact_ids( $grant_id )
    {
        global $wpdb;

        $sql_query = "SELECT contact_id FROM " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings WHERE grant_id=%d";
        $sql = $wpdb->prepare( $sql_query, $grant_id );
        $data = $wpdb->get_results( $sql, 'ARRAY_A' );

        $contact_ids = $data;

        return $contact_ids;
    }


    /**
     * Function get_contact_data
     * @params $contact_id
     * @return array $contact_data
     */
    function get_contact_data( $contact_id )
    {
        global $wpdb;

        $sql_query = "SELECT contact_name, contact_title, contact_org_dept, contact_address1, contact_address2, contact_city, contact_state,
                             country, contact_zip, contact_phone_1, contact_phone_2, contact_fax, contact_email_1, contact_email_2,
                             updated_at, created_at
                      FROM " . $wpdb->prefix . "gs_grant_contacts WHERE id=%d";
        $sql = $wpdb->prepare( $sql_query, $contact_id );
        $data = $wpdb->get_results( $sql );

        $contact_data = $data[0];

        return $contact_data;
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
     * Function get_deadline_data
     * @params $deadline_id
     * @return $deadline_data
     */
    function get_deadline_data( $deadline_id )
    {
        global $wpdb;

        $sql_query = "SELECT id, grant_id, date_title, year, month, date, satisfied, date_notes, updated_at, created_at
                      FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE id=%d";
        $sql = $wpdb->prepare( $sql_query, $deadline_id );
        $data = $wpdb->get_results( $sql );

        $deadline_data = $data;

        return $deadline_data;
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
     * Function get_first_deadline
     * @params $grant_id
     * @return $deadline
     */
    function get_first_deadline( $grant_id )
    {
        global $wpdb;

        $sql_query = "SELECT month, date FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE grant_id=%d AND date_title='deadline'";
        $sql = $wpdb->prepare( $sql_query, $grant_id );
        $data = $wpdb->get_results( $sql );

        $deadline = date('Y') . '-' . $data[0]->month . '-' . $data[0]->date;
        $deadline = strtotime( $deadline );

        return $deadline;
    }


    /**
     * Function get_last_deadline
     * @params $grant_id
     * @return $deadline
     */
    function get_last_deadline( $grant_id )
    {
        global $wpdb;

        $sql_query = "SELECT month, date FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE grant_id=%d AND date_title='deadline'";
        $sql = $wpdb->prepare( $sql_query, $grant_id );
        $data = $wpdb->get_results( $sql );

        foreach ( $data as $data_item ) {
            $deadline = date('Y') . '-' . $data_item->month . '-' . $data_item->date;
        }
        $deadline = strtotime( $deadline );

        return $deadline;
    }


    /**
     * Function get_keydates_ids
     * @params $grant_id
     * @return $keydates_ids
     */
    function get_keydates_ids( $grant_id )
    {
        global $wpdb;

        $sql_query = "SELECT id FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE grant_id=%d AND date_title!=%s AND date_title!=%s";
        $sql = $wpdb->prepare( $sql_query, $grant_id, 'deadline', 'revisit' );
        $data = $wpdb->get_results( $sql, 'ARRAY_A' );

        $keydates_ids = $data;

        return $keydates_ids;
    }


    /**
     * Function get_keydates_data
     * @params $keydates_id
     * @return $keydates_data
     */
    function get_keydates_data( $keydates_id )
    {
        global $wpdb;

        $sql_query = "SELECT id, grant_id, date_title, year, month, date, satisfied, date_notes, updated_at, created_at
                      FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE id=%d";
        $sql = $wpdb->prepare( $sql_query, $keydates_id );
        $data = $wpdb->get_results( $sql );

        $keydates_data = $data;

        return $keydates_data;
    }


    /**
     * Function get_revisit_ids
     * @params $grant_id
     * @return $revisit_ids
     */
    function get_revisit_ids( $grant_id )
    {
        global $wpdb;

        $sql_query = "SELECT id FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE grant_id=%d AND date_title=%s";
        $sql = $wpdb->prepare( $sql_query, $grant_id, 'revisit' );
        $data = $wpdb->get_results( $sql, 'ARRAY_A' );

        $revisit_ids = $data;

        return $revisit_ids;
    }


    /**
     * Function get_revisit_data
     * @params $revisit_id
     * @return $revisit_data
     */
    function get_revisit_data( $revisit_id )
    {
        global $wpdb;

        $sql_query = "SELECT id, grant_id, date_title, year, month, date, satisfied, date_notes, updated_at, created_at
                      FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE id=%d";
        $sql = $wpdb->prepare( $sql_query, $revisit_id );
        $data = $wpdb->get_results( $sql );

        $revisit_data = $data;

        return $revisit_data;
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
            case 'editor':
                $status_clause = "";    //grab records with any status
                break;
            default:
                $status_clause = "AND gs.status='A'";   //grab only active records
                break;
        }

        $res = array();

        $ids = array_unique($ids);
        $ids = implode(",", $ids);

//        print_r($ids);  //debug

//        echo "IDS:$ids<br>";
//        echo "ORD:$order<br>";

        $sql_query = "SELECT
                          gs.id, gs.title, gs.description, gs.amount_currency, gs.amount_min,
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

//        echo "SQLQ1199: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query, $ids );

//        echo "SQL:$sql<br>";

        $data = $wpdb->get_results( $sql );

//        echo "<pre>";   //debug
//        print_r($data);
//        echo "</pre>";

        $count = 0;
        foreach ( $data as $data_row ) {
            $res[$count]['id']              = $data_row->id;
            $res[$count]['title']           = $data_row->title;
            $res[$count]['description']     = $data_row->description;
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

//        echo "<pre>";   //debug
//        print_r($res);
//        echo "</pre>";

        return $res;
    }
    /**
     * Function get_saved_grant_sponsor_info
     * @params &$ids, $order, $if_sort_deadline
     * @return $res
     */
    function get_saved_grant_sponsor_info( $ss_id, $search_mode="access" )
    {
        global $wpdb;

        // switch ($search_mode) {
        //     case 'editor':
        //         $status_clause = "";    //grab records with any status
        //         break;
        //     default:
        //         $status_clause = " and gs.status='A'";   //grab only active records
        //         break;
        // }

        $res = array();

        $sql_query = "SELECT
                          gs.id, gs.title, gs.description, gs.amount_currency, gs.amount_min,
                          gs.amount_max, gscm.sponsor_id, gsp.sponsor_name,
                          df_deadline, dl_deadline, gs.updated_at, gs.created_at
                      FROM
                      (select grant_id from {$wpdb->prefix}gs_save_searchresult_ids where ss_id={$ss_id}) ss
                      Left join {$wpdb->prefix}gs_grants AS gs on ss.grant_id=gs.id
                      LEFT JOIN {$wpdb->prefix}gs_grant_sponsor_contact_mappings AS gscm ON gs.id = gscm.grant_id
                      LEFT JOIN {$wpdb->prefix}gs_grant_sponsors AS gsp ON gscm.sponsor_id = gsp.id
                      LEFT JOIN
                          (SELECT grant_id, MIN( CONCAT(LPAD(month,2,'0'),'-',LPAD(date,2,'0')) ) as df_deadline,  MAX( CONCAT(LPAD(month,2,'0'),'-',LPAD(date,2,'0')) ) as dl_deadline FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE date_title='deadline' GROUP BY grant_id ORDER BY month, date) as dlt
                      ON
                          dlt.grant_id = gs.id
                      WHERE gs.id IS NOT NULL
                          $status_clause
                      ";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql );
        $count = 0;
        foreach ( $data as $data_row ) {
            $res[$count]['id']              = $data_row->id;
            $res[$count]['title']           = $data_row->title;
            $res[$count]['description']     = $data_row->description;
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
            $grant_sponsor_info[$count]['deadline'] = self::get_deadline( $value['id'] );
            $count++;
        }
        return $grant_sponsor_info;
    }


    /**
     * Function represent_deadline
     * @params $deadline
     * @return $deadline_char
     */
    function represent_deadline( $deadlines )
    {
        $deadline_char = '';

        $months = array(
            '1' => 'Jan', '2'  => 'Feb', '3'  => 'Mar', '4'  => 'Apr',
            '5' => 'May', '6'  => 'Jun', '7'  => 'Jul', '8'  => 'Aug',
            '9' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
        );

        $deadlines = explode(',', $deadlines);
        foreach ($deadlines as $deadline) {
            $month = explode('-', $deadline);
            foreach ($months as $key => $value) {
                if ($key == $month[0]) {
                    if ($deadline_char == '') {
                        $deadline_char =  $value . ' ' . $month[1];
                    }
                    else {
                        $deadline_char .= ', ' . $value . ' ' . $month[1];
                    }
                }
            }
        }

        return $deadline_char;
    }

    function get_link($is_uri = true){
        $link = "";
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            $link = "https";
        else
            $link = "http";
        
        // Here append the common URL characters.
        $link .= "://";
        
        // Append the host(domain name, ip) to the URL.
        $link .= $_SERVER['HTTP_HOST'];
        
        // Append the requested resource location to the URL
        if ($is_uri)
            $link .= $_SERVER['REQUEST_URI'];
        return $link;
    }
    /**
     * Function generate_content
     * @params  $info,
     *          $page_menu,
     *          $size,
     *          $show_from,
     *          $show_to,
     *          $sort,
     *          $entry_id,
     *          $search_type = "advanced" or "quick"
     * @return $content
     */
    function generate_content($info, $page_menu, $page_num, $size, $show_from, $show_to, $sort_dir, $entry_id, $search_type="advanced", $table_head_class )
    {

        if ( empty($info) ) {
            //do_action("gs_add_subscriber_log", self::get_current_user_and_guest_id(), SEARCH_STATUS, "no results found in searches");
            $content = '<div class="search-error">';
            $content .= 'Sorry, no information matching your criteria have been found.<br>';
            if (!isset($_GET['saved'])){
                $content .= '<a href="/access/' . $search_type . '-search/?ppeid=' . $entry_id . '">Modify Search</a> | ';
            }
            $content .= '<a href="/access/' . $search_type . '-search">New Search</a>';
            $content .= '</div>';
        }
        else {
            //do_action("gs_add_subscriber_log", self::get_current_user_and_guest_id(), SEARCH_STATUS, "successful grant listing searches");
            $link = self::get_link();
            $entry_id = intval($entry_id);
            $odd_even = 1;
            $content .= '<div class="search-options">';
            $content .= '<a href="/access/' . $search_type . '-search"><b>New Search</b></a>';
            if (!isset($_GET['saved'])){
                $content .= ' | <a href="/access/' . $search_type . '-search/?ppeid=' . $entry_id . '"><b>Modify Search</b></a>';
            }
            $content .= ' | <a class="link-save-search search" href="#"><b>Save Search</b></a>';///access/' . $search_type . '-search/?sseid=' . $entry_id . '
            $content .= ' | <a href="#" class="export-share-link"><b>Export/Share</b></a>';
            
            //start dialog
            $content .= '<div class="save-search-dialog" style="display:none;">';
            $content .= '<div class="save-seach-section">';
            if (is_user_logged_in()){
                $content .= '<label>Search Title</label>';
                $content .= '<input type="text" name="search_title" id="search_title" value="" placeholder="Enter a title for this saved search"/>';
                $content .= '<input type="radio" name="is_agent" id="saved_search" checked value="0"><label for="saved_search">Save Search Results</label><br>';
                $content .= '<input type="radio" name="is_agent" id="search_agent" value="1"><label for="search_agent">Save Search Criteria (Create Search Agent)</label> ';
                $content .= '<p class="err-msg"></p>';
                $content .= '<div class="text-center"><input type="button" name="ssave_btn" id="ssave_btn" value="Save Search"/></div>';
                $content .= '<p class="success-msg"></p>';
            }else{
                $content .= '<p>Login Required</p>';
                $content .= '<div class="text-center"><a class="button" href="'.home_url("/login").'">Login</a></div>';
            }
            $content .= '</div>';
            $content .= '</div>';

            $content .= '<div class="es-dialog" style="display:none;">';

            $content .= '<div class="export-section">';
            $content .= '<input type="radio" class="export-rdo" name="item_down" id="csv_down" value="'.$link.'&download=csv"><label for="csv_down">CSV</label>';
            $content .= '<input type="radio" class="export-rdo" name="item_down" id="xlsx_down" value="'.$link.'&download=xlsx"><label for="xlsx_down">Excel</label>';
            $content .= '<input type="radio" class="export-rdo" name="item_down" id="text_down" value="'.$link.'&download=txt"><label for="text_down">Text</label>';
            $content .= '<input type="radio" class="export-rdo" name="item_down" id="pdf_down" value="'.$link.'&download=pdf"><label for="pdf_down">PDF</label>';
            $content .= '<br>';
            $content .= '<input type="button" name="export_btn" id="export_btn" value="Export"/>';
            $content .= '</div>';

            $content .= '<div class="print-section">';
            $content .= '<p>Warning: attempting to print a long list of results may lock up your browser.</p>';
            $content .= '<a href="'.$link.'&print=results" class="print-result button" data="print">Print</a>';
            $content .= '</div>';
            
            $content .= '<div class="share-section">';
            $content .= '<input type="text" name="sharing" id="sharing" value="" placeholder="Email Address"/>';
            $content .= '<p class="err-msg"></p>';
            $content .= '<label>Message (optional)</label>';
            $content .= '<textarea name="sharing_content" id="sharing_content"></textarea>';
            $content .= '<p>Note: Only the first 100 grants in your search results will be included in this email.</p>';
            $content .= '<div class="text-center"><a href="'.$link.'&sharing=results" class="sharing-result button" data="sharing">Share</a></div>';
            $content .= '<p class="success-msg"></p>';
            $content .= '</div>';
            
            $content .= '</div>';
            //end dialog

            $content .= '</div>';
            //start paginate_menu content
            $content .= '<div class="search-filter">';
            $content .= '<input type="text" name="search" id="search" value="' . (isset($_GET['search'])?$_GET['search']:'').'" placeholder="Search term">';
            $content .= '<button class="button filter_btn">Filter</button><button class="button clear_btn">Clear</button>';
            $content .= '</div>';

            $content .= '<div class="paginate_menu"><b>Displaying: </b>' . $show_from . ' - ' . $show_to. '  of ' . $size;
            $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $page_menu;
            $content .= '</div>';

            $perpage = get_user_meta(self::get_current_user_and_guest_id(), 'gs_per_page', true);
            if (empty($perpage)) {
                if ( absint( $_GET['pp'] ) ) {
                    $perpage=absint( $_GET['pp'] );
                } else {
                    $perpage=10;
                }
            }

            $content .= '<div class="per_page_section">';
            $content .= 'Display <select id="per_page" class="per-page">';
            $content .= '<option value="10" '.($perpage==10?'selected':'').'>10</option>';
            $content .= '<option value="20" '.($perpage==20?'selected':'').'>20</option>';
            $content .= '<option value="50" '.($perpage==50?'selected':'').'>50</option>';
            $content .= '<option value="100" '.($perpage==100?'selected':'').'>100</option>';
            $content .= '</select> results per page';
            $content .= '</div>';
            //end paginate_menu content
            $content .= '<table class="sortable mass-editable" summary="Search Results">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<th><a href="' . $base_url . '/access/search-results/?sid=' . $entry_id . '&sb=gt&sd=' . $sort_dir['gt'] . '&pn=' . $page_num . '" class="' . $table_head_class['gt'] . '">Grant Title</a></th>';
            $content .= '<th><a href="' . $base_url . '/access/search-results/?sid=' . $entry_id . '&sb=sp&sd=' . $sort_dir['sp'] . '&pn=' . $page_num . '" class="' . $table_head_class['sp'] . '">Sponsor</a></th>';
            $content .= '<th><a href="' . $base_url . '/access/search-results/?sid=' . $entry_id . '&sb=dl&sd=' . $sort_dir['dl'] . '&pn=' . $page_num . '" class="' . $table_head_class['dl'] . '">Deadlines</a></th>';
            $content .= '<th><a href="' . $base_url . '/access/search-results/?sid=' . $entry_id . '&sb=am&sd=' . $sort_dir['am'] . '&pn=' . $page_num . '" class="' . $table_head_class['am'] . '">Amount</a></th>';
            $content .= '</tr>';
            $content .= '</thead>';
            $content .= '<tbody>';
            foreach ($info as $key => $value) {
                if ($odd_even % 2 != 0) {
                    $content .= '<tr class="odd">';
                }
                else {
                    $content .= '<tr>';
                }
                $description = explode(' ', $value['description']);
                if (count($description) > 30) {
                    $temp = '';
                    for ($i = 0; $i < 30; $i++) {
                        $temp .= $description[$i] . ' ';
                    }
                    $value['description'] = $temp . '...';
                }
                $content .= '<td><a class="listing" href="' . $base_url . '/access/grant-details/?gid=' . $value['id'] . '"><strong>' . $value['title'] . '</strong></a><br />' . $value['description'] . '</td>';
                $content .= '<td>' . $value['sponsor_name'] . '</td>';
                if ($value['deadline'] == '') {
                    $content .= '<td>(not specified)</td>';
                }
                else if ($value['deadline'] != '') {
                    $deadline = $value['deadline'];
                    $deadline = self::represent_deadline($deadline);
                    $content .= '<td>' . $deadline . '</td>';
                }
                if ($value['amount_min'] == 0 and $value['amount_max'] > 0) {
                    $content .= '<td>Up to ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'] . '</td>';
                }
                else if ($value['amount_min'] > 0 and $value['amount_max'] == 0) {
                    $content .= '<td>' . number_format($value['amount_min']) . ' - (not specified) ' . $value['amount_currency'] . '</td>';
                }
                else if ($value['amount_min'] == 0 and $value['amount_max'] == 0) {
                    $content .= '<td>(not specified)</td>';
                }
                else {
                    $content .= '<td>' . number_format($value['amount_min']) . ' - ' . number_format($value['amount_max']) . ' ' . $value['amount_currency'] . '</td>';
                }
                $content .= '</tr>';
                $odd_even++;
            }
            $content .= '</tbody>';
            $content .= '</table>';
            //start paginate_menu content
            $content .= '<div class="paginate_menu"><b>Displaying: </b>' . $show_from . ' - ' . $show_to. '  of ' . $size;
            $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $page_menu;
            $content .= '</div>';
            $content .= '<div class="per_page_section">';
            $content .= 'Display <select id="per_page" class="per-page">';
            $content .= '<option value="10" '.($perpage==10?'selected':'').'>10</option>';
            $content .= '<option value="20" '.($perpage==20?'selected':'').'>20</option>';
            $content .= '<option value="50" '.($perpage==50?'selected':'').'>50</option>';
            $content .= '<option value="100" '.($perpage==100?'selected':'').'>100</option>';
            $content .= '</select> results per page';
            $content .= '</div>';
            //end paginate_menu content
        }
        return $content;
    }


    /**
     * Function generate_content_editor
     * @params  $info,
     *          $page_menu,
     *          $size,
     *          $show_from,
     *          $show_to,
     *          $sort,
     *          $entry_id,
     *          $search_type = "advanced" or "quick"
     * @return $content
     */
    function generate_content_editor ($info, $page_menu, $page_num, $size, $show_from, $show_to, $sort_dir, $entry_id, $search_type="advanced", $table_head_class )
    {
        $content = '<h2>' . ucfirst($search_type) . ' Search Results</h2>';

        if ( empty($info) ) {
            //do_action("gs_add_subscriber_log", get_current_user_id(), SEARCH, "no results found in records");
            $content .= '<div class="search-error">';
            $content .= 'Sorry, no information matching your criteria have been found.<br>';
            if (!isset($_GET['saved'])){
                $content .= '<a href="/editor/search/' . $search_type . '-search/?ppeid=' . $entry_id . '">Modify Search</a> | ';
            }
            $content .= '<a href="/editor/search/' . $search_type . '-search/">New Search</a>';
            $content .= '</div>';
        }
        else {
            //do_action("gs_add_subscriber_log", get_current_user_id(), SEARCH, "successful grant listing records");
            $link = self::get_link();

            $entry_id = intval($entry_id);
            $content .= '<div class="search-options">';
            $content .= '<a href="/editor/search/' . $search_type . '-search"><b>New Search</b></a>';
            if (!isset($_GET['saved'])){
                $content .= ' | <a href="/editor/search/' . $search_type . '-search/?ppeid=' . $entry_id . '"><b>Modify Search</b></a>';
            }
            $content .= ' | <a class="link-save-search editor" href="#"><b>Save Search</b></a>';///editor/search/' . $search_type . '-search/?sseid=' . $entry_id . '
            $content .= ' | <a href="#" class="export-share-link"><b>Export/Share</b></a>';
            
            //start dialog
            $content .= '<div class="save-search-dialog" style="display:none;">';
            $content .= '<div class="save-seach-section">';
            //$content .= '<label>Search Title</label>';
            $content .= '<input type="text" name="search_title" id="search_title" value="" placeholder="Enter a title for this saved search"/>';
            $content .= '<p class="err-msg"></p>';
            $content .= '<div class="text-center"><input type="button" name="ssave_btn" id="ssave_btn" value="Save Search"/></div>';
            $content .= '<p class="success-msg"></p>';
            $content .= '</div>';
            $content .= '</div>';


            //start dialog
            $content .= '<div class="es-dialog" style="display:none;">';
            
            $content .= '<div class="export-section">';
            $content .= '<input type="radio" class="export-rdo" name="item_down" id="csv_down" value="'.$link.'&download=csv"><label for="csv_down">CSV</label>';
            $content .= '<input type="radio" class="export-rdo" name="item_down" id="xlsx_down" value="'.$link.'&download=xlsx"><label for="xlsx_down">Excel</label>';
            $content .= '<input type="radio" class="export-rdo" name="item_down" id="text_down" value="'.$link.'&download=txt"><label for="text_down">Text</label>';
            $content .= '<input type="radio" class="export-rdo" name="item_down" id="pdf_down" value="'.$link.'&download=pdf"><label for="pdf_down">PDF</label>';
            $content .= '<br>';
            $content .= '<input type="button" name="export_btn" id="export_btn" value="Export"/>';
            $content .= '</div>';

            $content .= '<div class="print-section">';
            $content .= '<p>Warning: attempting to print a long list of results may lock up your browser.</p>';
            $content .= '<a href="'.$link.'&print=editor" class="print-editor button" data="print">Print</a>';
            $content .= '</div>';
            
            $content .= '<div class="share-section">';
            $content .= '<input type="text" name="sharing" id="sharing" value="" placeholder="Email Address"/>';
            $content .= '<p class="err-msg"></p>';
            $content .= '<label>Message (optional)</label>';
            $content .= '<textarea name="sharing_content" id="sharing_content"></textarea>';
            $content .= '<p>Note: Only the first 100 grants in your search results will be included in this email.</p>';
            $content .= '<div class="text-center"><a href="'.$link.'&sharing=editor" class="sharing-editor button" data="sharing">Share</a></div>';
            $content .= '<p class="success-msg"></p>';
            $content .= '</div>';
            
            $content .= '</div>';
            //end dialog
            
            $content .= '</div>';
            //start paginate_menu content
            $content .= '<div class="search-filter">';
            $content .= '<input type="text" name="search" id="search" value="' . (isset($_GET['search'])?$_GET['search']:'').'" placeholder="Search term">';
            $content .= '<button class="button filter_btn">Filter</button><button class="button clear_btn">Clear</button>';
            $content .= '</div>';
            
            $content .= '<div class="paginate_menu"><b>Displaying: </b>' . $show_from . ' - ' . $show_to. '  of ' . $size;
            $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $page_menu;
            $content .= '</div>';

            $content .= '<div class="per_page_section">';
            $content .= 'Display <select id="per_page" class="per-page">';
            $content .= '<option value="10" '.(isset($_GET['pp'])&&$_GET['pp']==10?'selected':'').'>10</option>';
            $content .= '<option value="20" '.(isset($_GET['pp'])&&$_GET['pp']==20?'selected':'').'>20</option>';
            $content .= '<option value="50" '.(isset($_GET['pp'])&&$_GET['pp']==50?'selected':'').'>50</option>';
            $content .= '<option value="100" '.(isset($_GET['pp'])&&$_GET['pp']==100?'selected':'').'>100</option>';
            $content .= '</select> results per page';
            $content .= '</div>';
            
            //end paginate_menu content

            $content .= '<div class="mass-edit-options" style="display:none">';
            $content .= '<input type="submit" name="mass-process" value="Bulk Edit Checked Items" id="mass-process">';
            $content .= '</div>';

            $content .= '<table class="editor_quick_search sortable mass-editable" summary="Search Results">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<th><input type="checkbox" name="mass_action" class="mass-action-all" value="all"></th>';
            $content .= '<th><a href="' . $base_url . '/editor/search/results/?sid=' . $entry_id . '&sb=id&sd=' . $sort_dir['id'] . '&pn=' . $page_num . '" class="' . $table_head_class['id'] . '">ID</a></th>';
            $content .= '<th></th>';
            $content .= '<th></th>';
            $content .= '<th><a href="' . $base_url . '/editor/search/results/?sid=' . $entry_id . '&sb=gt&sd=' . $sort_dir['gt'] . '&pn=' . $page_num . '" class="' . $table_head_class['gt'] . '">Title</a></th>';
            $content .= '<th><a href="' . $base_url . '/editor/search/results/?sid=' . $entry_id . '&sb=sp&sd=' . $sort_dir['sp'] . '&pn=' . $page_num . '" class="' . $table_head_class['sp'] . '">Sponsor</a></th>';
            $content .= '<th><a href="' . $base_url . '/editor/search/results/?sid=' . $entry_id . '&sb=ud&sd=' . $sort_dir['ud'] . '&pn=' . $page_num . '" class="' . $table_head_class['ud'] . '">Updated</a></th>';
            $content .= '<th><a href="' . $base_url . '/editor/search/results/?sid=' . $entry_id . '&sb=ca&sd=' . $sort_dir['ca'] . '&pn=' . $page_num . '" class="' . $table_head_class['ca'] . '">Created</a></th>';
            $content .= '</tr>';
            $content .= '</thead>';
            $content .= '<tbody>';
            foreach ($info as $key => $value) {
                $content .= '<td><input type="checkbox" name="mass_action" class="mass-action-select" value="' . $value['id'] . '"></td>';
                $content .= '<td>' . $value['id'] . '</td>';
                $content .= '<td><a href="/editor/records/view/?gid=' . $value['id'] . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '"><nobr>View</nobr></a></td>';
                $content .= '<td><a href="/editor/records/edit/?gid=' . $value['id'] . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '"><nobr>Edit</nobr></a></td>';
                $content .= '<td>' . stripslashes($value['title']) . '</td>';
                $content .= '<td>' . stripslashes($value['sponsor_name']) . '</td>';
                $content .= '<td>' . $value['updated_at'] . '</td>';
                $content .= '<td>' . $value['created_at'] . '</td>';
                $content .= '</tr>';
            }
            $content .= '</tbody>';
            $content .= '</table>';
            //start paginate_menu content
            $content .= '<div class="paginate_menu"><b>Displaying: </b>' . $show_from . ' - ' . $show_to. '  of ' . $size;
            $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $page_menu;
            $content .= '</div>';
            $content .= '<div class="per_page_section">';
            $content .= 'Display <select id="per_page" class="per-page">';
            $content .= '<option value="10" '.(isset($_GET['pp'])&&$_GET['pp']==10?'selected':'').'>10</option>';
            $content .= '<option value="20" '.(isset($_GET['pp'])&&$_GET['pp']==20?'selected':'').'>20</option>';
            $content .= '<option value="50" '.(isset($_GET['pp'])&&$_GET['pp']==50?'selected':'').'>50</option>';
            $content .= '<option value="100" '.(isset($_GET['pp'])&&$_GET['pp']==100?'selected':'').'>100</option>';
            $content .= '</select> results per page';
            $content .= '</div>';
            //end paginate_menu content

//            echo "<pre>";
//            print_r( $GLOBALS['wp_scripts']->registered );
//            echo "</pre>";

            $content .= GrantSelectRecordsAddOn::mass_edits_modal();
        }

        return $content;
    }


    /**
     * Function grantselect_search_grant_details
     * Returns array containing grant record details
     * @params $grant_id
     * @return $res
     */
    function grantselect_search_grant_details() {
        $record_num = absint( $_GET['gid'] );
        $grant_details = self::grantselect_get_grant_details( $record_num );

        if (isset($_GET['download'])){
            ob_start();
            ?>
            <!-- Begin Content Column -->
            <div id="content">
                <div id="content_wrapper">
                    <div id="content_left">
                        <!-- Intro Text -->
                        <div id="intro">
                            <h3>
                                <strong><?php echo stripslashes($grant_details->title); ?></strong>
                            </h3>
                        </div>
                        <!--intro-->
                        <!-- Sponsor info -->
                        <div class="tabbed_wrapper">
                            <h3 id="tabbed_header">Sponsor Info</h3>
                            <div class="tabbed_header">
                                <?php if (!empty($grant_details->sponsor)) : ?>
                                    <?php foreach ($grant_details->sponsor as $k => $v) : ?>
                                        <p>
                                            <?php if (!empty($v->sponsor_name)) : ?>
                                                <?=stripslashes($v->sponsor_name);?><br />
                                            <?php endif; ?>

                                            <?php if (!empty($v->sponsor_department)) : ?>
                                                <?=stripslashes($v->sponsor_department);?><br />
                                            <?php endif ?>

                                            <?php if (!empty($v->sponsor_address)) : ?>
                                                <?=stripslashes($v->sponsor_address); ?>
                                            <?php endif; ?>

                                            <?php if (!empty($v->sponsor_address2)) : ?>
                                                <?=stripslashes($v->sponsor_address2); ?>
                                            <?php endif; ?>

                                            <?php if (!empty($v->sponsor_address) || !empty($v->sponsor_address2)) : ?><br /><?php endif; ?>

                                            <?php if (!empty($v->sponsor_city)):?><?=stripslashes($v->sponsor_city);?><?php endif;?><?php if (!empty($v->sponsor_city) && !empty($v->sponsor_state)) : ?>,<?php endif; ?>

                                            <?php if (!empty($v->sponsor_state)) : ?>
                                                <?=stripslashes($v->sponsor_state); ?>
                                            <?php endif; ?>

                                            <?php if (!empty($v->sponsor_zip)) : ?>
                                                <?=stripslashes($v->sponsor_zip); ?><br />
                                            <?php endif; ?>

                                            <?php if (!empty($v->sponsor_country)) : ?>
                                                <?=stripslashes($v->sponsor_country); ?><br />
                                            <?php endif; ?>

                                            Website:
                                            <?php if (!empty($v->sponsor_url)) : ?>
                                                <a href="<?=$v->sponsor_url;?>" target="_new">
                                                    <?=$v->sponsor_url;?></a>
                                            <?php else : ?>
                                                (not specified)
                                            <?php endif; ?>
                                            <br />

                                            Type:
                                            <?php if (!empty($v->sponsor_type)) : ?>
                                                <?=$v->sponsor_type;?>
                                            <?php else : ?>
                                                (not specified)
                                            <?php endif; ?>
                                        </p>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- end sponsor info -->

                        <!-- Grant program info -->
                        <div class="tabbed_wrapper">
                            <h3 id="tabbed_header">Grant Info</h3>
                            <div class="tabbed_header">
                                <p><strong><?=stripslashes($grant_details->title);?></strong>
                                </p>
                                <p><strong>Program URL</strong>:
                                    <?php if(!empty($grant_details->grant_url_1)) : ?>
                                        <a href="<?php echo $grant_details->grant_url_1; ?>" target="_new"><?php echo $grant_details->grant_url_1; ?></a>
                                    <?php else : ?>
                                        (not specified)
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <strong>Amount</strong>:
                                    <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                                        <?=number_format($grant_details->amount_min, 2); ?> - <?=number_format($grant_details->amount_max, 2); ?> <?=$grant_details->amount_currency; ?>
                                    <?php endif; ?>

                                    <?php if (empty($grant_details->amount_min) && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                                        <?=number_format($grant_details->amount_max, 2); ?> <?=$grant_details->amount_currency; ?>
                                    <?php endif; ?>

                                    <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min == '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                                        Up to&nbsp;<?=number_format($grant_details->amount_max, 2); ?> <?=$grant_details->amount_currency; ?>
                                    <?php endif; ?>

                                    <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && empty($grant_details->amount_max)) : ?>
                                        <?=number_format($grant_details->amount_min, 2); ?> <?=$grant_details->amount_currency; ?>
                                    <?php endif; ?>

                                    <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max == '0.00') : ?>
                                        Up to&nbsp;<?=number_format($grant_details->amount_min, 2); ?> <?=$grant_details->amount_currency; ?>
                                    <?php endif; ?>

                                    <?php if (empty($grant_details->amount_min) && empty($grant_details->amount_max)) : ?>
                                        (not specified)
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <strong>Description</strong>:<br />
                                    <?php if (!empty($grant_details->description)) : ?>
                                        <?=nl2br(stripslashes($grant_details->description)); ?>
                                    <?php else : ?>
                                        (not specified)
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <strong>Requirements</strong>:<br />
                                    <?php if (!empty($grant_details->requirements)) : ?>
                                        <?=nl2br(stripslashes($grant_details->requirements)); ?>
                                    <?php else : ?>
                                        (not specified)
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <strong>Geographic Focus</strong>:<br />
                                    <?php if (!empty($grant_details->geo_location)): ?>
                                        <?php foreach($grant_details->geo_location as $k=>$v):?>
                                            <?=stripslashes($v->geo_location);?><br>
                                        <?php endforeach;?>
                                    <?php else: ?>
                                        (not specified)
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <strong>Restrictions</strong>:<br />
                                    <?php if (!empty($grant_details->restrictions)) : ?>
                                        <?=nl2br(stripslashes($grant_details->restrictions)); ?>
                                    <?php else : ?>
                                        (not specified)
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <strong>Samples</strong>:<br />
                                    <?php if (!empty($grant_details->samples)) : ?>
                                        <?=nl2br(stripslashes($grant_details->samples)); ?>
                                    <?php else : ?>
                                        (not specified)
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="clear">

                            </div>
                        </div>
                        <!-- end grant program info -->
                        <!-- Contact info -->
                        <div class="tabbed_wrapper">
                            <h3 id="tabbed_header">Contact Info</h3>
                            <div class="tabbed_header">
                                <?php if(!empty($grant_details->contact_info)) : ?>
                                    <?php foreach ($grant_details->contact_info as $k => $v) : ?>
                                        <p>
                                        <?php if (!empty($v->contact_name)) : ?>
                                            <?=stripslashes($v->contact_name);?>
                                        <?php endif;?>

                                        <?php if (!empty($v->contact_title) && !empty($v->contact_name)):?>, <?php endif;?>

                                        <?php if (!empty($v->contact_title)) : ?>
                                            <?=stripslashes($v->contact_title); ?>
                                        <?php endif; ?>

                                        <?php if (!empty($v->contact_title) || !empty($v->contact_name)) : ?><br /><?php endif;?>

                                        <?php if (!empty($v->contact_org_dept)) : ?>
                                            <?=stripslashes($v->contact_org_dept); ?><br />
                                        <?php endif; ?>

                                        <?php if (!empty($v->contact_address1) and !empty($v->contact_address2)) : ?>
                                            <?=stripslashes($v->contact_address1); ?>,
                                        <?php endif; ?>

                                        <?php if (!empty($v->contact_address1) and empty($v->contact_address2)) : ?>
                                            <?=stripslashes($v->contact_address1); ?>
                                        <?php endif; ?>

                                        <?php if(!empty($v->contact_address2)) : ?>
                                            <?=stripslashes($v->contact_address2); ?>
                                        <?php endif; ?>

                                        <?php if (!empty($v->contact_address1) || !empty($v->contact_address2)) : ?><br /><?php endif;?>

                                        <?php if (!empty($v->contact_city)) : ?>
                                            <?=stripslashes($v->contact_city); ?>,
                                        <?php endif; ?>

                                        <?php if (!empty($v->contact_state)) : ?>
                                            <?=stripslashes($v->contact_state); ?>
                                        <?php endif; ?>

                                        <?php if (!empty($v->contact_zip)) : ?>
                                            <?=stripslashes($v->contact_zip); ?>
                                        <?php endif; ?>

                                        <?php if(!empty($v->contact_state) || !empty($v->contact_zip)) : ?><br /><?php endif; ?>

                                        <?php
                                        $phones = '';
                                        if (!empty($v->contact_phone_1)) {
                                            $phones = stripslashes($v->contact_phone_1);
                                        }
                                        if (!empty($v->contact_phone_2)) {
                                            if ($phones != '') {
                                                $phones .= ', ' . stripslashes($v->contact_phone_2);
                                            }
                                            else {
                                                $phones = stripslashes($v->contact_phone_2);
                                            }
                                        }
                                        echo $phones . '<br />';
                                        ?>

                                        <?php if (!empty($v->contact_fax)) : ?>
                                            fax: <?=stripslashes($v->contact_fax); ?><br />
                                        <?php endif; ?>

                                        <?php if (!empty($v->contact_email_1)) : ?>
                                            email: <a href="mailto:<?=$v->contact_email_1; ?>"><?=$v->contact_email_1; ?></a>
                                        <?php endif; ?>
                                    <?php endforeach;?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- end contact info -->
                        <!-- Deadline info starts -->
                        <div class="tabbed_wrapper">
                            <h3 id="tabbed_header">Deadlines</h3>
                            <div class="tabbed_header">
                                <ul>
                                    <?php if (!empty($grant_details->deadline_data)) : ?>
                                        <?php foreach ( $grant_details->deadline_data as $key => $value ) : ?>
                                            <?php
                                            if ($value->satisfied == 'R') {
                                                $satisfied = '(Satisfied by: Received)';
                                            }
                                            elseif ($value->satisfied == 'P') {
                                                $satisfied = '(Satisfied by: Postmarked)';
                                            }
                                            ?>
                                            <li><?=date('F',mktime(0, 0, 0, $value->month)) . ' ' . $value->date?>&nbsp;<?=$satisfied;?></li>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        (not specified)
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Deadline info ends -->
                        <!-- Key dates starts -->
                        <div class="tabbed_wrapper">
                            <h3 id="tabbed_header">Key Dates</h3>
                            <div class="tabbed_header">
                                <ul>
                                    <?php if (!empty($grant_details->key_dates)) : ?>
                                        <?php foreach ( $grant_details->key_dates as $key => $value ) : ?>
                                            <?php
                                            if ($value->date_title == 'LOI') {
                                                $value->date_title = 'Letter of Intent';
                                            }
                                            else if ($value->date_title =='Board Mtg') {
                                                $value->date_title = 'Board Meeting';
                                            }
                                            else if ($value->date_title == 'Mini Proposal') {
                                                $value->date_title = 'Mini/Pre-Proposal';
                                            }
                                            else if ($value->date_title == 'Web or Live Conference') {
                                                $value->date_title = 'Informational Session/Workshop';
                                            }
                                            else if ($value->date_title =='Semifinals') {
                                                $value->date_title = 'Notification of Awards';
                                            }
                                            ?>
                                            <li><?=ucwords($value->date_title) .' - '. date('F',mktime(0, 0, 0, $value->month)) . ' ' . $value->date;?></li>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        (not specified)
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                        <!-- Key dates ends -->
                        <!-- Segment Codes ends -->
                        <!-- Program Codes starts -->
                        <div class="tabbed_wrapper">
                            <h3 id="tabbed_header">Programs</h3>
                            <div class="tabbed_header">
                                <ul>
                                    <?php if (!empty($grant_details->program_data)) : ?>
                                        <?php foreach ($grant_details->program_data as $key => $value) : ?>
                                            <li><?=ucwords(stripslashes($value->program_title));?></li>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        (not specified)
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Program Codes ends -->
                        <!-- Subjects starts -->
                        <div class="tabbed_wrapper">
                            <h3 id="tabbed_header">Subjects</h3>
                            <div class="tabbed_header">
                                <ul>
                                    <?php if (!empty($grant_details->subject_data)) : ?>
                                        <?php foreach ($grant_details->subject_data as $key => $value) : ?>
                                            <li><?=ucwords(stripslashes($value->subject_title));?></li>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        (not specified)
                                    <?php endif;?>
                                </ul>
                            </div>
                        </div>
                        <!-- Subjects ends -->
                        <!-- Target Populations starts -->
                        <div class="tabbed_wrapper">
                            <h3 id="tabbed_header">Target Populations</h3>
                            <div class="tabbed_header">
                                <ul>
                                    <?php if (!empty($grant_details->target_data)) : ?>
                                        <?php foreach ($grant_details->target_data as $key => $value) : ?>
                                            <li><?=ucwords(stripslashes($value->target_title));?></li>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        (not specified)
                                    <?php endif;?>
                                </ul>
                            </div>
                        </div>

                        <!-- Target Populations ends -->
                    </div><!-- end content_left-->
                    <div class="clear">
                    </div>
                </div><!-- end content_wrapper-->
            </div><!-- End Content Column -->

            <?php
            $html = ob_get_contents();
            ob_end_clean();
            if ($_REQUEST['download'] == "share"){
                $to = $_REQUEST['to'];
                $subject = "Grant Details : " . $grant_details->title;
                $body = $html;
                if ($_REQUEST['sharing_content'] != ""){
                    $sc = nl2br($_REQUEST['sharing_content']);
                    $body = $sc . "<br><br>" . $html;
                }
                
                $cu_display_name = "";
                $cu_email = "";
                $user_id = 0;
                if (is_user_logged_in()){
                    $current_user = wp_get_current_user();
                    $user_id = $current_user->ID;
                    $cu_display_name = $current_user->display_name;
                    $cu_email = $current_user->user_email;
                }else{
                    $user_id = $_SESSION['guest_user_id'];
                    $cu_display_name = "GrantSelect";
                    $admin_email = explode("@", get_bloginfo('admin_email'));
                    $cu_email = "noreply@" . $admin_email[1];

                }
                do_action("gs_add_subscriber_log", $user_id, EMAILALERT_STATUS, "send grant detail with email alert.");
                $headers = array('Content-Type: text/html; charset=UTF-8','From: '.$cu_display_name.' <'.$cu_email.'>');
                $result = wp_mail( $to, $subject, $body, $headers );
                echo json_encode(['success'=>$result, 'From'=>$cu_display_name, 'email'=>$cu_email]);
                exit;    
            }
            if ($_GET['download'] == "pdf"){
                $special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", "%", "+", chr(0));
                
                $pdf_title = substr($grant_details->title, 0, min(40, strlen($grant_details->title))) . ".pdf";
                $pdf_title = str_replace($special_chars, '', $pdf_title);
                // ... a few rows later are whitespaces removed as well ...
                $pdf_title = preg_replace( '/[\r\n\t-]+/', '-', $pdf_title );
                
                ob_end_clean();
                header('Content-type: application/pdf');
                header('Content-Disposition: attachment; filename="'.$pdf_title.'"');
                header('Cache-Control: max-age=0');
                
                
                $dompdf = new Dompdf();
                

                $dompdf->loadHtml($html);

                // (Optional) Setup the paper size and orientation
                $dompdf->setPaper('A4', 'portrait');

                // Render the HTML as PDF
                $dompdf->render();

                // Output the generated PDF to Browser
                $dompdf->stream($pdf_title);
                exit;
            }else if ($_GET['download'] == "print"){
                echo json_encode(['success'=>true, 'html'=>$html]);
                exit;
            }
        }

        do_action("gs_add_subscriber_log", self::get_current_user_and_guest_id(), GRANTDETAIL_STATUS, "visit grant details");
        $content = self::grantselect_display_grant_details( $grant_details );
        return $content;
    }


    /**
     * Function grantselect_get_grant_details
     * Returns array containing grant record details
     * @params $grant_id
     * @return $res
     */
    function grantselect_get_grant_details ( $grant_id, $data_set='', $data_array=array() ) {

        global $wpdb;

        if ( empty($grant_id) && $data_set == 'editor' ) {

            //create object with empty set
            $sql_query = "SELECT 0 FROM " . $wpdb->prefix . "gs_grants LIMIT 1";
            $sql = $wpdb->prepare( $sql_query );
            $data = $wpdb->get_results( $sql );
            $grant_record = $data[0];

            $sql    = $wpdb->prepare( $sql_query );
            $result = $wpdb->get_results( $sql, 'OBJECT' );
            $data   = $result[0];

            $grant_record->sponsor          = array( 0=>clone $data );
            $grant_record->geo_location     = array( 0=>clone $data );
            $grant_record->contact_info     = array( 0=>clone $data );
            $grant_record->deadline_data    = array( 0=>clone $data );
            $grant_record->key_dates        = array( 0=>clone $data );
            $grant_record->segment_data     = array( 0=>clone $data );
            $grant_record->program_data     = array( 0=>clone $data );
            $grant_record->subject_data     = array( 0=>clone $data );
            $grant_record->target_data      = array( 0=>clone $data );
            $grant_record->revisit          = array( 0=>clone $data );

            if ( !empty( $data_array ) ) {

                //load passed data into grant object
                $grant_record->title            = $data_array["grant"]["title"];
                $grant_record->description      = $data_array["grant"]["description"];
                $grant_record->requirements     = $data_array["grant"]["requirements"];
                $grant_record->restrictions     = $data_array["grant"]["restrictions"];
                $grant_record->samples          = $data_array["grant"]["samples"];
                $grant_record->cfda             = $data_array["grant"]["cfda"];
                $grant_record->amount_currency  = $data_array["grant"]["amount_currency"];

                //format "amount" data
                if (!empty($data_array['grant']['amount_min'])) {
                    $temp = $data_array['grant']['amount_min'];
                    $temp = preg_split('//', $temp, -1, PREG_SPLIT_NO_EMPTY);
                    $out  = '';
                    foreach ($temp as $t) {
                        if (preg_match("/\d+|\,|\./", $t)) {
                            $out .= $t;
                        }
                    }
                    $data_array['grant']['amount_min'] = preg_replace("/\,/", '', $out);
                }
                elseif ($data_array['grant']['amount_min'] == '0') {
                    $data_array['grant']['amount_min'] = '0.00';
                }
                else {
                    $data_array['grant']['amount_min'] = NULL;
                }
                if (!empty($data_array['grant']['amount_max'])) {
                    $temp = $data_array['grant']['amount_max'];
                    $temp = preg_split('//', $temp, -1, PREG_SPLIT_NO_EMPTY);
                    $out = '';
                    foreach ($temp as $t) {
                        if (preg_match("/\d+|\,|\./", $t)) {
                            $out .= $t;
                        }
                    }
                    $data_array['grant']['amount_max'] = preg_replace("/\,/", '', $out);
                }
                else {
                    $data_array['grant']['amount_max'] = NULL;
                }
                $grant_record->amount_min       = $data_array["grant"]["amount_min"];
                $grant_record->amount_max       = $data_array["grant"]["amount_max"];

                $grant_record->amount_notes     = $data_array["grant"]["amount_notes"];
                $grant_record->grant_url_1      = $data_array["grant"]["grant_url_1"];
                $grant_record->grant_url_2      = $data_array["grant"]["grant_url_2"];
                $grant_record->status           = $data_array["grant"]["status"];

                if ( $data_array["grant"]["email_alerts"] ) {
                    $grant_record->email_alerts = 1;
                } else {
                    $grant_record->email_alerts = 0;
                }

                foreach ( $data_array["GrantSponsor"] as $key=>$data ) {
                    $grant_record->sponsor[0]->$key = $data;
                }

                $params_array = explode('@', $data_array['all_sponsors']);
                $sponsor_ID = (int)$params_array[0];
                $grant_record->sponsor[0]->id   = $sponsor_ID; //id of selected Sponsor from pulldown list

                $geo_location_obj = array_shift($grant_record->geo_location);
                $geo_locations_list = self::get_geo_locations_list();
                foreach ( $data_array["GrantGeoLocation"]["geo_location2"] as $geo_id ) {
                    if ( !empty($geo_id) ) {
                        $grant_record->geo_location[$geo_id] = clone $geo_location_obj;
                        $grant_record->geo_location[$geo_id]->id = $geo_id;
                        if ( $geo_id == 1 ) {
                            $geo_locale = 'Domestic';
                            $geo_location = '-----All States-----';
                        } else if ( $geo_id == 247 ) {
                            $geo_locale = 'Foreign';
                            $geo_location = '---All Countries---';
                        } else {
                            $geo_locale = $geo_locations_list[$geo_id]->geo_locale;
                            $geo_location = $geo_locations_list[$geo_id]->geo_location;
                        }

                        $grant_record->geo_location[$geo_id]->geo_locale   = $geo_locale;
                        $grant_record->geo_location[$geo_id]->geo_location = $geo_location;
                    }
                }

                if ( !empty($data_array['contact']['contact_name'][0]) || !empty($data_array['contact']['contact_title'][0]) ) {
                    $contact_info_obj = array_shift($grant_record->contact_info);
                    for ($i = 0; $i < count( $data_array['contact']['contact_name'] ); $i++) {
                        $contact_name = $data_array['contact']['contact_name'][$i];
                        $grant_record->contact_info[$contact_name] = clone $contact_info_obj;
                        $grant_record->contact_info[$contact_name]->id                = $data_array['contact']['id'][$i];
                        $grant_record->contact_info[$contact_name]->contact_name      = $data_array['contact']['contact_name'][$i];
                        $grant_record->contact_info[$contact_name]->contact_title     = $data_array['contact']['contact_title'][$i];
                        $grant_record->contact_info[$contact_name]->contact_org_dept  = $data_array['contact']['contact_org_dept'][$i];
                        $grant_record->contact_info[$contact_name]->contact_address1  = $data_array['contact']['contact_address1'][$i];
                        $grant_record->contact_info[$contact_name]->contact_address2  = $data_array['contact']['contact_address2'][$i];
                        $grant_record->contact_info[$contact_name]->contact_city      = $data_array['contact']['contact_city'][$i];
                        $grant_record->contact_info[$contact_name]->contact_state     = $data_array['contact']['contact_state'][$i];
                        $grant_record->contact_info[$contact_name]->country           = $data_array['contact']['country'][$i];
                        $grant_record->contact_info[$contact_name]->contact_zip       = $data_array['contact']['contact_zip'][$i];
                        $grant_record->contact_info[$contact_name]->contact_phone_1   = $data_array['contact']['contact_phone_1'][$i];
                        $grant_record->contact_info[$contact_name]->contact_phone_2   = $data_array['contact']['contact_phone_2'][$i];
                        $grant_record->contact_info[$contact_name]->contact_fax       = $data_array['contact']['contact_fax'][$i];
                        $grant_record->contact_info[$contact_name]->contact_email_1   = $data_array['contact']['contact_email_1'][$i];
                        $grant_record->contact_info[$contact_name]->contact_email_2   = $data_array['contact']['contact_email_2'][$i];
                    }
                }

                if ( !empty($data_array['deadline']['month']) ) {
                    $deadline_obj = array_shift($grant_record->deadline_data);
                    for ($i = 0; $i < count( $data_array['deadline']['month'] ); $i++) {
                        $grant_record->deadline_data[$i] = clone $deadline_obj;
                        $grant_record->deadline_data[$i]->date_title    = 'deadline';
                        $grant_record->deadline_data[$i]->month         = $data_array['deadline']['month'][$i];
                        $grant_record->deadline_data[$i]->date          = $data_array['deadline']['day'][$i];
                        $grant_record->deadline_data[$i]->satisfied     = $data_array['deadline']['satisfied'][$i];
                    }
                }

                if ( !empty($data_array['keydates']['month']) ) {
                    $keydates_obj = array_shift($grant_record->key_dates);
                    for ($i = 0; $i < count( $data_array['keydates']['month'] ); $i++) {
                        $grant_record->key_dates[$i] = clone $deadline_obj;
                        $grant_record->key_dates[$i]->date_title    = $data_array['keydates']['date_title'][$i];
                        $grant_record->key_dates[$i]->month         = $data_array['keydates']['month'][$i];
                        $grant_record->key_dates[$i]->date          = $data_array['keydates']['day'][$i];
                        $grant_record->key_dates[$i]->satisfied     = '';
                    }
                }

                if ( !empty($data_array['GrantSegmentMappings']['segment_id']) ) {
                    $segment_data_obj = array_shift($grant_record->segment_data);
                    for ($i = 0; $i < count( $data_array['GrantSegmentMappings']['segment_id'] ); $i++) {
                        $segment_id = $data_array['GrantSegmentMappings']['segment_id'][$i];
                        $grant_record->segment_data[$segment_id] = clone $segment_data_obj;
                        $grant_record->segment_data[$segment_id]->id = $data_array['GrantSegmentMappings']['segment_id'][$i];
                    }
                }

                if ( !empty($data_array['GrantProgramMappings']['program_id']) ) {
                    $program_data_obj = array_shift($grant_record->program_data);
                    for ($i = 0; $i < count( $data_array['GrantProgramMappings']['program_id'] ); $i++) {
                        $program_id = $data_array['GrantProgramMappings']['program_id'][$i];
                        $grant_record->program_data[$program_id] = clone $program_data_obj;
                        $grant_record->program_data[$program_id]->id = $data_array['GrantProgramMappings']['program_id'][$i];
                    }
                }

                $subjects_list = self::get_subjects_list();
                if ( !empty($data_array['GrantSubjectMappings']['subject_title2']) ) {
                    $subject_data_obj = array_shift($grant_record->subject_data);
                    for ($i = 0; $i < count( $data_array['GrantSubjectMappings']['subject_title2'] ); $i++) {
                        $grant_record->subject_data[$i] = clone $subject_data_obj;
                        $subject_id = $data_array['GrantSubjectMappings']['subject_title2'][$i];
                        $grant_record->subject_data[$i]->id             = $subject_id;
                        $grant_record->subject_data[$i]->subject_title  = $subjects_list[$subject_id]->subject_title;
                    }
                }

                if ( !empty($data_array['GrantTargetMappings']['target_title']) ) {
                    $target_data_obj = array_shift($grant_record->target_data);
                    for ($i = 0; $i < count( $data_array['GrantTargetMappings']['target_title'] ); $i++) {
                        $target_id = $data_array['GrantTargetMappings']['target_title'][$i];
                        $grant_record->target_data[$target_id] = clone $target_data_obj;
                        $grant_record->target_data[$target_id]->id = $data_array['GrantTargetMappings']['target_title'][$i];
                    }
                }

                if ( !empty($data_array['revisit']['month']) ) {
                    $grant_record->revisit[0]->month = $data_array['revisit']['month'];
                    $grant_record->revisit[0]->date  = $data_array['revisit']['day'];
                    $grant_record->revisit[0]->year  = $data_array['revisit']['year'];
                }
            }

//            echo "<pre>";
//            print_r($grant_record);
//            echo "</pre>";

            return $grant_record;
        }

        if ($data_set == 'editor') {
            $sql_query = "SELECT
              *
            FROM
              " . $wpdb->prefix . "gs_grants
            WHERE
              id = %s
            LIMIT
              1";

            $key_dates_where_stmt = "AND gkd.date_title != 'revisit'";

        } else {
            $sql_query = "SELECT
              id, title, description, requirements, restrictions,
              samples, amount_currency, grant_url_1, grant_url_2,
              amount_min, amount_max
            FROM
              " . $wpdb->prefix . "gs_grants
            WHERE
              status = 'A' AND id = %s
            LIMIT
              1";

            $key_dates_where_stmt = "AND gkd.date_title != 'revisit'";
        }

        //grab grant data
        $sql = $wpdb->prepare( $sql_query, $grant_id );
        $data = $wpdb->get_results( $sql );
        $grant_record = $data[0];

        if ( !empty( $grant_record->id ) && $grant_record->id >= 0 ) {

            //grab sponsor data
            $sql_query = "SELECT
                gs.id, gs.sponsor_name, gs.sponsor_department, gs.sponsor_address,  gs.sponsor_address2,gs.sponsor_city,
                gs.sponsor_state, gs.sponsor_zip, gs.sponsor_country,
                gs.sponsor_url, gs.grant_sponsor_type_id, gst.sponsor_type
              FROM
                " . $wpdb->prefix . "gs_grant_sponsors as gs
              JOIN
                " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings as gscm
              ON
                gscm.sponsor_id = gs.id
              JOIN
                " . $wpdb->prefix . "gs_grant_sponsor_types as gst
              ON
                gs.grant_sponsor_type_id = gst.id
              WHERE
                gscm.grant_id = %s
              LIMIT 1
             ";
            $sql = $wpdb->prepare( $sql_query, $grant_id );
            $data = $wpdb->get_results( $sql, 'OBJECT' );
            $grant_record->sponsor = $data;

            //grab geo_location data
            $sql_query = "SELECT
                gl.id, gl.geo_locale, gl.geo_location
              FROM
                " . $wpdb->prefix . "gs_grant_geo_locations as gl
              JOIN
                " . $wpdb->prefix . "gs_grant_geo_mappings as gm
              ON
                gm.geo_id = gl.id
              WHERE
                gm.grant_id = %s
              ORDER BY gl.geo_locale ASC, gl.geo_location ASC
             ";
            $sql = $wpdb->prepare( $sql_query, $grant_id );
            $data = $wpdb->get_results( $sql, 'OBJECT_K' );
            $grant_record->geo_location = $data;

            //grab contact information
            $sql_query = "SELECT
                gc.id, gc.contact_name, gc.contact_title, gc.contact_org_dept, gc.contact_address1, gc.contact_address2,
                gc.contact_city, gc.contact_state, gc.contact_zip, gc.contact_phone_1, gc.contact_phone_2,
                gc.contact_fax, gc.contact_email_1, gc.contact_email_2, gc.country
              FROM
                " . $wpdb->prefix . "gs_grant_contacts as gc
              JOIN
                " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings as gscm
              ON
                gscm.contact_id = gc.id
              WHERE
                gscm.grant_id = %s
             ";
            $sql = $wpdb->prepare( $sql_query, $grant_id );
            $data = $wpdb->get_results( $sql, 'OBJECT_K' );
            $grant_record->contact_info = $data;

            //grab deadlines
            $sql_query = "SELECT
                gkd.id, gkd.date_title, gkd.year, gkd.month, gkd.date,
                gkd.satisfied, gkd.date_notes
              FROM
                " . $wpdb->prefix . "gs_grant_key_dates as gkd
              WHERE
                gkd.grant_id = %s AND gkd.date_title LIKE 'deadline'
             ORDER BY
                gkd.year, gkd.month, gkd.date";
            $sql = $wpdb->prepare( $sql_query, $grant_id );
            $data = $wpdb->get_results( $sql, 'OBJECT_K' );
            $grant_record->deadline_data = $data;

            //grab key dates
            $sql_query = "SELECT
                gkd.id, gkd.date_title, gkd.year, gkd.month, gkd.date,
                gkd.satisfied, gkd.date_notes
              FROM
                " . $wpdb->prefix . "gs_grant_key_dates as gkd
              WHERE
                gkd.grant_id = %s AND gkd.date_title != 'deadline' " . $key_dates_where_stmt . "
                ORDER BY
                gkd.year, gkd.month, gkd.date";
            $sql = $wpdb->prepare( $sql_query, $grant_id );
            $data = $wpdb->get_results( $sql, 'OBJECT_K' );
            $grant_record->key_dates = $data;

            if ( $data_set == 'editor' ) {
                //grab segments
                $sql_query = "SELECT
                gs.id, gs.segment_title
              FROM
                " . $wpdb->prefix . "gs_grant_segments as gs
              JOIN
                " . $wpdb->prefix . "gs_grant_segment_mappings as gsm
              ON
                gsm.segment_id = gs.id
              WHERE
                gsm.grant_id = %s
              ORDER BY
                gs.segment_title ASC
             ";
                $sql = $wpdb->prepare( $sql_query, $grant_id );
                $data = $wpdb->get_results( $sql, 'OBJECT_K' );
                $grant_record->segment_data = $data;
            }

            //grab program types
            $sql_query = "SELECT
                gp.id, gp.program_title
              FROM
                " . $wpdb->prefix . "gs_grant_programs as gp
              JOIN
                " . $wpdb->prefix . "gs_grant_program_mappings as gpm
              ON
                gpm.program_id = gp.id
              WHERE
                gpm.grant_id = %s
              ORDER BY
                gp.program_title ASC
             ";
            $sql = $wpdb->prepare( $sql_query, $grant_id );
            $data = $wpdb->get_results( $sql, 'OBJECT_K' );
            $grant_record->program_data = $data;

            //grab subjects
            $sql_query = "SELECT
                gs.id, gs.subject_title
              FROM
                " . $wpdb->prefix . "gs_grant_subjects as gs
              JOIN
                " . $wpdb->prefix . "gs_grant_subject_mappings as gsm
              ON
                gsm.subject_id = gs.id
              WHERE
                gsm.grant_id = %s
              ORDER BY
                gs.subject_title ASC
             ";
            $sql = $wpdb->prepare( $sql_query, $grant_id );
            $data = $wpdb->get_results( $sql, 'OBJECT_K' );
            $grant_record->subject_data = $data;

            //grab target populations
            $sql_query = "SELECT
                gt.id, gt.target_title
              FROM
                " . $wpdb->prefix . "gs_grant_targets as gt
              JOIN
                " . $wpdb->prefix . "gs_grant_target_mappings as gtm
              ON
                gtm.target_id = gt.id
              WHERE
                gtm.grant_id = %s
              ORDER BY
                gt.target_title ASC
             ";

            $sql = $wpdb->prepare( $sql_query, $grant_id );
            $data = $wpdb->get_results( $sql, 'OBJECT_K' );
            $grant_record->target_data = $data;

            if ( $data_set == 'editor' ) {
                //grab editor data
                $sql_query = "SELECT
                                et.editor_id, u.display_name, et.timestamp, et.updated_at, et.created_at
                              FROM
                                " . $wpdb->prefix . "gs_editor_transactions as et
                              JOIN
                                " . $wpdb->prefix . "users as u
                              ON
                                et.editor_id = u.id
                              WHERE
                                et.grant_id = %s
                              ORDER BY
                                et.timestamp DESC, et.updated_at DESC, et.created_at DESC
                              LIMIT
                                1
                             ";
                $sql = $wpdb->prepare( $sql_query, $grant_id );
                $data = $wpdb->get_results( $sql, 'OBJECT' );
                $grant_record->last_editor = $data;
            }

//            echo "<pre>";   //debug
//            print_r($grant_record);
//            echo "</pre>";

            return $grant_record;
        }
    }


    /**
     * Function get_geo_locations_list
     * @return $geo_locations_list
     */
    function get_geo_locations_list( $geo_locale = null )
    {
        global $wpdb;

        //TODO: Get Gravity Forms geo location lists to match database geo location lists (including IDs)

        $where_stmt = '';
        if ( !empty($geo_locale) ) {
            $where_stmt = " AND geo_locale = '%s' ";
        }
        $sql_query = "SELECT id, geo_locale, geo_location FROM " . $wpdb->prefix . "gs_grant_geo_locations
                        WHERE geo_location !='' AND geo_location !='All Countries' AND geo_location !='All States' $where_stmt
                        ORDER BY geo_locale ASC, geo_location ASC";
        $sql = $wpdb->prepare( $sql_query, $geo_locale );

        $geo_locations_list = $wpdb->get_results( $sql, 'OBJECT_K' );

        return $geo_locations_list;
    }


    /**
     * Function get_subjects_list
     * @return $subjects_list
     */
    function get_subjects_list()
    {
        global $wpdb;

        $sql_query = "SELECT id, subject_title FROM " . $wpdb->prefix . "gs_grant_subjects
                        WHERE subject_title !=''
                        ORDER BY subject_title ASC";
        $sql = $wpdb->prepare( $sql_query );
        $subjects_list = $wpdb->get_results( $sql, 'OBJECT_K' );

//        echo "<pre>";
//        echo $sql . "\n\n";
//        print_r($subjects_list);
//        echo "</pre>";

        return $subjects_list;
    }
    /**
     * Function get_programs_list
     * @return $programs_list
     */
    function get_programs_list()
    {
        global $wpdb;

        $sql_query = "SELECT id, program_title FROM " . $wpdb->prefix . "gs_grant_programs
                        WHERE program_title !=''
                        ORDER BY program_title ASC";
        $sql = $wpdb->prepare( $sql_query );
        $programs_list = $wpdb->get_results( $sql, 'OBJECT_K' );
        return $programs_list;
    }

    //TODO: create populators for other fields on advanced search (programs, sponsor types)
    /**
     * Function grantselect_populate_form_fields
     * populates certain fields with data from database
     * @params $form
     * @return $form
     */
    static public function grantselect_populate_form_fields ( $form ) {

        foreach ( $form['fields'] as &$field ) {

            //identify if field is to be populated, otherwise skip
            if ( strpos( $field->cssClass, 'geolocation-domestic-checkboxes' ) !== false ) {
                $field_instance = 'geolocation-domestic-checkboxes';
            } elseif ( strpos( $field->cssClass, 'geolocation-foreign-checkboxes' ) !== false ) {
                $field_instance = 'geolocation-foreign-checkboxes';
            } elseif ( strpos( $field->cssClass, 'subject-headings-checkboxes' ) !== false ) {
                $field_instance = 'subject-headings-checkboxes';
            } elseif ( strpos( $field->cssClass, 'programtype-checkboxes' ) !== false ) {
                $field_instance = 'programtype-checkboxes';
            } else {
                continue;
            }

            $field_id = $field->id;

            switch ($field_instance) {
                case 'geolocation-domestic-checkboxes':

                    $geolocations = self::get_geo_locations_list( 'domestic' );

//                    echo "<pre>";
//                    print_r($geolocations);
//                    echo "</pre>";
//                    die;

                    $choices = array();
                    $inputs = array();
                    $choices[] = array( 'text' => 'All U.S. States and Territories', 'value'=> '1' );
                    $inputs[] =  array( 'label' => 'All U.S. States and Territories', 'id' => "{$field_id}.1" );
                    $input_id = 2;  //initialize
                    foreach ( $geolocations as $geolocation ) {
                        //skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
                        if ( $input_id % 10 == 0 ) {
                            $input_id++;
                        }
                        $choices[] = array( 'text' => $geolocation->geo_location, 'value' => $geolocation->id );
                        $inputs[] = array( 'label' => $geolocation->geo_location, 'id' => "{$field_id}.{$input_id}" );
                        $input_id++;
                    }
                    $field->choices = $choices;
                    $field->inputs = $inputs;

                    break;
                case 'geolocation-foreign-checkboxes':

                    $geolocations = self::get_geo_locations_list( 'foreign' );

//                    echo "<pre>";
//                    print_r($geolocations);
//                    echo "</pre>";
//                    die;

                    $choices = array();
                    $inputs = array();
                    $choices[] = array( 'text'=>'All Countries', 'value'=>247 );
                    $inputs[] =  array( 'label' => 'All Countries', 'id' => "{$field_id}.1" );
                    $input_id = 2;  //initialize
                    foreach ( $geolocations as $geolocation ) {
                        //skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
                        if ( $input_id % 10 == 0 ) {
                            $input_id++;
                        }
                        $choices[] = array( 'text' => $geolocation->geo_location, 'value' => $geolocation->id );
                        $inputs[] = array( 'label' => $geolocation->geo_location, 'id' => "{$field_id}.{$input_id}" );
                        $input_id++;
                    }
                    $field->choices = $choices;
                    $field->inputs = $inputs;

                    break;
                case 'subject-headings-checkboxes':

                    $subjects = self::get_subjects_list();

//                    echo "<pre>";
//                    print_r($subjects);
//                    echo "</pre>";

                    $choices = array();
                    $inputs = array();
                    $input_id = 1;  //initialize
                    foreach ( $subjects as $subject ) {
                        //skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
                        if ( $input_id % 10 == 0 ) {
                            $input_id++;
                        }
                        $choices[] = array( 'text' => $subject->subject_title, 'value' => $subject->id );
                        $inputs[] = array( 'label' => $subject->subject_title, 'id' => "{$field_id}.{$input_id}" );
                        $input_id++;
                    }
                    $field->choices = $choices;
                    $field->inputs = $inputs;

                    break;
                case 'programtype-checkboxes':
                    $programs = self::get_programs_list();
                    $choices = array();
                    $inputs = array();
                    $input_id = 1;  //initialize
                    foreach ( $programs as $program ) {
                        //skipping index that are multiples of 10 (multiples of 10 create problems as the input IDs)
                        if ( $input_id % 10 == 0 ) {
                            $input_id++;
                        }
                        $choices[] = array( 'text' => $program->program_title, 'value' => $program->id );
                        $inputs[] = array( 'label' => $program->program_title, 'id' => "{$field_id}.{$input_id}" );
                        $input_id++;
                    }
                    $field->choices = $choices;
                    $field->inputs = $inputs;
                    break;
            }

        }

        return $form;

    }


    /**
     * Function grantselect_display_grant_details
     * Outputs grant record details for display on website
     * @params $grant_details
     *         $type_of_search_result
     *         $search_mode = "access" or "editor"
     * @return $res
     */
    function grantselect_display_grant_details( $grant_details, $type_of_search_result="search-results", $search_mode="access" ) {
        
        ob_start();
        ?>
        <?php if ($grant_details == null):?>
            <div id="content">
                <a href="javascript:history.back();">Back to search results</a>
            </div>
            <div class="permission-modal" style="display:none;">
                <div class="text-center">
                    <p>The grant you are attempting to access does not exist.</p>
                    <p>
                        <a href="javascript:history.back();" class="button back-btn">Back to search results</a>
                    </p>
                </div>
            </div>
            <script>
                jQuery(document).ready(function(){
                    jQuery(".permission-modal").dialog({'title':'Access', 'modal':true, 'width': '800px'});
                });
            </script>
        <?php
            $res = ob_get_contents();
            ob_end_clean();

            return $res;
        ?>
        <?php endif;?>
        <!-- Begin Content Column -->
        <div id="content">
            <!--<a href="<?php echo '/' . $search_mode . '/' . $type_of_search_result; ?>?back=1">Back to search results</a>-->
            <a href="javascript:history.back();">Back to search results</a>
            <div class="pull-right">
                <a href="<?php echo self::get_link(false) . '/access/grant-details/?download=pdf&gid=' . $grant_details->id;?>" class="download-pdf ">Download PDF</a>&nbsp;|&nbsp;<a href="#" class="print-grant-detail">Print</a>&nbsp;|&nbsp;<a href="#" class="share-grant-detail">Share</a>  
                <div class="share-grant-dialog" style="display:none;">
                    <div class="share-section">
                        <input type="text" name="sharing" id="sharing" value="" placeholder="Email Address"/>
                        <p class="err-msg"></p>
                        <label>Message (optional)</label>
                        <textarea name="sharing_content" id="sharing_content"></textarea>
                        <div class="text-center"><a href="#" class="sharing-grant button" data="sharing">Share</a></div>
                        <p class="success-msg"></p>
                    </div>
                </div>
            </div>
            <div id="content_wrapper">
                <div id="content_left">
                    <!-- Intro Text -->
                    <div id="intro">
                        <h3>
                            <strong><?php echo stripslashes($grant_details->title); ?></strong>
                        </h3>
                    </div>
                    <!--intro-->
                    <!-- Sponsor info -->
                    <div class="tabbed_wrapper">
                        <h3 id="tabbed_header">Sponsor Info</h3>
                        <div class="tabbed_header">
                            <?php if (!empty($grant_details->sponsor)) : ?>
                                <?php foreach ($grant_details->sponsor as $k => $v) : ?>
                                    <p>
                                        <?php if (!empty($v->sponsor_name)) : ?>
                                            <?=stripslashes($v->sponsor_name);?><br />
                                        <?php endif; ?>

                                        <?php if (!empty($v->sponsor_department)) : ?>
                                            <?=stripslashes($v->sponsor_department);?><br />
                                        <?php endif ?>

                                        <?php if (!empty($v->sponsor_address)) : ?>
                                            <?=stripslashes($v->sponsor_address); ?>
                                        <?php endif; ?>

                                        <?php if (!empty($v->sponsor_address2)) : ?>
                                            <?=stripslashes($v->sponsor_address2); ?>
                                        <?php endif; ?>

                                        <?php if (!empty($v->sponsor_address) || !empty($v->sponsor_address2)) : ?><br /><?php endif; ?>

                                        <?php if (!empty($v->sponsor_city)):?><?=stripslashes($v->sponsor_city);?><?php endif;?><?php if (!empty($v->sponsor_city) && !empty($v->sponsor_state)) : ?>,<?php endif; ?>

                                        <?php if (!empty($v->sponsor_state)) : ?>
                                            <?=stripslashes($v->sponsor_state); ?>
                                        <?php endif; ?>

                                        <?php if (!empty($v->sponsor_zip)) : ?>
                                            <?=stripslashes($v->sponsor_zip); ?><br />
                                        <?php endif; ?>

                                        <?php if (!empty($v->sponsor_country)) : ?>
                                            <?=stripslashes($v->sponsor_country); ?><br />
                                        <?php endif; ?>

                                        Website:
                                        <?php if (!empty($v->sponsor_url)) : ?>
                                            <a href="<?=$v->sponsor_url;?>" target="_new">
                                                <?=$v->sponsor_url;?></a>
                                        <?php else : ?>
                                            (not specified)
                                        <?php endif; ?>
                                        <br />

                                        Type:
                                        <?php if (!empty($v->sponsor_type)) : ?>
                                            <?=$v->sponsor_type;?>
                                        <?php else : ?>
                                            (not specified)
                                        <?php endif; ?>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- end sponsor info -->

                    <!-- Grant program info -->
                    <div class="tabbed_wrapper">
                        <h3 id="tabbed_header">Grant Info</h3>
                        <div class="tabbed_header">
                            <p><strong><?=stripslashes($grant_details->title);?></strong>
                            </p>
                            <p><strong>Program URL</strong>:
                                <?php if(!empty($grant_details->grant_url_1)) : ?>
                                    <a href="<?php echo $grant_details->grant_url_1; ?>" target="_new"><?php echo $grant_details->grant_url_1; ?></a>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </p>
                            <p>
                                <strong>Amount</strong>:
                                <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                                    <?=number_format($grant_details->amount_min, 2); ?> - <?=number_format($grant_details->amount_max, 2); ?> <?=$grant_details->amount_currency; ?>
                                <?php endif; ?>

                                <?php if (empty($grant_details->amount_min) && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                                    <?=number_format($grant_details->amount_max, 2); ?> <?=$grant_details->amount_currency; ?>
                                <?php endif; ?>

                                <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min == '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                                    Up to&nbsp;<?=number_format($grant_details->amount_max, 2); ?> <?=$grant_details->amount_currency; ?>
                                <?php endif; ?>

                                <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && empty($grant_details->amount_max)) : ?>
                                    <?=number_format($grant_details->amount_min, 2); ?> <?=$grant_details->amount_currency; ?>
                                <?php endif; ?>

                                <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max == '0.00') : ?>
                                    Up to&nbsp;<?=number_format($grant_details->amount_min, 2); ?> <?=$grant_details->amount_currency; ?>
                                <?php endif; ?>

                                <?php if (empty($grant_details->amount_min) && empty($grant_details->amount_max)) : ?>
                                    (not specified)
                                <?php endif; ?>
                            </p>
                            <p>
                                <strong>Description</strong>:<br />
                                <?php if (!empty($grant_details->description)) : ?>
                                    <?=nl2br(stripslashes($grant_details->description)); ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </p>
                            <p>
                                <strong>Requirements</strong>:<br />
                                <?php if (!empty($grant_details->requirements)) : ?>
                                    <?=nl2br(stripslashes($grant_details->requirements)); ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </p>
                            <p>
                                <strong>Geographic Focus</strong>:<br />
                                <?php if (!empty($grant_details->geo_location)): ?>
                                    <?php foreach($grant_details->geo_location as $k=>$v):?>
                                        <?=stripslashes($v->geo_location);?><br>
                                    <?php endforeach;?>
                                <?php else: ?>
                                    (not specified)
                                <?php endif; ?>
                            </p>
                            <p>
                                <strong>Restrictions</strong>:<br />
                                <?php if (!empty($grant_details->restrictions)) : ?>
                                    <?=nl2br(stripslashes($grant_details->restrictions)); ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </p>
                            <p>
                                <strong>Samples</strong>:<br />
                                <?php if (!empty($grant_details->samples)) : ?>
                                    <?=nl2br(stripslashes($grant_details->samples)); ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="clear">

                        </div>
                    </div>
                    <!-- end grant program info -->
                    <!-- Contact info -->
                    <div class="tabbed_wrapper">
                        <h3 id="tabbed_header">Contact Info</h3>
                        <div class="tabbed_header">
                            <?php if(!empty($grant_details->contact_info)) : ?>
                                <?php foreach ($grant_details->contact_info as $k => $v) : ?>
                                    <p>
                                    <?php if (!empty($v->contact_name)) : ?>
                                        <?=stripslashes($v->contact_name);?>
                                    <?php endif;?>

                                    <?php if (!empty($v->contact_title) && !empty($v->contact_name)):?>, <?php endif;?>

                                    <?php if (!empty($v->contact_title)) : ?>
                                        <?=stripslashes($v->contact_title); ?>
                                    <?php endif; ?>

                                    <?php if (!empty($v->contact_title) || !empty($v->contact_name)) : ?><br /><?php endif;?>

                                    <?php if (!empty($v->contact_org_dept)) : ?>
                                        <?=stripslashes($v->contact_org_dept); ?><br />
                                    <?php endif; ?>

                                    <?php if (!empty($v->contact_address1) and !empty($v->contact_address2)) : ?>
                                        <?=stripslashes($v->contact_address1); ?>,
                                    <?php endif; ?>

                                    <?php if (!empty($v->contact_address1) and empty($v->contact_address2)) : ?>
                                        <?=stripslashes($v->contact_address1); ?>
                                    <?php endif; ?>

                                    <?php if(!empty($v->contact_address2)) : ?>
                                        <?=stripslashes($v->contact_address2); ?>
                                    <?php endif; ?>

                                    <?php if (!empty($v->contact_address1) || !empty($v->contact_address2)) : ?><br /><?php endif;?>

                                    <?php if (!empty($v->contact_city)) : ?>
                                        <?=stripslashes($v->contact_city); ?>,
                                    <?php endif; ?>

                                    <?php if (!empty($v->contact_state)) : ?>
                                        <?=stripslashes($v->contact_state); ?>
                                    <?php endif; ?>

                                    <?php if (!empty($v->contact_zip)) : ?>
                                        <?=stripslashes($v->contact_zip); ?>
                                    <?php endif; ?>

                                    <?php if(!empty($v->contact_state) || !empty($v->contact_zip)) : ?><br /><?php endif; ?>

                                    <?php
                                    $phones = '';
                                    if (!empty($v->contact_phone_1)) {
                                        $phones = stripslashes($v->contact_phone_1);
                                    }
                                    if (!empty($v->contact_phone_2)) {
                                        if ($phones != '') {
                                            $phones .= ', ' . stripslashes($v->contact_phone_2);
                                        }
                                        else {
                                            $phones = stripslashes($v->contact_phone_2);
                                        }
                                    }
                                    echo $phones . '<br />';
                                    ?>

                                    <?php if (!empty($v->contact_fax)) : ?>
                                        fax: <?=stripslashes($v->contact_fax); ?><br />
                                    <?php endif; ?>

                                    <?php if (!empty($v->contact_email_1)) : ?>
                                        email: <a href="mailto:<?=$v->contact_email_1; ?>"><?=$v->contact_email_1; ?></a>
                                    <?php endif; ?>
                                <?php endforeach;?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- end contact info -->
                    <!-- Deadline info starts -->
                    <div class="tabbed_wrapper">
                        <h3 id="tabbed_header">Deadlines</h3>
                        <div class="tabbed_header">
                            <ul>
                                <?php if (!empty($grant_details->deadline_data)) : ?>
                                    <?php foreach ( $grant_details->deadline_data as $key => $value ) : ?>
                                        <?php
                                        if ($value->satisfied == 'R') {
                                            $satisfied = '(Satisfied by: Received)';
                                        }
                                        elseif ($value->satisfied == 'P') {
                                            $satisfied = '(Satisfied by: Postmarked)';
                                        }
                                        ?>
                                        <li><?=date('F',mktime(0, 0, 0, $value->month)) . ' ' . $value->date?>&nbsp;<?=$satisfied;?></li>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Deadline info ends -->
                    <!-- Key dates starts -->
                    <div class="tabbed_wrapper">
                        <h3 id="tabbed_header">Key Dates</h3>
                        <div class="tabbed_header">
                            <ul>
                                <?php if (!empty($grant_details->key_dates)) : ?>
                                    <?php foreach ( $grant_details->key_dates as $key => $value ) : ?>
                                        <?php
                                        if ($value->date_title == 'LOI') {
                                            $value->date_title = 'Letter of Intent';
                                        }
                                        else if ($value->date_title =='Board Mtg') {
                                            $value->date_title = 'Board Meeting';
                                        }
                                        else if ($value->date_title == 'Mini Proposal') {
                                            $value->date_title = 'Mini/Pre-Proposal';
                                        }
                                        else if ($value->date_title == 'Web or Live Conference') {
                                            $value->date_title = 'Informational Session/Workshop';
                                        }
                                        else if ($value->date_title =='Semifinals') {
                                            $value->date_title = 'Notification of Awards';
                                        }
                                        ?>
                                        <li><?=ucwords($value->date_title) .' - '. date('F',mktime(0, 0, 0, $value->month)) . ' ' . $value->date;?></li>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    <!-- Key dates ends -->
                    <!-- Segment Codes starts -->
                    <!--<div class="tabbed_wrapper">
                    <h3 id="tabbed_header">Segment Codes</h3>
                      <div class="tabbed_header">
                        <ul>
                          <?php //if(!empty($GrantSegmentDataList)) : ?>
					          <?php //foreach($GrantSegmentDataList AS $key => $value) : ?>
					              <li><?//=ucwords($value->segment_title);?></li>
					          <?php //endforeach; ?>
				          <?//php else : ?>
                              (not specified)
				          <?//php endif; ?>
                        </ul>
                      </div>
                  </div>-->

                    <!-- Segment Codes ends -->
                    <!-- Program Codes starts -->
                    <div class="tabbed_wrapper">
                        <h3 id="tabbed_header">Programs</h3>
                        <div class="tabbed_header">
                            <ul>
                                <?php if (!empty($grant_details->program_data)) : ?>
                                    <?php foreach ($grant_details->program_data as $key => $value) : ?>
                                        <li><?=ucwords(stripslashes($value->program_title));?></li>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Program Codes ends -->
                    <!-- Subjects starts -->
                    <div class="tabbed_wrapper">
                        <h3 id="tabbed_header">Subjects</h3>
                        <div class="tabbed_header">
                            <ul>
                                <?php if (!empty($grant_details->subject_data)) : ?>
                                    <?php foreach ($grant_details->subject_data as $key => $value) : ?>
                                        <li><?=ucwords(stripslashes($value->subject_title));?></li>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif;?>
                            </ul>
                        </div>
                    </div>
                    <!-- Subjects ends -->
                    <!-- Target Populations starts -->
                    <div class="tabbed_wrapper">
                        <h3 id="tabbed_header">Target Populations</h3>
                        <div class="tabbed_header">
                            <ul>
                                <?php if (!empty($grant_details->target_data)) : ?>
                                    <?php foreach ($grant_details->target_data as $key => $value) : ?>
                                        <li><?=ucwords(stripslashes($value->target_title));?></li>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif;?>
                            </ul>
                        </div>
                    </div>

                    <!-- Target Populations ends -->
                </div><!-- end content_left-->
                <div class="clear">
                </div>
            </div><!-- end content_wrapper-->
        </div><!-- End Content Column -->

        <?php
        $res = ob_get_contents();
        ob_end_clean();

        return $res;
    }


    /**
     * Function grantselect_access_search_javascript
     * Inserts needed Javascript for GrantSelect Advanced Search, Quick Search, and Book Production pages
     * Used for Subject Headings checkbox field
     */
    function grantselect_access_search_javascript() {
        if ( is_page ('163') || is_page ('165') || is_page ('796') || is_page ('763') || is_page('167') || is_page('169') ) { // either access quick search page, access advanced search page, editor quick search page, or book production page
            ?>
            <script type="text/javascript">
                function add_all_checked_subjects()
                {
                    var all_checkboxes_val  = [];
                    var all_checkboxes_id  = [];
                    var all_checkboxes_text = [];
                    var count               = 0;
                    jQuery(".subject-box input:checkbox:checked").each(function(){
                        all_checkboxes_val[count]  = jQuery(this).val();
                        all_checkboxes_id[count] = jQuery(this).attr('id');
                        label_id = all_checkboxes_id[count].replace("choice","label");
                        all_checkboxes_text[count] = jQuery("#"+label_id).text();
                        count++;
                    });
                    jQuery("#selected_subjects").empty();
                    for (i = 0; i < all_checkboxes_val.length; i++) {
                        jQuery("#selected_subjects").append( jQuery('<option value="' + all_checkboxes_val[i] + '"><b>' + all_checkboxes_text[i] + '</b></option>'));
                    }
                }

                jQuery( ".subject-box input:checkbox" )
                    .change(function () {
                        add_all_checked_subjects();
                    });

                function clear_all_checked_subjects()
                {
                    jQuery(".subject-box input:checkbox:checked").each(function(){
                        jQuery(this).prop( "checked", false );
                    });
                    jQuery("#selected_subjects").empty();
                }
            </script>
            <script type="text/javascript">
                function filter_subjects( filterType, filterParam )
                {
                    var checkbox_id;
                    var checkbox_text;
                    var regex_string;
                    var regex_options = 'i';

                    if ( filterType == 'alpha' ) {
                        regex_string = '^' + filterParam;
                    } else if ( filterType == 'clear' ) {
                        regex_string = '.*';
                    } else {
                        regex_string = filterParam;
                        if ( jQuery("#case_sensitive:checkbox:checked").length > 0) {
                            regex_options = '';
                        }
                    }
                    regex_exp = new RegExp( regex_string, regex_options );

                    jQuery(".subject-box .ginput_container label").each(function(){
                        checkbox_text = jQuery(this).text();
                        checkbox_id = jQuery(this).attr('id').replace("label","gchoice");
                        if ( regex_exp.test(checkbox_text) ) {
                            jQuery("."+checkbox_id).show();
                        } else {
                            jQuery("."+checkbox_id).hide();
                        }
                    });
                }
            </script>
            <?php
            if ( !empty(absint($_GET['ppeid'])) || !empty(absint($_GET['bpid'])) ) {
            ?>
                <script type="text/javascript">
                    jQuery(document).ready(function() {
                            add_all_checked_subjects();
                    });
                </script>
            <?php
            }
        }
    }


    /**
     * Function grantselect_prepopulate_forms
     * Pre-populates Search form fields with options selected in previous search
     * @params $form
     * @return $form
     */
    function grantselect_prepopulate_forms ( $form ) {

        //if ppeid is unspecified or invalid, skip this and just return the default form values
        $previous_entry_id = absint($_GET['ppeid']);
        if (empty($previous_entry_id)) {
            return $form;
        } else {
            $entry = GFAPI::get_entry($previous_entry_id);
//            echo "<pre>";
//            print_r($entry);
//            echo "</pre>";
        }

        //make sure data being accessed is owned by current user
        $created_by = 'xXxXx';
        if (!is_wp_error($entry)) {
            $created_by = $entry['created_by'];
        }
        $current_user_id = 0;
        if (is_user_logged_in()){
            $current_user_id = get_current_user_id();
        }else if (isset($_SESSION['guest_user_id'])){
            $current_user_id = $_SESSION['guest_user_id'];
        }
        
        if ($created_by != null && $current_user_id != $created_by) {
            return $form;
        }

        $forms_to_prepopulate = array(1,2,6,7,8);
        if( in_array( $form["id"], $forms_to_prepopulate ) ) {

            switch ( $form['id'] ) {
                case 1: //access quick search
                    $text_fields        = array(1);
                    $multiselect_fields = array(2,3);
                    break;
                case 2: //access advanced search
                    $text_fields        = array(1,6,7,8,9,10,11,12,13,14,15,17);
                    $multiselect_fields = array(2,3,5,16,18,19);
                    break;
                case 6: //editor quick search
                    $text_fields        = array(1);
                    $multiselect_fields = array(2,3);
                    break;
                case 7: //editor title search
                    $text_fields        = array(9,10);
                    $multiselect_fields = array();
                    break;
                case 8: //editor sponsor search
                    $text_fields        = array(8);
                    $multiselect_fields = array();
                    break;
            }

            foreach( $form['fields'] as &$field ) {
                if( in_array( $field->id, $text_fields ) ) {
                    $field["defaultValue"] = $entry[ $field->id ];
                } elseif ( in_array( $field->id, $multiselect_fields ) ) {
                    foreach( $field->choices as $index=>&$choice ) {
                        if( $entry[ $field->inputs[$index]["id"] ] === $choice['value'] ) {
                            $choice['isSelected'] = true;
                        }
                    }
                }
            }
        }
        return $form;
    }
}