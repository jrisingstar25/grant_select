<?php

if (!defined('ABSPATH')) die('No direct access.');

if (!class_exists('MPSUM_Clear_Logs_Cron')) {

	/**
	 * Clear logs cron
	 */
	class MPSUM_Clear_Logs_Cron {

		public static $schedule;

		/**
		 * Adds a method to run when premium cron event fires
		 *
		 * @param string $schedule No of days
		 */
		private function __construct($schedule) {
			self::$schedule = $schedule;
			add_filter('cron_schedules', array($this, 'cron_schedules'));
			add_action('eum_clear_logs', array($this, 'clear_logs'));
			add_action('eum_logs', array($this, 'logs_clearing'));
		}

		/**
		 * Adds custom cron schedules
		 *
		 * @param array $schedules - An array of available cron schedules
		 *
		 * @return mixed - An array of modified cron schedules
		 */
		public function cron_schedules($schedules) {
			$schedules['eum_clear_logs'] = array( 'interval' => 86400 * self::$schedule, 'display' => sprintf(_n('Every %s day', 'Every %s days', self::$schedule, 'stops-core-theme-and-plugin-updates'), self::$schedule));
			return $schedules;
		}

		/**
		 * Returns singleton instance of this class
		 *
		 * @param string $schedule No of days
		 *
		 * @return object MPSUM_Cron_Scheduler Singleton Instance
		 */
		public static function get_instance($schedule = 0) {
			static $instance = null;
			if (null === $instance) {
				$instance = new self($schedule);
			}
			return $instance;
		}

		/**
		 * Includes log clearing settings form
		 */
		public function logs_clearing() {
			Easy_Updates_Manager()->include_template('logs-clearing.php');
		}

		/**
		 * Clear the WordPress crons
		 */
		 public function remove_clear_logs_event() {
			wp_clear_scheduled_hook('eum_clear_logs');
		 }

		/**
		 * Schedules core, plugin and theme updates
		 */
		public function schedule_clear_logs_event() {
			if (!wp_next_scheduled('eum_clear_logs')) {
				if (self::$schedule > 0) {
					wp_schedule_event(time() + self::$schedule * 86400, 'eum_clear_logs', 'eum_clear_logs');
				}
			}
		}

		public function clear_logs() {
			MPSUM_Logs::clear();
		}
	}
}
