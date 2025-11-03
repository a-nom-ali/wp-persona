<?php

use Ai_Persona\API;
use Ai_Persona\Providers\Provider_Interface;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/class-api.php';

class ApiStreamTest extends TestCase {

    protected function setUp(): void {
        ai_persona_tests_reset_filters();
    }

    public function test_stream_passes_filtered_prompt_to_provider() {
        $captured = array();

        $provider = new class( $captured ) implements Provider_Interface {
            public $captured;

            public function __construct( & $captured ) {
                $this->captured =& $captured;
            }

            public function generate( $prompt, array $context = array() ) {
                return array();
            }

            public function stream( $prompt, array $context = array(), ?callable $emit = null ) {
                $this->captured['prompt']  = $prompt;
                $this->captured['context'] = $context;

                if ( $emit ) {
                    $emit( array( 'type' => 'done' ) );
                }
            }
        };

        add_filter(
            'ai_persona_prompt_before_render',
            function ( $prompt ) {
                return $prompt . ' +filtered';
            },
            10,
            2
        );

        $api    = new API( $provider );
        $events = array();

        $api->stream(
            'original prompt',
            array( 'persona_id' => 42 ),
            function ( $event ) use ( &$events ) {
                $events[] = $event;
            }
        );

        $this->assertSame( 'original prompt +filtered', $captured['prompt'] );
        $this->assertSame( 42, $captured['context']['persona_id'] );
        $this->assertSame( 'done', $events[0]['type'] );
    }
}
