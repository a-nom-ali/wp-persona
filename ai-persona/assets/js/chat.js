/* global wp */

( () => {
	const rootSelector = '.ai-persona-chat';

	const boot = () => {
		document.querySelectorAll( rootSelector ).forEach( ( node ) => {
			if ( node.dataset.bootstrapped ) {
				return;
			}

			node.dataset.bootstrapped = 'true';
			node.innerHTML = '<p>AI Persona chat placeholder. SSE stream integration pending.</p>';
		} );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
