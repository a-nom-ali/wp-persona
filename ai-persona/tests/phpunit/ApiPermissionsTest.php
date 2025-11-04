<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/frontend/api-endpoints.php';

class ApiPermissionsTest extends TestCase {

    protected function setUp(): void {
        ai_persona_tests_reset_filters();
        ai_persona_tests_set_current_user_can( false );
        ai_persona_tests_set_valid_nonce( 'valid-nonce' );
    }

    public function test_permissions_check_allows_editors() {
        ai_persona_tests_set_current_user_can( true );

        $request = new WP_REST_Request();
        $result  = Ai_Persona\Frontend\permissions_check( $request );

        $this->assertTrue( $result );
    }

    public function test_permissions_check_allows_valid_nonce() {
        $request = new WP_REST_Request( array( '_wpnonce' => 'valid-nonce' ) );
        $result  = Ai_Persona\Frontend\permissions_check( $request );

        $this->assertTrue( $result );
    }

    public function test_permissions_check_allows_nonce_header() {
        $request = new WP_REST_Request();
        $request->set_header( 'X-WP-Nonce', 'valid-nonce' );

        $result = Ai_Persona\Frontend\permissions_check( $request );

        $this->assertTrue( $result );
    }

    public function test_permissions_check_rejects_invalid_credentials() {
        $request = new WP_REST_Request( array( '_wpnonce' => 'invalid' ) );
        $result  = Ai_Persona\Frontend\permissions_check( $request );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'Authentication required. Provide a REST nonce or WordPress Application Password to continue.', $result->get_error_message() );

        $data = $result->get_error_data();

        $this->assertIsArray( $data );
        $this->assertSame( 401, $data['status'] );
        $this->assertArrayHasKey( 'details', $data );
        $this->assertStringContainsString( 'nonce', $data['details']['nonce'] );
        $this->assertStringContainsString( 'Application Password', $data['details']['application_password'] );
    }

    public function test_permissions_check_can_be_overridden_by_filter() {
        add_filter( 'ai_persona_rest_permissions_check', function ( $override, $request ) {
            return true;
        }, 10, 2 );

        $request = new WP_REST_Request();
        $result  = Ai_Persona\Frontend\permissions_check( $request );

        $this->assertTrue( $result );
    }

    public function test_permissions_check_filter_can_block_access() {
        add_filter( 'ai_persona_rest_permissions_check', function ( $override, $request ) {
            return new WP_Error( 'blocked', 'Blocked by filter.' );
        }, 10, 2 );

        $request = new WP_REST_Request();
        $result  = Ai_Persona\Frontend\permissions_check( $request );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'Blocked by filter.', $result->get_error_message() );
    }

    public function test_handle_persona_export_returns_payload() {
        ai_persona_tests_set_current_user_can( true );

        ai_persona_tests_set_post_meta( 456, 'ai_persona_role', 'Test role' );
        ai_persona_tests_set_post_meta( 456, 'ai_persona_guidelines', array( 'Be concise' ) );

        $request  = new WP_REST_Request( array( 'id' => 456 ) );
        $response = Ai_Persona\Frontend\handle_persona_export( $request );
        $data     = $response->get_data();

        $this->assertSame( 456, $data['id'] );
        $this->assertSame( 'Test role', $data['persona']['role'] );
        $this->assertStringContainsString( 'Test role', $data['compiled_prompt'] );
    }
}
