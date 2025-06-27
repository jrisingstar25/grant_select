<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

/**
 * Display a warning to the administrators if the API credentials are missing in the
 * register page
 *
 */
function pms_stripe_api_credentials_admin_warning() {

    if( !current_user_can( 'manage_options' ) )
        return;

    $are_active = array_intersect( array( 'stripe', 'stripe_intents' ), pms_get_active_payment_gateways() );

    if( pms_get_stripe_api_credentials() == false && !empty( $are_active ) ) {

        echo '<div class="pms-warning-message-wrapper">';
            echo '<p>' . sprintf( __( 'Your Stripe API settings are missing. In order to make payments you will need to add your API credentials %1$s here %2$s.', 'paid-member-subscriptions' ), '<a href="' . admin_url( 'admin.php?page=pms-settings-page&nav_tab=payments#pms-settings-payment-gateways' ) .'" target="_blank">', '</a>' ) . '</p>';
            echo '<p><em>' . __( 'This message is visible only by Administrators.', 'paid-member-subscriptions' ) . '</em></p>';
        echo '</div>';

    }

}
add_action( 'pms_new_subscription_form_top', 'pms_stripe_api_credentials_admin_warning' );
add_action( 'pms_upgrade_subscription_form_top', 'pms_stripe_api_credentials_admin_warning' );
add_action( 'pms_renew_subscription_form_top', 'pms_stripe_api_credentials_admin_warning' );
add_action( 'pms_retry_payment_form_top', 'pms_stripe_api_credentials_admin_warning' );


/**
 * Cancel Stripe subscription before the user upgrades the subscription
 *
 */
function pms_stripe_cancel_subscription_before_upgrade( $member_subscription_id, $payment_data ) {

    $user_id = $payment_data['user_id'];

    // Get payment_profile_id
    $payment_profile_id = pms_member_get_payment_profile_id( $user_id, $member_subscription_id );

    // Continue only if the profile id is a PayPal one
    if( !pms_is_stripe_payment_profile_id($payment_profile_id) )
        return;

    // Instantiate the payment gateway with data
    $payment_data = array(
        'user_data' => array(
            'user_id'       => $user_id,
            'subscription'  => pms_get_subscription_plan( $member_subscription_id )
        )
    );

    $stripe_gate = pms_get_payment_gateway( 'stripe', $payment_data );

    // Cancel the subscription and return the value
    $confirmation = $stripe_gate->cancel_subscription( $payment_profile_id );

}
add_action( 'pms_stripe_before_upgrade_subscription', 'pms_stripe_cancel_subscription_before_upgrade', 10, 2 );

add_action( 'wp_ajax_pms_create_payment_intent', 'pms_stripe_create_payment_intent' );
add_action( 'wp_ajax_nopriv_pms_create_payment_intent', 'pms_stripe_create_payment_intent' );
function pms_stripe_create_payment_intent(){

    if( !check_ajax_referer( 'pms_create_payment_intent', 'pms_nonce' ) )
        die();

    if( !isset( $_POST['form_type'] ) )
        die();

    // If the user is not logged in, the data from the register form needs to be validated
    if( !is_user_logged_in() ){

        // Validate PMS Register form
        if( $_POST['form_type'] == 'pms' ){

            // This also validates PWYW
            if( !PMS_Form_Handler::validate_register_form() ){
                $errors = pms_stripe_get_generated_errors();

                echo json_encode( array(
                    'success' => false,
                    'data'    => $errors,
                ) );
                die();
            }

            // Validate subscription plans
            if( !PMS_Form_Handler::validate_subscription_plans() || !PMS_Form_Handler::validate_subscription_plans_member_eligibility() ){
                $errors = pms_stripe_get_generated_errors();

                echo json_encode( array(
                    'success' => false,
                    'data'   => $errors,
                ) );
                die();
            }

        // Validate WPPB Register form
        } else if( $_POST['form_type'] == 'wppb' && !empty( $_POST['wppb_fields' ] ) ){

            $wppb_errors = pms_stripe_validate_wppb_form_fields();

            // Validate PMS fields
            PMS_Form_Handler::validate_subscription_plans();
            PMS_Form_Handler::validate_subscription_plans_member_eligibility();

            $pms_errors  = pms_stripe_get_generated_errors();

            if( !empty( $wppb_errors ) || !empty( $pms_errors ) ){
                echo json_encode( array(
                    'success'     => false,
                    'data'        => '',
                    'wppb_errors' => $wppb_errors,
                    'pms_errors'  => $pms_errors,
                ) );
                die();
            }

        } else if( $_POST['form_type'] == 'pms_email_confirmation' && !empty( $_POST['pms_user_id'] ) ){

            // Validate Billing Fields
            do_action( 'pms_register_form_validation' );

            $errors = pms_stripe_get_generated_errors();

            if( !empty( $errors ) ){
                echo json_encode( array(
                    'success' => false,
                    'data'   => $errors,
                ) );
                die();
            }

        }

    } else {

        if( $_POST['form_type'] == 'pms_new_subscription' ){

            // We only validate the subscription plans if MSPU is active since the user can have multiple plans
            if( class_exists( 'PMS_MSU_Form_Handler' ) )
                PMS_Form_Handler::validate_subscription_plans();
            else
                PMS_Form_Handler::validate_new_subscription_form();

        } else if( $_POST['form_type'] == 'pms_upgrade_subscription' ){

            PMS_Form_Handler::validate_upgrade_subscription_form();

        } else if( $_POST['form_type'] == 'pms_renew_subscription' ){

            PMS_Form_Handler::validate_renew_subscription_form();

        } else if( $_POST['form_type'] == 'pms_confirm_retry_payment_subscription' ){

            PMS_Form_Handler::validate_retry_payment_form();

        }

        $errors = pms_stripe_get_generated_errors();

        if( !empty( $errors ) ){
            echo json_encode( array(
                'success'    => false,
                'pms_errors' => $errors,
            ) );
            die();
        }

    }

    // Initialize gateway
    $gateway = new PMS_Payment_Gateway_Stripe_Payment_Intents();
    $gateway->init();

    if( isset( $_POST['setup_intent'] ) && $_POST['setup_intent'] == true )
        $gateway->create_setup_intent();
    else
        $gateway->create_payment_intent();

    die();

}

add_action( 'wp_ajax_pms_confirm_payment_intent', 'pms_stripe_confirm_payment_intent' );
add_action( 'wp_ajax_nopriv_pms_confirm_payment_intent', 'pms_stripe_confirm_payment_intent' );
function pms_stripe_confirm_payment_intent(){
    $gateway = new PMS_Payment_Gateway_Stripe_Payment_Intents();
    $gateway->init();

    $gateway->confirm_payment_intent();

    die();
}

add_action( 'wp_ajax_pms_failed_payment_authentication', 'pms_stripe_failed_payment_authentication' );
add_action( 'wp_ajax_nopriv_pms_failed_payment_authentication', 'pms_stripe_failed_payment_authentication' );
function pms_stripe_failed_payment_authentication(){
    $gateway = new PMS_Payment_Gateway_Stripe_Payment_Intents();
    $gateway->init();

    $gateway->failed_payment_authentication();

    die();
}

add_action( 'wp_ajax_pms_reauthenticate_intent', 'pms_reauthenticate_intent' );
add_action( 'wp_ajax_nopriv_pms_reauthenticate_intent', 'pms_reauthenticate_intent' );
function pms_reauthenticate_intent(){
    $gateway = new PMS_Payment_Gateway_Stripe_Payment_Intents();
    $gateway->init();

    $gateway->reauthenticate_intent();

    die();
}

function pms_stripe_validate_wppb_form_fields(){

    // Load fields
    include_once( WPPB_PLUGIN_DIR .'/front-end/default-fields/default-fields.php' );
    if( function_exists( 'wppb_include_extra_fields_files' ) )
        wppb_include_extra_fields_files();

    // Load WPPB fields data
    $wppb_manage_fields = get_option( 'wppb_manage_fields', 'not_found' );

    $output_field_errors = array();

    foreach( $_POST['wppb_fields'] as $id => $value ) {

        $field = array();

        // return field name from field class
        $field_name = explode( ' ', $value['class'] );
        $field_name = substr( $field_name[1], 5 );
        $field_name = esc_attr( $field_name );

        // return field title by removing required sign *
        if( isset( $value['title'] ) ) {
            $field['field-title'] = str_replace( '*', '', $value['title'] );
            $field['field-title'] = sanitize_text_field( $field['field-title'] );
        }

        // return the id of the field from the field li (wppb-form-element-XX)
        if( isset( $id ) ) {
            $field_id = intval( substr( $id, 18 ) );
        }

        // check for fields errors for woocommerce billing fields
        if( $field_name == 'woocommerce-customer-billing-address' ) {
            if( function_exists( 'wppb_woo_billing_fields_array' ) && function_exists( 'wppb_check_woo_individual_fields_val' ) ) {
                $field['field'] = 'WooCommerce Customer Billing Address';

                $billing_fields = wppb_woo_billing_fields_array();

                if( ! empty( $_POST['billing_country'] ) && class_exists( 'WC_Countries' ) ) {
                    $WC_Countries_Obj = new WC_Countries();
                    $locale = $WC_Countries_Obj->get_country_locale();

                    if( isset( $locale[$_POST['billing_country']]['state']['required'] ) && ( $locale[$_POST['billing_country']]['state']['required'] == false ) ) {
                        if( is_array( $billing_fields ) && isset( $billing_fields['billing_state'] ) ) {
                            $billing_fields['billing_state']['required'] = 'No';
                        }
                    }
                }

                if( isset( $value['fields'] ) ) {
                    foreach( $value['fields'] as $key => $woo_field_label ) {
                        $key = sanitize_text_field( $key );

                        $woo_error_for_field = wppb_check_woo_individual_fields_val( '', $billing_fields[$key], $key, $_POST, $_POST['form_type'] );

                        if( ! empty( $woo_error_for_field ) ) {
                            $output_field_errors[$key]['field'] = $key;
                            $output_field_errors[$key]['error'] = '<span class="wppb-form-error">'. $woo_error_for_field .'</span>';
                            $output_field_errors[$key]['type'] = 'woocommerce';
                        }
                    }
                }
            }
        }

        // check for fields errors for woocommerce shipping fields
        if( $field_name == 'woocommerce-customer-shipping-address' ) {
            if( function_exists( 'wppb_woo_shipping_fields_array' ) && function_exists( 'wppb_check_woo_individual_fields_val' ) ) {
                $field['field'] = 'WooCommerce Customer Shipping Address';

                $shipping_fields = wppb_woo_shipping_fields_array();

                if( ! empty( $_POST['shipping_country'] ) && class_exists( 'WC_Countries' ) ) {
                    $WC_Countries_Obj = new WC_Countries();
                    $locale = $WC_Countries_Obj->get_country_locale();

                    if( isset( $locale[$_POST['shipping_country']]['state']['required'] ) && ( $locale[$_POST['shipping_country']]['state']['required'] == false ) ) {
                        if( is_array( $shipping_fields ) && isset( $shipping_fields['shipping_state'] ) ) {
                            $shipping_fields['shipping_state']['required'] = 'No';
                        }
                    }
                }

                if( isset( $value['fields'] ) ) {
                    foreach( $value['fields'] as $key => $woo_field_label ) {
                        $key = sanitize_text_field( $key );

                        $woo_error_for_field = wppb_check_woo_individual_fields_val( '', $shipping_fields[$key], $key, $_POST, $_POST['form_type'] );

                        if( ! empty( $woo_error_for_field ) ) {
                            $output_field_errors[$key]['field'] = $key;
                            $output_field_errors[$key]['error'] = '<span class="wppb-form-error">'. $woo_error_for_field .'</span>';
                            $output_field_errors[$key]['type'] = 'woocommerce';
                        }
                    }
                }
            }
        }

        // add repeater fields to fields array
        if( isset( $value['extra_groups_count'] ) ) {
            $wppb_manage_fields = apply_filters( 'wppb_form_fields', $wppb_manage_fields, array( 'context' => 'multi_step_forms', 'extra_groups_count' => esc_attr( $value['extra_groups_count'] ), 'global_request' => $_POST, 'form_type' => 'register' ) );
        }

        // search for fields in fields array by meta-name or id (if field does not have a mata-name)
        if( ! empty( $value['meta-name'] ) && $value['meta-name'] != 'passw1' && $value['meta-name'] != 'passw2' && pms_wppb_msf_get_field_options( $value['meta-name'], $wppb_manage_fields ) !== false ) {
            $field = pms_wppb_msf_get_field_options( $value['meta-name'], $wppb_manage_fields );
        } elseif( ! empty( $field_id ) && pms_wppb_msf_get_field_options( $field_id, $wppb_manage_fields, 'id' ) !== false
            && $field_name != 'woocommerce-customer-billing-address' && $field_name != 'woocommerce-customer-shipping-address' ) {

            //@TODO: DON'T FORGET TO BRING THIS FUNCTION TO STRIPE
            $field = pms_wppb_msf_get_field_options( $field_id, $wppb_manage_fields, 'id' );
        }

        // check for fields errors
        if( $field_name != 'woocommerce-customer-billing-address' && $field_name != 'woocommerce-customer-shipping-address' ) {
            $error_for_field = apply_filters( 'wppb_check_form_field_'. $field_name, '', $field, $_POST, 'register' );
        }

        // construct the array with fields errors
        if( ! empty( $value['meta-name'] ) && ! empty( $error_for_field ) ) {
            $output_field_errors[esc_attr( $value['meta-name'] )]['field'] = $field_name;
            $output_field_errors[esc_attr( $value['meta-name'] )]['error'] = '<span class="wppb-form-error">'. wp_kses_post( $error_for_field ) .'</span>';
        }

    }

    $output_field_errors = apply_filters( 'wppb_output_field_errors_filter', $output_field_errors );

    return $output_field_errors;

}

// Intents rework notices
add_action( 'plugins_loaded', 'pms_stripe_rework_notices' );
function pms_stripe_rework_notices() {

     /**
      * Add a notice if core is out of date
      */
      if ( defined( 'PMS_VERSION' ) && version_compare( PMS_VERSION, '2.4.0', '<' ) ) {

          $message = __( 'Your <strong>Paid Member Subscriptions</strong> version is not 100% compatible with the current version of the <strong>Stripe add-on</strong>.<br>', 'paid-member-subscriptions' );
          $message .= __( 'Please update <strong>Paid Member Subscriptions</strong> to the latest version.', 'paid-member-subscriptions' );

          new PMS_Add_General_Notices( 'pms_stripe_rework_core_incompatibility',
              $message,
              'error' );

      }

     /**
      * Add a notice if Tax add-on is out of date
      */
      if ( defined( 'PMS_TAX_VERSION' ) && version_compare( PMS_TAX_VERSION, '1.1.7', '<' ) ) {

          $message = __( 'Your <strong>Tax & EU VAT</strong> add-on version is not 100% compatible with the current version of the <strong>Stripe add-on</strong>.<br>', 'paid-member-subscriptions' );
          $message .= __( 'Please update the <strong>Tax & EU VAT</strong> add-on to the latest version.', 'paid-member-subscriptions' );

          new PMS_Add_General_Notices( 'pms_stripe_rework_tax_addon_incompatibility',
              $message,
              'error' );

      }

}
