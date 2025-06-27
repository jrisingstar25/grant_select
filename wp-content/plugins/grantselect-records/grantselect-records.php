<?php
defined( 'ABSPATH' ) or die();

/*
Plugin Name: GrantSelect Records Functionality
Plugin URI: https://www.magimpact.com
description: Functionality for record management features in Editorial Portal. (Requires GrantSelect Search Functionality plugin installed and activated)
Version: 1.0.1
Author: magIMPACT
Author URI: https://www.magimpact.com

License: Copyright 2020-2021 GrantSelect. All Rights Reserved
*/

define( 'GF_GRANTSELECT_RECORDS_ADDON_VERSION', '1.0.1' );
date_default_timezone_set('America/Indiana/Indianapolis');

add_action( 'gform_loaded', array( 'GrantSelect_Records_Bootstrap', 'load' ), 6 );
add_action( 'wp_enqueue_scripts', 'grantselect_records_enqueue_scripts' );
add_action( 'wp_head', 'grantselect_header_scripts' );

add_shortcode('grantselect_records_summary', array('GrantSelectRecordsAddOn', 'grantselect_records_display_summary'));
add_shortcode('grantselect_record_manager', array('GrantSelectRecordsAddOn', 'grantselect_record_manager'));
add_shortcode('grantselect_editorial_search_results', array('GrantSelectRecordsAddOn', 'grantselect_search_display_results'));

//add_filter( 'gform_pre_render', array('GrantSelectSearchAddOn', 'grantselect_prepopulate_forms' ) );
//add_action( 'genesis_after', array( 'GrantSelectSearchAddOn', 'grantselect_access_search_javascript' ) );
add_action( 'wp_ajax_search_editor', array( 'GrantSelectRecordsAddOn', 'grantselect_search_display_results' ) );
add_action( 'wp_ajax_sharing_editor', array( 'GrantSelectRecordsAddOn', 'grantselect_search_display_results' ) );

function grantselect_records_enqueue_scripts() {
    wp_enqueue_script( 'grantselect-records-js', plugin_dir_url( __FILE__ ) . 'js/grantselect-records.js', array('jquery'), GF_GRANTSELECT_RECORDS_ADDON_VERSION );
    wp_enqueue_script( 'countrystatedd', plugin_dir_url( __FILE__ ) . 'js/countrystatedd.js', array('jquery'), GF_GRANTSELECT_RECORDS_ADDON_VERSION );
    wp_enqueue_script( 'filterlist', plugin_dir_url( __FILE__ ) . 'js/filterlist.js', array('jquery'), GF_GRANTSELECT_RECORDS_ADDON_VERSION );
    wp_enqueue_script( 'massedits-js', plugin_dir_url( __FILE__ ) . 'js/massedits.js', array('jquery', 'jquery-ui-dialog', 'jquery-ui-accordion'), GF_GRANTSELECT_RECORDS_ADDON_VERSION );
    wp_enqueue_script( 'jquery-ui-core');
    wp_enqueue_script( 'jquery-ui-tabs' );
    wp_localize_script( 'massedits-js', 'searchajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
}

function grantselect_header_scripts(){
    ?>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <?php
}

class GrantSelect_Records_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once('class-grantselect-records.php');
//        require_once('../grantselect-search/class-grantselect-search.php');

        GFAddOn::register( 'GrantSelectRecordsAddOn' );
    }

}

function grantselect_records() {
    return GrantSelectRecordsAddOn::get_instance();
}
