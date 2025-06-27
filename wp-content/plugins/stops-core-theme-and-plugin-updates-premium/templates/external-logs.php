<?php
if (!defined('ABSPATH')) die('No direct access.');
if (empty($loggers)) return;
echo '<div class="external-logging">';
echo '<ul>';
$options = MPSUM_Updates_Manager::get_options('logs');
$logger_additional_options = isset($options['logger_additional_options']) ? $options['logger_additional_options'] : '';
foreach ($loggers as $logger) {
	$logger_id = strtolower(get_class($logger));
	$logger_options = $logger->get_options_list();
	echo '<li class="eum_logger_type">';
	printf('<label for="%1$s"><input type="checkbox" id="%1$s" name="eum_logger_type[%1$s]" value="1" %3$s />%2$s</label>', $logger_id, $logger->get_description(), checked($logger->is_enabled(), 1, false));
	if (!empty($logger_options)) {
		foreach ($logger_options as $key => $value) {
			$option_value = isset($logger_additional_options[$key]) ? $logger_additional_options[$key] : '';
			printf('<div class="eum_logger_additional_options"><input type="text" name="logger_additional_options[%1$s]" id="%1$s" size="50" value="%2$s" placeholder="%3$s"></div>', $key, $option_value, $value[0]);
		}
	}
	echo '</li>';
}
echo '</ul></div>';
printf('<input type="submit" name="submit" id="save-logs-settings" class="button button-primary" value="%1$s" />', esc_attr__('Save', 'stops-core-theme-and-plugin-updates'));
