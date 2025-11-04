<?php

namespace Ai_Persona\Webhooks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve saved webhook endpoints.
 *
 * @return array
 */
function get_endpoints() {
	$endpoints = get_option( 'ai_persona_webhook_endpoints', array() );

	if ( ! is_array( $endpoints ) ) {
		$endpoints = array();
	}

	return apply_filters( 'ai_persona_webhook_endpoints', $endpoints );
}

/**
 * Sanitize the webhook endpoints option.
 *
 * @param mixed $value Raw option value.
 * @return array
 */
function sanitize_endpoints_option( $value ) {
	if ( is_string( $value ) ) {
		$value = preg_split( '/\r?\n/', $value );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$clean = array();

	foreach ( $value as $endpoint ) {
		$endpoint = trim( $endpoint );

		if ( '' === $endpoint ) {
			continue;
		}

		$sanitized = esc_url_raw( $endpoint );

		if ( empty( $sanitized ) ) {
			continue;
		}

		$clean[] = $sanitized;
	}

	return $clean;
}

/**
 * Dispatch persona response webhooks.
 *
 * @param array  $response Provider response payload.
 * @param string $prompt   Compiled persona prompt.
 * @param array  $context  Request context data.
 */
function dispatch_response_webhooks( $response, $prompt, $context ) {
	$endpoints = get_endpoints();

	if ( empty( $endpoints ) ) {
		return;
	}

	$payload = array(
		'persona_id'      => isset( $context['persona_id'] ) ? $context['persona_id'] : null,
		'user_input'      => isset( $context['user_input'] ) ? $context['user_input'] : '',
		'compiled_prompt' => $prompt,
		'provider'        => isset( $response['provider'] ) ? $response['provider'] : 'unknown',
		'output'          => isset( $response['output'] ) ? $response['output'] : '',
		'context'         => $context,
		'raw_response'    => $response,
	);

	/**
	 * Filter the payload sent to webhooks.
	 *
	 * Return false to skip dispatching.
	 *
	 * @param array|false $payload  Prepared payload.
	 * @param array        $response Provider response payload.
	 * @param string       $prompt   Compiled persona prompt.
	 * @param array        $context  Request context data.
	 */
	$payload = apply_filters( 'ai_persona_webhook_payload', $payload, $response, $prompt, $context );

	if ( false === $payload ) {
		return;
	}

	$body = wp_json_encode( $payload );

	foreach ( $endpoints as $endpoint ) {
		$args = array(
			'body'    => $body,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 10,
		);

		/**
		 * Filter the request arguments for webhook dispatch.
		 *
		 * @param array  $args     Request arguments passed to wp_remote_post().
		 * @param string $endpoint Current webhook endpoint.
		 * @param array  $payload  Payload being sent.
		 */
		$args = apply_filters( 'ai_persona_webhook_request_args', $args, $endpoint, $payload );

		wp_remote_post( $endpoint, $args );
	}
}
