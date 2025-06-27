<?php
if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_External_Logs')) return;

/**
 * Class MPSUM_External_Logs
 */
class MPSUM_External_Logs extends MPSUM_Logs {

	private $did_upgrader_process_action = false;

	protected $logger = null;

	protected $readable_log_messages = array();

	/**
	 * MPSUM_External_Logs constructor. Initiates and adds necessary hooks
	 */
	protected function __construct() {
		parent::__construct();
		add_action('eum_logs', array($this, 'display_settings'));
		$this->setup_loggers();
		add_action('wp_loaded', array($this, 'send_postponed_email_log'));
	}

	/**
	 * Returns instance of singleton pattern
	 *
	 * @return MPSUM_External_Logs
	 */
	public static function get_instance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Setup External logger(s)
	 */
	public function setup_loggers() {
		$logger = $this->get_logger();
		$loggers = $this->eum_loggers();

		if (!empty($loggers)) {
			foreach ($loggers as $_logger) {
				$logger->add_logger($_logger);
			}
		}
	}

	/**
	 * Gets logger instance
	 *
	 * @return MPSUM_Logger
	 */
	public function get_logger() {
		static $_instance = null;
		if (null === $_instance) {
			$_instance = new MPSUM_Logger();
		}
		return $_instance;
	}

	/**
	 * Returns list of EUM loggers instances
	 * Apply filter eum_loggers
	 *
	 * @return array
	 */
	public function eum_loggers() {
		$loggers = array();
		$options_keys = array();
		$loggers_classes = $this->get_loggers_classes();

		$options = MPSUM_Updates_Manager::get_options('logs');
		$logger_additional_options = isset($options['logger_additional_options']) ? $options['logger_additional_options'] : '';

		// create loggers classes instances.
		if (!empty($loggers_classes)) {

			foreach ($loggers_classes as $logger_class => $source) {

				$logger_id = strtolower($logger_class);
				$logger = new $logger_class();

				$logger_options = $logger->get_options_list();

				if (!empty($logger_options)) {
					foreach (array_keys($logger_options) as $option_name) {
						if (array_key_exists($option_name, $options_keys)) {
							$options_keys[$option_name]++;
						} else {
							$options_keys[$option_name] = 0;
						}

						$option_value = isset($logger_additional_options[$option_name]) ? $logger_additional_options[$option_name] : '';

						$logger->set_option($option_name, $option_value);
					}
				}

				// check if logger is active.
				$active = isset($options['eum_logger_type'][$logger_id]) && "1" === $options['eum_logger_type'][$logger_id] ? true : false;

				if ($active) {
					$logger->enable();
				} else {
					$logger->disable();
				}

				$loggers[] = $logger;
			}
		}

		$loggers = apply_filters('eum_loggers', $loggers);

		return $loggers;
	}

	/**
	 * Returns associative array with logger class name in a key and path to class file in a value.
	 *
	 * @return array
	 */
	public function get_loggers_classes() {
		$loggers_classes = array(
			'MPSUM_PHP_Logger'      => EASY_UPDATES_MANAGER_MAIN_PATH . 'includes/MPSUM_PHP_Logger.php',
			'MPSUM_Email_Logger'    => EASY_UPDATES_MANAGER_MAIN_PATH . 'includes/MPSUM_Email_Logger.php',
			'MPSUM_Syslog_Logger'   => EASY_UPDATES_MANAGER_MAIN_PATH .'includes/MPSUM_Syslog_Logger.php',
			'MPSUM_Slack_Logger'    => EASY_UPDATES_MANAGER_MAIN_PATH .'includes/MPSUM_Slack_Logger.php',
		);

		return apply_filters('eum_loggers_classes', $loggers_classes);
	}

	/**
	 * Displays settings on logs tab
	 */
	public function display_settings() {
		$loggers = $this->get_logger()->get_loggers();
		Easy_Updates_Manager()->include_template('external-logs.php', false, array('loggers' => $loggers));
	}

	/**
	 * Make sure PHP fatal errors are caught during an update and/or log the updating information in the $readable_log_messages variable (if any) and send them via other log methods like Email, Slack, Syslog, PHP error log, etc
	 */
	public function perform_shutdown_task() {
		if (!is_array($this->log_messages) || empty($this->log_messages)) $this->log_messages = array();
		foreach ($this->log_messages as $type => $entities) {
			if (!is_array($entities) || empty($entities)) continue;
			foreach ($entities as $key => $data) {
				if ('core' === $type) {
					$key = "core_$key";
				} elseif ('translation' === $type) {
					$key = "translation_$key";
				}
				$this->readable_log_messages[$key] = $this->get_message($data['name'], $type, $data['from'], $data['to'], $this->auto_update ? 'automatically' : 'manually', $data['status'], get_current_user_id());
			}
		}
		if (!empty($this->readable_log_messages)) {
			$this->did_upgrader_process_action = true;
			$this->log($this->readable_log_messages);
		}
		parent::perform_shutdown_task();
	}

	/**
	 * Log update information to the log table when upgrader process is complete during either manual or automatic operation and send them via other log methods like Email, Slack, Syslog, PHP error log, etc
	 *
	 * @param WP_Upgrader $wp_upgrader WP_Upgrader instance. In other contexts, $this, might be a Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or Language_Pack_Upgrader instance
	 * @param array       $hook_extra  Extra arguments passed to hooked filters
	 */
	public function log_updates($wp_upgrader, $hook_extra) {
		if (isset($hook_extra['action']) && 'update' !== $hook_extra['action']) return;
		switch ($hook_extra['type']) {
			case 'translation':
				foreach ($hook_extra['translations'] as $translation) {
					$key = 'core' !== $translation['type'] && isset($this->log_messages['translation'][$translation['slug']]) ? $translation['slug'] : ('core' === $translation['type'] && isset($this->log_messages['translation']['wordpress_default_'.$translation['language']]) ? 'wordpress_default_'.$translation['language'] : '');
					if ('' === $key) continue;
					if (!isset($this->log_messages['translation'][$key])) continue;
					$this->readable_log_messages["translation_$key"] = $this->get_message($this->log_messages['translation'][$key]['name'], $hook_extra['type'], $this->log_messages['translation'][$key]['from'], $this->log_messages['translation'][$key]['to'], $this->auto_update ? 'automatically' : 'manually', $this->log_messages['translation'][$key]['status'], get_current_user_id());
				}
				break;
			case 'core':
				foreach ($this->log_messages['core'] as $key => $data) {
					$this->readable_log_messages["core_$key"] = $this->get_message($data['name'], $hook_extra['type'], $data['from'], $data['to'], $this->auto_update ? 'automatically' : 'manually', $data['status'], get_current_user_id());
				}
				break;
			case 'plugin':
				$plugins = isset($hook_extra['plugins']) && is_array($hook_extra['plugins']) ? $hook_extra['plugins'] : (isset($hook_extra['plugin']) ? $hook_extra['plugin'] : array());
				foreach ((array) $plugins as $plugin) {
					if (!isset($this->log_messages['plugin'][$plugin])) continue;
					$this->readable_log_messages[$plugin] = $this->get_message($this->log_messages['plugin'][$plugin]['name'], $hook_extra['type'], $this->log_messages['plugin'][$plugin]['from'], $this->log_messages['plugin'][$plugin]['to'], $this->auto_update ? 'automatically' : 'manually', $this->log_messages['plugin'][$plugin]['status'], get_current_user_id());
				}
				break;
			case 'theme':
				$themes = isset($hook_extra['themes']) && is_array($hook_extra['themes']) ? $hook_extra['themes'] : (isset($hook_extra['theme']) ? $hook_extra['theme'] : array());
				foreach ((array) $themes as $theme) {
					if (!isset($this->log_messages['theme'][$theme])) continue;
					$this->readable_log_messages[$theme] = $this->get_message($this->log_messages['theme'][$theme]['name'], $hook_extra['type'], $this->log_messages['theme'][$theme]['from'], $this->log_messages['theme'][$theme]['to'], $this->auto_update ? 'automatically' : 'manually', $this->log_messages['theme'][$theme]['status'], get_current_user_id());
				}
				break;
		}
		parent::log_updates($wp_upgrader, $hook_extra);
	}

	/**
	 * Log update information to the log table when automatic updates is complete and send them via other log methods like Email, Slack, Syslog, PHP error log, etc
	 *
	 * @param array $update_results The results of all attempted updates
	 */
	public function log_automatic_updates($update_results) {
		if (empty($update_results)) return;
		foreach ($update_results as $type => $results) {
			foreach ($results as $result) {
				if ('core' === $type) {
					if (!isset($this->log_messages[$type])) continue;
					foreach ($this->log_messages[$type] as $key => $data) {
						$this->readable_log_messages["core_$key"] = $this->get_message($data['name'], $type, $data['from'], $data['to'], 'automatically', $data['status']);
					}
				} elseif ('translation' === $type) {
					if (!isset($this->log_messages[$type], $this->log_messages[$type][$result->item->slug])) continue;
					$this->readable_log_messages["translation_$result->item->slug"] = $this->get_message($this->log_messages[$type][$result->item->slug]['name'], $type, $this->log_messages[$type][$result->item->slug]['from'], $this->log_messages[$type][$result->item->slug]['to'], 'automatically', isset($result->result) && $result->result && !is_wp_error($result->result) ? 1 : 0);
				} else {
					if (!isset($this->log_messages[$type], $this->log_messages[$type][$result->item->$type])) continue;
					$this->readable_log_messages[$result->item->$type] = $this->get_message($this->log_messages[$type][$result->item->$type]['name'], $type, $this->log_messages[$type][$result->item->$type]['from'], $this->log_messages[$type][$result->item->$type]['to'], 'automatically', isset($result->result) && $result->result && !is_wp_error($result->result) ? 1 : 0);
				}
			}
		}
		parent::log_automatic_updates($update_results);
	}

	/**
	 * Inserts result of upgrade process message to log table
	 *
	 * @param array $messages Upgrade name
	 */
	private function log($messages) {
		$loggers = $this->get_logger()->get_loggers();
		if (empty($loggers)) return;
		foreach ($loggers as $logger) {
			if (is_a($logger, 'MPSUM_Email_Logger')) {
				// Unlike automatic updating and manual bulk updating plugins on the WP Updates screen (update-core.php), bulk updating on the WP plugins screen are handled via admin-ajax request, each updating process will go through a single request thus making "EUM Email Log" is sent multiple times
				// during the ajax request WP doesn't give a specific variable via $_POST/$_REQUEST that can be used to determine which plugin is the last one being updated or how many plugins were selected for the updating operation
				// so for now we could only prevent EUM Email Logs from flooding users' inbox by using time interval, this could reduce the number of emails being sent to the user, for example if a user performs bulk plugins updating on the WP Plugins screen and they select 4 plugins
				// then the outcome of the mechanism that we provided here would be:
				// 1. EUM will send 4 emails for each updating status of the plugins but the emails will be sent in fixed time interval, which is 90 seconds minimum
				// 2. EUM will at least send 2 emails depending on how long the updating operation of the 2nd, 3rd and 4th plugins take time to complete the process, if they can be completed within 90 seconds then only two emails will be sent
				require_once(ABSPATH."wp-admin/includes/class-wp-upgrader.php");
				if (is_multisite()) switch_to_blog(get_main_site_id(get_main_network_id()));
				$readable_email_logging_messages = array_merge(get_option('eum_readable_email_logging_messages', array()), $messages);
				if (!empty($readable_email_logging_messages) && WP_Upgrader::create_lock('eum_readable_email_logging_messages', 90)) {
					$logger->flush_log($readable_email_logging_messages);
					update_option('eum_readable_email_logging_messages', array(), true);
				} elseif ($this->did_upgrader_process_action) { // prevent other PHP processes/threads from accessing this block of code to avoid race conditions
					update_option('eum_readable_email_logging_messages', $readable_email_logging_messages, true);
				}
				if (is_multisite()) restore_current_blog();
			} else {
				foreach ($messages as $message) {
					$logger->info($message);
				}
			}
		}
	}

	/**
	 * Creates a readable log message
	 *
	 * @param string $name         Upgrade name
	 * @param string $type         Type of upgrade
	 * @param string $version_from Upgrade from version number
	 * @param string $version      Upgrade to version number
	 * @param string $action       Action type, manual or automatic
	 * @param int    $status       Status of upgrade
	 * @param int    $user_id      ID of user who is responsible for update
	 *
	 * @return string A log message
	 */
	private function get_message($name, $type, $version_from, $version, $action, $status, $user_id = 0) {
		$url = is_multisite() ? network_site_url() : site_url();
		$type = ucfirst($type);
		$status_message = (1 === $status) ? __('Update is successful!', 'stops-core-theme-and-plugin-updates') : __('Update failed!', 'stops-core-theme-and-plugin-updates');
		if (0 === $user_id) {
			return sprintf('%1$s: %2$s: %3$s is updated from %4$s to %5$s %6$s at %7$s. %8$s', $url, $type, $name, $version_from, $version, $action, current_time('mysql'), $status_message);
		}
		$user = get_user_by('ID', $user_id);
		return sprintf('%1$s: %2$s: %3$s is updated from %4$s to %5$s %6$s at %7$s by user %8$s.', $url, $type, $name, $version_from, $version, $action, current_time('mysql'), $status_message, $user->user_login);
	}

	/**
	 * Send any postponed readable email logging messages
	 */
	public function send_postponed_email_log() {
		$this->log(array());
	}
}
