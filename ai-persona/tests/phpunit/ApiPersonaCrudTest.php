<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/frontend/api-endpoints.php';

class ApiPersonaCrudTest extends TestCase {

    protected function setUp(): void {
        ai_persona_tests_reset_filters();
        ai_persona_tests_reset_meta();
        ai_persona_tests_reset_posts();
    }

    public function test_handle_persona_create_persists_data() {
        $request = new WP_REST_Request(
            array(
                'title'   => 'Creative Coach',
                'status'  => 'draft',
                'persona' => array(
                    'role'       => 'You are a creative writing coach.',
                    'guidelines' => array( 'Offer constructive feedback' ),
                    'constraints'=> array( 'Avoid spoilers' ),
                ),
            )
        );

        $response = Ai_Persona\Frontend\handle_persona_create( $request );
        $data     = $response->get_data();

        $this->assertNotEmpty( $data['id'] );
        $this->assertSame( 'Creative Coach', $data['title'] );
        $this->assertSame( 'You are a creative writing coach.', $data['persona']['role'] );
        $this->assertStringContainsString( 'You are a creative writing coach.', $data['compiled_prompt'] );

        $stored = ai_persona_tests_get_post( $data['id'] );
        $this->assertSame( 'ai_persona', $stored['post_type'] );
    }

    public function test_handle_persona_update_modifies_existing_persona() {
        $create_request = new WP_REST_Request(
            array(
                'title'   => 'Creative Coach',
                'persona' => array(
                    'role'       => 'Initial role',
                    'guidelines' => array( 'Guideline one' ),
                ),
            )
        );

        $create_response = Ai_Persona\Frontend\handle_persona_create( $create_request );
        $created         = $create_response->get_data();

        $update_request = new WP_REST_Request(
            array(
                'id'      => $created['id'],
                'title'   => 'Creative Coach Updated',
                'persona' => array(
                    'role'       => 'Updated role',
                    'guidelines' => array( 'Stay concise' ),
                ),
            )
        );

        $update_response = Ai_Persona\Frontend\handle_persona_update( $update_request );
        $updated         = $update_response->get_data();

        $this->assertSame( 'Creative Coach Updated', $updated['title'] );
        $this->assertSame( 'Updated role', $updated['persona']['role'] );
        $this->assertSame( array( 'Stay concise' ), $updated['persona']['guidelines'] );

        $stored = ai_persona_tests_get_post( $created['id'] );
        $this->assertSame( 'Creative Coach Updated', $stored['post_title'] );
    }
}
