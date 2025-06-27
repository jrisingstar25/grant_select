<?php

// Make sure Gravity Forms is active and already loaded.
if (!class_exists('GFForms')) {
    die();
}

GFForms::include_feed_addon_framework();

/**
 * GrantSelectReportsAddOn
 *
 * @copyright   Copyright (c) 2020-2021, GrantSelect
 * @since       1.0
 */
class GrantSelectReportsAddOn extends GFFeedAddOn {

    protected $_version = GF_GRANTSELECT_REPORTS_ADDON_VERSION;
    protected $_min_gravityforms_version = '2.4.20';
    protected $_slug = 'grantselect-reports';
    protected $_path = 'grantselect-reports/grantselect-reports.php';
    protected $_full_path = __FILE__;
    protected $_title = 'GrantSelect Report Functionality';
    protected $_short_title = 'GrantSelect Report Functionality';

    const MONTH_ARRAY = array(
            1=>"Jan",
            2=>"Feb",
            3=>"Mar",
            4=>"Apr",
            5=>"May",
            6=>"Jun",
            7=>"Jul",
            8=>"Aug",
            9=>"Sep",
            10=>"Oct",
            11=>"Nov",
            12=>"Dec"
    );

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

    // function for reports shortcode
    public function grantselect_report( $atts ) {

        //start session if one hasn't been started yet
        if (!session_id()) {
            session_start();
        }

        //update session data
        $current_url = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']); //strip off query string
        $current_url = rtrim($current_url,"/"); //strip off any trailing slash
        $url_components = explode( "/", $current_url );
        $report_slug = array_pop($url_components);

        if ( !empty($_POST) || empty($_GET['sb']) ) {
            unset ( $_SESSION[$report_slug]['sb'] );
        } else {
            $_SESSION[$report_slug]['sb'] = $_GET['sb'];
        }

        if ( !empty($_POST) || empty($_GET['sd']) ) {
            unset ( $_SESSION[$report_slug]['sd'] );
        } else {
            $_SESSION[$report_slug]['sd'] = $_GET['sd'];
        }

        if ( !empty($_POST) || empty($_GET['pn']) ) {
            unset ( $_SESSION[$report_slug]['pn'] );
        } else {
            $_SESSION[$report_slug]['pn'] = $_GET['pn'];
        }

        $report_type = $atts['report_type'];
        $display_content = ''; //initialize

        //display report
        switch ($report_type) {
            case 'record_counts':

                if ( !empty($_POST['display_mode']) ||
                     !empty($_POST['segment']) ||
                     !empty($_POST['older_newer']) ||
                     !empty($_POST['date_month']) ||
                     !empty($_POST['date_day']) ||
                     !empty($_POST['date_year']) ) {
                    $_SESSION['record-counts']['display_mode'] = $_POST['display_mode'];
                    $_SESSION['record-counts']['segment'] = $_POST['segment'];
                    $_SESSION['record-counts']['older_newer'] = $_POST['older_newer'];
                    $_SESSION['record-counts']['date_month'] = $_POST['date_month'];
                    $_SESSION['record-counts']['date_day'] = $_POST['date_day'];
                    $_SESSION['record-counts']['date_year'] = $_POST['date_year'];
                }

                $display_content .= self::display_report_form_record_counts();
                $display_content .= "<div class='grantselect-report'>";
                if ( !empty($_POST) || !empty($_SESSION['record-counts']['sb']) || !empty($_SESSION['record-counts']['sd']) || !empty($_SESSION['record-counts']['pn']) ) {
                    $display_content .= self::display_report_results_record_counts();
                }
                $display_content .= "</div>";
                break;

            case 'sponsor_counts':

                if ( !empty($_POST['display_mode']) ||
                    !empty($_POST['type']) ||
                    !empty($_POST['older_newer']) ||
                    !empty($_POST['date_month']) ||
                    !empty($_POST['date_day']) ||
                    !empty($_POST['date_year']) ) {
                    $_SESSION['sponsor-counts']['display_mode'] = $_POST['display_mode'];
                    $_SESSION['sponsor-counts']['type'] = $_POST['type'];
                    $_SESSION['sponsor-counts']['older_newer'] = $_POST['older_newer'];
                    $_SESSION['sponsor-counts']['date_month'] = $_POST['date_month'];
                    $_SESSION['sponsor-counts']['date_day'] = $_POST['date_day'];
                    $_SESSION['sponsor-counts']['date_year'] = $_POST['date_year'];
                }

                $display_content .=  self::display_report_form_sponsor_counts();
                $display_content .= "<div class='grantselect-report'>";
                if ( !empty($_POST) || !empty($_SESSION['sponsor-counts']['sb']) || !empty($_SESSION['sponsor-counts']['sd']) || !empty($_SESSION['sponsor-counts']['pn']) ) {
                    $display_content .= self::display_report_results_sponsor_counts();
                }
                $display_content .= "</div>";
                break;

            case 'subject_headings':

                if ( !empty($_POST['subject_alpha']) ||
                    !empty($_POST['num_used']) ) {
                    $_SESSION['subject-headings']['subject_alpha'] = $_POST['subject_alpha'];
                    $_SESSION['subject-headings']['num_used'] = $_POST['num_used'];
                }

//                echo "SESSION:<br>";
//                echo "<pre>";
//                print_r($_SESSION);
//                echo "</pre>";

                $display_content .=  self::display_report_form_subject_headings();
                $display_content .= "<div class='grantselect-report'>";
                if ( !empty($_POST) || !empty($_SESSION['subject-headings']['sb']) || !empty($_SESSION['subject-headings']['sd']) || !empty($_SESSION['subject-headings']['pn']) ) {
                    $display_content .= self::display_report_results_subject_headings();
        }
                $display_content .= "</div>";
                break;

            case 'unique_sponsors':

                if ( !empty($_POST['display_mode']) ||
                    !empty($_POST['segment']) ||
                    !empty($_POST['older_newer']) ||
                    !empty($_POST['date_month']) ||
                    !empty($_POST['date_day']) ||
                    !empty($_POST['date_year']) ) {
                    $_SESSION['unique-sponsors']['display_mode'] = $_POST['display_mode'];
                    $_SESSION['unique-sponsors']['segment'] = $_POST['segment'];
                    $_SESSION['unique-sponsors']['older_newer'] = $_POST['older_newer'];
                    $_SESSION['unique-sponsors']['date_month'] = $_POST['date_month'];
                    $_SESSION['unique-sponsors']['date_day'] = $_POST['date_day'];
                    $_SESSION['unique-sponsors']['date_year'] = $_POST['date_year'];
                }

                $display_content .=  self::display_report_form_unique_sponsors();
                $display_content .= "<div class='grantselect-report'>";
                if ( !empty($_POST) || !empty($_SESSION['unique-sponsors']['sb']) || !empty($_SESSION['unique-sponsors']['sd']) || !empty($_SESSION['unique-sponsors']['pn']) ) {
                    $display_content .= self::display_report_results_unique_sponsors();
                }
                $display_content .= "</div>";
                break;
            case 'suspended_records':
                $display_content .= "<div class='grantselect-report'>";
                $display_content .=  self::display_report_results_suspended_records();
                $display_content .= "</div>";
                break;
            case 'ready_for_review':
                $display_content .= "<div class='grantselect-report'>";
                $display_content .=  self::display_report_results_ready_for_review_records();
                $display_content .= "</div>";
                break;
            case 'revisit':
                $display_content .= "<div class='grantselect-report'>";
                $display_content .= self::display_report_results_revisit();
                $display_content .= "</div>";
                break;
            case 'pending_records':
                $display_content .= "<div class='grantselect-report'>";
                $display_content .=  self::display_report_results_pending_records();
                $display_content .= "</div>";
                break;
            case 'subject_headings_instances':
                //TODO: Find out from client how this report is different from Subject Headings report
                $display_content .= "<div class='grantselect-report'>";
//                $display_content .=  self::display_report_results_subject_headings_instances();
                $display_content .= "</div>";
                break;
            case 'missing_sponsors':
                $display_content .= "<div class='grantselect-report'>";
                $display_content .=  self::display_report_results_missing_sponsors();
                $display_content .= "</div>";
                break;
            case 'users_search_criteria':
                if ( !empty($_POST['date_range']) ||
                     !empty($_POST['date_month_from']) ||
                     !empty($_POST['date_day_from']) ||
                     !empty($_POST['date_year_from']) ||
                     !empty($_POST['date_month_to']) ||
                     !empty($_POST['date_day_to']) ||
                     !empty($_POST['date_year_to']) ||
                     !empty($_POST['date_report']) ) {
                    $_SESSION['users-search-criteria']['date_range']        = $_POST['date_range'];
                    $_SESSION['users-search-criteria']['date_day_from']     = $_POST['date_day_from'];
                    $_SESSION['users-search-criteria']['date_month_from']   = $_POST['date_month_from'];
                    $_SESSION['users-search-criteria']['date_year_from']    = $_POST['date_year_from'];
                    $_SESSION['users-search-criteria']['date_day_to']       = $_POST['date_day_to'];
                    $_SESSION['users-search-criteria']['date_month_to']     = $_POST['date_month_to'];
                    $_SESSION['users-search-criteria']['date_year_to']      = $_POST['date_year_to'];
                    $_SESSION['users-search-criteria']['report']            = $_POST['report'];
                }

                $display_content .= self::display_report_form_users_search_criteria();
                $display_content .= "<div class='grantselect-report'>";
                if ( !empty($_POST) ||
                     !empty($_SESSION['users-search-criteria']['sb']) ||
                     !empty($_SESSION['users-search-criteria']['sd']) ||
                     !empty($_SESSION['users-search-criteria']['pn']) ) {
                    $display_content .=  self::display_report_results_users_search_criteria();
                }
                $display_content .= "</div>";
                break;
        }

        return $display_content;
    }


    /**
     * Function display_report_form_record_counts
     * @return $content
     */
    function display_report_form_record_counts()
    {
        global $wpdb;
        $display_mode = filter_var ( $_SESSION['record-counts']['display_mode'], FILTER_SANITIZE_STRIPPED );
        if ( is_array($_SESSION['record-counts']['segment']) ) {
            $segment      = filter_var_array ( $_SESSION['record-counts']['segment'], FILTER_SANITIZE_STRIPPED );
        } else {
            $segment = array();
        }
        $older_newer  = filter_var ( $_SESSION['record-counts']['older_newer'], FILTER_SANITIZE_STRIPPED );
        $date_month   = filter_var ( $_SESSION['record-counts']['date_month'], FILTER_SANITIZE_STRIPPED );
        $date_day     = filter_var ( $_SESSION['record-counts']['date_day'], FILTER_SANITIZE_STRIPPED );
        $date_year    = filter_var ( $_SESSION['record-counts']['date_year'], FILTER_SANITIZE_STRIPPED );

        $content = '<p class="record-description">Choose your options and submit:</p>';

        $content .= '<form method="post" action="">';
        $content .= '<select name="display_mode" size="1">';
        $content .= '<option value="count"';
        if ($display_mode == 'count') {
            $content .= ' selected=""';
        }
        $content .= '>Count Records</option>';
        $content .= '<option value="display"';
        if ($display_mode == 'display') {
            $content .= ' selected=""';
        }
        $content .= '>Count and List *</option>';
        $content .= '</select>';

        $content .= '<select name="older_newer" size="1">';
        $content .= '<option value="newer"';
        if ($older_newer == 'newer') {
            $content .= ' selected=""';
        }
        $content .= '>Updated On or Later Than</option>';
        $content .= '<option value="older"';
        if ($older_newer == 'older') {
            $content .= ' selected=""';
        }
        $content .= '>Older Than</option>';
        $content .= '</select>';

        $content .= '<select name="date_month">';
        for ( $i=1; $i<=12; $i++ ) {
            $content .= "<option value=\"$i\"";
            if (intval($date_month) == $i) {
                $content .= ' selected=""';
            }
            $content .= ">" . self::MONTH_ARRAY[$i] . "</option>";
        }
        $content .= '</select>';

        $content .= '<select name="date_day">';
        for ( $i=1; $i<=31; $i++ ) {
            $content .= "<option value=\"$i\"";
            if (intval($date_day) == $i) {
                $content .= ' selected=""';
            }
            $content .= ">$i</option>";
        }
        $content .= '</select>';

        $current_year = intval(date("Y"));
        $content .= '<select name="date_year">';
        // $sql = "SELECT g.updated_at FROM {$wpdb->prefix}gs_grants g, {$wpdb->prefix}gs_grant_segments s, {$wpdb->prefix}gs_grant_segment_mappings m
        // WHERE
        // g.id = m.grant_id AND
        // g.status = 'A' AND
        // s.id = m.segment_id
        // order by g.updated_at limit 1";
        //ignore segment
        $sql = "SELECT g.updated_at FROM {$wpdb->prefix}gs_grants g
        WHERE
        g.status = 'A'
        order by g.updated_at limit 1";
        $row = $wpdb->get_row($sql);
        $earliest_year = $current_year - 5;
        if ($row){
            $last_updated = intval(date("Y", strtotime($row->updated_at)));
            if ($earliest_year > $last_updated){
                $earliest_year = $last_updated;
            }
        }
        // $sql = "SELECT created_at FROM {$wpdb->prefix}gs_grants ORDER BY created_at ASC LIMIT 1";
        // $row = $wpdb->get_row($sql);
        // $earliest_year = $current_year - 5;
        // if ($row){
        //     $last_updated = intval(date("Y", strtotime($row->created_at)));
        //     if ($earliest_year > $last_updated){
        //         $earliest_year = $last_updated;
        //     }
        // }
        for ( $i=$current_year; $i>=$earliest_year; $i-- ) {
            $content .= '<option value="' . ($i) . '"';
            if (intval($date_year) == ($i)) {
                $content .= ' selected=""';
            }
            $content .= '>' . ($i) . '</option>';
        }
        
        $content .= '</select>';

        $content .= '<select class="counts-select" name="segment[]" size="5" multiple="multiple">';
        $content .= '<option value="all"';
        if (in_array('all', $segment)) {
            $content .= ' selected=""';
        }
        $content .= '>(All Records)</option>';
        $segment_list = GrantSelectRecordsAddOn::get_segment_list();
        foreach ( $segment_list as $key => $value ) {
            $content .= '<option value="' . $value->id . '"';
            if (in_array($value->id, $segment)) {
                $content .= ' selected=""';
            }
            $content .= '>' . $value->segment_title . '</option>';
        }
        $content .= '</select>';

        $content .= '<input type="submit" value="Submit" name="submit">';
        $content .= '<p class="record-note"><em>*</em> Count and List may result in a very large document! Always do a count first.</p>';
        $content .= '</form>';

        return $content;

    }


    /**
     * Function display_report_results_record_counts
     * @return $content
     */
    function display_report_results_record_counts()
    {
        global $wpdb;

        $display_mode = filter_var ( $_SESSION['record-counts']['display_mode'], FILTER_SANITIZE_STRIPPED );
        if ( is_array($_SESSION['record-counts']['segment']) ) {
            $segment      = filter_var_array ( $_SESSION['record-counts']['segment'], FILTER_SANITIZE_STRIPPED );
        } else {
            $segment = array();
        }
        $older_newer  = filter_var ( $_SESSION['record-counts']['older_newer'], FILTER_SANITIZE_STRIPPED );
        $date_month   = filter_var ( $_SESSION['record-counts']['date_month'], FILTER_SANITIZE_STRIPPED );
        $date_day     = filter_var ( $_SESSION['record-counts']['date_day'], FILTER_SANITIZE_STRIPPED );
        $date_year    = filter_var ( $_SESSION['record-counts']['date_year'], FILTER_SANITIZE_STRIPPED );

        if( $display_mode == 'count' ){
            $table_head_class = array(  //initialize
                'ti' => '',
                'ct' => ''
            );
        } else if ( $display_mode == 'display' ) {
            $table_head_class = array(  //initialize
                'ti' => '',
                'st' => '',
                'ud' => '',
            );
        }

        $sort_by_raw         = filter_var ( $_SESSION['record-counts']['sb'], FILTER_SANITIZE_STRIPPED );
        $sort_dir['current'] = filter_var ( $_SESSION['record-counts']['sd'], FILTER_SANITIZE_STRIPPED );

        //sort by
        if ( empty($sort_by_raw) ) {
            if ($display_mode == 'count') {
                $sort_by     = 'segment_title';
                $sort_by_raw = 'st';
                $table_head_class['st'] = 'sorted-by';
            } elseif ($display_mode == 'display') {
                $sort_by     = 'id';
                $sort_by_raw = 'id';
                $table_head_class['id'] = 'sorted-by';
            }
        } else {
            switch ($sort_by_raw) {
                case 'ti':
                    $sort_by     = 'title';
                    $table_head_class['ti'] = 'sorted-by';
                    break;
                case 'ct':
                    $sort_by     = 'count';
                    $table_head_class['ct'] = 'sorted-by';
                    break;
                case 'st':
                    $sort_by     = 'segment_title';
                    $table_head_class['st'] = 'sorted-by';
                    break;
                case 'ud':
                    $sort_by     = 'updated_at';
                    $table_head_class['ud'] = 'sorted-by';
                    break;
            }
        };

        //sort direction
        if ( empty( $sort_dir['current'] ) ) {
            $sort_dir['current'] = 'ASC';
            $sort_dir['ti']      = 'ASC';
            $sort_dir['ct']      = 'ASC';
            $sort_dir['st']      = 'ASC';
            $sort_dir['ud']      = 'ASC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir['ti']      = $sort_dir['current'];
            $sort_dir['ct']      = $sort_dir['current'];
            $sort_dir['st']      = $sort_dir['current'];
            $sort_dir['ud']      = $sort_dir['current'];
        };
        if ( $sort_dir['current'] == 'ASC' ) {
            $sort_dir[$sort_by_raw] = 'DESC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir[$sort_by_raw] = 'ASC';
            $table_head_class[$sort_by_raw] .= ' down';
        };

        if ( empty($_SESSION['record-counts']['pn']) ) {
            $page_num = 1;
        } else {
            $page_num = filter_var ( $_SESSION['record-counts']['pn'], FILTER_SANITIZE_STRIPPED );
        };

        if ( !empty($segment) && implode("", $segment) != 'all' ){ // segments passed
            if( in_array("all", $segment) ) {
                $str_segment = "";
            } else {
                $str_segment = "AND m.segment_id IN(" . implode( ",", $segment).")";
            }
        }else{
            $str_segment = "";
        }

        if ($older_newer == 'newer'){ // newer or older modificator
            $str_older_sign = '>=';
        } elseif ($older_newer == 'older'){
            $str_older_sign = '<=';
        }

        $d = date("Y-m-d H:i:s", mktime(0, 0, 0, $date_month, $date_day, $date_year)); // date formatted

        $order_by = $sort_by . " " . $sort_dir['current'];

        if ($display_mode == 'count'){   // just count records
            $sql_query =   "SELECT
                                count(DISTINCT g.title) as count, s.segment_title
                            FROM
                                " . $wpdb->prefix . "gs_grants g, " . $wpdb->prefix . "gs_grant_segments s, " . $wpdb->prefix . "gs_grant_segment_mappings m
                            WHERE
                                g.id = m.grant_id AND
                                g.status = 'A' AND
                                s.id = m.segment_id " . $str_segment . " AND
                                g.updated_at " . $str_older_sign . " '" . $d . "'
                            GROUP BY
                                s.segment_title
                            ORDER BY
                                $order_by";

        } else if ($display_mode == 'display') {
            // $sql_query =   "SELECT
            //                     g.id, g.title, g.updated_at, s.segment_title
            //                 FROM
            //                     " . $wpdb->prefix . "gs_grants g, " . $wpdb->prefix . "gs_grant_segments s, " . $wpdb->prefix . "gs_grant_segment_mappings m
            //                 WHERE
            //                     g.id = m.grant_id AND
            //                     g.status = 'A' AND
            //                     s.id = m.segment_id " . $str_segment . " AND
            //                     g.updated_at " . $str_older_sign . " '" . $d . "'
			// 		        GROUP BY
            //                     g.title
            //                 ORDER BY
            //                     $order_by";
            //ignore segment
            $sql_query =   "SELECT f.id, f.title, f.updated_at, GROUP_CONCAT(f.segment_title) AS segment_title 
                            FROM (SELECT
                                g.id, g.title, g.updated_at, s.segment_title
                            FROM
                                " . $wpdb->prefix . "gs_grants g 
                            LEFT JOIN " . $wpdb->prefix . "gs_grant_segment_mappings m ON g.id=m.grant_id 
                            LEFT JOIN " . $wpdb->prefix . "gs_grant_segments s ON s.id=m.segment_id
                            WHERE
                                g.status = 'A' " . $str_segment . " AND
                                g.updated_at " . $str_older_sign . " '" . $d . "'
                            ".
                                ") f GROUP BY
                                f.id
                            ORDER BY
                                $order_by";
        }

        $sql = $wpdb->prepare( $sql_query );

//        echo "sql: " . $sql . "<br />"; //debug

        $data = $wpdb->get_results( $sql, "OBJECT_K" );

//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $content = '';

        //count cols: title, count
        //count and list cols: title, segment title, updated, last edit by

        $content .= '<div class="mass-edit-options" style="display:none">';
        $content .= '<input type="submit" name="mass-process" value="Bulk Edit Checked Items" id="mass-process">';
        $content .= '</div>';

        $content .= '<table id="report_form" class="record-counts ' . $display_mode . ' mass-editable">';
        $content .= '<tr>';
        if( $display_mode == 'count' ){
            $content .= '<th><a href="/editor/reports/record-counts/?sb=st&sd=' . $sort_dir['st'] . '&pn=' . $page_num . '" class="' . $table_head_class['st'] . '">Segment Title</a></th>';
            $content .= '<th><a href="/editor/reports/record-counts/?sb=ct&sd=' . $sort_dir['ct'] . '&pn=' . $page_num . '" class="' . $table_head_class['ct'] . '">Count</a></th>';
        } else if ( $display_mode == 'display' ) {
            $content .= '<th><input type="checkbox" name="mass_action" class="mass-action-all" value="all"></th>';
            $content .= '<th><a href="/editor/reports/record-counts/?sb=ti&sd=' . $sort_dir['ti'] . '&pn=' . $page_num . '" class="' . $table_head_class['ti'] . '">Title</a></th>';
            $content .= '<th><a href="/editor/reports/record-counts/?sb=st&sd=' . $sort_dir['st'] . '&pn=' . $page_num . '" class="' . $table_head_class['st'] . '">Segment Title</a></th>';
            $content .= '<th><a href="/editor/reports/record-counts/?sb=ud&sd=' . $sort_dir['ud'] . '&pn=' . $page_num . '" class="' . $table_head_class['ud'] . '">Updated</a></th>';
            $content .= '<th class="non-sortable">Last Edit By</th>';
        }
        $content .= '</tr>';

        //TODO: Add sort by column for "Last Edit By" column

        if ( !empty($data) ) {
            $total_count = 0;
            foreach( $data as $key=>$value ) :
                $content .= '<tr>';
                if( $display_mode == 'count' ){
                    $content .= '<td>' . $value->segment_title . '</td>';
                    $content .= '<td>' . number_format($value->count) . '</td>';
                    $total_count += $value->count;
                } else if ( $display_mode == 'display' ) {
                    $content .= '<td><input type="checkbox" name="mass_action" class="mass-action-select" value="' . $value->id . '"></td>';
                    $content .= '<td>' . stripslashes($value->title) . '</td>';
                    $content .= '<td>' . stripslashes($value->segment_title) . '</td>';
                    $content .= '<td>' . $value->updated_at . '</td>';
                    $content .= '<td>' . self::get_last_editor( $value->id ) . '</td>';
                }
                $content .= '</tr>';
            endforeach;

            //Put total at the bottom of count table
            if( $display_mode == 'count' ){
                $content .= '<td class="table-total label">Total</td>';
                $content .= '<td class="table-total value">' . number_format($total_count) . '</td>';
            }

        } else {
            if ($display_mode == 'count') {
                $content .= '<td colspan="2">(No matching records found)</td>';
            } elseif ($display_mode == 'display') {
                $content .= '<td colspan="4">(No matching records found)</td>';
            }
        }

        $content .= "</table>";
        $content .= GrantSelectRecordsAddOn::mass_edits_modal();

        return $content;

    }


    /**
     * Function display_report_form_sponsor_counts
     * @return $content
     */
    function display_report_form_sponsor_counts()
    {
        $display_mode = filter_var ( $_SESSION['sponsor-counts']['display_mode'], FILTER_SANITIZE_STRIPPED );
        if ( is_array($_SESSION['sponsor-counts']['type']) ) {
            $type     = filter_var_array ( $_SESSION['sponsor-counts']['type'], FILTER_SANITIZE_STRIPPED );
        } else {
            $type     = array();
        }
        $older_newer  = filter_var ( $_SESSION['sponsor-counts']['older_newer'], FILTER_SANITIZE_STRIPPED );
        $date_month   = filter_var ( $_SESSION['sponsor-counts']['date_month'], FILTER_SANITIZE_STRIPPED );
        $date_day     = filter_var ( $_SESSION['sponsor-counts']['date_day'], FILTER_SANITIZE_STRIPPED );
        $date_year    = filter_var ( $_SESSION['sponsor-counts']['date_year'], FILTER_SANITIZE_STRIPPED );

        $content = '<p class="record-description">Choose your options and submit:</p>';

        $content .= '<form method="post" action="">';
        $content .= '<select name="display_mode" size="1">';

        $content .= '<option value="count"';
        if ($display_mode == 'count') {
            $content .= ' selected=""';
        }
        $content .= '>Count Records</option>';
        $content .= '<option value="display"';
        if ($display_mode == 'display') {
            $content .= ' selected=""';
        }
        $content .= '>Count and List *</option>';
        $content .= '</select>';

        $content .= '<select name="older_newer" size="1">';
        $content .= '<option value="newer"';
        if ($older_newer == 'newer') {
            $content .= ' selected=""';
        }
        $content .= '>Updated On or Later Than</option>';
        $content .= '<option value="older"';
        if ($older_newer == 'older') {
            $content .= ' selected=""';
        }
        $content .= '>Older Than</option>';
        $content .= '</select>';

        $content .= '<select name="date_month">';
        for ( $i=1; $i<=12; $i++ ) {
            $content .= "<option value=\"$i\"";
            if (intval($date_month) == $i) {
                $content .= ' selected=""';
            }
            $content .= ">" . self::MONTH_ARRAY[$i] . "</option>";
        }
        $content .= '</select>';

        $content .= '<select name="date_day">';
        for ( $i=1; $i<=31; $i++ ) {
            $content .= "<option value=\"$i\"";
            if (intval($date_day) == $i) {
                $content .= ' selected=""';
            }
            $content .= ">$i</option>";
        }
        $content .= '</select>';

        $current_year = intval(date("Y"));
        $content .= '<select name="date_year">';
        for ( $i=10; $i>=0; $i-- ) {
            $content .= '<option value="' . ($current_year - $i) . '"';
            if (intval($date_year) == ($current_year - $i)) {
                $content .= ' selected=""';
            }
            $content .= '>' . ($current_year - $i) . '</option>';
        }
        $content .= '</select>';

        $content .= '<select class="counts-select" name="type[]" size="5" multiple="multiple">';
        $content .= '<option value="all"';
        if (in_array('all', $type)) {
            $content .= ' selected=""';
        }
        $content .= '>(All Records)</option>';

        $type_list = GrantSelectRecordsAddOn::get_sponsor_type_list();
        foreach ( $type_list as $key => $value ) {
            $content .= '<option value="' . $value->id . '"';
            if (in_array($value->id, $type)) {
                $content .= ' selected=""';
            }
            $content .= '>' . $value->sponsor_type . '</option>';
        }
        $content .= '</select>';

        $content .= '<input type="submit" value="Submit" name="submit">';
        $content .= '<p class="record-note"><em>*</em> Count and List may result in a very large document! Always do a count first.</p>';
        $content .= '</form>';

        return $content;

    }


    /**
     * Function display_report_results_sponsor_counts
     * @return $content
     */
    function display_report_results_sponsor_counts()
    {
        global $wpdb;

        $display_mode = filter_var ( $_SESSION['sponsor-counts']['display_mode'], FILTER_SANITIZE_STRIPPED );
        if ( is_array($_SESSION['sponsor-counts']['type']) ) {
            $type     = filter_var_array ( $_SESSION['sponsor-counts']['type'], FILTER_SANITIZE_STRIPPED );
        } else {
            $type     = array();
        }
        $older_newer  = filter_var ( $_SESSION['sponsor-counts']['older_newer'], FILTER_SANITIZE_STRIPPED );
        $date_month   = filter_var ( $_SESSION['sponsor-counts']['date_month'], FILTER_SANITIZE_STRIPPED );
        $date_day     = filter_var ( $_SESSION['sponsor-counts']['date_day'], FILTER_SANITIZE_STRIPPED );
        $date_year    = filter_var ( $_SESSION['sponsor-counts']['date_year'], FILTER_SANITIZE_STRIPPED );

        if( $display_mode == 'count' ){
            $table_head_class = array(  //initialize
                'st' => '',
                'ct' => ''
            );
        } else if ( $display_mode == 'display' ) {
            $table_head_class = array(  //initialize
                'sn' => '',
                'st' => '',
                'ud' => ''
            );
        }

        $sort_by_raw         = filter_var ( $_SESSION['sponsor-counts']['sb'], FILTER_SANITIZE_STRIPPED );
        $sort_dir['current'] = filter_var ( $_SESSION['sponsor-counts']['sd'], FILTER_SANITIZE_STRIPPED );

        //sort by
        if ( empty($sort_by_raw) ) {
            if ($display_mode == 'count') {
                $sort_by     = 'sponsor_type';
                $sort_by_raw = 'st';
                $table_head_class['st'] = 'sorted-by';
            } elseif ($display_mode == 'display') {
                $sort_by     = 'sponsor_name';
                $sort_by_raw = 'sn';
                $table_head_class['sn'] = 'sorted-by';
            }
        } else {
            switch ($sort_by_raw) {
                case 'st':
                    $sort_by     = 'sponsor_type';
                    $table_head_class['st'] = 'sorted-by';
                    break;
                case 'ct':
                    $sort_by     = 'count';
                    $table_head_class['ct'] = 'sorted-by';
                    break;
                case 'sn':
                    $sort_by     = 'sponsor_name';
                    $table_head_class['sn'] = 'sorted-by';
                    break;
                case 'ud':
                    $sort_by     = 'updated_at';
                    $table_head_class['ud'] = 'sorted-by';
                    break;
            }
        };

        //sort direction
        if ( empty( $sort_dir['current'] ) ) {
            $sort_dir['current'] = 'ASC';
            $sort_dir['st']      = 'ASC';
            $sort_dir['ct']      = 'ASC';
            $sort_dir['sn']      = 'ASC';
            $sort_dir['ud']      = 'ASC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir['st']      = $sort_dir['current'];
            $sort_dir['ct']      = $sort_dir['current'];
            $sort_dir['sn']      = $sort_dir['current'];
            $sort_dir['ud']      = $sort_dir['current'];
        };
        if ( $sort_dir['current'] == 'ASC' ) {
            $sort_dir[$sort_by_raw] = 'DESC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir[$sort_by_raw] = 'ASC';
            $table_head_class[$sort_by_raw] .= ' down';
        };

        if ( empty($_SESSION['sponsor-counts']['pn']) ) {
            $page_num = 1;
        } else {
            $page_num = filter_var ( $_SESSION['sponsor-counts']['pn'], FILTER_SANITIZE_STRIPPED );
        };

        if ( !empty($type) && implode("", $type) != 'all' ){ // sponsor types passed
            if( in_array("all", $type) ) {
                $str_type = "";
            } else {
                $str_type = "IN(" . implode( ",", $type).")";
            }
        }else{
            $str_type = "";
        }

        if ($older_newer == 'newer'){ // newer or older modificator
            $str_older_sign = '>=';
        } elseif ($older_newer == 'older'){
            $str_older_sign = '<=';
        }

        $d = date("Y-m-d H:i:s", mktime(0, 0, 0, $date_month, $date_day, $date_year)); // date formatted

        $order_by = $sort_by . " " . $sort_dir['current'];

        if ($display_mode == 'count'){   // just count records
            $sql_query =   "SELECT
                                grant_sponsor_type_id, sponsor_name, COUNT( grant_sponsor_type_id ) AS count, st.sponsor_type
                            FROM
                                " . $wpdb->prefix . "gs_grant_sponsors as gs, " . $wpdb->prefix . "gs_grant_sponsor_types as st
                            WHERE
                                gs.grant_sponsor_type_id $str_type AND
                                st.id = grant_sponsor_type_id AND
                                gs.status='A' AND
                                gs.sponsor_name != '' AND
                                gs.updated_at " . $str_older_sign . " '" . $d . "'
                            GROUP BY
                                gs.grant_sponsor_type_id
                            ORDER BY
                                $order_by";

        } else if ($display_mode == 'display') {
            $sql_query =   "SELECT
                                gs.id, gs.grant_sponsor_type_id, gs.sponsor_name, gs.updated_at, st.sponsor_type
                            FROM
                                " . $wpdb->prefix . "gs_grant_sponsors gs, " . $wpdb->prefix . "gs_grant_sponsor_types st
                            WHERE
                                gs.grant_sponsor_type_id $str_type AND
                                st.id = gs.grant_sponsor_type_id AND
                                gs.status='A' AND
                                gs.sponsor_name != '' AND
                                gs.updated_at " . $str_older_sign . " ' " . $d . "'
                            ORDER BY
                                $order_by";
        }

        $sql = $wpdb->prepare( $sql_query );

//        echo "sql: " . $sql . "<br />"; //debug

        $data = $wpdb->get_results( $sql, "OBJECT_K" );

//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $content = '';

        //count cols: title, count
        //count and list cols: sponsor name, sponsor type, updated, last edit by

        $content .= '<table id="report_form" class="sponsor-counts ' . $display_mode . '">';
        $content .= '<tr>';
        if( $display_mode == 'count' ){
            $content .= '<th><a href="/editor/reports/sponsor-counts/?sb=st&sd=' . $sort_dir['st'] . '&pn=' . $page_num . '" class="' . $table_head_class['st'] . '">Sponsor Type</a></th>';
            $content .= '<th><a href="/editor/reports/sponsor-counts/?sb=ct&sd=' . $sort_dir['ct'] . '&pn=' . $page_num . '" class="' . $table_head_class['ct'] . '">Count</a></th>';
        } else if ( $display_mode == 'display' ) {
            $content .= '<th><a href="/editor/reports/sponsor-counts/?sb=sn&sd=' . $sort_dir['sn'] . '&pn=' . $page_num . '" class="' . $table_head_class['sn'] . '">Sponsor Name</a></th>';
            $content .= '<th><a href="/editor/reports/sponsor-counts/?sb=st&sd=' . $sort_dir['st'] . '&pn=' . $page_num . '" class="' . $table_head_class['st'] . '">Sponsor Type</a></th>';
            $content .= '<th><a href="/editor/reports/sponsor-counts/?sb=ud&sd=' . $sort_dir['ud'] . '&pn=' . $page_num . '" class="' . $table_head_class['ud'] . '">Updated</a></th>';
            $content .= '<th class="non-sortable">Last Edit By</th>';
        }
        $content .= '</tr>';

        if ( !empty($data) ) {
            $total_count = 0;
            foreach( $data as $key=>$value ) :
                $content .= '<tr>';
                if( $display_mode == 'count' ){
                    $content .= '<td>' . stripslashes($value->sponsor_type) . '</td>';
                    $content .= '<td>' . number_format($value->count) . '</td>';
                    $total_count += $value->count;
                } else if ( $display_mode == 'display' ) {
                    $content .= '<td>' . stripslashes($value->sponsor_name) . '</td>';
                    $content .= '<td>' . stripslashes($value->sponsor_type) . '</td>';
                    $content .= '<td>' . $value->updated_at . '</td>';
                    $content .= '<td>' . self::get_last_editor_sponsor( $value->id ) . '</td>';
                }
                $content .= '</tr>';
            endforeach;

            //Put total at the bottom of count table
            if( $display_mode == 'count' ){
                $content .= '<td class="table-total label">Total</td>';
                $content .= '<td class="table-total value">' . number_format($total_count) . '</td>';
            }

        } else {
            if ($display_mode == 'count') {
                $content .= '<td colspan="2">(No matching sponsor types found)</td>';
            } elseif ($display_mode == 'display') {
                $content .= '<td colspan="4">(No matching sponsors found)</td>';
            }
        }

        $content .= "</table>";

        return $content;

    }


    /**
     * Function display_report_form_subject_headings
     * @return $content
     */
    function display_report_form_subject_headings()
    {
        $content  = '<p class="record-description">This report pulls a list of subject headings along with the number of times a heading is used.</p>';
        $content .= '<p class="record-note">NOTE: This report is very intensive—it may take more than a minute to run, and even longer to download.</p>';

        $content .= '<form name="subject_headings" action="" method="post">';
        $content .= '<input type="submit" value="All Subject Headings - Alphabetically" name="subject_alpha" id="subject_headings_alpha">';
        $content .= '<input type="submit" value="All Subject Headings - By Number of Times Used" name="num_used" id="subject_headings_num_used">';
        $content .= '</form>';

        return $content;

    }


    /**
     * Function display_report_results_subject_headings
     * @return $content
     */
    function display_report_results_subject_headings() {

        global $wpdb;

        if ( !empty( filter_var ( $_SESSION['subject-headings']['subject_alpha'], FILTER_SANITIZE_STRIPPED ) ) ) {
            $display_mode = 'subject_alpha';
        } elseif ( !empty( filter_var ( $_SESSION['subject-headings']['num_used'], FILTER_SANITIZE_STRIPPED ) ) ) {
            $display_mode = 'num_used';
        }

        if( $display_mode == 'subject_alpha' ){
            $table_head_class = array(  //initialize
                'st' => ''
            );
        } else if ( $display_mode == 'num_used' ) {
            $table_head_class = array(  //initialize
                'st' => '',
                'ct' => ''
            );
        }

        $sort_by_raw         = filter_var ( $_SESSION['subject-headings']['sb'], FILTER_SANITIZE_STRIPPED );
        $sort_dir['current'] = filter_var ( $_SESSION['subject-headings']['sd'], FILTER_SANITIZE_STRIPPED );

        //sort by
        if ( empty($sort_by_raw) ) {
            if ($display_mode == 'subject_alpha') {
                $sort_by     = 'subject_title';
                $sort_by_raw = 'st';
                $table_head_class['st'] = 'sorted-by';
            } elseif ($display_mode == 'num_used') {
                $sort_by     = 'count';
                $sort_by_raw = 'ct';
                $table_head_class['ct'] = 'sorted-by';
            }
        } else {
            switch ($sort_by_raw) {
                case 'st':
                    $sort_by     = 'subject_title';
                    $table_head_class['st'] = 'sorted-by';
                    break;
                case 'ct':
                    $sort_by     = 'count';
                    $table_head_class['ct'] = 'sorted-by';
                    break;
            }
        };

        //sort direction
        if ( empty( $sort_dir['current'] ) ) {
            if ($display_mode == 'subject_alpha') {
                $sort_dir['current'] = 'ASC';
                $sort_dir['st']      = 'ASC';
                $sort_dir['ct']      = 'ASC';
                $table_head_class[$sort_by_raw] .= ' up';
            } elseif ($display_mode == 'num_used') {
                $sort_dir['current'] = 'DESC';
                $sort_dir['st']      = 'ASC';
                $sort_dir['ct']      = 'DESC';
                $table_head_class[$sort_by_raw] .= ' down';
            };
        } else {
            $sort_dir['st']      = $sort_dir['current'];
            $sort_dir['ct']      = $sort_dir['current'];
        };
        if ( $sort_dir['current'] == 'ASC' ) {
            $sort_dir[$sort_by_raw] = 'DESC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir[$sort_by_raw] = 'ASC';
            $table_head_class[$sort_by_raw] .= ' down';
        };

        if ( empty($_SESSION['sponsor-counts']['pn']) ) {
            $page_num = 1;
        } else {
            $page_num = filter_var ( $_SESSION['sponsor-counts']['pn'], FILTER_SANITIZE_STRIPPED );
        };

        if( $display_mode == 'subject_alpha' ){
            $sql_query  =  "SELECT
                                id, subject_title, updated_at, created_at
                            FROM
                                " . $wpdb->prefix . "gs_grant_subjects
                            ORDER BY
                                $sort_by " . $sort_dir['current'];
        } else if ( $display_mode == 'num_used' ) {
            $sql_query  =  "SELECT
                                m.subject_id as id, s.subject_title, s.updated_at, s.created_at, count(m.subject_id) AS count
                            FROM
                                " . $wpdb->prefix . "gs_grant_subjects s, " . $wpdb->prefix . "gs_grant_subject_mappings m
                            WHERE
                                s.id=m.subject_id
                            GROUP BY
                                m.subject_id
                            ORDER BY
                                $sort_by " . $sort_dir['current'];
        }

//        echo "SQLQ384: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $content = '';

        $content .= '<table id="report_form" class="subject-headings ' . $display_mode . '">';
        $content .= '<tr>';
        if( $display_mode == 'subject_alpha' ){
            $content .= '<th><a href="/editor/reports/subject-headings/?sb=st&sd=' . $sort_dir['st'] . '&pn=' . $page_num . '" class="' . $table_head_class['st'] . '">Subject Title</a></th>';
        } else if ( $display_mode == 'num_used' ) {
            $content .= '<th><a href="/editor/reports/subject-headings/?sb=st&sd=' . $sort_dir['st'] . '&pn=' . $page_num . '" class="' . $table_head_class['st'] . '">Subject Title</a></th>';
            $content .= '<th><a href="/editor/reports/subject-headings/?sb=ct&sd=' . $sort_dir['ct'] . '&pn=' . $page_num . '" class="' . $table_head_class['ct'] . '">Used</a></th>';
        }
        $content .= '</tr>';

        if ( !empty($data) ) {
            $total_count = 0;
            foreach( $data as $key=>$value ) :
                $content .= '<tr>';
                if( $display_mode == 'subject_alpha' ){
                    $content .= '<td>' . stripslashes($value->subject_title) . '</td>';
                } else if ( $display_mode == 'num_used' ) {
                    $content .= '<td>' . stripslashes($value->subject_title) . '</td>';
                    $content .= '<td>' . $value->count . '</td>';
                    $total_count += $value->count;
                }
                $content .= '</tr>';
            endforeach;

            //Put total at the bottom of count table
            if( $display_mode == 'num_used' ){
                $content .= '<td class="table-total label">Total</td>';
                $content .= '<td class="table-total value">' . number_format($total_count) . '</td>';
            }

        } else {
            if ($display_mode == 'subject_alpha') {
                $content .= '<td colspan="1">(No matching subject headings found)</td>';
            } elseif ($display_mode == 'num_used') {
                $content .= '<td colspan="2">(No matching subject headings found)</td>';
            }
        }

        $content .= "</table>";

        return $content;
    }


    /**
     * Function display_report_form_unique_sponsors
     * @return $content
     */
    function display_report_form_unique_sponsors()
    {
        $display_mode = filter_var ( $_SESSION['unique-sponsors']['display_mode'], FILTER_SANITIZE_STRIPPED );
        if ( is_array($_SESSION['unique-sponsors']['segment']) ) {
            $segment     = filter_var_array ( $_SESSION['unique-sponsors']['segment'], FILTER_SANITIZE_STRIPPED );
        } else {
            $segment     = array();
        }
        $older_newer  = filter_var ( $_SESSION['unique-sponsors']['older_newer'], FILTER_SANITIZE_STRIPPED );
        $date_month   = filter_var ( $_SESSION['unique-sponsors']['date_month'], FILTER_SANITIZE_STRIPPED );
        $date_day     = filter_var ( $_SESSION['unique-sponsors']['date_day'], FILTER_SANITIZE_STRIPPED );
        $date_year    = filter_var ( $_SESSION['unique-sponsors']['date_year'], FILTER_SANITIZE_STRIPPED );

        $content = '<p class="record-description">Choose your options and submit:</p>';

        $content .= '<form method="post" action="">';
        $content .= '<select name="display_mode" size="1">';

        $content .= '<option value="count"';
        if ($display_mode == 'count') {
            $content .= ' selected=""';
        }
        $content .= '>Count Records</option>';
        $content .= '<option value="display"';
        if ($display_mode == 'display') {
            $content .= ' selected=""';
        }
        $content .= '>Count and List *</option>';
        $content .= '</select>';

        $content .= '<select name="older_newer" size="1">';
        $content .= '<option value="newer"';
        if ($older_newer == 'newer') {
            $content .= ' selected=""';
        }
        $content .= '>Updated On or Later Than</option>';
        $content .= '<option value="older"';
        if ($older_newer == 'older') {
            $content .= ' selected=""';
        }
        $content .= '>Older Than</option>';
        $content .= '</select>';

        $content .= '<select name="date_month">';
        for ( $i=1; $i<=12; $i++ ) {
            $content .= "<option value=\"$i\"";
            if (intval($date_month) == $i) {
                $content .= ' selected=""';
            }
            $content .= ">" . self::MONTH_ARRAY[$i] . "</option>";
        }
        $content .= '</select>';

        $content .= '<select name="date_day">';
        for ( $i=1; $i<=31; $i++ ) {
            $content .= "<option value=\"$i\"";
            if (intval($date_day) == $i) {
                $content .= ' selected=""';
            }
            $content .= ">$i</option>";
        }
        $content .= '</select>';

        $current_year = intval(date("Y"));
        $content .= '<select name="date_year">';
        for ( $i=10; $i>=0; $i-- ) {
            $content .= '<option value="' . ($current_year - $i) . '"';
            if (intval($date_year) == ($current_year - $i)) {
                $content .= ' selected=""';
            }
            $content .= '>' . ($current_year - $i) . '</option>';
        }
        $content .= '</select>';

        $content .= '<select class="counts-select" name="segment[]" size="5" multiple="multiple">';
        $content .= '<option value="all"';
        if (in_array('all', $segment)) {
            $content .= ' selected=""';
        }
        $content .= '>(All Records)</option>';

        $segment_list = GrantSelectRecordsAddOn::get_segment_list();
        foreach ( $segment_list as $key => $value ) {
            $content .= '<option value="' . $value->id . '"';
            if (in_array($value->id, $segment)) {
                $content .= ' selected=""';
            }
            $content .= '>' . stripslashes($value->segment_title) . '</option>';
        }
        $content .= '</select>';

        $content .= '<input type="submit" value="Submit" name="submit">';
        $content .= '<p class="record-note"><em>*</em> Count and List may result in a very large document! Always do a count first.</p>';
        $content .= '</form>';

        return $content;

    }


    /**
     * Function display_report_results_unique_sponsors
     * @return $content
     */
    function display_report_results_unique_sponsors()
    {
        global $wpdb;

        //TODO: Check with client what exactly this report is supposed to show, and verify it is showing that

        $display_mode = filter_var ( $_SESSION['unique-sponsors']['display_mode'], FILTER_SANITIZE_STRIPPED );
        if ( is_array($_SESSION['unique-sponsors']['segment']) ) {
            $segment     = filter_var_array ( $_SESSION['unique-sponsors']['segment'], FILTER_SANITIZE_STRIPPED );
        } else {
            $segment     = array();
        }
        $older_newer  = filter_var ( $_SESSION['unique-sponsors']['older_newer'], FILTER_SANITIZE_STRIPPED );
        $date_month   = filter_var ( $_SESSION['unique-sponsors']['date_month'], FILTER_SANITIZE_STRIPPED );
        $date_day     = filter_var ( $_SESSION['unique-sponsors']['date_day'], FILTER_SANITIZE_STRIPPED );
        $date_year    = filter_var ( $_SESSION['unique-sponsors']['date_year'], FILTER_SANITIZE_STRIPPED );

        if( $display_mode == 'count' ){
            $table_head_class = array(  //initialize
                'sn' => '',
                'ct' => ''
            );
        } else if ( $display_mode == 'display' ) {
            $table_head_class = array(  //initialize
                'sn' => '',
                'gt' => '',
                'ct' => '',
                'st' => '',
                'ud' => ''
            );
        }

        $sort_by_raw         = filter_var ( $_SESSION['unique-sponsors']['sb'], FILTER_SANITIZE_STRIPPED );
        $sort_dir['current'] = filter_var ( $_SESSION['unique-sponsors']['sd'], FILTER_SANITIZE_STRIPPED );

        //sort by
        if ( empty($sort_by_raw) ) {
            if ($display_mode == 'count') {
                $sort_by     = 'sponsor_name';
                $sort_by_raw = 'sn';
                $table_head_class['sn'] = 'sorted-by';
            } elseif ($display_mode == 'display') {
                $sort_by     = 'sponsor_name';
                $sort_by_raw = 'sn';
                $table_head_class['sn'] = 'sorted-by';
            }
        } else {
            switch ($sort_by_raw) {
                case 'sn':
                    $sort_by     = 'sponsor_name';
                    $table_head_class['sn'] = 'sorted-by';
                    break;
                case 'ct':
                    $sort_by     = 'count';
                    $table_head_class['ct'] = 'sorted-by';
                    break;
                case 'gt':
                    $sort_by     = 'title';
                    $table_head_class['gt'] = 'sorted-by';
                    break;
                case 'st':
                    $sort_by     = 'segment_title';
                    $table_head_class['st'] = 'sorted-by';
                    break;
                case 'ud':
                    $sort_by     = 'updated_at';
                    $table_head_class['ud'] = 'sorted-by';
                    break;
            }
        };

        //sort direction
        if ( empty( $sort_dir['current'] ) ) {
            $sort_dir['current'] = 'ASC';
            $sort_dir['sn']      = 'ASC';
            $sort_dir['ct']      = 'ASC';
            $sort_dir['gt']      = 'ASC';
            $sort_dir['st']      = 'ASC';
            $sort_dir['ud']      = 'ASC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir['sn']      = $sort_dir['current'];
            $sort_dir['ct']      = $sort_dir['current'];
            $sort_dir['gt']      = $sort_dir['current'];
            $sort_dir['st']      = $sort_dir['current'];
            $sort_dir['ud']      = $sort_dir['current'];
        };
        if ( $sort_dir['current'] == 'ASC' ) {
            $sort_dir[$sort_by_raw] = 'DESC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir[$sort_by_raw] = 'ASC';
            $table_head_class[$sort_by_raw] .= ' down';
        };

        if ( empty($_SESSION['unique-sponsors']['pn']) ) {
            $page_num = 1;
        } else {
            $page_num = filter_var ( $_SESSION['unique-sponsors']['pn'], FILTER_SANITIZE_STRIPPED );
        };

        if ( !empty($segment) && implode("", $segment) != 'all' ){ // segment titles passed
            if( in_array("all", $segment) ) {
                $str_segment = "";
            } else {
                $str_segment = "IN(" . implode( ",", $segment).")";
            }
        }else{
            $str_segment = "";
        }

        if ($older_newer == 'newer'){ // newer or older modificator
            $str_older_sign = '>=';
        } elseif ($older_newer == 'older'){
            $str_older_sign = '<=';
        }

        $d = date("Y-m-d H:i:s", mktime(0, 0, 0, $date_month, $date_day, $date_year)); // date formatted

        $order_by = $sort_by . " " . $sort_dir['current'];
        if ($sort_by != 'sponsor_name') {
            $order_by .= ', sponsor_name ASC';
        } elseif ($display_mode == 'display' ) {    // $sort_by==sponsor_name and $display_more == "display"
            $order_by .= ', title ASC';
        }

        //sql query for display_mode == 'display'
        $sql_query  =  "SELECT
                                g.id, sp.sponsor_name, 1 AS count, g.updated_at, s.segment_title, g.title
                            FROM
                                " . $wpdb->prefix . "gs_grants g,
                                " . $wpdb->prefix . "gs_grant_segments s,
                                " . $wpdb->prefix . "gs_grant_segment_mappings m,
                                " . $wpdb->prefix . "gs_grant_sponsors sp,
                                " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings gsm
                            WHERE
                                g.id = m.grant_id AND
                                g.id = gsm.grant_id AND
                                s.id = m.segment_id AND
                                m.segment_id " . $str_segment . " AND
                                sp.id = gsm.sponsor_id AND
                                sp.sponsor_name != '' AND
                                g.updated_at " . $str_older_sign . " '" . $d . "' AND
                                g.title != '' AND
                                g.status != 'E' AND
                                sp.status='A'
                            GROUP BY
                                g.id
                            ORDER BY
                                $order_by";

        if ($display_mode == 'count') {   // just count records

//            $sql_query  =  "SELECT
//                                g.id, sp.sponsor_name, count( sp.sponsor_name ) AS count
//						    FROM
//						        " . $wpdb->prefix . "gs_grants g,
//						        " . $wpdb->prefix . "gs_grant_segments s,
//						        " . $wpdb->prefix . "gs_grant_segment_mappings m,
//						        " . $wpdb->prefix . "gs_grant_sponsors sp,
//						        " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings gsm
//						    WHERE
//                                g.id = m.grant_id AND
//                                g.id = gsm.grant_id AND
//                                s.id = m.segment_id AND
//                                m.segment_id " . $str_segment . " AND
//                                sp.id = gsm.sponsor_id AND
//                                sp.sponsor_name != '' AND
//                                g.updated_at " . $str_older_sign . " '" . $d . "' AND
//                                g.title != '' AND
//                                g.status != 'E' AND
//                                sp.status='A'
//                            GROUP BY
//                                sp.sponsor_name
//                            ORDER BY
//                                $order_by";

            $sql_query  =  "SELECT
                                id, sponsor_name, count( sponsor_name ) AS count
						    FROM
						        ( $sql_query ) AS grant_list
                            GROUP BY
                                sponsor_name
                            ORDER BY
                                $order_by";

        }

        $sql = $wpdb->prepare( $sql_query );

//        echo "sql: " . $sql . "<br />"; //debug

        $data = $wpdb->get_results( $sql, "OBJECT" );

//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $content = '';

        //count cols: sponsor name, count
        //count and list cols: sponsor name, grant title, count, segment title, updated

        $content .= '<div class="mass-edit-options" style="display:none">';
        $content .= '<input type="submit" name="mass-process" value="Bulk Edit Checked Items" id="mass-process">';
        $content .= '</div>';

        $content .= '<table id="report_form" class="unique-sponsors ' . $display_mode . ' mass-editable">';
        $content .= '<tr>';
        if( $display_mode == 'count' ){
            $content .= '<th><a href="/editor/reports/unique-sponsors/?sb=sn&sd=' . $sort_dir['sn'] . '&pn=' . $page_num . '" class="' . $table_head_class['sn'] . '">Sponsor Name</a></th>';
            $content .= '<th><a href="/editor/reports/unique-sponsors/?sb=ct&sd=' . $sort_dir['ct'] . '&pn=' . $page_num . '" class="' . $table_head_class['ct'] . '">Count</a></th>';
        } else if ( $display_mode == 'display' ) {
            $content .= '<th><input type="checkbox" name="mass_action" class="mass-action-all" value="all"></th>';
            $content .= '<th><a href="/editor/reports/unique-sponsors/?sb=sn&sd=' . $sort_dir['sn'] . '&pn=' . $page_num . '" class="' . $table_head_class['sn'] . '">Sponsor Name</a></th>';
            $content .= '<th><a href="/editor/reports/unique-sponsors/?sb=gt&sd=' . $sort_dir['gt'] . '&pn=' . $page_num . '" class="' . $table_head_class['gt'] . '">Grant Title</a></th>';
            $content .= '<th><a href="/editor/reports/unique-sponsors/?sb=ct&sd=' . $sort_dir['ct'] . '&pn=' . $page_num . '" class="' . $table_head_class['ct'] . '">Count</a></th>';
            $content .= '<th><a href="/editor/reports/unique-sponsors/?sb=st&sd=' . $sort_dir['st'] . '&pn=' . $page_num . '" class="' . $table_head_class['st'] . '">Segment Title</a></th>';
            $content .= '<th><a href="/editor/reports/unique-sponsors/?sb=ud&sd=' . $sort_dir['ud'] . '&pn=' . $page_num . '" class="' . $table_head_class['ud'] . '">Updated</a></th>';
        }
        $content .= '</tr>';

        if ( !empty($data) ) {
            $total_count = 0;
            foreach( $data as $key=>$value ) :
                $content .= '<tr>';
                if( $display_mode == 'count' ){
                    $content .= '<td>' . stripslashes($value->sponsor_name) . '</td>';
                    $content .= '<td>' . number_format($value->count) . '</td>';
                    $total_count += $value->count;
                } else if ( $display_mode == 'display' ) {
                    $content .= '<td><input type="checkbox" name="mass_action" class="mass-action-select" value="' . $value->id . '"></td>';
                    $content .= '<td>' . stripslashes($value->sponsor_name) . '</td>';
                    $content .= '<td>' . stripslashes($value->title) . '</td>';
                    $content .= '<td>' . $value->count . '</td>';
                    $content .= '<td>' . stripslashes($value->segment_title) . '</td>';
                    $content .= '<td>' . $value->updated_at . '</td>';
                    $total_count += $value->count;
                }
                $content .= '</tr>';
            endforeach;

            //Put total at the bottom of count table
            if( $display_mode == 'count' ){
                $content .= '<td class="table-total label">Total</td>';
                $content .= '<td class="table-total value">' . number_format($total_count) . '</td>';
            } elseif ( $display_mode == 'display' ) {
                $content .= '<td class="table-total label" colspan="3">Total</td>';
                $content .= '<td class="table-total value" colspan="3">' . number_format($total_count) . '</td>';
            }

        } else {
            if ($display_mode == 'count') {
                $content .= '<td colspan="2">(No matching sponsors found)</td>';
            } elseif ($display_mode == 'display') {
                $content .= '<td colspan="6">(No matching sponsors found)</td>';
            }
        }

        $content .= "</table>";
        $content .= GrantSelectRecordsAddOn::mass_edits_modal();

        return $content;

    }


    /**
     * Function display_report_results_suspended_records
     * @return $content
     */
    function display_report_results_suspended_records() {

        global $wpdb;

        $table_head_class = array(  //initialize
            'id' => '',
            'ti' => '',
            'lm' => '',
            'cb' => ''
        );

        $sort_by_raw         = filter_var ( $_SESSION['suspended-records']['sb'], FILTER_SANITIZE_STRIPPED );
        $sort_dir['current'] = filter_var ( $_SESSION['suspended-records']['sd'], FILTER_SANITIZE_STRIPPED );

        //sort_by
        if ( empty($sort_by_raw) ) {
            $sort_by     = 'id';
            $sort_by_raw = 'id';
            $table_head_class['id'] = 'sorted-by';
        } else {
            switch ( $sort_by_raw ) {
                case 'id':
                    $sort_by     = 'id';
                    $table_head_class['id'] = 'sorted-by';
                    break;
                case 'ti':
                    $sort_by     = 'title';
                    $table_head_class['ti'] = 'sorted-by';
                    break;
                case 'lm':
                    $sort_by     = 'updated_at';
                    $table_head_class['lm'] = 'sorted-by';
                    break;
                case 'cb':
                    $sort_by     = 'created_at';
                    $table_head_class['cb'] = 'sorted-by';
                    break;
            }
        };

        //sort direction
        if ( empty( $sort_dir['current'] ) ) {
            $sort_dir['current'] = 'ASC';
            $sort_dir['id']      = 'ASC';
            $sort_dir['ti']      = 'ASC';
            $sort_dir['lm']      = 'ASC';
            $sort_dir['cb']      = 'ASC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir['id']      = $sort_dir['current'];
            $sort_dir['ti']      = $sort_dir['current'];
            $sort_dir['lm']      = $sort_dir['current'];
            $sort_dir['cb']      = $sort_dir['current'];
        };
        if ( $sort_dir['current'] == 'ASC' ) {
            $sort_dir[$sort_by_raw] = 'DESC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir[$sort_by_raw] = 'ASC';
            $table_head_class[$sort_by_raw] .= ' down';
        };

        //page number
        if ( empty($_SESSION['suspended-records']['pn']) ) {
            $page_num = 1;
        } else {
            $page_num = filter_var ( $_SESSION['suspended-records']['pn'], FILTER_SANITIZE_STRIPPED );
        };

        $sql_query = "SELECT
                            id, title, updated_at, created_at FROM " . $wpdb->prefix . "gs_grants
                        WHERE
                            status = 'S'
                        ORDER BY
                            $sort_by " . $sort_dir['current'];

        //echo "SQLQ384: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        //        echo "<pre>";
        //        print_r($data);
        //        echo "</pre>";

        $content = '';

        $content .= '<div class="mass-edit-options" style="display:none">';
        $content .= '<input type="submit" name="mass-process" value="Bulk Edit Checked Items" id="mass-process">';
        $content .= '</div>';

        $content .= '<table id="report_form" class="suspended-records mass-editable">';
        $content .= '<tr>';
        $content .= '<th><input type="checkbox" name="mass_action" class="mass-action-all" value="all"></th>';
        $content .= '<th><a href="/editor/reports/suspended-records/?sb=id&sd=' . $sort_dir['id'] . '&pn=' . $page_num . '" class="' . $table_head_class['id'] . '">ID</a></th>';
        $content .= '<th>&nbsp;</th>';
        $content .= '<th>&nbsp;</th>';
        $content .= '<th><a href="/editor/reports/suspended-records/?sb=ti&sd=' . $sort_dir['ti'] . '&pn=' . $page_num . '" class="' . $table_head_class['ti'] . '">Title</a></th>';
        $content .= '<th><a href="/editor/reports/suspended-records/?sb=lm&sd=' . $sort_dir['lm']  . '&pn=' . $page_num . '" class="' . $table_head_class['lm'] . '">Last Modified</a></th>';
        $content .= '<th><a href="/editor/reports/suspended-records/?sb=cb&sd=' . $sort_dir['cb']    . '&pn=' . $page_num . '" class="' . $table_head_class['cb'] . '">Check Back</a></th>';
        $content .= '</tr>';

        foreach( $data as $key=>$value ) :
            $content .= '<tr>';
            $content .= '<td><input type="checkbox" name="mass_action" class="mass-action-select" value="' . $value->id . '"></td>';
            $content .= '<td>' . $value->id . '</td>';
            $content .= '<td><a href="/editor/records/view/?gid=' . $value->id . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">View</a></td>';
            $content .= '<td><a href="/editor/records/edit/?gid=' . $value->id . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">Edit</a></td>';
            $content .= '<td>' . stripslashes($value->title) . '</td>';
            $content .= '<td>' . $value->updated_at . '</td>';
            $content .= '<td>' . $value->created_at . '</td>';
            $content .= '</tr>';
        endforeach;

        $content .= "</table>";
        $content .= GrantSelectRecordsAddOn::mass_edits_modal();

        return $content;
    }

    /**
     * Function display_report_results_ready_for_review_records
     * @return $content
     */
    function display_report_results_ready_for_review_records() {

        global $wpdb;

        $table_head_class = array(  //initialize
            'id' => '',
            'ti' => '',
            'lm' => '',
            'cb' => ''
        );

        $sort_by_raw         = filter_var ( $_SESSION['ready-for-review']['sb'], FILTER_SANITIZE_STRIPPED );
        $sort_dir['current'] = filter_var ( $_SESSION['ready-for-review']['sd'], FILTER_SANITIZE_STRIPPED );

        //sort_by
        if ( empty($sort_by_raw) ) {
            $sort_by     = 'id';
            $sort_by_raw = 'id';
            $table_head_class['id'] = 'sorted-by';
        } else {
            switch ( $sort_by_raw ) {
                case 'id':
                    $sort_by     = 'id';
                    $table_head_class['id'] = 'sorted-by';
                    break;
                case 'ti':
                    $sort_by     = 'title';
                    $table_head_class['ti'] = 'sorted-by';
                    break;
                case 'lm':
                    $sort_by     = 'updated_at';
                    $table_head_class['lm'] = 'sorted-by';
                    break;
                case 'cb':
                    $sort_by     = 'created_at';
                    $table_head_class['cb'] = 'sorted-by';
                    break;
            }
        };

        //sort direction
        if ( empty( $sort_dir['current'] ) ) {
            $sort_dir['current'] = 'ASC';
            $sort_dir['id']      = 'ASC';
            $sort_dir['ti']      = 'ASC';
            $sort_dir['lm']      = 'ASC';
            $sort_dir['cb']      = 'ASC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir['id']      = $sort_dir['current'];
            $sort_dir['ti']      = $sort_dir['current'];
            $sort_dir['lm']      = $sort_dir['current'];
            $sort_dir['cb']      = $sort_dir['current'];
        };
        if ( $sort_dir['current'] == 'ASC' ) {
            $sort_dir[$sort_by_raw] = 'DESC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir[$sort_by_raw] = 'ASC';
            $table_head_class[$sort_by_raw] .= ' down';
        };

        //page number
        if ( empty($_SESSION['ready-for-review']['pn']) ) {
            $page_num = 1;
        } else {
            $page_num = filter_var ( $_SESSION['ready-for-review']['pn'], FILTER_SANITIZE_STRIPPED );
        };

        $sql_query = "SELECT
                            id, title, updated_at, created_at FROM " . $wpdb->prefix . "gs_grants
                        WHERE
                            status = 'R'
                        ORDER BY
                            $sort_by " . $sort_dir['current'];

        //echo "SQLQ384: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        //        echo "<pre>";
        //        print_r($data);
        //        echo "</pre>";

        $content = '';

        $content .= '<div class="mass-edit-options" style="display:none">';
        $content .= '<input type="submit" name="mass-process" value="Bulk Edit Checked Items" id="mass-process">';
        $content .= '</div>';

        $content .= '<table id="report_form" class="ready-for-review mass-editable">';
        $content .= '<tr>';
        $content .= '<th><input type="checkbox" name="mass_action" class="mass-action-all" value="all"></th>';
        $content .= '<th><a href="/editor/reports/ready-for-review/?sb=id&sd=' . $sort_dir['id'] . '&pn=' . $page_num . '" class="' . $table_head_class['id'] . '">ID</a></th>';
        $content .= '<th>&nbsp;</th>';
        $content .= '<th>&nbsp;</th>';
        $content .= '<th><a href="/editor/reports/ready-for-review/?sb=ti&sd=' . $sort_dir['ti'] . '&pn=' . $page_num . '" class="' . $table_head_class['ti'] . '">Title</a></th>';
        $content .= '<th><a href="/editor/reports/ready-for-review/?sb=lm&sd=' . $sort_dir['lm']  . '&pn=' . $page_num . '" class="' . $table_head_class['lm'] . '">Last Modified</a></th>';
        $content .= '<th><a href="/editor/reports/ready-for-review/?sb=cb&sd=' . $sort_dir['cb']    . '&pn=' . $page_num . '" class="' . $table_head_class['cb'] . '">Check Back</a></th>';
        $content .= '</tr>';

        foreach( $data as $key=>$value ) :
            $content .= '<tr>';
            $content .= '<td><input type="checkbox" name="mass_action" class="mass-action-select" value="' . $value->id . '"></td>';
            $content .= '<td>' . $value->id . '</td>';
            $content .= '<td><a href="/editor/records/view/?gid=' . $value->id . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">View</a></td>';
            $content .= '<td><a href="/editor/records/edit/?gid=' . $value->id . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">Edit</a></td>';
            $content .= '<td>' . stripslashes($value->title) . '</td>';
            $content .= '<td>' . $value->updated_at . '</td>';
            $content .= '<td>' . $value->created_at . '</td>';
            $content .= '</tr>';
        endforeach;

        $content .= "</table>";
        $content .= GrantSelectRecordsAddOn::mass_edits_modal();

        return $content;
    }

    /**
     * Function display_report_results_revisit
     * @return $content
     */
    function display_report_results_revisit() {

        global $wpdb;

        $table_head_class = array(  //initialize
            'id' => '',
            'ti' => '',
            'st' => '',
            'rd' => ''
        );

        //sort_by
        if ( empty($_GET['sb']) ) {
            $sort_by     = 'id';
            $sort_by_raw = 'id';
            $table_head_class['id'] = 'sorted-by';
        } else {
            switch ($_GET['sb']) {
                case 'id':
                    $sort_by     = 'g.id';
                    $sort_by_raw = 'id';
                    $table_head_class['id'] = 'sorted-by';
                    break;
                case 'ti':
                    $sort_by     = 'g.title';
                    $sort_by_raw = 'ti';
                    $table_head_class['ti'] = 'sorted-by';
                    break;
                case 'st':
                    $sort_by     = 'g.status';
                    $sort_by_raw = 'st';
                    $table_head_class['st'] = 'sorted-by';
                    break;
                case 'rd':
                    $sort_by     = 'revisit_date';
                    $sort_by_raw = 'rd';
                    $table_head_class['rd'] = 'sorted-by';
                    break;
            }
        };

        if ( empty($_GET['sd']) ) {
            $sort_dir['current'] = 'ASC';
            $sort_dir['id']      = 'ASC';
            $sort_dir['ti']      = 'ASC';
            $sort_dir['st']      = 'ASC';
            $sort_dir['rd']      = 'ASC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir['current'] = filter_var ( $_GET['sd'], FILTER_SANITIZE_STRIPPED );
            $sort_dir['id']      = $sort_dir['current'];
            $sort_dir['ti']      = $sort_dir['current'];
            $sort_dir['st']      = $sort_dir['current'];
            $sort_dir['rd']      = $sort_dir['current'];
        };
        if ( $sort_dir['current'] == 'ASC' ) {
            $sort_dir[$sort_by_raw] = 'DESC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir[$sort_by_raw] = 'ASC';
            $table_head_class[$sort_by_raw] .= ' down';
        }

        if ( empty($_GET['pn']) ) {
            $page_num = 1;
        } else {
            $page_num = filter_var ( $_GET['pn'], FILTER_SANITIZE_STRIPPED );
        };


        $sql_query = "SELECT
                          g.id, g.title, g.status, CONCAT_WS( \"-\", kd.year, LPAD(kd.month,2,0), LPAD(kd.date,2,0) ) as revisit_date
                      FROM
                          " . $wpdb->prefix . "gs_grants as g
                      LEFT JOIN
                          " . $wpdb->prefix . "gs_grant_key_dates as kd
                      ON
                        kd.grant_id = g.id
                      WHERE
                        date_title='revisit' AND g.status != 'S'
                      ORDER BY
                        $sort_by " . $sort_dir['current'];
        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql );

//        echo "==SQLQ384: $sql_query==<br>";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $content = '';

        $content .= '<div class="mass-edit-options" style="display:none">';
        $content .= '<input type="submit" name="mass-process" value="Bulk Edit Checked Items" id="mass-process">';
        $content .= '</div>';

        $content .= '<table id="report_form" class="revisit mass-editable">';
        $content .= '<tr>';
        $content .= '<th><input type="checkbox" name="mass_action" class="mass-action-all" value="all"></th>';
        $content .= '<th><a href="/editor/reports/revisit/?sb=id&sd=' . $sort_dir['id'] . '&pn=' . $page_num . '" class="' . $table_head_class['id'] . '">ID</a></th>';
        $content .= '<th>&nbsp;</th>';
        $content .= '<th>&nbsp;</th>';
        $content .= '<th><a href="/editor/reports/revisit/?sb=ti&sd=' . $sort_dir['ti'] . '&pn=' . $page_num . '" class="' . $table_head_class['ti'] . '">Title</a></th>';
        $content .= '<th><a href="/editor/reports/revisit/?sb=ti&sd=' . $sort_dir['st'] . '&pn=' . $page_num . '" class="' . $table_head_class['st'] . '">Status</a></th>';
        $content .= '<th><a href="/editor/reports/revisit/?sb=rd&sd=' . $sort_dir['rd'] . '&pn=' . $page_num . '" class="' . $table_head_class['rd'] . '">Revisit Date</a></th>';
        $content .= '</tr>';

        foreach( $data as $key=>$value ) :
            $content .= '<tr>';
            $content .= '<td><input type="checkbox" name="mass_action" class="mass-action-select" value="' . $value->id . '"></td>';
            $content .= '<td>' . $value->id . '</td>';
            $content .= '<td><a href="/editor/records/view/?gid=' . $value->id . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">View</a></td>';
            $content .= '<td><a href="/editor/records/edit/?gid=' . $value->id . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">Edit</a></td>';
            $content .= '<td>' . stripslashes($value->title) . '</td>';
            $content .= '<td>' . $value->status . '</td>';
            $content .= '<td>' . $value->revisit_date . '</td>';
            $content .= '</tr>';
        endforeach;

        $content .= "</table>";
        $content .= GrantSelectRecordsAddOn::mass_edits_modal();

        return $content;
    }


    /**
     * Function display_report_results_pending_records
     * @return $content
     */
    function display_report_results_pending_records() {

        global $wpdb;

        $table_head_class = array(  //initialize
            'id' => '',
            'ti' => '',
            'lm' => '',
            'cb' => ''
        );

        //sort_by
        if ( empty($_GET['sb']) ) {
            $sort_by     = 'id';
            $sort_by_raw = 'id';
            $table_head_class['id'] = 'sorted-by';
        } else {
            switch ($_GET['sb']) {
                case 'id':
                    $sort_by     = 'id';
                    $sort_by_raw = 'id';
                    $table_head_class['id'] = 'sorted-by';
                    break;
                case 'ti':
                    $sort_by     = 'title';
                    $sort_by_raw = 'ti';
                    $table_head_class['ti'] = 'sorted-by';
                    break;
                case 'lm':
                    $sort_by     = 'updated_at';
                    $sort_by_raw = 'lm';
                    $table_head_class['lm'] = 'sorted-by';
                    break;
                case 'cb':
                    $sort_by     = 'created_at';
                    $sort_by_raw = 'cb';
                    $table_head_class['cb'] = 'sorted-by';
                    break;
            }
        };

        if ( empty($_GET['sd']) ) {
            $sort_dir['current'] = 'ASC';
            $sort_dir['id']      = 'ASC';
            $sort_dir['ti']      = 'ASC';
            $sort_dir['lm']      = 'ASC';
            $sort_dir['cb']      = 'ASC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir['current'] = filter_var ( $_GET['sd'], FILTER_SANITIZE_STRIPPED );
            $sort_dir['id']      = $sort_dir['current'];
            $sort_dir['ti']      = $sort_dir['current'];
            $sort_dir['lm']      = $sort_dir['current'];
            $sort_dir['cb']      = $sort_dir['current'];
        };
        if ( $sort_dir['current'] == 'ASC' ) {
            $sort_dir[$sort_by_raw] = 'DESC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir[$sort_by_raw] = 'ASC';
            $table_head_class[$sort_by_raw] .= ' down';
        };

        if ( empty($_GET['pn']) ) {
            $page_num = 1;
        } else {
            $page_num = filter_var ( $_GET['pn'], FILTER_SANITIZE_STRIPPED );
        };

        $sql_query = "SELECT
                            id, title, updated_at, created_at FROM " . $wpdb->prefix . "gs_grants
                        WHERE
                            status = 'P'
                        ORDER BY
                            $sort_by " . $sort_dir['current'];

//        echo "SQLQ384: $sql_query<br>";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $content = '';

        $content .= '<div class="mass-edit-options" style="display:none">';
        $content .= '<input type="submit" name="mass-process" value="Bulk Edit Checked Items" id="mass-process">';
        $content .= '</div>';

        $content .= '<table id="report_form" class="pending-records mass-editable">';
        $content .= '<tr>';
        $content .= '<th><input type="checkbox" name="mass_action" class="mass-action-all" value="all"></th>';
        $content .= '<th><a href="/editor/reports/pending-records/?sb=id&sd=' . $sort_dir['id'] . '&pn=' . $page_num . '" class="' . $table_head_class['id'] . '">ID</a></th>';
        $content .= '<th>&nbsp;</th>';
        $content .= '<th>&nbsp;</th>';
        $content .= '<th><a href="/editor/reports/pending-records/?sb=ti&sd=' . $sort_dir['ti'] . '&pn=' . $page_num . '" class="' . $table_head_class['ti'] . '">Title</a></th>';
        $content .= '<th><a href="/editor/reports/pending-records/?sb=lm&sd=' . $sort_dir['lm']  . '&pn=' . $page_num . '" class="' . $table_head_class['lm'] . '">Last Modified</a></th>';
        $content .= '<th><a href="/editor/reports/pending-records/?sb=cb&sd=' . $sort_dir['cb']    . '&pn=' . $page_num . '" class="' . $table_head_class['cb'] . '">Check Back</a></th>';
        $content .= '</tr>';

        foreach( $data as $key=>$value ) :
            $content .= '<tr>';
            $content .= '<td><input type="checkbox" name="mass_action" class="mass-action-select" value="' . $value->id . '"></td>';
            $content .= '<td>' . $value->id . '</td>';
            $content .= '<td><a href="/editor/records/view/?gid=' . $value->id . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">View</a></td>';
            $content .= '<td><a href="/editor/records/edit/?gid=' . $value->id . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">Edit</a></td>';
            $content .= '<td>' . stripslashes($value->title) . '</td>';
            $content .= '<td>' . $value->updated_at . '</td>';
            $content .= '<td>' . $value->created_at . '</td>';
            $content .= '</tr>';
        endforeach;

        $content .= "</table>";
        $content .= GrantSelectRecordsAddOn::mass_edits_modal();

        return $content;
    }


    /**
     * Function display_report_results_missing_sponsors
     * @return $content
     */
    function display_report_results_missing_sponsors() {

        global $wpdb;

        $table_head_class = array(  //initialize
            'id' => '',
            'ti' => '',
            'st' => '',
            'ud' => ''
        );

        //sort_by
        if ( empty($_GET['sb']) ) {
            $sort_by     = 'id';
            $sort_by_raw = 'id';
            $table_head_class['id'] = 'sorted-by';
        } else {
            switch ($_GET['sb']) {
                case 'id':
                    $sort_by     = 'g.id';
                    $sort_by_raw = 'id';
                    $table_head_class['id'] = 'sorted-by';
                    break;
                case 'ti':
                    $sort_by     = 'g.title';
                    $sort_by_raw = 'ti';
                    $table_head_class['ti'] = 'sorted-by';
                    break;
                case 'st':
                    $sort_by     = 'g.status';
                    $sort_by_raw = 'st';
                    $table_head_class['st'] = 'sorted-by';
                    break;
                case 'ud':
                    $sort_by     = 'updated_at';
                    $sort_by_raw = 'ud';
                    $table_head_class['ud'] = 'sorted-by';
                    break;
            }
        };

        if ( empty($_GET['sd']) ) {
            $sort_dir['current'] = 'ASC';
            $sort_dir['id']      = 'ASC';
            $sort_dir['ti']      = 'ASC';
            $sort_dir['st']      = 'ASC';
            $sort_dir['ud']      = 'ASC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir['current'] = filter_var ( $_GET['sd'], FILTER_SANITIZE_STRIPPED );
            $sort_dir['id']      = $sort_dir['current'];
            $sort_dir['ti']      = $sort_dir['current'];
            $sort_dir['st']      = $sort_dir['current'];
            $sort_dir['ud']      = $sort_dir['current'];
        };
        if ( $sort_dir['current'] == 'ASC' ) {
            $sort_dir[$sort_by_raw] = 'DESC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir[$sort_by_raw] = 'ASC';
            $table_head_class[$sort_by_raw] .= ' down';
        }

        if ( empty($_GET['pn']) ) {
            $page_num = 1;
        } else {
            $page_num = filter_var ( $_GET['pn'], FILTER_SANITIZE_STRIPPED );
        };


        $sql_query = "SELECT
                          g.id, g.title, g.status, g.updated_at
                      FROM
                          " . $wpdb->prefix . "gs_grants as g
                      LEFT JOIN
                          " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings as gscm
                      ON
                        gscm.grant_id = g.id
                      WHERE
                        gscm.grant_id IS NULL || gscm.sponsor_id IS NULL
                      ORDER BY
                        $sort_by " . $sort_dir['current'];
        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql );

//        echo "==SQLQ1861: $sql_query==<br>";

        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $content = '<p>Each record below has either an <em>actual</em> or <em>potential</em> issue with its sponsor info. If the sponsor info looks OK, try editing both the sponsor info and contact info within the grant. In many cases this will reset the sponsor info mapping and fix the potential issue.</p>';

        $content .= '<div class="mass-edit-options" style="display:none">';
        $content .= '<input type="submit" name="mass-process" value="Bulk Edit Checked Items" id="mass-process">';
        $content .= '</div>';

        $content .= '<table id="report_form" class="missing-sponsors mass-editable">';
        $content .= '<tr>';
        $content .= '<th><input type="checkbox" name="mass_action" class="mass-action-all" value="all"></th>';
        $content .= '<th><a href="/editor/reports/missing-sponsors/?sb=id&sd=' . $sort_dir['id'] . '&pn=' . $page_num . '" class="' . $table_head_class['id'] . '">ID</a></th>';
        $content .= '<th>&nbsp;</th>';
        $content .= '<th>&nbsp;</th>';
        $content .= '<th><a href="/editor/reports/missing-sponsors/?sb=ti&sd=' . $sort_dir['ti'] . '&pn=' . $page_num . '" class="' . $table_head_class['ti'] . '">Title</a></th>';
        $content .= '<th><a href="/editor/reports/missing-sponsors/?sb=st&sd=' . $sort_dir['st'] . '&pn=' . $page_num . '" class="' . $table_head_class['st'] . '">Status</a></th>';
        $content .= '<th><a href="/editor/reports/missing-sponsors/?sb=ud&sd=' . $sort_dir['ud'] . '&pn=' . $page_num . '" class="' . $table_head_class['ud'] . '">Last Updated</a></th>';
        $content .= '</tr>';

        if ( count($data) == 0 ) {
            $content .= '<tr>';
            $content .= '<td colspan="6">(No records found)</td>';
            $content .= '</tr>';
        }

        foreach( $data as $key=>$value ) :
            $content .= '<tr>';
            $content .= '<td><input type="checkbox" name="mass_action" class="mass-action-select" value="' . $value->id . '"></td>';
            $content .= '<td>' . $value->id . '</td>';
            $content .= '<td><a href="/editor/records/view/?gid=' . $value->id . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">View</a></td>';
            $content .= '<td><a href="/editor/records/edit/?gid=' . $value->id . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">Edit</a></td>';
            $content .= '<td>' . stripslashes($value->title) . '</td>';
            $content .= '<td>' . $value->status . '</td>';
            $content .= '<td>' . $value->updated_at . '</td>';
            $content .= '</tr>';
        endforeach;

        $content .= "</table>";
        $content .= GrantSelectRecordsAddOn::mass_edits_modal();

        return $content;
    }


    /**
     * Function display_report_form_users_search_criteria
     * @return $content
     */
    function display_report_form_users_search_criteria()
    {
        //date range
        $date_range = filter_var ( $_SESSION['users-search-criteria']['date_range'], FILTER_SANITIZE_STRIPPED );

        //from date
        if ( !empty($_SESSION['users-search-criteria']['date_day_from']) ) {
            $date_day_from   = filter_var ( $_SESSION['users-search-criteria']['date_day_from'], FILTER_SANITIZE_STRIPPED );
        } else {
            $date_day_from   = date("j");
        }
        if ( !empty($_SESSION['users-search-criteria']['date_month_from']) ) {
            $date_month_from   = filter_var ( $_SESSION['users-search-criteria']['date_month_from'], FILTER_SANITIZE_STRIPPED );
        } else {
            $date_month_from   = date("n");
        }
        if ( !empty($_SESSION['users-search-criteria']['date_year_from']) ) {
            $date_year_from   = filter_var ( $_SESSION['users-search-criteria']['date_year_from'], FILTER_SANITIZE_STRIPPED );
        } else {
            $date_year_from   = date("Y");
        }

        //to date
        if ( !empty($_SESSION['users-search-criteria']['date_day_to']) ) {
            $date_day_to   = filter_var ( $_SESSION['users-search-criteria']['date_day_to'], FILTER_SANITIZE_STRIPPED );
        } else {
            $date_day_to   = date("j");
        }
        if ( !empty($_SESSION['users-search-criteria']['date_month_to']) ) {
            $date_month_to   = filter_var ( $_SESSION['users-search-criteria']['date_month_to'], FILTER_SANITIZE_STRIPPED );
        } else {
            $date_month_to   = date("n");
        }
        if ( !empty($_SESSION['users-search-criteria']['date_year_to']) ) {
            $date_year_to   = filter_var ( $_SESSION['users-search-criteria']['date_year_to'], FILTER_SANITIZE_STRIPPED );
        } else {
            $date_year_to   = date("Y");
        }

        $content = '<p class="record-description">Choose a date range and click a report button to view/refresh data:</p>';

        $content .= '<form method="post" action="">';

        $content .= "<p class=\"date-range-label\">Date Range</p>";

        $preset_date_ranges = array(
            'today'         => 'Today',
            'yesterday'     => 'Yesterday',
            'prev_week'     => 'Previous 7 Days',
            'curr_month'    => 'This Month',
            'last_month'    => 'Last Month',
            'prev_30_days'  => 'Previous 30 Days',
            'curr_year'     => 'This Year',
            'last_year'     => 'Last Year',
            'prev_12_mo'    => 'Previous 12 months',
            'custom'        => 'Custom Dates'
        );
        $content .= '<select name="date_range" class="usc-date-range-select" size="1">';
        foreach ($preset_date_ranges as $slug=>$period) {
            $content .= '<option value="' . $slug . '"';
            if ($date_range == $slug) {
                $content .= ' selected=""';
            }
            $content .= '>' . $period . '</option>';
        }
        $content .= '</select>';

        $content .= '<div class="custom-dates">';
        $content .= '<select name="date_month_from">';
        for ( $i=1; $i<=12; $i++ ) {
            $content .= "<option value=\"$i\"";
            if (intval($date_month_from) == $i) {
                $content .= ' selected=""';
            }
            $content .= ">" . self::MONTH_ARRAY[$i] . "</option>";
        }
        $content .= '</select>';

        $content .= '<select name="date_day_from">';
        for ( $i=1; $i<=31; $i++ ) {
            $content .= "<option value=\"$i\"";
            if (intval($date_day_from) == $i) {
                $content .= ' selected=""';
            }
            $content .= ">$i</option>";
        }
        $content .= '</select>';

        $current_year = intval(date("Y"));
        $content .= '<select name="date_year_from">';
        for ( $i=10; $i>=0; $i-- ) {
            $content .= '<option value="' . ($current_year - $i) . '"';
            if (intval($date_year_from) == ($current_year - $i)) {
                $content .= ' selected=""';
            }
            $content .= '>' . ($current_year - $i) . '</option>';
        }
        $content .= '</select>';

        $content .= ' &mdash; ';

        $content .= '<select name="date_month_to">';
        for ( $i=1; $i<=12; $i++ ) {
            $content .= "<option value=\"$i\"";
            if (intval($date_month_to) == $i) {
                $content .= ' selected=""';
            }
            $content .= ">" . self::MONTH_ARRAY[$i] . "</option>";
        }
        $content .= '</select>';

        $content .= '<select name="date_day_to">';
        for ( $i=1; $i<=31; $i++ ) {
            $content .= "<option value=\"$i\"";
            if (intval($date_day_to) == $i) {
                $content .= ' selected=""';
            }
            $content .= ">$i</option>";
        }
        $content .= '</select>';

        $current_year = intval(date("Y"));
        $content .= '<select name="date_year_to">';
        for ( $i=10; $i>=0; $i-- ) {
            $content .= '<option value="' . ($current_year - $i) . '"';
            if (intval($date_year_to) == ($current_year - $i)) {
                $content .= ' selected=""';
            }
            $content .= '>' . ($current_year - $i) . '</option>';
        }
        $content .= '</select>';
        $content .= '</div>';
        $content .= '<input type="hidden" name="mode" value="display">';
        $content .= '<input type="submit" value="Keywords Report" name="report">';
        $content .= '<input type="submit" value="Geographic Locations Report" name="report">';
        $content .= '<input type="submit" value="Subject Headings Report" name="report">';
        $content .= '</form>';

        return $content;
    }


    /**
     * Function display_report_results_users_search_criteria
     * @return $content
     */
    function display_report_results_users_search_criteria() {

        global $wpdb;

        $date_range = filter_var ( $_SESSION['users-search-criteria']['date_range'], FILTER_SANITIZE_STRIPPED );

        if ($date_range == 'today') {
            $begin_date = gmdate("Y-m-d H:i:s", strtotime("today midnight"));
            $end_date   = gmdate("Y-m-d H:i:s", strtotime("tomorrow midnight"));
        } elseif ($date_range == 'yesterday') {
            $begin_date = gmdate("Y-m-d H:i:s", strtotime("yesterday midnight"));
            $end_date   = gmdate("Y-m-d H:i:s", strtotime("today midnight"));
        } elseif ($date_range == 'prev_week') {
            $begin_date = gmdate("Y-m-d H:i:s", strtotime("today -7 days midnight"));
            $end_date   = gmdate("Y-m-d H:i:s", strtotime("today midnight"));
        } elseif ($date_range == 'curr_month') {
            $begin_date = gmdate("Y-m-d H:i:s", strtotime("first day of this month midnight"));
            $end_date   = gmdate("Y-m-d H:i:s", strtotime("first day of next month midnight"));
        } elseif ($date_range == 'last_month') {
            $begin_date = gmdate("Y-m-d H:i:s", strtotime("first day of previous month midnight"));
            $end_date   = gmdate("Y-m-d H:i:s", strtotime("first day of this month midnight"));
        } elseif ($date_range == 'prev_30_days') {
            $begin_date = gmdate("Y-m-d H:i:s", strtotime("today -30 days midnight"));
            $end_date   = gmdate("Y-m-d H:i:s", strtotime("today midnight"));
        } elseif ($date_range == 'curr_year') {
            $begin_date = gmdate("Y-m-d H:i:s", strtotime("first day of january this year midnight"));
            $end_date   = gmdate("Y-m-d H:i:s", strtotime("first day of january next year midnight"));
        } elseif ($date_range == 'last_year') {
            $begin_date = gmdate("Y-m-d H:i:s", strtotime("first day of january last year midnight"));
            $end_date   = gmdate("Y-m-d H:i:s", strtotime("first day of january this year midnight"));
        } elseif ($date_range == 'prev_12_mo') {
            $begin_date = gmdate("Y-m-d H:i:s", strtotime("today -12 months midnight"));
            $end_date   = gmdate("Y-m-d H:i:s", strtotime("today midnight"));
        } elseif ($date_range == 'custom') {
            $date_year_from     = filter_var ( $_SESSION['users-search-criteria']['date_year_from'], FILTER_SANITIZE_STRIPPED );
            $date_month_from    = filter_var ( $_SESSION['users-search-criteria']['date_month_from'], FILTER_SANITIZE_STRIPPED );
            $date_day_from      = filter_var ( $_SESSION['users-search-criteria']['date_day_from'], FILTER_SANITIZE_STRIPPED );
            $date_year_to       = filter_var ( $_SESSION['users-search-criteria']['date_year_to'], FILTER_SANITIZE_STRIPPED );
            $date_month_to      = filter_var ( $_SESSION['users-search-criteria']['date_month_to'], FILTER_SANITIZE_STRIPPED );
            $date_day_to        = filter_var ( $_SESSION['users-search-criteria']['date_day_to'], FILTER_SANITIZE_STRIPPED );

            $begin_date = gmdate("Y-m-d H:i:s", strtotime("$date_month_from" . "/" . $date_day_from . "/" . $date_year_from . " midnight"));
            $end_date   = gmdate("Y-m-d H:i:s", strtotime("$date_month_to" . "/" . $date_day_to . "/" . $date_year_to . " +1 day midnight"));
        }

        //report type
        if ( !empty($_SESSION['users-search-criteria']['report']) ) {
            $report = filter_var ( $_SESSION['users-search-criteria']['report'], FILTER_SANITIZE_STRIPPED );
        } else {
            $report = '';
        }

//        echo "From: " . $date_year_from . "/" . $date_month_from . "/" . $date_day_from . "<br>";
//        echo "To: " . $date_year_to . "/" . $date_month_to . "/" . $date_day_to . "<br>";

        $table_head_class = array(  //initialize
            'kw' => '',
            'gl' => '',
            'st' => '',
            'ct' => ''
        );

        //sort_by
        if ( empty($_GET['sb']) || !empty($_POST['report']) ) {
            $sort_by     = 'count';
            $sort_by_raw = 'ct';
            $table_head_class['ct'] = 'sorted-by';
        } else {
            switch ($_GET['sb']) {
                case 'kw':
                    $sort_by     = 'keyword';
                    $sort_by_raw = 'kw';
                    $table_head_class['kw'] = 'sorted-by';
                    break;
                case 'gl':
                    $sort_by     = 'geo_location';
                    $sort_by_raw = 'gl';
                    $table_head_class['gl'] = 'sorted-by';
                    break;
                case 'st':
                    $sort_by     = 'subject_title';
                    $sort_by_raw = 'st';
                    $table_head_class['st'] = 'sorted-by';
                    break;
                case 'ct':
                    $sort_by     = 'count';
                    $sort_by_raw = 'ct';
                    $table_head_class['ct'] = 'sorted-by';
                    break;
            }
        };

        if ( empty($_GET['sd']) || !empty($_POST['report']) ) {
            $sort_dir['current'] = 'DESC';
            $sort_dir['kw']      = 'DESC';
            $sort_dir['gl']      = 'DESC';
            $sort_dir['st']      = 'DESC';
            $sort_dir['ct']      = 'DESC';
            $table_head_class[$sort_by_raw] .= ' down';
        } else {
            $sort_dir['current'] = filter_var ( $_GET['sd'], FILTER_SANITIZE_STRIPPED );
            $sort_dir['kw']      = $sort_dir['current'];
            $sort_dir['gl']      = $sort_dir['current'];
            $sort_dir['st']      = $sort_dir['current'];
            $sort_dir['ct']      = $sort_dir['current'];
        };
        if ( $sort_dir['current'] == 'ASC' ) {
            $sort_dir[$sort_by_raw] = 'DESC';
            $table_head_class[$sort_by_raw] .= ' up';
        } else {
            $sort_dir[$sort_by_raw] = 'ASC';
            $table_head_class[$sort_by_raw] .= ' down';
        }

        if ( empty($_GET['pn']) || !empty($_POST['report']) ) {
            $page_num = 1;
        } else {
            $page_num = filter_var ( $_GET['pn'], FILTER_SANITIZE_STRIPPED );
        };

//        echo "<pre>";
//        print_r($_SESSION);
//        echo "</pre>";


        $order_by = $sort_by . " " . $sort_dir['current'];
        if ($sort_by == 'count') {
            switch ($report) {
                case 'Keywords Report':
                    $order_by .= ', keyword ASC';
                    break;
                case 'Geographic Locations Report':
                    $order_by .= ', geo_location ASC';
                    break;
                case 'Subject Headings Report':
                    $order_by .= ', subject_title ASC';
                    break;
            }
        }

        switch ($report) {
            case 'Keywords Report':
                //quick search: form_id=1, meta_key=1
                //adv search: form_id=2, meta_key=1
                $sql_query = "SELECT
                                  em.meta_value as keyword, count(em.meta_value) AS count
                              FROM
                                  " . $wpdb->prefix . "gf_entry_meta as em
                              LEFT JOIN
                                  " . $wpdb->prefix . "gf_entry as e
                              ON
                                  em.entry_id = e.id
                              WHERE
                                  ((em.form_id = 1 AND em.meta_key = 1) OR (em.form_id = 2 AND em.meta_key = 1)) AND
                                  (e.date_created >= CAST(%s AS DATETIME) AND e.date_created < CAST(%s AS DATETIME))
                              GROUP BY
                                  em.meta_value
                              ORDER BY
                                  $order_by";
                break;
            case 'Geographic Locations Report':
                //quick search: form_id=1, meta_key=2.x (domestic), geo_location
                //adv search: form_id=2, (meta_key=2.x (domestic), 3.x (foreign)), geo_location
                $sql_query = "SELECT
                                  gl.geo_location, count(em.meta_value) AS count
                              FROM
                                  " . $wpdb->prefix . "gf_entry_meta as em
                              LEFT JOIN
                                  " . $wpdb->prefix . "gs_grant_geo_locations as gl
                              ON
                                  em.meta_value = gl.id
                              LEFT JOIN
                                  " . $wpdb->prefix . "gf_entry as e
                              ON
                                  em.entry_id = e.id
                              WHERE
                                  ((em.form_id = 1 AND em.meta_key LIKE '2.%') OR (em.form_id = 2 AND (em.meta_key LIKE '2.%' OR em.meta_key LIKE '3.%' ))) AND
                                  (e.date_created >= CAST(%s AS DATETIME) AND e.date_created < CAST(%s AS DATETIME)) AND
                                  gl.geo_location != ''
                              GROUP BY
                                  em.meta_value
                              ORDER BY
                                  $order_by";
                break;
            case 'Subject Headings Report':
                //quick search: form_id=1, meta_key=3.x, subject_title
                //adv search: form_id=2, meta_key=5.x, subject_title
                $sql_query = "SELECT
                                  gs.subject_title, count(em.meta_value) AS count
                              FROM
                                  " . $wpdb->prefix . "gf_entry_meta as em
                              LEFT JOIN
                                  " . $wpdb->prefix . "gs_grant_subjects as gs
                              ON
                                  em.meta_value = gs.id
                              LEFT JOIN
                                  " . $wpdb->prefix . "gf_entry as e
                              ON
                                  em.entry_id = e.id
                              WHERE
                                  ((em.form_id = 1 AND em.meta_key LIKE '3.%') OR (em.form_id = 2 AND em.meta_key LIKE '5.%')) AND
                                  (e.date_created >= CAST(%s AS DATETIME) AND e.date_created < CAST(%s AS DATETIME)) AND
                                  gs.subject_title != ''
                              GROUP BY
                                  em.meta_value
                              ORDER BY
                                  $order_by";
                break;
        }

        $sql = $wpdb->prepare( $sql_query, $begin_date, $end_date );
        $data = $wpdb->get_results( $sql );

//        echo "SQL: $sql<br>";

//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $content = '<p class="date-range-indicator"><span class="report-label">' . ucfirst($report) . '</span> for <span class="date-label">' . date( "n/j/Y", strtotime($begin_date . " UTC")) . '&ndash;' . date( "n/j/Y", strtotime($end_date . " -1 day UTC")) . '</span><button class="export-report" style="float:right" data-value="'.$report.'">Export</button></p>';
        $content .= '<table id="report_form" class="users-search-criteria">';
        $content .= '<tr>';
        switch ($report) {
            case 'Keywords Report':
                $content .= '<th><a href="/editor/reports/users-search-criteria/?sb=kw&sd=' . $sort_dir['kw'] . '&pn=' . $page_num . '" class="' . $table_head_class['kw'] . '">Keyword</a></th>';
                break;
            case 'Geographic Locations Report':
                $content .= '<th><a href="/editor/reports/users-search-criteria/?sb=gl&sd=' . $sort_dir['gl'] . '&pn=' . $page_num . '" class="' . $table_head_class['gl'] . '">Geographic Location</a></th>';
                break;
            case 'Subject Headings Report':
                $content .= '<th><a href="/editor/reports/users-search-criteria/?sb=st&sd=' . $sort_dir['st'] . '&pn=' . $page_num . '" class="' . $table_head_class['st'] . '">Subject Heading</a></th>';
                break;
        }
        $content .= '<th><a href="/editor/reports/users-search-criteria/?sb=ct&sd=' . $sort_dir['ct'] . '&pn=' . $page_num . '" class="' . $table_head_class['ct'] . '">Times Searched</a></th>';
        $content .= '</tr>';

        switch ($report) {
            case 'Keywords Report':
                $search_criteria = 'keywords';
                break;
            case 'Geographic Locations Report':
                $search_criteria = 'geographic locations';
                break;
            case 'Subject Headings Report':
                $search_criteria = 'subject headings';
                break;
        }

        if ( empty($data) ) {
            $content .= '<tr>';
            $content .= '<td colspan="2" class="none-found">(No ' . $search_criteria . ' searched during the selected period)</td>';
            $content .= '</tr>';
        } else {
            $total_count = 0;
            foreach( $data as $key=>$value ) :
                $content .= '<tr>';
                switch ($report) {
                    case 'Keywords Report':
                        $content .= '<td>' . stripslashes($value->keyword) . '</td>';
                        break;
                    case 'Geographic Locations Report':
                        $content .= '<td>' . stripslashes($value->geo_location) . '</td>';
                        break;
                    case 'Subject Headings Report':
                        $content .= '<td>' . stripslashes($value->subject_title) . '</td>';
                        break;
                }
                $content .= '<td>' . $value->count . '</td>';
                $content .= '</tr>';
                $total_count += $value->count;
            endforeach;
        }

        //Put total at the bottom of count table
        $content .= '<td class="table-total label">Total</td>';
        $content .= '<td class="table-total value">' . number_format($total_count) . '</td>';

        $content .= "</table>";
        if ($_POST['mode'] == "csv"){
            $filename = $report . ".csv";
            $delimiter=",";
            ob_end_clean();
            // tell the browser it's going to be a csv file
            header('Content-Type: application/csv');
            // tell the browser we want to save it instead of displaying it
            header('Content-Disposition: attachment; filename="'.$filename.'";');

            $f = fopen('php://output', 'w'); 
            if ( empty($data) ) {
                fputcsv($f, array('(No ' . $search_criteria . ' searched during the selected period'), $delimiter);
            } else {
                $total_count = 0;
                switch ($report) {
                    case 'Keywords Report':
                        fputcsv($f, array('KEYWORD', 'TIMES SEARCHED'), $delimiter);
                        break;
                    case 'Geographic Locations Report':
                        fputcsv($f, array('Geographic Location', 'TIMES SEARCHED'), $delimiter);
                        break;
                    case 'Subject Headings Report':
                        fputcsv($f, array('Subject Heading', 'TIMES SEARCHED'), $delimiter);
                        break;
                }
                
                foreach( $data as $key=>$value ) :
                    switch ($report) {
                        case 'Keywords Report':
                            fputcsv($f, array($value->keyword, $value->count), $delimiter);
                            break;
                        case 'Geographic Locations Report':
                            fputcsv($f, array($value->geo_location, $value->count), $delimiter);
                            break;
                        case 'Subject Headings Report':
                            fputcsv($f, array($value->subject_title, $value->count), $delimiter);
                            break;
                    }
                endforeach;
            }
            ob_flush();
            exit();
        }
        return $content;
    }


    /**
     * Function  get_last_editor
     * @params $grant_id
     * @return editor_name
     */
    private function get_last_editor( $grant_id )
    {
        $editor_name = '';

        global $wpdb;

        $sql_query = "SELECT id, editor_id FROM " . $wpdb->prefix . "gs_editor_transactions WHERE grant_id=%d ORDER BY id DESC LIMIT 1";

        $sql = $wpdb->prepare( $sql_query, $grant_id );

        $data = $wpdb->get_results( $sql );

        $editor_id = $data[0]->editor_id;

        if ($editor_id != '') {
            $sql_query = "SELECT display_name FROM " . $wpdb->prefix . "users WHERE ID=%d";

            $sql = $wpdb->prepare( $sql_query, $editor_id );
            $data = $wpdb->get_results( $sql );

            $editor_name = $data[0]->display_name;
        }
        return $editor_name;
    }


    /**
     * Function  get_last_editor_sponsor
     * @params $sponsor_id
     * @return editor_name
     */
    private function get_last_editor_sponsor($sponsor_id)
    {
        $editor_name = '';

        global $wpdb;

        $sql_query = "SELECT id, editor_id FROM " . $wpdb->prefix . "gs_editor_transactions WHERE sponsor_id=%d ORDER BY id DESC LIMIT 1";

        $sql = $wpdb->prepare( $sql_query, $sponsor_id );

        $data = $wpdb->get_results( $sql );

        $editor_id = $data[0]->editor_id;

        if ($editor_id != '') {
            $sql_query = "SELECT display_name FROM " . $wpdb->prefix . "users WHERE ID=%d";

            $sql = $wpdb->prepare( $sql_query, $editor_id );
            $data = $wpdb->get_results( $sql );

            $editor_name = $data[0]->display_name;
        }
        return $editor_name;
    }
}