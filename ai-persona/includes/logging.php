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


/**
 * Resolve the absolute path to the analytics log file.
 *
 * @return string Log file path or empty string when uploads are unavailable.
 */
function get_log_file() {
	$uploads = wp_upload_dir();
	if ( empty( $uploads['basedir'] ) ) {
		return '';
	}

	$dir = trailingslashit( $uploads['basedir'] ) . 'ai-persona';
	wp_mkdir_p( $dir );

	return trailingslashit( $dir ) . 'persona.log';
}

/**
 * Retrieve recent log entries from the analytics file.
 *
 * @param int $count Number of entries to return.
 * @return array List of decoded log entries.
 */
function get_log_entries( $count = 50 ) {
	$file = get_log_file();

	if ( ! $file || ! file_exists( $file ) ) {
		return array();
	}

	$lines = array_slice( file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ), -abs( $count ) );

	return array_values( array_filter( array_map( 'json_decode', $lines, array_fill( 0, count( $lines ), true ) ) ) );
}

/**
 * Summarise analytics log entries by persona and provider.
 *
 * @return array Aggregate totals structure.
 */
function get_log_totals() {
	$entries = get_log_entries( 1000 );
	$totals  = array(
		'total'       => 0,
		'by_provider' => array(),
		'by_persona'  => array(),
	);

	foreach ( $entries as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$totals['total']++;

		$provider = isset( $entry['provider'] ) ? $entry['provider'] : 'unknown';
		$persona  = isset( $entry['persona_id'] ) ? (int) $entry['persona_id'] : 0;

		if ( ! isset( $totals['by_provider'][ $provider ] ) ) {
			$totals['by_provider'][ $provider ] = 0;
		}

		if ( ! isset( $totals['by_persona'][ $persona ] ) ) {
			$totals['by_persona'][ $persona ] = 0;
		}

		$totals['by_provider'][ $provider ]++;
		$totals['by_persona'][ $persona ]++;
	}

	return $totals;
}
