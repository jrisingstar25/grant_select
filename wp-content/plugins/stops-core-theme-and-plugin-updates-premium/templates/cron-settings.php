<?php if (!defined('ABSPATH')) die('No direct access'); ?>
<div class="eum-advanced-settings-container schedule-update">
<?php
printf('<h3>%s</h3>', esc_html__('Automatic update scheduling', 'stops-core-theme-and-plugin-updates'));
$options = MPSUM_Updates_Manager::get_options('advanced');
if (empty($options['cron_schedule'])) {
	$options['cron_schedule'] = 'twicedaily';
}
if (!isset($options['cron_days_list'])) {
	if ('daily' !== $options['cron_schedule']) {
		$options['cron_days_list'] = array(1, 2, 3, 4, 5, 6, 7);
	} else {
		$options['cron_days_list'] = array();
	}
}
$cron = MPSUM_Update_Cron::get_instance();
?>
<select class="eum_cron_schedule" name="eum_cron_schedule">
	<option value="every_3_hours" <?php selected('every_3_hours', $options['cron_schedule']); ?>><?php printf(__('Every %d hours', 'stops-core-theme-and-plugin-updates'), 3); ?></option>
	<option value="every_6_hours" <?php selected('every_6_hours', $options['cron_schedule']); ?>><?php printf(__('Every %d hours', 'stops-core-theme-and-plugin-updates'), 6); ?></option>
	<option value="twicedaily" <?php selected('twicedaily', $options['cron_schedule']); ?>><?php echo sprintf(__('Every %d hours', 'stops-core-theme-and-plugin-updates'), 12).' '.__('(WordPress default)', 'stops-core-theme-and-plugin-updates'); ?></option>
	<option value="daily" <?php selected('daily', $options['cron_schedule']); ?>><?php _e('Daily', 'stops-core-theme-and-plugin-updates'); ?></option>
	<option value="weekly" <?php selected('weekly', $options['cron_schedule']); ?>><?php _e('Weekly', 'stops-core-theme-and-plugin-updates');?></option>
	<option value="fortnightly" <?php selected('fortnightly', $options['cron_schedule']); ?>><?php _e('Fortnightly', 'stops-core-theme-and-plugin-updates');?></option>
	<option value="monthly" <?php selected('monthly', $options['cron_schedule']); ?>><?php _e('Monthly', 'stops-core-theme-and-plugin-updates'); ?></option>
</select>
<?php $cron->display_date_time_options($options); ?>
<?php printf('<p class="submit"><input type="submit" name="submit" id="save-cron-schedule" class="button button-primary" value="%s"></p>', esc_attr__('Save scheduling', 'stops-core-theme-and-plugin-updates')); ?>
<?php

$time = $cron->calculate_next_event(true);
?>
<p>
	<?php
	if (isset($options['all_updates']) && 'off' == $options['all_updates']) {
		_e('You have turned off all updates; therefore scheduling will take no effect.', 'stops-core-theme-and-plugin-updates');
	} elseif ($time > 0) {
		$timezone = get_option('timezone_string');
		$gmt_offset = get_option('gmt_offset');
		if (empty($timezone)) {
			$timezone = sprintf(__('UTC offset: %s', 'stops-core-theme-and-plugin-updates'), $gmt_offset);
		}
		printf(__('Your next scheduled event is at: %s', 'stops-core-theme-and-plugin-updates'), '<span id="eum-next-cron-schedule">' . get_date_from_gmt(date('Y-m-d H:i:s', $time)) . ' ' . $timezone  . '</span>');
	} else {
		printf(__("Automatic update events do not appear to have the days configured for them to take place.", 'stops-core-theme-and-plugin-updates'));
	}
	?>
</p>
</div>
