<?php
if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('MPSUM_Webhook')) {

	/**
	 * Class to create a webhook for forced updates
	 */
	class MPSUM_Webhook {

		/**
		 * Adds and removes necessary action and filter hooks to anonymize
		 */
		private function __construct() {
			add_action('eum_advanced_headings', array($this, 'heading'), 15);
			add_action('eum_advanced_settings', array($this, 'settings'), 15);
			add_filter('eum_i18n', array($this, 'webhook_i18n'));
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
			printf('<div data-menu_name="eum-webhook">%s <span class="eum-advanced-menu-text">%s</span></div>', '<i class="material-icons">all_out</i>', esc_html__('Webhook', 'stops-core-theme-and-plugin-updates'));
		}

		/**
		 * Outputs feature settings
		 */
		public function settings() {
			Easy_Updates_Manager()->include_template('webhook.php');
		}

		/**
		 * Add webhook i18n
		 *
		 * @param string $i18n
		 * @return string
		 */
		public function webhook_i18n($i18n) {
			$i18n['webhook_enable'] = __('Enable Webhook', 'stops-core-theme-and-plugin-updates');
			$i18n['webhook_disable'] = __('Disable Webhook', 'stops-core-theme-and-plugin-updates');
			$i18n['webhook_copy'] = _x('Copy', 'Copy webhook to clipboard', 'stops-core-theme-and-plugin-updates');
			$i18n['webhook_copied'] = _x('Copied', 'Copy webhook to clipboard', 'stops-core-theme-and-plugin-updates');
			return $i18n;
		}
	}
}
