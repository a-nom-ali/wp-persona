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
	const escapeHtml = ( value = '' ) => value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	const formatInline = ( value = '' ) => {
		let output = escapeHtml( value );
		output = output.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
		output = output.replace(/\*([^*]+)\*/g, '<em>$1</em>');
		output = output.replace(/`([^`]+)`/g, '<code>$1</code>');
		output = output.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
		return output;
	};
	const renderMarkdown = ( target, markdown = '' ) => {
		const lines = ( markdown || '' ).split(/\r?\n/);
		const html = [];
		let inUnordered = false;
		let inOrdered = false;
		let inCodeBlock = false;
		let codeBuffer = [];
		const flushLists = () => {
			if ( inUnordered ) {
				html.push('</ul>');
				inUnordered = false;
			}
			if ( inOrdered ) {
				html.push('</ol>');
				inOrdered = false;
			}
		};
		lines.forEach( ( line ) => {
			const trimmed = line.trim();
			if ( trimmed.startsWith('```') ) {
				if ( inCodeBlock ) {
					html.push(`<pre><code>${ escapeHtml( codeBuffer.join('\n') ) }</code></pre>`);
					codeBuffer = [];
					inCodeBlock = false;
				} else {
					inCodeBlock = true;
				}
				return;
			}
			if ( inCodeBlock ) {
				codeBuffer.push( line );
				return;
			}
			if ( /^[-*]\s+/.test( trimmed ) ) {
				if ( ! inUnordered ) {
					flushLists();
					html.push('<ul>');
					inUnordered = true;
				}
				const item = trimmed.replace(/^[-*]\s+/, '');
				html.push(`<li>${ formatInline( item ) }</li>`);
				return;
			}
			if ( /^\d+\.\s+/.test( trimmed ) ) {
				if ( ! inOrdered ) {
					flushLists();
					html.push('<ol>');
					inOrdered = true;
				}
				const item = trimmed.replace(/^\d+\.\s+/, '');
				html.push(`<li>${ formatInline( item ) }</li>`);
				return;
			}
			if ( trimmed.length === 0 ) {
				flushLists();
				return;
			}
			flushLists();
			html.push(`<p>${ formatInline( trimmed ) }</p>`);
		} );
		flushLists();
		if ( inCodeBlock && codeBuffer.length ) {
			html.push(`<pre><code>${ escapeHtml( codeBuffer.join('\n') ) }</code></pre>`);
		}
		target.innerHTML = html.join('');
	};
	const parsePersonaOptions = ( rawValue ) => {
		if ( ! rawValue ) {
			return [];
		}
		let parsed = rawValue;

		if ( typeof rawValue === 'string' ) {
			try {
				parsed = JSON.parse( rawValue );
			} catch ( error ) {
				return [];
			}
		}

		if ( ! Array.isArray( parsed ) ) {
			return [];
		}

		return parsed
			.map( ( option ) => {
				if ( typeof option === 'number' ) {
					return {
						id: option,
						label: '',
					};
				}

				if ( typeof option === 'string' ) {
					return {
						id: parseInt( option, 10 ),
						label: '',
					};
				}

				if ( ! option || typeof option !== 'object' || ! option.id ) {
					return null;
				}

				return {
					id: parseInt( option.id, 10 ),
					label: option.label ? String( option.label ) : '',
				};
			} )
			.filter( ( option ) => option && ! Number.isNaN( option.id ) && option.id > 0 );
	};
	const normalizePersonaOptions = ( options ) => {
		const normalized = [];

		options.forEach( ( option ) => {
			if ( ! option || typeof option.id !== 'number' || Number.isNaN( option.id ) || option.id <= 0 ) {
				return;
			}

			if ( normalized.some( ( existing ) => existing.id === option.id ) ) {
				return;
			}

			const label = option.label && option.label.trim ? option.label.trim() : option.label;
			normalized.push( {
				id: option.id,
				label: label && label.length ? label : `Persona #${ option.id }`,
			} );
		} );

		return normalized;
	};
	const boot = () => {
		document.querySelectorAll( rootSelector ).forEach( ( node ) => {
			if ( ensureBootstrapped( node ) ) {
				return;
			}
			const datasetOptions = node.dataset.personaOptions ? parsePersonaOptions( node.dataset.personaOptions ) : [];
			let personaOptions = normalizePersonaOptions( datasetOptions );
			let personaId = parseInt( node.dataset.personaId || '0', 10 );
			const showHeader = node.dataset.showHeader !== 'false';
			const headerTitle = node.dataset.headerTitle || 'Chat with persona';

			if ( Number.isNaN( personaId ) || personaId < 0 ) {
				personaId = 0;
			}

			if ( personaId > 0 && ! personaOptions.some( ( option ) => option.id === personaId ) ) {
				personaOptions = normalizePersonaOptions(
					personaOptions.concat( [
						{
							id: personaId,
							label: '',
						},
					] )
				);
			}

			if ( personaId <= 0 && personaOptions.length ) {
				personaId = personaOptions[0].id;
			}

			const messages = [];
			const conversationHistory = [];
			const personaMetaCache = new Map();
			const wrapper = document.createElement( 'div' );
			wrapper.className = 'ai-persona-chat__inner';
			let header = null;
			let titleGroup = null;
			let title = null;
			let personaSummary = null;
			let personaSelect = null;

			const buildPersonaSelect = () => {
				if ( ! personaOptions.length ) {
					return null;
				}
				const select = document.createElement( 'select' );
				select.className = 'ai-persona-chat__persona-select';
				select.setAttribute( 'aria-label', 'Select persona' );
				personaOptions.forEach( ( option ) => {
					const optionNode = document.createElement( 'option' );
					optionNode.value = String( option.id );
					optionNode.textContent = option.label;
					select.appendChild( optionNode );
				} );
				const currentValue = personaId > 0 ? personaId : personaOptions[0].id;
				select.value = String( currentValue );
				return select;
			};

			if ( showHeader ) {
				header = document.createElement( 'div' );
				header.className = 'ai-persona-chat__header';
				titleGroup = document.createElement( 'div' );
				titleGroup.className = 'ai-persona-chat__title-group';
				title = document.createElement( 'h3' );
				title.className = 'ai-persona-chat__title';
				title.textContent = headerTitle;
				titleGroup.appendChild( title );

				if ( personaOptions.length ) {
					personaSummary = document.createElement( 'p' );
					personaSummary.className = 'ai-persona-chat__persona-summary';
					personaSummary.hidden = true;
					titleGroup.appendChild( personaSummary );
				}

				header.appendChild( titleGroup );

				personaSelect = buildPersonaSelect();

				if ( personaSelect ) {
					header.appendChild( personaSelect );
				}

				wrapper.appendChild( header );
			} else if ( personaOptions.length ) {
				personaSelect = buildPersonaSelect();

				if ( personaSelect ) {
					const toolbar = document.createElement( 'div' );
					toolbar.className = 'ai-persona-chat__persona-toolbar';
					toolbar.appendChild( personaSelect );
					wrapper.appendChild( toolbar );
				}
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
			const personaLabelFor = ( id ) => {
				const option = personaOptions.find( ( entry ) => entry.id === id );
				return option ? option.label : '';
			};
			const updatePersonaSummary = ( meta ) => {
				if ( ! personaSummary ) {
					return;
				}
				const summary = meta && meta.persona && meta.persona.role ? meta.persona.role : '';
				if ( summary ) {
					personaSummary.textContent = summary;
					personaSummary.hidden = false;
				} else {
					personaSummary.textContent = '';
					personaSummary.hidden = true;
				}
			};
			const updatePersonaPresentation = ( meta ) => {
				const label = personaLabelFor( personaId );

				if ( title ) {
					title.textContent = label ? `${ headerTitle } (${ label })` : headerTitle;
				}

				if ( textarea ) {
					textarea.placeholder = label ? `Ask ${ label }...` : 'Ask the persona...';
				}

				updatePersonaSummary( meta );
			};
			const fetchPersonaMeta = ( id ) => {
				if ( ! id ) {
					return Promise.resolve( null );
				}

				if ( personaMetaCache.has( id ) ) {
					return Promise.resolve( personaMetaCache.get( id ) );
				}

				const metaUrl = buildRestUrl( `persona/${ id }` );

				return fetch( metaUrl, { credentials: 'include' } )
					.then( ( response ) => ( response.ok ? response.json() : null ) )
					.then( ( payload ) => {
						personaMetaCache.set( id, payload );
						return payload;
					} )
					.catch( () => null );
			};
			const resetConversation = () => {
				conversationHistory.length = 0;
				messages.length = 0;
				flushMessages( list, messages );
			};
			const setActivePersona = ( nextId, options = {} ) => {
				const normalizedId = parseInt( nextId, 10 );

				if ( Number.isNaN( normalizedId ) || normalizedId <= 0 ) {
					return;
				}

				if ( normalizedId === personaId && ! options.force ) {
					return;
				}

				personaId = normalizedId;

				if ( personaSelect && String( personaSelect.value ) !== String( personaId ) ) {
					personaSelect.value = String( personaId );
				}

				resetConversation();
				closeStream();

				const label = personaLabelFor( personaId );

				if ( label ) {
					appendMessage( 'assistant', `Now chatting with ${ label }.` );
				}

				updatePersonaPresentation( null );

				fetchPersonaMeta( personaId ).then( ( meta ) => {
					if ( personaId === normalizedId ) {
						updatePersonaPresentation( meta );
					}
				} );
			};

			if ( personaSelect ) {
				personaSelect.addEventListener( 'change', ( event ) => {
					setActivePersona( event.target.value, { force: true } );
				} );
			}

			updatePersonaPresentation( null );

			if ( personaId ) {
				fetchPersonaMeta( personaId ).then( ( meta ) => {
					updatePersonaPresentation( meta );
				} );
			}
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
						renderMarkdown( assistantMessage, aggregate );
					} );
					activeStream.addEventListener( 'complete', ( event ) => {
						aggregate = event.data || aggregate;
						renderMarkdown( assistantMessage, aggregate );
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
							renderMarkdown( assistantMessage, payload.output );
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
