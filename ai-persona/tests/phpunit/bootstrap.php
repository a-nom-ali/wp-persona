<?php

// Minimal bootstrap providing WordPress-like helpers for isolated unit tests.

require_once __DIR__ . '/../../includes/providers/interface-provider.php';
require_once __DIR__ . '/../../includes/providers/class-null-provider.php';
require_once __DIR__ . '/../../includes/providers/class-ollama-provider.php';

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $message;

		public function __construct( $code = '', $message = '' ) {
			$this->message = $message;
		}

		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore
		return $text;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value ) { // phpcs:ignore
		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {} // phpcs:ignore
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) { // phpcs:ignore
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) { // phpcs:ignore
		return isset( $response['body'] ) ? $response['body'] : '';
	}
}

/**
 * HTTP stub for controlling wp_remote_post responses.
 */
class Ai_Persona_Tests_HTTP_Stub {
	public static $next_response = null;

	public static function reset() {
		self::$next_response = null;
	}

	public static function queue( $response ) {
		self::$next_response = $response;
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = array() ) { // phpcs:ignore
		$response = Ai_Persona_Tests_HTTP_Stub::$next_response;

		if ( is_callable( $response ) ) {
			return call_user_func( $response, $url, $args );
		}

		return $response;
	}
}
