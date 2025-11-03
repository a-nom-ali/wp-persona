<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/admin/metaboxes.php';

class AdminImportTest extends TestCase {

    protected function setUp(): void {
        ai_persona_tests_set_valid_nonce( 'valid-nonce' );
    }

    protected function tearDown(): void {
        unset( $_FILES['ai_persona_import_file'], $_POST['ai_persona_import_nonce'] );
    }

    public function test_handle_import_upload_parses_json() {
        $tmp = tempnam( sys_get_temp_dir(), 'ai-persona-import' );

        $payload = array(
            'role'       => 'Imported role',
            'guidelines' => array( 'Stay helpful' ),
        );

        file_put_contents( $tmp, wp_json_encode( $payload ) );

        $_FILES['ai_persona_import_file'] = array(
            'name'     => 'persona.json',
            'type'     => 'application/json',
            'tmp_name' => $tmp,
            'error'    => 0,
            'size'     => filesize( $tmp ),
        );

        $_POST['ai_persona_import_nonce'] = 'valid-nonce';

        $result = Ai_Persona\Admin\ai_persona_handle_import_upload( 999 );

        $this->assertSame( 'Imported role', $result['role'] );
        $this->assertSame( array( 'Stay helpful' ), $result['guidelines'] );

        unlink( $tmp );
    }
}
