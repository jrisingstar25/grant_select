<?php
if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('MPSUM_Log_Export')) {

	/**
	 * Class to export data
	 */
	class MPSUM_Log_Export {

		/**
		 * Adds i18n
		 */
		private function __construct() {
			add_filter('eum_export_i18n', array($this, 'export_i18n'));
		}

		/**
		 * Returns a singleton instance
		 *
		 * @return MPSUM_Log_Export
		 */
		public static function get_instance() {
			static $instance = null;
			if (null === $instance) {
				$instance = new self();
			}
			return $instance;
		}

		/**
		 * Add export i18n
		 */
		public function export_i18n($i18n) {
			$date = date("Y-m-d");
			$sitename = is_multisite() ? get_site_option('site_name') : get_option('blogname');
			$csv_filename = sanitize_title($sitename) . "-" . $date . ".csv";
			$json_filename = sanitize_title($sitename) . "-" . $date . ".json";
			$i18n['export_csv_filename'] = $csv_filename;
			$i18n['export_json_filename'] = $json_filename;
			return $i18n;
		}
	}
}
