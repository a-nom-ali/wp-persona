<?php

use Ai_Persona\Providers\OpenAI_Provider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class OpenAIProviderTest extends TestCase {

    protected function setUp(): void {
        Ai_Persona_Tests_HTTP_Stub::reset();
    }

    public function test_generate_requires_api_key() {
        $provider = new OpenAI_Provider( '', 'gpt-4o-mini', 'https://api.openai.com/v1' );
        $result   = $provider->generate( 'Test prompt' );

        $this->assertSame( '', $result['output'] );
        $this->assertSame( 'openai', $result['provider'] );
        $this->assertSame( 'Missing OpenAI API key.', $result['error'] );
    }

    public function test_generate_sends_authorized_request() {
        $captured = array();

        Ai_Persona_Tests_HTTP_Stub::queue(
            function ( $url, $args ) use ( &$captured ) {
                $captured = compact( 'url', 'args' );

                return array(
                    'body' => json_encode(
                        array(
                            'choices' => array(
                                array(
                                    'message' => array( 'content' => 'OpenAI says hi' ),
                                ),
                            ),
                        )
                    ),
                );
            }
        );

        $provider = new OpenAI_Provider( 'test-key', 'gpt-4o-mini', 'https://api.openai.com/v1' );
        $result   = $provider->generate( 'Prompt' );

        $this->assertSame( 'OpenAI says hi', $result['output'] );
        $this->assertSame( 'openai', $result['provider'] );

        $this->assertSame( 'https://api.openai.com/v1/chat/completions', $captured['url'] );

        $this->assertArrayHasKey( 'headers', $captured['args'] );
        $this->assertSame( 'Bearer test-key', $captured['args']['headers']['Authorization'] );

        $payload = json_decode( $captured['args']['body'], true );
        $this->assertSame( 'gpt-4o-mini', $payload['model'] );
        $this->assertSame( 'Prompt', $payload['messages'][0]['content'] );
    }
}
