<?php

/**
 * Proactively download and cache core, theme, and plugin files.
 */
class WP_CLI_Pre_Cache_Command extends WP_CLI_Command {

	/**
	 * Proactively download and cache one or more WordPress plugins.
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

WP_CLI::add_command( 'pre-cache', 'WP_CLI_Pre_Cache_Command' );