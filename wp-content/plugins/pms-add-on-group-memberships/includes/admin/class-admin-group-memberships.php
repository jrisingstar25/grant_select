<?php

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

// Return if PMS is not active
if( ! defined( 'PMS_VERSION' ) ) return;

Class PMS_Admin_Group_Memberships {

	public function __construct(){

		// Figure out how to add the Group subscription type to the Subscription Plans interface
		add_action( 'admin_init',                                 array( $this, 'hook_subscription_plan_type_change' ) );

		// Add the admin field to specify the available seats
		add_action( 'pms_view_meta_box_subscription_details_top', array( $this, 'add_subscription_plan_seats_field' ) );

		// Save the extra fields from the Subscription Plan Details meta-box on post save
		add_action( 'pms_save_meta_box_pms-subscription',         array( $this, 'save_subscription_plan_settings_fields' ) );

		// Allow admins to define new subscription plan tiers
		add_filter( 'pms_action_add_new_subscription_plan',       array( $this, 'members_list_allow_add_new_action' ) );

		// Display custom 'group' column in the Members List
		add_filter( 'pms_members_list_table_columns',             array( $this, 'members_list_add_group_column' ) );

		// Populate the group column
		add_filter( 'pms_members_list_table_entry_data',          array( $this, 'members_list_add_group_column_data' ), 20, 2 );

		// Add Filter by Group select
		add_action( 'pms_members_list_extra_table_nav',           array( $this, 'members_list_add_group_filter' ) );

		// Filter Members by Group
		add_filter( 'pms_get_members_args',                       array( $this, 'members_list_filter_members_by_group' ) );

		// Remove Views count when filtering by Groups
		add_filter( 'pms_members_list_table_get_views',           array( $this, 'members_list_remove_views' ) );

		// Add `Edit Owner` custom row action
		add_filter( 'pms_members_list_username_actions',          array( $this, 'members_list_add_edit_owner_action' ), 20, 2 );

		// Filter page output to add the Group Details and Edit Page
		add_filter( 'pms_submenu_page_members_output',            array( $this, 'members_list_output' ) );

		// Edit Group Details
		add_action( 'wp_ajax_pms_edit_group_details',             array( $this, 'members_list_edit_group_details' ) );

		// Remove Members functionality
		add_action( 'admin_init',                                 array( $this, 'members_list_remove_members' ) );

		// Errors when saving subscription plans
		add_action( 'pre_post_update', 				  			  array( $this, 'validate_group_subscription_plan_save' ), 20, 3 );
		add_action( 'pms_cpt_admin_notice_messages', 			  array( $this, 'valididate_group_subscription_plan_messages' ) );

	}

	public function hook_subscription_plan_type_change(){

	    /**
	     * If the Fixed Period Membership add-on is active, hook into its subscription types, else add our own Select field
	     */
	    if( function_exists( 'pms_msfp_add_subscription_plan_settings_fields' ) )
	    	add_filter( 'pms_subscription_plan_types', array( $this, 'add_subscription_plan_type_filter' ) );
	    else
	    	add_action( 'pms_view_meta_box_subscription_details_top', array( $this, 'add_subscription_plan_type' ) );

	}

	// If not already present, generate the Subscription Plan Type Select field
	public function add_subscription_plan_type( $subscription_plan_id ){

		$subscription_type = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_type', true );

		$types = array(
			'regular' => esc_html__( 'Regular', 'paid-member-subscriptions' ),
			'group'   => esc_html__( 'Group', 'paid-member-subscriptions' )
		);

		$types = apply_filters( 'pms_subscription_plan_types', $types );
		?>

		<!-- Subscription Plan Type -->
		<div class="pms-meta-box-field-wrapper">
		    <label for="pms-subscription-plan-type" class="pms-meta-box-field-label">
				<?php esc_html_e( 'Subscription Type', 'paid-member-subscriptions' ); ?>
			</label>

		    <select id="pms-subscription-plan-type" name="pms_subscription_plan_type">

				<?php foreach( $types as $slug => $label ) : ?>
					<option value="<?php echo $slug; ?>" <?php selected( $subscription_type, $slug ); ?>><?php echo $label; ?></option>
				<?php endforeach; ?>

		    </select>
		    <p class="description"><?php esc_html_e( 'Please select the duration type for this subscription plan.', 'paid-member-subscriptions' ); ?></p>
		</div>

		<?php

	}

	// Add the necessary type to the existing select
	public function add_subscription_plan_type_filter( $types ){

		$types['group'] = esc_html__( 'Group', 'paid-member-subscriptions' );

		return $types;

	}

	public function add_subscription_plan_seats_field( $subscription_plan_id ){

		$seats = get_post_meta( $subscription_plan_id, 'pms_subscription_plan_seats', true );

		// calculate min
		$min_seats = 2;

		if( isset( $_GET['pms-action'], $_GET['plan_id'] ) && $_GET['pms-action'] == 'add_upgrade' ){

			$min_seats = get_post_meta( $_GET['plan_id'], 'pms_subscription_plan_seats', true );

		} elseif ( isset( $_GET['action'], $_GET['post'] ) && $_GET['action'] == 'edit' ){

			$group = pms_get_subscription_plans_group( (int) $_GET['post'] );

			if( !empty( $group ) && count( $group ) > 1 ){

				$downgrade_key = '';

				foreach( $group as $key => $plan ){
					if( $plan->id == $_GET['post'] )
						$downgrade_key = $key + 1;
				}

				if( $downgrade_key != count( $group ) )
					$min_seats = get_post_meta( $group[$downgrade_key]->id, 'pms_subscription_plan_seats', true );

			}

		}
		?>

		<div class="pms-meta-box-field-wrapper">

			<label for="pms-subscription-plan-seats" class="pms-meta-box-field-label"><?php esc_html_e( 'Seats', 'paid-member-subscriptions' ); ?></label>

			<input type="number" name="pms_subscription_plan_seats" id="pms-subscription-plan-seats" min="<?php echo !empty( $min_seats ) ? $min_seats : 2; ?>" value="<?php echo $seats; ?>" />

			<p class="description"><?php esc_html_e( 'The number of additional members, including the owner, that can be added to the subscription.', 'paid-member-subscriptions' ); ?></p>

		</div>
	<?php
	}

	public function save_subscription_plan_settings_fields( $subscription_plan_id ){

		if( empty( $_POST['post_ID'] ) || $subscription_plan_id != $_POST['post_ID'] )
	        return;

	    if( isset( $_POST['pms_subscription_plan_type'] ) )
	        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_type', sanitize_text_field( $_POST['pms_subscription_plan_type'] ) );

	    if( isset( $_POST['pms_subscription_plan_seats'] ) )
	        update_post_meta( $subscription_plan_id, 'pms_subscription_plan_seats', sanitize_text_field( $_POST['pms_subscription_plan_seats'] ) );

	}

	public function members_list_add_group_column( $columns ){
		$subscriptions = $columns['subscriptions'];

		unset( $columns['subscriptions'] );

		$columns['group']         = __( 'Group', 'paid-member-subscriptions' );
		$columns['subscriptions'] = $subscriptions;

		return $columns;
	}

	public function members_list_add_group_column_data( $data, $member ){

		if( empty( $member->subscriptions ) )
			return $data;

		//determine id
		$subscription_id = '';
		foreach( $member->subscriptions as $subscription ){
			$plan = pms_get_subscription_plan( $subscription['subscription_plan_id'] );

			if( $plan->type != 'group' )
				continue;

			$subscription_id = $subscription['id'];
			break;
		}

		if( empty( $subscription_id ) )
			return $data;

		if( pms_gm_is_group_owner( $subscription_id ) )
			$owner_subscription_id = $subscription_id;
		else
			$owner_subscription_id = pms_get_member_subscription_meta( $subscription_id, 'pms_group_subscription_owner', true );

		if( empty( $owner_subscription_id ) )
			return $data;

		$group_name = pms_gm_get_group_name( $subscription_id );

		if( empty( $group_name ) )
			$group_name = 'Undefined';

		$data['group'] = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'subpage' => 'group_details', 'group_owner' => $owner_subscription_id ) ), $group_name );

		return $data;

	}

	public function members_list_allow_add_new_action( $action ){
	    return 'allow';
	}

	public function members_list_add_group_filter(){

		$groups = $this->get_all_active_group_names();

		if( empty( $groups ) )
			return;

		echo '<div class="pms-members-filter">';
            echo '<select name="pms-filter-group" id="pms-filter-group">';
                echo '<option value="">' . __( 'Group...', 'paid-member-subscriptions' ) . '</option>';

                foreach( $groups as $group )
                    echo '<option value="' . $group['member_subscription_id'] . '" ' . ( !empty( $_GET['pms-filter-group'] ) ? selected( $group['member_subscription_id'], $_GET['pms-filter-group'], false ) : '' ) . '>' . $group['meta_value'] . '</option>';
            echo '</select>';
		echo '</div>';

	}

	public function members_list_filter_members_by_group( $args ){

		if( !is_admin() || !isset( $_GET['pms-filter-group'] ) || empty( $_GET['pms-filter-group'] ) )
			return $args;

		$args['group_owner'] = sanitize_text_field( $_GET['pms-filter-group'] );

		return $args;

	}

	public function members_list_remove_views( $views ){
		if( !empty( $_GET['pms-filter-group'] ) )
			return array();

		return $views;
	}

	public function members_list_add_edit_owner_action( $actions, $item ){
		if( empty( $item['subscriptions'][0] ) )
			return $actions;

		$plan = pms_get_subscription_plan( $item['subscriptions'][0]->subscription_plan_id );

		if( $plan->type != 'group' )
			return $actions;

		$owner_subscription_id = pms_get_member_subscription_meta( $item['subscriptions'][0]->id, 'pms_group_subscription_owner', true );

		if( empty( $owner_subscription_id ) )
			return $actions;

		$owner_subscription = pms_get_member_subscription( $owner_subscription_id );

		if( empty( $owner_subscription ) )
			return $actions;

		$actions['group_owner_edit'] = '<a href="' . add_query_arg( array( 'subpage' => 'edit_member', 'member_id' => $owner_subscription->user_id ) ) . '">' . __( 'Edit Owner', 'paid-member-subscriptions' ) . '</a>';

		return $actions;
	}

	public function members_list_output( $content ){

		if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'group_details' && !empty( $_GET['group_owner'] ) )
			include_once 'views/view-page-members-group-details.php';
		else
			return $content;

	}

	public function members_list_edit_group_details(){
		check_ajax_referer( 'pms_gm_admin_edit_group_details_nonce', 'security' );

		if( empty( $_POST['owner_id'] ) )
			$this->ajax_response( 'error', esc_html__( 'Something went wrong.', 'paid-member-subscriptions' ) );

		$group_name      = sanitize_text_field( $_POST['group_name'] );
		$group_seats     = (int)$_POST['seats'];
		$subscription_id = (int)$_POST['owner_id'];

		$subscription = pms_get_member_subscription( $subscription_id );

		//Validate
		if( empty( $subscription->id ) )
			pms_errors()->add( 'subscription', esc_html__( 'Invalid subscriptions.', 'paid-member-subscriptions' ) );

		if( empty( $group_name ) )
			pms_errors()->add( 'group_name', esc_html__( 'Group name cannot be empty.', 'paid-member-subscriptions' ) );

		if( empty( $group_seats ) )
			pms_errors()->add( 'group_seats', esc_html__( 'Group seats cannot be empty.', 'paid-member-subscriptions' ) );

		if( !is_numeric( $group_seats ) )
			pms_errors()->add( 'group_seats', esc_html__( 'Group seats needs to be a number', 'paid-member-subscriptions' ) );

		if( $group_seats < pms_gm_get_used_seats( $subscription_id ) )
			pms_errors()->add( 'group_seats', esc_html__( 'Available seats needs to be equal or bigger than used seats.', 'paid-member-subscriptions' ) );

		if ( count( pms_errors()->get_error_codes() ) > 0 ){
			$errors = pms_errors()->get_error_messages();
			$this->ajax_response( 'error', $errors[0] );
		}

		pms_update_member_subscription_meta( $subscription->id, 'pms_group_name', $group_name );

		pms_update_member_subscription_meta( $subscription->id, 'pms_group_seats', $group_seats );

		if( !empty( $_POST['group_description'] ) )
			pms_update_member_subscription_meta( $subscription->id, 'pms_group_description', sanitize_text_field( $_POST['group_description'] ) );

		$this->ajax_response( 'success', esc_html__( 'Group subscription details edited successfully !', 'paid-member-subscriptions' ) );
	}

	public function members_list_remove_members(){

        if( ! current_user_can( 'manage_options' ) )
            return;

		if( !isset( $_REQUEST['pmstkn'] ) || !wp_verify_nonce( $_REQUEST['pmstkn'], 'pms_remove_members_form_nonce' ) )
			return;

		if( empty( $_POST['pms_reference'] ) || empty( $_POST['pms_subscription_id'] ) )
			return;

		$reference          = sanitize_text_field( $_POST['pms_reference'] );
		$subscription_id    = sanitize_text_field( $_POST['pms_subscription_id'] );
		$owner_subscription = pms_get_member_subscription( (int)$subscription_id );

		$user = get_user_by( 'email', $reference );

		if( !empty( $user->ID ) ){
			$member_subscription = pms_get_member_subscriptions( array( 'user_id' => $user->ID ) );

			if( empty( $member_subscription[0] ) )
				return;

			$member_subscription_id = $member_subscription[0]->id;
			$member_subscription[0]->remove();

            if( pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_group_subscription_member', (int)$member_subscription_id ) )
                pms_success()->add( 'remove_member', esc_html__( 'Member removed successfully !', 'paid-member-subscriptions' ) );

		} else {
			$meta_id = pms_gm_get_meta_id_by_value( $owner_subscription->id, $reference );

            pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails_' . $meta_id );

            if( pms_delete_member_subscription_meta( $owner_subscription->id, 'pms_gm_invited_emails', $reference ) )
				pms_success()->add( 'remove_member', esc_html__( 'Member removed successfully !', 'paid-member-subscriptions' ) );
		}

	}

	public function validate_group_subscription_plan_save( $post_id, $data ){

		if( get_post_type( $post_id ) != 'pms-subscription' )
			return;

		$error = $this->get_group_subscription_plan_error();

		if ( $error !== false ) {

			if( isset( $_GET['pms-action'] ) && ( $_GET['pms-action'] == 'move_up_subscription_plan' || $_GET['pms-action'] == 'move_down_subscription_plan' ) )
				$redirect = admin_url( 'edit.php?post_type=pms-subscription' );
			else
				$redirect = get_edit_post_link( $post_id, 'redirect' );

			wp_safe_redirect( add_query_arg( 'pms-subscription-error', $error, $redirect ) );
			exit;
		}

	}

	public function valididate_group_subscription_plan_messages( $messages ){

		$messages = array(
			0 => __( 'Group subscriptions can only be added as upgrades to regular plans.', 'paid-member-subscriptions' ),
			1 => __( 'Regular plans cannot be added as upgrades to Group subscription plans.', 'paid-member-subscriptions' ),
			2 => __( 'You need to define the number of seats for this Group Subscription.', 'paid-member-subscriptions' ),
			3 => __( 'Group subscriptions cannot be downgrades to regular plans.', 'paid-member-subscriptions' )
		);

		return $messages;

	}

	private function get_group_subscription_plan_error(){

		$error = false;

		if( isset( $_POST['pms_subscription_plan_type'] ) && $_POST['pms_subscription_plan_type'] == 'group' ){

			if( !isset( $_POST['pms_subscription_plan_seats'] ) || empty( $_POST['pms_subscription_plan_seats'] ) )
				$error = 2;

			// Check that Group Subscriptions are added as upgrades to other subs
			if( isset( $_POST['ID'] ) ){

				$upgrades = pms_get_subscription_plan_upgrades( (int)$_POST['ID'] );

				if( !empty( $upgrades ) ){
					foreach( $upgrades as $upgrade ){

						if( $upgrade->id != $_POST['ID'] && $upgrade->type == 'regular' ){
							$error = 0;
							break;
						}

					}
				}

			}

		}

		// When adding regular plans as upgrades we need to check that downgrades dont have a group subscription
		if( isset( $_POST['pms_subscription_plan_type'] ) && ( ( isset( $_POST['pms-action'] ) && $_POST['pms-action'] == 'add_upgrade' ) || ( isset( $_POST['action'] ) && $_POST['action'] == 'editpost' ) ) && $_POST['pms_subscription_plan_type'] == 'regular' ){

			$parent_id = isset( $_POST['pms-subscription-plan-id'] ) ? (int)$_POST['pms-subscription-plan-id'] : (int)$_POST['ID'];

			$plans = pms_get_subscription_plans_group( $parent_id );

			// Find key of plan we add the upgrade to
			$upgrade_key = null;

			foreach( $plans as $key => $plan ){
				if( $plan->id == $parent_id ){
					$upgrade_key = $key;
					break;
				}
			}

			if( $upgrade_key !== null ){

				$downgrades = array_slice( $plans, $upgrade_key );

				foreach( $downgrades as $downgrade ){
					if( $downgrade->id != $_POST['ID'] && $downgrade->type == 'group' ){
						$error = 1;
						break;
					}
				}

			}

		}

		// Check when moving a subscription plan up
		if( isset( $_GET['post_id'], $_GET['pms-action'] ) && $_GET['pms-action'] == 'move_up_subscription_plan' ){

			$current_plan = pms_get_subscription_plan( (int)$_GET['post_id'] );

			if( isset( $current_plan->id ) && $current_plan->type == 'regular' ){

				$current_post = get_post( $current_plan->id );

				if( $current_post->post_parent != 0 ){

					$parent_plan = pms_get_subscription_plan( $current_post->post_parent );

					if( isset( $parent_plan->id ) && $parent_plan->type == 'group' )
						$error = 1;

				}

			}
		}

		if( isset( $_GET['post_id'], $_GET['pms-action'] ) && $_GET['pms-action'] == 'move_down_subscription_plan' ){

			$current_plan = pms_get_subscription_plan( (int)$_GET['post_id'] );

			if( isset( $current_plan->id ) && $current_plan->type == 'group' ){

				$children_posts = get_posts( array( 'post_type' => 'pms-subscription', 'post_status' => 'any', 'numberposts' => 1, 'post_parent' => $current_plan->id ) );

				if( !empty( $children_posts ) ){

					$child_plan = pms_get_subscription_plan( $children_posts[0]->ID );

					if( isset( $child_plan->id ) && $child_plan->type == 'regular' )
						$error = 3;

				}

			}
		}

		return $error;

	}

	private function ajax_response( $type, $message ){
		echo json_encode( array( 'status' => $type, 'message' => $message ) );
		die();
	}

	private function get_all_active_group_names(){

		global $wpdb;

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT subscription_meta.meta_id, subscription_meta.member_subscription_id, subscription_meta.meta_value FROM {$wpdb->prefix}pms_member_subscriptionmeta as subscription_meta INNER JOIN {$wpdb->prefix}pms_member_subscriptions as subscriptions ON subscription_meta.member_subscription_id = subscriptions.id WHERE subscription_meta.meta_key = %s AND subscription_meta.meta_value != '' AND subscriptions.status != 'abandoned'", 'pms_group_name' ), 'ARRAY_A' );

		if( !empty( $result ) )
			return $result;

		return false;

	}

}

$pms_group_memberships_admin = new PMS_Admin_Group_Memberships;
