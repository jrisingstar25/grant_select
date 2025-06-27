<?php
if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('MPSUM_Update_Notifications')) {

	/**
	 * Class to anonymize update requests send to wp.org
	 */
	class MPSUM_Update_Notifications {

		/**
		 * Holds the class instance.
		 *
		 * @since 8.0.1
		 * @access static
		 * @var MPSUM_Update_Notifications $instance
		 */
		private static $instance = null;

		/**
		 * Returns a singleton instance
		 *
		 * @return MPSUM_Update_Notifications
		 */
		public static function get_instance() {
			if (null == self::$instance) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Adds and removes necessary action and filter hooks to anonymize
		 */
		private function __construct() {

			// Add default option
			add_filter('mpsum_default_options', array($this, 'add_to_defaults'));

			// Adding necessary i18n to localized options
			add_filter('eum_i18n', array($this, 'i18n'));

			// Default Cron Schedules
			add_filter('cron_schedules', array($this, 'cron_schedules'));

			// Ensure update notifications are on
			$options = MPSUM_Updates_Manager::get_options('core');
			if (!isset($options['update_notification_updates'])) return;
			if (isset($options['update_notification_updates']) && 'off' === $options['update_notification_updates']) {
				return;
			}

			// Set up cron events
			add_action('eum_notification_updates_weekly', array($this, 'maybe_send_update_notification_email'));
			add_action('eum_notification_updates_monthly', array($this, 'maybe_send_update_notification_email'));
		}

		/**
		 * Adds custom cron schedules
		 *
		 * @param array $schedules - An array of available cron schedules
		 *
		 * @return mixed - An array of modified cron schedules
		 */
		public function cron_schedules($schedules) {
			$schedules['eum_notification_updates_weekly'] = array('interval' => 7 * 86400, 'display' => __('Once Weekly', 'stops-core-theme-and-plugin-updates'));
			$schedules['eum_notification_updates_monthly'] = array('interval' => 365.25 * 86400 / 12, 'display' => __('Once Monthly', 'stops-core-theme-and-plugin-updates'));
			return $schedules;
		}

		/**
		 * Adds translatable string to existing translation array
		 *
		 * @param array $i18n Translation array
		 *
		 * @return array Updated translation array
		 */
		public function i18n($i18n) {
			$react_i18n = $i18n['I18N'];
			if (!is_array($react_i18n)) return $i18n;

			// Populate new i18n into react's i18n
			$new_i18n = array(
				'notification_emails_label'          => __('Update notification e-mails', 'stops-core-theme-and-plugin-updates'),
				'notification_emails_description'    => __('Be notified when there are WordPress updates.', 'stops-core-theme-and-plugin-updates').' '.__('This is particularly useful if you have disabled any kind of updates.', 'stops-core-theme-and-plugin-updates'),
				'notification_emails_weekly'         => _x('Weekly', 'Weekly WordPress Updates', 'stops-core-theme-and-plugin-updates'),
				'notification_emails_monthly'        => _x('Monthly', 'Monthly WordPress Updates', 'stops-core-theme-and-plugin-updates'),
				'notification_emails_off_status'     => __('E-mail notifications for updates are now off.', 'stops-core-theme-and-plugin-updates'),
				'notification_emails_weekly_status'  => __('E-mail notifications for updates will now be sent on a weekly basis.', 'stops-core-theme-and-plugin-updates'),
				'notification_emails_monthly_status' => __('E-mail notifications of updates will now be sent on a monthly basis.', 'stops-core-theme-and-plugin-updates'),
				'notification_emails_send_now'       => _x('Send Now', 'Send email update notifications', 'stops-core-theme-and-plugin-updates'),
			);
			$i18n['I18N'] = array_merge($react_i18n, $new_i18n);
			return $i18n;
		}

		/**
		 * Add option to default options
		 *
		 * @param array $options array of default options
		 * @return array of default options
		 */
		public function add_to_defaults($options) {
			$options['update_notification_updates'] = 'off';
			return $options;
		}

		/**
		 * Send update notification email in plain text
		 */
		public function maybe_send_update_notification_email() {

			// Filter to retrieve plugin information to save as a transient
			add_filter('expiration_of_site_transient_update_plugins', array($this, 'set_plugin_updates'), 9, 2);

			// Filter to retrieve theme information to save as a transient
			add_filter('expiration_of_site_transient_update_themes', array($this, 'set_theme_updates'), 9, 2);

			// Filter to retrieve WordPress core information to save as transient
			add_filter('expiration_of_site_transient_update_core', array($this, 'set_core_updates'), 9, 2);

			// Force transient refresh and send email
			wp_version_check(array(), true);
			wp_update_plugins();
			wp_update_themes();

			// Get transients with updated updates information
			$plugins = get_site_transient('eum_plugin_updates');
			$themes = get_site_transient('eum_theme_updates');
			$core = get_site_transient('eum_core_updates');

			// Remove Filters
			remove_filter('expiration_of_site_transient_update_plugins', array($this, 'set_plugin_updates'), 9, 2);
			remove_filter('expiration_of_site_transient_update_themes', array($this, 'set_theme_updates'), 9, 2);
			remove_filter('expiration_of_site_transient_update_core', array($this, 'set_core_updates'), 9, 2);

			// Translations array to collect information
			$translations = array();

			// Get core updates
			$core_message = '';
			if (is_object($core)) {
				if (isset($core->translations) && !empty($core->translations) && is_array($core->translations)) {
					foreach ($core->translations as $translation) {
						$translations[] = $translation;
					}
				}
				if (isset($core->updates) && !empty($core->updates)) {
					$core_meta = current($core->updates);
					$old_core_version = $core->version_checked;
					$new_core_version = $core_meta->version;

					if ($old_core_version != $new_core_version) {
						// Format email for core
						$core_message .= __('== WordPress Updates ==', 'stops-core-theme-and-plugin-updates') . "\r\n\r\n";
						$core_message .= __('Current Version:', 'stops-core-theme-and-plugin-updates') . ' ' . $old_core_version . "\r\n";
						$core_message .= __('New Version:', 'stops-core-theme-and-plugin-updates') . ' ' . $new_core_version . "\r\n";
						$core_message .= __('Changelog:', 'stops-core-theme-and-plugin_updates') . ' ' . sprintf('https://codex.wordpress.org/Version_%s', $new_core_version) . "\r\n\r\n";
					}
				}
			}

			// Get plugin updates
			$plugin_message = '';
			$is_plugin_updates = false;
			if (is_object($plugins)) {
				if (isset($plugins->translations) && !empty($plugins->translations) && is_array($plugins->translations)) {
					foreach ($plugins->translations as $translation) {
						$translations[] = $translation;
					}
				}
				if (isset($plugins->response) && !empty($plugins->response)) {
					$plugin_updates = $plugins->response;
					foreach ($plugin_updates as $plugin_slug => $plugin_data) {
						$plugin_meta = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_slug, false, true);
						$old_plugin_version = $plugin_meta['Version'];
						$new_plugin_version = $plugin_data->new_version;

						if ($old_plugin_version == $new_plugin_version) {
							continue;
						} else {
							$is_plugin_updates = true;
						}
						// Format email for plugins
						$plugin_message .= $plugin_meta['Name'] . "\r\n";
						$plugin_message .= __('Current Version:', 'stops-core-theme-and-plugin-updates') . ' ' . $old_plugin_version . "\r\n";
						$plugin_message .= __('New Version:', 'stops-core-theme-and-plugin-updates') . ' ' . $new_plugin_version . "\r\n";
						if (isset($plugin_data->id)) {
							$plugin_message .= __('Changelog:', 'stops-core-theme-and-plugin_updates') . ' ' . esc_url(sprintf('%s/#developers', $plugin_data->id)) . "\r\n\r\n";
						} else {
							$plugin_message .= "\r\n\r\n";
						}
					}
					if (true === $is_plugin_updates) {
						$plugin_message = __('== Plugin updates ==', 'stops-core-theme-and-plugin-updates') . "\r\n\r\n" . $plugin_message;
					}
				}
			}

			// Get theme updates
			$theme_message = '';
			$is_theme_updates = false;
			if (is_object($themes)) {
				if (!empty($themes->translations) && is_array($themes->translations)) {
					foreach ($themes->translations as $translation) {
						$translations[] = $translation;
					}
				}
				if (isset($themes->response) && !empty($themes->response)) {
					$theme_updates = $themes->response;
					foreach ($theme_updates as $theme_slug => $theme_data) {
						$theme_meta = wp_get_theme($theme_slug);
						$old_theme_version = $theme_meta->version;
						$new_theme_version = $theme_data['new_version'];

						if ($old_theme_version == $new_theme_version) {
							continue;
						} else {
							$is_theme_updates = true;
						}

						// Format email for themes
						$theme_message .= $theme_meta->name . "\r\n";
						$theme_message .= __('Current Version:', 'stops-core-theme-and-plugin-updates') . ' ' . $old_theme_version . "\r\n";
						$theme_message .= __('New Version:', 'stops-core-theme-and-plugin-updates') . ' ' . $new_theme_version . "\r\n";
						if (isset($theme_data['url'])) {
							$theme_message .= __('Theme details:', 'stops-core-theme-and-plugin-updates') . ' ' . esc_url($theme_data['url']) . "\r\n\r\n";
						} else {
							$theme_message .= "\r\n\r\n";
						}
					}
					if (true === $is_theme_updates) {
						$theme_message = __('== Theme updates ==', 'stops-core-theme-and-plugin-updates') . "\r\n\r\n" . $theme_message;
					}
				}
			}

			// Loop through translations
			$translations_message = '';
			if (! empty($translations)) {
				$translations_message = __('== Translation updates ==', 'stops-core-theme-and-plugin-updates') . "\r\n\r\n";
				foreach ($translations as $translation) {
					$translation_info = MPSUM_Logs::run()->get_update_name((object) $translation);
					foreach ($translation_info as $type => $translation_meta) {
						$translated_type = $type;
						if ('core' === $type) {
							$translated_type = _x('Core', 'WordPress Core', 'stops-core-theme-and-plugin-updates');
						} elseif ('plugin' === $type) {
							$translated_type = _x('Plugin', 'WordPress Plugin', 'stops-core-theme-and-plugin-updates');
						} elseif ('theme' === $type) {
							$translated_type = _x('Theme', 'WordPress Theme', 'stops-core-theme-and-plugin-updates');
						}
						$translations_message .= $translated_type . "\r\n";
						$translations_message .= $translation_meta['name'] . "\r\n";
						$translations_message .= __('New Version:', 'stops-core-theme-and-plugin-updates') . $translation_meta['version'] . "\r\n\r\n";
					}
				}
			}

			// Don't send email if there are no updates
			if (empty($core_message) && empty($plugin_message) && empty($theme_message) && empty($translations_message)) {
				return;
			}

			// Prepare E-mail Addresses to send to
			$core_options = MPSUM_Updates_Manager::get_options('core');
			$email_addresses = isset($core_options['email_addresses']) ? $core_options['email_addresses'] : array();
			$email_addresses_to_override = array();
			$emails_to_send = '';
			foreach ($email_addresses as $emails) {
				if (is_email($emails)) {
					$email_addresses_to_override[] = $emails;
				}
			}
			if (!empty($email_addresses_to_override)) {
				$emails_to_send = $email_addresses_to_override;
			} else {
				if (is_multisite()) {
					$emails_to_send = get_site_option('admin_email');
				} else {
					$emails_to_send = get_option('admin_email');
				}
			}

			// Get site name
			$sitename = '';
			if (is_multisite()) {
				$sitename = get_site_option('site_name');
			} else {
				$sitename = get_option('blogname');
			}

			// Get Send E-mail
			$sender_email = '';
			if (is_multisite()) {
				$sender_email = get_site_option('admin_email');
			} else {
				$sender_email = get_option('admin_email');
			}

			// Set headers
			$headers = array();
			$headers[] = sprintf('From: %s <%s>', esc_html($sitename), $sender_email);

			/**
			 * Change the subject of the update notification email.
			 *
			 * @since 8.0.1
			 *
			 * @param string Email Subject
			 * @param string URL of site or network
			 */
			$subject = apply_filters('eum_update_notification_subject', sprintf(__('WordPress updates are available for: %s', 'stops-core-theme-and-plugin-updates'), esc_url(network_site_url())), network_site_url());

			// Get Message
			$message = $core_message . $plugin_message . $theme_message . $translations_message;

			/**
			 * Allow others to provide an action prior to emailing.
			 *
			 * Allow others to provide an action prior to emailing.
			 *
			 * @since 8.0.1
			 *
			 * @param mixed  $emails_to_send Array or string of emails to send.
			 * @param string $subject        Subject of the email
			 * @param string $message        Message of the email
			 * @param array  $headers        Headers to be sent via email
			 * @param object $plugins        Plugins update object
			 * @param object $themes         Themes update object
			 * @param object $core           Core update object
			 */
			do_action('eum_update_notification_before_send', $emails_to_send, $subject, $message, $headers, $plugins, $themes, $core);

			/**
			 * Allow others to override email sending.
			 *
			 * Allow others to override email sending.
			 *
			 * @since 8.0.1
			 *
			 * @param bool   Whether to send the email or not
			 * @param array  $emails_to_send Array of emails to send.
			 * @param string $subject        Subject of the email
			 * @param string $message        Message of the email
			 * @param array  $headers        Headers to be sent via email
			 * @param object $plugins        Plugins update object
			 * @param object $themes         Themes update object
			 * @param object $core           Core update object
			 *
			 * @return bool Whether to send the email or not
			 */
			$allow_send_email = apply_filters('eum_update_notifications_send_email', true, $emails_to_send, $subject, $message, $headers, $plugins, $themes, $core);

			if ($allow_send_email) {
				// Send email
				wp_mail($emails_to_send, $subject, $message, $headers);
			}

			/**
			 * Allow others to provide an action after emailing.
			 *
			 * Allow others to provide an action after emailing.
			 *
			 * @since 8.0.1
			 *
			 * @param bool   $allow_send_email Boolean to determine if emails have been sent (default true)
			 * @param mixed  $emails_to_send   Array or string of emails to send.
			 * @param string $subject          Subject of the email
			 * @param string $message          Message of the email
			 * @param array  $headers          Headers to be sent via email
			 * @param object $plugins          Plugins update object
			 * @param object $themes           Themes update object
			 * @param object $core             Core update object
			 */
			do_action('eum_update_notification_after_send', $allow_send_email, $emails_to_send, $subject, $message, $headers, $plugins, $themes, $core);
		}

		/**
		 * Set Plugin updates in a transient
		 *
		 * @see maybe_send_update_notification_email
		 *
		 * @param int    $expiration     Time until expiration in seconds. Use 0 for no expiration.
		 * @param object $value          New value of plugin site transient.
		 * @param string $transient_name Transient name.
		 */
		public function set_plugin_updates($expiration, $value) {
			if (is_object($value) && empty($value->response)) return;
			set_site_transient('eum_plugin_updates', $value);
		}

		/**
		 * Set Theme updates in a transient
		 *
		 * @see maybe_send_update_notification_email
		 *
		 * @param int    $expiration     Time until expiration in seconds. Use 0 for no expiration.
		 * @param object $value          New value of plugin site transient.
		 * @param string $transient_name Transient name.
		 */
		public function set_theme_updates($expiration, $value) {
			if (is_object($value) && empty($value->response)) return;
			set_site_transient('eum_theme_updates', $value);
		}

		/**
		 * Set Core updates in a transient
		 *
		 * @param int    $expiration     Time until expiration in seconds. Use 0 for no expiration.
		 * @param object $value          New value of plugin site transient.
		 * @param string $transient_name Transient name.
		 */
		public function set_core_updates($expiration, $value) {
			if (is_object($value) && empty($value->updates)) return;
			if (empty($value)) return;
			set_site_transient('eum_core_updates', $value);
		}
	}
}
