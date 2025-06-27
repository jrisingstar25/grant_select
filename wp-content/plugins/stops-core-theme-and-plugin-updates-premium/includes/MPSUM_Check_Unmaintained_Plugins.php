<?php
if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_Check_Unmaintained_Plugins')) return;

/**
 * Class MPSUM_Check_Plugins_Removed checks to see if plugins have been removed from the repo
 */
class MPSUM_Check_Unmaintained_Plugins {

	/**
	 * MPSUM_Check_Plugins_Removed constructor.
	 */
	private function __construct() {
		add_action('eum_advanced_headings', array($this, 'heading'), 98);
		add_action('eum_advanced_settings', array($this, 'settings'), 98);
		add_action('after_plugin_row', array($this, 'after_plugin_row'), 10, 3);
		add_filter('eum_i18n', array($this, 'unmaintained_plugins_i18n'));
		add_filter('mpsum_default_options', array($this, 'add_to_defaults'));
		add_action('after_plugin_row', array($this, 'after_plugin_row'), 10, 3);
	}

	/**
	 * Initiates and returns singleton instance of this class
	 *
	 * @return MPSUM_Check_Unmaintained_Plugins instance
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
	 */
	public function after_plugin_row($plugin_file) {
		$core_options = MPSUM_Updates_Manager::get_options('core');
		if (!isset($core_options['unmaintained_plugins']) || 'off' === $core_options['unmaintained_plugins']) {
			return;
		}
		$plugin_item = $this->perform_api_check($plugin_file);
		$plugin_slug = dirname($plugin_file);
		if (!$this->is_maintained($plugin_item)) {
			printf('<tr class="plugin-update-tr" id="%1$s-eum-notice" data-slug="%1$s"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p>%2$s</p></div></td></tr>', esc_attr($plugin_slug), esc_html__('This plugin has not been maintained in a while.', 'stops-core-theme-and-plugin-updates').' '.esc_html__('Please consider finding an alternative.', 'stops-core-theme-and-plugin-updates') . ' ' . sprintf(esc_html__('The last update was: %s ago.', 'stops-core-theme-and-plugin-updates'), human_time_diff(strtotime($plugin_item->last_updated))));
		}
	}

	/**
	 * Checks to see if the item last updated has been over a year.
	 *
	 * @since 8.1.0
	 * @access private
	 *
	 * @param object $plugin_item Object holding the asset to be updated
	 * @return bool  true if maintained (or third-party plugin) or false if not updated in over a year.
	 */
	private function is_maintained($plugin_item) {
		$last_updated = isset($plugin_item->last_updated) ? strtotime($plugin_item->last_updated) : false;

		// Last updated may be false (not set) due to a third-party plugin. Skip checking.
		if (!$last_updated) {
			return true;
		}

		/**
		 * Filter time for plugin checks.
		 *
		 * @since 8.1.0
		 *
		 * @param string $time The time to check for in strtotime format.
		 */
		$time_to_check = strtotime(apply_filters('eum_unmaintained_plugins_time', '-1 year'), time());
		if ($last_updated < $time_to_check) {
			return false;
		}
		return true;
	}

	/**
	 * Add plugin warning to WordPress plugin's page
	 *
	 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
	 * @param bool   $force       Force check the plugin (ignore transients).
	 *
	 * @return object Plugin API data or empty object
	 */
	public function perform_api_check($plugin_file, $force = false) {
		$force = isset($_GET['force_api_check']);

		$plugin_item = new stdClass();

		// Check for valid filename.
		if (! preg_match('/^[a-z0-9-_]+(\/[a-z0-9-_]+)?\.php$/i', $plugin_file)) {
			return $plugin_item;
		}

		// Check to see if plugin file is available on the site.
		$utils = MPSUM_Utils::get_instance();
		if (! $utils->plugin_exists($plugin_file)) {
			return $plugin_item;
		}

		// Check for previously cached plugin data.
		$plugin_unmaintained_transient = get_site_transient('eum_plugin_unmaintained_' . $plugin_file);
		if (false !== $plugin_unmaintained_transient && is_object($plugin_unmaintained_transient) && ! $force) {
			return $plugin_unmaintained_transient;
		} else {
			// Perform API check.
			if (! function_exists('plugins_api')) {
				require_once ABSPATH . '/wp-admin/includes/plugin-install.php';
			}
			$plugin_item = plugins_api(
				'plugin_information',
				array(
					'slug' => dirname($plugin_file),
				)
			);
			if (is_wp_error($plugin_item)) {
				// Set an empty object for potential third-party plugin that isn't recognized by the API.
				$plugin_item = new stdClass();
			}
		}
		$plugin_file_mod_time = filemtime(WP_PLUGIN_DIR.'/'.$plugin_file);
		if (isset($plugin_item->last_updated)) {
			$last_updated = strtotime($plugin_item->last_updated);
			if (false !== $last_updated && false !== $plugin_file_mod_time && $plugin_file_mod_time > $last_updated) $plugin_item->last_updated = gmdate('Y-m-d g:ia e', $plugin_file_mod_time);
		}
		// Set transient for 360 minutes so we don't hit the API every single time.
		set_site_transient('eum_plugin_unmaintained_' . $plugin_file, $plugin_item, 6 * 60 * 60);
		return $plugin_item;
	}

	/**
	 * Outputs i18n for the module.
	 *
	 * @param array $l18n Internalization array
	 *
	 * @return array Updated internalization array
	 */
	public function unmaintained_plugins_i18n($l18n) {
		$new_i18n = array(
			'saving'                       => __('Saving...', 'stops-core-theme-and-plugin-updates'),
			'working'                      => __('Working...', 'stops-core-theme-and-plugin-updates'),
			'enable_unmaintained_plugins'  => __('Enable Plugin Checks', 'stops-core-theme-and-plugin-updates'),
			'disable_unmaintained_plugins' => __('Disable Plugin Checks', 'stops-core-theme-and-plugin-updates')
		);
		return array_merge($l18n, $new_i18n);
	}

	/**
	 * Add option to default options
	 *
	 * @param array $options array of default options
	 * @return array of default options
	 */
	public function add_to_defaults($options) {
		$options['unmaintained_plugins'] = 'off';
		return $options;
	}

	/**
	 * Outputs feature heading
	 */
	public function heading() {
		printf('<div data-menu_name="unmaintained-plugins">%s <span class="eum-advanced-menu-text">%s</span></div>', '<i class="material-icons">hourglass_empty</i>', esc_html__('Unmaintained plugins', 'stops-core-theme-and-plugin-updates'));
	}

	/**
	 * Outputs feature settings
	 */
	public function settings() {
		Easy_Updates_Manager()->include_template('unmaintained_plugins.php');
	}
}
