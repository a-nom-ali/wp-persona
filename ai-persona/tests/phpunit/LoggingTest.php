<?php

use Ai_Persona\Logging;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/logging.php';

class LoggingTest extends TestCase {

    protected function setUp(): void {
        ai_persona_tests_reset_filters();
        ai_persona_tests_reset_options();
        ai_persona_tests_reset_meta();
        ai_persona_tests_reset_uploads();
    }

    public function test_logging_skips_when_disabled() {
        delete_option( 'ai_persona_logging_enabled' );

        $result = Logging\is_enabled();
        $this->assertFalse( $result );

        Logging\log_generation_event( array( 'provider' => 'test' ), 'prompt', array() );

        $log_path = $this->get_log_path();
        $this->assertFileDoesNotExist( $log_path );
    }

    public function test_logging_writes_entry_when_enabled() {
        update_option( 'ai_persona_logging_enabled', true );

        Logging\log_generation_event(
            array( 'provider' => 'test-provider' ),
            'Example prompt',
            array( 'persona_id' => 42, 'user_input' => 'Hello' )
        );

        $log_path = $this->get_log_path();
        $this->assertFileExists( $log_path );

        $contents = file_get_contents( $log_path );
        $this->assertStringContainsString( '"persona_id": 42', $contents );
        $this->assertStringContainsString( '"provider": "test-provider"', $contents );
    }

    private function get_log_path() {
        $uploads = wp_upload_dir();
        return trailingslashit( $uploads['basedir'] ) . 'ai-persona/persona.log';
    }
}
