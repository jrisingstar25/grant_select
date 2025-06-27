<?php
/**
 * Plugin Name: Paid Member Subscriptions - Group Memberships Add-on
 * Plugin URI: https://www.cozmoslabs.com/
 * Description: Sell group subscriptions to your members. These are umbrella memberships that contain multiple seats purchased and managed by a single account.
 * Version: 1.1.5
 * Author: Cozmoslabs, Georgian Cocora
 * Author URI: https://www.cozmoslabs.com/
 * Text Domain: paid-member-subscriptions
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2019 Cozmoslabs (www.cozmoslabs.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_Group_Memberships_Base {

    public function __construct(){

        define( 'PMS_GM_VERSION', '1.1.5' );
        define( 'PMS_GM_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        define( 'PMS_GM_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );

        if( version_compare( PMS_VERSION, '1.9.2', '>=' ) ){
            $this->load_dependencies();
            $this->init();
        } else {
            $message = __( 'Your version of Paid Member Subscriptions is not compatible with the Group Memberships add-on. Please update Paid member subscriptions to the latest version.', 'paid-member-subscriptions' );

            $pms_notifications_instance = PMS_Plugin_Notifications::get_instance();

            if( !$pms_notifications_instance->is_plugin_page() ) {
                $message .= sprintf(__(' %1$sDismiss%2$s', 'paid-member-subscriptions'), "<a class='dismiss-right' href='" . esc_url(add_query_arg('pms_group_memberships_core_version_message_dismiss_notification', '0')) . "'>", "</a>");
                $pms_force_show = false;
            }
            else{
                $pms_force_show = true;
            }

            new PMS_Add_General_Notices( 'pms_group_memberships_core_version_message',
                $message,
                'error',
                '',
                '',
                $pms_force_show );
        }

    }

    private function init(){

        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

        register_activation_hook( __FILE__, array( $this, 'install' ) );

        add_action( 'init', array( $this, 'add_notice' ) );

    }


    /**
     * Load needed files
     *
     */
    private function load_dependencies(){

        if( is_admin() ){
            if( file_exists( PMS_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-info-list-table.php' ) )
                include PMS_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-info-list-table.php';

            if( file_exists( PMS_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-members-list-table.php' ) )
                include PMS_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-members-list-table.php';

            if( file_exists( PMS_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-memberships.php' ) )
                include PMS_GM_PLUGIN_DIR_PATH . 'includes/admin/class-admin-group-memberships.php';
        }

        if( file_exists( PMS_GM_PLUGIN_DIR_PATH . 'includes/functions.php' ) )
            include PMS_GM_PLUGIN_DIR_PATH . 'includes/functions.php';

        if( file_exists( PMS_GM_PLUGIN_DIR_PATH . 'includes/class-emails.php' ) )
            include PMS_GM_PLUGIN_DIR_PATH . 'includes/class-emails.php';

        if( file_exists( PMS_GM_PLUGIN_DIR_PATH . 'includes/class-group-memberships.php' ) )
            include PMS_GM_PLUGIN_DIR_PATH . 'includes/class-group-memberships.php';

    }

    public function install(){

        if( get_option( 'pms_gm_first_activation', false ) === false ){

            update_option( 'pms_gm_first_activation', time() );

            $email_settings = get_option( 'pms_emails_settings', array() );

            $email_settings['invite_is_enabled'] = 'yes';

            update_option( 'pms_emails_settings', $email_settings );

        }
    }


    /**
     * Enqueue admin scripts
     *
     */
    public function admin_scripts( $hook ){

        if( get_post_type() == 'pms-subscription' )
            wp_enqueue_script( 'pms-gm-admin-script', PMS_GM_PLUGIN_DIR_URL . 'assets/js/admin.js', array( 'jquery' ), PMS_GM_VERSION );

        if( $hook == 'paid-member-subscriptions_page_pms-members-page' ){
            wp_enqueue_style( 'pms-gm-style-back-end', PMS_GM_PLUGIN_DIR_URL . 'assets/css/style-back-end.css', array(), PMS_GM_VERSION );
            wp_enqueue_script( 'pms-gm-admin-group-details', PMS_GM_PLUGIN_DIR_URL . 'assets/js/admin-group-details.js', array( 'jquery' ), PMS_GM_VERSION );

            wp_localize_script( 'pms-gm-admin-group-details', 'pms_gm', array(
                'ajax_url'                      => admin_url( 'admin-ajax.php' ),
                'edit_group_details_nonce'      => wp_create_nonce( 'pms_gm_admin_edit_group_details_nonce' ),
                'resend_group_invitation_nonce' => wp_create_nonce( 'pms_group_subscription_resend_invitation' ),
                'remove_user_message'           => esc_html__( 'Are you sure you want to remove this member ?', 'paid-member-subscriptions' )
            ));
        }

    }

    public function frontend_scripts(){

        wp_enqueue_style( 'pms-group-memberships-style-front', plugin_dir_url(__FILE__). 'assets/css/style-front-end.css' );

        wp_enqueue_script( 'pms-frontend-group-memberships-js', plugin_dir_url(__FILE__) . 'assets/js/front-end.js', array( 'jquery' ), PMS_GM_VERSION );

        if( get_query_var( 'tab' ) == 'manage-group' ){
            wp_enqueue_script( 'pms-gm-group-dashboard', plugin_dir_url(__FILE__) . 'assets/js/frontend-group-dashboard.js', array( 'jquery' ), PMS_GM_VERSION );

            wp_localize_script( 'pms-gm-group-dashboard', 'pms_gm', array(
                'ajax_url'                      => admin_url( 'admin-ajax.php' ),
                'remove_group_member_nonce'     => wp_create_nonce( 'pms_group_subscription_member_remove' ),
                'resend_group_invitation_nonce' => wp_create_nonce( 'pms_group_subscription_resend_invitation' ),
                'remove_user_message'           => esc_html__( 'Are you sure you want to remove this member ?', 'paid-member-subscriptions' ),
            ) );
        }

    }

    public function add_notice(){

        if( version_compare( PMS_VERSION, '2.0.7', '<' ) ){

            $message = __( 'Your version of <strong>Paid Member Subscriptions</strong> is not 100% compatible with the <strong>Group Memberships</strong> add-on. Please update to <strong>Paid Member Subscriptions</strong> version <strong>2.0.7</strong> or above.', 'paid-member-subscriptions' );

            $pms_notifications_instance = PMS_Plugin_Notifications::get_instance();

            if( !$pms_notifications_instance->is_plugin_page() ) {
                $message .= sprintf(__(' %1$sDismiss%2$s', 'paid-member-subscriptions'), "<a class='dismiss-right' href='" . esc_url(add_query_arg('pms_group_memberships_core_version_message_update_dismiss_notification', '0')) . "'>", "</a>");
                $pms_force_show = false;
            }
            else{
                $pms_force_show = true;
            }

            new PMS_Add_General_Notices( 'pms_group_memberships_core_version_message_update',
                $message,
                'error',
                '',
                '',
                $pms_force_show );

        }

    }

}

new PMS_Group_Memberships_Base;

if( class_exists( 'pms_PluginUpdateChecker' ) ) {
    $slug              = 'group-memberships';
    $localSerial       = pms_get_serial_number();
    $pms_stripe_update = new pms_PluginUpdateChecker('http://updatemetadata.cozmoslabs.com/?localSerialNumber=' . $localSerial . '&uniqueproduct=CLPMSGM', __FILE__, $slug );
}
