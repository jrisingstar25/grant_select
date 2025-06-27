<?php
defined( 'ABSPATH' ) or die();

/*
Plugin Name: GrantSelect Report Functionality
Plugin URI: https://www.magimpact.com
description: Functionality for report features in Access Portal.
Version: 1.0.1
Author: magIMPACT
Author URI: https://www.magimpact.com

License: Copyright 2020-2021 GrantSelect. All Rights Reserved
*/

define( 'GF_GRANTSELECT_REPORTS_ADDON_VERSION', '1.0.1' );
date_default_timezone_set('America/Indiana/Indianapolis');

add_action( 'gform_loaded', array( 'GrantSelect_Reports_Bootstrap', 'load' ), 5 );
add_action( 'wp_enqueue_scripts', 'grantselect_reports_enqueue_scripts' );
add_action( 'wp_ajax_process_mass_edits', array( 'GrantSelectRecordsAddOn', 'process_mass_edits' ) );

add_shortcode('grantselect_report', array('GrantSelectReportsAddOn', 'grantselect_report'));

function grantselect_reports_enqueue_scripts() {
    wp_enqueue_script( 'reportforms-js', plugin_dir_url( __FILE__ ) . 'js/reportforms.js', array('jquery'), GF_GRANTSELECT_REPORTS_ADDON_VERSION );
}

class GrantSelect_Reports_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once('class-grantselect-reports.php');

        GFAddOn::register( 'GrantSelectReportsAddOn' );
    }

}

function grantselect_report() {
    return GrantSelectReportsAddOn::get_instance();
}
