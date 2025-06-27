<?php

if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_Premium_Admin_Ajax')) return;

/**
 * Class MPSUM_Premium_Admin_Ajax to handle premium feature ajax requests
 */
class MPSUM_Premium_Admin_Ajax {

	/**
	 * MPSUM_Premium_Admin_Ajax constructor.
	 */
	private function __construct() {
		add_action('wp_ajax_nopriv_eum_webhook', array($this, 'ajax_webhook'));
		add_action('wp_ajax_eum_webhook', array($this, 'ajax_webhook'));
		add_action('wp_ajax_eum_export_logs', array($this, 'ajax_export_logs'));
		add_action('wp_ajax_eum_export_csv', array($this, 'ajax_export_logs_csv'));
		add_action('wp_ajax_eum_export_json', array($this, 'ajax_export_logs_json'));
	}

	/**
	 * Returns singleton instance
	 *
	 * @return MPSUM_Premium_Admin_Ajax
	 */
	public static function get_instance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Handles the Ajax export of logs
	 *
	 * @see __construct
	 */
	public function ajax_export_logs() {
		// Perform a nonce check
		if (!wp_verify_nonce($_REQUEST['nonce'], 'eum_export_logs')) {
			die('Security Check.');
		}

		// Perform a capabilities check
		if (is_multisite()) {
			if (!current_user_can('manage_network')) {
				die('User cannot manage network.');
			}
		} else {
			if (!current_user_can('install_plugins')) {
				die('User cannot install plugins.');
			}
		}
		ob_start();
		Easy_Updates_Manager()->include_template('export-logs.php');
		die(ob_get_clean());
	}

	/**
	 * Handles the Ajax export of logs in CSV format
	 *
	 * @see __construct
	 */
	public function ajax_export_logs_csv() {
		if (!wp_verify_nonce($_REQUEST['nonce'], 'eum_export_logs')) {
			die('Security check.');
		}

		// Perform a capabilities check
		if (is_multisite()) {
			if (!current_user_can('manage_network')) {
				die('User cannot manage network.');
			}
		} else {
			if (!current_user_can('install_plugins')) {
				die('User cannot install plugins.');
			}
		}
		ob_start();
		$list_table = new MPSUM_Logs_List_Table();
		$list_table->prepare_items();
		$columns = $list_table->get_columns();
		$csv_header = array();
		foreach ($columns as $column_name) {
			$csv_header[] = $column_name;
		}
		echo implode(',', $csv_header) . "\r\n";
		$list_table->display_csv();
		die(ob_get_clean());
	}

	/**
	 * Handles the Ajax export of logs in JSON format
	 *
	 * @see __construct
	 */
	public function ajax_export_logs_json() {
		if (!wp_verify_nonce($_REQUEST['nonce'], 'eum_export_logs')) {
			die('Security check.');
		}

		// Perform a capabilities check
		if (is_multisite()) {
			if (!current_user_can('manage_network')) {
				die('User cannot manage network.');
			}
		} else {
			if (!current_user_can('install_plugins')) {
				die('User cannot install plugins.');
			}
		}
		ob_start();
		$list_table = new MPSUM_Logs_List_Table();
		$list_table->prepare_items();
		$list_table->display_json();
		die(ob_get_clean());
	}

	/**
	 * Handles the Ajax webhook
	 *
	 * @see __construct
	 *
	 * @param array $_GET {
	 *		$type string $hash    Hash sent to Ajax request
	 *		$type string $action  Action for Ajax
	 *	}
	 */
	public function ajax_webhook() {

		// Set response code so we know we're hitting the webhook
		$response = array(
			'code'            => 200,
			'errors'          => false,
			'ran_immediately' => false,
			'timestamp'       => time()
		);
		
		// Allow white label
		$eum_white_label = apply_filters('eum_whitelabel_name', __('Easy Updates Manager', 'stops-core-theme-and-plugin-updates'));
		
		// Check for existence of hash in url params
		if (!isset($_GET['eum_webhook_hash'])) {
			$response['errors'] = true;
			$response['message'] = sprintf(__('No hash found for %s', 'stops-core-theme-and-plugin-updates'), $eum_white_label);
			wp_send_json($response);
		}

		// Attempt to get options
		$webhook_options = get_site_option('easy_updates_manager_webhook');
		if (false === $webhook_options) {
			$response['errors'] = true;
			$response['message'] = sprintf(__('Webhooks are not enabled for %s', 'stops-core-theme-and-plugin-updates'), $eum_white_label);
			wp_send_json($response);
		}

		// Check for existence of hash
		if (!isset($webhook_options['eum_webhook_hash'])) {
			$response['errors'] = true;
			$response['message'] = sprintf(__('No hash found for webhooks in %s. Make sure you have enabled webhooks or copied the right hash.', 'stops-core-theme-and-plugin-updates'), $eum_white_label);
			wp_send_json($response);
		}

		// Check to see if hatches match
		$maybe_hash = sanitize_text_field($_GET['eum_webhook_hash']);
		if ($maybe_hash !== $webhook_options['eum_webhook_hash']) {
			$response['errors'] = true;
			$response['message'] = sprintf(__('Hash does not match webhooks in %s', 'stops-core-theme-and-plugin-updates'), $eum_white_label);
			wp_send_json($response);
		}

		// Get core options for more error checking
		$options = MPSUM_Updates_Manager::get_options('core');

		// Check to see if updates are turned off
		if ('off' === $options['all_updates']) {
			$response['errors'] = true;
			$response['message'] = __('Updates are turned off for this installation', 'stops-core-theme-and-plugin-updates');
			wp_send_json($response);
		}

		// Check to see if automatic updates are off
		if ('off' == $options['automatic_development_updates'] && 'off' == $options['automatic_major_updates'] && 'off' == $options['automatic_minor_updates'] && 'off' == $options['automatic_plugin_updates'] && 'off' == $options['automatic_theme_updates'] && 'off' == $options['automatic_translation_updates']) {
			$response['errors'] = true;
			$response['message'] = __('Automatic updates are turned off for this installation', 'stops-core-theme-and-plugin-updates');
			wp_send_json($response);
		}

		// Check to see if updates are off
		if ('off' == $options['core_updates'] || 'off' == $options['plugin_updates'] || 'off' == $options['theme_updates'] || 'off' == $options['translation_updates']) {
			$response['errors'] = true;
			$response['message'] = __('Theme, Plugin, Translations and Core updates are turned off for this installation', 'stops-core-theme-and-plugin-updates');
			wp_send_json($response);
		}

		// All clear, let's set a timestamp for throttling
		$timestamp = 0;
		if (isset($webhook_options['timestamp'])) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				// Debug mode doesn't have throttling
				$timestamp = time();
			} elseif ($webhook_options['timestamp'] > time()) {
				$response['errors'] = true;
				$response['timestamp'] = $webhook_options['timestamp'];
				$response['message'] = __('Updates requests are limited to once every five minutes', 'stops-core-theme-and-plugin-updates');
				wp_send_json($response);
			} else {
				// Throttle for five minutes
				$timestamp = time() + 300;
			}
		} else {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				// Debug mode doesn't have throttling
				$timestamp = time();
			} else {
				// Throttle for five minutes
				$timestamp = time() + 300;
			}
		}

		// Save the timestamp in options and response array
		$response['timestamp'] = $webhook_options['timestamp'] = $timestamp;
		update_site_option('easy_updates_manager_webhook', $webhook_options);

		// All is well, let's do some force updates
		/** This filter is documented in /includes/MPSUM_Admin_Ajax.php */
		if (apply_filters('eum_force_updates_disable_lock', false)) {
			delete_option('auto_updater.lock');
		}
		delete_site_transient('MPSUM_PLUGINS');
		delete_site_transient('MPSUM_THEMES');
		wp_maybe_auto_update();

		// Send valid response
		$response['ran_immediately'] = true;
		$response['message'] = __('Automatic updates have been run', 'stops-core-theme-and-plugin-updates');
		wp_send_json($response);
	}

	/**
	 * Handles all ajax requests and calls appropriate methods
	 *
	 * @param string $subaction Action to be executed
	 * @param array  $data      Passed data from ajax request
	 */
	public function ajax_handler($subaction, $data) {

		if (!method_exists($this, $subaction)) return;

		$data = isset($data) ? $data : array();
		$results = call_user_func(array($this, $subaction), $data);

		// For WP List Table extended class (plugins, themes) result is already returned.
		if (is_wp_error($results)) {
			$results = array(
				'result' => false,
				'error_code' => $results->get_error_code(),
				'error_message' => $results->get_error_message(),
				'error_data' => $results->get_error_data(),
			);
		}

		// if nothing was returned for some reason, set as result null.
		if (empty($results)) {
			$results = array(
				'result' => null
			);
		}

		$result = json_encode($results);

		$json_last_error = json_last_error();

		// if json_encode returned error then return error.
		if ($json_last_error) {
			$result = array(
				'result' => false,
				'error_code' => $json_last_error,
				'error_message' => 'json_encode error : '.$json_last_error,
				'error_data' => '',
			);

			$result = json_encode($result);
		}

		echo $result;

		die;
	}

	/**
	 * Formats HTML for WordPress Plugins
	 *
	 * @param string $plugins removed in HTML format
	 */
	private function check_plugins_message($plugins) {
		$html = '<ol>';
		foreach ($plugins as $plugin_meta) {
			$html .= sprintf('<li>%s</li>', esc_html($plugin_meta['Name']));
		}
		$html .= '</ol>';
		return $html;
	}

	/**
	 * Checks to see if plugins are in the WordPress Directory
	 *
	 * @param array $data {
	 * 		@type string $force - Whether to force checking. Can be 'true', or 'false'.
	 * }
	 *
	 * @return array Array of result of ajax call
	 */
	public function check_plugins($data) {
		$force = $data['force'];

		// Return plugins if caching - Ignore of force is set to true
		$plugins_removed_option = get_site_transient('eum_plugins_removed_from_directory');
		if (false !== $plugins_removed_option && is_array($plugins_removed_option) && 'false' === $force) {
			$return = array();
			if (!empty($plugins_removed_option)) {
				$return['errors'] = true;
				$return['message'] = sprintf(__('The following plugins have been removed from the WordPress Plugin Directory and may pose a security risk: %s', 'stops-core-theme-and-plugin-updates'), $this->check_plugins_message($plugins_removed_option));
			} else {
				$return['errors'] = false;
				$return['message'] = __('All WordPress Plugins have been checked and no errors have been found.', 'stops-core-theme-and-plugin-updates');
			}
			$return['plugins'] = $plugins_removed_option;
			return $return;
		}

		// Get array for plugins removed from directory
		$plugins_removed = array();

		// Get plugins for site
		$plugins = get_plugins();
		foreach ($plugins as $plugin_file => $plugin_meta) {

			$plugin_invalid = false;

			// Get the plugin slug
			$plugin_slug = trim(dirname($plugin_file));

			// Get option to check for plugins removed from the WordPress repo
			$plugin_removed_option = get_site_option('eum_plugin_removed_' . $plugin_slug);
			if (false !== $plugin_removed_option && !empty($plugin_removed_option) && 'true' === $plugin_removed_option && 'false' === $force) {
				$plugins_removed[] = $plugin_removed_option;
				continue;
			} elseif (false !== $plugin_removed_option && !empty($plugin_removed_option) && 'false' === $plugin_removed_option && 'false' === $force) {
				continue;
			} else {

				// Check to see if plugin in in SVN
				$svn_url = 'https://plugins.svn.wordpress.org/' . $plugin_slug . '/';
				$svn_check = wp_remote_get($svn_url);
				$svn_response_code = wp_remote_retrieve_response_code($svn_check);
				if (200 === $svn_response_code) {

					// Plugin exists, let's check the plugins API - Timeout is one hour
					$timeout = 60 * 60;
					$plugin_data_to_send = array(
						$plugin_file => $plugin_meta
					);
					$plugin_api_data = array();
					$plugin_api_data['plugins'] = $plugin_data_to_send;
					$plugin_api_options = array(
						'timeout' => $timeout,
						'body' => array(
							'plugins'      => wp_json_encode($plugin_api_data),
							'all'          => wp_json_encode(true),
						),
						'user-agent' => 'WordPress',
					);
					$http_url = 'http://api.wordpress.org/plugins/update-check/1.1/';
					if (wp_http_supports(array('ssl'))) {
						$http_url = set_url_scheme($http_url, 'https');
					}
					$raw_response = wp_remote_post($http_url, $plugin_api_options);
					if (!is_wp_error($raw_response) && 200 === wp_remote_retrieve_response_code($raw_response)) {
						$raw_body = json_decode(wp_remote_retrieve_body($raw_response));
						if (empty($raw_body->plugins) && empty($raw_body->translations) && empty($raw_body->no_update)) {

							// We may have a plugin not on the repo anymore
							// One last check - We should receive a 500 error code on the details page
							$wordpress_plugin_information_url = network_admin_url('plugin-install.php?tab=plugin-information&plugin=' . $plugin_slug);

							// Pass cookies
							$cookies = array();
							foreach ($_COOKIE as $name => $value) {
								$cookies[] = new WP_Http_Cookie(array( 'name' => $name, 'value' => $value ));
							}
							$wordpress_plugin_information_response = wp_remote_get($wordpress_plugin_information_url, array('cookies' => $cookies));
							if (500 === wp_remote_retrieve_response_code($wordpress_plugin_information_response)) {

								// Set an option that the plugin is invalid
								update_site_option('eum_plugin_removed_' . $plugin_slug, 'true');
								$plugins_removed[] = $plugin_meta;
								$plugin_invalid = true;
							}
						}
					}
				}
			}
			if (!$plugin_invalid) {
				update_site_option('eum_plugin_removed_' . $plugin_slug, 'false');
			}
		}
		set_site_transient('eum_plugins_removed_from_directory', $plugins_removed, 6 * 60 * 60);
		$return = array();
		if (!empty($plugins_removed)) {
			$return['errors'] = true;
			$return['message'] = sprintf(__('The following plugins have been removed from the WordPress Plugin Directory and may pose a security risk: %s', 'stops-core-theme-and-plugin-updates'), $this->check_plugins_message($plugins_removed));
		} else {
			$return['errors'] = false;
			$return['message'] = __('All WordPress Plugins have been checked and no errors have been found.', 'stops-core-theme-and-plugin-updates');
		}
		$return['plugins'] = $plugins_removed;
		return $return;
	}

	/**
	 * Saves cron schedule options to database and schedules cron events
	 *
	 * @param array $data Updated cron schedule data to save
	 *
	 * @return string Next schedule's date and time
	 */
	public function save_cron_schedule($data) {

		if (!current_user_can('manage_options')) return;

		parse_str($data, $updated_options);

		// Save options
		$options = MPSUM_Updates_Manager::get_options('advanced');
		$eum_cron_schedule = isset($updated_options['eum_cron_schedule']) ? $updated_options['eum_cron_schedule'] : 'twicedaily';
		$eum_cron_time = isset($updated_options['eum_cron_time']) ? $updated_options['eum_cron_time'] : '00:00';
		$eum_cron_week = isset($updated_options['eum_cron_week']) ? $updated_options['eum_cron_week'] : '1st';
		$eum_cron_week_day = isset($updated_options['eum_cron_week_day']) ? $updated_options['eum_cron_week_day'] : '1';
		$eum_cron_day_number = isset($updated_options['eum_cron_day_number']) ? $updated_options['eum_cron_day_number'] : '1';
		$eum_cron_days_list = isset($updated_options['eum_cron_days_list']) && is_array($updated_options['eum_cron_days_list']) ? $updated_options['eum_cron_days_list'] : array();
		$options['cron_schedule'] = sanitize_text_field($eum_cron_schedule);
		$options['cron_time'] = sanitize_text_field($eum_cron_time);
		$options['cron_week'] = sanitize_text_field($eum_cron_week);
		$options['cron_week_day'] = sanitize_text_field($eum_cron_week_day);
		$options['cron_day_number'] = sanitize_text_field($eum_cron_day_number);
		$options['cron_days_list'] = $eum_cron_days_list;
		MPSUM_Updates_Manager::update_options($options, 'advanced');

		// Set up new cron events
		$cron = MPSUM_Update_Cron::get_instance();
		$cron->set_cron_events();
		$result = array();
		$options = MPSUM_Updates_Manager::get_options('core');
		$options['next_scheduled_event'] = $cron->calculate_next_event();
		MPSUM_Updates_Manager::update_options($options, 'core');
		$result['time'] = get_date_from_gmt(date('Y-m-d H:i:s', $cron->calculate_next_event())). ' '.get_option('timezone_string');
		$result['message'] = __('Cron schedule has been updated.', 'stops-core-theme-and-plugin-updates');
		return $result;
	}

	public function save_clear_log_schedule($updated_options) {

		if (!current_user_can('manage_options')) return;

		$schedule = isset($updated_options['clear-logs']) && absint($updated_options['clear-logs']) >= 0 ? $updated_options['clear-logs'] : 0;
		if (0 === $schedule) {
			return __('Your logs will not expire automatically', 'stops-core-theme-and-plugin-updates');
		}
		$options = MPSUM_Updates_Manager::get_options('logs');
		$options['clear_logs'] = $schedule;
		MPSUM_Updates_Manager::update_options($options, 'logs');
		$clear_logs_cron = MPSUM_Clear_Logs_Cron::get_instance();
		$clear_logs_cron->remove_clear_logs_event();
		if (absint($updated_options['clear-logs']) > 0) {
			$clear_logs_cron::$schedule = $schedule;
			$clear_logs_cron->schedule_clear_logs_event();
			$message = __('Log clearing has been scheduled.', 'stops-core-theme-and-plugin-updates');
		} else {
			$message = __('Your logs will not expire automatically', 'stops-core-theme-and-plugin-updates');
		}

		return $message;
	}

	/**
	 * Save log settings
	 *
	 * @param array $data An array of posted data
	 * @return string
	 */
	public function save_logs_settings($data) {
		if (!current_user_can('manage_options')) return 'Security check.';
		parse_str($data, $updated_options);
		$this->save_clear_log_schedule($updated_options);
		$this->save_external_log_settings($updated_options);
		$message = __('Log settings has been saved.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Saves external logging settings
	 *
	 * @param array $updated_options An array of updated options
	 */
	public function save_external_log_settings($updated_options) {
		if (!current_user_can('manage_options')) return 'Security check.';
		$eum_logger_type = isset($updated_options['eum_logger_type']) ? $updated_options['eum_logger_type'] : array();
		$options = MPSUM_Updates_Manager::get_options('logs');
		$logger_additional_options = isset($updated_options['logger_additional_options']) ? $updated_options['logger_additional_options'] : '';
		$options['eum_logger_type'] = $eum_logger_type;
		$options['logger_additional_options'] = $logger_additional_options;
		MPSUM_Updates_Manager::update_options($options, 'logs');
	}

	/**
	 * Saves delay updates option
	 *
	 * @param string $data Updated delay updates data to save
	 *
	 * @return string A response message for ajax call
	 */
	public function save_delay_updates($data) {
		if (!current_user_can('manage_options')) return 'Security check.';
		parse_str($data, $updated_options);
		$schedule = isset($updated_options['delay-updates']) && absint($updated_options['delay-updates']) >= 0 ? $updated_options['delay-updates'] : 0;
		$options = MPSUM_Updates_Manager::get_options('advanced');
		$options['delay_updates'] = $schedule;
		MPSUM_Updates_Manager::update_options($options, 'advanced');
		$message = __('Update delay has been saved.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Enables Auto Backup
	 *
	 * @return string Returns success message.
	 */
	public function enable_auto_backup() {
		if (!current_user_can('manage_options')) return 'Security check.';
		$options = MPSUM_Updates_Manager::get_options('advanced');
		if (empty($options)) {
			$options = MPSUM_Admin_Core::get_defaults();
		}
		$options['auto_backup'] = 'on';
		MPSUM_Updates_Manager::update_options($options, 'advanced');
		$message = __('Auto backup before updates has been enabled.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Disables Auto Backup
	 *
	 * @return string Returns success message.
	 */
	public function disable_auto_backup() {
		if (!current_user_can('manage_options')) return 'Security check.';
		$options = MPSUM_Updates_Manager::get_options('advanced');
		if (empty($options)) {
			$options = MPSUM_Admin_Core::get_defaults();
		}
		$options['auto_backup'] = 'off';
		MPSUM_Updates_Manager::update_options($options, 'advanced');
		$message = __('Auto backup before updates has been disabled.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Saves updated anonymize update settings
	 *
	 * @param array $data An array of updated options
	 *
	 * @return string Returns success message
	 */
	public function save_anonymize_updates($data) {
		if (!current_user_can('manage_options')) return 'Security check.';
		parse_str($data, $updated_options);
		// Save options
		$options = MPSUM_Updates_Manager::get_options('advanced');
		$anonymize_updates = isset($updated_options['anonymize_updates']) ? $updated_options['anonymize_updates'] : 'default';
		$options['anonymize_updates'] = sanitize_text_field($anonymize_updates);
		MPSUM_Updates_Manager::update_options($options, 'advanced');
		$message = __('Anonymize updates option saved.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Exports plugin settings
	 *
	 * @return string A json encoded string of exported options
	 */
	public function export_settings() {
		if (!current_user_can('manage_options')) return 'Security check.';
		$options = MPSUM_Updates_Manager::get_options();
		$options_to_export = array();
		$url = '';
		if (is_multisite()) {
			$url = network_site_url();
		} else {
			$url = site_url();
		}
		$options_to_export['meta']['site_url'] = $url;
		// Indicate the last time the format changed - i.e. do not update this unless there is a format change
		$options_to_export['meta']['version'] = '7.0.5';
		$options_to_export['meta']['date'] = date("Y-m-d H:i:s");

		foreach ($options as $key => $value) {
			$value = is_serialized($value) ? MPSUM_Updates_Manager::unserialize($value) : $value;
			$options_to_export['data'][$key] = $value;
		}

		// Include white label
		$options_to_export['data']['whitelabel']['notices'] = get_site_option('easy_updates_manager_enable_notices', 'on');
		$options_to_export['data']['whitelabel']['author'] = get_site_option('easy_updates_manager_author', __('Easy Updates Manager Team', 'stops-core-theme-and-plugin-updates'));
		$options_to_export['data']['whitelabel']['url'] = get_site_option('easy_updates_manager_url', esc_url('https://easyupdatesmanager.com/'));
		$options_to_export['data']['whitelabel']['label'] = get_site_option('easy_updates_manager_name', __('Easy Updates Manager Premium', 'stops-core-theme-and-plugin-updates'));

		$json_file = json_encode($options_to_export);
		return $json_file;
	}

	/**
	 * Imports plugin settings
	 *
	 * @param array $data An array of options to import
	 *
	 * @return string A success message
	 */
	public function import_settings($data) {
		if (!current_user_can('manage_options')) return 'Security check.';
		if (isset($data['whitelabel'])) {
			update_site_option('easy_updates_manager_enable_notices', $data['whitelabel']['notices']);
			update_site_option('easy_updates_manager_author', $data['whitelabel']['author']);
			update_site_option('easy_updates_manager_url', $data['whitelabel']['url']);
			update_site_option('easy_updates_manager_name', $data['whitelabel']['label']);
			unset($data['whitelabel']);
		}
		MPSUM_Updates_Manager::update_options($data);
		$message = __('Settings imported successfully.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Enables Safe Mode
	 *
	 * @return string Return safe mode success message.
	 */
	public function enable_safe_mode() {
		if (!current_user_can('manage_options')) return 'Security check.';
		$options = MPSUM_Updates_Manager::get_options('core');
		if (empty($options)) {
			$options = MPSUM_Admin_Core::get_defaults();
		}
		$options['safe_mode'] = 'on';
		MPSUM_Updates_Manager::update_options($options, 'core');
		$message = __('Safe mode has been enabled.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Disables Safe Mode
	 *
	 * @return string Return safe mode disabled message.
	 */
	public function disable_safe_mode() {
		if (!current_user_can('manage_options')) return 'Security check.';
		$options = MPSUM_Updates_Manager::get_options('core');
		if (empty($options)) {
			$options = MPSUM_Admin_Core::get_defaults();
		}
		$options['safe_mode'] = 'off';
		MPSUM_Updates_Manager::update_options($options, 'core');
		$message = __('Safe mode has been disabled.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Enables Version Control Protection
	 *
	 * @return string Return version control success message.
	 */
	public function enable_version_control_protection() {
		if (!current_user_can('manage_options')) return 'Security check.';
		$options = MPSUM_Updates_Manager::get_options('core');
		if (empty($options)) {
			$options = MPSUM_Admin_Core::get_defaults();
		}
		$options['version_control'] = 'on';
		MPSUM_Updates_Manager::update_options($options, 'core');
		$message = __('Version control protection has been enabled.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Disables Version Control Protection
	 *
	 * @return string Return version control success message.
	 */
	public function disable_version_control_protection() {
		if (!current_user_can('manage_options')) return 'Security check.';
		$options = MPSUM_Updates_Manager::get_options('core');
		if (empty($options)) {
			$options = MPSUM_Admin_Core::get_defaults();
		}
		$options['version_control'] = 'off';
		MPSUM_Updates_Manager::update_options($options, 'core');
		$message = __('Version control protection has been disabled.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}
	
	/**
	 * Enables Unmaintained plugins check
	 *
	 * @return string Return success message.
	 */
	public function enable_unmaintained_plugins() {
		if (!current_user_can('manage_options')) return 'Security check.';
		$options = MPSUM_Updates_Manager::get_options('core');
		if (empty($options)) {
			$options = MPSUM_Admin_Core::get_defaults();
		}
		$options['unmaintained_plugins'] = 'on';
		MPSUM_Updates_Manager::update_options($options, 'core');
		$message = __('The unmaintained plugins check has been enabled.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Disables Unmaintained plugins check
	 *
	 * @return string Return success message.
	 */
	public function disable_unmaintained_plugins() {
		if (!current_user_can('manage_options')) return 'Security check.';
		$options = MPSUM_Updates_Manager::get_options('core');
		if (empty($options)) {
			$options = MPSUM_Admin_Core::get_defaults();
		}
		$options['unmaintained_plugins'] = 'off';
		MPSUM_Updates_Manager::update_options($options, 'core');
		$message = __('The unmaintained plugins check has been disabled.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Enables the webhook
	 *
	 * @return array Webhook URL and message
	 */
	public function enable_webhook() {
		if (!current_user_can('manage_options')) die('Security check.');
		$hook_hash = wp_generate_password(25, false, false);
		$admin_url = admin_url('admin-ajax.php');
		$ajax_url = add_query_arg(array( 'eum_webhook_hash' => $hook_hash, 'action' => 'eum_webhook' ), $admin_url);
		$option = array(
			'enabled'             => 'true',
			'eum_webhook_hash'    => $hook_hash,
			'hook_url'            => $ajax_url,
		);
		update_site_option('easy_updates_manager_webhook', $option);
		$response = array(
			'hook_url' => $ajax_url,
			'message'  => __('Webhook enabled successfully', 'stops-core-theme-and-plugin-updates'),
		);
		return $response;
	}

	/**
	 * Disables the webhook
	 *
	 * @return string A success message
	 */
	public function disable_webhook() {
		if (!current_user_can('manage_options')) die('Security check.');
		delete_site_option('easy_updates_manager_webhook');
		$message = __('Webhook disabled successfully.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * White lists the site
	 *
	 * @param array $data An array of options
	 *
	 * @return string A success message
	 */
	public function whitelist_save($data) {
		if (!current_user_can('manage_options')) die('Security check.');
		$plugin_name = trim(sanitize_text_field($data['plugin_name']));
		$plugin_author = trim(sanitize_text_field($data['plugin_author']));
		$plugin_url = trim(sanitize_text_field($data['plugin_url']));
		$notices = sanitize_text_field($data['notices']);

		// Check plugin name
		if (empty($plugin_name)) {
			update_site_option('easy_updates_manager_name', __('Easy Updates Manager Premium', 'stops-core-theme-and-plugin-updates'));
		} else {
			update_site_option('easy_updates_manager_name', $plugin_name);
		}

		// Check plugin author
		if (empty($plugin_author)) {
			update_site_option('easy_updates_manager_author', __('Easy Updates Manager Team', 'stops-core-theme-and-plugin-updates'));
		} else {
			update_site_option('easy_updates_manager_author', $plugin_author);
		}

		// Check plugin URL
		if (empty($plugin_url)) {
			update_site_option('easy_updates_manager_url', 'https://easyupdatesmanager.com/');
		} else {
			update_site_option('easy_updates_manager_url', $plugin_url);
		}

		// Check notices - 'true' being default
		if ('false' === $notices || empty($notices)) {
			update_site_option('easy_updates_manager_enable_notices', 'off');
		} else {
			update_site_option('easy_updates_manager_enable_notices', 'on');
		}

		$message = __('White-label settings saved successfully.', 'stops-core-theme-and-plugin-updates');
		return $message;
	}

	/**
	 * Resets Whitelist Options
	 *
	 * @return string $return json options
	 */
	public function whitelist_reset() {

		if (!current_user_can('manage_options')) die('Security check.');

		// Remove whitelist
		delete_site_option('easy_updates_manager_enable_notices');
		delete_site_option('easy_updates_manager_name');
		delete_site_option('easy_updates_manager_author');
		delete_site_option('easy_updates_manager_url');

		// Return data
		$return = array();
		$return['name'] = __('Easy Updates Manager Premium', 'stops-core-theme-and-plugin-updates');
		$return['url'] = 'https://easyupdatesmanager.com';
		$return['author'] = __('Easy Updates Manager Team', 'stops-core-theme-and-plugin-updates');
		$return['message'] = __('White-label settings have been reset.', 'stops-core-theme-and-plugin-updates');

		return $return;
	}
}
