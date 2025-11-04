<?php

namespace Ai_Persona\Logging;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine if analytics logging is enabled.
 *
 * @return bool
 */
function is_enabled() {
	return (bool) get_option( 'ai_persona_logging_enabled', false );
}

/**
 * Log generation event to file when enabled.
 *
 * @param array  $response Provider response payload.
 * @param string $prompt   Compiled prompt string.
 * @param array  $context  Context data.
 */
function log_generation_event( $response, $prompt, $context ) {
	if ( ! is_enabled() ) {
		return;
	}

	$uploads = wp_upload_dir();

	if ( empty( $uploads['basedir'] ) ) {
		return;
	}

	$dir = trailingslashit( $uploads['basedir'] ) . 'ai-persona';
	wp_mkdir_p( $dir );

	$provider = isset( $response['provider'] ) ? $response['provider'] : 'unknown';
	$persona_id = isset( $context['persona_id'] ) ? (int) $context['persona_id'] : 0;

	$entry = array(
		'timestamp'  => gmdate( 'c' ),
		'persona_id' => $persona_id,
		'provider'   => $provider,
		'prompt_len' => strlen( (string) $prompt ),
		'user_input' => isset( $context['user_input'] ) ? (string) $context['user_input'] : '',
	);

	$log_line = wp_json_encode( $entry ) . "\n";

	$file = trailingslashit( $dir ) . 'persona.log';
	file_put_contents( $file, $log_line, FILE_APPEND );

	/**
	 * Fire after a persona generation event has been logged.
	 *
	 * @param array  $entry    Logged entry data.
	 * @param array  $response Provider response payload.
	 * @param string $prompt   Compiled prompt string.
	 * @param array  $context  Context data.
	 */
	do_action( 'ai_persona_logged_event', $entry, $response, $prompt, $context );
}
