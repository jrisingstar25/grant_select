<?php
defined( 'ABSPATH' ) or die();

/*
Plugin Name: GrantSelect Search Functionality
Plugin URI: https://www.magimpact.com
description: Functionality for search features in Search Portal.
Version: 1.0.0
Author: magIMPACT
Author URI: https://www.magimpact.com

License: Copyright 2020-2021 GrantSelect. All Rights Reserved
*/

define( 'GF_GRANTSELECT_SEARCH_ADDON_VERSION', '1.0.1' );
define( 'LOGIN_STATUS', '0' );
define( 'SEARCH_STATUS', '1' );
define( 'EMAILALERT_STATUS', '2' );
define( 'GRANTDETAIL_STATUS', '3' );
define( 'OTHER_ACCESS', '4' );
define( 'SAVED_SEARCH', '5' );
define( 'SEARCH_AGENT', '6' );
define( 'SEARCH_STATUS_PN', '7' );

date_default_timezone_set('America/Indiana/Indianapolis');

add_action( 'gform_loaded', array( 'GrantSelect_Search_Bootstrap', 'load' ), 5 );
add_shortcode('grantselect_search_results', array('GrantSelectSearchAddOn', 'grantselect_search_display_results'));
add_shortcode('grantselect_grant_details', array('GrantSelectSearchAddOn', 'grantselect_search_grant_details'));

add_filter( 'gform_pre_render', array('GrantSelectSearchAddOn', 'grantselect_populate_form_fields') );
add_filter( 'gform_pre_validation', array('GrantSelectSearchAddOn', 'grantselect_populate_form_fields') );
add_filter( 'gform_pre_submission_filter', array('GrantSelectSearchAddOn', 'grantselect_populate_form_fields') );
//add_filter( 'gform_admin_pre_render', array('GrantSelectSearchAddOn', 'grantselect_populate_form_fields') );

add_filter( 'gform_pre_render', array('GrantSelectSearchAddOn', 'grantselect_prepopulate_forms' ) );
add_action( 'genesis_after', array( 'GrantSelectSearchAddOn', 'grantselect_access_search_javascript' ) );
add_action( 'wp_enqueue_scripts', 'grantselect_search_enqueue_scripts' );
add_action( 'wp_ajax_process_mass_edits', array( 'GrantSelectRecordsAddOn', 'process_mass_edits' ) );

add_action( 'wp_ajax_search_results', array( 'GrantSelectSearchAddOn', 'grantselect_search_display_results' ) );
//add for IP Auth and Referrer Users.
add_action( 'wp_ajax_nopriv_search_results', array( 'GrantSelectSearchAddOn', 'grantselect_search_display_results' ) );

add_action( 'wp_ajax_sharing_result', array( 'GrantSelectSearchAddOn', 'grantselect_search_display_results' ) );
//add for IP Auth and Referrer Users.
add_action( 'wp_ajax_nopriv_sharing_result', array('GrantSelectSearchAddOn', 'grantselect_search_display_results') ); 

add_action( 'wp_ajax_grant_detail', array( 'GrantSelectSearchAddOn', 'grantselect_search_grant_details' ) );
//add for IP Auth and Referrer Users.
add_action( 'wp_ajax_nopriv_grant_detail', array( 'GrantSelectSearchAddOn', 'grantselect_search_grant_details' ) );

add_action( 'wp_ajax_gs_save_per_page', 'save_per_page');
add_action( 'wp_ajax_nopriv_gs_save_per_page', 'save_per_page');

//save saved search result
add_action( 'wp_ajax_gs_save_search_result', array('GrantSelectSearchAddOn', 'save_search_result') );
add_action( 'wp_ajax_nopriv_gs_save_search_result', array('GrantSelectSearchAddOn', 'save_search_result') ); 

//add_action('wp_ajax_access_filter_subjects', array( 'GrantSelectSearchAddOn', 'grantselect_access_filter_subjects' ));
//add_action('wp_ajax_nopriv_access_filter_subjects', array( 'GrantSelectSearchAddOn', 'grantselect_please_login' ));


function grantselect_search_enqueue_scripts() {
    wp_enqueue_script( 'sesarch-js', plugin_dir_url( __FILE__ ) . 'js/search.js', array('jquery'), GF_GRANTSELECT_SEARCH_ADDON_VERSION );
    wp_enqueue_style('jquery-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
    wp_enqueue_style( 'sesarch-css', plugin_dir_url( __FILE__ ) . 'grantselect-search.css', array(), GF_GRANTSELECT_SEARCH_ADDON_VERSION );
    $ss_url = array( site_url("/access/saved-search/"), site_url("/editor/search/saved-searches/"), site_url("/access/search-agents"));
	wp_localize_script( 'sesarch-js', 'ss_url', $ss_url );
}


class GrantSelect_Search_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once('class-grantselect-search.php');

        GFAddOn::register( 'GrantSelectSearchAddOn' );
    }

}

function grantselect_search() {
    return GrantSelectSearchAddOn::get_instance();
}
function save_per_page(){
    if (isset($_POST['per_page'])){
        update_user_meta(get_current_user_id(), 'gs_per_page', $_POST['per_page']);
    }
    echo json_encode(['success'=>true]);
    exit(0);
}