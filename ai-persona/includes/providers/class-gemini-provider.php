<?php

namespace Ai_Persona\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Gemini provider implementation.
 */
class Gemini_Provider implements Provider_Interface {

	/**
	 * API key credential.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Model identifier.
	 *
	 * @var string
	 */
	private $model;

	/**
	 * REST API base URL.
	 *
	 * @var string
	 */
	private $base_url;

	/**
	 * Constructor.
	 *
	 * @param string $api_key  API key.
	 * @param string $model    Model identifier.
	 * @param string $base_url Base API URL.
	 */
	public function __construct( $api_key, $model, $base_url ) {
		$this->api_key  = (string) $api_key;
		$this->model    = $model ?: 'gemini-1.5-flash';
		$this->base_url = untrailingslashit( $base_url ?: 'https://generativelanguage.googleapis.com/v1beta' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate( $prompt, array $context = array() ) {
		if ( empty( $this->api_key ) ) {
			return array(
				'output'   => '',
				'provider' => 'gemini',
				'error'    => __( 'Missing Google Gemini API key.', 'ai-persona' ),
			);
		}

		$contents = $this->build_contents( $context );

		if ( empty( $contents ) ) {
			return array(
				'output'   => '',
				'provider' => 'gemini',
				'error'    => __( 'Prompt content is required.', 'ai-persona' ),
			);
		}

		$payload = array(
			'contents'          => $contents,
			'generationConfig'  => array(
				'temperature' => 0.7,
			),
			'safetySettings'    => array(), // Allow filters to inject settings if required.
		);

		if ( ! empty( $prompt ) ) {
			$payload['systemInstruction'] = array(
				'parts' => array(
					array(
						'text' => (string) $prompt,
					),
				),
			);
		}

		$endpoint = sprintf(
			'%s/models/%s:generateContent?key=%s',
			$this->base_url,
			rawurlencode( $this->model ),
			rawurlencode( $this->api_key )
		);

		$request_args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 20,
		);

		/**
		 * Filter the Gemini request arguments before dispatch.
		 *
		 * @param array $request_args Request arguments passed to wp_remote_post().
		 * @param array $context      Request context (persona, messages, etc).
		 * @param array $payload      Raw payload sent to Gemini.
		 */
		$request_args = apply_filters( 'ai_persona_gemini_request_args', $request_args, $context, $payload );

		$response = wp_remote_post( $endpoint, $request_args );

		if ( is_wp_error( $response ) ) {
			return array(
				'output'   => '',
				'provider' => 'gemini',
				'error'    => $response->get_error_message(),
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return array(
				'output'   => '',
				'provider' => 'gemini',
				'error'    => __( 'Invalid response from Google Gemini.', 'ai-persona' ),
			);
		}

		if ( isset( $data['error']['message'] ) ) {
			return array(
				'output'   => '',
				'provider' => 'gemini',
				'error'    => (string) $data['error']['message'],
				'raw'      => $data,
			);
		}

		$content = $this->extract_text( $data );

		return array(
			'output'   => $content,
			'provider' => 'gemini',
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

		$result = $this->generate( $prompt, $context );

		if ( ! empty( $result['error'] ) ) {
			$emit(
				array(
					'type' => 'error',
					'data' => $result['error'],
				)
			);
			$emit( array( 'type' => 'done' ) );
			return;
		}

		if ( isset( $result['output'] ) ) {
			$emit(
				array(
					'type' => 'token',
					'data' => (string) $result['output'],
				)
			);
		}

		$emit( array( 'type' => 'done' ) );
	}

	/**
	 * Build Gemini conversation contents from context.
	 *
	 * @param array $context Request context.
	 * @return array
	 */
	private function build_contents( array $context ) {
		$contents = array();

		if ( ! empty( $context['messages'] ) && is_array( $context['messages'] ) ) {
			foreach ( $context['messages'] as $message ) {
				if ( ! is_array( $message ) || empty( $message['role'] ) || ! isset( $message['content'] ) ) {
					continue;
				}

				$role = 'user';

				if ( 'assistant' === $message['role'] ) {
					$role = 'model';
				}

				$contents[] = array(
					'role'  => $role,
					'parts' => array(
						array(
							'text' => (string) $message['content'],
						),
					),
				);
			}
		}

		if ( ! empty( $context['user_input'] ) ) {
			$contents[] = array(
				'role'  => 'user',
				'parts' => array(
					array(
						'text' => (string) $context['user_input'],
					),
				),
			);
		}

		return $contents;
	}

	/**
	 * Extract concatenated text output from a Gemini response payload.
	 *
	 * @param array $data Raw response data.
	 * @return string
	 */
	private function extract_text( array $data ) {
		if ( empty( $data['candidates'][0]['content']['parts'] ) || ! is_array( $data['candidates'][0]['content']['parts'] ) ) {
			return '';
		}

		$parts = $data['candidates'][0]['content']['parts'];
		$texts = array();

		foreach ( $parts as $part ) {
			if ( isset( $part['text'] ) ) {
				$texts[] = (string) $part['text'];
			}
		}

		return implode( "\n\n", array_filter( $texts ) );
	}
}
