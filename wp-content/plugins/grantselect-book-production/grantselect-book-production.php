<?php
defined( 'ABSPATH' ) or die();

/*
Plugin Name: GrantSelect Book Production Functionality
Plugin URI: https://www.magimpact.com
description: Functionality for book production features in Editor Portal.
Version: 1.0.0
Author: magIMPACT
Author URI: https://www.magimpact.com

License: Copyright 2020-2021 GrantSelect. All Rights Reserved
*/

define( 'GF_GRANTSELECT_BOOK_PRODUCTION_ADDON_VERSION', '1.0.0' );
date_default_timezone_set('America/Indiana/Indianapolis');

add_action( 'gform_loaded', array( 'GrantSelect_Book_Production_Bootstrap', 'load' ), 5 );
add_shortcode('grantselect_editorial_book_production_content', array('GrantSelectBookProductionAddOn', 'grantselect_book_production_content'));

add_filter( 'gform_pre_render', array('GrantSelectBookProductionAddOn', 'populate_form_fields') );
add_filter( 'gform_pre_validation', array('GrantSelectBookProductionAddOn', 'populate_form_fields') );
add_filter( 'gform_pre_submission_filter', array('GrantSelectBookProductionAddOn', 'populate_form_fields') );
//add_filter( 'gform_admin_pre_render', array('GrantSelectBookProductionAddOn', 'populate_form_fields') );

add_filter( 'gform_pre_render_10', array('GrantSelectBookProductionAddOn', 'grantselect_prepopulate_bp_form' ) );


class GrantSelect_Book_Production_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once('class-grantselect-book-production.php');

        GFAddOn::register( 'GrantSelectBookProductionAddOn' );
    }

}

function grantselect_book_production() {
    return GrantSelectBookProductionAddOn::get_instance();
}
