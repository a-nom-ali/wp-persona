<?php

namespace Ai_Persona\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Null provider returning empty payloads until a real client is wired.
 */
class Null_Provider implements Provider_Interface {

	/**
	 * {@inheritdoc}
	 */
	public function generate( $prompt, array $context = array() ) {
		return array(
			'output'   => '',
			'provider' => 'null',
		);
	}
}
