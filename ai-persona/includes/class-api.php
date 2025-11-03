<?php

namespace Ai_Persona;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles outbound AI service requests.
 */
class API {

	/**
	 * Placeholder for sending prompts to providers.
	 *
	 * @param string $prompt Persona prompt.
	 * @param array  $context Request context data.
	 * @return array
	 */
	public function generate( $prompt, array $context = array() ) {
		/**
		 * Filter the persona prompt before dispatch.
		 *
		 * @param string $prompt  Prepared prompt.
		 * @param array  $context Context data.
		 */
		$prompt = apply_filters( 'ai_persona_prompt_before_render', $prompt, $context );

		$response = array(
			'output' => '',
		);

		/**
		 * Fire after a persona response is produced.
		 *
		 * @param array  $response API response payload.
		 * @param string $prompt   Prompt that generated the response.
		 * @param array  $context  Context data.
		 */
		do_action( 'ai_persona_response_after_generate', $response, $prompt, $context );

		return $response;
	}
}
