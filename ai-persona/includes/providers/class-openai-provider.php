<?php

namespace Ai_Persona\Providers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OpenAI provider implementation.
 */
class OpenAI_Provider implements Provider_Interface {

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
        $this->api_key  = $api_key;
        $this->model    = $model;
        $this->base_url = untrailingslashit( $base_url ?: 'https://api.openai.com/v1' );
    }

    /**
     * {@inheritdoc}
     */
    public function generate( $prompt, array $context = array() ) {
        if ( empty( $this->api_key ) ) {
            return array(
                'output'   => '',
                'provider' => 'openai',
                'error'    => __( 'Missing OpenAI API key.', 'ai-persona' ),
            );
        }

        // Build messages array with system prompt and user input
        $messages = array();

        // Add system message (persona prompt)
        if ( ! empty( $prompt ) ) {
            $messages[] = array(
                'role'    => 'system',
                'content' => $prompt,
            );
        }

        // Add conversation history if provided
        if ( ! empty( $context['messages'] ) && is_array( $context['messages'] ) ) {
            $messages = array_merge( $messages, $context['messages'] );
        }

        // Add current user input
        if ( ! empty( $context['user_input'] ) ) {
            $messages[] = array(
                'role'    => 'user',
                'content' => (string) $context['user_input'],
            );
        }

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
         * Allow request arguments to be filtered before dispatching to OpenAI.
         *
         * @param array $request_args Request arguments.
         * @param array $context      Request context.
         */
        $request_args = apply_filters( 'ai_persona_openai_request_args', $request_args, $context );

        $endpoint = $this->base_url . '/chat/completions';
        $response = wp_remote_post( $endpoint, $request_args );

        if ( is_wp_error( $response ) ) {
            return array(
                'output'   => '',
                'provider' => 'openai',
                'error'    => $response->get_error_message(),
            );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $data ) ) {
            return array(
                'output'   => '',
                'provider' => 'openai',
                'error'    => __( 'Invalid response from OpenAI.', 'ai-persona' ),
            );
        }

        if ( isset( $data['error']['message'] ) ) {
            return array(
                'output'   => '',
                'provider' => 'openai',
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
            'provider' => 'openai',
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
