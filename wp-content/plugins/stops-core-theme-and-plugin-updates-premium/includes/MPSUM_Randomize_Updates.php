<?php
if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('MPSUM_Randomize_Updates')) {

	/**
	 * Class to anonymize update requests send to wp.org
	 */
	class MPSUM_Randomize_Updates {

		/**
		 * Adds and removes necessary action and filter hooks to anonymize
		 */
		private function __construct() {
			add_filter('core_version_check_query_args', array($this, 'randomize_non_essential_data'));
			add_filter('http_request_args', array($this, 'randomize_http_headers_useragent'), 10, 2);
		}

		/**
		 * Returns a singleton instance
		 *
		 * @return MPSUM_Randomize_Updates
		 */
		public static function get_instance() {
			static $instance = null;
			if (null === $instance) {
				$instance = new self();
			}
			return $instance;
		}

		/**
		 * Randomizes non essential data sent to wordpress.org in query arguments
		 *
		 * @param array $query An array of query arguments
		 *
		 * @return array Modified array of query arguments
		 */
		public function randomize_non_essential_data($query) {
			$random_non_essential_query_args = apply_filters('eum_random_non_essential_query_args', array(
				'local_package' => '',
				'blogs' => 1,
				'users' => 1,
				'multisite_enabled' => '0',
				'initial_db_version' => '1.0.0',
				'extensions' => array(),
				'platform_flags' => array(),
				'image_support' => array(),
			));
			foreach ($random_non_essential_query_args as $key => $value) {
				$query[$key] = $value;
			}
			return $query;
		}

		/**
		 * Randomize HTTP Header User Agent which is sent to wp.org
		 *
		 * @param array  $args An array of arguments
		 * @param string $url  URL
		 *
		 * @return array Randomized headers
		 */
		public function randomize_http_headers_useragent($args, $url) {
			if (!MPSUM_Utils::is_wp_api($url)) return $args;
			$random_port_number = rand(80, 9999);
			$args['user-agent'] ='WordPress; http://localhost:' . $random_port_number . '/';
			$args['headers'] = array(
				'wp_install' => 'http://localhost:' . $random_port_number . '/',
				'wp_blog' => 'http://localhost:' . $random_port_number . '/'
			);
			
			// Information on installed third-party entities provides unwanted entropy; it cannot be randomised, so is just removed
			foreach (array('plugins', 'themes') as $entity) {
				
				$removed_keys = array();
				
				if (!empty($args['body'][$entity])) {
					$items = json_decode($args['body'][$entity], true);
					if (null !== $items) {
						foreach ($items[$entity] as $key => $item) {
							// https://make.wordpress.org/core/2021/06/29/introducing-update-uri-plugin-header-in-wordpress-5-8/
							if (!empty($item['UpdateURI']) && !preg_match('#^(https?://)?w(ordpress)?\.org#', $item['UpdateURI']) && 'false' !== $item['UpdateURI']) {
								unset($items[$entity][$key]);
								$removed_keys[] = $key;
							}
						}
					}
				}
				
				if (!empty($removed_keys) && !empty($items['active'])) {
					foreach ($removed_keys as $removed_key) {
						unset($items['active'][$removed_key]);
					}
					$args['body'][$entity] = json_encode($items);
				}
			}
			
			return $args;
		}
	}
}
