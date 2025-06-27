<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_GM {

    public function __construct(){
        // For Group Subscriptions, replace the view for both the owner and member
    }

    //Hooks
    public function dashboard( $content ){
        if( get_query_var( 'tab' ) !== 'manage-group' )
            return $content;

        // Get current user id
        $user_id = pms_get_current_user_id();

        // If subscription is not present in the url, determine automatically
        if( isset( $_GET['subscription_id'] ) ){
            $subscription = pms_get_member_subscription( sanitize_text_field( $_GET['subscription_id'] ) );
        } else {
            $subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );

            foreach( $subscriptions as $member_subscription ){
                $plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );

                if( $plan->type == 'group' ){
                    $subscription = $member_subscription;
                    break;
                }

            }
        }

        if( empty( $subscription->id ) )
            return $content;

        // Only Group Owners should access the Dashboard
        if( !pms_gm_is_group_owner( $subscription->id ) )
            return $content;

        $output = '';
        ob_start();

        // Go Back link
        ?>
        <div class="pms-group-dashboard">
            <?php
                // Invite Members
                include 'views/view-invite-members.php';

                // Members List
                include 'views/view-members-list.php';

                // Edit Details
                include 'views/view-edit-group-details.php';
            ?>
        </div>

        <?php
        $output .= ob_get_clean();

        return $output;
    }

    public function dashboard_page_title( $title, $id = null ){

        if( !is_admin() && get_query_var( 'tab' ) == 'manage-group' && $id == pms_get_page( 'account' ) && $group_name = pms_get_current_user_group_name() )
            return sprintf( esc_html__( '%s Group Dashboard', 'paid-member-subscriptions' ), $group_name );

        return $title;

    }

    public function dashboard_page_title_remove_from_menu( $menu, $args ){

        remove_filter( 'the_title', array( $this, 'dashboard_page_title' ), 10 );

        return $menu;

    }

    public function dashboard_page_title_add_title_filter( $items, $args ){

        add_filter( 'the_title', array( $this, 'dashboard_page_title' ), 10, 2 );

        return $items;

    }

    public function invite_members(){

    	// Do nothing if we cannot validate the nonce
    	if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_invite_members_form_nonce' ) )
    		return;

    	if( empty( $_POST['pms_subscription_id'] ) || empty( $_POST['pms_emails_to_invite'] ) )
    		return;

        if( !pms_get_page( 'register', true ) ){
            pms_errors()->add( 'invite_members', esc_html__( 'Registration page not selected. Contact administrator.', 'paid-member-subscriptions' ) );

            return;
        }

    	$subscription = pms_get_member_subscription( sanitize_text_field( $_POST['pms_subscription_id'] ) );

        if( !pms_gm_is_group_owner( $subscription->id ) )
            return;

    	//try to split the string by comma
    	$emails = explode( ',', $_POST['pms_emails_to_invite'] );

    	//check if the first entry contains the end of line character and if so, split by EOL
    	//having more than 1 entry means that the above split worked
    	if( isset( $emails[0] ) && count( $emails ) == 1 && strstr( $emails[0], PHP_EOL ) )
    		$emails = explode( PHP_EOL, $_POST['pms_emails_to_invite'] );

        $invited_members = 0;
        $invited_emails  = pms_get_member_subscription_meta( $subscription->id, 'pms_gm_invited_emails' );

    	foreach( $emails as $email ){
            $email = str_replace( array( "\r", "\n", "\t"), '', $email );

            if( !$this->members_can_be_invited( $subscription ) )
                return;

            if( in_array( $email, $invited_emails ) )
                continue;

            // check if user already invited or registered with subscription
            $email = sanitize_text_field( $email );

            if( !filter_var( $email, FILTER_VALIDATE_EMAIL ) )
                continue;

            $invited_emails[] = $email;

            // If a user with this email is already registered, add him to the subscription
            $user = get_user_by( 'email', $email );

            if( !empty( $user ) ) {

                $existing_subscription = pms_get_member_subscriptions( array( 'user_id' => $user->ID, 'subscription_plan_id' => $subscription->subscription_plan_id ) );

                if( !empty( $existing_subscription ) )
                    continue;

                $subscription_data = array(
                    'user_id'              => $user->ID,
                    'subscription_plan_id' => $subscription->subscription_plan_id,
                    'start_date'           => $subscription->start_date,
                    'expiration_date'      => $subscription->expiration_date,
                    'status'               => 'active',
                );

                $new_subscription = new PMS_Member_Subscription();
                $new_subscription->insert( $subscription_data );

                pms_add_member_subscription_meta( $new_subscription->id, 'pms_group_subscription_owner', $subscription->id );
                pms_add_member_subscription_meta( $subscription->id, 'pms_group_subscription_member', $new_subscription->id );

                if( function_exists( 'pms_add_member_subscription_log' ) )
                    pms_add_member_subscription_log( $new_subscription->id, 'group_user_subscription_added' );

                $invited_members++;

                continue;
            }

            // Invite user
            //save email as subscription meta
            $meta_id = pms_add_member_subscription_meta( $subscription->id, 'pms_gm_invited_emails', $email );

            //generate and save invite key
            $invite_key = $this->generate_invite_key( $meta_id, $email, $subscription->id );
            
            //send email
            if( $invite_key !== false )
                do_action( 'pms_gm_send_invitation_email', $email, $subscription, $invite_key );
    	}

        $invited_members += (int)did_action( 'pms_gm_send_invitation_email' );

        if( $invited_members >= 1 )
            pms_success()->add( 'invite_members', sprintf( _n( '%d user added successfully!', '%d users added successfully!', $invited_members, 'paid-member-subscriptions' ), $invited_members ) );
        else
            pms_errors()->add( 'invite_members', esc_html__( 'Something went wrong. Please try again.', 'paid-member-subscriptions' ) );

    }

    public function edit_group_details(){

        // Do nothing if we cannot validate the nonce
        if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_gm_edit_group_details_nonce' ) )
            return;

        if( empty( $_POST['pms_subscription_id'] ) )
            return;

        $subscription = pms_get_member_subscription( sanitize_text_field( $_POST['pms_subscription_id'] ) );

        if( !pms_gm_is_group_owner( $subscription->id ) )
            return;

        //validate fields
        $group_name = sanitize_text_field( $_POST['group_name'] );

        if( empty( $group_name ) )
            pms_errors()->add( 'group_name', esc_html__( 'Organization name cannot be empty.', 'paid-member-subscriptions' ) );

        if ( count( pms_errors()->get_error_codes() ) > 0 )
            return;

        //save fields
        pms_update_member_subscription_meta( $subscription->id, 'pms_group_name', $group_name );

        $group_description = sanitize_text_field( $_POST['group_description'] );
        pms_update_member_subscription_meta( $subscription->id, 'pms_group_description', $group_description );

    }

    public function supress_register_plans_and_payment( $out, $pairs, $atts, $shortcode ){

        if( $this->verify_parameters() && $this->verify_invite_key() )
            $out['subscription_plans'] = 'none';

        return $out;

    }

    public function prefill_registration_email( $value ){

        if( !$this->verify_parameters() || !$this->verify_invite_key() )
            return $value;

        return esc_attr( $_GET['email'] );

    }

    public function disable_registration_email( $attributes ){

        if( !$this->verify_parameters() || !$this->verify_invite_key() )
            return $attributes;

        return $attributes . ' readonly';

    }

    public function pb_disable_editing_of_email_field( $attributes, $field, $form_location ){

        if( $form_location != 'register' || $field['field'] != 'Default - E-mail' )
            return $attributes;

        return $this->disable_registration_email( $attributes );

    }

    public function maybe_link_user_with_parent_subscription( $userdata ){

        //user_email => PMS key, email => PB key
        $email = isset( $_POST['user_email'] ) ? $_POST['user_email'] : ( isset( $_POST['email'] ) ? $_POST['email'] : '' );

        if( empty( $email ) )
            return;

        $email = sanitize_text_field( $email );

        if( $this->verify_parameters() && $this->verify_invite_key( $email ) ){

            //Array from the PMS hook and ID from PB
            if( is_array( $userdata ) )
                $user_id = $userdata['user_id'];
            else
                $user_id = $userdata;

            $data = array(
                'user_id'         => $user_id,
                'email'           => $email,
                'subscription_id' => (int)$_GET['subscription_id'],
                'pms_key'             => sanitize_text_field( $_GET['pms_key'] ),
            );

            $this->link_user_with_parent_subscription( $data );

        }
    }

    public function add_invited_user_message(){
        if( !$this->verify_parameters() || !$this->verify_invite_key() )
            return;

        $subscription = pms_get_member_subscription( sanitize_text_field( $_GET['subscription_id'] ) );
        ?>

        <div class="pms-gm-message">
            <?php printf( wp_kses( __( 'You were added to join this website by <strong>%s</strong>.', 'paid-member-subscriptions' ), array( 'strong' => array() ) ), pms_gm_get_user_name( $subscription->user_id ) ); ?>
            <br>
            <?php esc_html_e( 'Please fill the form below in order to complete your registration.', 'paid-member-subscriptions' ); ?>
        </div>

        <?php
    }

    public function add_purchase_message( $output, $include, $exclude_id_group, $member, $pms_settings, $subscription_plans, $form_location ){
        if( !in_array( $form_location, array( 'register', 'new_subscription', 'wppb_register' ) ) )
            return $output;

        ob_start();
        ?>

        <div class="pms-gm-message pms-gm-message__purchase">
            <?php esc_html_e( 'You have selected a Group Membership. After a successful payment you will be able to add up to %s additional users.', 'paid-member-subscriptions' ); ?>
        </div>

        <?php
        $output .= ob_get_clean();

        return $output;
    }

    public function add_custom_fields( $output, $include, $exclude_id_group, $member, $pms_settings, $subscription_plans, $form_location ){
        if( !in_array( $form_location, array( 'register', 'new_subscription', 'wppb_register' ) ) )
            return $output;

        ob_start();
        ?>

        <?php $field_errors = pms_errors()->get_error_messages( 'group_name' ); ?>
        <div class="pms-field pms-group-name-field pms-group-memberships-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_group_name"><?php echo apply_filters( 'pms_register_form_label_group_name', __( 'Group Name *', 'paid-member-subscriptions' ) ); ?></label>
            <input id="pms_group_name" name="group_name" type="text" value="<?php echo ( isset( $_POST['group_name'] ) ? esc_attr( $_POST['group_name'] ) : '' ); ?>" />

            <?php pms_display_field_errors( $field_errors ); ?>
        </div>

        <?php $field_errors = pms_errors()->get_error_messages( 'group_description' ); ?>
        <div class="pms-field pms-group-description-field pms-group-memberships-field <?php echo ( !empty( $field_errors ) ? 'pms-field-error' : '' ); ?>">
            <label for="pms_group_description"><?php echo apply_filters( 'pms_register_form_label_group_name', __( 'Group Description', 'paid-member-subscriptions' ) ); ?></label>
            <textarea id="pms_group_description" name="group_description" rows="2"><?php echo isset( $_POST['group_description'] ) ? esc_textarea( $_POST['group_description'] ) : ''; ?></textarea>

            <?php pms_display_field_errors( $field_errors ); ?>
        </div>

        <?php

        $output .= ob_get_clean();

        return $output;
    }

    public function validate_custom_fields(){
        if( !empty( $_POST['subscription_plans'] ) ){
            $subscription_plan = pms_get_subscription_plan( (int)$_POST['subscription_plans'] );

            if( $subscription_plan->type == 'group' && empty( $_POST['group_name'] ) )
                pms_errors()->add( 'group_name', __( 'Please enter a group name.', 'paid-member-subscriptions' ) );
        }
    }

    public function save_custom_fields( $id, $data ){
        if( empty( $id ) )
            return;

        if( isset( $_POST['group_name'] ) )
            pms_add_member_subscription_meta( $id, 'pms_group_name', sanitize_text_field( $_POST['group_name'] ) );

        if( isset( $_POST['group_description'] ) )
            pms_add_member_subscription_meta( $id, 'pms_group_description', sanitize_text_field( $_POST['group_description'] ) );
    }

    public function add_data_attributes( $data_attributes, $plan_id ){
        $plan = pms_get_subscription_plan( $plan_id );

        if( $plan->type == 'group' )
            $data_attributes['seats'] = get_post_meta( $plan_id, 'pms_subscription_plan_seats', true );

        return $data_attributes;
    }

    public function frontend_seats_display( $output, $subscription_plan ){
        $subscription_type = get_post_meta( $subscription_plan->id, 'pms_subscription_plan_type', true );

        if( $subscription_type == 'group' ){
            $seats = get_post_meta( $subscription_plan->id, 'pms_subscription_plan_seats', true );

            if( $subscription_plan->price == 0 )
                $price_output = '<span class="pms-subscription-plan-price-value">' . __( 'Free', 'paid-member-subscriptions' ) . '</span>';
            else
                $price_output = pms_format_price( $subscription_plan->price, pms_get_active_currency(), array( 'before_price' => '<span class="pms-subscription-plan-price-value">', 'after_price' => '</span>', 'before_currency' => '<span class="pms-subscription-plan-currency">', 'after_currency' => '</span>' ) );

            $output = sprintf( '<span class="pms-divider"> - </span> %s %s', $price_output, sprintf( __( 'for %s users', 'paid-member-subscriptions' ), $seats ) );
        }

        return $output;
    }

    public function replace_subscription_row( $row, $subscription, $subscription_plan ){
        if( $subscription_plan->type != 'group' )
            return $row;

        ob_start();

            include get_stylesheet_directory() . '/pms-add-on-group-memberships/includes/views/view-shortcode-account-subscriptions-row.php';

        $output = ob_get_clean();

        return $output;
    }

    public function filter_action_links( $url, $plan_id ){

        $user_id = get_current_user_id();

        if( empty( $user_id ) )
            return false;

        $member_subscription = pms_get_current_subscription_from_tier( $user_id, (int)$plan_id );

        $plan = pms_get_subscription_plan( $member_subscription->subscription_plan_id );

        if( $plan->type == 'group' && !pms_gm_is_group_owner( $member_subscription->id ) )
            return false;

        return $url;

    }

    public function remove_billing_details( $sections ){
        if( !$this->verify_parameters() || !$this->verify_invite_key() )
            return $sections;

        return array();
    }

    public function remove_child_subscriptions( $owner_id, $subscription_data ){
        if( empty( $owner_id ) )
            return;

        $plan = pms_get_subscription_plan( $subscription_data['subscription_plan_id'] );

        if( $plan->type != 'group' )
            return;

        $group_subscriptions = pms_gm_get_group_subscriptions( $owner_id );

        if( empty( $group_subscriptions ) )
            return;

        foreach( $group_subscriptions as $subscription_id ){
            $member_subscription = pms_get_member_subscription( $subscription_id );
            $member_subscription->remove();
        }
    }

    public function remove_subscription_from_group( $child_id, $subscription_data ){
        if( empty( $child_id ) )
            return;

        $owner_id = pms_get_member_subscription_meta( $child_id, 'pms_group_subscription_owner', true );

        if( empty( $owner_id ) )
            return;

        pms_delete_member_subscription_meta( $owner_id, 'pms_group_subscription_member', $child_id );
    }

    public function expire_child_subscriptions_wrapper( $subscription_id, $new_data, $old_data ){
        if( empty( $new_data['status'] ) || $new_data['status'] != 'expired' )
            return;

        // Do this only when the status changes from active to expired
        if( empty( $old_data['status'] ) || $old_data['status'] != 'active' )
            return;

        $subscription = pms_get_member_subscription( $subscription_id );

        $this->expire_child_subscriptions( $subscription );
    }

    public function expire_child_subscriptions( $subscription ){
        if( !$this->verify_action_params( $subscription ) )
            return;

        $group_subscriptions = pms_gm_get_group_subscriptions( $subscription->id );

        if( empty( $group_subscriptions ) )
            return;

        $data = array( 'status' => 'expired' );

        foreach( $group_subscriptions as $subscription_id ){
            $member_subscription = pms_get_member_subscription( $subscription_id );
            $member_subscription->update( $data );
        }
    }

    public function cancel_child_subscriptions( $member, $subscription ){
        if( !$this->verify_action_params( $subscription ) )
            return;

        $group_subscriptions = pms_gm_get_group_subscriptions( $subscription->id );

        if( empty( $group_subscriptions ) )
            return;

        $data = array(
            'status'          => 'canceled',
            'expiration_date' => $subscription->expiration_date
        );

        foreach( $group_subscriptions as $subscription_id ){
            $member_subscription = pms_get_member_subscription( $subscription_id );
            $member_subscription->update( $data );
        }
    }

    public function renew_child_subscriptions( $subscription ){
        if( !$this->verify_action_params( $subscription ) )
            return;

        $group_subscriptions = pms_gm_get_group_subscriptions( $subscription->id );

        if( empty( $group_subscriptions ) )
            return;

        $data = array(
            'status'          => $subscription->status,
            'expiration_date' => $subscription->expiration_date
        );

        foreach( $group_subscriptions as $subscription_id ){
            $member_subscription = pms_get_member_subscription( $subscription_id );
            $member_subscription->update( $data );
        }
    }

    public function renew_child_subscriptions_check_location( $subscription, $location ){
        if( $location == 'renew_subscription' )
            $this->renew_child_subscriptions( $subscription );
    }

    public function renew_child_subscriptions_admin( $subscription_id, $new_data, $old_data ){

        if( !empty( $new_data['status'] ) && $new_data['status'] != $old_data['status'] && $new_data['status'] == 'active' ){

            if( current_user_can( 'manage_options' ) || current_user_can( 'pms_edit_capability' ) ){

                if( ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'edit_subscription' ) || ( isset( $_GET['page'] ) && $_GET['page'] == 'pms-payments-page' ) )
                    $this->renew_child_subscriptions( pms_get_member_subscription( $subscription_id ) );

            }

        }

    }

    public function upgrade_child_subscriptions( $subscription ){
        if( !$this->verify_action_params( $subscription ) )
            return;

        $group_subscriptions = pms_gm_get_group_subscriptions( $subscription->id );

        if( empty( $group_subscriptions ) )
            return;

        $data = array(
            'subscription_plan_id' => $subscription->subscription_plan_id,
            'status'               => $subscription->status,
            'start_date'           => $subscription->start_date,
            'expiration_date'      => $subscription->expiration_date,
        );

        foreach( $group_subscriptions as $subscription_id ){
            $member_subscription = pms_get_member_subscription( $subscription_id );
            $member_subscription->update( $data );
        }
    }

    public function upgrade_child_subscriptions_check_location( $subscription, $location ){
        if( $location == 'upgrade_subscription' )
            $this->upgrade_child_subscriptions( $subscription );
    }

    public function plugin_scheduled_payments_failures( $subscription, $payment ){
        if( empty( $subscription->id ) )
            return;

        if( !empty( $payment->id ) && $payment->status != 'completed' )
            $this->expire_child_subscriptions( array(), $subscription );
    }

    public function remove_group_membership_member(){
        check_ajax_referer( 'pms_group_subscription_member_remove', 'security' );

        $reference          = sanitize_text_field( $_POST['reference'] );
        $subscription_id    = sanitize_text_field( $_POST['subscription_id'] );
        $owner_subscription = pms_get_member_subscription( (int)$subscription_id );

        if( !current_user_can( 'manage_options' ) ) {
            if( $owner_subscription->user_id != pms_get_current_user_id() )
                $this->ajax_response( 'error', __( 'You are not allowed to do this.', 'paid-member-subscriptions' ) );
        }

        //remove existing member
        if( is_numeric( $reference ) ){

            // remove member subscription
            $member_subscription = pms_get_member_subscription( (int)$reference );

            if( isset( $member_subscription ) ) {
                $member_subscription->remove();

                pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_group_subscription_member', (int)$reference );

                $this->ajax_response( 'success', __( 'User removed successfully!', 'paid-member-subscriptions' ) );

            }

        //remove invitation
        } else {

            $meta_id = pms_gm_get_meta_id_by_value( $owner_subscription->id, $reference );

            pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails_' . $meta_id );
            pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails', $reference );

            $this->ajax_response( 'success', __( 'User Addition removed succesfully!', 'paid-member-subscriptions' ) );

        }

        $this->ajax_response( 'error', __( 'Something went wrong, please try again.', 'paid-member-subscriptions' ) );
    }

    public function resend_invitation(){
        check_ajax_referer( 'pms_group_subscription_resend_invitation', 'security' );

        $reference          = sanitize_text_field( $_POST['reference'] );
        $subscription_id    = sanitize_text_field( $_POST['subscription_id'] );
        $owner_subscription = pms_get_member_subscription( (int)$subscription_id );

        if( !current_user_can( 'manage_options' ) ) {
            if( $owner_subscription->user_id != pms_get_current_user_id() )
                $this->ajax_response( 'error', __( 'You are not allowed to do this.', 'paid-member-subscriptions' ) );
        }

        $meta_id = pms_gm_get_meta_id_by_value( $owner_subscription->id, $reference );

        if( !empty( $meta_id ) ){
            $key = pms_get_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails_' . $meta_id, true );

            do_action( 'pms_gm_send_invitation_email', $reference, $owner_subscription, $key );

            $this->ajax_response( 'success', __( 'Invitation sent successfully!', 'paid-member-subscriptions' ) );
        }

        $this->ajax_response( 'error', __( 'Something went wrong, please try again.', 'paid-member-subscriptions' ) );
    }

    public function pb_remove_subscription_plans(){
        if( !$this->verify_parameters() )
            return;

        remove_filter( 'wppb_output_form_field_subscription-plans', 'pms_pb_subscription_plans_handler', 10 );
        remove_filter( 'wppb_save_form_field',                      'pms_pb_save_subscription_plans_value', 10 );
    }

    public function pb_remove_subscription_plans_validation( $fields ){
        if( !$this->verify_parameters() )
            return $fields;

        foreach( $fields as $key => $field ){
            if( $field['field'] == 'Subscription Plans' ){
                unset( $fields[$key] );
                break;
            }
        }

        return $fields;
    }

    public function pb_remove_payment_gateways( $fields ){
        if( !$this->verify_parameters() )
            return $fields;

        remove_filter( 'wppb_output_after_last_form_field', 'pms_pb_output_payment_gateways', 99 );

        return $fields;
    }

    public function pb_add_to_signup_meta( $meta ){
        if( isset( $_GET['subscription_id'] ) )
            $meta['subscription_id'] = intval( $_GET['subscription_id'] );

        if( isset( $_GET['pms_key'] ) )
            $meta['pms_key'] = sanitize_text_field( $_GET['pms_key'] );

        return $meta;
    }

    public function pb_maybe_link_user_with_parent_subscription( $user_id, $password, $meta ){
        if( empty( $meta['subscription_id'] ) && empty( $meta['pms_key'] ) )
            return;

        $user = get_userdata( $user_id );

        if( !$this->verify_invite_key( $user->user_email, $meta['subscription_id'], $meta['pms_key'] ) )
            return;

        $data = array(
            'user_id'         => $user_id,
            'email'           => $user->user_email,
            'subscription_id' => $meta['subscription_id'],
            'pms_key'         => $meta['pms_key'],
        );

        $this->link_user_with_parent_subscription( $data );

    }

    //Utils

    /**
     * Expects an array with the following keys: user_id, email, subscription_id, pms_key
     * Assigns the user to the given subscription_id group membership

     * @param  array $data
     * @return void
     */
    private function link_user_with_parent_subscription( $data ){
        $owner_subscription = pms_get_member_subscription( $data['subscription_id'] );

        $subscription_data = array(
            'user_id'              => $data['user_id'],
            'subscription_plan_id' => $owner_subscription->subscription_plan_id,
            'start_date'           => $owner_subscription->start_date,
            'expiration_date'      => $owner_subscription->expiration_date,
            'status'               => 'active',
        );

        $subscription = new PMS_Member_Subscription();
        $subscription->insert( $subscription_data );

        pms_add_member_subscription_meta( $subscription->id, 'pms_group_subscription_owner', $owner_subscription->id );
        pms_add_member_subscription_meta( $owner_subscription->id, 'pms_group_subscription_member', $subscription->id );

        $meta_id = pms_gm_get_meta_id_by_value( $owner_subscription->id, $data['email'] );

        pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails_' . $meta_id, $data['pms_key'] );
        pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails', $data['email'] );

        if( function_exists( 'pms_add_member_subscription_log' ) )
            pms_add_member_subscription_log( $subscription->id, 'group_user_accepted_invite' );
    }

    // Retrieve an array with invited users
    public function get_invited_users( $subscription_id ){
        return pms_get_member_subscription_meta( $subscription_id, 'pms_gm_invited_emails' );
    }

    // Verifies that the Email, Subscription and Key combination is valid
    public function verify_invite_key( $email = '', $subscription_id = '', $key = '' ){
        if( empty( $email ) )
            $email = sanitize_text_field( $_GET['email'] );

        if( empty( $subscription_id ) )
            $subscription_id = sanitize_text_field( $_GET['subscription_id'] );

        if( empty( $key ) )
            $key = sanitize_text_field( $_GET['pms_key'] );

        $meta_id = pms_gm_get_meta_id_by_value( $subscription_id, $email );

        if( empty( $meta_id ) )
            return false;

        $stored_key = pms_get_member_subscription_meta( $subscription_id, 'pms_gm_invited_emails_' . $meta_id, true );

        if( md5( $stored_key ) === md5( $key ) )
            return true;

        return false;
    }

    // Generates an invite key and saves it to the subscription
    private function generate_invite_key( $meta_id, $email, $subscription_id ){
        if( empty( $meta_id ) || empty( $email ) || empty( $subscription_id ) )
            return false;

        $data = $subscription_id . $email . get_site_url() . time();
        $key  = hash_hmac( 'sha256' , $data, $email . time() );

        if( pms_add_member_subscription_meta( $subscription_id, 'pms_gm_invited_emails_' . $meta_id, $key ) )
            return $key;

        return false;
    }

    // Verifies GET parameters for certain requests
    private function verify_parameters(){
        if( empty( $_GET['email'] ) || empty( $_GET['pms_key'] ) || empty( $_GET['subscription_id'] ) )
            return false;

        return true;
    }

    // Verifies the validity of a subscription plan
    private function verify_action_params( $subscription ){
        if( empty( $subscription->id ) )
            return false;

        $plan = pms_get_subscription_plan( $subscription->subscription_plan_id );

        if( $plan->type != 'group' )
            return false;

        return true;
    }

    // Checks if the website has any group memberships defined
    public function is_group_plan_defined(){
        foreach( pms_get_subscription_plans( true ) as $plan ) {
            if( $plan->type == 'group' )
                return true;
        }

        return false;
    }

    // Verifies if more members can be invited to the given subscription
    public function members_can_be_invited( $subscription ){
        return pms_gm_get_used_seats( $subscription->id ) >= pms_gm_get_total_seats( $subscription ) ? false : true;
    }

    // Generates front-end Members List actions
    public function get_members_row_actions( $reference, $subscription_id ){
        if( !is_numeric( $reference) || !pms_gm_is_group_owner( $reference ) )
            $actions = '<a class="pms-remove-reload" data-reference="'.$reference.'" data-subscription="'.$subscription_id.'" href="#"><nobr>'. esc_html__( 'Remove', 'paid-member-subscriptions' ) .'</nobr></a>';
        else
            $actions = '';

        if( !is_numeric( $reference ) )
            $actions .= '<a class="pms-resend" data-reference="'.$reference.'" data-subscription="'.$subscription_id.'" href="#"><nobr>'. esc_html__( 'Resend Add', 'paid-member-subscriptions' ) .'</nobr></a>';

        return $actions;
    }

    // Helper function to format ajax responses
    public function ajax_response( $type, $message ){
        echo json_encode( array( 'status' => $type, 'message' => $message ) );
        die();
    }

    public function is_email_confirmation_active(){
        $settings = get_option( 'wppb_general_settings', array() );

        return isset( $settings['emailConfirmation'] ) && $settings['emailConfirmation'] == 'yes' ? true : false;
    }
}

$pms_gm = new PMS_GM; //pms group memberships

