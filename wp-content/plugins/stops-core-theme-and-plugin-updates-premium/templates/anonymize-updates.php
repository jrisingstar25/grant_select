<?php
if (!defined('ABSPATH')) die('No direct access allowed');
?>
<div class="eum-advanced-settings-container anonymize-updates">
	<h3><?php esc_html_e('Anonymize updates requests', 'stops-core-theme-and-plugin-updates'); ?></h3>
	<p>
		<?php echo __('This feature adjusts what data is sent to wordpress.org when sending updates requests.', 'stops-core-theme-and-plugin-updates').' '.__('By default, WordPress sends various pieces of analytics together with your updates request.', 'stops-core-theme-and-plugin-updates'); ?>
	</p>
	<?php
		$options = MPSUM_Updates_Manager::get_options('advanced');
		$anonymize_updates = isset($options['anonymize_updates']) ? $options['anonymize_updates'] : 'default';
	?>
	<label for="anonymize_updates_default">
		<input type="radio" name="anonymize_updates" id="anonymize_updates_default" value="default" <?php checked($anonymize_updates, 'default'); ?> />
		<?php _e('Send full data (Default)', 'stops-core-theme-and-plugin-updates'); ?>
	</label>
	<label for="anonymize_updates_anonymize">
		<input type="radio" name="anonymize_updates" id="anonymize_updates_anonymize" value="anonymous" <?php checked($anonymize_updates, 'anonymous'); ?> />
		<?php _e('Anonymous, with blank data for the site URL', 'stops-core-theme-and-plugin-updates'); ?>
	</label>
	<label for="anonymize_updates_dummy_data">
		<input type="radio" name="anonymize_updates" id="anonymize_updates_dummy_data" value="random" <?php checked($anonymize_updates, 'random'); ?> />
		<?php _e('Anonymous, with fake data for the site URL', 'stops-core-theme-and-plugin-updates'); ?>
	</label>
	<?php printf('<input type="submit" name="submit" id="save-anonymize-update-option" class="button button-primary" value="%s">', esc_attr__('Save', 'stops-core-theme-and-plugin-updates')); ?>
</div>
