<?php

namespace Ai_Persona\Frontend;

use Ai_Persona\API;
use function Ai_Persona\compile_persona_prompt;
use function Ai_Persona\get_persona_data;
use function Ai_Persona\sanitize_persona_payload;
use function Ai_Persona\save_persona_data;
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

	register_rest_route(
		'ai-persona/v1',
		'/persona/(?P<id>\\d+)',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\\handle_persona_export',
			'permission_callback' => __NAMESPACE__ . '\\permissions_check',
		)
	);

	register_rest_route(
		'ai-persona/v1',
		'/persona',
		array(
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\handle_persona_create',
			'permission_callback' => __NAMESPACE__ . '\\permissions_check',
		)
	);

	register_rest_route(
		'ai-persona/v1',
		'/persona/(?P<id>\\d+)',
		array(
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\handle_persona_update',
			'permission_callback' => __NAMESPACE__ . '\\permissions_check',
		)
	);

	register_rest_route(
		'ai-persona/v1',
		'/persona/(?P<id>\\d+)',
		array(
			'methods'             => 'DELETE',
			'callback'            => __NAMESPACE__ . '\\handle_persona_delete',
			'permission_callback' => __NAMESPACE__ . '\\permissions_check',
		)
	);

	register_rest_route(
		'ai-persona/v1',
		'/persona/(?P<id>\\d+)/duplicate',
		array(
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\\handle_persona_duplicate',
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
 * Return structured persona export payload.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function handle_persona_export( WP_REST_Request $request ) {
	$persona_id = absint( $request->get_param( 'id' ) );

	if ( ! $persona_id ) {
		return new WP_REST_Response(
			array( 'error' => __( 'Persona ID is required.', 'ai-persona' ) ),
			400
		);
	}

	$persona = get_persona_data( $persona_id );

	if ( ! $persona ) {
		return new WP_REST_Response(
			array( 'error' => __( 'Persona not found.', 'ai-persona' ) ),
			404
		);
	}

	$compiled = compile_persona_prompt( $persona, array( 'persona_id' => $persona_id ) );

	$payload = array(
		'id'              => $persona_id,
		'persona'         => $persona,
		'compiled_prompt' => $compiled,
		'generated_at'    => gmdate( 'c' ),
		'plugin_version'  => defined( 'AI_PERSONA_VERSION' ) ? AI_PERSONA_VERSION : 'unknown',
	);

	return new WP_REST_Response( $payload, 200 );
}

/**
 * Create a persona via REST.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function handle_persona_create( WP_REST_Request $request ) {
	$title = (string) $request->get_param( 'title' );

	if ( '' === trim( $title ) ) {
		return new WP_REST_Response(
			array( 'error' => __( 'Persona title is required.', 'ai-persona' ) ),
			400
		);
	}

	$status = sanitize_persona_status_param( $request->get_param( 'status' ), 'draft', false );

	$persona_payload = sanitize_persona_payload( (array) $request->get_param( 'persona' ) );

	if ( '' === $persona_payload['role'] ) {
		return new WP_REST_Response(
			array( 'error' => __( 'Persona role is required.', 'ai-persona' ) ),
			400
		);
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'ai_persona',
			'post_status' => $status,
			'post_title'  => sanitize_text_field( $title ),
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return new WP_REST_Response(
			array( 'error' => $post_id->get_error_message() ),
			500
		);
	}

	save_persona_data( $post_id, $persona_payload );

	$data = build_persona_response( $post_id );
	$data['status'] = $status;

	return new WP_REST_Response( $data, 201 );
}

/**
 * Update an existing persona via REST.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function handle_persona_update( WP_REST_Request $request ) {
	$persona_id = absint( $request->get_param( 'id' ) );

	if ( ! $persona_id || 'ai_persona' !== get_post_type( $persona_id ) ) {
		return new WP_REST_Response(
			array( 'error' => __( 'Persona not found.', 'ai-persona' ) ),
			404
		);
	}

	$post_update = array( 'ID' => $persona_id );
	$needs_update = false;

	if ( null !== $request->get_param( 'title' ) ) {
		$title = sanitize_text_field( (string) $request->get_param( 'title' ) );
		if ( '' === $title ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Persona title cannot be empty.', 'ai-persona' ) ),
				400
			);
		}
		$post_update['post_title'] = $title;
		$needs_update              = true;
	}

	if ( null !== $request->get_param( 'status' ) ) {
		$status = sanitize_persona_status_param( $request->get_param( 'status' ), null, true );
		if ( null === $status ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Invalid status supplied.', 'ai-persona' ) ),
				400
			);
		}
		$post_update['post_status'] = $status;
		$needs_update               = true;
	}

	if ( $needs_update ) {
		$result = wp_update_post( $post_update, true );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array( 'error' => $result->get_error_message() ),
				500
			);
		}
	}

	if ( null !== $request->get_param( 'persona' ) ) {
		$persona_payload = sanitize_persona_payload( (array) $request->get_param( 'persona' ) );
		if ( '' === $persona_payload['role'] ) {
			return new WP_REST_Response(
				array( 'error' => __( 'Persona role is required.', 'ai-persona' ) ),
				400
			);
		}
		save_persona_data( $persona_id, $persona_payload );
	}

	$data = build_persona_response( $persona_id );

	return new WP_REST_Response( $data, 200 );
}

/**
 * Delete a persona via REST.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function handle_persona_delete( WP_REST_Request $request ) {
	$persona_id = absint( $request->get_param( 'id' ) );

	if ( ! $persona_id || 'ai_persona' !== get_post_type( $persona_id ) ) {
		return new WP_REST_Response(
			array( 'error' => __( 'Persona not found.', 'ai-persona' ) ),
			404
		);
	}

	$result = wp_delete_post( $persona_id, true );

	if ( ! $result ) {
		return new WP_REST_Response(
			array( 'error' => __( 'Failed to delete persona.', 'ai-persona' ) ),
			500
		);
	}

	return new WP_REST_Response( array( 'deleted' => true, 'id' => $persona_id ), 200 );
}

/**
 * Duplicate a persona and return the new record.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response
 */
function handle_persona_duplicate( WP_REST_Request $request ) {
	$persona_id = absint( $request->get_param( 'id' ) );

	if ( ! $persona_id || 'ai_persona' !== get_post_type( $persona_id ) ) {
		return new WP_REST_Response(
			array( 'error' => __( 'Persona not found.', 'ai-persona' ) ),
			404
		);
	}

	$source   = get_post( $persona_id );
	$persona  = get_persona_data( $persona_id );
	$title    = $request->get_param( 'title' );
	$new_title = $title ? sanitize_text_field( (string) $title ) : sprintf( __( '%s (Copy)', 'ai-persona' ), $source->post_title );

	$new_status = sanitize_persona_status_param( $request->get_param( 'status' ), $source->post_status, false );

	$new_post_id = wp_insert_post(
		array(
			'post_type'   => 'ai_persona',
			'post_status' => $new_status,
			'post_title'  => $new_title,
		),
		true
	);

	if ( is_wp_error( $new_post_id ) ) {
		return new WP_REST_Response(
			array( 'error' => $new_post_id->get_error_message() ),
			500
		);
	}

	save_persona_data( $new_post_id, $persona );

	$data = build_persona_response( $new_post_id );

	return new WP_REST_Response( $data, 201 );
}

/**
 * Normalise status parameter from REST requests.
 *
 * @param mixed  $status  Raw status.
 * @param string $default Default value when not strict.
 * @param bool   $strict  When true, invalid statuses return null.
 * @return string|null
 */
function sanitize_persona_status_param( $status, $default = 'draft', $strict = false ) {
	if ( null === $status ) {
		return $strict ? null : $default;
	}

	$status  = sanitize_key( (string) $status );
	$allowed = array( 'draft', 'publish', 'pending', 'private' );

	if ( in_array( $status, $allowed, true ) ) {
		return $status;
	}

	return $strict ? null : $default;
}

/**
 * Build REST response payload for a persona.
 *
 * @param int $post_id Persona post ID.
 * @return array
 */
function build_persona_response( $post_id ) {
	$persona  = get_persona_data( $post_id );
	$compiled = compile_persona_prompt( $persona, array( 'persona_id' => $post_id ) );
	$status   = function_exists( 'get_post_status' ) ? get_post_status( $post_id ) : 'draft';
	$post     = function_exists( 'get_post' ) ? get_post( $post_id ) : null;
	$title    = $post && isset( $post->post_title ) ? $post->post_title : '';

	return array(
		'id'              => $post_id,
		'title'           => $title,
		'status'          => $status,
		'persona'         => $persona,
		'compiled_prompt' => $compiled,
	);
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
