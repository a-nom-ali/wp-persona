<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/frontend/design-tokens.php';

class DesignTokensTest extends TestCase {

	protected function setUp(): void {
		ai_persona_tests_reset_filters();
	}

	public function test_default_tokens_include_required_keys() {
		$tokens = Ai_Persona\Frontend\Design_Tokens\get_design_tokens();

		$this->assertArrayHasKey( '--ai-persona-color-surface', $tokens );
		$this->assertArrayHasKey( '--ai-persona-radius-base', $tokens );
	}

	public function test_tokens_filter_allows_overrides() {
		add_filter(
			'ai_persona_design_tokens',
			function ( $tokens ) {
				$tokens['--ai-persona-color-surface'] = '#000000';
				return $tokens;
			}
		);

		$tokens = Ai_Persona\Frontend\Design_Tokens\get_design_tokens();

		$this->assertSame( '#000000', $tokens['--ai-persona-color-surface'] );
	}

	public function test_css_builder_outputs_scope_and_properties() {
		$css = Ai_Persona\Frontend\Design_Tokens\build_css_from_tokens(
			array(
				'--custom-prop' => '10px',
			),
			'.scope'
		);

		$this->assertStringContainsString( '.scope', $css );
		$this->assertStringContainsString( '--custom-prop: 10px;', $css );
	}
}
