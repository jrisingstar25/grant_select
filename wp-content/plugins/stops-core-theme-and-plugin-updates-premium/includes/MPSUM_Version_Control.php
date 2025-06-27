<?php
if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('MPSUM_Version_Control')) {

	/**
	 * Class to an option to skip VCS updates.
	 */
	class MPSUM_Version_Control {

		/**
		 * Adds and removes necessary action and filter hooks to anonymize
		 */
		private function __construct() {
			add_action('eum_advanced_headings', array($this, 'heading'), 15);
			add_action('eum_advanced_settings', array($this, 'settings'), 15);
			add_filter('eum_i18n', array($this, 'i18n'));
			add_filter('mpsum_default_options', array($this, 'options_defaults'));
		}

		/**
		 * Returns a singleton instance
		 *
		 * @return MPSUM_Anonymize_Updates
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
			printf('<div data-menu_name="eum-version-control">%s <span class="eum-advanced-menu-text">%s</span></div>', '<i class="material-icons">code</i>', esc_html__('Version control protection', 'stops-core-theme-and-plugin-updates'));
		}

		/**
		 * Outputs feature settings
		 */
		public function settings() {
			Easy_Updates_Manager()->include_template('version-control-protection.php');
		}

		/**
		 * Allows overriding of option defaults.
		 *
		 * @param array $defaults options.
		 *
		 * @return array modified defaults
		 */
		public function options_defaults($defaults) {
			$defaults['version_control'] = 'off';
			return $defaults;
		}

		/**
		 * Add Version Control i18n
		 *
		 * @param string $i18n
		 * @return string
		 */
		public function i18n($i18n) {
			$i18n['version_control_enable'] = __('Enable version control protection', 'stops-core-theme-and-plugin-updates');
			$i18n['version_control_disable'] = __('Disable version control protection', 'stops-core-theme-and-plugin-updates');
			return $i18n;
		}
	}
}
