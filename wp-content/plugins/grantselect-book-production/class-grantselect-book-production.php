<?php

// Make sure Gravity Forms is active and already loaded.
if (!class_exists('GFForms')) {
    die();
}

GFForms::include_feed_addon_framework();

/**
 * GrantSelectBookProductionAddOn
 *
 * @copyright   Copyright (c) 2020-2021, GrantSelect
 * @since       1.0
 */
class GrantSelectBookProductionAddOn extends GFFeedAddOn {

    protected $_version = GF_GRANTSELECT_BOOK_PRODUCTION_ADDON_VERSION;
    protected $_min_gravityforms_version = '2.4.23';
    protected $_slug = 'grantselect-book-production';
    protected $_path = 'grantselect-book-production/grantselect-book-production.php';
    protected $_full_path = __FILE__;
    protected $_title = 'GrantSelect Book Production Functionality';
    protected $_short_title = 'GrantSelect Book Production Functionality';

    const BOOK_TITLES = array(
        'Humanities'=>'Directory of Grants in the Humanities',
        'Community Development'=>'Funding Sources for Community and Economic Development',
        'Children and Youth'=>'Funding Sources for Children and Youth Programs',
        'K-12 School Programs'=>'Funding Sources for K-12 Education',
        'Research'=>'Directory of Research Grants',
        'Biomedical and Health Care'=>'Directory of Biomedical and Health Care Grants',
        'Operating Costs'=>'Operating Grants for Nonprofit Organizations',
        'Faith-Based Programs'=>'Funding Sources for Faith-Based Programs',
        'Community Colleges'=>'Funding Sources for Community Colleges',
        'Higher Education (non-research)' => 'Directory of Grants for Higher Education (non-research)',
        'Canada' => 'Directory of Canadian Funding Sources',
        'Environmental Programs' => 'Directory of Environmental Funding Sources',
        'Black & African American' => 'Funding Sources for Blacks & African American Organizations',
        'Native American' => 'Funding Sources for Native American/Alaskan Organizations',
        'Arts and Culture' => 'Directory of Grants in Arts and Culture',
        'Agricultural Programs' => 'Funding Sources for Agricultural Programs',
        'Fellowships' => 'Directory of Research Fellowships',
        'Environmental Programs' => 'Funding Sources for Conservation and the Environment',
        'Scholarships' => 'Directory of Scholarships',
        'Human Services' => 'Funding Sources for Human Services'
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

    // function for book production content shortcode
    public function grantselect_book_production_content( $atts ) {

//        echo "<pre>";
//        print_r($atts);
//        echo "</pre>";

        $entry_id = absint( $_GET['bpid'] );
        $current_user = get_current_user_id();

        if ( is_array( $atts ) ) {
            $display_mode = $atts['display'];
        } else {
            $display_mode = '';
        }

        switch ( $display_mode ) {
            case 'preview':
                $entry_id = absint( $_GET['bpid'] );
                $page = absint( $_GET['pn'] );  //display this page of the results
                if ( empty($page) ) $page = 1;
                $display_content = "";  //initialize
                $display_content .= "<div class='grantselect-book-production-results'>";
                $display_content .= self::display_book_production_results( $current_user, $entry_id, 'preview', $page );
                $display_content .= "</div>";
                break;
            case 'book':
                $entry_id = absint( $_GET['bpid'] );
                $display_content = "";  //initialize
                $display_content .= self::display_book_production_results( $current_user, $entry_id, 'book' );
                break;
            case 'subject-index':
                $entry_id = absint( $_GET['bpid'] );
                $display_content = "";  //initialize
                $display_content .= self::display_book_production_results( $current_user, $entry_id, 'subject-index' );
                break;
            case 'program-index':
                $entry_id = absint( $_GET['bpid'] );
                $display_content = "";  //initialize
                $display_content .= self::display_book_production_results( $current_user, $entry_id, 'program-index' );
                break;
            case 'geo-us-index':
                $entry_id = absint( $_GET['bpid'] );
                $display_content = "";  //initialize
                $display_content .= self::display_book_production_results( $current_user, $entry_id, 'geo-us-index' );
                break;
            case 'geo-foreign-index':
                $entry_id = absint( $_GET['bpid'] );
                $display_content = "";  //initialize
                $display_content .= self::display_book_production_results( $current_user, $entry_id, 'geo-foreign-index' );
                break;
            case 'editors':
                $entry_id = absint( $_GET['bpid'] );
                $display_content = "";  //initialize
                $display_content .= self::display_book_production_results( $current_user, $entry_id, 'editors' );
                break;
            case 'subject-titles':
                $entry_id = absint( $_GET['bpid'] );
                $display_content = "";  //initialize
                $display_content .= self::display_book_production_results( $current_user, $entry_id, 'subject-titles' );
                break;
            case 'program-titles':
                $entry_id = absint( $_GET['bpid'] );
                $display_content = "";  //initialize
                $display_content .= self::display_book_production_results( $current_user, $entry_id, 'program-titles' );
                break;
            default:
                $display_content = "";  //initialize
                $display_content .= "<div class='grantselect-book-production-content'>";
                $display_content .= self::book_production_content( $current_user, $entry_id );
                $display_content .= "</div>";
                break;
        }


        //////////////////// LEFT OFF HERE //////////////////////////////



        return $display_content;
    }

    /**
     * Function book_production_content
     * $user_id         = ID of user to whom the results belong
     * $entry_id        = Gravity Forms entry_id for the submitted book production form
     *
     * @return $out_result
     */
    function book_production_content( $user_id, $entry_id )
    {
        if ( empty($entry_id) ) {
            return false;
        }
        $entry = GFAPI::get_entry( $entry_id );

        $created_by = 'xXxXx';
        if ( !is_wp_error($entry) ) {
            $created_by = $entry['created_by'];
        }

//        echo "UID:$user_id<br>";
//        echo "CB:$created_by<br>";

//        echo "<pre>";
//        print_r($entry);
//        echo "</pre>";

        if ( $user_id != $created_by ) {
            $out_result = array(
                'error'    => true,
                'error_msg' => "<h2>Access denied</h2><p>You are not logged in to the appropriate account for accessing this content.</p>",
            );
            $content = '<div class="search-error">';
            $content .= $out_result['error_msg'];
            $content .= '</div>';

            return $content;
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

//        echo "<pre>";
//        echo "ECS:\n";
//        print_r($entry_checkbox_selections);
//        echo "</pre>";

        $segment_id = $entry[1];
        $segment_list = GrantSelectRecordsAddOn::get_segment_list();
        $segment_title = '';
        foreach ( $segment_list as $key => $value ) {
            if ( $value->id == $segment_id ) {
                $segment_title = self::BOOK_TITLES[$value->segment_title];
                break;
            }
        }

        if ( !empty($entry_checkbox_selections[2]) ) {
            $entry_checkbox_selections[2][1] = 1;   //add "all states" if it's not already included
            sort($entry_checkbox_selections[2]);
            $geo_ids_domestic = array_values($entry_checkbox_selections[2]);
        } else {
            $geo_ids_domestic = array();
        }
        if ( !empty($entry_checkbox_selections[3]) ) {
            $entry_checkbox_selections[3][1] = 247;   //add "all countries" if it's not already included
            sort($entry_checkbox_selections[3]);
            $geo_ids_foreign = array_values($entry_checkbox_selections[3]);
        } else {
            $geo_ids_foreign = array();
        }
        if (!empty($geo_ids_domestic) || !empty($geo_ids_foreign)) {
            $geo_locations = '';    //initialize
            $geo_locations_list = GrantSelectSearchAddOn::get_geo_locations_list();
            $geo_locations_list[1] = (object) array( 'id'=>1, 'geo_locale'=>'domestic', 'geo_location'=>'All States');
            $geo_locations_list[247] = (object) array( 'id'=>247, 'geo_locale'=>'foreign', 'geo_location'=>'All Countries');
            foreach ( $geo_ids_domestic as $geo_id ) {
                if ( empty($geo_locations) ) {
                    $geo_locations = $geo_locations_list[ $geo_id ]->geo_location;
                } else {
                    $geo_locations .= ', ' . $geo_locations_list[ $geo_id ]->geo_location;
                }
            }
            foreach ( $geo_ids_foreign as $geo_id ) {
                if ( empty($geo_locations) ) {
                    $geo_locations = $geo_locations_list[ $geo_id ]->geo_location;
                } else {
                    $geo_locations .= ', ' . $geo_locations_list[ $geo_id ]->geo_location;
                }
            }
        } else {
            $geo_locations = '(none specified)';
        }

        if ( !empty($entry_checkbox_selections[4]) ) {
            $subject_headings_ids = array_values($entry_checkbox_selections[4]);
        } else {
            $subject_headings_ids = array();
        }
        if ( !empty($subject_headings_ids) ) {
            $subject_headings = '';    //initialize
            $subjects_list = GrantSelectSearchAddOn::get_subjects_list();
            foreach ( $subject_headings_ids as $subject_id ) {
                if ( empty($subject_headings) ) {
                    $subject_headings = $subjects_list[ $subject_id ]->subject_title;
                } else {
                    $subject_headings .= ', ' . $subjects_list[ $subject_id ]->subject_title;
                }
            }
        } else {
            $subject_headings = '(none specified)';
        }

        if ( !empty($entry[8]) ) {
            switch ( $entry[8] ) {
                case 'newer':
                    $less_or_more = 'New On or Later Than';
                    break;
                case 'older':
                    $less_or_more = 'Older Than';
                    break;
            }
        }

        $range_month = $entry[9];
        $range_day = $entry[10];
        $range_year = $entry[11];

        ?>
        <div id="content_wrapper">
            <div id="content_left">

                <p><a href="/editor/book-production/?bpid=<?= $entry_id ?>">Change book criteria</a></p>

                <h2>Book</h2>
                <p><?= $segment_title ?></p>

                <h2>Geographic Location</h2>
                <p><?= $geo_locations ?></p>

                <h2>Subject Headings</h2>
                <p><?= $subject_headings ?></p>

                <h2>Date Range</h2>
                <p><?= $less_or_more ?> <?= $range_month ?>/<?= $range_day ?>/<?= $range_year ?></p>

                <h2>Preview</h2>
                <ul>
                    <li><a href="/editor/book-production/content/preview/?bpid=<?php echo $entry_id ?>">Book Content</a></li>
                </ul>

                <h2>Data Dumps</h2>
                <ul>
                    <li><a href="/editor/book-production/content/book/?bpid=<?php echo $entry_id ?>">Book Content</a></li>
                    <li><a href="/editor/book-production/content/subject-index/?bpid=<?php echo $entry_id ?>">Subject Index</a></li>
                    <li><a href="/editor/book-production/content/program-index/?bpid=<?php echo $entry_id ?>">Program Type Index</a></li>
                    <li><a href="/editor/book-production/content/geo-us-index/?bpid=<?php echo $entry_id ?>">Geo-US Index</a></li>
                    <li><a href="/editor/book-production/content/geo-foreign-index/?bpid=<?php echo $entry_id ?>">Geo-Foreign Index</a></li>
                    <li><a href="/editor/book-production/content/editors/?bpid=<?php echo $entry_id ?>">Contributing Editors</a></li>
                </ul>

                <form action="/editor/book-production/content/subject-index/?bpid=<?php echo $entry_id ?>" method="POST">
                    <a href="/editor/book-production/content/subject-titles/?bpid=<?php echo $entry_id ?>" target="_new">Subject Titles</a><br>
                    <p>Subject Index</p>
                    <input type="hidden" name="type" value="subject_index">
                    From: <input type="text" name="limit_from" value="" size="20">
                    To: <input type="text" name="limit_to" value="" size="20">
                    <input type="submit" name="submit" value="Generate">
                </form>

                <form action="/editor/book-production/content/program-index/?bpid=<?php echo $entry_id ?>" method="POST">
                    <a href="/editor/book-production/content/program-titles/?bpid=<?php echo $entry_id ?>" target="_new">Program Titles</a><br>
                    <p>Program Type Index</p>
                    <input type="hidden" name="type" value="program_type_index">
                    From: <input type="text" name="limit_from" value="" size="20">
                    To: <input type="text" name="limit_to" value="" size="20">
                    <input type="submit" name="submit" value="Generate">
                </form>

            </div>
        </div>
        <?php

    }


    /**
     * Function display_book_production_results
     * $user_id         = ID of user to whom the results belong
     * $entry_id        = Gravity Forms entry_id for the submitted book production form
     * $display_mode    = Format to display results in. Valid options: "preview", "book"
     * $num_of_page     = Which page of results should be displayed
     *
     * @return $out_result
     */
    function display_book_production_results( $user_id, $entry_id, $display_mode, $num_of_page='' )
    {
        if (empty($entry_id)) {
            return false;
        }
        $entry = GFAPI::get_entry($entry_id);

        $created_by = 'xXxXx';
        if (!is_wp_error($entry)) {
            $created_by = $entry['created_by'];
        }

//        echo "UID:$user_id<br>";
//        echo "CB:$created_by<br>";

//        echo "<pre>";
//        print_r($entry);
//        echo "</pre>";

        if ($user_id != $created_by) {
            $out_result = array(
                'error' => true,
                'error_msg' => "<h2>Access denied</h2><p>You are not logged in to the appropriate account for accessing this content.</p>",
            );
            $content = '<div class="search-error">';
            $content .= $out_result['error_msg'];
            $content .= '</div>';

            return $content;
        }

        //generate array of ticked checkboxes
        $entry_checkbox_selections = array();   //initialize
        $entry_ids = array_keys($entry);
        foreach ($entry_ids as $eid) {
            if (strpos($eid, '.')) {
                $entry_id_parts = explode('.', $eid);
                if (!empty($entry[$eid])) {
                    $entry_checkbox_selections[$entry_id_parts[0]][$entry_id_parts[1]] = $entry[$eid];
                }
            }
        }

//        echo "<pre>";
//        echo "ECS:\n";
//        print_r($entry_checkbox_selections);
//        echo "</pre>";

        $segment_id = $entry[1];
        if ( !empty($entry_checkbox_selections[2]) ) {
            $entry_checkbox_selections[2][1] = 1;   //add "all states" if it's not already included
            $geo_ids_domestic = array_values($entry_checkbox_selections[2]);
        } else {
            $geo_ids_domestic = array();
        }
        if ( !empty($entry_checkbox_selections[3]) ) {
            $entry_checkbox_selections[3][1] = 247;   //add "all countries" if it's not already included
            $geo_ids_foreign = array_values($entry_checkbox_selections[3]);
        } else {
            $geo_ids_foreign = array();
        }
        if ( !empty($entry_checkbox_selections[4]) ) {
            $subject_headings_ids = array_values($entry_checkbox_selections[4]);
        } else {
            $subject_headings_ids = array();
        }
        $range_direction = $entry[8];
        $range_month = $entry[9];
        $range_day = $entry[10];
        $range_year = $entry[11];
        $full_date = $range_year . '-' . str_pad($range_month,2,"0",STR_PAD_LEFT) . '-' . str_pad($range_day,2,"0",STR_PAD_LEFT) . ' ' . '00:00:00';

        $out_result = array();  //initialize output
        $sort = 'up';   //default sort order

//        echo "SID: $segment_id<br>";

        //get ids of all active grants that match segment
        $ids = self::get_grant_ids_by_segment( $segment_id );

//        echo "<pre>";
//        print_r($ids);
//        echo "</pre>";

        //remove ids that don't match geo_locations
        if ( !empty($geo_ids_domestic) || !empty($geo_ids_foreign) ) {
            $geo_ids_all = array_merge( $geo_ids_domestic, $geo_ids_foreign );
            $geo_ids = self::get_grant_by_geo( $geo_ids_all );
            $ids = array_intersect( $ids, $geo_ids );
        }

//        echo "<h2>ID</h2>";
//        echo "<pre>";
//        print_r($ids);
//        echo "</pre>";

        //remove ids that don't match subjects
        if ( !empty($subject_headings_ids) ) {
            $subject_ids = self::get_grant_by_subject( $subject_headings_ids );
            $ids = array_intersect( $ids, $subject_ids );
        }

//        echo "<h2>ID</h2>";
//        echo "<pre>";
//        print_r($ids);
//        echo "</pre>";

        //grab grant details for ids that fall in specified date range
        $data_dump = self::get_grants_by_id( $ids, $full_date, $range_direction );

//        echo "<pre>";
//        print_r($data_dump);
//        echo "</pre>";

        if ( empty($data_dump) ) {
            $data_dump = array('error'=>'Sorry, no book data matching your criteria has been found.');
        } else {
            //sort results alphabetically by title
            $data_dump = self::array_key_multi_sort( $data_dump, 'sort_title', 'up');

//        echo "<pre>";
//        print_r($data_dump);
//        echo "</pre>";

            //assign book indexes to results
            $id_to_index  = self::assign_indexes( $data_dump );

            //fill in contact info for grants
            $contact_info = self::fill_contact_info( $data_dump );

            //fill in sponsor info for grants
            $sponsor_info = self::fill_sponsor_info( $data_dump );
        }

        switch ($display_mode) {
            case 'preview':
                if ( !empty($data_dump) ) {
                    $size_of_result = sizeof($data_dump);
                    $show_from = $num_of_page * 20 - 20 + 1;
                    $show_to   = $num_of_page * 20;
                    if ($show_to > (int)$size_of_result) {
                        $show_to = $size_of_result;
                    }
                    $offset = ( $num_of_page - 1 ) * 20;
                    $data_dump = array_slice($data_dump, $offset, 20);

    //                echo "DATADUMP494:<br>";
    //                echo "<pre>";
    //                print_r($data_dump);
    //                echo "</pre>";

                    $page_menu = self::paginate_preview( $entry_id, 20, $num_of_page, (int)$size_of_result, '<span>&#171;</span>', '<span>&#187;</span>' );
                    echo self::generate_preview_table( $data_dump, $entry_id, $page_menu, $num_of_page, $size_of_result, $show_from, $show_to, $contact_info, $sponsor_info );
                }
                break;
            case 'book':
//                echo "<pre>";
//                print_r($data_dump);
//                echo "</pre>";
                if ( !empty($data_dump['error']) ) {
                    echo '<p>';
                    echo $data_dump['error'];
                    echo '</p>';
                } else {
                    foreach ($data_dump as $key => $value) {
                        echo '<p><br />';
                        if ($value['title'] != '') {
                            $index = $id_to_index[$value['id']];
                            echo '<b>' . $value['title'] . '<div align="right" width="100%">' . $index . '</div></b><br />';
                        }
                        if ($value['description'] != '') {
                            echo $value['description'] . '<br />';
                        }
                        if (trim($value['requirements']) != '') {
                            echo '<em>Requirements</em>: ' . $value['requirements'] . '<br />';
                        }
                        if (trim($value['restrictions']) != '') {
                            echo '<em>Restrictions</em>: ' . $value['restrictions'] . '<br />';
                        }
                        if (trim($value['geo_focus']) != '') {
                            echo '<em>Geographic Focus</em>: ' . $value['geo_focus'] . '<br />';
                        }
                        if (trim($value['dead_lines']) != '') {
                            echo '<em>Date(s) Application is Due</em>: ' . $value['dead_lines'] . '<br />';
                        }
                        if (trim($value['deadlines']) != '') {
                            echo '<em>Date(s) Application is Due</em>: ' . $value['deadlines'] . '<br />';
                        }
                        $amounts = '';
                        if (trim($value['amount_min']) != '' && $value['amount_min'] != 0.00) {
                            $amounts = number_format($value['amount_min']);
                        }
                        if (trim($value['amount_min']) != '' && $value['amount_min'] == 0.00) {
                            $amounts = 'Up to&nbsp;';
                        }
                        if (trim($value['amount_max']) != '' && $value['amount_max'] != 0.00) {
                            if ($amounts != '' && $amounts != 'Up to&nbsp;') {
                                $amounts .= ' - ' . number_format($value['amount_max']);
                            }
                            else if (trim($amounts) != '' && $amounts == 'Up to&nbsp;') {
                                $amounts .= number_format($value['amount_max']);
                            }
                            else {
                                $amounts = number_format($value['amount_max']);
                            }
                        }
                        if (trim($amounts) != '') {
                            echo '<em>Amount of Grant</em>: ' . $amounts . ' ' . $value['amount_currency'] . '<br />';
                        }
                        if (trim($value['samples']) != '') {
                            echo '<em>Samples</em>: ' . $value['samples'] . '<br />';
                        }
                        foreach ($contact_info as $c_key => $c_value) {
                            if ($c_key == $value['id']) {
                                echo '<em>Contact</em>: ' . $c_value . '<br />';
                            }
                        }
                        if (trim($value['grant_url_1']) != '') {
                            echo '<em>Internet</em>: ' . $value['grant_url_1'] . '<br />';
                        }
                        foreach ($sponsor_info as $s_key => $s_value) {
                            if ($s_key == $value['id']) {
                                echo '<em>Sponsor</em>: ' . $s_value . '<br />';
                            }
                        }
                        //$counter++;
                        echo '</p>';
                    }
                }

                break;
            case 'subject-titles':

                if ( !empty($data_dump['error']) ) {
                    echo '<p>';
                    echo $data_dump['error'];
                    echo '</p>';
                } else {
                    $titles = self::get_subject_title($data_dump);

                    $titles_list = array();
                    foreach ($titles as $key => $value) {
                        if (array_key_exists($value['subject_title'], $titles_list)) {
                            $titles_list[$value['subject_title']]++;
                        } else {
                            $titles_list[$value['subject_title']] = 1;
                        }
                    }
                    ksort($titles_list);

                    $cumulative_count = 0;
                    foreach ($titles_list as $key => $value) {
                        $cumulative_count += $value;
                        echo $key . " - " . $value . " (" . $cumulative_count . ")<br />\n";
                    }
                    //echo "<pre>"; //debug
                    //print_r($titles_list);  //debug
                    //echo "</pre>"; //debug
                }
                break;
            case 'subject-index':
//                echo "<pre>";
//                print_r($data_dump);
//                echo "</pre>";

                $limit_from = filter_var( $_POST['limit_from'], FILTER_SANITIZE_STRING );
                $limit_to   = filter_var( $_POST['limit_to'], FILTER_SANITIZE_STRING );

//                echo "LF: $limit_from<br>";
//                echo "LT: $limit_to<br>";

                $data_dump = self::get_full_subject_res($data_dump,$limit_from,$limit_to);
//                $data_dump = self::get_full_subject_res($data_dump);
                $data_dump = self::array_key_multi_sort($data_dump, 'sort_title', 'up');

                if ( !empty($data_dump['error']) ) {
                    echo '<p>';
                    echo $data_dump['error'];
                    echo '</p>';
                } else {
                    $subjects = array();
                    foreach ($data_dump as $key => $value) {
                        if (!in_array($value['subject_title'], $subjects)) {
                            array_push($subjects, $value['subject_title']);
                        }
                    }
                    sort($subjects);

//                    echo "<pre>";
//                    print_r($data_dump);
//                    echo "</pre>";

                    echo 'Index by subjects';
                    if ( !empty($limit_from) && !empty($limit_to) ) {
                        echo ' (' . $limit_from . '-' . $limit_to . ')';
                    }
                    echo '<br><br>';
                    foreach ($subjects as $subject) {
                        echo '<br><br><b>' . $subject . '</b><br />';
                        foreach ($data_dump as $key => $value) {
                            if ($value['subject_title'] == $subject) {
                                $index = $id_to_index[$value['id']];
                                if ($index == '') {
                                    $index = 'Not Assigned';
                                }
                                echo '&nbsp;&nbsp;&nbsp;' . $value['title'] . ', ' . $index . '<br />';
                            }
                        }
                    }
                    echo '<br /><br /><br />End Index by subjects';
                    if ( !empty($limit_from) && !empty($limit_to) ) {
                        echo ' (' . $limit_from . '-' . $limit_to . ')';
                    }
                    echo '<br />';

                }
                break;
            case 'program-titles':

                if ( !empty($data_dump['error']) ) {
                    echo '<p>';
                    echo $data_dump['error'];
                    echo '</p>';
                } else {
                    $titles = self::get_program_title($data_dump);

                    $titles_list = array();
                    foreach ($titles as $key=>$value) {
                        if (array_key_exists($value['program_title'],$titles_list)) {
                            $titles_list[$value['program_title']]++;
                        } else {
                            $titles_list[$value['program_title']]=1;
                        }
                    }
                    ksort($titles_list);

                    $cumulative_count = 0;
                    foreach($titles_list as $key=>$value) {
                        $cumulative_count += $value;
                        echo $key . " - " . $value . " (" . $cumulative_count . ")<br />\n";
                    }
                    //echo "<pre>"; //debug
                    //print_r($titles_list);  //debug
                    //echo "</pre>"; //debug
                }
                break;
            case 'program-index':
//                echo "<pre>";
//                print_r($data_dump);
//                echo "</pre>";

                $limit_from = filter_var( $_POST['limit_from'], FILTER_SANITIZE_STRING );
                $limit_to   = filter_var( $_POST['limit_to'], FILTER_SANITIZE_STRING );

//                echo "LF: $limit_from<br>";
//                echo "LT: $limit_to<br>";

                $data_dump = self::get_full_program_res($data_dump,$limit_from,$limit_to);
//                $data_dump = self::get_full_program_res($data_dump);
                $data_dump = self::array_key_multi_sort($data_dump, 'sort_title', 'up');

                if ( !empty($data_dump['error']) ) {
                    echo '<p>';
                    echo $data_dump['error'];
                    echo '</p>';
                } else {
                    $programs = array();
                    foreach ($data_dump as $key => $value) {
                        if (!in_array($value['program_title'], $programs)) {
                            array_push($programs, $value['program_title']);
                        }
                    }
                    sort($programs);

//                echo "<pre>";
//                print_r($programs);
//                echo "</pre>";

                    echo 'Index by program';
                    if ( !empty($limit_from) && !empty($limit_to) ) {
                        echo ' (' . $limit_from . '-' . $limit_to . ')';
                    }
                    echo '<br><br>';
                    foreach ($programs as $program) {
                        echo '<br><br><b>' . $program . '</b><br />';
                        foreach ($data_dump as $key => $value) {
                            if ($value['program_title'] == $program) {
                                $index = $id_to_index[$value['id']];
                                if ($index == '') {
                                    $index = 'Not Assigned';
                                }
                                echo '&nbsp;&nbsp;&nbsp;' . $value['title'] . ', ' . $index . '<br />';
                            }
                        }
                    }
                    echo '<br /><br /><br />End Index by program';
                    if ( !empty($limit_from) && !empty($limit_to) ) {
                        echo ' (' . $limit_from . '-' . $limit_to . ')';
                    }
                    echo '<br />';
                }
                break;
            case 'geo-us-index':
//                echo "<pre>";
//                print_r($data_dump);
//                echo "</pre>";

                $data_dump = self::get_full_geo_res_states($data_dump, 'Domestic');
                $data_dump = self::array_key_multi_sort($data_dump, 'sort_title', 'up');

                if ( !empty($data_dump['error']) ) {
                    echo '<p>';
                    echo $data_dump['error'];
                    echo '</p>';
                } else {
                    $states  = array();
                    foreach ($data_dump as $key => $value) {
                        if (!in_array($value['geo_location'], $states)) {
                            array_push($states, $value['geo_location']);
                        }
                    }
                    sort($states);
                    echo 'Index by state<br><br>';
                    foreach ($states as $state) {
                        echo '<br><br><b>' . $state . '</b><br />';
                        foreach ($data_dump as $key => $value) {
                            if ($value['geo_location'] == $state) {
                                $index = $id_to_index[$value['id']];
                                if ($index == '') {
                                    $index = 'Not Assigned';
                                }
                                echo '&nbsp;&nbsp;&nbsp;' . $value['title'] . ', ' . $index . '<br />';
                            }
                        }
                    }
                    echo '<br /><br /><br />End Index by state<br />';
                }
                break;
            case 'geo-foreign-index':
//                echo "<pre>";
//                print_r($data_dump);
//                echo "</pre>";

                $data_dump = self::get_full_geo_res_countries($data_dump, 'Foreign');
                $data_dump = self::array_key_multi_sort($data_dump, 'sort_title', 'up');

                if ( !empty($data_dump['error']) ) {
                    echo '<p>';
                    echo $data_dump['error'];
                    echo '</p>';
                } else {
                    $countries  = array();
                    foreach ($data_dump as $key => $value) {
                        if (!in_array($value['geo_location'], $countries)) {
                            array_push($countries, $value['geo_location']);
                        }
                    }
                    sort($countries);
                    echo 'Index by foreign country<br><br>';
                    foreach ($countries as $country) {
                        echo '<br><br><b>' . $country . '</b><br />';
                        foreach ($data_dump as $key => $value) {
                            if ($value['geo_location'] == $country) {
                                $index = $id_to_index[$value['id']];
                                if ($index == '') {
                                    $index = 'Not Assigned';
                                }
                                echo '&nbsp;&nbsp;&nbsp;' . $value['title'] . ', ' . $index . '<br />';
                            }
                        }
                    }
                    echo '<br /><br /><br />End Index by foreign country<br />';
                }
                break;
            case 'editors':
//                echo "<pre>";
//                print_r($data_dump);
//                echo "</pre>";

                if ( !empty($data_dump['error']) ) {
                    echo '<p>';
                    echo $data_dump['error'];
                    echo '</p>';
                } else {

                    $editors_list = self::get_contributing_editors($data_dump);

                    if ( empty($editors_list) ) {
                        echo 'No editors found.<br>';
                    } else {
                        echo 'Contributing editors<br><br><br>';
                        foreach ($editors_list as $editor) {
                            echo $editor['display_name'] . '<br />';
                        }
                        echo '<br /><br /><br />End Contributing editors<br />';
                    }
                }
                break;





            /////////////////////// LEFT OFF HERE //////////////////////////////




        }

    }

    /**
     * Function get_grant_ids_by_segment
     * @params $segment_id
     * @return $res
     */
    private function get_grant_ids_by_segment( $segment_id )
    {
        global $wpdb;

        $sql_query = "SELECT grant_id FROM " . $wpdb->prefix . "gs_grant_segment_mappings as gsm
                        JOIN " . $wpdb->prefix . "gs_grants as gg ON gsm.grant_id = gg.id
                        WHERE segment_id = %d AND gg.status = 'A'";
        $sql = $wpdb->prepare( $sql_query, $segment_id );
//        echo "SQL461: $sql<br>";
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }


    /**
     * Function get_grant_by_geo
     * @params $geo_location_ids
     * @return res
     */
    private function get_grant_by_geo( $geo_location_ids )
    {
        global $wpdb;

        $res = array();

        $geo_location_id_list = implode (',', $geo_location_ids);
        $sql_query = "SELECT grant_id FROM " . $wpdb->prefix . "gs_grant_geo_mappings WHERE geo_id IN( $geo_location_id_list )";
        $sql = $wpdb->prepare( $sql_query );
//        echo "SQL485: $sql<br>";
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }


    /*
    * Function get_grant_by_sub
    * @params $grant_subjects_ids
    * @return res
    */
    private function get_grant_by_subject( $grant_subjects_ids )
    {
        global $wpdb;

        $res = array();

        $grant_subjects_id_list = implode (',', $grant_subjects_ids);
        $sql_query = "SELECT grant_id FROM " . $wpdb->prefix . "gs_grant_subject_mappings WHERE subject_id IN( $grant_subjects_id_list )";
        $sql = $wpdb->prepare( $sql_query );
//        echo "SQL507: $sql<br>";
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        $res = array_keys( $data );

        return $res;
    }


    /**
     * Function get_grants_by_id
     * @params $grant_ids, $full_date, $date_direction
     * @return $res
     */
    private function get_grants_by_id( $grant_ids, $full_date, $range_direction )
    {
        global $wpdb;

        $res = array();

        foreach ( $grant_ids as $grant_id ) {
            $sql_query = "SELECT
                            id, grant_num, title, REPLACE ( REPLACE (title, ' ', '!'), '.', '' ) as sort_title, description, requirements,
                            restrictions, samples, amount_min, amount_max, amount_currency, grant_url_1
                          FROM
                            " . $wpdb->prefix . "gs_grants
                          WHERE
                            id=%d AND status='A'";  //sort_title column replaces certain characters in the title so records will sort better using strnatcasecmp() function
            if ($range_direction == 'newer') {
                $sql_query .= " AND updated_at >= '%s'";
            } else if ($range_direction == 'older') {
                $sql_query .= " AND updated_at < '%s'";
            }
            $sql = $wpdb->prepare($sql_query, $grant_id, $full_date);
//            echo "SQL550: $sql<br>";
            $data = $wpdb->get_results($sql, "OBJECT");

//            echo "DATA:<br>";
//            echo "<pre>";
//            print_r($data);
//            echo "</pre>";

            if ( !empty($data) ) {
                $dead_lines = self::get_dead_lines($data[0]->id);
//                echo "DL:<br>";
//                echo "<pre>";
//                print_r($dead_lines);
//                echo "</pre>";
                $geo_focus = self::get_sort_geo_locations($data[0]->id);
//                echo "GF:<br>";
//                echo "<pre>";
//                print_r($geo_focus);
//                echo "</pre>";

                $res[] = array(
                    'id'              => $data[0]->id,
                    'grant_num'       => $data[0]->grant_num,
                    'title'           => $data[0]->title,
                    'sort_title'      => $data[0]->sort_title,
                    'description'     => $data[0]->description,
                    'requirements'    => $data[0]->requirements,
                    'restrictions'    => $data[0]->restrictions,
                    'geo_focus'       => $geo_focus,
                    'dead_lines'      => $dead_lines,
                    'samples'         => $data[0]->samples,
                    'amount_min'      => $data[0]->amount_min,
                    'amount_max'      => $data[0]->amount_max,
                    'amount_currency' => $data[0]->amount_currency,
                    'grant_url_1'     => $data[0]->grant_url_1
                );
            }
        }
        return $res;
    }


    /**
     * Function get_geo_ids_single_grant
     * @params $grant_id
     * @return $location_ids
     */
    private function get_geo_ids_single_grant( $grant_id )
    {
        global $wpdb;

        $location_ids = array();

        $sql_query = "SELECT geo_id FROM " . $wpdb->prefix . "gs_grant_geo_mappings WHERE grant_id=%d";
        $sql = $wpdb->prepare( $sql_query, $grant_id );
//        echo "SQL602: $sql<br>";
        $data = $wpdb->get_results( $sql, "OBJECT_K" );
        $location_ids = array_keys( $data );

        return $location_ids;
    }


    /**
     * Function get_sort_geo_locations
     * @params $grant_id
     * @return $location_names
     */
    private function get_sort_geo_locations( $grant_id )
    {
        global $wpdb;

        $location_names = '';
        $domestic = array();
        $foreign  = array();

        $location_ids = self::get_geo_ids_single_grant( $grant_id );

        foreach ( $location_ids as $location_id ) {
            $sql_query = "SELECT geo_location, geo_locale FROM " . $wpdb->prefix . "gs_grant_geo_locations WHERE id=%d";
            $sql = $wpdb->prepare( $sql_query, $location_id );
//            echo "SQL633: $sql<br>";
            $data = $wpdb->get_results( $sql, "OBJECT" );

            if ($data[0]->geo_locale == 'Domestic') {
                $domestic[] = $data[0]->geo_location;
            }
            elseif ($data[0]->geo_locale == 'Foreign') {
                $foreign[] = $data[0]->geo_location;
            }
        }

        sort($domestic);
        sort($foreign);

        $d = implode(", ", $domestic);
        $f = implode(", ", $foreign);

        $d = preg_replace("/ALL STATES/", 'All States', $d);
        $f = preg_replace("/ALL COUNTRIES/", 'All Countries', $f);

        if ($d != '' and $f != '') {
            $location_names = $d . ', ' . $f;
        }
        elseif ($d != '' and $f  == '') {
            $location_names = $d;
        }
        elseif ($d == '' and $f != '') {
            $location_names = $f;
        }

        return $location_names;
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

        $sql_query = "SELECT month, date FROM " . $wpdb->prefix . "gs_grant_key_dates WHERE grant_id=%d AND date_title='deadline'";
        $sql = $wpdb->prepare( $sql_query, $grant_id );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        foreach ( $data as $deadline ) {
            $temp_month = $months[$deadline->month];
            if ($res != '') {
                $res .= '; ' . $temp_month . ' ' . $deadline->date;
            }
            else {
                $res = $temp_month . ' ' . $deadline->date;
            }
            $temp_month = '';
        }

        return $res;
    }


    /**
     * Function fill_contact_info
     * @params $grant_info
     * @return $res
     */
    private function fill_contact_info( $grant_info )
    {
        $res = array();
        foreach ($grant_info as $key => $value) {
            $id = $value['id'];
            $res[$id] = self::get_contact_info($id);
        }
        return $res;
    }


    /**
     * Function fill_sponsor_info
     * @params $grant_info
     * @return $res
     */
    private function fill_sponsor_info( $grant_info )
    {
        $res = array();
        foreach ($grant_info as $key => $value) {
            $id = $value['id'];
            $res[$id] = self::get_sponsor_info($id);
        }
        return $res;
    }


    /**
     * Function get_sponsor_contact_ids
     * @params $grant_id
     * @return $res(array with sponsor_id and contact_id for this grant)
     */
    private function get_sponsor_contact_ids( $grant_id )
    {
        global $wpdb;

        $res = array();

        $sql_query = "SELECT sponsor_id, contact_id FROM " . $wpdb->prefix . "gs_grant_sponsor_contact_mappings WHERE grant_id=%d";
        $sql = $wpdb->prepare( $sql_query, $grant_id );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        foreach( $data as $key=>$value ) {
            $res[] = array(
                'sponsor_id' => $value->sponsor_id,
                'contact_id' => $value->contact_id
            );
        }

        return $res;
    }


    /**
     * Function get_contact_info.
     * @params $grant_id
     * @return $out_info
     */
    private function get_contact_info( $grant_id )
    {
        global $wpdb;

        $ids        = self::get_sponsor_contact_ids( $grant_id );
        $res        = array();
        $out_info   = '';

        foreach ($ids as $key => $value) {
            $contact_id = $value['contact_id'];
            $sql_query = "SELECT
                              contact_name, contact_title, contact_phone_1, contact_phone_2, contact_fax, contact_email_1, contact_email_2
                          FROM
                              " . $wpdb->prefix . "gs_grant_contacts
                          WHERE
                              id=%d";
            $sql = $wpdb->prepare( $sql_query, $contact_id );
            $data = $wpdb->get_results( $sql, "OBJECT" );

//            echo "SQL887:$sql<br>";

            foreach ($data as $contact)
            $res[] = array(
                'contact_name'    => $contact->contact_name,
                'contact_title'   => $contact->contact_title,
                'contact_phone_1' => $contact->contact_phone_1,
                'contact_phone_2' => $contact->contact_phone_2,
                'contact_fax'     => $contact->contact_fax,
                'contact_email_1' => $contact->contact_email_1,
                'contact_email_2' => $contact->contact_email_2
            );
        }

        $counter = 1;
        foreach ($res as $key => $value) {
            if (trim($value['contact_name']) != '') {
                $out_info .= $value['contact_name'] . ', ';
            }
            if (trim($value['contact_title']) != '') {
                $out_info .= $value['contact_title'] . '; ';
            }
            if (trim($value['contact_phone_1']) != '' and trim($value['contact_phone_2']) != '') {
                $out_info .= $value['contact_phone_1'] . ' or ' . $value['contact_phone_2'] . '; ';
            }
            else if (trim($value['contact_phone_1']) != '' and trim($value['contact_phone_2']) == '') {
                $out_info .= $value['contact_phone_1'] . '; ';
            }
            else if (trim($value['contact_phone_1']) == '' and trim($value['contact_phone_2']) != '') {
                $out_info .= $value['contact_phone_2'] . '; ';
            }
            if (trim($value['contact_fax']) != '') {
                $out_info .= 'fax ' . $value['contact_fax'] . '; ';
            }
            if (trim($value['contact_email_1']) != '' and trim($value['contact_email_2']) != '') {
                $out_info .= $value['contact_email_1'] . ' or ' . $value['contact_email_2'] . '; ';
            }
            else if (trim($value['contact_email_1']) != '' and trim($value['contact_email_2']) == '') {
                $out_info .= $value['contact_email_1'] . '; ';
            }
            else if (trim($value['contact_email_1']) == '' and trim($value['contact_email_2']) != '') {
                $out_info .= $value['contact_email_2'] . '; ';
            }
            $out_info = substr($out_info,0,-2);
            if (count($res) > $counter) {
                $out_info .= '<br />';
            }
            $counter ++;
        }
        return $out_info;
    }


    /**
     * Function get_sponsor_info.
     * @params $grant_id
     * @return $out_info
     */
    private function get_sponsor_info($grant_id)
    {
        global $wpdb;

        $ids        = self::get_sponsor_contact_ids( $grant_id );
        $res        = array();
        $out_info   = '';
        $sponsor_id = $ids[0]['sponsor_id'];

        $sql_query = "SELECT
                          sponsor_name, sponsor_address, sponsor_city, sponsor_state, sponsor_zip, sponsor_country
                      FROM
                          " . $wpdb->prefix . "gs_grant_sponsors
                      WHERE
                        id=%d";
        $sql = $wpdb->prepare( $sql_query, $sponsor_id );
        $data = $wpdb->get_results( $sql, "OBJECT" );

        $res[] = array(
            'sponsor_name'    => $data[0]->sponsor_name,
            'sponsor_address' => $data[0]->sponsor_address,
            'sponsor_city'    => $data[0]->sponsor_city,
            'sponsor_state'   => $data[0]->sponsor_state,
            'sponsor_zip'     => $data[0]->sponsor_zip,
            'sponsor_country' => $data[0]->sponsor_country
        );

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
     * Function assign_indexes
     * @params $data_dump
     * @return $res
     */
    private function assign_indexes( $data_dump )
    {
        $res = array();
        $counter = 1;
        foreach ($data_dump  as $key => $value) {
            $id = $value['id'];
            $res[$id] = $counter;
            $counter++;
        }
        return $res;
    }


    /**
     * Function get_full_subject_res
     * @params $grant_info
     * @return $res
     */
    function get_full_subject_res(&$grant_info,$limit_from='',$limit_to='')
    {
        //$limit_from = "AIDS";  //debug
        //$limit_to = "Africa, Northern";  //debug

        $limit_from = html_entity_decode($limit_from);
        $limit_to = html_entity_decode($limit_to);

        //echo "LF: $limit_from<br />"; //debug
        //echo "LT: $limit_to<br />"; //debug

        $limit_from_count = strlen($limit_from);
        $limit_to_count = strlen($limit_to);

        $res = array();
        $titles = self::get_subject_title($grant_info);

//        echo "<pre>";
//        print_r($titles);
//        echo "</pre>";

        if ($limit_from != '' && $limit_to != '') {
            foreach ($titles as $t_key => $t_value) {
                if (substr($t_value['subject_title'],0,$limit_from_count) >= $limit_from AND
                    substr($t_value['subject_title'],0,$limit_to_count) <= $limit_to) {
                    $titles_limited[$t_key] = $t_value;
                }
            }
        } else {
            $titles_limited = $titles;
        }

        //$title_count = count($titles_limited);  //debug
        //echo "TC: $title_count<br />";  //debug
        //echo "<pre>"; //debug
        //print_r($titles_limited); //debug
        //echo "</pre>"; //debug
        //exit;

        foreach ($grant_info as $key => $value) {
            foreach ($titles_limited as $t_key => $t_value) {
                if ($value['id'] == $t_value['grant_id']) {
                    $res[] = array(
                        'id'            => $value['id'],
                        'grant_num'     => $value['grant_num'],
                        'title'         => $value['title'],
                        'sort_title'    => $value['sort_title'],
                        'description'   => $value['description'],
                        'requirements'  => $value['requirements'],
                        'restrictions'  => $value['restrictions'],
                        'samples'       => $value['samples'],
                        'grant_url_1'   => $value['grant_url_1'],
                        'subject_title' => $t_value['subject_title']
                    );
                }
            }
        }
        //echo "<pre>"; //debug
        //print_r($res);  //debug
        //echo "</pre>"; //debug
        return $res;
    }


    /**
     * Function get_subject_title
     * @params $grant_info
     * @return $res
     */
    function get_subject_title(&$grant_info)
    {
        global $wpdb;

        $title_ids = self::get_title_ids($grant_info);

        $res = array();
        foreach ($title_ids as $key => $value) {
            $id = $value['subject_id'];
            $sql_query = "SELECT subject_title FROM " . $wpdb->prefix . "gs_grant_subjects WHERE id=%d";
            $sql = $wpdb->prepare( $sql_query, $id );
//            echo "SQL1142:$sql<br>";
            $data = $wpdb->get_results( $sql, "OBJECT" );
            foreach ($data as $data_val) {
                $res[] = array(
                    'grant_id'      => $value['grant_id'],
                    'subject_title' => $data_val->subject_title
                );
            }
        }
//        echo "GST:<br>";
//        echo "<pre>";
//        print_r($res);
//        echo "</pre>";
        return $res;
    }


    /**
     * Function get_title_ids
     * @params $grant_info
     * @return $res
     */
    private function get_title_ids(&$grant_info)
    {
        global $wpdb;

        $res = array();

        foreach ($grant_info as $key => $value) {
            $id = $value['id'];
            $sql_query = "SELECT grant_id, subject_id FROM " . $wpdb->prefix . "gs_grant_subject_mappings WHERE grant_id=%d";
            $sql = $wpdb->prepare( $sql_query, $id );
            $data = $wpdb->get_results( $sql, "OBJECT" );
            foreach ($data as $data_val) {
                $res[] = array(
                    'grant_id' => $data_val->grant_id,
                    'subject_id' => $data_val->subject_id
                );
            }
        }
//        echo "GTID<br>";
//        echo "<pre>";
//        print_r($res);
//        echo "</pre>";
        return $res;
    }


    /**
     * Function get_full_program_res
     * @params $grant_info
     * @return $res
     */
    function get_full_program_res(&$grant_info,$limit_from='',$limit_to='')
    {
        $limit_from = html_entity_decode($limit_from);
        $limit_to = html_entity_decode($limit_to);

        $limit_from_count = strlen($limit_from);
        $limit_to_count = strlen($limit_to);

//echo "LF: $limit_from - $limit_from_count<br />";
//echo "LT: $limit_to - $limit_to_count<br />";
//exit;

        $res = array();
        $titles = self::get_program_title($grant_info);

//echo "<pre>"; //debug
//print_r($titles); //debug
//echo "</pre>"; //debug
//exit;

        if ($limit_from != '' && $limit_to != '') {
            foreach ($titles as $t_key => $t_value) {
                //echo "S: " . substr($t_value['program_title'],0,$limit_from_count) . "<br />";  //debug
                if (substr($t_value['program_title'],0,$limit_from_count) >= $limit_from AND
                    substr($t_value['program_title'],0,$limit_to_count) <= $limit_to) {
                    //echo "Here<br />";  //debug
                    $titles_limited[$t_key] = $t_value;
                }
            }
        } else {
            $titles_limited = $titles;
        }

//echo "<pre>"; //debug
//print_r($titles_limited); //debug
//echo "</pre>"; //debug
//exit;

        foreach ($grant_info as $key => $value) {
            foreach ($titles_limited as $p_key => $p_value) {
                if ($value['id'] == $p_value['grant_id']) {
                    $res[] = array(
                        'id'            => $value['id'],
                        'grant_num'     => $value['grant_num'],
                        'title'         => $value['title'],
                        'sort_title'    => $value['sort_title'],
                        'description'   => $value['description'],
                        'requirements'  => $value['requirements'],
                        'restrictions'  => $value['restrictions'],
                        'samples'       => $value['samples'],
                        'grant_url_1'   => $value['grant_url_1'],
                        'program_title' => $p_value['program_title']
                    );
                }
            }
        }
//echo "<pre>"; //debug
//print_r($res); //debug
//echo "</pre>"; //debug
        return $res;
    }


    /**
     * Function get_program_title
     * @params $grant_info
     * @return $res
     */
    function get_program_title(&$grant_info)
    {
        global $wpdb;

        $program_ids = self::get_program_ids($grant_info);

        $res = array();
        foreach ($program_ids as $key => $value) {
            $id = $value['program_id'];
            $sql_query = "SELECT program_title FROM " . $wpdb->prefix . "gs_grant_programs WHERE id=%d";
            $sql = $wpdb->prepare( $sql_query, $id );
//            echo "SQL1142:$sql<br>";
            $data = $wpdb->get_results( $sql, "OBJECT" );
            foreach ($data as $data_val) {
                $res[] = array(
                    'grant_id'      => $value['grant_id'],
                    'program_title' => $data_val->program_title
                );
            }
        }
//        echo "GPT:<br>";
//        echo "<pre>";
//        print_r($res);
//        echo "</pre>";
        return $res;
    }


    /**
     * Function get_program_ids
     * @params $grant_info
     * @return $res
     */
    function get_program_ids(&$grant_info)
    {
        global $wpdb;

        $res = array();

        foreach ($grant_info as $key => $value) {
            $id = $value['id'];
            $sql_query = "SELECT grant_id, program_id FROM " . $wpdb->prefix . "gs_grant_program_mappings WHERE grant_id=%d";
            $sql = $wpdb->prepare( $sql_query, $id );
            $data = $wpdb->get_results( $sql, "OBJECT" );
            foreach ($data as $data_val) {
                $res[] = array(
                    'grant_id' => $data_val->grant_id,
                    'program_id' => $data_val->program_id
                );
            }
        }
//        echo "GPID<br>";
//        echo "<pre>";
//        print_r($res);
//        echo "</pre>";
        return $res;
    }


    /**
     * Function get_full_geo_res
     * @params $grant_info, $location
     * @return $res
     */
    private function get_full_geo_res_states(&$grant_info, $location)
    {
        $res = array();
        $states = self::get_all_states();
//        echo "S:<br>";
//        echo "<pre>";
//        print_r($states);
//        echo "</pre>";
        $titles = self::get_geo_location($grant_info, $location);
//        echo "T:<br>";
//        echo "<pre>";
//        print_r($titles);
//        echo "</pre>";
        foreach ($grant_info as $key => $value) {
            foreach ($titles as $g_key => $g_value) {
                if ($value['id'] == $g_value['grant_id'] and $g_value['geo_location'] != 'All States') {
                    $res[] = array(
                        'id'            => $value['id'],
                        'grant_num'     => $value['grant_num'],
                        'title'         => $value['title'],
                        'sort_title'    => $value['sort_title'],
                        'description'   => $value['description'],
                        'requirements'  => $value['requirements'],
                        'restrictions'  => $value['restrictions'],
                        'samples'       => $value['samples'],
                        'grant_url_1'   => $value['grant_url_1'],
                        'geo_location'  => $g_value['geo_location']
                    );
                }
                else if ($value['id'] == $g_value['grant_id'] and $g_value['geo_location'] == 'All States') {
                    //foreach ($states as $state) {
                    $res[] =  array(
                        'id'            => $value['id'],
                        'grant_num'     => $value['grant_num'],
                        'title'         => $value['title'],
                        'sort_title'    => $value['sort_title'],
                        'description'   => $value['description'],
                        'requirements'  => $value['requirements'],
                        'restrictions'  => $value['restrictions'],
                        'samples'       => $value['samples'],
                        'grant_url_1'   => $value['grant_url_1'],
                        //'geo_location'  => $state
                        'geo_location'  => 'All States'
                    );
                    //}
                }
            }
        }
        return $res;
    }

    /**
     * Function get_full_geo_res_countries
     * @params $grant_info, $location
     * @return $res
     */
    private function get_full_geo_res_countries(&$grant_info, $location)
    {
        $res = array();
        $countries = self::get_all_countries();
        $titles    = self::get_geo_location($grant_info, $location);
        foreach ($grant_info as $key => $value) {
            foreach ($titles as $g_key => $g_value) {
                if ($value['id'] == $g_value['grant_id'] and $g_value['geo_location'] != 'All Countries') {
                    $res[] = array(
                        'id'            => $value['id'],
                        'grant_num'     => $value['grant_num'],
                        'title'         => $value['title'],
                        'sort_title'    => $value['sort_title'],
                        'description'   => $value['description'],
                        'requirements'  => $value['requirements'],
                        'restrictions'  => $value['restrictions'],
                        'samples'       => $value['samples'],
                        'grant_url_1'   => $value['grant_url_1'],
                        'geo_location'  => $g_value['geo_location']
                    );
                }
                else if ($value['id'] == $g_value['grant_id'] and $g_value['geo_location'] == 'All Countries') {
                    //foreach ($countries as $country) {
                    $res[] =  array(
                        'id'            => $value['id'],
                        'grant_num'     => $value['grant_num'],
                        'title'         => $value['title'],
                        'sort_title'    => $value['sort_title'],
                        'description'   => $value['description'],
                        'requirements'  => $value['requirements'],
                        'restrictions'  => $value['restrictions'],
                        'samples'       => $value['samples'],
                        'grant_url_1'   => $value['grant_url_1'],
                        //'geo_location'  => $country
                        'geo_location'  => 'All Countries'
                    );
                    //}
                }
            }
        }
        return $res;
    }


    /**
     * Function get_all_states
     * @return $states
     */
    private function get_all_states()
    {
        global $wpdb;

        $states = array();
        $sql_query = "SELECT geo_location FROM " . $wpdb->prefix . "gs_grant_geo_locations WHERE geo_locale='Domestic' AND geo_location !='All States'";
        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        foreach ($data as $index=>$value) {
            $states[] = $value->geo_location;
        }
//        echo "S:<br>";
//        echo "<pre>";
//        print_r($states);
//        echo "</pre>";
        return $states;
    }


    /**
     * Function get_all_countries
     * @return $countries
     */
    private function get_all_countries()
    {
        global $wpdb;

        $countries = array();
        $sql_query = "SELECT geo_location FROM " . $wpdb->prefix . "gs_grant_geo_locations WHERE geo_locale='Foreign' AND geo_location !='All Countries'";
        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql, "OBJECT_K" );

        foreach ($data as $index=>$value) {
            $countries[] = $value->geo_location;
        }
//        echo "C:<br>";
//        echo "<pre>";
//        print_r($countries);
//        echo "</pre>";
        return $countries;
    }


    /**
     * Function get_geo_location
     * @params $grant_info, $location
     * @return $res
     */
    function get_geo_location(&$grant_info, $location)
    {
        global $wpdb;

        $geo_ids = self::get_geo_ids($grant_info);

        $res = array();
        foreach ($geo_ids as $key => $value) {
            $id = $value['geo_id'];

            $sql_query = "SELECT geo_location FROM " . $wpdb->prefix . "gs_grant_geo_locations WHERE id=%d AND geo_locale=%s";
            $sql = $wpdb->prepare( $sql_query, $id, $location );
            $data = $wpdb->get_results( $sql, "OBJECT" );

            foreach ($data as $index=>$data_val) {
                $res[] = array(
                    'grant_id'     => $value['grant_id'],
                    'geo_location' => $data_val->geo_location
                );
            }
        }
        return $res;
    }


    /**
     * Function get_geo_ids
     * @params $grant_info
     * @return $res
     */
    function get_geo_ids(&$grant_info)
    {
        global $wpdb;

        $res = array();
        foreach ($grant_info as $key => $value) {
            $id = $value['id'];
            $sql_query = "SELECT grant_id, geo_id FROM " . $wpdb->prefix . "gs_grant_geo_mappings WHERE grant_id=%d";
            $sql = $wpdb->prepare( $sql_query, $id );
//            echo "SQL1670:$sql<br>";
            $data = $wpdb->get_results( $sql, "OBJECT" );

            foreach ($data as $index => $data_val) {
                $res[] = array(
                    'grant_id' => $data_val->grant_id,
                    'geo_id'   => $data_val->geo_id
                );
            }
        }
//        echo "GID:<br>";
//        echo "<pre>";
//        print_r($res);
//        echo "</pre>";
        return $res;
    }


    /**
     * Function get_contributing_editors
     * @params $grant_info
     * @return $res
     */
    function get_contributing_editors(&$grant_info) {

        global $wpdb;

        //batch grant IDs into groups of 100
        $record_counter = 0;
        $batch_counter = 0;
        $grant_id_lists = array();
        foreach ($grant_info as $key => $value) {
            $grant_id_lists[$batch_counter] = $grant_id_lists[$batch_counter] . $value['id'];
            if ($record_counter >= 99) {
                $record_counter = 0;
                $batch_counter++;
            } else {
                $grant_id_lists[$batch_counter] .= ',';
                $record_counter++;
            }
        }

//        echo "E:<br>";
//        echo "<pre>";
//        print_r($grant_id_lists);
//        echo "</pre>";

        $editors = array();
        foreach ($grant_id_lists as $grant_id_list) {
            $sql_query = "SELECT DISTINCT
                              et.editor_id, u.display_name
                          FROM
                              " . $wpdb->prefix . "gs_editor_transactions as et
                          LEFT JOIN
                              " . $wpdb->prefix . "users as u
                          ON
                              et.editor_id = u.ID
                          WHERE
                              et.grant_id IN ( " . $grant_id_list . " )";
            $sql = $wpdb->prepare( $sql_query );
//            echo "SQL1726:$sql<br>";
            $data = $wpdb->get_results( $sql, "OBJECT" );

            foreach ($data as $index => $data_val) {
                if (!empty($data_val->display_name)) {
                    $name_parts = explode(" ", $data_val->display_name);
                    $first_name = get_user_meta( $data_val->editor_id, 'first_name', true );
                    if ( empty($first_name) ) {
                        $first_name = reset($name_parts);
                    }
                    $last_name = get_user_meta( $data_val->editor_id, 'last_name', true );
                    if ( empty($last_name) ) {
                        $last_name = end($name_parts);
                    }
                    $editors[$data_val->editor_id] = array(
                        'display_name'  => $data_val->display_name,
                        'first_name'    => $first_name,
                        'last_name'     => $last_name
                    );
                }
            }
        }

        //sort by last name, first name
        $first_name = array_column($editors, 'first_name');
        $last_name  = array_column($editors, 'last_name');
            array_multisort($last_name, SORT_ASC, $first_name, SORT_ASC, $editors);

//        echo "E:<br>";
//        echo "<pre>";
//        print_r($editors);
//        echo "</pre>";
        return $editors;
    }





























    /**
     * Function array_key_multi_sort
     * @params $arr, $l, $f
     * @return $arr
     */
    function array_key_multi_sort($arr, $l, $c, $f='strnatcasecmp')
    {
        if ($c == 'up') {
            usort($arr, create_function('$a, $b', "return $f(\$a['$l'], \$b['$l']);"));
        }
        if ($c == 'down') {
            usort($arr, create_function('$a, $b', "return $f(\$b['$l'], \$a['$l']);"));
        }
        return $arr;
    }


    /**
     * Function paginate_preview().
     * Paginate book dump preview
     * @params $book_prod_id, $items_per_page, $page, $total_items, $prev_page_pin, $next_page_pin
     */
    function paginate_preview ( $book_prod_id, $items_per_page, $page, $total_items, $prev_page_pin, $next_page_pin )
    {
        $normal_page_style = 'page_link_btn';
        $next_page_style   = 'page_link_btn';
        $prev_page_style   = 'page_link_btn';
        $curr_page_style   = 'page_link_btn selected';

        $total = intval(($total_items - 1) / $items_per_page) + 1;
        if ($total > 5) {
            $last_page = '...' . '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . $total . '" class="' . $normal_page_style . '">' . $total . '</a>';
        }
        else {
            $last_page = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . $total . '" class="' . $normal_page_style . '">' . $total . '</a>';
        }
        if ($page + 2 >= $total) {
            $last_page = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . $total . '" class="' . $normal_page_style . '">' . $total . '</a>';
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
            $first_page = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . 1 . '" class="' . $normal_page_style . '">' . 1 . '</a>' . '...';
        }
        if ($page - 3 == 0 or $page - 3 == 1) {
            $first_page = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . 1 . '" class="' . $normal_page_style . '">' . 1 . '</a>';
        }
        if ($page != 1) {
            $prev_page = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . ($page - 1) . '" class="' . $prev_page_style . '">' . $prev_page_pin . '</a> ';
        }
        if ($page != $total) {
            $next_page = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . ($page + 1) .  '" class="' . $next_page_style . '">' . $next_page_pin . '</a> ';
        }
        if ($page - 3 == 1) {
            $page2left = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . ($page - 2) . '" class="' . $normal_page_style . '">' . ($page - 2) . '</a> ';
        }
        if ($page - 3 > 1) {
            $page2left = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . ($page - 3) . '" class="' . $normal_page_style . '">' . ($page - 3) . '</a> ';
        }
        if ($page - 2 > 2) {
            $page2left = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . ($page - 2) . '" class="' . $normal_page_style . '">' . ($page - 2) . '</a> ';
        }
        if ($page - 1 > 0) {
            $page1left = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . ($page - 1) . '" class="' . $normal_page_style . '">' . ($page - 1) . '</a>';
        }
        if ($page + 1 < $total) {
            $page1right = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . ($page + 1) . '" class="' . $normal_page_style . '">' . ($page + 1) .'</a>';
        }
        if ($page + 2 < $total) {
            $page2right = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . ($page + 2) . '" class="' . $normal_page_style . '">' . ($page + 2) .'</a>';
        }
        if ($page + 3 < $total and $page < 3) {
            $page3right = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . ($page + 3) . '" class="' . $normal_page_style . '">' . ($page + 3) .'</a>';
        }
        if ($page + 4 < $total and $page < 2) {
            $page4right = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . ($page + 4) . '" class="' . $normal_page_style . '">' . ($page + 4) .'</a>';
        }
        if ($page + 1 == $total) {
            $page1right = '';
        }
        $page = '<a href="/editor/book-production/content/preview/?bpid=' . $book_prod_id . '&pn=' . $page . '" class="' . $curr_page_style . '">' . $page . '</a>';
        $paginate_menu = $prev_page . $first_page . $page2left . $page1left . $page . $page1right . $page2right . $page3right. $page4right . $last_page . $next_page;

        return $paginate_menu;
    }


    /**
     * Function generate_preview_table
     * @params  $info,
     *          $entry_id,
     *          $page_menu,
     *          $page_num,
     *          $size,
     *          $show_from,
     *          $show_to
     * @return $content
     */
    function generate_preview_table ($info, $entry_id, $page_menu, $page_num, $size, $show_from, $show_to, $contact_info=array(), $sponsor_info=array() )
    {
//        echo "<pre>";
//        print_r($info);
//        echo "</pre>";

        $content = '';

        if ( empty($info) ) {
            $content .= '<div class="search-error">';
            $content .= 'Sorry, no information matching your criteria have been found.<br>';
            $content .= '<a href="/editor/book-production/content/?bpid=' . $entry_id . '"><b>Back to Book Production Content</b></a>';
            $content .= '</div>';
        }
        else {

            //TODO: Add "Results per page" selector so user can select more than 10 records displayed per page

            $entry_id = intval($entry_id);
            $content  .= '<div class="paginate_menu"><b>Displaying: </b>' . $show_from . ' - ' . $show_to. '  of ' . $size;
            $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $page_menu;
            $content .= '<div class="search-options">';
            $content .= '<a href="/editor/book-production/content/?bpid=' . $entry_id . '"><b>Back to Book Production Content</b></a>';
            $content .= '</div></div>';

            $content .= '<div class="mass-edit-options" style="display:none">';
            $content .= '<input type="submit" name="mass-process" value="Bulk Edit Checked Items" id="mass-process">';
            $content .= '</div>';

            $content .= '<table class="book_production_preview mass-editable" summary="Book Production Results">';
            $content .= '<thead>';
            $content .= '<tr>';
            $content .= '<th><input type="checkbox" name="mass_action" class="mass-action-all" value="all"></th>';
            $content .= '<th>ID</th>';
            $content .= '<th></th>';
            $content .= '<th></th>';
            $content .= '<th>Title</th>';
            $content .= '</tr>';
            $content .= '</thead>';
            $content .= '<tbody>';
            foreach ($info as $key => $value) {
                $content .= '<td><input type="checkbox" name="mass_action" class="mass-action-select" value="' . $value['id'] . '"></td>';
                $content .= '<td>' . $value['id'] . '</td>';
                $content .= '<td><a href="/editor/records/view/?gid=' . $value['id'] . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">View</a></td>';
                $content .= '<td><a href="/editor/records/edit/?gid=' . $value['id'] . '&uri=' . urlencode($_SERVER['REQUEST_URI']) . '">Edit</a></td>';
                $content .= '<td><b>' . $value['title'] . "</b><br />";

                $content .= '<p><br />';
                if ($value['description'] != '') {
                    $content .= $value['description'] . '<br />';
                }
                if (trim($value['requirements']) != '') {
                    $content .= '<em>Requirements</em>: ' . $value['requirements'] . '<br />';
                }
                if (trim($value['restrictions']) != '') {
                    $content .= '<em>Restrictions</em>: ' . $value['restrictions'] . '<br />';
                }
                if (trim($value['geo_focus']) != '') {
                    $content .= '<em>Geographic Focus</em>: ' . $value['geo_focus'] . '<br />';
                }
                if (trim($value['dead_lines']) != '') {
                    $content .= '<em>Date(s) Application is Due</em>: ' . $value['dead_lines'] . '<br />';
                }
                if (trim($value['deadlines']) != '') {
                    $content .= '<em>Date(s) Application is Due</em>: ' . $value['deadlines'] . '<br />';
                }
                $amounts = '';
                if (trim($value['amount_min']) != '' && $value['amount_min'] != 0.00) {
                    $amounts = number_format($value['amount_min']);
                }
                if (trim($value['amount_min']) != '' && $value['amount_min'] == 0.00) {
                    $amounts = 'Up to&nbsp;';
                }
                if (trim($value['amount_max']) != '' && $value['amount_max'] != 0.00) {
                    if ($amounts != '' && $amounts != 'Up to&nbsp;') {
                        $amounts .= ' - ' . number_format($value['amount_max']);
                    }
                    else if (trim($amounts) != '' && $amounts == 'Up to&nbsp;') {
                        $amounts .= number_format($value['amount_max']);
                    }
                    else {
                        $amounts = number_format($value['amount_max']);
                    }
                }
                if (trim($amounts) != '') {
                    $content .= '<em>Amount of Grant</em>: ' . $amounts . ' ' . $value['amount_currency'] . '<br />';
                }
                if (trim($value['samples']) != '') {
                    $content .= '<em>Samples</em>: ' . $value['samples'] . '<br />';
                }
                foreach ($contact_info as $c_key => $c_value) {
                    if ($c_key == $value['id']) {
                        $content .= '<em>Contact</em>: ' . $c_value . '<br />';
                    }
                }
                if (trim($value['grant_url_1']) != '') {
                    $content .= '<em>Internet</em>: ' . $value['grant_url_1'] . '<br />';
                }
                foreach ($sponsor_info as $s_key => $s_value) {
                    if ($s_key == $value['id']) {
                        $content .= '<em>Sponsor</em>: ' . $s_value . '<br />';
                    }
                }
                //$counter++;
                $content .= '</p>';


                $content .= '</td>';
                $content .= '</tr>';
            }
            $content .= '</tbody>';
            $content .= '</table>';

            $content  .= '<div class="paginate_menu"><b>Displaying: </b>' . $show_from . ' - ' . $show_to. '  of ' . $size;
            $content .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $page_menu;

//            echo "<pre>";
//            print_r( $GLOBALS['wp_scripts']->registered );
//            echo "</pre>";

            $content .= GrantSelectRecordsAddOn::mass_edits_modal();
        }

        return $content;
    }


    /**
     * Function populate_form_fields
     * populates certain fields with data from database
     * @params $form
     * @return $form
     */
    static public function populate_form_fields ( $form ) {

        foreach ( $form['fields'] as $field ) {

            //identify if field is to be populated, otherwise skip
            if ( strpos( $field->cssClass, 'book-segments-dropdown' ) !== false ) {
                $field_instance = 'book-segments-dropdown';
            } elseif ( strpos( $field->cssClass, 'year-list-dropdown' ) !== false ) {
                $field_instance = 'year-list-dropdown';
            } else {
                continue;
            }

            switch ($field_instance) {
                case 'book-segments-dropdown':

                    //items for book title pull-down
                    $pull_down_segments = array();
                    $segment_list = GrantSelectRecordsAddOn::get_segment_list();

//                    echo "<pre>";
//                    print_r($segment_list);
//                    echo "</pre>";

                    foreach ($segment_list as $key => $value) {
                        if (array_key_exists($value->segment_title, self::BOOK_TITLES)) {
                            $pull_down_segments[] = array( 'value' => $value->id, 'text' => self::BOOK_TITLES[$value->segment_title] );
                        }
                    }
                    $field->choices = $pull_down_segments;

                    break;
                case 'year-list-dropdown':
                    $current_year = intval(date("Y"));
                    $pull_down_years = array();
                    for ( $i=20; $i>=0; $i-- ) {
                        $pull_down_years[] = array( 'value' => ($current_year - $i), 'text' => ($current_year - $i) );
                    }
                    $field->choices = $pull_down_years;

                    break;
            }

        }

        return $form;
    }


    /**
     * Function grantselect_prepopulate_bp_form
     * Pre-populates Book Production form fields with options selected in previous search
     * @params $form
     * @return $form
     */
    function grantselect_prepopulate_bp_form ( $form ) {

        //if bpid is unspecified or invalid, skip this and just return the default form values
        $previous_entry_id = absint($_GET['bpid']);
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
        $current_user_id = get_current_user_id();
        if ($current_user_id != $created_by) {
            return $form;
        }

        $forms_to_prepopulate = array(10);
        if( in_array( $form["id"], $forms_to_prepopulate ) ) {

            switch ( $form['id'] ) {
                case 10: //book production
                    $text_fields        = array(1,8,9,10,11);
                    $multiselect_fields = array(2,3,4);
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