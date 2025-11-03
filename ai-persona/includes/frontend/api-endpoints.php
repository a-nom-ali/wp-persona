<?php

namespace Ai_Persona\Frontend;

use Ai_Persona\API;
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
}
add_action( 'rest_api_init', __NAMESPACE__ . '\\register_routes' );

/**
 * Validate REST permissions.
 *
 * @return bool|\WP_Error
 */
function permissions_check() {
	if ( current_user_can( 'edit_posts' ) ) {
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
	$prompt  = $request->get_param( 'prompt' );
	$context = (array) $request->get_param( 'context' );

	$api = new API();
	$result = $api->generate( (string) $prompt, $context );

	return new WP_REST_Response( $result, 200 );
}
