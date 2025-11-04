/* global wp */

( () => {
	const rootSelector = '.ai-persona-chat';

	const settings = window.AiPersonaSettings || {};

	const ensureBootstrapped = ( node ) => {
		if ( node.dataset.bootstrapped ) {
			return true;
		}

		node.dataset.bootstrapped = 'true';
		return false;
	};

	const createMessageEl = ( role, content = '' ) => {
		const message = document.createElement( 'div' );
		message.className = `ai-persona-chat__message ai-persona-chat__message--${ role }`;
		message.textContent = content;
		return message;
	};

	const buildRestUrl = ( path, params = {} ) => {
		const base = settings.restUrl || `${ window.location.origin }/wp-json/ai-persona/v1/`;
		const url = new URL( path, base );

		Object.entries( params ).forEach( ( [ key, value ] ) => {
			if ( value !== undefined && value !== null && value !== '' ) {
				url.searchParams.set( key, value );
			}
		} );

		return url.toString();
	};

	const flushMessages = ( container, messages ) => {
		container.innerHTML = '';
		messages.forEach( ( message ) => container.appendChild( message ) );
	};

	const boot = () => {
		document.querySelectorAll( rootSelector ).forEach( ( node ) => {
			if ( ensureBootstrapped( node ) ) {
				return;
			}

			const personaId = parseInt( node.dataset.personaId || '0', 10 );
			const showHeader = node.dataset.showHeader !== 'false';
			const headerTitle =
				node.dataset.headerTitle || 'Chat with persona';
			const messages = [];
			const conversationHistory = [];

			const wrapper = document.createElement( 'div' );
			wrapper.className = 'ai-persona-chat__inner';

			if ( showHeader ) {
				const header = document.createElement( 'div' );
				header.className = 'ai-persona-chat__header';

				const title = document.createElement( 'h3' );
				title.className = 'ai-persona-chat__title';
				title.textContent = headerTitle;

				header.appendChild( title );
				wrapper.appendChild( header );
			}

			const list = document.createElement( 'div' );
			list.className = 'ai-persona-chat__messages';

			const form = document.createElement( 'form' );
			form.className = 'ai-persona-chat__form';

			const textarea = document.createElement( 'textarea' );
			textarea.className = 'ai-persona-chat__input';
			textarea.placeholder = 'Ask the persona...';
			textarea.rows = 3;

			const submit = document.createElement( 'button' );
			submit.type = 'submit';
			submit.className = 'ai-persona-chat__submit';
			submit.textContent = 'Send';

			form.appendChild( textarea );
			form.appendChild( submit );

			wrapper.appendChild( list );
			wrapper.appendChild( form );

			node.innerHTML = '';
			node.appendChild( wrapper );

			let activeStream = null;

			const setLoading = ( state ) => {
				submit.disabled = state;
				submit.textContent = state ? 'Streaming…' : 'Send';
				textarea.readOnly = state;
			};

			const appendMessage = ( role, content ) => {
				const message = createMessageEl( role, content );
				messages.push( message );
				flushMessages( list, messages );
				list.scrollTop = list.scrollHeight;
				return message;
			};

			const closeStream = () => {
				if ( activeStream && typeof activeStream.close === 'function' ) {
					activeStream.close();
				}
				activeStream = null;
			};

			const handleError = ( target, error ) => {
				const previous = target.textContent;
				target.textContent = `${ previous }\n[Error: ${ error }]`;
			};

			const sendPrompt = ( prompt ) => {
				if ( ! prompt.trim() ) {
					return;
				}

				closeStream();

				appendMessage( 'user', prompt );
				const assistantMessage = appendMessage( 'assistant', '' );

				// Add user message to conversation history
				conversationHistory.push( {
					role: 'user',
					content: prompt,
				} );

				setLoading( true );

				const params = {
					prompt,
					persona_id: personaId || undefined,
					_wpnonce: settings.nonce || undefined,
				};

				// Add conversation history to params (for GET request, we'll send via POST body for streaming)
				if ( conversationHistory.length > 1 ) {
					// Exclude the current user message we just added
					params.messages = JSON.stringify( conversationHistory.slice( 0, -1 ) );
				}

				if ( 'EventSource' in window ) {
					const streamUrl = buildRestUrl( 'stream', params );
					let aggregate = '';

					activeStream = new EventSource( streamUrl, { withCredentials: true } );

					activeStream.addEventListener( 'message', ( event ) => {
						aggregate += event.data;
						assistantMessage.textContent = aggregate;
					} );

					activeStream.addEventListener( 'complete', ( event ) => {
						aggregate = event.data || aggregate;
						assistantMessage.textContent = aggregate;

						// Add assistant response to conversation history
						if ( aggregate ) {
							conversationHistory.push( {
								role: 'assistant',
								content: aggregate,
							} );
						}

						setLoading( false );
						closeStream();
					} );

					activeStream.addEventListener( 'error', ( event ) => {
						const message = event && event.data ? event.data : 'Connection error or stream interrupted. Please check provider configuration.';
						handleError( assistantMessage, message );
						setLoading( false );
						closeStream();
					} );

					activeStream.onerror = ( event ) => {
						// EventSource built-in error handler
						if ( aggregate.length === 0 ) {
							handleError( assistantMessage, 'Failed to connect to stream. Please check if the AI provider is configured correctly.' );
						}
						setLoading( false );
						closeStream();
					};

					return;
				}

				// Fallback: fetch entire response.
				const fallbackUrl = buildRestUrl( 'generate' );

				// Prepare request body with conversation history
				const requestBody = {
					prompt,
					context: { persona_id: personaId },
					_wpnonce: settings.nonce || undefined,
				};

				// Add conversation history (excluding current user message)
				if ( conversationHistory.length > 1 ) {
					requestBody.messages = conversationHistory.slice( 0, -1 );
				}

				fetch( fallbackUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify( requestBody ),
					credentials: 'include',
				} )
					.then( ( response ) => response.json() )
					.then( ( payload ) => {
						if ( payload && payload.output ) {
							assistantMessage.textContent = payload.output;

							// Add assistant response to conversation history
							conversationHistory.push( {
								role: 'assistant',
								content: payload.output,
							} );
						} else if ( payload && payload.error ) {
							handleError( assistantMessage, payload.error );
						} else {
							handleError( assistantMessage, 'Unexpected response' );
						}
					} )
					.catch( ( error ) => {
						handleError( assistantMessage, error.message || 'Request failed' );
					} )
					.finally( () => {
						setLoading( false );
					} );
			};

			form.addEventListener( 'submit', ( event ) => {
				event.preventDefault();
				const value = textarea.value;
				textarea.value = '';
				sendPrompt( value );
			} );
		} );
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
