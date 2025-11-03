<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class PersonaHelpersTest extends TestCase {

	protected function setUp(): void {
		ai_persona_tests_reset_filters();
	}

	public function test_get_persona_data_handles_empty_meta() {
		$this->assertNull( Ai_Persona\get_persona_data( 0 ) );

		ai_persona_tests_set_post_meta( 123, 'ai_persona_role', 'Role text' );
		$data = Ai_Persona\get_persona_data( 123 );

		$this->assertSame( 123, $data['id'] );
		$this->assertSame( 'Role text', $data['role'] );
		$this->assertIsArray( $data['guidelines'] );
	}

	public function test_compile_persona_prompt_includes_sections() {
		$persona = array(
			'role'        => 'You are a tutor.',
			'guidelines'  => array( 'Be kind', 'Explain thoroughly' ),
			'constraints' => array( 'No spoilers' ),
			'variables'   => array(
				array(
					'name'        => 'user_name',
					'description' => 'Current learner',
				),
			),
			'examples'    => array(
				array(
					'input'  => 'Explain gravity',
					'output' => 'Gravity is...',
				),
			),
		);

		$prompt = Ai_Persona\compile_persona_prompt( $persona );

		$this->assertStringContainsString( 'You are a tutor.', $prompt );
		$this->assertStringContainsString( '- Be kind', $prompt );
		$this->assertStringContainsString( '{{user_name}}', $prompt );
		$this->assertStringContainsString( 'Examples:', $prompt );
	}
}
