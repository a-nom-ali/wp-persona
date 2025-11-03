<?php

use Ai_Persona\Providers\Null_Provider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class NullProviderTest extends TestCase {

	public function test_stream_emits_done_event() {
		$provider = new Null_Provider();
		$events   = array();

		$provider->stream(
			'ignored prompt',
			array(),
			function ( $event ) use ( &$events ) {
				$events[] = $event;
			}
		);

		$this->assertNotEmpty( $events );
		$this->assertSame( 'token', $events[0]['type'] );
		$this->assertSame( 'done', $events[1]['type'] );
	}
}
