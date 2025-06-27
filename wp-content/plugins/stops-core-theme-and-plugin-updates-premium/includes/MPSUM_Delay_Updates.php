<?php
if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_Delay_Updates')) return;

/**
 * Class MPSUM_Delay_Updates handles delayed updates
 */
class MPSUM_Delay_Updates {

	/**
	 * MPSUM_Delay_Updates constructor.
	 */
	private function __construct() {
		add_action('eum_advanced_headings', array($this, 'heading'), 13);
		add_action('eum_advanced_settings', array($this, 'settings'), 13);
		add_filter('auto_update_core', array($this, 'auto_update_core'), PHP_INT_MAX - 8, 2);
		add_filter('auto_update_plugin', array($this, 'auto_update_plugin'), PHP_INT_MAX - 8, 2);
		add_filter('auto_update_theme', array($this, 'auto_update_theme'), PHP_INT_MAX - 8, 2);
	}

	/**
	 * Initiates and returns singleton instance of this class
	 *
	 * @return MPSUM_Delay_Updates instance
	 */
	public static function get_instance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Outputs feature heading
	 */
	public function heading() {
		printf('<div data-menu_name="delay-updates">%s <span class="eum-advanced-menu-text">%s</span></div>', '<i class="material-icons">hourglass_empty</i>', esc_html__('Delay automatic updates', 'stops-core-theme-and-plugin-updates'));
	}

	/**
	 * Outputs feature settings
	 */
	public function settings() {
		Easy_Updates_Manager()->include_template('delay-updates.php');
	}

	/**
	 * Decides whether to do available core update or delay it
	 *
	 * @param bool   $update A flag that decides whether to update or not
	 * @param object $item   A object contains core update details
	 *
	 * @return bool Returns false if update needs to be delayed, otherwise default update status
	 */
	public function auto_update_core($update, $item) {
		$core_updates = MPSUM_Updates_Manager::get_options('core_updates');
		if (empty($core_updates['wp_core']) || version_compare($core_updates['wp_core']['version'], $item->version, '<')) {
			$core_updates['wp_core'] = array(
				'version' => $item->version,
				'time' => time()
			);
			MPSUM_Updates_Manager::update_options($core_updates, 'core_updates');
		}
		$core_updates = MPSUM_Updates_Manager::get_options('core_updates', true);
		$time = $core_updates['wp_core']['time'];
		$now = time();
		$options = MPSUM_Updates_Manager::get_options('advanced');
		$delay = isset($options['delay_updates']) ? $options['delay_updates'] : '0';
		if ($now < $time + $delay * 86400) return false;
		return $update;
	}

	/**
	 * Decides whether to do available plugin update or delay it
	 *
	 * @param bool   $update A flag that decides whether to update or not
	 * @param object $item   A object contains plugin update details
	 *
	 * @return bool Returns false if update needs to be delayed, otherwise default update status
	 */
	public function auto_update_plugin($update, $item) {
		if (!$update || !isset($item->plugin)) return $update;
		$plugin_updates = MPSUM_Updates_Manager::get_options('plugin_updates');
		if (empty($plugin_updates[$item->plugin]) || version_compare($plugin_updates[$item->plugin]['version'], $item->new_version, '<')) {
			$plugin_updates[$item->plugin] = array(
				'version' => $item->new_version,
				'time' => time()
			);
			MPSUM_Updates_Manager::update_options($plugin_updates, 'plugin_updates');
		}
		$plugin_updates = MPSUM_Updates_Manager::get_options('plugin_updates', true);
		$time = $plugin_updates[$item->plugin]['time'];
		$now = time();
		$options = MPSUM_Updates_Manager::get_options('advanced');
		$delay = isset($options['delay_updates']) ? $options['delay_updates'] : '0';
		if ($now < $time + $delay * 86400) {
			return MPSUM_Utils::is_wp_site_health_plugin_theme($item);
		} else {
			unset($plugin_updates[$item->plugin]);
			MPSUM_Updates_Manager::update_options($plugin_updates, 'plugin_updates');
		}
		return $update;
	}

	/**
	 * Decides whether to do available theme update or delay it
	 *
	 * @param bool   $update A flag that decides whether to update or not
	 * @param object $item   A object contains theme update details
	 *
	 * @return bool Returns false if update needs to be delayed, otherwise default update status
	 */
	public function auto_update_theme($update, $item) {
		if (!$update || !isset($item->theme)) return $update;
		$theme_updates = MPSUM_Updates_Manager::get_options('theme_updates');
		if (empty($theme_updates[$item->theme]) || version_compare($theme_updates[$item->theme]['version'], $item->new_version, '<')) {
			$theme_updates[$item->theme] = array(
				'version' => $item->new_version,
				'time' => time()
			);
			MPSUM_Updates_Manager::update_options($theme_updates, 'theme_updates');
		}
		$theme_updates = MPSUM_Updates_Manager::get_options('theme_updates', true);
		$time = $theme_updates[$item->theme]['time'];
		$now = time();
		$options = MPSUM_Updates_Manager::get_options('advanced');
		$delay = isset($options['delay_updates']) ? $options['delay_updates'] : '0';
		if ($now < $time + $delay * 86400) {
			return MPSUM_Utils::is_wp_site_health_plugin_theme($item);
		} else {
			unset($theme_updates[$item->theme]);
			MPSUM_Updates_Manager::update_options($theme_updates, 'theme_updates');
		}
		return $update;
	}
}
