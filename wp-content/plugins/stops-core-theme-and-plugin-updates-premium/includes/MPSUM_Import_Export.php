<?php
if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_Import_Export')) return;

/**
 * Class MPSUM_Import_Export handles import and export settings
 */
class MPSUM_Import_Export {

	/**
	 * MPSUM_Import_Export constructor.
	 */
	private function __construct() {
		add_action('eum_advanced_headings', array($this, 'heading'), 98);
		add_action('eum_advanced_settings', array($this, 'settings'), 98);
	}

	/**
	 * Initiates and returns singleton instance of this class
	 *
	 * @return MPSUM_Import_Export instance
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
		printf('<div data-menu_name="import-export">%s <span class="eum-advanced-menu-text">%s</span></div>', '<i class="material-icons">save</i>', esc_html__('Export / import settings', 'stops-core-theme-and-plugin-updates'));
	}

	/**
	 * Outputs feature settings
	 */
	public function settings() {
		Easy_Updates_Manager()->include_template('import-export-settings.php');
	}
}
