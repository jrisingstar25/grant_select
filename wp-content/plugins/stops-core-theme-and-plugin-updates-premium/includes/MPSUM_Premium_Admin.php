<?php
if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_Premium_Admin')) return;

/**
 * Class MPSUM_Premium_Admin to handle admin section of premium features
 */
class MPSUM_Premium_Admin {

	/**
	 * MPSUM_Premium_Admin constructor. Adds necessary hooks
	 */
	private function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_styles'));
		add_action('eum_premium_ajax_handler', array(MPSUM_Premium_Admin_Ajax::get_instance(), 'ajax_handler'), 10, 2);
	}

	/**
	 * Returns a singleton instance
	 *
	 * @return MPSUM_Premium_Admin
	 */
	public static function get_instance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Enqueues necessary scripts and stylesheets
	 */
	public function enqueue_scripts_styles() {
		$pagenow = isset($_GET['page']) ? $_GET['page'] : false;

		if ('mpsum-update-options' !== $pagenow) return;

		$min_or_not = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
		$file_path = "js/modernizr/modernizr-custom$min_or_not";
		wp_enqueue_script('mpsum-modernizr-js', MPSUM_Updates_Manager::get_plugin_url("/$file_path.js"), array(), (int) filemtime(MPSUM_Updates_Manager::get_plugin_dir("$file_path.js")), true);
		$file_path = "js/eum-premium-admin$min_or_not";
		wp_enqueue_script('mpsum-premium-js', MPSUM_Updates_Manager::get_plugin_url("/$file_path.js"), array(), (int) filemtime(MPSUM_Updates_Manager::get_plugin_dir("$file_path.js")), true);
		$file_path = "js/jquery-timepicker/jquery.timepicker$min_or_not";
		wp_enqueue_script('mpsum-timepicker-js', MPSUM_Updates_Manager::get_plugin_url("/$file_path.js"), array('jquery'), (int) filemtime(MPSUM_Updates_Manager::get_plugin_dir("$file_path.js")), true);
		$file_path = "includes/select2/select2$min_or_not";
		wp_enqueue_script('mpsum-select2-js', MPSUM_Updates_Manager::get_plugin_url("/$file_path.js"), array('jquery'), (int) filemtime(MPSUM_Updates_Manager::get_plugin_dir("$file_path.js")), true);
		$file_path = "js/jquery-timepicker/jquery.timepicker$min_or_not";
		wp_enqueue_style('mpsum-timepicker-css', MPSUM_Updates_Manager::get_plugin_url("/$file_path.css"), array(), (int) filemtime(MPSUM_Updates_Manager::get_plugin_dir("$file_path.css")));
		$file_path = "css/style-premium$min_or_not";
		wp_enqueue_style('mpsum-premium-css', MPSUM_Updates_Manager::get_plugin_url("/$file_path.css"), array(), (int) filemtime(MPSUM_Updates_Manager::get_plugin_dir("$file_path.css")));
		$file_path = "includes/select2/select2$min_or_not";
		wp_enqueue_style('mpsum-select2-css', MPSUM_Updates_Manager::get_plugin_url("/$file_path.css"), array(), (int) filemtime(MPSUM_Updates_Manager::get_plugin_dir("$file_path.css")));

		if (isset($_GET['tab']) && 'logs' === $_GET['tab']) {
			$file_path = "js/eum-logs-datepicker-init$min_or_not";
			wp_enqueue_script('mpsum-datepicker-js', MPSUM_Updates_Manager::get_plugin_url("/$file_path.js"), array('jquery', 'jquery-ui-datepicker'), (int) filemtime(MPSUM_Updates_Manager::get_plugin_dir("$file_path.js")), true);
			$file_path = "css/jquery-ui$min_or_not";
			wp_enqueue_style('jquery-ui', MPSUM_Updates_Manager::get_plugin_url("/$file_path.css"), array(), (int) filemtime(MPSUM_Updates_Manager::get_plugin_dir("$file_path.css")));
		}
	}
}
