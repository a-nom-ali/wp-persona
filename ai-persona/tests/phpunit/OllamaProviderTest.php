<?php

use Ai_Persona\Providers\Ollama_Provider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class OllamaProviderTest extends TestCase {

	protected function setUp(): void {
		Ai_Persona_Tests_HTTP_Stub::reset();
	}

	public function test_generate_returns_output_on_successful_response() {
		Ai_Persona_Tests_HTTP_Stub::queue(
			array(
				'body' => json_encode(
					array(
						'response' => 'Hello from minimax',
					)
				),
			)
		);

		$provider = new Ollama_Provider( 'http://localhost:11434', 'minimax-m2:cloud' );
		$result   = $provider->generate( 'Test prompt' );

		$this->assertSame( 'Hello from minimax', $result['output'] );
		$this->assertSame( 'ollama', $result['provider'] );
		$this->assertArrayHasKey( 'raw', $result );
	}

	public function test_generate_returns_error_message_when_request_fails() {
		Ai_Persona_Tests_HTTP_Stub::queue( new WP_Error( 'failed', 'Connection refused' ) );

		$provider = new Ollama_Provider( 'http://localhost:11434', 'minimax-m2:cloud' );
		$result   = $provider->generate( 'Test prompt' );

		$this->assertSame( '', $result['output'] );
		$this->assertSame( 'ollama', $result['provider'] );
		$this->assertSame( 'Connection refused', $result['error'] );
	}

	public function test_generate_handles_invalid_json_response() {
		Ai_Persona_Tests_HTTP_Stub::queue(
			array(
				'body' => '{invalid json',
			)
		);

		$provider = new Ollama_Provider( 'http://localhost:11434', 'minimax-m2:cloud' );
		$result   = $provider->generate( 'Test prompt' );

		$this->assertSame( '', $result['output'] );
		$this->assertSame( 'ollama', $result['provider'] );
		$this->assertSame( 'Invalid response from Ollama.', $result['error'] );
	}
}
