<?php

namespace Ai_Persona\Frontend;

use Ai_Persona\API;
use function Ai_Persona\compile_persona_prompt;
use function Ai_Persona\get_persona_data;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register REST API routes.
 */
function register_routes() {
	register_rest_route(
		'ai-persona/v1',
		'/generate',
		array(
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\handle_generate',
			'permission_callback' => __NAMESPACE__ . '\\permissions_check',
		)
	);

	register_rest_route(
		'ai-persona/v1',
		'/stream',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\\handle_stream',
			'permission_callback' => __NAMESPACE__ . '\\permissions_check',
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );

/**
 * Validate REST permissions.
 *
 * @return bool|\WP_Error
 */
function permissions_check( WP_REST_Request $request ) {
	if ( current_user_can( 'edit_posts' ) ) {
		return true;
	}

	$nonce = $request->get_param( '_wpnonce' );

	if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return true;
	}

	return new \WP_Error( 'forbidden', __( 'You are not allowed to generate persona responses.', 'ai-persona' ), array( 'status' => 403 ) );
}

/**
 * Handle persona generation request.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function handle_generate( WP_REST_Request $request ) {
	$user_input = (string) $request->get_param( 'prompt' );
	$persona_id = absint( $request->get_param( 'persona_id' ) );
	$context    = (array) $request->get_param( 'context' );

	$persona_data = $persona_id ? get_persona_data( $persona_id ) : null;

	if ( $persona_id && ! $persona_data ) {
		return new WP_REST_Response(
			array( 'error' => __( 'Persona not found.', 'ai-persona' ) ),
			404
		);
	}

	$compiled_prompt = $persona_data ? compile_persona_prompt( $persona_data, array( 'persona_id' => $persona_id ) ) : (string) $request->get_param( 'system_prompt' );
	$prompt          = $compiled_prompt ?: (string) $request->get_param( 'prompt' );

	$context['persona_id'] = $persona_id ?: null;
	$context['persona']    = $persona_data ?: null;
	$context['user_input'] = $user_input;

	$api     = new API();
	$result  = $api->generate( (string) $prompt, $context );
	$payload = array_merge(
		$result,
		array(
			'persona'         => $persona_data,
			'compiled_prompt' => $prompt,
			'user_input'      => $user_input,
		)
	);

	return new WP_REST_Response( $payload, 200 );
}

/**
 * Handle streaming persona generation.
 *
 * @param WP_REST_Request $request Request object.
 */
function handle_stream( WP_REST_Request $request ) {
	$user_input = (string) $request->get_param( 'prompt' );
	$persona_id = absint( $request->get_param( 'persona_id' ) );

	if ( '' === trim( $user_input ) ) {
		status_header( 400 );
		echo wp_json_encode(
			array(
				'error' => __( 'Prompt is required.', 'ai-persona' ),
			)
		);
		exit;
	}

	$persona_data = $persona_id ? get_persona_data( $persona_id ) : null;

	if ( $persona_id && ! $persona_data ) {
		status_header( 404 );
		echo wp_json_encode(
			array(
				'error' => __( 'Persona not found.', 'ai-persona' ),
			)
		);
		exit;
	}

	$compiled_prompt = $persona_data ? compile_persona_prompt( $persona_data, array( 'persona_id' => $persona_id ) ) : (string) $request->get_param( 'system_prompt' );
	$prompt          = $compiled_prompt ?: $user_input;

	$context = array(
		'persona_id' => $persona_id ?: null,
		'persona'    => $persona_data,
		'user_input' => $user_input,
	);

	start_stream();

	$api = new API();
	$aggregate = '';
	$complete_sent = false;

	$api->stream(
		$prompt,
		$context,
		function ( $event ) use ( &$aggregate, &$complete_sent ) {
			if ( ! is_array( $event ) || empty( $event['type'] ) ) {
				return;
			}

			switch ( $event['type'] ) {
				case 'token':
					$aggregate .= isset( $event['data'] ) ? (string) $event['data'] : '';
					emit_sse( 'message', isset( $event['data'] ) ? (string) $event['data'] : '' );
					break;
				case 'error':
					emit_sse( 'error', isset( $event['data'] ) ? (string) $event['data'] : '' );
					break;
				case 'done':
					if ( ! $complete_sent ) {
						emit_sse( 'complete', $aggregate );
						$complete_sent = true;
					}
					break;
			}
		}
	);

	if ( ! $complete_sent ) {
		emit_sse( 'complete', $aggregate );
	}

end_stream();
}

/**
 * Prepare headers for SSE streaming.
 */
function start_stream() {
	if ( ! headers_sent() ) {
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'X-Accel-Buffering: no' );
	}

	@ini_set( 'output_buffering', 'off' );
	@ini_set( 'zlib.output_compression', '0' );

	while ( ob_get_level() > 0 ) {
		ob_end_flush();
	}

	emit_comment( 'stream-start' );
}

/**
 * Output SSE event.
 *
 * @param string $event Event name.
 * @param string $data  Payload data.
 */
function emit_sse( $event, $data ) {
	echo 'event: ' . $event . "\n";

	foreach ( explode( "\n", (string) $data ) as $line ) {
		echo 'data: ' . $line . "\n";
	}

	echo "\n";
	flush_buffers();
}

/**
 * Emit SSE comment (non-data line).
 *
 * @param string $comment Comment text.
 */
function emit_comment( $comment ) {
	echo ': ' . $comment . "\n\n";
	flush_buffers();
}

/**
 * Flush output buffers safely.
 */
function flush_buffers() {
	@ob_flush();
	flush();
}

/**
 * Finish streaming and close connection.
 */
function end_stream() {
	emit_comment( 'stream-end' );
	exit;
}
