<?php
if (!defined('ABSPATH')) die('No direct access');

// All is well, output the list table
$list_table = new MPSUM_Logs_List_Table();
$list_table->prepare_items();
?>
<html>
	<head>
		<title>
		<?php
		$sitename = is_multisite() ? get_site_option('site_name') : get_option('blogname');
		/**
		 * Change the title of the export dialog.
		 *
		 * @since 8.0.1
		 *
		 * @param string Title tag of the modal pop-up window
		 * @param string Site name
		 */
		$title_tag = apply_filters('eum_export_dialog_title_tag', sprintf(__('Updates log report for %s', 'stops-core-theme-and-plugin-updates'), $sitename));
		echo esc_html($title_tag);
		?>
		</title>
		<?php
		$min_or_not = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
		// Print styles and scripts we need
		wp_register_script('mpsum-log-export-js', MPSUM_Updates_Manager::get_plugin_url('/js/eum-logs-export-dialog' . $min_or_not . '.js'), array(), EASY_UPDATES_MANAGER_VERSION);
		wp_print_scripts(array( 'jquery', 'common', 'mpsum-log-export-js'));

		wp_register_style('mpsum-premium-css', MPSUM_Updates_Manager::get_plugin_url('/css/style-premium' . $min_or_not . '.css'), array(), EASY_UPDATES_MANAGER_VERSION, 'screen');
		wp_register_style('mpsum-export-log-print-css', MPSUM_Updates_Manager::get_plugin_url('/css/style-export-logs-print' . $min_or_not . '.css'), array(), EASY_UPDATES_MANAGER_VERSION, 'print');
		wp_print_styles(array('common', 'mpsum-premium-css', 'mpsum-export-log-print-css'));
		?>
		<script type="text/javascript">
			var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
			<?php
			$export_json = array();
			/**
			 * Change the i18n of the log export dialog.
			 *
			 * @since 8.0.1
			 *
			 * @param array Array of internationalized strings
			 */
			$export_json = apply_filters('eum_export_i18n', array());
			?>
			var mpsum_export_logs_i18n = <?php echo !empty($export_json) ? json_encode($export_json) : '{}'; ?>;
		</script>
	</head>
<body class="export-logs">
	<h3>
	<?php
	/**
	 * Change the title of the export dialog.
	 *
	 * @since 8.0.1
	 *
	 * @param string Export Dialog Title
	 * @param string Site name
	 */
	$title = apply_filters('eum_export_dialog_title', sprintf(__('Updates logs report for %s', 'stops-core-theme-and-plugin-updates'), $sitename), $sitename);
	echo esc_html($title);
	?>
	</h3>
	<table class="wp-list-table export-table <?php echo implode(' ', $list_table->get_table_classes()); ?>">
		<thead>
		<tr>
			<?php $list_table->print_column_headers(); ?>
		</tr>
		</thead>

		<tbody id="the-list">
			<?php $list_table->display_rows(); ?>
		</tbody>

		<tfoot>
		<tr>
			<?php $list_table->print_column_headers(false); ?>
		</tr>
		</tfoot>
	</table>
	<div id="export-options">
		<div id="eum-loading" style="display: none;"><?php esc_html_e('Loading...', 'stops-core-theme-and-plugin-updates');?></div>
		<form id="form-export-options" method="post">
			<input type="hidden" id="form-nonce" name="form-nonce" value="<?php echo isset($_REQUEST['nonce']) ? esc_attr(sanitize_text_field($_REQUEST['nonce'])) : '';?>" />
			<input type="hidden" id="date-start" name="date-start" value="<?php echo isset($_REQUEST['date_start']) ? esc_attr(sanitize_text_field($_REQUEST['date_start'])) : '';?>" />
			<input type="hidden" id="date-end" name="date-end" value="<?php echo isset($_REQUEST['date_end']) ? esc_attr(sanitize_text_field($_REQUEST['date_end'])) : '';?>" />
			<button id="export-print" class="button button-secondary"><?php esc_html_e('Print', 'stops-core-theme-and-plugin-updates'); ?></button>&nbsp;
			<button id="export-csv" class="button button-secondary"><?php esc_html_e('Download CSV', 'stops-core-theme-and-plugin-updates'); ?></button>&nbsp;
			<button id="export-json" class="button button-secondary"><?php esc_html_e('Download JSON', 'stops-core-theme-and-plugin-updates'); ?></button>
		</form>
	</div>
</body>