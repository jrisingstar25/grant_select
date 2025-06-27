<?php
if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_Check_Plugins_Removed')) return;

/**
 * Class MPSUM_Check_Plugins_Removed checks to see if plugins have been removed from the repo
 */
class MPSUM_Check_Plugins_Removed {

	/**
	 * MPSUM_Check_Plugins_Removed constructor.
	 */
	private function __construct() {
		add_action('eum_advanced_headings', array($this, 'heading'), 98);
		add_action('eum_advanced_settings', array($this, 'settings'), 98);
		add_action('after_plugin_row', array($this, 'after_plugin_row'), 10, 3);
	}

	/**
	 * Initiates and returns singleton instance of this class
	 *
	 * @return MPSUM_Check_Plugins_Removed instance
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
		$plugin_slug = dirname($plugin_file);
		$option = get_site_option('eum_plugin_removed_' . $plugin_slug);
		if ('true' === $option) {
			printf('<tr class="plugin-update-tr" id="%1$s-eum-notice" data-slug="%1$s"><td colspan="3" class="plugin-update colspanchange"><div class="update-message notice inline notice-error notice-alt"><p>%2$s</p></div></td></tr>', esc_attr($plugin_slug), esc_html__('This plugin has been removed from the WordPress plugin directory and may pose a security risk.', 'stops-core-theme-and-plugin-updates'));
		}
	}

	/**
	 * Checks to see if a plugin has been removed
	 *
	 * @since 8.0.1
	 *
	 * @param string $plugin_file The plugin file to check
	 */
	public function check_if_plugin_removed($plugin_file) {

		// Get the plugin slug
		$plugin_slug = trim(dirname($plugin_file));

		// Get option
		$plugin_option = get_site_option('eum_plugin_removed_' . $plugin_slug);
		if (false === $plugin_option) return;

		// Check to see if value is true and if it, throw an error
		if ('true' === $plugin_option) {
			$this->throw_plugin_remove_error();
		}
	}

	/**
	 * Throw an invalid Plugin Directory Removal notice.
	 *
	 * @since 8.0.1
	 */
	public function throw_plugin_remove_error() {
		?>
		<div class="mpsum-error mpsum-bold">
			<?php
			_e('This plugin has been removed from the WordPress plugin directory and may pose a security risk.', 'stops-core-theme-and-plugin-updates');
			?>
		</div>
		<?php
	}

	/**
	 * Outputs feature heading
	 */
	public function heading() {
		printf('<div data-menu_name="check-plugins">%s <span class="eum-advanced-menu-text">%s</span></div>', '<i class="material-icons">check_circle</i>', esc_html__('Dead plugins', 'stops-core-theme-and-plugin-updates'));
	}

	/**
	 * Outputs feature settings
	 */
	public function settings() {
		Easy_Updates_Manager()->include_template('check_plugins_removed.php');
	}
}
