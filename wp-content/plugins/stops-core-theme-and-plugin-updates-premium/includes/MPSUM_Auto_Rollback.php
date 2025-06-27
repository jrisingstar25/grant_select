<?php
if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_Auto_Rollback')) return;

/**
 * Class MPSUM_Auto_Rollback rolls back updates if plugins or themes fail
 */
class MPSUM_Auto_Rollback {

	protected $update_results = null;

	protected $is_in_critical_section = false;

	protected $scrape_key = '';

	protected $is_activating_plugin = false;

	protected $unprocessed_plugins_count = 0;

	protected $sandbox = false;

	/**
	 * Returns singleton instance of this class
	 *
	 * @return object MPSUM_Auto_Rollback Singleton Instance
	 */
	public static function get_instance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Adds necessary filters and actions
	 */
	private function __construct() {
		register_shutdown_function(array($this, 'perform_shutdown_task'));
		$this->update_results = get_site_option('eum_unproven_updates_post_install', array());
		// if WP_SANDBOX_SCRAPING constant is defined and is set to true, that means WP has turned into an errors scraping mode (via `wp_start_scraping_edited_file_errors()` wp-settings.php) where PHP fatals errors are no longer handled by the WP Error Protection API, but instead are directly wrote to a client's standard output and can be retrieved as a JSON object for variety of purposes
		// normally, when a scraping request is sent to the server, it must at least includes two mandatory query arguments (wp_scrape_key and wp_scrape_nonce), and since the scraper may come from other plugins it must be validated to see whether the scraper is legit from EUM
		if (defined('WP_SANDBOX_SCRAPING') && WP_SANDBOX_SCRAPING && isset($_REQUEST['wp_scrape_key']) && isset($_REQUEST['wp_scrape_nonce']) && isset($_REQUEST['wp_scrape_action']) && 'eum_activate_plugins' === $_REQUEST['wp_scrape_action']) {
			if (get_transient('scrape_key_'.substr(sanitize_key(wp_unslash($_REQUEST['wp_scrape_key'])), 0, 32)) !== wp_unslash($_REQUEST['wp_scrape_nonce'])) return;
			$this->sandbox = true;
			$this->scrape_key = substr(sanitize_key(wp_unslash($_REQUEST['wp_scrape_key'])), 0, 32);
			if (!empty($this->update_results['plugin']) && is_array($this->update_results['plugin'])) $this->unprocessed_plugins_count = count($this->update_results['plugin']); // unprocessed plugins count (the plugin on queue which are waiting to be processed)
			// no need to acquire the auto_updater lock as this request comes from a scraper that has acquired the lock from within MPSUM_Auto_Rollback::perform_plugin_activations() method
			$old_error_level = error_reporting(E_CORE_ERROR | E_COMPILE_ERROR | E_ERROR | E_PARSE | E_USER_ERROR | E_RECOVERABLE_ERROR); // PHP notices and warnings may prevent us from getting the PHP fatal error message as they can appear after a fatal error thus giving unexpected return when issuing PHP native error_get_last() function, so from here we expect only fatal error reports
			if (is_multisite()) switch_to_blog(get_main_site_id(get_main_network_id()));
			$this->activate_unproven_plugin_updates($this->update_results);
			if (is_multisite()) restore_current_blog();
			error_reporting($old_error_level); // set error reporting level back to its previous error level value
			return;
		} elseif (!empty($this->update_results)) {
			$this->perform_plugin_activations($this->update_results);
		}
		add_action('pre_auto_update', array($this, 'deactivate_plugin_before_update'), PHP_INT_MAX-1, 2);
		add_action('automatic_updates_complete', array($this, 'perform_plugin_activations'), 10, 1);
	}

	/**
	 * Deactivate plugin before update
	 *
	 * @param string $type The type of update being checked: 'core', 'theme', 'plugin', or 'translation'.
	 * @param object $item The update offer.
	 */
	public function deactivate_plugin_before_update($type, $item) {
		if ('plugin' === $type && isset($item->plugin) && $item->plugin) {
			if (in_array($item->plugin, apply_filters('eum_do_not_deactivate_plugins', array(MPSUM_Updates_Manager::get_plugin_basename())))) return; // do not deactivate the EUM plugin
			$active_plugins_multisite = array();
			if (is_multisite()) $active_plugins_multisite = MPSUM_Utils::get_instance()->get_network_active_plugins();
			$active_plugins_single_site = MPSUM_Utils::get_instance()->get_single_site_active_plugins();
			if (empty($this->update_results['plugin']) || !is_array($this->update_results['plugin'])) $this->update_results['plugin'] = array();
			$pre_auto_update_plugin = new stdClass;
			$pre_auto_update_plugin->item = $item;
			if (in_array($item->plugin, $active_plugins_single_site, true)) $pre_auto_update_plugin->eum_is_active = true;
			if (in_array($item->plugin, $active_plugins_multisite, true)) $pre_auto_update_plugin->eum_is_network_active = true;
			if (empty($this->update_results['plugin'])) {
				$this->update_results['plugin'][] = $pre_auto_update_plugin;
			} else {
				foreach ($this->update_results['plugin'] as $key => $plugin) {
					if (isset($plugin->item, $plugin->item->plugin) && $plugin->item->plugin === $item->plugin) break;
					if (count($this->update_results['plugin']) === (int) $key + 1) $this->update_results['plugin'][] = $pre_auto_update_plugin;
				}
			}
			$this->is_in_critical_section = true;
			update_site_option('eum_unproven_updates_post_install', $this->update_results);
			deactivate_plugins($item->plugin, true);
		}
	}

	/**
	 * Perform PHP fatal error checking and/or release auto_updater lock on shutdown
	 */
	public function perform_shutdown_task() {
		if ($this->is_in_critical_section) {
			// if is_in_critical_section is still true that means PHP time out, out of memory, or any PHP fatal error that terminates the script execution has just occurred, if so then the auto_updater lock needs to be released immediately, so that other processes can perform plugin activation without having to wait for some time
			if (!class_exists('WP_Upgrader')) require_once(ABSPATH.'wp-admin/includes/class-wp-upgrader.php');
			WP_Upgrader::release_lock('auto_updater');
		}
		if ($this->sandbox && '' !== $this->scrape_key) {
			echo "\n###### eum_scraping_result_start:{$this->scrape_key} ######\n";
			if ($this->is_activating_plugin) {
				// we only deal with errors that occur after WP's activate_plugin() function gets executed, and if is_activating_plugin is true that means a PHP fatal error has just occurred
				$error = error_get_last();
				if (!empty($error) && in_array($error['type'], array(E_CORE_ERROR, E_COMPILE_ERROR, E_ERROR, E_PARSE, E_USER_ERROR, E_RECOVERABLE_ERROR), true)) {
					echo wp_json_encode($error);
				} else {
					echo wp_json_encode(array(
						'type' => isset($error['type']) ? $error['type'] : 0,
						'message' => isset($error['message']) ? $error['message'] : 'Unknown Error',
						'file' => isset($error['file']) ? $error['file'] : '',
						'line' => isset($error['line']) ? $error['line'] : '',
					));
				}
			} elseif (0 === $this->unprocessed_plugins_count) {
				echo wp_json_encode(true);
			}
			echo "\n###### eum_scraping_result_end:{$this->scrape_key} ######\n";
		}
	}
	
	/**
	 * Perform plugin activations and send an email notice on fatal error
	 *
	 * @param array $update_results The results of all attempted updates
	 */
	public function perform_plugin_activations($update_results) {
		if (empty($update_results['plugin']) || !is_array($update_results)) return;
		// since this will do other plugin activations when users visit the site, so it can only be done on the main network is_main_network() and in its main site is_main_site() (unless we switch_to_blog)
		if (is_multisite()) switch_to_blog(get_main_site_id(get_main_network_id()));
		if (!class_exists('WP_Upgrader')) require_once(ABSPATH.'wp-admin/includes/class-wp-upgrader.php');
		if (!doing_action('automatic_updates_complete') && !WP_Upgrader::create_lock('auto_updater', 60*15)) {
			// if it cannot acquire the auto_updater lock then probably automatic update cron job is still running in the background, or another process is still acquiring the lock
			// when plugin updates are served from the same server where the plugin is installed, downloading them will return HTTP 503 status (Service Unavailable) because the request has been blocked by the below wp_die(), this is actually the same thing that happens when issuing WP's maintenance mode where updates are served in the same server, basically we just want users see the maintenance warning when they access the web via browser, so we check the user agent
			if (isset($_SERVER['HTTP_USER_AGENT']) && false !== stripos($_SERVER['HTTP_USER_AGENT'], 'wordpress')) return;
			wp_die(__('Briefly unavailable for scheduled maintenance.', 'stops-core-theme-and-plugin-updates').' '.__('Check back in a minute.', 'stops-core-theme-and-plugin-updates'), __('Maintenance — Easy Updates Manager', 'stops-core-theme-and-plugin-updates'), array('response' => 503, 'exit' => true));
		}
		$this->is_in_critical_section = true;
		$unproven_updates = get_site_option('eum_unproven_updates_post_install', $update_results);
		foreach ((array) $unproven_updates as $type => $results) {
			if ('plugin' !== $type || !is_array($results)) continue;
			foreach ($results as $plugin1) {
				if (!isset($plugin1->item, $plugin1->item->plugin)) continue;
				if (empty($update_results['plugin'])) {
					$update_results['plugin'][] = $plugin1;
				} else {
					foreach ($update_results['plugin'] as $key2 => $plugin2) {
						if (isset($plugin2->item, $plugin2->item->plugin) && $plugin2->item->plugin === $plugin1->item->plugin) {
							if (isset($plugin1->eum_is_active)) $update_results['plugin'][$key2]->eum_is_active = $plugin1->eum_is_active;
							if (isset($plugin1->eum_is_network_active)) $update_results['plugin'][$key2]->eum_is_network_active = $plugin1->eum_is_network_active;
							continue 2;
						}
						if (count($update_results['plugin']) === (int) $key2 + 1) $update_results['plugin'][] = $plugin1;
					}
				}
			}
		}
		update_site_option('eum_unproven_updates_post_install', $update_results);
		$white_screen_check = $this->scrape_site_for_errors('eum_activate_plugins');
		if (true !== $white_screen_check && is_array($white_screen_check) && isset($white_screen_check['message'])) {
			global $wpdb;
			if (isset($white_screen_check['code']) && in_array($white_screen_check['code'], array('loopback_request_failed', 'json_parse_error'), true)) { // got an error before activating plugins
				// if scraping the site doesn't return 200 (success) HTTP response code (e.g. 401 Unauthorize, 301 Moved Permanently, 408 Request Timeout), or if it's 200 but failed to parse the JSON output from the HTTP response body, then just reactivate the plugins back without further checking for a fatal error
				// doing this could lead to "white screen of death" as the plugins may have a buggy line that could throw a PHP fatal error but it's just that before reaching the line for plugin activation we're encountering a fatal error that's not caused by the plugins, but we've heard from the support, some users don't want to be sent an email asking them to check their site and reactivate plugins even though there's no issue with the plugins
				$update_results = array();
				if (is_multisite()) {
					$row = $wpdb->get_row($wpdb->prepare("SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", 'eum_unproven_updates_post_install', (int) get_current_network_id()));
					if (is_object($row) && isset($row->meta_value)) $update_results = MPSUM_Updates_Manager::unserialize($row->meta_value);
				} else {
					$row = $wpdb->get_row($wpdb->prepare("SELECT option_value from $wpdb->options where option_name = %s LIMIT 1", 'eum_unproven_updates_post_install'));
					if (is_object($row) && isset($row->option_value)) $update_results = MPSUM_Updates_Manager::unserialize($row->option_value);
				}
				// sometimes an unexpected HTTP status code may temporarily happen for a moment, for example it can happen due to overload leading to website down
				// however, the choices we've got here is whether we should just activate it or give it several attempts as scraping might not work at first try but works the second try
				// basically, from here when plugins get activated using the code below then it will be activating the old plugins that are already loaded into the script's memory not the updated ones
				$this->activate_unproven_plugin_updates($update_results);
			} else { // got an error when trying to activate a plugin
				$options = MPSUM_Updates_Manager::get_options('core');
				$active_plugins = $active_sitewide_plugins = array();
				if (is_multisite()) {
					// must use direct SQL query via WPDB so that we always get the actual value of active_plugins and/or active_sitewide_plugins options. We can't rely on WP options API (i.e. get_option) and/or is_plugin_active() and is_plugin_active_for_network() functions as they might return an outdated value due to caching mechanism
					$row = $wpdb->get_row($wpdb->prepare("SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", 'active_sitewide_plugins', (int) get_current_network_id()));
					if (is_object($row) && isset($row->meta_value)) $active_sitewide_plugins = MPSUM_Updates_Manager::unserialize($row->meta_value);
				}
				// must use direct SQL query via WPDB so that we always get the actual value of active_plugins and/or active_sitewide_plugins options. We can't rely on WP options API (i.e. get_option) and/or is_plugin_active() and is_plugin_active_for_network() functions as they might return an outdated value due to caching mechanism
				$row = $wpdb->get_row($wpdb->prepare("SELECT option_value from $wpdb->options where option_name = %s LIMIT 1", 'active_plugins'));
				if (is_object($row) && isset($row->option_value)) $active_plugins = MPSUM_Updates_Manager::unserialize($row->option_value);
				foreach ($update_results as $type => $results) {
					if ('plugin' === $type && !empty($results)) {
						foreach ($results as $key => $plugin) {
							// check whether or not the plugin has been activated, or whether the plugin should stay inactive due to its original setting
							if (!isset($plugin->item, $plugin->item->plugin) || in_array($plugin->item->plugin, $active_plugins, true) || isset($active_sitewide_plugins[$plugin->item->plugin]) || ((!isset($plugin->eum_is_network_active) || false === $plugin->eum_is_network_active) && (!isset($plugin->eum_is_active) || false === $plugin->eum_is_active))) {
								// remove the plugin that was successfully activated from the list, or remove one that doesn't need an activation
								unset($update_results['plugin'][$key]);
							}
						}
						$update_results['plugin'] = array_values($update_results['plugin']); // array reindexing
						$failed_plugin_activation = array_splice($update_results['plugin'], 0, 1); // move out the first index of $update_results['plugin'] array, which is the plugin that can't be activated
						// it's better to update the eum_unproven_updates_post_install option before attempting to send an email instead of updating it after the email is sent. We actually dont want spamming the user with lots of emails when time out or out of memory error orccurs
						update_site_option('eum_unproven_updates_post_install', $update_results); // update option without that failed plugin, so that we can try reactivating other plugins the next time the site gets visited
						if (!empty($failed_plugin_activation[0])) array_unshift($update_results['plugin'], $failed_plugin_activation[0]); // move the failed plugin back into $update_results['plugin'] for email reporting purpose
						if (!empty($update_results['plugin']) && apply_filters('eum_rollback_allow_send_email', !isset($options['rollback_updates_notification_emails']) || 'off' !== $options['rollback_updates_notification_emails'])) $this->send_notification_emails(array('plugin' => $update_results['plugin']), $white_screen_check);
						unset($plugin, $results);
					}
				}
				unset($update_results);
			}
		}
		// it's already at the end of a critical section, so we set is_in_critical_section false, and after leaving this method if it's still doing the automatic_updates_complete action, then it's up to the other methods as to how they want the auto_updater lock to be treated
		$this->is_in_critical_section = false;
		if (!doing_action('automatic_updates_complete')) WP_Upgrader::release_lock('auto_updater');
		if (is_multisite()) restore_current_blog();
	}

	/**
	 * Try activating unproven plugin updates to see whether the activation succeeds or throws a fatal error
	 * Due to atomic action it requires, this method should be called from within the MPSUM_Auto_Rollback::perform_plugin_activations() method only, any calls from outsite that method will need to acquire the auto_updater lock first
	 *
	 * @param array $update_results The results of all attempted updates
	 */
	protected function activate_unproven_plugin_updates($update_results) {
		if (empty($update_results['plugin']) || !is_array($update_results['plugin'])) return;
		foreach ($update_results as $type => $results) {
			if ('plugin' === $type && !empty($results)) {
				global $wpdb;
				if (!function_exists('activate_plugin')) require_once(ABSPATH.'wp-admin/includes/plugin.php');
				$active_plugins = $active_sitewide_plugins = array();
				// must use direct SQL query via WPDB so that we always get the actual value of active_plugins and/or active_sitewide_plugins options. We can't rely on WP options API (i.e. get_option) and/or is_plugin_active() and is_plugin_active_for_network() functions as they might return an outdated value due to caching mechanism
				if (is_multisite()) {
					$row = $wpdb->get_row($wpdb->prepare("SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", 'active_sitewide_plugins', (int) get_current_network_id()));
					if (is_object($row) && isset($row->meta_value)) $active_sitewide_plugins = MPSUM_Updates_Manager::unserialize($row->meta_value);
				}
				$row = $wpdb->get_row($wpdb->prepare("SELECT option_value from $wpdb->options where option_name = %s LIMIT 1", 'active_plugins'));
				if (is_object($row) && isset($row->option_value)) $active_plugins = MPSUM_Updates_Manager::unserialize($row->option_value);
				if (class_exists('WP_Plugin_Dependencies') && is_callable(array('WP_Plugin_Dependencies', 'initialize'))) WP_Plugin_Dependencies::initialize();

				$j=1;
				while (!empty($results)) {
					$key = key($results);
					$plugin = current($results);
					$simplified_results = wp_list_pluck(wp_list_pluck($results, 'item'), 'plugin', 'slug');
					if (isset($plugin->item, $plugin->item->plugin) && !in_array($plugin->item->plugin, $active_plugins, true) && !isset($active_sitewide_plugins[$plugin->item->plugin])) {
						if (class_exists('WP_Plugin_Dependencies') && is_callable('WP_Plugin_Dependencies', 'get_dependency_names') && array_intersect_key($simplified_results, WP_Plugin_Dependencies::get_dependency_names($plugin->item->plugin))) {
							if (count($results) < $j) break; // it's not possible to have two or more plugins having dependencies each other, not sure if that could happen but here we prevent infinite loop from happening
							$plugin_dependent = array_shift($results);
							$results[] = $plugin_dependent;
							$j++;
							continue;
						}
						// if a plugin activation goes wrong due to a fatal error then the script will stop immediately thus giving only one plugin activation at a time, even though there's more than one plugin updates in the $update_results
						// but the next time the site gets visited, EUM will automatically activate and check the other plugins for errors via eum_unproven_updates_post_install option
						if (is_multisite() && isset($plugin->eum_is_network_active) && true === $plugin->eum_is_network_active) {
							$this->is_activating_plugin = true;
							activate_plugin($plugin->item->plugin, '', true, true); // main network (active_sitewide_plugins)
							$this->is_activating_plugin = false;
						}
						if (isset($plugin->eum_is_active) && true === $plugin->eum_is_active) {
							$this->is_activating_plugin = true;
							activate_plugin($plugin->item->plugin, '', false, true); // multi-site (main site) or single site (active_plugins)
							$this->is_activating_plugin = false;
						}
					}
					unset($update_results['plugin'][$key]);
					$this->unprocessed_plugins_count = count($update_results['plugin']);
					unset($results[$key]);
					$j=1;
				}
				$update_results['plugin'] = array_values($update_results['plugin']);
				update_site_option('eum_unproven_updates_post_install', $update_results);
				unset($plugin, $results);
			}
			// we currently don't yet have a specific action for theme errors, so for now let WP_Recovery_Mode take care of them
		}
		unset($update_results);
	}

	/**
	 * Scrape the site for errors while performing an action
	 *
	 * @since 8.0.1
	 * @access private
	 * @param string $action an action to perform
	 * @return mixed true if everything okay, array if not
	 */
	protected function scrape_site_for_errors($action = '') {
		
		$scrape_key = md5(rand());
		$transient = 'scrape_key_' . $scrape_key;
		$scrape_nonce = strval(rand());
		set_transient($transient, $scrape_nonce, 60); // It shouldn't take more than 60 seconds to make one loopback request.
		
		$needle_start = "###### eum_scraping_result_start:$scrape_key ######";
		$needle_end = "###### eum_scraping_result_end:$scrape_key ######";
		$scrape_params = array(
			'wp_scrape_key' => $scrape_key,
			'wp_scrape_nonce' => $scrape_nonce,
		);
		if (!empty($action)) $scrape_params['wp_scrape_action'] = $action;
		$headers = array(
			'Cache-Control' => 'no-cache',
		);

		$cookies = wp_unslash($_COOKIE);

		$sslverify = apply_filters('https_local_ssl_verify', false);

		// in future, we might want to check what authorization is currently being used (if any), it could be either basic or digest?, but we only deal with basic auth for now
		if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
			$headers['Authorization'] = 'Basic ' . base64_encode(wp_unslash($_SERVER['PHP_AUTH_USER']) . ':' . wp_unslash($_SERVER['PHP_AUTH_PW']));
		}

		// Make sure PHP process doesn't die before loopback requests complete.
		@set_time_limit(300);

		// Time to wait for loopback requests to finish.
		$timeout = 30;

		// Setup some failure variables
		$loopback_request_failure = array(
			'code' => 'loopback_request_failed',
			'message' => __('Unable to communicate back with site to check for fatal errors.', 'stops-core-theme-and-plugin-updates').' '.__('You will want to update your assets manually.', 'stops-core-theme-and-plugin-updates'),
		);
		$json_parse_failure = array(
			'code' => 'json_parse_error',
			'message' => __("Expression evaluation failed.", 'stops-core-theme-and-plugin-updates').' '.__("The JSON payload can't be parsed", 'stops-core-theme-and-plugin-updates'),
		);

		// Set and perform loopback
		$url = home_url('/');
		$url = add_query_arg($scrape_params, $url);
		$r = wp_remote_get($url, compact('headers', 'timeout', 'cookies', 'sslverify'));
		$body = wp_remote_retrieve_body($r);
		$scrape_result_position = strpos($body, $needle_start);

		// Check for scrape variables
		$result = null;
		if (false === $scrape_result_position) {
			$result = $loopback_request_failure;
		} else {
			$error_output = substr($body, $scrape_result_position + strlen($needle_start));
			$error_output = substr($error_output, 0, strpos($error_output, $needle_end));
			$result = json_decode(trim($error_output), true);
			if (empty($result)) {
				$result = $json_parse_failure;
			}
		}
		delete_transient($transient);

		return $result;
	}

	/**
	 * Send a notification email regarding a fatal error that happens post automatic update
	 *
	 * @access protected
	 * @param array $updates an array of associative arrays keyed by strings 'plugin', 'theme' and/or 'core'
	 * @param array $error   an associative array describing the last error with keys "type", "message", "file" and "line"
	 */
	protected function send_notification_emails($updates, $error) {
		
		// Detail in email the update type
		$updates_message = '';
		foreach ($updates as $type => $results) {
			switch ($type) {
				case 'core':
					$core = $results[0];
					$name = $core->name;
					$updates_message .= __('== WordPress Core ==', 'stops-core-theme-and-plugin-updates') . "\r\n\r\n";
					$updates_message .= $name . "\r\n";
					break;
				case 'plugin':
					if (!empty($results)) {
						$updates_message .= "\r\n" . __('== WordPress Plugins ==', 'stops-core-theme-and-plugin-updates') . "\r\n\r\n";
						foreach ($results as $key => $plugin) {
							$name = (isset($plugin->name) && !empty($plugin->name)) ? $plugin->name : $plugin->item->slug;
							$version = isset($plugin->item->new_version) ? $plugin->item->new_version : '0.00';
							$updates_message .= sprintf(__('Plugin Name : %s', 'stops-core-theme-and-plugin-updates'), esc_html($name)) . "\r\n";
							$updates_message .= sprintf(__('New Version : %s', 'stops-core-theme-and-plugin-updates'), esc_html($version)) . "\r\n";
							if (0 == $key) {
								$updates_message .= sprintf(__('Action Taken: Permanently deactivated because an attempt to reactivate this plugin ended up with an error %s', 'stops-core-theme-and-plugin-updates'), '—'.esc_html($error['message'])) . "\r\n\r\n";
							} else {
								$updates_message .= __('Action Taken : Temporarily deactivated as a precaution and will try to reactivate at the time your site is loaded/visited', 'stops-core-theme-and-plugin-updates') . "\r\n\r\n";
							}
						}
					}
					break;
				case 'theme':
					if (!empty($results)) {
						$updates_message .= "\r\n" . __('== WordPress Themes ==', 'stops-core-theme-and-plugin-updates') . "\r\n\r\n";
						foreach ($results as $theme) {
							$name = $theme->name;
							$version = $theme->item->new_version;
							$updates_message .= sprintf(__('%s: Version %s', 'stops-core-theme-and-plugin-updates'), esc_html($name), esc_html($version)) . "\r\n";
						}
					}
					break;
			}
		}

		// Get site name
		$sitename = is_multisite() ? get_site_option('site_name') : get_option('blogname');

		// Get Send E-mail
		$sender_email = is_multisite() ? get_site_option('admin_email') : get_option('admin_email');

		// Set headers
		$headers = array();
		$headers[] = sprintf('From: %s <%s>', esc_html($sitename), $sender_email);

		/**
		 * Change the subject of the rollback notification email.
		 *
		 * @since 8.0.1
		 *
		 * @param string Email Subject
		 * @param string URL of site or network
		 */
		$subject = apply_filters('eum_rollback_subject', sprintf(__('Please check your site immediately as some plugins were not reactivated after an update: %s', 'stops-core-theme-and-plugin-updates'), esc_url(network_site_url())));
		$white_screen_message = __('Please view your site immediately.', 'stops-core-theme-and-plugin-updates').' '.__('One of your plugins caused a fatal PHP error.', 'stops-core-theme-and-plugin-updates').' '.__('We have deactivated the problematic plugin, we might have also deactivated some other plugins during the update.', 'stops-core-theme-and-plugin-updates').' '.__('If later you find out that the plugins were not reactivated, you may want to attempt to reactivate them manually.', 'stops-core-theme-and-plugin-updates') . "\r\n";
		$to = MPSUM_Utils::get_instance()->get_emails();
		if (false === filter_var($to, FILTER_VALIDATE_EMAIL)) {
			// the user may not provide an email address on the EUM settings page, and if that really happens they gonna miss the information of the failed update and may file a support ticket once they know some of their plugins have been deactivated without notice. So, it might worth getting the WordPress admin email and use it as the recipient address
			$to = get_bloginfo('admin_email');
		}

		/**
		 * Allow others to provide an action prior to emailing.
		 *
		 * Allow others to provide an action prior to emailing.
		 *
		 * @since 8.0.1
		 *
		 * @param mixed  $to Array or string of emails to send.
		 * @param string $subject        Subject of the email
		 * @param string $message        Message of the email
		 * @param array  $headers        Headers to be sent via email
		 * @param array  $updates        Results from the automatic update process
		 */
		do_action('eum_rollback_before_send', $to, $subject, $white_screen_message . $updates_message, $headers, $updates);

		if (!function_exists('wp_mail')) include_once(ABSPATH.'wp-includes/pluggable.php');
		wp_mail($to, $subject, $white_screen_message . $updates_message, $headers);

		/**
		 * Allow others to provide an action after emailing.
		 *
		 * Allow others to provide an action after emailing.
		 *
		 * @since 8.0.1
		 *
		 * @param bool   $allow_send_email Boolean to determine if emails have been sent (default true)
		 * @param mixed  $to               Array or string of emails to send.
		 * @param string $subject          Subject of the email
		 * @param string $message          Message of the email
		 * @param array  $headers          Headers to be sent via email
		 * @param array  $updates          Update results
		 */
		do_action('eum_rollback_after_send', true, $to, $subject, $white_screen_message . $updates_message, $headers, $updates);
	}
}
