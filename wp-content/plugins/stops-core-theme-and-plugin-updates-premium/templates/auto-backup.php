<?php
if (!defined('ABSPATH')) die('No direct access');
?>
<div id="eum-auto-backup" class="eum-advanced-settings-container auto-backup">
<?php
printf('<h3>%s</h3>', esc_html__('Automatic Backup', 'stops-core-theme-and-plugin-updates'));
$utils = MPSUM_Utils::get_instance();
$updraftplus = $utils->is_installed('updraftplus');
if (true === $updraftplus['installed'] && true === $updraftplus['active']) {
	$options = MPSUM_Updates_Manager::get_options('advanced');
	if (empty($options['auto_backup'])) {
		$options['auto_backup'] = 'on';
	}
	if ('on' === $options['auto_backup']) {
		printf('<p id="eum-auto-backup-description">%s</p>', __('Your site will be backed up automatically with UpdraftPlus before updating.', 'stops-core-theme-and-plugin-updates'));
		printf('<p class="submit"><input type="submit" name="submit" id="eum_ud_disable_auto_backup" class="button button-primary" value="%s"></p>', esc_attr__('Disable auto backups', 'stops-core-theme-and-plugin-updates'));
	} else {
		printf('<p id="eum-auto-backup-description">%s</p>', __('Your site will NOT be backed up automatically before updating.', 'stops-core-theme-and-plugin-updates'));
		printf('<p class="submit"><input type="submit" name="submit" id="eum_ud_enable_auto_backup" class="button button-primary" value="%s"></p>', esc_attr__('Enable auto backup', 'stops-core-theme-and-plugin-updates'));
	}
} else {
	if (true === $updraftplus['installed'] && false === $updraftplus['active']) {
		$can_activate = is_multisite() ? current_user_can('manage_network_plugins') : current_user_can('activate_plugins');
		if ($can_activate) {
			$activate_link = is_multisite() ? network_admin_url('plugins.php?action=activate&plugin='.$updraftplus['name']) : self_admin_url('plugins.php?action=activate&plugin='.$updraftplus['name']);
			$url = esc_url(wp_nonce_url(
				$activate_link,
				'activate-plugin_'.$updraftplus['name']
			));
			$url_text = __('Follow this link to activate it.', 'stops-core-theme-and-plugin-updates');
			$anchor = "<a href=\"{$url}\">{$url_text}</a>";
		}
		$required_plugin = __('Automatic backups are done using UpdraftPlus.', 'stops-core-theme-and-plugin-updates');
		printf('<div class="mpsum-notice mpsum-bold id="eum-auto-backup-description">%s %s</div>', $required_plugin, $anchor);
	} else {
		if (current_user_can('install_plugins')) {
			$url = esc_url(wp_nonce_url(
				is_multisite() ? network_admin_url('update.php?action=install-plugin&plugin=updraftplus') : self_admin_url('update.php?action=install-plugin&plugin=updraftplus'),
				'install-plugin_updraftplus'
			));
			$url_text = __('Follow this link to install it.', 'stops-core-theme-and-plugin-updates');
			$anchor = "<a href=\"{$url}\">{$url_text}</a>";
			$required_plugin = __('Automatic backups are done using UpdraftPlus.', 'stops-core-theme-and-plugin-updates');
			printf('<p id="eum-auto-backup-description">%s %s</p>', $required_plugin, $anchor);
		}
	}
}
?>
</div>
