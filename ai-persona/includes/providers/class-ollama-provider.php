<?php

namespace Ai_Persona\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ollama provider using local minimax-m2:cloud model.
 */
class Ollama_Provider implements Provider_Interface {

	/**
	 * REST endpoint base URL.
	 *
	 * @var string
	 */
	private $base_url;

	/**
	 * Model identifier.
	 *
	 * @var string
	 */
	private $model;

	/**
	 * Constructor.
	 *
	 * @param string $base_url Ollama server URL.
	 * @param string $model    Model name to invoke.
	 */
	public function __construct( $base_url, $model ) {
		$this->base_url = untrailingslashit( $base_url );
		$this->model    = $model;
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate( $prompt, array $context = array() ) {
		$request_args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'model'  => $this->model,
					'prompt' => $prompt,
					'stream' => false,
				)
			),
			'timeout' => 15,
		);

		/**
		 * Allow request arguments to be filtered before dispatching to Ollama.
		 *
		 * @param array $request_args Request arguments.
		 * @param array $context      Request context.
		 */
		$request_args = apply_filters( 'ai_persona_ollama_request_args', $request_args, $context );

		$response = wp_remote_post( "{$this->base_url}/api/generate", $request_args );

		if ( is_wp_error( $response ) ) {
			return array(
				'output'   => '',
				'provider' => 'ollama',
				'error'    => $response->get_error_message(),
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return array(
				'output'   => '',
				'provider' => 'ollama',
				'error'    => __( 'Invalid response from Ollama.', 'ai-persona' ),
			);
		}

		return array(
			'output'   => isset( $data['response'] ) ? (string) $data['response'] : '',
			'provider' => 'ollama',
			'raw'      => $data,
		);
	}
}
