<?php
if (!defined('ABSPATH')) die('No direct access');
?>
<div class="eum-advanced-settings-container unmaintained-plugins">
	<h3><?php esc_html_e('Alert for unmaintained plugins', 'stops-core-theme-and-plugin-updates');?></h3>
	<div class="mpsum-notice mpsum-regular"><?php esc_html_e('When visiting the plugins screen, you will be alerted to any outdated (unmaintained) plugins.', 'stops-core-theme-and-plugin-updates'); ?></div>
	<div id="safe-mode">
	<?php
	$options = MPSUM_Updates_Manager::get_options('core');
	if (!isset($options['unmaintained_plugins']) || 'off' === $options['unmaintained_plugins']) {
		printf('<p class="submit"><input type="submit" name="enable-unmaintained-plugins-check" id="enable-unmaintained-plugins-check" class="button button-primary" value="%s"></p>', esc_attr__('Enable plugin checks', 'stops-core-theme-and-plugin-updates'));
	} else {
		printf('<p class="submit"><input type="submit" name="disable-unmaintained-plugins-check" id="disable-unmaintained-plugins-check" class="button button-primary" value="%s"></p>', esc_attr__('Disable plugin checks', 'stops-core-theme-and-plugin-updates'));
	}
	?>
	</div>

</div>
