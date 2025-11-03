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

	/**
	 * {@inheritdoc}
	 */
	public function stream( $prompt, array $context = array(), ?callable $emit = null ) {
		if ( ! is_callable( $emit ) ) {
			return;
		}

		if ( ! function_exists( 'curl_init' ) ) {
			$emit(
				array(
					'type' => 'error',
					'data' => __( 'cURL is required for streaming responses.', 'ai-persona' ),
				)
			);

			$emit(
				array(
					'type' => 'done',
				)
			);

			return;
		}

		$payload = array(
			'model'  => $this->model,
			'prompt' => $prompt,
			'stream' => true,
		);

		$ch = curl_init( "{$this->base_url}/api/generate" );
		$buffer = '';

		curl_setopt_array(
			$ch,
			array(
				CURLOPT_POST           => true,
				CURLOPT_HTTPHEADER     => array( 'Content-Type: application/json' ),
				CURLOPT_POSTFIELDS     => wp_json_encode( $payload ),
				CURLOPT_RETURNTRANSFER => false,
				CURLOPT_WRITEFUNCTION  => function ( $handle, $chunk ) use ( &$buffer, $emit ) {
					$buffer .= $chunk;

					while ( false !== ( $pos = strpos( $buffer, "\n" ) ) ) {
						$line = trim( substr( $buffer, 0, $pos ) );
						$buffer = substr( $buffer, $pos + 1 );

						if ( '' === $line ) {
							continue;
						}

						$data = json_decode( $line, true );

						if ( ! is_array( $data ) ) {
							continue;
						}

						if ( isset( $data['error'] ) ) {
							$emit(
								array(
									'type' => 'error',
									'data' => (string) $data['error'],
									'raw'  => $data,
								)
							);
							continue;
						}

						if ( isset( $data['response'] ) ) {
							$emit(
								array(
									'type' => 'token',
									'data' => (string) $data['response'],
									'raw'  => $data,
								)
							);
						}

						if ( ! empty( $data['done'] ) ) {
							$emit(
								array(
									'type' => 'done',
									'raw'  => $data,
								)
							);
						}
					}

					return strlen( $chunk );
				},
				CURLOPT_TIMEOUT        => 0,
				CURLOPT_CONNECTTIMEOUT => 5,
			)
		);

		curl_exec( $ch );

		if ( 0 !== curl_errno( $ch ) ) {
			$emit(
				array(
					'type' => 'error',
					'data' => curl_error( $ch ),
				)
			);
		}

		curl_close( $ch );
	}
}
