<?php

namespace runcommand\precache;
use WP_CLI;

require_once dirname( __FILE__ ) . '/inc/class-precache-command.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'precache', 'WP_CLI_Precache_Command' );
}
