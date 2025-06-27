<?php
if (!defined('ABSPATH')) die('No direct access');
$enable_notices = get_site_option('easy_updates_manager_enable_notices', 'on');
$eum_white_author = get_site_option('easy_updates_manager_author', __('Easy Updates Manager Team', 'stops-core-theme-and-plugin-updates'));
$eum_white_url = get_site_option('easy_updates_manager_url', esc_url('https://easyupdatesmanager.com/'));
$eum_white_label = get_site_option('easy_updates_manager_name', __('Easy Updates Manager Premium', 'stops-core-theme-and-plugin-updates'));
?>
<div class="eum-advanced-settings-container eum-whitelabel" id="eum-whitelabel">
	<h3><?php echo esc_html(_x('White-label Easy Updates Manager', 'Advanced title heading', 'stops-core-theme-and-plugin-updates'));?></h3>
	<div class="mpsum-notice"><p><?php esc_html_e('White-label Easy Updates Manager, which might be useful for some types of client site.', 'stops-core-theme-and-plugin-updates'); ?></p></div>
	<div><p>
		<label for="eum-whitelabel-text"><?php echo esc_html('White-label the plugin name', 'stops-core-theme-and-plugin-updates'); ?></label>:&nbsp;
		<input type="text" id="eum-whitelabel-text" class="regular-text" value="<?php echo esc_attr($eum_white_label);?>" />
	</p></div>
	<div><p>
		<label for="eum-whitelabel-author"><?php echo esc_html('White-label the plugin author', 'stops-core-theme-and-plugin-updates'); ?>:&nbsp;
		<input type="text" id="eum-whitelabel-author" class="regular-text" value="<?php echo esc_attr($eum_white_author); ?>" />
	</p></div>
	<div><p>
		<label for="eum-whitelabel-url"><?php echo esc_html('White-label the plugin URL', 'stops-core-theme-and-plugin-updates'); ?>:&nbsp;
		<input type="text" id="eum-whitelabel-url" class="regular-text" value="<?php echo esc_attr($eum_white_url); ?>" />
	</p></div>
	<div id="eum-notices"><p>
		<input type="checkbox" name="whitelist-notices" id="whitelist-notices" <?php checked('on', $enable_notices, true); ?> /> <label for="whitelist-notices"><?php esc_html_e('Enable (tasteful) notices from the Easy Updates Manager team on your Easy Updates Manager dashboard page about other plugins/products of potential interest.', 'stops-core-theme-and-plugin-updates'); ?>
		<p>
		<?php
		printf('<p class="submit"><input type="submit" name="whitelist-save" id="whitelist-save" class="button button-primary" value="%s" />&nbsp;&nbsp;<input type="submit" name="whitelist-reset" id="whitelist-reset" class="button button-secondary btn btn-secondary" value="%s"></p>', esc_attr__('Save', 'stops-core-theme-and-plugin-updates'), esc_attr__('Reset', 'stops-core-theme-and-plugin-updates'));
		?>
		</p>
	</p></div>
</div>
