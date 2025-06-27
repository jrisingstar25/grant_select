<?php
if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('MPSUM_White_Label')) {

	/**
	 * Class to create whitelabel option
	 */
	class MPSUM_White_Label {

		/**
		 * Adds and removes necessary action and filter hooks to whitelabel
		 */
		private function __construct() {
			add_action('eum_advanced_headings', array($this, 'heading'), 100);
			add_action('eum_advanced_settings', array($this, 'settings'), 100);
			add_filter('all_plugins', array($this, 'maybe_change_plugin_name'));
			add_filter('eum_whitelabel_name', array($this, 'fetch_whitelabel_name'));
		}

		/**
		 * Returns a singleton instance
		 *
		 * @return MPSUM_White_Label
		 */
		public static function get_instance() {
			static $instance = null;
			if (null === $instance) {
				$instance = new self();
			}
			return $instance;
		}

		/**
		 * Filter the plugins name
		 *
		 * @param array $plugins An array of all plugins for the site
		 *
		 * @return array $plugins An array of all plugins for the site
		 */
		public function maybe_change_plugin_name($plugins) {
			$eum_white_label  = $eum_white_author = $eum_white_url = '';
			$eum_white_label = get_site_option('easy_updates_manager_name', __('Easy Updates Manager Premium', 'stops-core-theme-and-plugin-updates'));
			$eum_white_author = get_site_option('easy_updates_manager_author', __('Easy Updates Manager Team', 'stops-core-theme-and-plugin-updates'));
			$eum_white_url = get_site_option('easy_updates_manager_url', esc_url('https://easyupdatesmanager.com/'));

			// Replace with white listed data
			foreach ($plugins as &$data) {
				if ('Easy Updates Manager Premium' === $data['Name']) {
					$data['Name'] = esc_html($eum_white_label);
					$data['Title'] = esc_html($eum_white_label);
					$data['AuthorName'] = esc_html($eum_white_author);
					$data['Author'] = esc_html($eum_white_author);
					$data['PluginURI'] = esc_url($eum_white_url);
					$data['AuthorURI'] = esc_url($eum_white_url);
				}
			}
			return $plugins;
		}

		/**
		 * Outputs feature heading
		 */
		public function heading() {
			printf('<div data-menu_name="eum-whitelabel">%s <span class="eum-advanced-menu-text">%s</span></div>', '<i class="material-icons">label</i>', esc_html__('White-label', 'stops-core-theme-and-plugin-updates'));
		}

		/**
		 * Outputs feature settings
		 */
		public function settings() {
			Easy_Updates_Manager()->include_template('white-label.php');
		}
		
		/**
		 * Return either the default or the white-labelled plugin name
		 */
		public function fetch_whitelabel_name() {
			return get_site_option('easy_updates_manager_name', __('Easy Updates Manager Premium', 'stops-core-theme-and-plugin-updates'));
		}
	}
}
