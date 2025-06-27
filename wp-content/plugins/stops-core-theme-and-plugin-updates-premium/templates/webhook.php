<?php
if (!defined('ABSPATH')) die('No direct access');

$options = get_site_option('easy_updates_manager_webhook', false);
?>
<div class="eum-advanced-settings-container eum-webhook">
	<h3><?php echo esc_html(_x('Webhook', 'Advanced title heading', 'stops-core-theme-and-plugin-updates'));?></h3>
	<p><?php echo esc_html__('Enable a webhook that, if visited, will force the running of automatic updates.', 'stops-core-theme-and-plugin-updates').' '.esc_html__('Do not share this URL publicly.', 'stops-core-theme-and-plugin-updates'); ?></p>
	<div id="webhooks">
	<?php
	if (false === $options) {
		?>
		<div id="eum-webhook-url-wrapper" class="eum-hidden">
			<?php echo esc_html__('Your Webhook URL is:', 'stops-core-theme-and-plugin-updates') . ' ' . '<input id="eum-webhook-url" />' . '<button id="eum-webhook-copy">' . esc_html__('Copy', 'stops-core-theme-and-plugin-updates') . '</button>';
			printf('<p class="submit"><input type="submit" name="refresh-webhook" id="refresh-webhook" class="button button-secondary" value="%s"></p>', esc_attr__('Refresh Webhook', 'stops-core-theme-and-plugin-updates'));
			?>
		</div>
	<?php
	}
	if (is_array($options) && 'true' === $options['enabled']) {
		?>
		<div id="eum-webhook-url-wrapper">
			<?php echo esc_html(__('Your Webhook URL is:', 'stops-core-theme-and-plugin-updates')) . ' ' . '<input type="text" id="eum-webhook-url" value="' . esc_url_raw($options['hook_url']) . '" />' . '<button id="eum-webhook-copy">' . esc_html__('Copy', 'stops-core-theme-and-plugin-updates') . '</button>';
;
			printf('<p class="submit"><input type="submit" name="refresh-webhook" id="refresh-webhook" class="button button-secondary" value="%s"></p>', esc_attr__('Refresh Webhook', 'stops-core-theme-and-plugin-updates'));
			?>
		</div>
		<?php
	}
	if (false === $options) {
		printf('<p class="submit"><input type="submit" name="enable-webhook" id="enable-webhook" class="button button-primary" value="%s"></p>', esc_attr__('Enable Webhook', 'stops-core-theme-and-plugin-updates'));
	} else {
		printf('<p class="submit"><input type="submit" name="disable-webhook" id="disable-webhook" class="button button-primary" value="%s"></p>', esc_attr__('Disable Webhook', 'stops-core-theme-and-plugin-updates'));
	}
	
	?>
	</div>
</div>
