<?php

use WP_CLI\Utils;

/**
 * Proactively download and cache core, theme, and plugin files.
 */
class WP_CLI_Precache_Command {

	/**
	 * Proactively download and cache WordPress core.
	 *
	 * ## OPTIONS
	 *
	 * [--version=<version>]
	 * : Specify the version to cache.
	 *
	 * [--locale=<locale>]
	 * : Specify the language to cache.
	 *
	 * @when before_wp_load
	 */
	public function core( $args, $assoc_args ) {

		$locale = \WP_CLI\Utils\get_flag_value( $assoc_args, 'locale', 'en_US' );

		if ( isset( $assoc_args['version'] ) ) {
			$version = $assoc_args['version'];
			$download_url = $this->get_download_url($version, $locale, 'tar.gz');
		} else {
			$offer = $this->get_download_offer( $locale );
			if ( ! $offer ) {
				WP_CLI::error( "The requested locale ($locale) was not found." );
			}
			$version = $offer['current'];
			$download_url = str_replace( '.zip', '.tar.gz', $offer['download'] );
		}

		WP_CLI::log( sprintf( 'Downloading WordPress %s (%s)...', $version, $locale ) );

		$cache = WP_CLI::get_cache();
		$cache_key = "core/wordpress-{$version}-{$locale}.tar.gz";
		$cache_file = $cache->has( $cache_key );

		$temp = \WP_CLI\Utils\get_temp_dir() . uniqid('wp_') . '.tar.gz';

		$headers = array( 'Accept' => 'application/json' );
		$options = array(
			'timeout' => 600,  // 10 minutes ought to be enough for everybody
			'filename' => $temp
		);

		$response = Utils\http_request( 'GET', $download_url, null, $headers, $options );
		if ( 404 == $response->status_code ) {
			WP_CLI::error( "Release not found. Double-check locale or version." );
		} else if ( 20 != substr( $response->status_code, 0, 2 ) ) {
			WP_CLI::error( "Couldn't access download URL (HTTP code {$response->status_code})" );
		}

		$md5_response = Utils\http_request( 'GET', $download_url . '.md5' );
		if ( 20 != substr( $md5_response->status_code, 0, 2 ) ) {
			WP_CLI::error( "Couldn't access md5 hash for release (HTTP code {$response->status_code})" );
		}

		$md5_file = md5_file( $temp );
		if ( $md5_file === $md5_response->body ) {
			WP_CLI::log( 'md5 hash verified: ' . $md5_file );
		} else {
			WP_CLI::error( "md5 hash for download ({$md5_file}) is different than the release hash ({$md5_response->body})" );
		}

		$cache->import( $cache_key, $temp );

		WP_CLI::success( "WordPress pre-cached as {$cache_key}" );
	}

	/**
	 * Proactively download and cache one or more WordPress themes.
	 *
	 * ## OPTIONS
	 *
	 * [<theme>...]
	 * : One or more themes to proactively cache.
	 *
	 * [--version=<version>]
	 * : Specify the version to cache.
	 */
	public function theme( $args, $assoc_args ) {

		foreach( $args as $slug ) {
			$api = themes_api( 'theme_information', array( 'slug' => $slug ) );

			if ( is_wp_error( $api ) ) {
				return $api;
			}

			if ( isset( $assoc_args['version'] ) ) {
				self::alter_api_response( $api, $assoc_args['version'] );
			}

			$this->pre_cache( 'theme', $api );
		}

		WP_CLI::success( "Theme(s) pre-cached." );

	}

	/**
	 * Proactively download and cache one or more WordPress plugins.
	 *
	 * ## OPTIONS
	 *
	 * [<plugin>...]
	 * : One or more plugins to proactively cache.
	 *
	 * [--version=<version>]
	 * : Specify the version to cache.
	 */
	public function plugin( $args, $assoc_args ) {

		require_once ABSPATH.'wp-admin/includes/plugin.php';
		require_once ABSPATH.'wp-admin/includes/plugin-install.php';

		foreach( $args as $slug ) {
			$api = plugins_api( 'plugin_information', array( 'slug' => $slug ) );

			if ( is_wp_error( $api ) ) {
				return $api;
			}

			if ( isset( $assoc_args['version'] ) ) {
				self::alter_api_response( $api, $assoc_args['version'] );
			}

			$this->pre_cache( 'plugin', $api );
		}

		WP_CLI::success( "Plugin(s) pre-cached." );
	}

	/**
	 * Proactively cache one of the entities
	 */
	private function pre_cache( $item_type, $api ) {

		$cache_manager = WP_CLI::get_http_cache_manager();
		WP_CLI::log( sprintf( 'Caching %s (%s)', $api->name, $api->version ) );
		$cache_manager->whitelist_package( $api->download_link, $item_type, $api->slug, $api->version );
		$tmp = download_url( $api->download_link );
		@unlink( $tmp );

	}

	/**
	 * Gets download url based on version, locale and desired file type.
	 *
	 * @param $version
	 * @param string $locale
	 * @param string $file_type
	 * @return string
	 */
	private function get_core_download_url( $version, $locale = 'en_US', $file_type = 'zip' ) {
		if ('en_US' === $locale) {
			$url = 'https://wordpress.org/wordpress-' . $version . '.' . $file_type;

			return $url;
		} else {
			$url = sprintf(
				'https://%s.wordpress.org/wordpress-%s-%s.' . $file_type,
				substr($locale, 0, 2),
				$version,
				$locale
			);

			return $url;
		}
	}

	private function get_download_offer( $locale ) {
		$out = unserialize( self::_read(
			'https://api.wordpress.org/core/version-check/1.6/?locale=' . $locale ) );

		$offer = $out['offers'][0];

		if ( $offer['locale'] != $locale ) {
			return false;
		}

		return $offer;
	}

	private function get_download_url($version, $locale = 'en_US', $file_type = 'zip') {
		if ('en_US' === $locale) {
			$url = 'https://wordpress.org/wordpress-' . $version . '.' . $file_type;

			return $url;
		} else {
			$url = sprintf(
				'https://%s.wordpress.org/wordpress-%s-%s.' . $file_type,
				substr($locale, 0, 2),
				$version,
				$locale
			);

			return $url;
		}
	}

	/**
	 * Prepare an API response for downloading a particular version of an item.
	 *
	 * @param object $response wordpress.org API response
	 * @param string $version The desired version of the package
	 */
	private static function alter_api_response( $response, $version ) {
		if ( $response->version == $version )
			return;

		list( $link ) = explode( $response->slug, $response->download_link );

		if ( false !== strpos( $response->download_link, 'theme' ) )
			$download_type = 'theme';
		else
			$download_type = 'plugin';

		if ( 'dev' == $version ) {
			$response->download_link = $link . $response->slug . '.zip';
			$response->version = 'Development Version';
		} else {
			$response->download_link = $link . $response->slug . '.' . $version .'.zip';
			$response->version = $version;

			// check if the requested version exists
			$response = wp_remote_head( $response->download_link );
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				\WP_CLI::error( sprintf(
					"Can't find the requested %s's version %s in the WordPress.org %s repository.",
					$download_type, $version, $download_type ) );
			}
		}
	}

}
