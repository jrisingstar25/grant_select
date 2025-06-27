<?php

if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_Update_Cron')) return;

/**
 * Class MPSUM_Update_Cron handles scheduled crons for updates
 */
class MPSUM_Update_Cron {

	/**
	 * Adds necessary filters and actions
	 */
	private function __construct() {
		add_action('eum_advanced_headings', array($this, 'heading'), 12);
		add_action('eum_advanced_settings', array($this, 'settings'), 12);
		add_filter('cron_schedules', array($this, 'cron_schedules'));
		add_filter('pre_reschedule_event', '__return_null', PHP_INT_MAX - 10);
		add_filter('pre_schedule_event', '__return_null', PHP_INT_MAX - 10);
		add_filter('schedule_event', array($this, 'force_time_rescheduling'), PHP_INT_MAX - 10);
		add_filter('auto_update_core', array($this, 'update_if_on_schedule'), PHP_INT_MAX - 10, 2);
		add_filter('auto_update_plugin', array($this, 'update_if_on_schedule'), PHP_INT_MAX - 10, 2);
		add_filter('auto_update_theme', array($this, 'update_if_on_schedule'), PHP_INT_MAX - 10, 2);
		add_filter('auto_update_translation', array($this, 'update_if_on_schedule'), PHP_INT_MAX - 10, 2);
		add_action('eum_auto_updates', array($this, 'perform_automatic_update'));
		if (false === wp_get_scheduled_event('eum_auto_updates')) {
			// // Either it's an upgrade from 9.0.17 and the user hasn't done anything to the schedule setting not even save the setting or if somehow the below WordPress event schedules don't match with what is in the setting
			$this->set_cron_events();
		}
	}

	/**
	 * Perform WordPress automatic background updates
	 *
	 * @return Void
	 */
	public function perform_automatic_update() {
		if (doing_action('wp_maybe_auto_update')) return;
		if (wp_doing_cron()) do_action('wp_maybe_auto_update');
	}

	/**
	 * Let the auto-updates operation run if it's confirmed to be on schedule, also allowing other plugins to trigger their own auto-updates event/cron as long as it's in the same schedule time with our setting
	 *
	 * @param bool   $update A flag that decides whether to update or not
	 * @param object $item   A object contains plugin update details
	 *
	 * @return bool False if not on schedule (early or overdue), true otherwise (let it through)
	 */
	public function update_if_on_schedule($update, $item) {
		static $prev_scheduled_event, $grace_period_logged = null;
		if (MPSUM_Utils::is_wp_site_health_plugin_theme($item)) return true;
		$options = MPSUM_Updates_Manager::get_options('core');
		if (empty($prev_scheduled_event) && isset($options['next_scheduled_event'])) $prev_scheduled_event = $options['next_scheduled_event'];
		$options['next_scheduled_event'] = $this->calculate_next_event();
		MPSUM_Updates_Manager::update_options($options, 'core');
		if (empty($prev_scheduled_event) || !$update) return $update; // if it's the first time install, the prev_scheduled_event is not yet set, so we just return what we have in the $update
		$now = time();
		$do_update = ($now >= $prev_scheduled_event && $prev_scheduled_event + 3600 >= $now); // let the auto-updates run even if it's already late but should be not more than an hour
		// we already eliminate some potential issues that can cause auto-updates to run outside the EUM schedule, but we add some log to help us in debugging possible future issues. This seems to be the only one that can happen in the future and when it happens we have the log that confirms it
		if (!$do_update && !$grace_period_logged) {
			error_log('eum: auto-updates event got triggered, but was overdue and had passed the grace period of one hour');
			$grace_period_logged = true;
		}
		return $do_update;
	}

	/**
	 * Force time rescheduling of the wp_version_check, wp_update_plugins and wp_update_themes crons so that they're aligned with our own auto-updates schedule setting, in case they were changed/overriden by other plugins
	 * When someone using other plugin tries to change the wp_version_check, wp_update_plugins, or wp_update_themes schedule from the backend, this function will not immediately replace the schedule with our own schedule setting
	 * but instead the replacement will take place after the cron for the event is triggered. Consequently, The event will later be triggered outside EUM's schedule but it doesn't mean we allow it to run, because we still have
	 * a hook (@see update_if_on_schedule) that will check whether wp_version_check, wp_update_plugins, or wp_update_themes is aligned with EUM's schedule.
	 *
	 * @param Object $event {
	 *     An object containing an event's data.
	 *
	 *     @type string       $hook      Action hook to execute when the event is run.
	 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
	 *     @type string|false $schedule  How often the event should subsequently recur.
	 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
	 *     @type int          $interval  Optional. The interval time in seconds for the schedule. Only present for recurring events.
	 * }
	 * @return Object The event that may have been adjusted to match with the schedule setting
	 */
	public function force_time_rescheduling($event) {
		if (!isset($event->hook) || !in_array($event->hook, array('wp_version_check', 'wp_update_plugins', 'wp_update_themes', 'eum_auto_updates'))) return $event;

		if (!apply_filters('eum_allow_schedule_overriden', false) && !defined('DOING_CRON') && 'eum_auto_updates' !== $event->hook) return $event; // if it's not from a cron which could mean EUM's auto update scheduling is being updated by the user or if other plugin tries to change wp_version_check, wp_update_plugins, or wp_update_themes schedules from the admin dashboard then let it be, but we will not allow them to change our eum_auto_updates event

		error_log('Rescheduling '.$event->hook.' to make it match with the Easy Updates Manager automatic update scheduling feature');

		$cron_schedules = $this->cron_schedules(array());
		$options = MPSUM_Updates_Manager::get_options('advanced');

		if (empty($options['cron_schedule'])) $options['cron_schedule'] = 'twicedaily';
		if (empty($options['cron_time'])) $options['cron_time'] = '00:15';

		$event->schedule = $options['cron_schedule'];

		if (isset($cron_schedules[$options['cron_schedule']]['interval'])) {
			$event->interval = $cron_schedules[$options['cron_schedule']]['interval'];
		} elseif (isset($cron_schedules['eum_'.$options['cron_schedule']]['interval'])) {
			$event->interval = $cron_schedules['eum_'.$options['cron_schedule']]['interval'];
		}

		if ('weekly' === $options['cron_schedule']) {
			$event->schedule = 'eum_weekly';
		} elseif ('fortnightly' === $options['cron_schedule']) {
			$event->schedule = 'eum_fortnightly';
		} elseif ('monthly' === $options['cron_schedule']) {
			$event->schedule = 'eum_monthly';
		}

		$event->timestamp = $this->calculate_next_event();
		return $event;
	}

	/**
	 * Calculate the next scheduled event based on the user's predefined schedule setting
	 *
	 * @param Boolean $zero_timestamp_for_unspecified_days Whether to allow zero timestamp if there are no days selected
	 * @return Integer Timestamp
	 */
	public function calculate_next_event($zero_timestamp_for_unspecified_days = false) {
		$options = MPSUM_Updates_Manager::get_options('advanced');

		if (empty($options['cron_schedule'])) $options['cron_schedule'] = 'twicedaily';
		if (empty($options['cron_time'])) $options['cron_time'] = '00:15';

		
		if (isset($options['cron_days_list'])) {
			$allowed_days = $options['cron_days_list'];
		} else {
			if ('daily' !== $options['cron_schedule']) {
				$allowed_days = array(1, 2, 3, 4, 5, 6, 7);
			} else {
				$allowed_days = array();
			}
		}
		$use_selective_days = false;

		$timestamp = strtotime(date('Y-m-d').' '.$options['cron_time']) - (3600 * get_option('gmt_offset'));

		if ('every_3_hours' === $options['cron_schedule']) {
			$interval = 3600 * 3;
			$use_selective_days = true;
		} elseif ('every_6_hours' === $options['cron_schedule']) {
			$interval = 3600 * 6;
			$use_selective_days = true;
		} elseif ('twicedaily' ===  $options['cron_schedule']) {
			$interval = 43200;
			$use_selective_days = true;
		} elseif ('daily' === $options['cron_schedule']) {
			$interval = 86400;
			$allowed_days = array_diff(array('1', '2', '3', '4', '5', '6', '7'), $allowed_days); // $cron_days_list contains excepted days, so we use array_diff to get allowed days
			$use_selective_days = true;
		} elseif ('weekly' === $options['cron_schedule']) {
			$interval = 86400 * 7;
			$day_of_week = $this->get_week_day($options['cron_week_day']);
			$timestamp = strtotime("This $day_of_week, ".$options['cron_time'], strtotime(date('Y-m-d'))) - (3600 * get_option('gmt_offset'));
		} elseif ('fortnightly' === $options['cron_schedule']) {
			$interval = 86400 * 14;
			$user_week_number = absint($options['cron_week']);
			$day_of_week = $this->get_week_day($options['cron_week_day']);
			$timestamp = strtotime("first {$day_of_week} of ". date("F"));
			if (2 === $user_week_number) $timestamp += (7 * 86400);
			$timestamp = strtotime(date('Y-m-d', $timestamp) . ' ' .$options['cron_time']) - (3600 * get_option('gmt_offset'));
		} elseif ('monthly' === $options['cron_schedule']) {
			$user_day_number = absint($options['cron_day_number']);
			$interval = strtotime('+1 month', strtotime(date("Y-m-" . $user_day_number) . ' ' . $options['cron_time'])) - strtotime(date("Y-m-" . $user_day_number) . ' ' . $options['cron_time']);
			$timestamp = strtotime(date("Y-m-" . $user_day_number) . ' ' . $options['cron_time']) - (3600 * get_option('gmt_offset'));
		}
		$now = time();
		if ($timestamp < $now) {
			$timestamp = $now + ($interval - (($now - $timestamp) % $interval));
		} else {
			$timestamp = $now + (($timestamp - $now) % $interval);
		}

		if ($use_selective_days) {
			while (!empty($allowed_days) && is_array($allowed_days) && array_intersect($allowed_days, range(1, 7))) {
				$timestamp_day_in_number = (int) get_date_from_gmt(gmdate('Y-m-d H:i', $timestamp), 'w');
				if (in_array($timestamp_day_in_number+1, $allowed_days)) break;
				$timestamp += $interval;
			}
			if (empty($allowed_days) && $zero_timestamp_for_unspecified_days) return 0;
		}

		return $timestamp;
	}

	/**
	 * Adds custom cron schedules
	 *
	 * @param array $schedules - An array of available cron schedules
	 *
	 * @return array - An array of modified cron schedules
	 */
	public function cron_schedules($schedules) {
		$schedules['every_3_hours'] = array('interval' => 3600 * 3, 'display' => sprintf(__('Every %d hours', 'stops-core-theme-and-plugin-updates'), 3));
		$schedules['every_6_hours'] = array('interval' => 3600 * 6, 'display' => sprintf(__('Every %d hours', 'stops-core-theme-and-plugin-updates'), 6));
		$schedules['eum_weekly'] = array('interval' => 86400 * 7, 'display' => __('Once weekly', 'stops-core-theme-and-plugin-updates'));
		$schedules['eum_fortnightly'] = array('interval' => 86400 * 14, 'display' => __('Once every fortnight', 'stops-core-theme-and-plugin-updates'));
		$schedules['eum_monthly'] = array('interval' => 365.25 * 86400 / 12, 'display' => __('Once every month', 'stops-core-theme-and-plugin-updates'));
		return $schedules;
	}

	/**
	 * Returns singleton instance of this class
	 *
	 * @return object MPSUM_Cron_Scheduler Singleton Instance
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
		printf('<div data-menu_name="schedule-update">%s <span class="eum-advanced-menu-text">%s</span></div>', '<i class="material-icons">schedule</i>', esc_html__('Automatic update scheduling', 'stops-core-theme-and-plugin-updates'));
	}

	/**
	 * Outputs feature settings
	 */
	public function settings() {
		Easy_Updates_Manager()->include_template('cron-settings.php');
	}

	/**
	 * Schedule cron to update when existing values are available
	 */
	public function set_cron_events() {
		$options = MPSUM_Updates_Manager::get_options('advanced');
		$eum_cron_schedule = isset($options['cron_schedule']) ? $options['cron_schedule'] : 'twicedaily';

		$this->clear_wordpress_crons();

		// Set up daily cron
		if ('weekly' === $eum_cron_schedule) {
			$eum_cron_schedule = 'eum_weekly';
		} elseif ('fortnightly' === $eum_cron_schedule) {
			$eum_cron_schedule = 'eum_fortnightly';
		} elseif ('monthly' === $eum_cron_schedule) {
			$eum_cron_schedule = 'eum_monthly';
		}
		$this->schedule_events($this->calculate_next_event(), $eum_cron_schedule);
	}

	/**
	 * Clears default WordPress crons
	 */
	 public function clear_wordpress_crons() {
		wp_clear_scheduled_hook('eum_auto_updates');
		wp_clear_scheduled_hook('wp_update_plugins');
		wp_clear_scheduled_hook('wp_update_themes');
		wp_clear_scheduled_hook('wp_version_check');
	 }

	/**
	 * Sets default WordPress crons
	 */
	public function set_default_cron() {
		add_action('wp_update_plugins', 'wp_update_plugins');
		add_action('wp_update_themes', 'wp_update_themes');
		add_action('wp_version_check', 'wp_version_check');
	}

	/**
	 * Get the week day for day of the week.
	 *
	 * @param int $day Day of the week.
	 *
	 * @return string Week day
	 */
	private function get_week_day($day) {
		$day = absint($day);
		$days = array(
			'1' => 'Sunday',
			'2' => 'Monday',
			'3' => 'Tuesday',
			'4' => 'Wednesday',
			'5' => 'Thursday',
			'6' => 'Friday',
			'7' => 'Saturday',
		);
		return $days[$day];
	}

	/**
	 * Schedules core, plugin and theme updates
	 *
	 * @param integer $timestamp - A timestamp to schedule events
	 * @param string  $schedule  - A cron schedule
	 */
	private function schedule_events($timestamp, $schedule) {
		wp_schedule_event($timestamp, $schedule, 'eum_auto_updates');
		wp_schedule_event($timestamp, $schedule, 'wp_update_plugins');
		wp_schedule_event($timestamp, $schedule, 'wp_update_themes');
		wp_schedule_event($timestamp, $schedule, 'wp_version_check');
	}

	/**
	 * Displays date and time options based on selected cron schedule
	 */
	public function display_date_time_options($options) {
		$options = $this->set_default_cron_options($options);
		$this->display_week_day($options['cron_week_day']);
		$this->display_week($options['cron_week']);
		$this->display_day_number($options['cron_day_number']);
		$this->display_time($options['cron_time']);
		$this->display_days_list($options['cron_days_list']);
	}

	/**
	 * Sets default settings for cron schedules
	 *
	 * @param  array $options An array of options
	 * @return array An array of modifiedoptions
	 */
	private function set_default_cron_options($options) {
		if (empty($options['cron_week_day'])) {
			$options['cron_week_day'] = 1;
		}

		if (empty($options['cron_week'])) {
			$options['cron_week'] = 1;
		}

		if (empty($options['cron_day_number'])) {
			$options['cron_day_number'] = 1;
		}

		if (empty($options['cron_time'])) {
			$options['cron_time'] = '00:15';
		}

		return $options;
	}

	/**
	 * Display multiple selection dropdown box that has a list of selectable days
	 *
	 * @param array $days_list An array of user-defined days
	 * @return string The HTML content corresponding to the days selected by the user or default days
	 */
	private function display_days_list($days_list) {
		$week_days = $this->get_week_days();
		$html = '<div id="select_days_list" data-placeholder="'.esc_attr__('Select the day of the week', 'stops-core-theme-and-plugin-updates').'">';
		$html .= sprintf('<label class="every_3_hours every_6_hours twicedaily">%s:</label>', __('On the following days', 'stops-core-theme-and-plugin-updates'));
		$html .= sprintf('<label class="daily">%s:</label>', __('Except on these days', 'stops-core-theme-and-plugin-updates'));
		$html .= '<select class="eum_days_list" name="eum_cron_days_list[]" multiple="multiple">';
		foreach ($week_days as $key => $value) {
			$html .= sprintf('<option value="%1$s" %2$s>%3$s</option>', $key, in_array($key, $days_list) ? 'selected="selected"' : '', $value);
		}
		$html .= '</select></div>';
		echo $html;
	}

	/**
	 * Displays week days select field
	 *
	 * @param array $week_day An array of week days
	 * @return void
	 */
	private function display_week_day($week_day) {
		$week_days = $this->get_week_days();
		$html = '';
		$html .= '<select class="eum_week_days" name="eum_cron_week_day">';
		foreach ($week_days as $key => $value) {
			$html .= sprintf('<option value="%1$s" %2$s>%3$s</option>', $key, selected($week_day, $key, false), $value);
		}
		$html .= '</select>';
		echo $html;
	}

	/**
	 * Displays week select field
	 *
	 * @param array $week An array of 2 weeks
	 * @return void
	 */
	private function display_week($week) {
		$weeks = array('1st' => __('1st Week', 'stops-core-theme-and-plugin-updates'), '2nd' => __('2nd Week', 'stops-core-theme-and-plugin-updates'));
		$html = '';
		$html .= '<select class="eum_week_number" name="eum_cron_week">';
		foreach ($weeks as $key => $value) {
			$html .= sprintf('<option value="%1$s" %2$s>%3$s</option>', $key, selected($week, $key, false), $value);
		}
		$html .= '</select>';
		echo $html;
	}

	/**
	 * Displays days select field
	 *
	 * @param array $day An array of days of a month
	 * @return void
	 */
	private function display_day_number($day) {
		$days = $this->get_days();
		$html = '<div class="eum_day_number_wrapper">';
		$html .= sprintf('<label>%s</label>', __('Day Number:', 'stops-core-theme-and-plugin-updates'));
		$html .= '<select class="eum_day_number" name="eum_cron_day_number">';
		foreach ($days as $value) {
			$html .= sprintf('<option value="%1$s" %2$s>%3$s</option>', $value, selected($day, $value, false), $value);
		}
		$html .= '</select></div>';
		echo $html;
	}

	/**
	 * Displays time field
	 *
	 * @param string $time Time value
	 * @return void
	 */
	private function display_time($time) {
		$html = '';
		$html .= sprintf('<label>%s', __('Time:', 'stops-core-theme-and-plugin-updates'));
		$html .= sprintf('<input type="time" name="eum_cron_time" value="%s">', $time);
		$html .= '</label>';
		echo $html;
	}

	/**
	 * An array of week days
	 *
	 * @return array An array of week days
	 */
	private function get_week_days() {
		$week_days = array();
		global $wp_locale;
		for ($day_index = 1; $day_index < 8; $day_index++) {
			$week_days[$day_index] = $wp_locale->get_weekday($day_index - 1);
		}
		return $week_days;
	}

	/**
	 * Month days
	 *
	 * @return array An array of available days across all months
	 */
	private function get_days() {
		$days = array();
		for ($day=1; $day<=28; $day++) {
			$days[$day] = $day;
		}
		return $days;
	}
}
