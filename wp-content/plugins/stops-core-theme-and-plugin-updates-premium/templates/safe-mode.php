<?php if (!defined('ABSPATH')) die('No direct access.'); ?>
<div class="eum-advanced-settings-container plugin-safe-mode">
	<h3><?php echo esc_html(_x('Safe mode', 'Advanced title heading', 'stops-core-theme-and-plugin-updates'));?></h3>
	<div class="mpsum-notice mpsum-regular"><?php echo esc_html__('"Safe mode" will block automatic updates to plugin and theme updates if the update states a minimum PHP or WP version requirement that is not met by this site/server.', 'stops-core-theme-and-plugin-updates').' '.esc_html__('This can prevent your site crashing due to running incompatible plugins/themes.', 'stops-core-theme-and-plugin-updates'); ?></div>
	<div id="safe-mode">
	<?php
	$options = MPSUM_Updates_Manager::get_options('core');
	if (!isset($options['safe_mode']) || 'off' === $options['safe_mode']) {
		printf('<p class="submit"><input type="submit" name="enable-safe-mode" id="enable-safe-mode" class="button button-primary" value="%s"></p>', esc_attr__('Enable safe mode', 'stops-core-theme-and-plugin-updates'));
	} else {
		printf('<p class="submit"><input type="submit" name="disable-safe-mode" id="disable-safe-mode" class="button button-primary" value="%s"></p>', esc_attr__('Disable safe mode', 'stops-core-theme-and-plugin-updates'));
	}
	?>
	</div>
</div>
