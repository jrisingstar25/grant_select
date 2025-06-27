<?php
if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_Safe_Mode')) return;

/**
 * Class MPSUM_Premium_Admin to handle admin section of premium features
 */
class MPSUM_Safe_Mode {

	/**
	 * MPSUM_Premium_Admin constructor. Adds necessary hooks
	 */
	private function __construct() {
		add_action('eum_advanced_headings', array($this, 'heading'), 20);
		add_action('eum_advanced_settings', array($this, 'settings'), 20);
		add_filter('eum_i18n', array($this, 'safe_mode_i18n'));
		add_action('auto_update_plugin',  array($this, 'maybe_automatic_updates_plugins'), PHP_INT_MAX - 5, 2);
		add_action('auto_update_theme',  array($this, 'maybe_automatic_updates_themes'), PHP_INT_MAX - 5, 2);
		add_filter('mpsum_default_options', array($this, 'add_to_defaults'));
		add_action('after_plugin_row', array($this, 'after_plugin_row'), 10, 3);
	}

	/**
	 * Returns a singleton instance
	 *
	 * @return MPSUM_Premium_Admin
	 */
	public static function get_instance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Add plugin warning to WordPress plugin's page
	 *
	 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data.
	 * @param string $status      Status of the plugin. Defaults are 'All', 'Active','Inactive', 'Recently Activated', 'Upgrade', 'Must-Use','Drop-ins', 'Search'.
	 */
	public function after_plugin_row($plugin_file) {
		$core_options = MPSUM_Updates_Manager::get_options('core');
		if (!isset($core_options['safe_mode']) || 'off' === $core_options['safe_mode']) {
			return;
		}
		$plugin_item = $this->perform_api_check($plugin_file);
		$plugin_slug = dirname($plugin_file);
		
		// Allow white label
		$eum_white_label = apply_filters('eum_whitelabel_name', __('Easy Updates Manager', 'stops-core-theme-and-plugin-updates'));
		
		if (!$this->is_compatible_wp_version($plugin_item)) {
			printf('<tr class="plugin-update-tr" id="%1$s-eum-notice" data-slug="%1$s"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p>%2$s</p></div></td></tr>', esc_attr($plugin_slug), sprintf(esc_html__('This plugin has not been tested with the your version of WordPress and will not be updated automatically by %s.', 'stops-core-theme-and-plugin-updates'), $eum_white_label));
		}
		if (!$this->is_compatible_php_version($plugin_item)) {
			printf('<tr class="plugin-update-tr" id="%1$s-eum-notice" data-slug="%1$s"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p>%2$s</p></div></td></tr>', esc_attr($plugin_slug), sprintf(esc_html__('This plugin does not support your PHP version and will not be updated automatically by %s.', 'stops-core-theme-and-plugin-updates'), $eum_white_label));
		}
	}

	/**
	 * Add plugin warning to WordPress plugin's page
	 *
	 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
	 *
	 * @return object Plugin API data or empty object
	 */
	public function perform_api_check($plugin_file) {
		$plugin_safe_mode_transient = get_site_transient('eum_plugin_safe_mode_' . $plugin_file);
		if (false !== $plugin_safe_mode_transient && is_object($plugin_safe_mode_transient)) {
			return $plugin_safe_mode_transient;
		} else {
			// Perform API check
			$args = (object) array( 'slug' => dirname($plugin_file), 'fields' => array('tested' => true, 'requires_php' => true));
			$request = array( 'action' => 'plugin_information', 'timeout' => 15, 'request' => serialize($args) );
			$url = 'https://api.wordpress.org/plugins/info/1.0/';
			$response = wp_remote_post($url, array( 'body' => $request ));
			if (!is_wp_error($response)) {
				$plugin_item = MPSUM_Updates_Manager::unserialize(wp_remote_retrieve_body($response));
				if (empty($plugin_item)) {
					$plugin_item = (object) array();
				}
				// Set transient for 360 minutes so we don't hit the API every single time
				set_site_transient('eum_plugin_safe_mode_' . $plugin_file, $plugin_item, 6 * 60 * 60);
				return $plugin_item;
			}
		}
		return (object) array();
	}

	/**
	 * Maybe output safe mode messages for plugins
	 *
	 * @param object $item Single plugin item
	 */
	public function maybe_output_check_safe_mode($item) {
		// Allow white label
		$eum_white_label = apply_filters('eum_whitelabel_name', __('Easy Updates Manager', 'stops-core-theme-and-plugin-updates'));
		
		if (!$this->is_compatible_wp_version($item)) {
			printf('<div class="mpsum-error mpsum-bold">%s</div>', sprintf(esc_html__('This plugin has not been tested with the your version of WordPress and will not be updated automatically by %s.', 'stops-core-theme-and-plugin-updates'), $eum_white_label));
		}
		if (!$this->is_compatible_php_version($item)) {
			printf('<div class="mpsum-error mpsum-bold">%s</div>', sprintf(esc_html__('This plugin does not support your PHP version and will not be updated automatically by %s.', 'stops-core-theme-and-plugin-updates'), $eum_white_label));
		}
	}

	/**
	 * Add option to default options
	 *
	 * @param array $options array of default options
	 * @return array of default options
	 */
	public function add_to_defaults($options) {
		$options['safe_mode'] = 'off';
		return $options;
	}

	/**
	 * Checks WP Compatibility and PHP compatibility.
	 *
	 * @since 8.0.1
	 * @access public
	 * @see __construct
	 * @internal uses auto_update_plugin filter
	 *
	 * @param bool   $update Whether the item has automatic updates enabled
	 * @param object $item   Object holding the asset to be updated
	 * @return bool  true to update, false if not
	 */
	public function maybe_automatic_updates_plugins($update, $item) {

		if (!$update) return $update;
		// Check to see if a third-party plugin is being used and skip safe mode if that's the case
		if (!isset($item->tested) || !isset($item->id)) {
			return $update;
		}

		// Check to see if safe mode is enabled
		$core_options = MPSUM_Updates_Manager::get_options('core');
		if (!isset($core_options['safe_mode']) || 'off' === $core_options['safe_mode']) {
			return $update;
		}

		// Check WordPress version
		if (!$this->is_compatible_wp_version($item) || (defined('EUM_SAFE_MODE_DEBUG') && true === EUM_SAFE_MODE_DEBUG)) {
			$update = MPSUM_Utils::is_wp_site_health_plugin_theme($item);
			if (!$update) {
				$logs = MPSUM_External_Logs::get_instance();
				$plugin_name = $logs->get_name_for_update('plugin', $item->slug);
				$logs->insert_log(
					$plugin_name,
					'plugin',
					$item->new_version,
					$item->new_version,
					'automatic',
					2,
					0,
					sprintf(__('Safe mode is enabled for this plugin and it required a WP version %s higher than the current version %s.', 'stops-core-theme-and-plugin-updates'), $item->tested, MPSUM_Updates_Manager::get_instance()->get_wordpress_version())
				);
			}
			return $update;
		}

		// Check PHP version
		if (!$this->is_compatible_php_version($item) || (defined('EUM_SAFE_MODE_DEBUG') && true === EUM_SAFE_MODE_DEBUG)) {
			$update = MPSUM_Utils::is_wp_site_health_plugin_theme($item);
			if (!$update) {
				$logs = MPSUM_External_Logs::get_instance();
				$plugin_name = $logs->get_name_for_update('plugin', $item->slug);
				$logs->insert_log(
					$plugin_name,
					'plugin',
					$item->new_version,
					$item->new_version,
					'automatic',
					2,
					0,
					sprintf(__('Safe mode is enabled for this plugin and it required a PHP version %s higher than the current version %s.', 'stops-core-theme-and-plugin-updates'), $item->requires_php, phpversion())
				);
			}
			return $update;
		}

		return $update;
	}

	/**
	 * Checks WP Compatibility and PHP compatibility.
	 *
	 * @since 8.0.1
	 * @access public
	 * @see __construct
	 * @internal uses auto_update_plugin filter
	 *
	 * @param bool   $update Whether the item has automatic updates enabled
	 * @param object $item   Object holding the asset to be updated
	 * @return bool  true to update, false if not
	 */
	public function maybe_automatic_updates_themes($update, $item) {

		if (!$update) return $update;
		// Check that a PHP requirement is set, otherwise it may be a premium theme.
		if (! isset($item->requires_php)) {
			return $update;
		}

		// Check to see if safe mode is enabled
		$core_options = MPSUM_Updates_Manager::get_options('core');
		if (!isset($core_options['safe_mode']) || 'off' === $core_options['safe_mode']) {
			return $update;
		}

		// Check PHP version
		if (!$this->is_compatible_php_version($item) || (defined('EUM_SAFE_MODE_DEBUG') && true === EUM_SAFE_MODE_DEBUG)) {
			$update = MPSUM_Utils::is_wp_site_health_plugin_theme($item);
			if (!$update) {
				$logs = MPSUM_External_Logs::get_instance();
				$theme_name = $logs->get_name_for_update('theme', $item->theme);
				$logs->insert_log(
					$theme_name,
					'theme',
					$item->new_version,
					$item->new_version,
					'automatic',
					2,
					0,
					sprintf(__('Safe mode is enabled for this theme and it required a PHP version %s higher than the current version %s.', 'stops-core-theme-and-plugin-updates'), $item->requires_php, phpversion())
				);
			}
			return $update;
		}

		return $update;
	}

	/**
	 * Checks PHP version for compatibility
	 *
	 * @since 8.0.1
	 * @access public
	 *
	 * @param object $item Object holding the asset to be updated
	 * @return bool  true to update, false if not
	 */
	public function is_compatible_php_version($item) {
		// Dev note: currently the API being used by WordPress internally (http://api.wordpress.org/plugins/update-check/1.1/)
		if (isset($item->requires_php) && !empty($item->requires_php)) {
			if (version_compare(phpversion(), $item->requires_php, '<=')) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks WP version for compatibility
	 *
	 * @since 8.0.1
	 * @access public
	 *
	 * @param object $item Object holding the asset to be updated
	 * @return bool  true to update, false if not
	 */
	public function is_compatible_wp_version($item) {
		if (empty($item->tested)) {
			return true;
		}

		// Takes a version like 4.9.6 and strips it down to 4.9 - If no match found, return true
		$pattern = '/^(\d+)\.(\d+)/';
		preg_match($pattern, $item->tested, $matches);
		if (isset($matches[1]) && isset($matches[2])) {
			$version = $matches[1] . '.' . $matches[2];
		} else {
			return true;
		}

		// Strip WP version down to two digits
		preg_match($pattern, MPSUM_Updates_Manager::get_instance()->get_wordpress_version(), $matches);
		$wp_version = $matches[1] . '.' . $matches[2];

		if (version_compare($wp_version, $version, '>')) {
			return false;
		}
		return true;
	}

	/**
	 * Outputs feature heading
	 *
	 * @param array $l18n Internalization array
	 *
	 * @return array Updated internalization array
	 */
	public function safe_mode_i18n($l18n) {
			$new_i18n = array(
				'saving'              => __('Saving...', 'stops-core-theme-and-plugin-updates'),
				'working'             => __('Working...', 'stops-core-theme-and-plugin-updates'),
				'enable_safe_mode'    => __('Enable Safe Mode', 'stops-core-theme-and-plugin-updates'),
				'disable_safe_mode'   => __('Disable Safe Mode', 'stops-core-theme-and-plugin-updates')
			);
		return array_merge($l18n, $new_i18n);
	}

	/**
	 * Outputs feature heading
	 */
	public function heading() {
		printf('<div class="safe-mode-icon" data-menu_name="plugin-safe-mode">%s <span class="eum-advanced-menu-text">%s</span></div>', '<i class="material-icons">security</i>', esc_html(_x('Safe mode', 'Advanced title heading', 'stops-core-theme-and-plugin-updates')));
	}

	/**
	 * Includes scheduled cron settings form
	 */
	public function settings() {
		Easy_Updates_Manager()->include_template('safe-mode.php');
	}
}
