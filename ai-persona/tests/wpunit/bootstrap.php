<?php

$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

$config_path = getenv( 'WP_TESTS_CONFIG_FILE_PATH' );
if ( ! $config_path ) {
	$config_path = __DIR__ . '/wp-tests-config.php';
}

if ( file_exists( $config_path ) && ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
	define( 'WP_TESTS_CONFIG_FILE_PATH', $config_path );
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "Could not find WordPress test suite in {$_tests_dir}.\n" );
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

function ai_persona_tests_load_plugin() {
	require dirname( dirname( __DIR__ ) ) . '/ai-persona.php';
}
tests_add_filter( 'muplugins_loaded', 'ai_persona_tests_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
