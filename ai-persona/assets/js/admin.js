/* global wp */

( () => {
	if ( ! window.wp || ! wp.element || ! wp.components ) {
		return;
	}

	const root = document.getElementById( 'ai-persona-builder' );
	const hiddenInput = document.getElementById( 'ai-persona-payload' );

	if ( ! root || ! hiddenInput ) {
		return;
	}

	const { __ } = wp.i18n || { __: ( string ) => string };
	const { createElement: el, Fragment, useState, useEffect, useMemo, render } = wp.element;
	const {
		TextareaControl,
		TextControl,
		Button,
		Notice,
		Card,
		CardBody,
		CardHeader,
		Modal,
		Spinner,
		SelectControl,
	} = wp.components;
	const apiFetch = wp.apiFetch ? wp.apiFetch : null;

	const uniqueId = ( () => {
		let index = Date.now();
		return () => `ai-persona-item-${ index++ }`;
	} )();

	const parseInitialState = () => {
		try {
			const raw = root.dataset.initialState;
			if ( raw ) {
				return JSON.parse( raw );
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.warn( 'Failed to parse persona initial state.', error );
		}

		return {};
	};

	const hydrateList = ( list ) => {
		if ( ! Array.isArray( list ) || ! list.length ) {
			return [ { id: uniqueId(), value: '' } ];
		}

		return list.map( ( value ) => ( {
			id: uniqueId(),
			value: value || '',
		} ) );
	};

	const hydrateExamples = ( examples ) => {
		if ( ! Array.isArray( examples ) || ! examples.length ) {
			return [ { id: uniqueId(), input: '', output: '' } ];
		}

		return examples.map( ( example ) => ( {
			id: uniqueId(),
			input: ( example && example.input ) || '',
			output: ( example && example.output ) || '',
		} ) );
	};

	const hydrateVariables = ( variables ) => {
		if ( ! Array.isArray( variables ) || ! variables.length ) {
			return [ { id: uniqueId(), name: '', description: '' } ];
		}

		return variables.map( ( variable ) => ( {
			id: uniqueId(),
			name: ( variable && variable.name ) || '',
			description: ( variable && variable.description ) || '',
		} ) );
	};

	const normaliseWizardSuggestions = ( payload ) => {
		const safeList = ( list ) =>
			Array.isArray( list )
				? list
						.map( ( item ) => ( item === null || item === undefined ? '' : String( item ) ).trim() )
						.filter( ( item ) => item.length > 0 )
				: [];

		const normalised = {
			role: '',
			guidelines: [],
			constraints: [],
			variables: [],
			examples: [],
		};

		if ( ! payload || 'object' !== typeof payload ) {
			return normalised;
		}

		if ( payload.role ) {
			normalised.role = String( payload.role ).trim();
		}

		normalised.guidelines = safeList( payload.guidelines );
		normalised.constraints = safeList( payload.constraints );

		if ( Array.isArray( payload.variables ) ) {
			normalised.variables = payload.variables
				.map( ( variable ) => {
					if ( ! variable || 'object' !== typeof variable ) {
						return null;
					}

					const name = ( variable.name || variable.token || variable.key || '' ).toString().trim();
					const description = ( variable.description || variable.detail || variable.about || '' ).toString().trim();

					if ( ! name ) {
						return null;
					}

					return {
						name,
						description,
					};
				} )
				.filter( Boolean );
		}

		if ( Array.isArray( payload.examples ) ) {
			normalised.examples = payload.examples
				.map( ( example ) => {
					if ( ! example || 'object' !== typeof example ) {
						return null;
					}

					const input = ( example.input || example.user || example.prompt || '' ).toString().trim();
					const output = ( example.output || example.response || example.assistant || '' ).toString().trim();

					if ( ! input && ! output ) {
						return null;
					}

					return {
						input,
						output,
					};
				} )
				.filter( Boolean );
		}

		return normalised;
	};

	const initialData = parseInitialState();

	const initialState = {
		role: initialData.role || '',
		guidelines: hydrateList( initialData.guidelines ),
		constraints: hydrateList( initialData.constraints ),
		examples: hydrateExamples( initialData.examples ),
		variables: hydrateVariables( initialData.variables ),
	};

	const Builder = () => {
		const [ state, setState ] = useState( initialState );
		const [ wizardOpen, setWizardOpen ] = useState( false );
		const [ wizardLoading, setWizardLoading ] = useState( false );
		const [ wizardError, setWizardError ] = useState( '' );
		const [ wizardSuggestions, setWizardSuggestions ] = useState( null );
		const [ wizardForm, setWizardForm ] = useState( {
			goal: '',
			audience: '',
			tone: '',
			detail: 'balanced',
		} );

		const canUseWizard = typeof apiFetch === 'function';

		const payload = useMemo( () => {
			return {
				role: state.role || '',
				guidelines: state.guidelines
					.map( ( item ) => item.value.trim() )
					.filter( ( value ) => value.length > 0 ),
				constraints: state.constraints
					.map( ( item ) => item.value.trim() )
					.filter( ( value ) => value.length > 0 ),
				examples: state.examples
					.map( ( example ) => ( {
						input: ( example.input || '' ).trim(),
						output: ( example.output || '' ).trim(),
					} ) )
					.filter( ( example ) => example.input || example.output ),
				variables: state.variables
					.map( ( variable ) => ( {
						name: ( variable.name || '' ).trim(),
						description: ( variable.description || '' ).trim(),
					} ) )
					.filter( ( variable ) => variable.name ),
			};
		}, [ state ] );

		useEffect( () => {
			hiddenInput.value = JSON.stringify( payload );
		}, [ payload ] );

		const errors = useMemo( () => {
			const issues = [];
			if ( ! payload.role.trim() ) {
				issues.push( __( 'Role is required to anchor the persona.', 'ai-persona' ) );
			}

			if ( payload.guidelines.length === 0 ) {
				issues.push( __( 'Add at least one guideline so the persona has behavioural direction.', 'ai-persona' ) );
			}

			return issues;
		}, [ payload ] );

		const preview = useMemo( () => {
			const chunks = [];

			if ( payload.role ) {
				chunks.push( `${ __( 'Role', 'ai-persona' ) }:\n${ payload.role }` );
			}

			if ( payload.guidelines.length > 0 ) {
				chunks.push( `${ __( 'Guidelines', 'ai-persona' ) }:\n${ payload.guidelines.map( ( item ) => `- ${ item }` ).join( '\n' ) }` );
			}

			if ( payload.constraints.length > 0 ) {
				chunks.push( `${ __( 'Constraints', 'ai-persona' ) }:\n${ payload.constraints.map( ( item ) => `- ${ item }` ).join( '\n' ) }` );
			}

			if ( payload.variables.length > 0 ) {
				chunks.push( `${ __( 'Variables', 'ai-persona' ) }:\n${ payload.variables.map( ( variable ) => `{{${ variable.name }}} – ${ variable.description || __( 'Description pending', 'ai-persona' ) }` ).join( '\n' ) }` );
			}

			if ( payload.examples.length > 0 ) {
				chunks.push( `${ __( 'Examples', 'ai-persona' ) }:\n${ payload.examples.map( ( example, index ) => `${ index + 1 }. ${ __( 'User', 'ai-persona' ) }: ${ example.input || __( 'n/a', 'ai-persona' ) }\n   ${ __( 'Assistant', 'ai-persona' ) }: ${ example.output || __( 'n/a', 'ai-persona' ) }` ).join( '\n\n' ) }` );
			}

			return chunks.join( '\n\n' );
		}, [ payload ] );

		const updateListItem = ( key, id, value ) => {
			setState( ( current ) => ( {
				...current,
				[ key ]: current[ key ].map( ( item ) => ( item.id === id ? { ...item, value } : item ) ),
			} ) );
		};

		const removeListItem = ( key, id ) => {
			setState( ( current ) => {
				const next = current[ key ].filter( ( item ) => item.id !== id );
				return {
					...current,
					[ key ]: next.length ? next : [ { id: uniqueId(), value: '' } ],
				};
			} );
		};

		const addListItem = ( key ) => {
			setState( ( current ) => ( {
				...current,
				[ key ]: [ ...current[ key ], { id: uniqueId(), value: '' } ],
			} ) );
		};

		const updateExample = ( id, field, value ) => {
			setState( ( current ) => ( {
				...current,
				examples: current.examples.map( ( example ) => ( example.id === id ? { ...example, [ field ]: value } : example ) ),
			} ) );
		};

		const removeExample = ( id ) => {
			setState( ( current ) => {
				const next = current.examples.filter( ( example ) => example.id !== id );
				return {
					...current,
					examples: next.length ? next : [ { id: uniqueId(), input: '', output: '' } ],
				};
			} );
		};

		const addExample = () => {
			setState( ( current ) => ( {
				...current,
				examples: [ ...current.examples, { id: uniqueId(), input: '', output: '' } ],
			} ) );
		};

		const updateVariable = ( id, field, value ) => {
			setState( ( current ) => ( {
				...current,
				variables: current.variables.map( ( variable ) => ( variable.id === id ? { ...variable, [ field ]: value } : variable ) ),
			} ) );
		};

		const removeVariable = ( id ) => {
			setState( ( current ) => {
				const next = current.variables.filter( ( variable ) => variable.id !== id );
				return {
					...current,
					variables: next.length ? next : [ { id: uniqueId(), name: '', description: '' } ],
				};
			} );
		};

		const addVariable = () => {
			setState( ( current ) => ( {
				...current,
				variables: [ ...current.variables, { id: uniqueId(), name: '', description: '' } ],
			} ) );
		};

		const openWizard = () => {
			if ( ! canUseWizard ) {
				return;
			}

			setWizardOpen( true );
			setWizardError( '' );
			setWizardSuggestions( null );
			setWizardLoading( false );
		};

		const closeWizard = () => {
			setWizardOpen( false );
			setWizardError( '' );
			setWizardLoading( false );
		};

		const updateWizardForm = ( key, value ) => {
			setWizardForm( ( current ) => ( {
				...current,
				[ key ]: value,
			} ) );
		};

		const handleWizardGenerate = () => {
			if ( ! canUseWizard ) {
				setWizardError( __( 'The WordPress REST client is unavailable in this context.', 'ai-persona' ) );
				return;
			}

			if ( ! wizardForm.goal.trim() ) {
				setWizardError( __( 'Describe the persona goal before requesting suggestions.', 'ai-persona' ) );
				return;
			}

			setWizardLoading( true );
			setWizardError( '' );
			setWizardSuggestions( null );

			const systemPrompt = [
				'You are an expert AI persona prompt engineer for WordPress.',
				'Return JSON with the following keys: role (string), guidelines (array of strings), constraints (array of strings),',
				'variables (array of objects with name and description), and examples (array of objects with input and output).',
				'Provide concise, actionable entries. Do not include markdown fences or explanatory text—return JSON only.',
			].join( '\n' );

			const userPrompt = [
				`Goal: ${ wizardForm.goal }`,
				`Audience: ${ wizardForm.audience || 'General' }`,
				`Tone: ${ wizardForm.tone || 'Balanced' }`,
				`Detail Level: ${ wizardForm.detail }`,
			].join( '\n' );

			apiFetch( {
				path: '/ai-persona/v1/generate',
				method: 'POST',
				data: {
					system_prompt: systemPrompt,
					prompt: userPrompt,
					context: {
						source: 'admin_prompt_wizard',
						detail: wizardForm.detail,
					},
				},
			} )
				.then( ( response ) => {
					if ( response && response.error ) {
						throw new Error( response.error );
					}

					let raw = response && response.output ? response.output : '';

					if ( raw && typeof raw === 'object' ) {
						raw = JSON.stringify( raw );
					}

					if ( typeof raw !== 'string' ) {
						throw new Error( __( 'Unexpected response from the provider.', 'ai-persona' ) );
					}

					let trimmed = raw.trim();

					if ( trimmed.startsWith( '```' ) ) {
						trimmed = trimmed.replace( /^```json/gi, '' ).replace( /```$/g, '' ).trim();
					}

					let parsed;

					try {
						parsed = trimmed ? JSON.parse( trimmed ) : null;
					} catch ( parseError ) {
						throw new Error( __( 'The assistant response was not valid JSON. Adjust the prompt and try again.', 'ai-persona' ) );
					}

					if ( ! parsed || 'object' !== typeof parsed ) {
						throw new Error( __( 'The assistant did not return structured suggestions.', 'ai-persona' ) );
					}

					const normalised = normaliseWizardSuggestions( parsed );

					if ( ! normalised.role && ! normalised.guidelines.length && ! normalised.constraints.length ) {
						throw new Error( __( 'The assistant response was empty. Refine your request and try again.', 'ai-persona' ) );
					}

					setWizardSuggestions( normalised );
				} )
				.catch( ( error ) => {
					const message = error && error.message ? error.message : __( 'Unable to generate suggestions. Please try again.', 'ai-persona' );
					setWizardError( message );
				} )
				.finally( () => {
					setWizardLoading( false );
				} );
		};

		const applyWizardSuggestions = () => {
			if ( ! wizardSuggestions ) {
				return;
			}

			setState( ( current ) => {
				const next = { ...current };

				if ( wizardSuggestions.role ) {
					next.role = wizardSuggestions.role;
				}

				if ( wizardSuggestions.guidelines.length ) {
					next.guidelines = hydrateList( wizardSuggestions.guidelines );
				}

				if ( wizardSuggestions.constraints.length ) {
					next.constraints = hydrateList( wizardSuggestions.constraints );
				}

				if ( wizardSuggestions.variables.length ) {
					next.variables = hydrateVariables( wizardSuggestions.variables );
				}

				if ( wizardSuggestions.examples.length ) {
					next.examples = hydrateExamples( wizardSuggestions.examples );
				}

				return next;
			} );

			closeWizard();
		};

		const renderWizardModal = () => {
			if ( ! wizardOpen || ! Modal ) {
				return null;
			}

			const detailOptions = [
				{ label: __( 'Concise', 'ai-persona' ), value: 'concise' },
				{ label: __( 'Balanced', 'ai-persona' ), value: 'balanced' },
				{ label: __( 'Comprehensive', 'ai-persona' ), value: 'comprehensive' },
			];

			return el(
				Modal,
				{
					title: __( 'AI-assisted prompt refinement', 'ai-persona' ),
					onRequestClose: closeWizard,
					className: 'ai-persona-wizard__modal',
				},
				el(
					'div',
					{ className: 'ai-persona-wizard__body' },
					el(
						'p',
						{ className: 'ai-persona-wizard__intro' },
						__( 'Describe the persona goals and let the assistant propose role, guidelines, and guardrails.', 'ai-persona' )
					),
					el( TextareaControl, {
						label: __( 'Persona goal', 'ai-persona' ),
						value: wizardForm.goal,
						onChange: ( value ) => updateWizardForm( 'goal', value ),
						rows: 3,
						help: __( 'Explain what this persona should achieve or the scenario it supports.', 'ai-persona' ),
					} ),
					el( TextareaControl, {
						label: __( 'Primary audience or context', 'ai-persona' ),
						value: wizardForm.audience,
						onChange: ( value ) => updateWizardForm( 'audience', value ),
						rows: 2,
					} ),
					el( TextControl, {
						label: __( 'Voice and tone preferences', 'ai-persona' ),
						value: wizardForm.tone,
						onChange: ( value ) => updateWizardForm( 'tone', value ),
						help: __( 'Examples: Energetic coach, calm expert, compliance-focused.', 'ai-persona' ),
					} ),
					SelectControl
						? el( SelectControl, {
								label: __( 'Detail level', 'ai-persona' ),
								value: wizardForm.detail,
								options: detailOptions,
								onChange: ( value ) => updateWizardForm( 'detail', value ),
						  } )
						: null,
					wizardError
						? el(
								Notice,
								{ status: 'error', isDismissible: false },
								el( 'p', null, wizardError )
						  )
						: null,
					el(
						'div',
						{ className: 'ai-persona-wizard__actions' },
						el(
							Button,
							{
								variant: 'primary',
								onClick: handleWizardGenerate,
								isBusy: wizardLoading,
								disabled: wizardLoading || ! canUseWizard,
							},
							wizardLoading ? __( 'Generating…', 'ai-persona' ) : __( 'Generate suggestions', 'ai-persona' )
						),
						el(
							Button,
							{
								variant: 'secondary',
								onClick: () => {
									setWizardSuggestions( null );
									setWizardError( '' );
								},
								disabled: wizardLoading,
							},
							__( 'Clear results', 'ai-persona' )
						)
					),
					wizardLoading
						? el(
								'div',
								{ className: 'ai-persona-wizard__status' },
								Spinner ? el( Spinner, null ) : null,
								el( 'span', null, __( 'Working with the assistant…', 'ai-persona' ) )
						  )
						: null,
					wizardSuggestions
						? el(
								'div',
								{ className: 'ai-persona-wizard__preview' },
								wizardSuggestions.role
									? el(
											'div',
											{ className: 'ai-persona-wizard__preview-section' },
											el( 'h4', null, __( 'Suggested role', 'ai-persona' ) ),
											el( 'p', null, wizardSuggestions.role )
									  )
									: null,
								wizardSuggestions.guidelines.length
									? el(
											'div',
											{ className: 'ai-persona-wizard__preview-section' },
											el( 'h4', null, __( 'Guidelines', 'ai-persona' ) ),
											el(
												'ul',
												null,
												wizardSuggestions.guidelines.map( ( item, index ) =>
													el( 'li', { key: `wizard-guideline-${ index }` }, item )
												)
											)
									  )
									: null,
								wizardSuggestions.constraints.length
									? el(
											'div',
											{ className: 'ai-persona-wizard__preview-section' },
											el( 'h4', null, __( 'Constraints', 'ai-persona' ) ),
											el(
												'ul',
												null,
												wizardSuggestions.constraints.map( ( item, index ) =>
													el( 'li', { key: `wizard-constraint-${ index }` }, item )
												)
											)
									  )
									: null,
								wizardSuggestions.variables.length
									? el(
											'div',
											{ className: 'ai-persona-wizard__preview-section' },
											el( 'h4', null, __( 'Variables', 'ai-persona' ) ),
											el(
												'ul',
												null,
												wizardSuggestions.variables.map( ( variable, index ) =>
													el(
														'li',
														{ key: `wizard-variable-${ index }` },
														el( 'strong', null, `{{${ variable.name }}}` ),
														variable.description ? ` – ${ variable.description }` : ''
													)
												)
											)
									  )
									: null,
								wizardSuggestions.examples.length
									? el(
											'div',
											{ className: 'ai-persona-wizard__preview-section' },
											el( 'h4', null, __( 'Examples', 'ai-persona' ) ),
											wizardSuggestions.examples.map( ( example, index ) =>
												el(
													'div',
													{ key: `wizard-example-${ index }`, className: 'ai-persona-wizard__preview-example' },
													el(
														'p',
														null,
														el( 'strong', null, __( 'User', 'ai-persona' ) ),
														': ',
														example.input || __( 'n/a', 'ai-persona' )
													),
													el(
														'p',
														null,
														el( 'strong', null, __( 'Assistant', 'ai-persona' ) ),
														': ',
														example.output || __( 'n/a', 'ai-persona' )
													)
												)
											)
									  )
									: null
						  )
						: null,
					el(
						'div',
						{ className: 'ai-persona-wizard__footer' },
						el(
							Button,
							{
								variant: 'secondary',
								onClick: closeWizard,
							},
							__( 'Close', 'ai-persona' )
						),
						el(
							Button,
							{
								variant: 'primary',
								onClick: applyWizardSuggestions,
								disabled: ! wizardSuggestions,
							},
							__( 'Apply to persona', 'ai-persona' )
						)
					)
				)
			);
		};

		return el(
			Fragment,
			null,
			renderWizardModal(),
			el(
				'div',
				{ className: 'ai-persona-builder' },
				errors.length > 0
					? el(
							Notice,
							{ status: 'warning', isDismissible: false },
							errors.map( ( message, index ) => el( 'p', { key: `error-${ index }` }, message ) )
					  )
					: null,
				el(
					'div',
					{ className: 'ai-persona-builder__toolbar' },
					el(
						Button,
						{
							variant: 'secondary',
							onClick: openWizard,
							disabled: ! canUseWizard,
						},
						__( 'Refine with AI assistant', 'ai-persona' )
					),
					el(
						'span',
						{ className: 'ai-persona-builder__toolbar-hint' },
						canUseWizard
							? __( 'Generate starter guidelines from natural language goals.', 'ai-persona' )
							: __( 'wp.apiFetch unavailable on this screen; wizard disabled.', 'ai-persona' )
					)
				),
				el(
					Card,
					{ className: 'ai-persona-builder__section' },
					el( CardHeader, null, __( 'Role', 'ai-persona' ) ),
					el(
						CardBody,
						null,
						el( TextareaControl, {
							label: __( 'Persona role and scope', 'ai-persona' ),
							value: state.role,
							onChange: ( value ) => setState( ( current ) => ( { ...current, role: value } ) ),
							help: __( 'Explain who the persona is, their expertise, and how they should think.', 'ai-persona' ),
							rows: 4,
						} )
					)
				),
				el(
					'div',
					{ className: 'ai-persona-builder__lists' },
					el(
						Card,
						{ className: 'ai-persona-builder__section' },
						el( CardHeader, null, __( 'Guidelines', 'ai-persona' ) ),
						el(
							CardBody,
							null,
							state.guidelines.map( ( item ) =>
								el(
									'div',
									{ className: 'ai-persona-builder__list-item', key: item.id },
									el( TextControl, {
										label: __( 'Guideline', 'ai-persona' ),
										value: item.value,
										onChange: ( value ) => updateListItem( 'guidelines', item.id, value ),
									} ),
									el(
										Button,
										{
											variant: 'secondary',
											isDestructive: true,
											onClick: () => removeListItem( 'guidelines', item.id ),
										},
										__( 'Remove', 'ai-persona' )
									)
								)
							),
							el(
								Button,
								{ variant: 'primary', onClick: () => addListItem( 'guidelines' ) },
								__( 'Add guideline', 'ai-persona' )
							)
						)
					),
					el(
						Card,
						{ className: 'ai-persona-builder__section' },
						el( CardHeader, null, __( 'Constraints', 'ai-persona' ) ),
						el(
							CardBody,
							null,
							state.constraints.map( ( item ) =>
								el(
									'div',
									{ className: 'ai-persona-builder__list-item', key: item.id },
									el( TextControl, {
										label: __( 'Constraint', 'ai-persona' ),
										value: item.value,
										onChange: ( value ) => updateListItem( 'constraints', item.id, value ),
									} ),
									el(
										Button,
										{
											variant: 'secondary',
											isDestructive: true,
											onClick: () => removeListItem( 'constraints', item.id ),
										},
										__( 'Remove', 'ai-persona' )
									)
								)
							),
							el(
								Button,
								{ variant: 'primary', onClick: () => addListItem( 'constraints' ) },
								__( 'Add constraint', 'ai-persona' )
							)
						)
					)
				),
				el(
					Card,
					{ className: 'ai-persona-builder__section' },
					el( CardHeader, null, __( 'Variables', 'ai-persona' ) ),
					el(
						CardBody,
						null,
						state.variables.map( ( variable ) =>
							el(
								'div',
								{ className: 'ai-persona-builder__list-item', key: variable.id },
								el( TextControl, {
									label: __( 'Token', 'ai-persona' ),
									value: variable.name,
									onChange: ( value ) => updateVariable( variable.id, 'name', value ),
									help: __( 'Use lowercase with underscores (example: customer_name).', 'ai-persona' ),
								} ),
								el( TextControl, {
									label: __( 'Description', 'ai-persona' ),
									value: variable.description,
									onChange: ( value ) => updateVariable( variable.id, 'description', value ),
								} ),
								el(
									Button,
									{
										variant: 'secondary',
										isDestructive: true,
										onClick: () => removeVariable( variable.id ),
									},
									__( 'Remove', 'ai-persona' )
								)
							)
						),
						el( Button, { variant: 'primary', onClick: addVariable }, __( 'Add variable', 'ai-persona' ) )
					)
				),
				el(
					Card,
					{ className: 'ai-persona-builder__section' },
					el( CardHeader, null, __( 'Examples', 'ai-persona' ) ),
					el(
						CardBody,
						null,
						state.examples.map( ( example ) =>
							el(
								'div',
								{ key: example.id, style: { marginBottom: '1rem' } },
								el( TextareaControl, {
									label: __( 'User input', 'ai-persona' ),
									value: example.input,
									onChange: ( value ) => updateExample( example.id, 'input', value ),
									rows: 3,
								} ),
								el( TextareaControl, {
									label: __( 'Assistant reply', 'ai-persona' ),
									value: example.output,
									onChange: ( value ) => updateExample( example.id, 'output', value ),
									rows: 3,
								} ),
								el(
									Button,
									{
										variant: 'secondary',
										isDestructive: true,
										onClick: () => removeExample( example.id ),
									},
									__( 'Remove example', 'ai-persona' )
								)
							)
						),
						el( Button, { variant: 'primary', onClick: addExample }, __( 'Add example', 'ai-persona' ) )
					)
				),
				el(
					Card,
					{ className: 'ai-persona-builder__section' },
					el( CardHeader, null, __( 'Prompt preview', 'ai-persona' ) ),
					el(
						CardBody,
						null,
						el(
							'div',
							{ className: 'ai-persona-builder__preview' },
							preview
								? preview
								: el(
										'span',
										{ className: 'ai-persona-builder__empty' },
										__( 'Complete the sections above to preview the compiled persona prompt.', 'ai-persona' )
								  )
						)
					)
				)
			)
		);
	};

	render( el( Builder ), root );
} )();
