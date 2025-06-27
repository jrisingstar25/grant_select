<?php

if (!defined('ABSPATH')) die('No direct access');

$options = MPSUM_Updates_Manager::get_options('logs');

if (!isset($options['clear_logs'])) $options['clear_logs'] = '0';

printf('<p>%s</p>', __('Clear logs after every specified number of days.', 'stops-core-theme-and-plugin-updates').' '.__('Set to 0 for no expiration.', 'stops-core-theme-and-plugin-updates'));
?>
<label for="clear-logs">
	<input type="number" id="logs-clearing" name="clear-logs" min="0" value="<?php echo absint($options['clear_logs']); ?>">
	<?php _e('days', 'stops-core-theme-and-plugin-updates'); ?>
</label>