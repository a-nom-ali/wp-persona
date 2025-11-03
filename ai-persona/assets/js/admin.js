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
	} = wp.components;

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

		return el(
			'div',
			{ className: 'ai-persona-builder' },
			errors.length > 0
				? el(
					Notice,
					{ status: 'warning', isDismissible: false },
					errors.map( ( message, index ) => el( 'p', { key: `error-${ index }` }, message ) )
				)
				: null,
			sel(
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
			sel(
				'div',
				{ className: 'ai-persona-builder__lists' },
				sel(
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
								el( Button, {
									variant: 'secondary',
									isDestructive: true,
									onClick: () => removeListItem( 'guidelines', item.id ),
								}, __( 'Remove', 'ai-persona' ) )
							)
						),
						el( Button, { variant: 'primary', onClick: () => addListItem( 'guidelines' ) }, __( 'Add guideline', 'ai-persona' ) )
					)
				),
				sel(
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
								el( Button, {
									variant: 'secondary',
									isDestructive: true,
									onClick: () => removeListItem( 'constraints', item.id ),
								}, __( 'Remove', 'ai-persona' ) )
							)
						),
						el( Button, { variant: 'primary', onClick: () => addListItem( 'constraints' ) }, __( 'Add constraint', 'ai-persona' ) )
					)
				)
			),
			sel(
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
							el( Button, {
								variant: 'secondary',
								isDestructive: true,
								onClick: () => removeVariable( variable.id ),
							}, __( 'Remove', 'ai-persona' ) )
						)
					),
					el( Button, { variant: 'primary', onClick: addVariable }, __( 'Add variable', 'ai-persona' ) )
				)
			),
			sel(
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
							el( Button, {
								variant: 'secondary',
								isDestructive: true,
								onClick: () => removeExample( example.id ),
							}, __( 'Remove example', 'ai-persona' ) )
						)
					),
					el( Button, { variant: 'primary', onClick: addExample }, __( 'Add example', 'ai-persona' ) )
				)
			),
			sel(
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
							: el( 'span', { className: 'ai-persona-builder__empty' }, __( 'Complete the sections above to preview the compiled persona prompt.', 'ai-persona' ) )
					)
				)
			)
		);
	};

	render( el( Builder ), root );
} )();
