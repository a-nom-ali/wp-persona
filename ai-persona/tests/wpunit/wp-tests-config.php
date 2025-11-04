<?php

/**
 * Local configuration for the WordPress integration test suite.
 *
 * Update the database constants to point at a disposable test database.
 * You can also export the values as environment variables and they will be
 * consumed automatically.
 */

define( 'WP_TESTS_DOMAIN', getenv( 'WP_TESTS_DOMAIN' ) ?: 'example.org' );
define( 'WP_TESTS_EMAIL', getenv( 'WP_TESTS_EMAIL' ) ?: 'admin@example.org' );
define( 'WP_TESTS_TITLE', getenv( 'WP_TESTS_TITLE' ) ?: 'AI Persona Test Suite' );
define( 'WP_PHP_BINARY', getenv( 'WP_PHP_BINARY' ) ?: ( PHP_BINARY ?: 'php' ) );

define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ?: 'wordpress_tests' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ?: '' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ?: '127.0.0.1' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = getenv( 'WP_TESTS_TABLE_PREFIX' ) ?: 'wptests_';

if ( ! defined( 'ABSPATH' ) ) {
	$abspath = getenv( 'WP_TESTS_ABSPATH' );
	if ( ! $abspath ) {
		$abspath = dirname( __FILE__ ) . '/wordpress/';
	}

	define( 'ABSPATH', rtrim( $abspath, '/\\' ) . '/' );
}

define( 'WP_DEBUG', true );

// Optional: point to the Yoast PHPUnit Polyfills if the autoloader is not used.
if ( getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) );
}
