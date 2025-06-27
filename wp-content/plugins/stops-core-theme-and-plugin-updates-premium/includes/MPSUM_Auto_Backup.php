<?php

if (!defined('ABSPATH')) die('No direct access.');

if (class_exists('MPSUM_Auto_Backup')) return;

/**
 * Class MPSUM_Auto_Backup handles automatic backups before updates
 */
class MPSUM_Auto_Backup {

	private $already_backed_up = array();

	private $is_autobackup_core = null;

	/**
	 * Adds necessary filters and actions
	 */
	private function __construct() {
	
		// Disabled 24-Jan-2020 for investigation of issues
		return;
	
		add_action('eum_advanced_headings', array($this, 'heading'), 11);
		add_action('eum_advanced_settings', array($this, 'settings'), 11);

		add_action('init', array($this, 'auto_backup_init'));
	}

	/**
	 * Runs upon the WP action init
	 */
	public function auto_backup_init() {
		if (defined('EUM_DOING_FORCE_UPDATES') && EUM_DOING_FORCE_UPDATES) {
			return;
		}
		if ($this->is_ud_installed_and_active()) {
			if (class_exists('UpdraftPlus_Options') && ! UpdraftPlus_Options::get_updraft_option('updraft_autobackup_default', true)) {
				add_action('pre_auto_update', array($this, 'pre_auto_update'), 10, 2);
				add_filter('updraftplus_dirlist_wpcore_override', array($this, 'dirlist_wpcore_override'), 20, 2);
				add_filter('updraft_backupable_file_entities', array($this, 'backupable_file_entities'), 20, 2);
				add_filter('updraftplus_backup_makezip_wpcore', array($this, 'backup_makezip_wpcore'), 20, 3);
			}
		}
	}

	/**
	 * Check if UpdraftPlus is installed and active
	 *
	 * @return bool Returns true if UD is installed and active, otherwise false.
	 */
	private function is_ud_installed_and_active() {
		$options = MPSUM_Updates_Manager::get_options('advanced');
		if (!isset($options['auto_backup'])) {
			$options['auto_backup'] = 'on';
		}
		if ('off' == $options['auto_backup']) {
			return false;
		} else {
			$utils = MPSUM_Utils::get_instance();
			$updraftplus = $utils->is_installed('updraftplus');
			return $updraftplus['installed'] && $updraftplus['active'];
		}
		return false;
	}

	/**
	 * Returns singleton instance of this class
	 *
	 * @return object MPSUM_Auto_Backup Singleton Instance
	 */
	public static function get_instance() {
		static $instance = null;
		if (null === $instance) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Outputs feature heading
	 */
	public function heading() {
		printf('<div data-menu_name="auto-backup">%s <span class="eum-advanced-menu-text">%s</span></div>', '<i class="material-icons">backup</i>', esc_html__('Automatic backup', 'stops-core-theme-and-plugin-updates'));
	}

	/**
	 * Outputs feature settings
	 */
	public function settings() {
		Easy_Updates_Manager()->include_template('auto-backup.php');
	}

	/**
	 * Decides available update type and enable auto backup and reschedules auto update
	 *
	 * @param  string $type This is the type such as plugin or theme
	 * @param  object $item This is the item
	 */
	public function pre_auto_update($type, $item) {
		// Can also be 'translation'. We don't auto-backup for those.
		if ('plugin' == $type || 'theme' == $type) {
			$this->auto_update(true, $item, $type . 's');
		} elseif ('core' == $type) {
			$this->auto_update(true, $item, $type);
		}
	}

	/**
	 * Reschedules auto update timing and takes Auto backup
	 *
	 * @param  boolean $update Whether to update or not
	 * @param  object  $item   Item to be updated
	 * @param  string  $type   Item type
	 *
	 * @return boolean Decides whether to update or not
	 */
	public function auto_update($update, $item, $type) {
		// Get option for backups already taken.
		$auto_backup_types = get_site_option('eum_auto_backups', array());
		if (in_array($type, $auto_backup_types)) {
			return true;
		}

		if ('core' == $type) {
			// This has to be copied from WP_Automatic_Updater::should_update() because it's another reason why the eventual decision may be false.
			// If it's a core update, are we actually compatible with its requirements?
			global $wpdb;
			$php_compat = version_compare(phpversion(), $item->php_version, '>=');
			if (file_exists(WP_CONTENT_DIR . '/db.php') && empty($wpdb->is_mysql))
				$mysql_compat = true;
			else $mysql_compat = version_compare($wpdb->db_version(), $item->mysql_version, '>=');
			if (!$php_compat || !$mysql_compat)
				return false;
		}

		// Go ahead - it's auto-backup-before-auto-update time.
		// Add job data to indicate that a resumption should be scheduled if the backup completes before the cloud-backup stage
		add_filter('updraftplus_initial_jobdata', array($this, 'initial_jobdata'));
		add_filter('updraftplus_initial_jobdata', array($this, 'initial_jobdata2'));

		// Reschedule the real background update for 10 minutes from now (i.e. lessen the risk of a timeout by chaining it).
		$this->reschedule(600);

		global $updraftplus;

		if (!is_object($updraftplus) || !is_callable(array($updraftplus, 'get_backupable_file_entities'))) return false;

		$backup_database = !in_array('db', $this->already_backed_up);

		if ('core' == $type) {
			$entities = $updraftplus->get_backupable_file_entities();
			if (isset($entities['wpcore'])) {
				$backup_files = true;
				$backup_files_array = array('wpcore');
			}
		} else {
			$backup_files = true;
			$backup_files_array = array($type);
		}

		if ('core' == $type) {
			$this->is_autobackup_core = true;
		}

		$updraftplus->boot_backup($backup_files, $backup_database, $backup_files_array, true);

		$this->already_backed_up[] = $type;
		if ($backup_database) {
			$this->already_backed_up[] = 'db';
			$auto_backup_types[] = 'db';
		}
		$auto_backup_types[] = $type;
		update_site_option('eum_auto_backups', $auto_backup_types);

		// The backup apparently completed. Reschedule for very soon, in case not enough PHP time remains to complete an update too.
		$this->reschedule(120);

		// But then, also go ahead anyway, in case there's enough time (we want to minimise the time between the backup and the update)
		return $update;
	}

	/**
	 * Modifies job data to indicate that a resumption should be scheduled if backup completes before the cloud-backup stage
	 *
	 * @param array $jobdata An array of job data
	 *
	 * @return array An array of job data
	 */
	public function initial_jobdata($jobdata) {
		if (!is_array($jobdata)) return $jobdata;
		$jobdata[] = 'reschedule_before_upload';
		$jobdata[] = true;
		return $jobdata;
	}

	/**
	 * Modifies job data to indicate that this is a automatic backup before update
	 *
	 * @param array $jobdata An array of job data
	 *
	 * @return array An array of job data
	 */
	public function initial_jobdata2($jobdata) {
		if (!is_array($jobdata)) return $jobdata;
		$jobdata[] = 'is_autobackup';
		$jobdata[] = true;
		$jobdata[] = 'label';
		$jobdata[] = __('Automatic backup before update', 'updraftplus');
		return $jobdata;
	}

	/**
	 * For WordPress core updates, Core files are added to potentially backed up file array
	 * regardless of user settings in UpdraftPlus plugin
	 *
	 * @param  array   $arr       An array of backupable file types
	 * @param  boolean $full_info A boolean flag to indicate file entities details type
	 *
	 * @return array An array of backupable file entities
	 */
	public function backupable_file_entities($arr, $full_info) {
		if ($full_info) {
			$arr['wpcore'] = array(
				'path' => untrailingslashit(ABSPATH),
				'description' => apply_filters('updraft_wpcore_description', __('WordPress core (including any additions to your WordPress root directory)', 'stops-core-theme-and-plugin-updates')),
				'htmltitle' => sprintf(__('WordPress root directory server path: %s', 'stops-core-theme-and-plugin-updates'), ABSPATH)
			);
		} else {
			$arr['wpcore'] = untrailingslashit(ABSPATH);
		}
		return $arr;
	}

	/**
	 * Reschedules automatic update to perform auto backup before update
	 *
	 * @param integer $how_long Seconds to reschedule
	 */
	private function reschedule($how_long) {
		if (!$how_long) return;
		wp_clear_scheduled_hook('wp_maybe_auto_update');
		wp_schedule_single_event(time() + $how_long, 'wp_maybe_auto_update');
	}

	/**
	 * $whichdir will equal untrailingslashit(ABSPATH) (is ultimately sourced from our backupable_file_entities filter callback)
	 *
	 * @param  string $whichdir
	 * @param  string $backup_file_basename
	 * @param  string $index
	 * @return array
	 */
	public function backup_makezip_wpcore($whichdir, $backup_file_basename, $index) {

		global $updraftplus, $updraftplus_backup;

		// Actually create the thing

		$wpcore_dirlist = $this->backup_wpcore_dirlist($whichdir, true);

		if (count($wpcore_dirlist) > 0) {
			$created = $updraftplus_backup->create_zip($wpcore_dirlist, 'wpcore', $backup_file_basename, $index);
			if (is_string($created) || is_array($created)) {
				return $created;
			} else {
				$updraftplus->log("WP Core backup: create_zip returned an error");
				return false;
			}
		} else {
			$updraftplus->log("No backup of WP core directories: there was nothing found to back up");
			$updraftplus->log(sprintf(__("No backup of %s directories: there was nothing found to back up", 'stops-core-theme-and-plugin-updates'), __('WordPress Core', ' stops-core-theme-and-plugin-updates')), 'error');
			return false;
		}

	}

	/**
	 * Returns WordPress directory list excluding `wp-content`
	 *
	 * @param string $whichdir Directory to backup
	 * @param bool   $logit    Decides whether to log or not
	 *
	 * @return array List of files and folders to backup
	 */
	public function backup_wpcore_dirlist($whichdir, $logit = false) {

		// Need to properly analyse the plugins, themes, uploads, content paths in order to strip them out (they may have various non-default manual values)

		global $updraftplus;

		if (false !== ($wpcore_dirlist = apply_filters('updraftplus_dirlist_wpcore_override', false, $whichdir))) return $wpcore_dirlist;

		$possible_backups = $updraftplus->get_backupable_file_entities(false);
		// We don't want to exclude the very thing we are backing up
		unset($possible_backups['wpcore']);
		// We do want to exclude everything in wp-content
		$possible_backups['wp-content'] = WP_CONTENT_DIR;

		$possible_backups_dirs = array();

		foreach ($possible_backups as $key => $dir) {
			if (is_array($dir)) {
				foreach ($dir as $ind => $rdir) {
					if (!empty($rdir)) $possible_backups_dirs[$rdir] = $key.$ind;
				}
			} else {
				if (!empty($dir)) $possible_backups_dirs[$dir] = $key;
			}
		}

		// Create an array of directories to be skipped
		$exclude = UpdraftPlus_Options::get_updraft_option('updraft_include_wpcore_exclude', '');
		if ($logit) $updraftplus->log("Exclusion option setting (wpcore): ".$exclude);
		// Make the values into the keys
		$wpcore_skip = array_flip(preg_split("/,/", $exclude));
		$wpcore_skip['wp_content'] = 0;

		// Removing the slash is important (though ought to be redundant by here); otherwise path matching does not work
		$wpcore_dirlist = $updraftplus->compile_folder_list_for_backup(untrailingslashit($whichdir), $possible_backups_dirs, $wpcore_skip);

		return $wpcore_dirlist;

	}

	/**
	 * Makes sure to backup only the core files
	 *
	 * @param boolean $l        Boolean to decide WordPress core directory override
	 * @param array   $whichdir Directory of WordPress
	 *
	 * @return array List of files and folders of WordPress core
	 */
	public function dirlist_wpcore_override($l, $whichdir) {
		// This does not need to include everything - only code
		$possible = array('wp-admin', 'wp-includes', 'index.php', 'xmlrpc.php', 'wp-config.php', 'wp-activate.php', 'wp-app.php', 'wp-atom.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-commentsrss2.php', 'wp-cron.php', 'wp-feed.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-pass.php', 'wp-rdf.php', 'wp-register.php', 'wp-rss2.php', 'wp-rss.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php', '.htaccess');

		$wpcore_dirlist = array();
		$whichdir = trailingslashit($whichdir);

		foreach ($possible as $file) {
			if (file_exists($whichdir.$file)) $wpcore_dirlist[] = $whichdir.$file;
		}

		return (!empty($wpcore_dirlist)) ? $wpcore_dirlist : $l;
	}
}
