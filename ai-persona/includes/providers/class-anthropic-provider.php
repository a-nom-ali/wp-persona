<?php

namespace Ai_Persona\Providers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Anthropic provider implementation.
 */
class Anthropic_Provider implements Provider_Interface {

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
        $this->api_key  = $api_key;
        $this->model    = $model;
        $this->base_url = untrailingslashit( $base_url ?: 'https://api.anthropic.com/v1' );
    }

    /**
     * {@inheritdoc}
     */
    public function generate( $prompt, array $context = array() ) {
        if ( empty( $this->api_key ) ) {
            return array(
                'output'   => '',
                'provider' => 'anthropic',
                'error'    => __( 'Missing Anthropic API key.', 'ai-persona' ),
            );
        }

        // Build messages array (Anthropic uses separate system parameter)
        $messages = array();

        // Add conversation history if provided
        if ( ! empty( $context['messages'] ) && is_array( $context['messages'] ) ) {
            $messages = $context['messages'];
        }

        // Add current user input
        if ( ! empty( $context['user_input'] ) ) {
            $messages[] = array(
                'role'    => 'user',
                'content' => (string) $context['user_input'],
            );
        }

        // If no messages, add the prompt as user message (fallback)
        if ( empty( $messages ) ) {
            $messages[] = array(
                'role'    => 'user',
                'content' => $prompt,
            );
        }

        $request_body = array(
            'model'      => $this->model,
            'max_tokens' => 1024,
            'messages'   => $messages,
        );

        // Anthropic uses a separate system parameter (not in messages)
        if ( ! empty( $prompt ) && ! empty( $context['user_input'] ) ) {
            $request_body['system'] = $prompt;
        }

        $request_args = array(
            'headers' => array(
                'Content-Type'      => 'application/json',
                'x-api-key'         => $this->api_key,
                'anthropic-version' => '2023-06-01',
            ),
            'body'    => wp_json_encode( $request_body ),
            'timeout' => 20,
        );

        /**
         * Allow request arguments to be filtered before dispatching to Anthropic.
         *
         * @param array $request_args Request arguments.
         * @param array $context      Request context.
         */
        $request_args = apply_filters( 'ai_persona_anthropic_request_args', $request_args, $context );

        $endpoint = $this->base_url . '/messages';
        $response = wp_remote_post( $endpoint, $request_args );

        if ( is_wp_error( $response ) ) {
            return array(
                'output'   => '',
                'provider' => 'anthropic',
                'error'    => $response->get_error_message(),
            );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $data ) ) {
            return array(
                'output'   => '',
                'provider' => 'anthropic',
                'error'    => __( 'Invalid response from Anthropic.', 'ai-persona' ),
            );
        }

        if ( isset( $data['error']['message'] ) ) {
            return array(
                'output'   => '',
                'provider' => 'anthropic',
                'error'    => (string) $data['error']['message'],
                'raw'      => $data,
            );
        }

        $content = '';

        if ( isset( $data['content'][0]['text'] ) ) {
            $content = (string) $data['content'][0]['text'];
        }

        return array(
            'output'   => $content,
            'provider' => 'anthropic',
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
}
