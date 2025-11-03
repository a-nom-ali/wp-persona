<?php

namespace Ai_Persona\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contract for AI provider clients.
 */
interface Provider_Interface {

	/**
	 * Send a prompt to the provider and return structured data.
	 *
	 * @param string $prompt  Final persona prompt.
	 * @param array  $context Additional request context (persona, user, metadata).
	 * @return array
	 */
	public function generate( $prompt, array $context = array() );

	/**
	 * Stream responses from the provider.
	 *
	 * @param string   $prompt Persona prompt.
	 * @param array    $context Additional request context data.
	 * @param callable $emit Callback receiving associative arrays describing events.
	 */
	public function stream( $prompt, array $context = array(), ?callable $emit = null );
}
