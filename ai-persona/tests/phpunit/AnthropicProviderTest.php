<?php

use Ai_Persona\Providers\Anthropic_Provider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class AnthropicProviderTest extends TestCase {

    protected function setUp(): void {
        Ai_Persona_Tests_HTTP_Stub::reset();
    }

    public function test_generate_requires_api_key() {
        $provider = new Anthropic_Provider( '', 'claude-3-haiku-20240307', 'https://api.anthropic.com/v1' );
        $result   = $provider->generate( 'Prompt' );

        $this->assertSame( '', $result['output'] );
        $this->assertSame( 'anthropic', $result['provider'] );
        $this->assertSame( 'Missing Anthropic API key.', $result['error'] );
    }

    public function test_generate_sends_authorized_request() {
        $captured = array();

        Ai_Persona_Tests_HTTP_Stub::queue(
            function ( $url, $args ) use ( &$captured ) {
                $captured = compact( 'url', 'args' );

                return array(
                    'body' => json_encode(
                        array(
                            'content' => array(
                                array( 'text' => 'Anthropic response' ),
                            ),
                        )
                    ),
                );
            }
        );

        $provider = new Anthropic_Provider( 'anth-key', 'claude-3-haiku-20240307', 'https://api.anthropic.com/v1' );
        $result   = $provider->generate( 'Prompt' );

        $this->assertSame( 'Anthropic response', $result['output'] );
        $this->assertSame( 'anthropic', $result['provider'] );

        $this->assertSame( 'https://api.anthropic.com/v1/messages', $captured['url'] );
        $this->assertSame( 'anth-key', $captured['args']['headers']['x-api-key'] );

        $payload = json_decode( $captured['args']['body'], true );
        $this->assertSame( 'claude-3-haiku-20240307', $payload['model'] );
        $this->assertSame( 'Prompt', $payload['messages'][0]['content'] );
    }
}
