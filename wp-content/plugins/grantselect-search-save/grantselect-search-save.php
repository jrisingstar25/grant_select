<?php
defined( 'ABSPATH' ) or die();
/*
Plugin Name: GrantSelect Search Save
Plugin URI: https://www.magimpact.com/
description: Functionality for management of saved search result.
Version: 1.0.0
Author: magIMPACT
Author URI: https://www.magimpact.com/
*/

define( 'GS_SAVED_SEARCH', '1.0.0' );
define( 'GS_SS_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GS_SS_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'SEARCH', 0);
define( 'EDITOR', 1);

register_activation_hook( __FILE__, 'gs_search_install' );

function gs_search_install(){
    global $wpdb;
    $create_tables_query = array();

    // User Table Alter
    $charset_collate = $wpdb->get_charset_collate();
    //$create_tables_query[0] = "DROP TABLE `" . $wpdb->prefix . "gs_save_searchresult`";
    $create_tables_query[0] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "gs_save_searchresult` (
                                                        `ID` bigint(20) NOT NULL AUTO_INCREMENT,
                                                        `user_id` bigint(20) NOT NULL default 0,
                                                        `search_title` varchar(256) DEFAULT '' NOT NULL,
                                                        `entry_id` bigint(20) NOT NULL default 0,
                                                        `type` int(11) NOT NULL default 0,
                                                        `is_agent` int(11) NOT NULL default 0,
                                                        `created_at` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,	
                                                        PRIMARY KEY (`ID`)
                                                        ) $charset_collate;";
    
    $create_tables_query[1] = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "gs_save_searchresult_ids` (
                                                        `ID` bigint(20) NOT NULL AUTO_INCREMENT,
                                                        `ss_id` bigint(20) NOT NULL default 0,
                                                        `grant_id` bigint(20) NOT NULL default 0,	
                                                        PRIMARY KEY (`ID`)
                                                        ) $charset_collate;";
    foreach ($create_tables_query as $create_table_query) {
        $wpdb->query($create_table_query);
    }
}

Class Grantselect_Search_Save {
    private $table_save_search_result;
    private $per_pages;
    public function __construct(){
        global $wpdb;
        $this->table_save_search_result   = $wpdb->prefix . "gs_save_searchresult";
        $this->per_pages = [10, 20, 50, 100];
        
        $this->init();
    }
    private function init(){
        
        //get saved search list
        add_shortcode("gs-saved-search-list", array($this, "get_gs_saved_search_list"));

        //get saved search entry data
        add_shortcode("gs-search-agent-edit", array($this, "get_gs_search_agent_edit"));

        //ajax paginate
        add_action( 'wp_ajax_gs_saved_search_list', array($this, 'get_saved_search_list') );
        add_action( 'wp_ajax_nopriv_gs_saved_search_list', array($this, 'get_saved_search_list') );

        //ajax paginate
        add_action( 'wp_ajax_gs_saved_search_removes', array($this, 'remove_saved_search') );
        add_action( 'wp_ajax_nopriv_gs_saved_search_removes', array($this, 'remove_saved_search') );

        //gf submit button text change
        add_filter( 'gform_submit_button', array($this, 'form_submit_button'), 10, 2 );

        //ajax email alert list 
        add_action( 'wp_ajax_gs_search_agent_update', array($this, 'update_search_agent') );
        add_action( 'wp_ajax_nopriv_gs_search_agent_update', array($this, 'update_search_agent') );
    }
    
    public function get_saved_search_list(){
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
            $is_agent = 0;
            if (isset($_POST['is_agent'])){
                $is_agent = $_POST['is_agent'];
            }
            $where = "";
            //Search Title
            if (isset($_POST['search_val']) && $_POST['search_val'] != ""){
                $where .= ' and search_title like "%' . $_POST['search_val'] . '%" ';
            }
            $user_id        = get_current_user_id();
            $results        = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table_save_search_result} where type=%d and is_agent=%d and user_id=%d {$where} ORDER BY created_at DESC LIMIT %d, %d", array($gs_type, $is_agent, $user_id, $start, $per_page)));
            $count          = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$this->table_save_search_result} where type=%d and is_agent=%d and user_id=%d {$where}", array($gs_type, $is_agent, $user_id)));
            $total_count    = $wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$this->table_save_search_result} where type=%d and is_agent=%d and user_id=%d", array($gs_type, $is_agent, $user_id)));
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
            if( file_exists( GS_SS_DIR_PATH . 'templates/saved_search_ajax_table.php' ) ){
                include GS_SS_DIR_PATH . 'templates/saved_search_ajax_table.php';
            }
            $paginate_html = ob_get_clean();
        }
        echo $paginate_html;
        exit(0);
    }
    public function get_gs_saved_search_list($atts){
        wp_enqueue_style( 'ssearch', GS_SS_DIR_URL . 'css/savedsearch.css', array(),  GS_SAVED_SEARCH);
        wp_enqueue_script( 'ssearch', GS_SS_DIR_URL . 'js/savedsearch.js', array('jquery'), GS_SAVED_SEARCH, true );
        
        global $wpdb;
        $gs_type = 0;
        if (isset($atts['type']) && strtolower($atts['type'])=="editor"){
            $gs_type = 1;
        }
        $is_agent = 0;
        if (isset($atts['agent']) && strtolower($atts['agent'])=="true"){
            $is_agent = 1;
        }
        ob_start();
        if( file_exists( GS_SS_DIR_PATH . 'templates/saved_search_lists.php' ) ){
            include GS_SS_DIR_PATH . 'templates/saved_search_lists.php';
        }
        $content = ob_get_clean();
        return $content;
    }
    public function remove_saved_search(){
        global $wpdb;
        $sids = $_POST['sids'];
        foreach ($sids as $sid){
            $result = $wpdb->query(
                $wpdb->prepare(
                    "
                    DELETE FROM {$this->table_save_search_result} 
                    WHERE ID=%d",
                    array($sid)
                )
            );
        }
        echo json_encode(['success'=>true]);
        die();
    }
    public function get_gs_search_agent_edit($atts){
        global $wpdb;
        $content = "";
        if (!isset($_GET['id'])){
            wp_enqueue_style( 'ss-agent', GS_SS_DIR_URL . 'css/savedsearch.css', array('css'),  GS_SAVED_SEARCH);
            wp_enqueue_script( 'ss-agent', GS_SS_DIR_URL . 'js/search-agent.js', array('jquery'), GS_SAVED_SEARCH, true );
            $SA_VAR['entry_id'] = "0";
            $SA_VAR['redirect_url'] = home_url('/access/search-agents');
            wp_localize_script( 'ss-agent', 'SA_VAR', $SA_VAR );
            $content = "<div class='gs-wrap-container'>";
            $content .= "<div class='gs-form-wrapper'>";
            $content .= "<label class='gfield_label'>Search Agent Title:</label>";
            $content .= "<div class='ginput_container ginput_container_text'>";
            $content .= "<input type='hidden' id='sa_id' name='sa_id' value='0'>";
            $content .= "<input name='sa_name' id='sa_name' type='text' value='' class='large' aria-invalid='false'>";
            $content .= "</div>";
            //advanced search form short code
            $content .= do_shortcode( '[gravityform id="2" title="false" description="false" ajax="true" ]');
            
            $content .= "<p class='success-msg'></p>";
            $content .= "</div>";
            $content .= "</div>";
            
        }else{
            $ID = $_GET['id'];

            $saved_search_row = $wpdb->get_row($wpdb->prepare("select * from {$wpdb->prefix}gs_save_searchresult where ID=%d", array($ID)));
            
            if ($saved_search_row && $saved_search_row->is_agent == 1){
                
                $entry_info = $wpdb->get_row($wpdb->prepare("select * from {$wpdb->prefix}gf_entry where id=%d", array($saved_search_row->entry_id)));
                
                if ($entry_info){
                    wp_enqueue_style( 'ss-agent', GS_SS_DIR_URL . 'css/savedsearch.css', array(),  GS_SAVED_SEARCH);
                    wp_enqueue_script( 'ss-agent', GS_SS_DIR_URL . 'js/search-agent.js', array('jquery'), GS_SAVED_SEARCH, true );
                    $SA_VAR['entry_id'] = $saved_search_row->entry_id;
                    $SA_VAR['redirect_url'] = home_url('/access/search-agents');
                    wp_localize_script( 'ss-agent', 'SA_VAR', $SA_VAR );
                    $content = "<div class='gs-wrap-container'>";
                    $content .= "<div class='gs-form-wrapper'>";
                    $content .= "<label class='gfield_label'>Search Agent Title:</label>";
                    $content .= "<div class='ginput_container ginput_container_text'>";
                    $content .= "<input type='hidden' id='sa_id' name='sa_id' value='".$saved_search_row->ID."'>";
                    $content .= "<input name='sa_name' id='sa_name' type='text' value='".$saved_search_row->search_title."' class='large' aria-invalid='false'>";
                    $content .= "</div>";
                    $content .= do_shortcode( '[gravityform id="'.$entry_info->form_id.'" title="false" description="false" ajax="true" ]');
                    $content .= "<p class='success-msg'></p>";
                    $content .= "</div>";
                    $content .= "</div>";
                }
                
            }
            
        }
        return $content;
    }
    /**
     * Function form_submit_button
     * update search agent info.
     *
     * @return void
     */
    public function form_submit_button( $button, $form ) {
        global $wpdb;
        if (isset($_GET['agent']) && ($_GET['agent'] == 'edit' || $_GET['agent'] == 'create')){
            $ID = $_GET['id'];
            $saved_search_row = $wpdb->get_row($wpdb->prepare("select * from {$wpdb->prefix}gs_save_searchresult where ID=%d", array($ID)));
            //search agent button
            return "<input type='hidden' id='ss_id' name='ss_id' value='{$saved_search_row->ID}'><button class='button gform_button' data-id='{$form['id']}' id='gform_submit_button'><span>Submit</span></button>";
        }else{
            return $button;
        }
    }
    /**
     * Function update_search_agent
     * update search agent info.
     *
     * @return void
     */
    public function update_search_agent(){
        global $wpdb;
        if ($_POST['ID'] == 0){
            //create search agent info with form entry id
            $wpdb->insert(
                $wpdb->prefix . "gs_save_searchresult",
                [
                    'user_id'       => get_current_user_id(),
                    'search_title'  => $_POST['search_title'],
                    'entry_id'      => $_POST['entry_id'],
                    'type'          => 0,
                    'created_at'    => date("Y-m-d h:i:s"),
                    'is_agent'      => 1
                ]
            );
            
        }else{
            $row = $wpdb->get_row($wpdb->prepare("select * from {$wpdb->prefix}gs_save_searchresult where ID=%d", array($_POST['ID'])));
            GFAPI::delete_entry( $row->entry_id );
            $wpdb->update(
                $wpdb->prefix . "gs_save_searchresult",
                [
                    'search_title'  => $_POST['search_title'],
                    'entry_id'      => $_POST['entry_id']
                ],
                [
                    'ID'            => $_POST['ID']
                ]
            );
        }
        echo json_encode(array("success"=>true));
        exit(0);
    }
}
new Grantselect_Search_Save;