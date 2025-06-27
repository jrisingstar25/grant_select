<?php
/**
 * Plugin Name: Paid Member Subscriptions - Global Content Restriction Add-on
 * Plugin URI: http://www.cozmoslabs.com/
 * Description: Add global content restriction rules to subscription plans, based on post type, taxonomy and terms.
 * Version: 1.0.9
 * Author: Cozmoslabs, Madalin Ungureanu
 * Author URI: http://www.cozmoslabs.com
 * License: GPL2
 */
/*  Copyright 2015 Cozmoslabs (www.cozmoslabs.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
/*
* Define plugin path
*/
define( 'PMS_GCR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PMS_GCR_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

// Meta box for subscription content restriction
if( file_exists( PMS_GCR_PLUGIN_DIR . 'class-meta-box-subscription-plan-content-restriction.php' ) )
    include_once PMS_GCR_PLUGIN_DIR . 'class-meta-box-subscription-plan-content-restriction.php';

if( is_admin() )
    add_action( 'admin_enqueue_scripts', 'pms_gcr_enqueue_admin_scripts' );

function pms_gcr_enqueue_admin_scripts() {
    wp_enqueue_style( 'pms-gcr-style', PMS_GCR_PLUGIN_DIR_URL . 'assets/css/pms-global-content-restriction.css' );
    wp_enqueue_script( 'pms-gcr-js', PMS_GCR_PLUGIN_DIR_URL . 'assets/js/admin/meta-box-subscription-content-restriction.js' );
}

if( class_exists( 'pms_PluginUpdateChecker' ) ) {
    $slug           = 'global-content-restriction';
    $localSerial    = pms_get_serial_number();
    $pms_nmf_update = new pms_PluginUpdateChecker('http://updatemetadata.cozmoslabs.com/?localSerialNumber=' . $localSerial . '&uniqueproduct=CLPMSGCR', __FILE__, $slug );
}
