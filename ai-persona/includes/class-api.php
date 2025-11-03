<?php

namespace Ai_Persona;

use Ai_Persona\Providers\Null_Provider;
use Ai_Persona\Providers\Provider_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles outbound AI service requests.
 */
class API {

	/**
	 * Active provider client.
	 *
	 * @var Provider_Interface
	 */
	private $provider;

	/**
	 * Constructor.
	 *
	 * @param Provider_Interface|null $provider Provider to use.
	 */
	public function __construct( Provider_Interface $provider = null ) {
		$this->provider = $provider ?: $this->resolve_provider();
	}

	/**
	 * Resolve the provider using filters for extensibility.
	 *
	 * @return Provider_Interface
	 */
	private function resolve_provider() {
		$provider = apply_filters( 'ai_persona_resolve_provider', null );

		if ( $provider instanceof Provider_Interface ) {
			return $provider;
		}

		return new Null_Provider();
	}

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

		$response = $this->provider->generate( $prompt, $context );

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
