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

    public function test_permissions_check_rejects_invalid_credentials() {
        $request = new WP_REST_Request( array( '_wpnonce' => 'invalid' ) );
        $result  = Ai_Persona\Frontend\permissions_check( $request );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'You are not allowed to generate persona responses.', $result->get_error_message() );
    }
}
