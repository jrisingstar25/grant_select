<?php
if (!defined('ABSPATH')) die('No direct access');
?>
<div class="eum-advanced-settings-container check-plugins">
	<h3><?php esc_html_e('Check if plugins have been removed from the WordPress plugin directory', 'stops-core-theme-and-plugin-updates');?></h3>
	<div class="mpsum-notice mpsum-regular">
		<p><?php echo esc_html__('Check for plugins which have been removed from the WordPress Plugin Directory.', 'stops-core-theme-and-plugin-updates').' '.esc_html__('The results are cached for some hours; to clear your cache check the \'Force check\' option below.', 'stops-core-theme-and-plugin-updates'); ?></p>
		<input type="checkbox" id="eum-check-plugins-force" name="eum-plugins-removed-force" /> <label for="eum-check-plugins-force"><?php esc_html_e('Force check', 'stops-core-theme-and-plugin-updates'); ?></label>
	</div>
	<button type="button" style="clear:left;" class="button-primary" id="eum-check-plugins"><?php _e('Check plugins', 'stops-core-theme-and-plugin-updates');?></button>

	<div id="eum-check-plugins-status" class="mpsum-error mpsum-bold" style="display:none"></div>

</div>
