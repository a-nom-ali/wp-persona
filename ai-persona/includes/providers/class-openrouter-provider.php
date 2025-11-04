<?php

namespace Ai_Persona\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenRouter provider implementation.
 */
class OpenRouter_Provider implements Provider_Interface {

	/**
	 * API key credential.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Chat completion model name.
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
		$this->model    = $model ?: 'openai/gpt-4o-mini';
		$this->base_url = untrailingslashit( $base_url ?: 'https://openrouter.ai/api/v1' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate( $prompt, array $context = array() ) {
		if ( empty( $this->api_key ) ) {
			return array(
				'output'   => '',
				'provider' => 'openrouter',
				'error'    => __( 'Missing OpenRouter API key.', 'ai-persona' ),
			);
		}

		$messages = $this->build_messages( $prompt, $context );

		$request_args = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_key,
			),
			'body'    => wp_json_encode(
				array(
					'model'       => $this->model,
					'messages'    => $messages,
					'temperature' => 0.7,
				)
			),
			'timeout' => 20,
		);

		/**
		 * Filter the OpenRouter request arguments before dispatch.
		 *
		 * @param array $request_args Request arguments passed to wp_remote_post().
		 * @param array $context      Request context (persona, messages, etc).
		 */
		$request_args = apply_filters( 'ai_persona_openrouter_request_args', $request_args, $context );

		$endpoint = $this->base_url . '/chat/completions';
		$response = wp_remote_post( $endpoint, $request_args );

		if ( is_wp_error( $response ) ) {
			return array(
				'output'   => '',
				'provider' => 'openrouter',
				'error'    => $response->get_error_message(),
			);
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return array(
				'output'   => '',
				'provider' => 'openrouter',
				'error'    => __( 'Invalid response from OpenRouter.', 'ai-persona' ),
			);
		}

		if ( isset( $data['error']['message'] ) ) {
			return array(
				'output'   => '',
				'provider' => 'openrouter',
				'error'    => (string) $data['error']['message'],
				'raw'      => $data,
			);
		}

		$content = '';

		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			$content = (string) $data['choices'][0]['message']['content'];
		}

		return array(
			'output'   => $content,
			'provider' => 'openrouter',
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
	 * Prepare chat messages for OpenRouter.
	 *
	 * @param string $prompt  Persona system prompt.
	 * @param array  $context Request context.
	 * @return array
	 */
	private function build_messages( $prompt, array $context ) {
		$messages = array();

		if ( ! empty( $prompt ) ) {
			$messages[] = array(
				'role'    => 'system',
				'content' => (string) $prompt,
			);
		}

		if ( ! empty( $context['messages'] ) && is_array( $context['messages'] ) ) {
			foreach ( $context['messages'] as $message ) {
				if ( ! is_array( $message ) || empty( $message['role'] ) || ! isset( $message['content'] ) ) {
					continue;
				}

				$role = 'user';

				if ( 'assistant' === $message['role'] ) {
					$role = 'assistant';
				}

				$messages[] = array(
					'role'    => $role,
					'content' => (string) $message['content'],
				);
			}
		}

		if ( ! empty( $context['user_input'] ) ) {
			$messages[] = array(
				'role'    => 'user',
				'content' => (string) $context['user_input'],
			);
		}

		return $messages;
	}
}
