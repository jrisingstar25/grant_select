<?php
if (!defined('ABSPATH')) die('No direct access');

$options = MPSUM_Updates_Manager::get_options('core');
?>
<div class="eum-advanced-settings-container eum-version-control">
	<h3><?php echo esc_html(_x('Version control protection', 'Advanced title heading', 'stops-core-theme-and-plugin-updates'));?></h3>
	<p><?php esc_html_e('Enabling this option will skip plugins or themes under version control, as detected by the presence of a .git, .svn or .hg directory.', 'stops-core-theme-and-plugin-updates'); ?></p>
	<div id="version-control-protection">
	<?php
	if (!isset($options['version_control']) || 'off' === $options['version_control']) {
		printf('<p class="submit"><input type="submit" name="enable-version-control" id="enable-version-control" class="button button-primary" value="%s"></p>', esc_attr__('Enable version control protection', 'stops-core-theme-and-plugin-updates'));
	} else {
		printf('<p class="submit"><input type="submit" name="disable-version-control" id="disable-version-control" class="button button-primary" value="%s"></p>', esc_attr__('Disable version control protection', 'stops-core-theme-and-plugin-updates'));
	}
	?>
	</div>
</div>
