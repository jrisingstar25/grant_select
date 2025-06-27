<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('MPSUM_Premium')) {

	/**
	 * MPSUM_Premium Class
	 *
	 * Developer Notes:
	 * automatic_patch_releases setting is only available on premium, and also available in plugins/themes tab when individual(choose per plugin/theme) option is selected, hence we don't hook the auto_update_* filter in MPSUM_Disable_Updates class which mostly initialise free-related settings
	 */
	class MPSUM_Premium {

		/**
		 * MPSUM_Premium constructor. Adds necessary actions and filters. Instantiates required classes.
		 */
		public function __construct() {
			add_filter('eum_i18n', array($this, 'i18n'));
			MPSUM_Premium_Admin::get_instance();
			MPSUM_Auto_Backup::get_instance();
			MPSUM_Delay_Updates::get_instance();
			MPSUM_Import_Export::get_instance();
			MPSUM_Update_Cron::get_instance();
			$options = MPSUM_Updates_Manager::get_options('logs');
			$schedule = isset($options['clear_logs']) ? $options['clear_logs'] : 0;
			MPSUM_Clear_Logs_Cron::get_instance($schedule);
			MPSUM_External_Logs::get_instance();
			MPSUM_Safe_Mode::get_instance();
			MPSUM_Anonymize_Updates::get_instance();
			MPSUM_Update_Notifications::get_instance();
			MPSUM_Webhook::get_instance();
			MPSUM_Check_Plugins_Removed::get_instance();
			MPSUM_Auto_Rollback::get_instance();
			MPSUM_Log_Export::get_instance();
			MPSUM_White_Label::get_instance();
			MPSUM_Version_Control::get_instance();
			MPSUM_Check_Unmaintained_Plugins::get_instance();
			MPSUM_Semantic_Versioning::get_instance();
			MPSUM_Cron_Days_Schedule::get_instance();
			$core_options = MPSUM_Updates_Manager::get_options('core');
			if (isset($core_options['theme_updates']) && in_array($core_options['theme_updates'], array('automatic_patch_releases', 'individual'))) {
				add_filter('auto_update_theme', '__return_true', PHP_INT_MAX - 15, 2); // the auto_update_theme's priority number must be lower than the one in the MPSUM_Disable_Updates class
			}
			if (isset($core_options['plugin_updates']) && in_array($core_options['plugin_updates'], array('automatic_patch_releases', 'individual'))) {
				add_filter('auto_update_plugin', '__return_true', PHP_INT_MAX - 15, 2); // the auto_update_plugin's priority number must be lower than the one in the MPSUM_Disable_Updates class
			}
			$this->run_updater();
		}

		/**
		 * Returns singleton instance of this class
		 *
		 * @return MPSUM_Premium Instance
		 */
		public static function get_instance() {
			static $instance = null;
			if (null === $instance) {
				$instance = new self();
			}
			return $instance;
		}

		/**
		 * Runs the plugin updater class dependency
		 */
		public function run_updater() {
			if (!class_exists('Updraft_Manager_Updater_1_9')) {
				include_once(EASY_UPDATES_MANAGER_MAIN_PATH . 'vendor/davidanderson684/simba-plugin-manager-updater/class-udm-updater.php');
			}

			try {
				new Updraft_Manager_Updater_1_9(EASY_UPDATES_MANAGER_SITE_URL . 'plugin-info/', 7, EASY_UPDATES_MANAGER_SLUG, array('require_login' => false));
			} catch (Exception $e) {
				error_log($e->getMessage().' at '.$e->getFile().' line '.$e->getLine());
			}

		}

		/**
		 * Adds translatable string to existing translation array
		 *
		 * @param array $i18n Translation array
		 *
		 * @return array Updated translation array
		 */
		public function i18n($i18n) {
			$date = date("Y-m-d");
			$sitename = is_multisite() ? get_site_option('site_name') : get_option('blogname');
			$json_filename = sanitize_title($sitename) . "-" . $date . ".json";
			$premium_i18n = array(
				'not_valid_number' => __('Not a valid number', 'stops-core-theme-and-plugin-updates'),
				'enable_auto_backup' => __('Enable auto backup', 'stops-core-theme-and-plugin-updates'),
				'enabled_auto_backup_description' => __('Your site will be backed up with UpdraftPlus automatically before updating.', 'stops-core-theme-and-plugin-updates'),
				'disable_auto_backup' => __('Disable Auto Backup', 'stops-core-theme-and-plugin-updates'),
				'disabled_auto_backup_description' => __('Your site will NOT be backed up automatically before updating.', 'stops-core-theme-and-plugin-updates'),
				'export_settings_filename' => $json_filename,
				'import_select_file' => __('You have not yet selected a file to import.', 'stops-core-theme-and-plugin-updates'),
				'import_invalid_json_file' => __('Error: The chosen file is corrupt.', 'stops-core-theme-and-plugin-updates').' '.__('Please choose a valid Easy Updates Manager export file.', 'stops-core-theme-and-plugin-updates'),
				'site_url' => get_option('siteurl'),
				'import_confirmation' => __("The file you're trying to import is not of this site, If you have customized plugin and themes update settings, this will override this site's settings.", 'stops-core-theme-and-plugin-updates').' '.__("Do you still want to import settings?", 'stops-core-theme-and-plugin-updates'),
			);
			$i18n =	array_merge($i18n, $premium_i18n);
			return $i18n;
		}
	}
}

if (!function_exists('Easy_Updates_Manager_Premium')) {
	/**
	 * Runs premium features
	 *
	 * @return MPSUM_Premium Instance
	 */
	function Easy_Updates_Manager_Premium() {
		return MPSUM_Premium::get_instance();
	}
}

Easy_Updates_Manager_Premium();
