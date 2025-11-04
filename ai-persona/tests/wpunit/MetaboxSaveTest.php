<?php

use Ai_Persona\Admin;

class MetaboxSaveTest extends WP_UnitTestCase {

	public function test_metabox_saves_structured_payload() {
		$post_id = $this->factory->post->create(
			array(
				'post_type' => 'ai_persona',
				'post_title' => 'Metabox Persona',
			)
		);

		$payload = array(
			'role'        => 'You are a helpful assistant.',
			'guidelines'  => array( 'Be kind', 'Stay concise' ),
			'constraints' => array( 'No spoilers' ),
			'variables'   => array(
				array( 'name' => 'user_name', 'description' => 'Name of the requesting user' ),
			),
			'examples'    => array(
				array( 'input' => 'Hello?', 'output' => 'Hi there!' ),
			),
		);

		$_POST['ai_persona_metabox_nonce'] = wp_create_nonce( 'ai_persona_metabox' );
		$_POST['ai_persona_payload']       = wp_json_encode( $payload );

		Admin\save_metabox( $post_id );

		$this->assertSame( 'You are a helpful assistant.', get_post_meta( $post_id, 'ai_persona_role', true ) );
		$this->assertSame( $payload['guidelines'], get_post_meta( $post_id, 'ai_persona_guidelines', true ) );
		$this->assertSame( $payload['constraints'], get_post_meta( $post_id, 'ai_persona_constraints', true ) );
		$this->assertSame( $payload['variables'], get_post_meta( $post_id, 'ai_persona_variables', true ) );
		$this->assertSame( $payload['examples'], get_post_meta( $post_id, 'ai_persona_examples', true ) );

		unset( $_POST['ai_persona_metabox_nonce'], $_POST['ai_persona_payload'] );
	}
}
