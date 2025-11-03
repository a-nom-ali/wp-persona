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
}
