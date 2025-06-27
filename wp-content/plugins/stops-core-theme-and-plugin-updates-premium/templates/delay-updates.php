<?php
if (!defined('ABSPATH')) die('No direct access');

$options = MPSUM_Updates_Manager::get_options('advanced');

if (!isset($options['delay_updates'])) $options['delay_updates'] = '0';
echo '<div class="eum-advanced-settings-container delay-updates">';
printf('<h3>%s</h3>', esc_html__('Delay automatic updates to avoid short-lived (possibly faulty) releases', 'stops-core-theme-and-plugin-updates'));
printf('<p>%s<br>%s</p>', __('If an update has problems, often the developer will make a new release soon afterwards.', 'stops-core-theme-and-plugin-updates'), __('You can delay your automatic updates for the specified number of days to gain confidence that the update has not needed replacing by another.', 'stops-core-theme-and-plugin-updates'));
printf('<p><b>%s</b>%s</p>', __('Warning: ', 'stops-core-theme-and-plugin-updates'), __("If an update was fixing a security issue, then your site will be unfixed for longer (unfortunately WordPress's update system does not have a way to indicate which releases are security fixes).", 'stops-core-theme-and-plugin-updates'));
?>
	<label for="delay-updates">
		<input type="number" id="delay-updates" name="delay-updates" min="0" value="<?php echo $options['delay_updates']; ?>">
		<?php _e('days', 'stops-core-theme-and-plugin-updates'); ?>
	</label>
<?php
printf('<p class="submit"><input type="submit" name="submit" id="save-delay-updates" class="button button-primary" value="%s"></p>', esc_attr__('Save delay', 'stops-core-theme-and-plugin-updates'));
echo '</div>';
