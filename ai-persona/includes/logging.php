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

/**
 * Provide aggregate metrics for the analytics dashboard.
 *
 * @return array
 */
function get_log_summary() {
	$entries = get_log_entries( 1000 );

	if ( empty( $entries ) ) {
		return array(
			'total_events'     => 0,
			'unique_personas'  => 0,
			'unique_providers' => 0,
			'last_24_hours'    => 0,
			'last_7_days'      => 0,
			'avg_prompt_chars' => 0,
			'providers'        => array(),
			'personas'         => array(),
		);
	}

	$provider_counts = array();
	$persona_counts  = array();
	$total_prompt    = 0;
	$last24          = 0;
	$last7           = 0;
	$now             = time();

	foreach ( $entries as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$provider = isset( $entry['provider'] ) ? $entry['provider'] : 'unknown';
		$persona  = isset( $entry['persona_id'] ) ? (int) $entry['persona_id'] : 0;
		$timestamp = isset( $entry['timestamp'] ) ? strtotime( $entry['timestamp'] ) : false;

		if ( ! isset( $provider_counts[ $provider ] ) ) {
			$provider_counts[ $provider ] = 0;
		}
		$provider_counts[ $provider ]++;

		if ( ! isset( $persona_counts[ $persona ] ) ) {
			$persona_counts[ $persona ] = 0;
		}
		$persona_counts[ $persona ]++;

		$total_prompt += isset( $entry['prompt_len'] ) ? (int) $entry['prompt_len'] : 0;

		if ( $timestamp ) {
			if ( ( $now - $timestamp ) <= DAY_IN_SECONDS ) {
				$last24++;
			}

			if ( ( $now - $timestamp ) <= WEEK_IN_SECONDS ) {
				$last7++;
			}
		}
	}

	arsort( $provider_counts );
	arsort( $persona_counts );

	$total_events = array_sum( $provider_counts );
	$avg_prompt   = $total_events ? round( $total_prompt / $total_events ) : 0;

	return array(
		'total_events'     => $total_events,
		'unique_personas'  => count( array_keys( $persona_counts ) ),
		'unique_providers' => count( array_keys( $provider_counts ) ),
		'last_24_hours'    => $last24,
		'last_7_days'      => $last7,
		'avg_prompt_chars' => $avg_prompt,
		'providers'        => $provider_counts,
		'personas'         => $persona_counts,
	);
}

/**
 * Return recent log entries prepared for dashboard display.
 *
 * @param int $limit Number of entries to return.
 * @return array
 */
function get_recent_log_entries( $limit = 20 ) {
	$entries = array_reverse( get_log_entries( $limit ) );
	$results = array();

	foreach ( $entries as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$results[] = array(
			'timestamp'  => isset( $entry['timestamp'] ) ? $entry['timestamp'] : '',
			'provider'   => isset( $entry['provider'] ) ? $entry['provider'] : 'unknown',
			'persona_id' => isset( $entry['persona_id'] ) ? (int) $entry['persona_id'] : 0,
			'prompt_len' => isset( $entry['prompt_len'] ) ? (int) $entry['prompt_len'] : 0,
			'preview'    => sanitize_log_preview( isset( $entry['user_input'] ) ? $entry['user_input'] : '' ),
		);
	}

	return $results;
}

/**
 * Prepare the user input preview for safe display.
 *
 * @param string $value Raw user input.
 * @return string
 */
function sanitize_log_preview( $value ) {
	$stripped = wp_strip_all_tags( (string) $value );
	$stripped = preg_replace( '/\\s+/', ' ', $stripped );
	$preview  = mb_substr( $stripped, 0, 160 );

	if ( mb_strlen( $stripped ) > 160 ) {
		$preview .= '…';
	}

	return $preview;
}
