<?php
if (!defined('ABSPATH')) die('No direct access.');

/**
 * Class MPSUM_Cron_Days_Schedule handles delayed updates
 */
class MPSUM_Cron_Days_Schedule {

	/**
	 * Initiates and returns singleton instance of this class
	 *
	 * @return MPSUM_Cron_Days_Schedule instance
	 */
	public static function get_instance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 *  Constructor
	 */
	private function __construct() {
		add_filter('auto_update_core', array($this, 'maybe_auto_update_on_scheduled_days'), PHP_INT_MAX - 10, 2);
		add_filter('auto_update_plugin', array($this, 'maybe_auto_update_on_scheduled_days'), PHP_INT_MAX - 10, 2);
		add_filter('auto_update_theme', array($this, 'maybe_auto_update_on_scheduled_days'), PHP_INT_MAX - 10, 2);
		add_filter('auto_update_translation', array($this, 'maybe_auto_update_on_scheduled_days'), PHP_INT_MAX - 10, 2);
	}

	/**
	 * Check whether the user-defined days allow update to be going through the automatic process
	 *
	 * @param bool   $update A flag that decides whether to update or not
	 * @param object $item   A object contains plugin update details
	 *
	 * @return bool False if update needs to be skipped, otherwise default update status
	 */
	public function maybe_auto_update_on_scheduled_days($update, $item) {
		if (MPSUM_Utils::is_wp_site_health_plugin_theme($item)) return true;
		$options = MPSUM_Updates_Manager::get_options('advanced');
		if (!$update || empty($options['cron_schedule']) || !in_array($options['cron_schedule'], array('every_3_hours', 'every_6_hours', 'twicedaily', 'daily'))) return $update;
		$current_day = (int) get_date_from_gmt(gmdate('Y-m-d H:i:s', time()), 'w') + 1;
		if ('daily' === $options['cron_schedule']) {
			if (!empty($options['cron_days_list']) && in_array($current_day, $options['cron_days_list'])) return false;
		} else {
			if (isset($options['cron_days_list']) && !in_array($current_day, $options['cron_days_list'])) return false;
		}
		return true;
	}
}
