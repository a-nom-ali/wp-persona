const fs = require( 'fs' );
const path = require( 'path' );

const scriptPath = path.resolve( __dirname, '../../assets/js/chat.js' );
const script = fs.readFileSync( scriptPath, 'utf8' );

try {
	if ( ! /new\s+EventSource/.test( script ) ) {
		throw new Error( 'EventSource usage not found' );
	}

	if ( ! /stream/.test( script ) ) {
		throw new Error( 'Stream endpoint reference missing' );
	}

	console.log( 'chat-streaming.test: passed' );
} catch ( error ) {
	console.error( 'chat-streaming.test: failed', error.message );
	process.exitCode = 1;
}
