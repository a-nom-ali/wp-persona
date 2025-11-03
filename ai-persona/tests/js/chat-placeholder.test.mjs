import test from 'node:test';
import assert from 'node:assert';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const __filename = fileURLToPath( import.meta.url );
const __dirname = dirname( __filename );

const scriptPath = resolve( __dirname, '../../assets/js/chat.js' );
const script = readFileSync( scriptPath, 'utf8' );

test( 'chat placeholder references SSE transition', () => {
	assert.match( script, /SSE stream integration pending/ );
} );
