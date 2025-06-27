<?php

// Child theme (Do not remove!).
define( 'CHILD_THEME_NAME', 'GrantSelect' );
define( 'CHILD_THEME_URL', 'https://www.magimpact.com/' );
define( 'CHILD_THEME_VERSION', '1.5.4' );
define( 'MAI_THEME_SP', true );

// Support the Mai Theme Engine (Do not remove!).
add_theme_support( 'mai-theme-engine' );

/**
 * Mai Theme dependencies (Do not remove!).
 * This auto-installs Mai Theme Engine plugin,
 * which is required for the theme to function properly.
 *
 * composer require afragen/wp-dependency-installer
 */
include_once( __DIR__ . '/vendor/autoload.php' );
add_filter( 'pand_theme_loader', '__return_true' );
WP_Dependency_Installer::instance()->run( __DIR__ );

// Don't do anything else if the Mai Theme Engine plugin is not active.
if ( ! class_exists( 'Mai_Theme_Engine' ) ) {
	return;
}

// Include all php files in the /includes/ directory.
foreach ( glob( dirname( __FILE__ ) . '/includes/*.php' ) as $file ) { include $file; }


/**********************************
 * Add your customizations below! *
 **********************************/


// Enqueue CSS files.
add_action( 'wp_enqueue_scripts', 'maitheme_enqueue_fonts' );
function maitheme_enqueue_fonts() {
	wp_enqueue_style( 'maitheme-google-fonts', '//fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,700;0,800;1,400;1,700;1,800&family=Source+Sans+3:ital,wght@0,300;0,400;0,600;0,700;0,900;1,300;1,400;1,600;1,700;1,900&display=swap', array(), CHILD_THEME_VERSION );
}


add_action( 'wp_enqueue_scripts', 'custom_load_font_awesome' );
function custom_load_font_awesome() {
    wp_enqueue_style( 'font-awesome-free', '//use.fontawesome.com/releases/v5.8.2/css/all.css' );
}

//add_action( 'wp_enqueue_scripts', 'load_dashicons_front_end' );
//function load_dashicons_front_end() {
//  wp_enqueue_style( 'dashicons' );
//}


//* Add Dashicon to search form button
//add_filter( 'genesis_search_button_text', 'wpsites_search_button_icon' );
//function wpsites_search_button_icon( $text ) {
//	return esc_attr( '&#xf179;' );
//}


// Customize the site footer text.
add_filter( 'genesis_footer_creds_text', 'maitheme_site_footer_text' );
function maitheme_site_footer_text( $text ) {
	$url  = 'https://maitheme.com/';
	$name = 'Mai Theme';
	return sprintf( 'Copyright &copy; %s <a href="%s" title="%s">%s</a> &middot; All Rights Reserved &middot; Powered by <a rel="nofollow noopener" href="%s">%s</a>',
		date('Y'),
		get_bloginfo('url'),
		get_bloginfo('name'),
		get_bloginfo('name'),
		$url,
		$name
	);
}

//* Custom spinner for Gravity Forms
add_filter("gform_ajax_spinner_url", "spinner_url", 10, 2);
function spinner_url($image_src, $form) {
    return "/wp-content/uploads/2021/03/Spinner-32px.gif";
}

function pms_adding_scripts() { ?>
	<script>
		var ajaxurl = '<?php echo admin_url('admin-ajax.php') ?>';
	</script>
<?php

	wp_enqueue_script('pms_list_script', site_url() . '/wp-content/themes/grantselect/assets/js/list.js', array('jquery'), CHILD_THEME_VERSION, true);
	wp_enqueue_script('pms_script', site_url() . '/wp-content/themes/grantselect/assets/js/customer.js', array('jquery'), CHILD_THEME_VERSION, true);
	
} 
add_action( 'wp_enqueue_scripts', 'pms_adding_scripts', 10 ); 

if( ! function_exists( 'remove_class_filter' ) ){
	/**
	* @param string $tag         Filter to remove
	* @param string $class_name  Class name for the filter's callback
	* @param string $method_name Method name for the filter's callback
	* @param int    $priority    Priority of the filter (default 10)
	*
	* @return bool Whether the function is removed.
	*/
	function remove_class_filter( $tag, $class_name = '', $method_name = '', $priority = 10 ) {
		   global $wp_filter;
		   // Check that filter actually exists first
		   if ( ! isset( $wp_filter[ $tag ] ) ) {
			   return FALSE;
		   }
		   if ( is_object( $wp_filter[ $tag ] ) && isset( $wp_filter[ $tag ]->callbacks ) ) {
			   // Create $fob object from filter tag, to use below
			   $fob       = $wp_filter[ $tag ];
			   $callbacks = &$wp_filter[ $tag ]->callbacks;
		   } else {
			   $callbacks = &$wp_filter[ $tag ];
		   }
		   // Exit if there aren't any callbacks for specified priority
		   if ( ! isset( $callbacks[ $priority ] ) || empty( $callbacks[ $priority ] ) ) {
			   return FALSE;
		   }
		   // Loop through each filter for the specified priority, looking for our class & method
		   foreach ( (array) $callbacks[ $priority ] as $filter_id => $filter ) {
			   // Filter should always be an array - array( $this, 'method' ), if not goto next
			   if ( ! isset( $filter['function'] ) || ! is_array( $filter['function'] ) ) {
				   continue;
			   }
			   // If first value in array is not an object, it can't be a class
			   if ( ! is_object( $filter['function'][0] ) ) {
				   continue;
			   }
			   // Method doesn't match the one we're looking for, goto next
			   if ( $filter['function'][1] !== $method_name ) {
				   continue;
			   }
			   // Method matched, now let's check the Class
			   if ( get_class( $filter['function'][0] ) === $class_name ) {
				   // WordPress 4.7+ use core remove_filter() since we found the class object
				   if ( isset( $fob ) ) {
					   // Handles removing filter, reseting callback priority keys mid-iteration, etc.
					   $fob->remove_filter( $tag, $filter['function'], $priority );
				   } else {
					   // Use legacy removal process (pre 4.7)
					   unset( $callbacks[ $priority ][ $filter_id ] );
					   // and if it was the only filter in that priority, unset that priority
					   if ( empty( $callbacks[ $priority ] ) ) {
						   unset( $callbacks[ $priority ] );
					   }
					   // and if the only filter for that tag, set the tag to an empty array
					   if ( empty( $callbacks ) ) {
						   $callbacks = array();
					   }
					   // Remove this filter from merged_filters, which specifies if filters have been sorted
					   unset( $GLOBALS['merged_filters'][ $tag ] );
				   }
				   return TRUE;
			   }
		   }
		   return FALSE;
	}
   }
//Customize PMS_Shortcodes and PMS_Group_Memberships
include_once( get_stylesheet_directory() . '/paid-member-subscriptions/includes/class-shortcodes.php' );
remove_action( 'init', array( 'PMS_Shortcodes', 'init' ) );
add_action( 'init', array( 'PMSWMG_Shortcodes', 'init' ) );
//remove old Group Membership dashboard
remove_class_filter('pms_account_shortcode_content', 'PMS_Group_Memberships', 'dashboard', 11);
add_filter("pms_register_form_label_group_name", "pms_register_form_label_group_name_for_org");
function pms_register_form_label_group_name_for_org($str){
	
	switch ($str){
		case __( 'Group Name *', 'paid-member-subscriptions' ):
			$str = __( 'Organization Name *', 'paid-member-subscriptions' );
			break;
		case __( 'Group Description', 'paid-member-subscriptions' ):
			$str = __( 'Description', 'paid-member-subscriptions' );
			break;
	}
	return $str;
}
add_filter( 'wp_setup_nav_menu_item', 'customize_cpm_setup_nav_menu_item', 100 );
/**
 * Function that adds the menu item url
 *
 * @since v.1.0.0
 */
function customize_cpm_setup_nav_menu_item( $item ) {
	global $pagenow;

	$redirect_after_logout_url = '';

	$wppb_cpm_form_page_url = get_post_meta( $item->ID, 'wppb-cpm-form-page-url', true );

	if( $pagenow != 'nav-menus.php' && strstr( $item->type, 'wppb_cpm' ) != '' ) {
		if( ! empty( $wppb_cpm_form_page_url ) ) {
			switch( $item->type ) {
				case 'wppb_cpm_login_logout' :
					if (is_user_logged_in()){
						$item->url = esc_url( remove_query_arg("redirect_to", wp_logout_url()));
					}
					break;
			}
		}
	}

	return $item;
}

/**
 * Remove admin bard from logged in users if option is selected
 */
$pms_misc_settings = get_option( 'pms_misc_settings', array() );

if( isset( $pms_misc_settings, $pms_misc_settings['hide-admin-bar'] ) && $pms_misc_settings['hide-admin-bar'] == 1 ){
    add_filter( 'show_admin_bar', 'pms_remove_admin_bar_for_all_users' );
}

function pms_remove_admin_bar_for_all_users(){

    // if( current_user_can( 'manage_options' ) )
    //     return true;

    return false;

}

/*
 * Change Send Credentials via Email text. Tags: send credentials, email
 */
add_filter('wppb_send_credentials_checkbox_logic', 'wppbc_send_credentials_checkbox', 10, 2);
function wppbc_send_credentials_checkbox($requestdata, $form){
   return '<li class="wppb-form-field wppb-send-credentials-checkbox"><label for="send_credentials_via_email"><input id="send_credentials_via_email" type="checkbox" name="send_credentials_via_email" value="sending"'.( ( isset( $request_data['send_credentials_via_email'] ) && ( $request_data['send_credentials_via_email'] == 'sending' ) ) ? ' checked' : '' ).'/>'.
   __( 'Send username and password to your organization\'s contact person via email.', 'profilebuilder').'</label></li>';
}

// Redirect /register/ page only when there are no query parameters. Do NOT redirect if there are query parameters, as this functionality is needed for invited Group Users to be able to register
function redirect_if_no_query_params() {
    if ($_SERVER['REQUEST_URI'] === '/register/' && (!isset($_SERVER['QUERY_STRING']) || empty($_SERVER['QUERY_STRING']))) {
        wp_redirect(home_url('/plans/'), 301); // Redirect to the new page
        exit;
    }
}
add_action('template_redirect', 'redirect_if_no_query_params');



