<?php
if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('MPSUM_Semantic_Versioning')) {

	/**
	 * Class to create semantic versioning option
	 */
	class MPSUM_Semantic_Versioning {

		/**
		 * Adds and removes necessary action and filter hooks to whitelabel
		 */
		private function __construct() {
			add_filter('eum_i18n', array($this, 'i18n'));
			add_action('auto_update_plugin',  array($this, 'maybe_automatic_updates_plugins'), PHP_INT_MAX - 8, 2);
			add_action('auto_update_theme',  array($this, 'maybe_automatic_updates_themes'), PHP_INT_MAX - 8, 2);
			add_action('eum_populate_plugins_list_table_column', array($this, 'insert_plugin_semantic_versioning_selector'), 10, 2);
			add_action('eum_populate_themes_list_table_column', array($this, 'insert_theme_semantic_versioning_selector'), 10, 2);
			add_filter('eum_plugins_list_table_bulk_actions', array($this, 'add_bulk_actions'));
			add_filter('eum_themes_list_table_bulk_actions', array($this, 'add_bulk_actions'));
			add_filter('eum_plugins_list_table_allowed_statuses', array($this, 'add_list_table_status'));
			add_filter('eum_themes_list_table_allowed_statuses', array($this, 'add_list_table_status'));
			add_filter('eum_plugins_list_table_prepare_items', array($this, 'prepare_plugin_items'));
			add_filter('eum_themes_list_table_prepare_items', array($this, 'prepare_theme_items'));
			add_filter('eum_plugins_list_table_text_view', array($this, 'set_table_text_view'), 10, 3);
			add_filter('eum_themes_list_table_text_view', array($this, 'set_table_text_view'), 10, 3);
			add_filter('eum_save_core_options', array($this, 'save_core_options'), 10, 3);
			add_filter('eum_plugins_update_options', array($this, 'plugins_update_options'), 10, 3);
			add_filter('eum_themes_update_options', array($this, 'themes_update_options'), 10, 3);
			add_filter('eum_entity_auto_update_setting_html', array($this, 'auto_update_column_setting_html'), 10, 2);
		}

		/**
		 * Returns a singleton instance
		 *
		 * @return MPSUM_Semantic_Versioning
		 */
		public static function get_instance() {
			static $instance = null;
			if (null === $instance) {
				$instance = new self();
			}
			return $instance;
		}

		/**
		 * Outputs semantic versioning i18n
		 *
		 * @param array $i18n Internalization array
		 *
		 * @return array Updated internalization array
		 */
		public function i18n($i18n) {
			$new_i18n = array(
				'plugin_updates_label_automatic_patch_releases'         => __('Auto update to patch releases only', 'stops-core-theme-and-plugin-updates'),
				'plugin_updates_label_automatic_patch_releases_tooltip' => __('Automatically upgrade all plugins to patch releases only.', 'stops-core-theme-and-plugin-updates'),
				'theme_updates_label_automatic_patch_releases'          => __('Auto update to patch releases only', 'stops-core-theme-and-plugin-updates'),
				'theme_updates_label_automatic_patch_releases_tooltip'  => __('Automatically upgrade all themes to patch releases only.', 'stops-core-theme-and-plugin-updates'),
			);
			$i18n['I18N'] = array_merge($i18n['I18N'], $new_i18n);
			return $i18n;
		}

		/**
		 * Checks for semantic versioning and returns true or false if it is a semantic update.
		 *
		 * @since 8.1.0
		 * @access public
		 * @see __construct
		 *
		 * @param string $version_from The current version number from which a plugin or theme will be updated
		 * @param string $version_to   The offered version number to which a plugin or theme will be updated
		 *
		 * @return bool false if not semantic update, true if so.
		 */
		public static function is_semantic_update($version_from, $version_to) {
			// Regex from: https://regex101.com/r/vkijKf/1/.
			$regex = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';
			preg_match($regex, $version_from, $original_version);
			preg_match($regex, $version_to, $new_version);

			// Now that the Regexs' have been run, check for semantic versioning for third argument (zero based).
			if (isset($original_version[1]) && isset($new_version[1])) { // get 1 out of 1.2
				if ($original_version[1] === $new_version[1]) {
					// Top Level match - Now let's check for a sub-version match.
					if (isset($original_version[2]) && isset($new_version[2])) { // get 2 out of 1.2.3
						if ($original_version[2] === $new_version[2]) {
							// First two numbers match. Let's see if there's a patch release.
							if (isset($new_version[3])) {
								// This is a patch release.
								return true;
							}
						}
					}
				}
			}
			return false;
		}

		/**
		 * Checks for plugin compatibility with minor releases.
		 *
		 * @since 8.1.0
		 * @access public
		 * @see __construct
		 * @internal uses auto_update_plugin filter
		 *
		 * @param bool   $update Whether the item has automatic updates enabled
		 * @param object $item   Object holding the asset to be updated
		 * @return bool  true to update, false if not
		 */
		public function maybe_automatic_updates_plugins($update, $item) {
			if (!$update) return $update;
			$core_options = MPSUM_Updates_Manager::get_options('core');
			if (!isset($core_options['plugin_updates'])) $core_options['plugin_updates'] = ''; // to prevent PHP notices when activating EUM the first time where 'plugin_updates' key doesn't yet exist
			$plugin_semantic_options = MPSUM_Updates_Manager::get_options('plugins_semantic');
			if (!in_array($core_options['plugin_updates'], array('automatic_patch_releases', 'individual')) || ('individual' === $core_options['plugin_updates'] && isset($item->plugin) && !in_array($item->plugin, $plugin_semantic_options))) {
				return $update;
			}

			// Check to version information
			if (isset($item->new_version)) {
				// Check for plugin existence
				if (!isset($item->plugin)) {
					return $update;
				}

				$plugin = get_plugin_updates();
				if (!isset($plugin[$item->plugin]) || !isset($plugin[$item->plugin]->Version) || !isset($plugin[$item->plugin]->update) || !isset($plugin[$item->plugin]->update->new_version)) {
					return $update;
				}
				$from_version = $plugin[$item->plugin]->Version;
				if (1 === substr_count($from_version, '.')) $from_version .= '.0'; // if the version doesn't contain a patch number (e.g. 1.0) then add zero patch number (i.e. 1.0.0) so that the regex in is_semantic_update method will recognize it as a valid semantic version
				$to_version   = $plugin[$item->plugin]->update->new_version;

				// Perform Semantic Check, at this point in time we already know semantic versioning feature is currently enabled according to the check we had early in this method
				if (!self::is_semantic_update($from_version, $to_version)) return MPSUM_Utils::is_wp_site_health_plugin_theme($item);
			}
			return $update;
		}

		/**
		 * Checks for theme compatibility with minor releases.
		 *
		 * @since 8.1.0
		 * @access public
		 * @see __construct
		 * @internal uses auto_update_theme filter
		 *
		 * @param bool   $update Whether the item has automatic updates enabled
		 * @param object $item   Object holding the asset to be updated
		 * @return bool  true to update, false if not
		 */
		public function maybe_automatic_updates_themes($update, $item) {
			if (!$update) return $update;
			$core_options = MPSUM_Updates_Manager::get_options('core');
			if (!isset($core_options['theme_updates'])) $core_options['theme_updates'] = ''; // to prevent PHP notices when activating EUM the first time where 'theme_updates' key doesn't yet exist
			$theme_semantic_options = MPSUM_Updates_Manager::get_options('themes_semantic');
			if (!in_array($core_options['theme_updates'], array('automatic_patch_releases', 'individual')) || ('individual' === $core_options['theme_updates'] && isset($item->theme) && !in_array($item->theme, $theme_semantic_options))) {
				return $update;
			}

			// Check to version information
			if (isset($item->new_version)) {
				// Check for theme existence
				if (!isset($item->theme)) {
					return $update;
				}

				$theme = get_theme_updates();
				if (!isset($theme[$item->theme]) || !isset($theme[$item->theme]['Version']) || !isset($theme[$item->theme]->update) || !isset($theme[$item->theme]->update['new_version'])) {
					return $update;
				}
				$from_version = $theme[$item->theme]['Version'];
				if (1 === substr_count($from_version, '.')) $from_version .= '.0'; // if the version doesn't contain a patch number (e.g. 1.0) then add zero patch number (i.e. 1.0.0) so that the regex in is_semantic_update method will recognize it as a valid semantic version
				$to_version   = $theme[$item->theme]->update['new_version'];

				// Perform Semantic Check, at this point in time we already know semantic versioning feature is currently enabled according to the check we had early in this method
				if (!self::is_semantic_update($from_version, $to_version)) return MPSUM_Utils::is_wp_site_health_plugin_theme($item);
			}
			return $update;
		}

		/**
		 * Generate "Patch releases only (semantic versioning)" toggle/selector (on/off) for the specified plugin (item) into the plugins list table in the 'name' column
		 *
		 * @param string $column_name The name of the column to where the selector will be placed
		 * @param array  $item        The current plugin item
		 */
		public function insert_plugin_semantic_versioning_selector($column_name, $item) {
			if ('name' !== $column_name) return;
			$plugin_options = MPSUM_Updates_Manager::get_options('plugins');
			$plugin_semantic_options = MPSUM_Updates_Manager::get_options('plugins_semantic');
			$core_options = MPSUM_Updates_Manager::get_options('core');
			if (isset($core_options['plugin_updates']) && in_array($core_options['plugin_updates'], array('individual'))) {
				printf('<div class="eum-plugins-semantic-wrapper" %s>', in_array($item[0], $plugin_options) ? 'style="display: none;"' : '');
				printf('<h4>%s</h4>', esc_html__('Patch releases only?', 'stops-core-theme-and-plugin-updates'));
				echo '<div class="toggle-wrapper toggle-wrapper-plugins-semantic">';
				$enable_class = $disable_class = '';
				$checked = 'false';
				if (in_array($item[0], $plugin_semantic_options)) {
					$enable_class = 'eum-active';
					$checked = 'true';
				} else {
					$disable_class = 'eum-active';
				}

				printf('<input type="hidden" name="plugins_semantic[%s]" value="%s">',
					$item[0],
					$checked
				);

				printf('<button aria-label="%s" class="eum-toggle-button eum-enabled %s" data-checked="%s" value="on">%s</button>',
					esc_html__('Enable updates for patch releases only', 'stops-core-theme-and-plugin-updates'),
					esc_attr($enable_class),
					$item[0],
					esc_html__('On', 'stops-core-theme-and-plugin-updates')
				);

				printf('<button aria-label="%s" class="eum-toggle-button eum-disabled %s" data-checked="%s" value="off">%s</button>',
					esc_attr__('Disable updates for patch releases only', 'stops-core-theme-and-plugin-updates'),
					esc_attr($disable_class),
					$item[0],
					esc_html__('Off', 'stops-core-theme-and-plugin-updates')
				);

				echo '</div></div>';
			}
		}

		/**
		 * Generate "Patch releases only (semantic versioning)" toggle/selector (on/off) for the specified plugin (item) into the themes list table in the 'name' column
		 *
		 * @param string $column_name The name of the column to where the selector will be placed
		 * @param array  $item        The current theme item
		 */
		public function insert_theme_semantic_versioning_selector($column_name, $item) {
			if ('name' !== $column_name) return;
			$theme_options = MPSUM_Updates_Manager::get_options('themes');
			$theme_semantic_options = MPSUM_Updates_Manager::get_options('themes_semantic');
			$core_options = MPSUM_Updates_Manager::get_options('core');
			$stylesheet = $item->get_stylesheet();
			if (isset($core_options['theme_updates']) && in_array($core_options['theme_updates'], array('individual'))) {
				printf('<div class="eum-themes-semantic-wrapper" %s>', in_array($stylesheet, $theme_options) ? 'style="display: none;"' : '');
				printf('<h4>%s</h4>', esc_html__('Patch releases only?', 'stops-core-theme-and-plugin-updates'));
				echo '<div class="toggle-wrapper toggle-wrapper-themes-semantic">';
				$enable_class = $disable_class = '';
				if (in_array($stylesheet, $theme_semantic_options)) {
					$enable_class = 'eum-active';
					$checked = 'true';
				} else {
					$disable_class = 'eum-active';
					$checked = 'false';
				}

				printf('<input type="hidden" name="themes_semantic[%s]" value="%s">',
					$stylesheet,
					$checked
				);

				printf('<button aria-label="%s" class="eum-toggle-button eum-enabled %s" data-checked="%s">%s</button>',
					esc_html__('Enable updates for patch releases only', 'stops-core-theme-and-plugin-updates'),
					esc_attr($enable_class),
					$stylesheet,
					esc_html__('On', 'stops-core-theme-and-plugin-updates')
				);

				printf('<button aria-label="%s" class="eum-toggle-button eum-disabled %s" data-checked="%s">%s</button>',
					esc_attr__('Disable updates for patch releases only', 'stops-core-theme-and-plugin-updates'),
					esc_attr($disable_class),
					$stylesheet,
					esc_html__('Off', 'stops-core-theme-and-plugin-updates')
				);

				echo '</div></div>';
			}
		}

		/**
		 * Add semantic/patch releases items to the bulk actions' dropdown box on the plugins/themes' list table
		 *
		 * @param array $actions An associative array containing a list of bulk actions
		 * @return array The list of bulk actions that has 'patch releases only' actions (on/off)
		 */
		public function add_bulk_actions($actions) {
			$actions['allow-semantic-selected'] = esc_html__('Patch releases only on', 'stops-core-theme-and-plugin-updates');
			$actions['disallow-semantic-selected'] = esc_html__('Patch releases only off', 'stops-core-theme-and-plugin-updates');
			return $actions;
		}

		/**
		 * Set a new criteria in form of text for plugins categorisation. It associates with a defined allowed status
		 */
		public function set_table_text_view($text, $type, $count) {
			if ('automatic_patch_releases' !== $type) return $text;
			return _n('Patch releases only <span class="count">(%s)</span>', 'Patch releases only <span class="count">(%s)</span>', $count, 'stops-core-theme-and-plugin-updates');
		}

		/**
		 * Add 'automatic_patch_releases' as a new allowed status, which is linked to the 'patch releases only' text view criteria that will be used for grouping plugins
		 *
		 * @param array $allowed_statuses An array of allowed statuses
		 * @return array The list of allowed statuses that has 'automatic_patch_releases' for identifying text view criteria
		 */
		public function add_list_table_status($allowed_statuses) {
			$allowed_statuses[] = 'automatic_patch_releases';
			return $allowed_statuses;
		}

		/**
		 * Prepare a list of plugins that is associated with the 'automatic_patch_releases' allowed status
		 *
		 * @params array $plugins An associative array of plugins keyed by allowed statuses. Each allowed status contains a list of plugins to which it's represented
		 * @return array The associative array of plugins in which a list of plugins has been added to the 'automatic_patch_releases' status key
		 */
		public function prepare_plugin_items($plugins) {
			$core_options = MPSUM_Updates_Manager::get_options('core');
			$plugins['automatic_patch_releases'] = array();
			if (isset($core_options['plugin_updates']) && 'individual' === $core_options['plugin_updates'] && isset($plugins['all'])) {
				$plugin_options = MPSUM_Updates_Manager::get_options('plugins');
				$plugin_semantic_options = MPSUM_Updates_Manager::get_options('plugins_semantic');
				foreach ((array) $plugins['all'] as $plugin_file => $plugin_data) {
					if (false === array_search($plugin_file, $plugin_options) && in_array($plugin_file, $plugin_semantic_options)) {
						$plugins['automatic_patch_releases'][$plugin_file] = $plugin_data;
					}
				}
			}
			return $plugins;
		}

		/**
		 * Prepare a list of themes that is associated with the 'automatic_patch_releases' allowed status
		 *
		 * @params array $themes An associative array of themes keyed by allowed statuses. Each allowed status contains a list of themes to which it's represented
		 * @return array The associative array of themes in which a list of themes has been added to the 'automatic_patch_releases' status key
		 */
		public function prepare_theme_items($themes) {
			$core_options = MPSUM_Updates_Manager::get_options('core');
			$themes['automatic_patch_releases'] = array();
			if (isset($core_options['theme_updates']) && 'individual' === $core_options['theme_updates'] && isset($themes['all'])) {
				$theme_options = MPSUM_Updates_Manager::get_options('themes');
				$theme_semantic_options = MPSUM_Updates_Manager::get_options('themes_semantic');
				foreach ((array) $themes['all'] as $theme => $theme_data) {
					if (false === array_search($theme, $theme_options) && in_array($theme, $theme_semantic_options)) {
						$themes['automatic_patch_releases'][$theme] = $theme_data;
					}
				}
			}
			return $themes;
		}

		/**
		 * Set 'Auto update to patch releases only' and save 'automatic_patch_releases' as the user preference option depending on whether theme or plugin updates that was chosen from the updates settings UI
		 *
		 * @param array  $options An associative array of core options
		 * @param string $id      An ID that represents what updates setting the user chose from the UI
		 * @param string $value   The option value
		 */
		public function save_core_options($options, $id, $value) {
			if ('automatic_patch_releases' !== $value) return $options;
			if ('theme-updates' === $id) {
				$options['theme_updates'] = $value;
			} elseif ('plugin-updates' === $id) {
				$options['plugin_updates'] = $value;
			}
			return $options;
		}

		/**
		 * Save/update 'Patch releases only on/off' selector value depending on what plugin it's aimed at (single) or to what plugins the option/value must be applied (bulk actions/multiple plugins)
		 *
		 * @param array  $all_options     An associative array of options for a particular plugin keyed by its basic and additional features (e.g. 'plugins' (allowed/blocked), 'plugins_automatic' (automatic updates))
		 * @param array  $updated_options An associative array of updated options from the remote call
		 * @param string $action          A parameter indicating that a single or bulk actions was chosen for particular plugins
		 * @return array An array of options in which plugins affected by the 'patch releases only' setting under the 'plugins_semantic' key name has been updated
		 */
		public function plugins_update_options($all_options, $updated_options, $action = '') {
			$plugin_semantic_options = MPSUM_Updates_Manager::get_options('plugins_semantic');
			if ('' === $action) { // single
				$plugins_semantic = isset($updated_options['plugins_semantic']) ? (array) $updated_options['plugins_semantic'] : array();
				$plugins = isset($updated_options['plugins']) ? (array) $updated_options['plugins'] : array();
				foreach ($plugins as $plugin => $choice) {
					if ("false" === $choice) {
						if (($key = array_search($plugin, $plugin_semantic_options)) !== false) {
							unset($plugin_semantic_options[$key]);
						}
					}
				}

				foreach ($plugins_semantic as $plugin => $choice) {
					if ("true" === $choice) {
						$plugin_semantic_options[] = $plugin;
						if (($key = array_search($plugin, $all_options['plugins'])) !== false) {
							unset($all_options['plugins'][$key]);
						}
					} else {
						if (($key = array_search($plugin, $plugin_semantic_options)) !== false) {
							unset($plugin_semantic_options[$key]);
						}
					}
				}
			} else { // bulk update
				$plugins = isset($updated_options['checked']) ? (array) $updated_options['checked'] : array();
				switch ($action) {
					case 'allow-semantic-selected':
						foreach ($plugins as $plugin) {
							$plugin_semantic_options[] = $plugin;
							if (($key = array_search($plugin, $all_options['plugins'])) !== false) {
								unset($all_options['plugins'][$key]);
							}
						}
						break;
					case 'disallow-semantic-selected':
						foreach ($plugins as $plugin) {
							if (($key = array_search($plugin, $plugin_semantic_options)) !== false) {
								unset($plugin_semantic_options[$key]);
							}
						}
						break;
					default:
						break;
				}
			}
			$all_options['plugins_semantic'] = array_values(array_unique($plugin_semantic_options));
			return $all_options;
		}

		/**
		 * Save/update 'Patch releases only on/off' selector value depending on what theme it's aimed at (single) or to what themes the option/value must be applied (bulk actions/multiple themes)
		 *
		 * @param array  $all_options     An associative array of options for a particular theme keyed by its basic and additional features (e.g. 'themes' (allowed/blocked), 'themes_automatic' (automatic updates))
		 * @param array  $updated_options An associative array of updated options from the remote call
		 * @param string $action          A parameter indicating that a single or bulk actions was chosen for particular themes
		 * @return array An array of options in which themes affected by the 'patch releases only' setting under the 'themes_semantic' key name has been updated
		 */
		public function themes_update_options($all_options, $updated_options, $action = '') {
			$theme_semantic_options = MPSUM_Updates_Manager::get_options('themes_semantic');
			if ('' === $action) { // single
				$themes_semantic = isset($updated_options['themes_semantic']) ? (array) $updated_options['themes_semantic'] : array();
				$themes = isset($updated_options['themes']) ? (array) $updated_options['themes'] : array();
				foreach ($themes as $theme => $choice) {
					if ("false" === $choice) {
						if (($key = array_search($theme, $theme_semantic_options)) !== false) {
							unset($theme_semantic_options[$key]);
						}
					}
				}
		
				foreach ($themes_semantic as $theme => $choice) {
					if ("true" === $choice) {
						$theme_semantic_options[] = $theme;
						if (($key = array_search($theme, $all_options['themes'])) !== false) {
							unset($all_options['themes'][$key]);
						}
					} else {
						if (($key = array_search($theme, $theme_semantic_options)) !== false) {
							unset($theme_semantic_options[$key]);
						}
					}
				}
			} else { // bulk update
				$themes = isset($updated_options['checked']) ? (array) $updated_options['checked'] : array();
				switch ($action) {
					case 'allow-semantic-selected':
						foreach ($themes as $theme) {
							$theme_semantic_options[] = $theme;
							if (($key = array_search($theme, $all_options['themes'])) !== false) {
								unset($all_options['themes'][$key]);
							}
						}
						break;
					case 'disallow-semantic-selected':
						foreach ($themes as $theme) {
							if (($key = array_search($theme, $theme_semantic_options)) !== false) {
								unset($theme_semantic_options[$key]);
							}
						}
						break;
					default:
						break;
				}
			}
			$all_options['themes_semantic'] = array_values(array_unique($theme_semantic_options));
			return $all_options;
		}

		/**
		 * Overwrite the default content of WP's 'Automatic Updates' column in the plugins list table for single and multi sites in WordPress 5.5+
		 *
		 * @param string $html   the HTML to filter
		 * @param string $entity the entity type (theme/plugin)
		 * @return string a filtered HTML string or template
		 */
		public function auto_update_column_setting_html($html, $entity) {
			$core_options = MPSUM_Updates_Manager::get_options('core');
			$url = MPSUM_Admin::get_url();
			$eum_white_label = apply_filters('eum_whitelabel_name', __('Easy Updates Manager', 'stops-core-theme-and-plugin-updates'));
			$updates = 'plugin' == $entity ? $core_options['plugin_updates'] : $core_options['theme_updates'];
			if ('automatic_patch_releases' === $updates) return '<a href="'.$url.'">'.sprintf(__('Managed by %s.', 'stops-core-theme-and-plugin-updates'), $eum_white_label).'</a>';
			return $html;
		}
	}
}
