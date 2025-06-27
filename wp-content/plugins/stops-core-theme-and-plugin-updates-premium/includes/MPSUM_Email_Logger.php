<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (class_exists('MPSUM_Email_Logger')) return;

/**
 * Class MPSUM_Email_Logger
 */
class MPSUM_Email_Logger extends MPSUM_Abstract_Logger {

	protected $allow_multiple = true;

	/**
	 * MPSUM_Email_Logger constructor
	 */
	public function __construct() {
	}

	/**
	 * Returns logger description
	 *
	 * @return string
	 */
	public function get_description() {
		return __('Log events to e-mail', 'stops-core-theme-and-plugin-updates');
	}

	/**
	 * Returns list of logger options.
	 *
	 * @return array
	 */
	public function get_options_list() {
		return array(
			'emails' => array(
				__('Enter e-mail for logs here', 'stops-core-theme-and-plugin-updates'),
				'email', // validator
			)
		);
	}

	/**
	 * Emergency message
	 *
	 * @param  string $message
	 * @param  array  $context
	 * @return null|void
	 */
	public function emergency($message, array $context = array()) {
		$this->log(MPSUM_Log_Levels::EMERGENCY, $message, $context);
	}

	/**
	 * Alert message
	 *
	 * @param  string $message
	 * @param  array  $context
	 * @return null|void
	 */
	public function alert($message, array $context = array()) {
		$this->log(MPSUM_Log_Levels::ALERT, $message, $context);
	}

	/**
	 * Critical message
	 *
	 * @param  string $message
	 * @param  array  $context
	 * @return null|void
	 */
	public function critical($message, array $context = array()) {
		$this->log(MPSUM_Log_Levels::CRITICAL, $message, $context);
	}

	/**
	 * Error message
	 *
	 * @param  string $message
	 * @param  array  $context
	 * @return null|void
	 */
	public function error($message, array $context = array()) {
		$this->log(MPSUM_Log_Levels::ERROR, $message, $context);
	}

	/**
	 * Warning message
	 *
	 * @param  string $message
	 * @param  array  $context
	 * @return null|void
	 */
	public function warning($message, array $context = array()) {
		$this->log(MPSUM_Log_Levels::WARNING, $message, $context);
	}

	/**
	 * Notice message
	 *
	 * @param  string $message
	 * @param  array  $context
	 * @return null|void
	 */
	public function notice($message, array $context = array()) {
		$this->log(MPSUM_Log_Levels::NOTICE, $message, $context);
	}

	/**
	 * Info message
	 *
	 * @param  string $message
	 * @param  array  $context
	 * @return null|void
	 */
	public function info($message, array $context = array()) {
		$this->log(MPSUM_Log_Levels::INFO, $message, $context);
	}

	/**
	 * Debug message
	 *
	 * @param  string $message
	 * @param  array  $context
	 * @return null|void
	 */
	public function debug($message, array $context = array()) {
		$this->log(MPSUM_Log_Levels::DEBUG, $message, $context);
	}

	/**
	 * Log message with any level
	 *
	 * @param  mixed  $level
	 * @param  string $message
	 * @param  array  $context
	 * @return null|bool
	 */
	public function log($level, $message, array $context = array()) {

		if (!$this->is_enabled()) return false;

		$options = MPSUM_Updates_Manager::get_options('core');
		$log = isset($options['eum_mail_logger_log']) ? $options['eum_mail_logger_log'] : array();

		$message = '['.MPSUM_Log_Levels::to_text($level).'] : '.$this->interpolate($message, $context);

		$log[] = $message;
		$options['eum_mail_logger_log'] = $log;
		MPSUM_Updates_Manager::update_options($options, 'core');
	}

	/**
	 * Add recipient email
	 *
	 * @param string $email
	 */
	public function add_email($email) {
		$emails = $this->get_option('emails', array());
		$emails[] = $email;
		$this->set_option('emails', $emails);
	}

	/**
	 * Return list of recipients email
	 *
	 * @return null
	 */
	public function get_emails() {
		return $this->get_option('emails', get_option('admin_email'));
	}

	/**
	 * Email and clear log
	 *
	 * @param array $messages An array of log messages
	 */
	public function flush_log($messages) {
		if (empty($messages)) return;

		if (!$this->is_enabled()) return;

		$email_addresses = $this->get_emails();
		$subject = $this->get_option('MPSUM_mail_logger_subject', 'EUM Email Log');

		$log = join("\n", $messages);
		wp_mail($email_addresses, $subject, $log);
	}

	/**
	 * Return log messages
	 *
	 * @return array
	 */
	public function get_log() {
		$options = MPSUM_Updates_Manager::get_options('core');
		return isset($options['eum_mail_logger_log']) ? $options['eum_mail_logger_log'] : array();
	}
}
