( () => {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls, PanelColorSettings } = wp.blockEditor || wp.editor;
	const { useSelect } = wp.data;
	const { PanelBody, SelectControl, ToggleControl, TextControl, Spinner, RangeControl, CheckboxControl } = wp.components;
	const { useMemo, Fragment, createElement: el } = wp.element;
	const { __, sprintf } = wp.i18n;

	registerBlockType( 'ai-persona/chat', {
		title: __( 'AI Persona Chat', 'ai-persona' ),
		icon: 'format-chat',
		category: 'widgets',
		attributes: {
			personaId: {
				type: 'number',
				default: 0,
			},
			showHeader: {
				type: 'boolean',
				default: true,
			},
			headerTitle: {
				type: 'string',
				default: __( 'Chat with persona', 'ai-persona' ),
			},
			primaryColor: {
				type: 'string',
				default: '',
			},
			backgroundColor: {
				type: 'string',
				default: '',
			},
			textColor: {
				type: 'string',
				default: '',
			},
			borderRadius: {
				type: 'number',
				default: 0,
			},
			maxWidth: {
				type: 'string',
				default: '',
			},
			fontSize: {
				type: 'string',
				default: '',
			},
			personaOptions: {
				type: 'array',
				default: [],
			},
		},
		edit: ( props ) => {
			const { attributes, setAttributes } = props;
			const {
				personaId = 0,
				showHeader = true,
				headerTitle = __( 'Chat with persona', 'ai-persona' ),
				primaryColor = '',
				backgroundColor = '',
				textColor = '',
				borderRadius = 0,
				maxWidth = '',
				fontSize = '',
				personaOptions: personaOptionsAttr = [],
			} = attributes;

			const personas = useSelect(
				( select ) =>
					select( 'core' ).getEntityRecords( 'postType', 'ai_persona', {
						per_page: -1,
						context: 'view',
					} ),
				[]
			);

			const personaSelectOptions = useMemo( () => {
				const options = [
					{
						label: __( 'Select a persona', 'ai-persona' ),
						value: 0,
					},
				];

				if ( Array.isArray( personas ) ) {
					personas.forEach( ( persona ) => {
						options.push( {
							label: persona?.title?.rendered || sprintf( __( 'Persona #%d', 'ai-persona' ), persona.id ),
							value: persona.id,
						} );
					} );
				}

				return options;
			}, [ personas ] );

			const personaSwitchChoices = useMemo( () => {
				if ( ! Array.isArray( personas ) ) {
					return [];
				}

				return personas.map( ( persona ) => ( {
					id: persona.id,
					label: persona?.title?.rendered || sprintf( __( 'Persona #%d', 'ai-persona' ), persona.id ),
				} ) );
			}, [ personas ] );

			const personaOptionsSelection = useMemo( () => {
				if ( ! Array.isArray( personaOptionsAttr ) ) {
					return [];
				}

				const labelLookup = new Map(
					personaSwitchChoices.map( ( option ) => [ option.id, option.label ] )
				);

				return personaOptionsAttr.reduce( ( carry, option ) => {
					let id = 0;
					let label = '';

					if ( typeof option === 'number' ) {
						id = option;
					} else if ( typeof option === 'string' ) {
						id = parseInt( option, 10 );
					} else if ( option && typeof option === 'object' ) {
						id = option.id || 0;
						label = option.label || '';
					}

					id = parseInt( id, 10 );

					if ( ! id || carry.some( ( entry ) => entry.id === id ) ) {
						return carry;
					}

					if ( ! label ) {
						label = labelLookup.get( id ) || sprintf( __( 'Persona #%d', 'ai-persona' ), id );
					}

					carry.push( {
						id,
						label,
					} );

					return carry;
				}, [] );
			}, [ personaOptionsAttr, personaSwitchChoices ] );

			const switcherEnabled = personaOptionsSelection.length > 0;

			const updatePersonaOptionsSelection = ( nextSelection ) => {
				const sanitized = Array.isArray( nextSelection )
					? nextSelection
						.filter( ( option ) => option && option.id )
						.map( ( option ) => ( {
							id: option.id,
							label: option.label || sprintf( __( 'Persona #%d', 'ai-persona' ), option.id ),
						} ) )
						.sort( ( a, b ) => a.label.localeCompare( b.label ) )
					: [];

				setAttributes( { personaOptions: sanitized } );
			};

			const handleTogglePersonaOption = ( option, isChecked ) => {
				const current = personaOptionsSelection || [];
				let next = current.filter( ( entry ) => entry.id !== option.id );

				if ( isChecked ) {
					next = next.concat( option );
				}

				updatePersonaOptionsSelection( next );
			};

			const handleEnableSwitcher = ( value ) => {
				if ( value ) {
					const defaultOption =
						personaSwitchChoices.find( ( option ) => option.id === personaId ) ||
						personaSwitchChoices[0];

					if ( defaultOption ) {
						updatePersonaOptionsSelection( [ defaultOption ] );
					}
				} else {
					updatePersonaOptionsSelection( [] );
				}
			};

			const selectedPersona = personaSelectOptions.find( ( option ) => option.value === personaId );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Persona', 'ai-persona' ), initialOpen: true },
						Array.isArray( personas )
							? el( SelectControl, {
								label: __( 'Persona', 'ai-persona' ),
								value: personaId,
								options: personaSelectOptions,
								onChange: ( value ) => setAttributes( { personaId: parseInt( value, 10 ) || 0 } ),
								help: __( 'Pick which persona powers this chat block.', 'ai-persona' ),
							} )
							: el( 'div', { className: 'ai-persona-chat__loading' }, el( Spinner, {} ), el( 'span', {}, __( 'Loading personas…', 'ai-persona' ) ) )
					),
					el(
						PanelBody,
						{ title: __( 'Persona Switcher', 'ai-persona' ), initialOpen: false },
						Array.isArray( personas )
							? el(
								Fragment,
								{},
								el( ToggleControl, {
									label: __( 'Enable persona switcher', 'ai-persona' ),
									checked: switcherEnabled,
									onChange: ( value ) => handleEnableSwitcher( value ),
									help: __( 'Allow visitors to pick from multiple personas in the chat widget.', 'ai-persona' ),
								} ),
								switcherEnabled
									? (
										personaSwitchChoices.length > 0
											? personaSwitchChoices.map( ( option ) =>
												el( CheckboxControl, {
													key: option.id,
													label: option.label,
													checked: personaOptionsSelection.some( ( entry ) => entry.id === option.id ),
													onChange: ( isChecked ) => handleTogglePersonaOption( option, isChecked ),
												} )
											)
											: el( 'p', { className: 'components-help-text' }, __( 'Create personas to populate the switcher.', 'ai-persona' ) )
									)
									: null
							)
							: el( 'div', { className: 'ai-persona-chat__loading' }, el( Spinner, {} ), el( 'span', {}, __( 'Loading personas…', 'ai-persona' ) ) )
					),
					el(
						PanelBody,
						{ title: __( 'Display', 'ai-persona' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show header', 'ai-persona' ),
							checked: !! showHeader,
							onChange: ( value ) => setAttributes( { showHeader: value } ),
					} ),
						showHeader
							? el( TextControl, {
								label: __( 'Header title', 'ai-persona' ),
								value: headerTitle,
								onChange: ( value ) => setAttributes( { headerTitle: value } ),
							} )
							: null
					),
					el( PanelColorSettings, {
						title: __( 'Color Settings', 'ai-persona' ),
						initialOpen: false,
						colorSettings: [
							{
								value: primaryColor,
								onChange: ( value ) => setAttributes( { primaryColor: value } ),
								label: __( 'Primary Color', 'ai-persona' ),
							},
							{
								value: backgroundColor,
								onChange: ( value ) => setAttributes( { backgroundColor: value } ),
								label: __( 'Background Color', 'ai-persona' ),
							},
							{
								value: textColor,
								onChange: ( value ) => setAttributes( { textColor: value } ),
								label: __( 'Text Color', 'ai-persona' ),
							},
						],
					} ),
					el(
						PanelBody,
						{ title: __( 'Styling', 'ai-persona' ), initialOpen: false },
						el( RangeControl, {
							label: __( 'Border Radius (px)', 'ai-persona' ),
							value: borderRadius,
							onChange: ( value ) => setAttributes( { borderRadius: value } ),
							min: 0,
							max: 50,
							help: __( 'Set to 0 to use default theme radius', 'ai-persona' ),
						} ),
						el( TextControl, {
							label: __( 'Max Width', 'ai-persona' ),
							value: maxWidth,
							onChange: ( value ) => setAttributes( { maxWidth: value } ),
							help: __( 'e.g., 600px, 100%, or leave empty for full width', 'ai-persona' ),
						} ),
						el( TextControl, {
							label: __( 'Font Size', 'ai-persona' ),
							value: fontSize,
							onChange: ( value ) => setAttributes( { fontSize: value } ),
							help: __( 'e.g., 16px, 1rem, or leave empty for default', 'ai-persona' ),
						} )
					)
				),
				el(
					'div',
					{ className: 'ai-persona-chat-placeholder' },
					el(
						'p',
						{},
						selectedPersona?.label
							? sprintf( __( 'AI Persona Chat (%s)', 'ai-persona' ), selectedPersona.label )
							: __( 'AI Persona Chat will render on the front end.', 'ai-persona' )
					),
					el(
						'p',
						{},
						showHeader
							? sprintf( __( 'Header: “%s”', 'ai-persona' ), headerTitle )
							: __( 'Header hidden', 'ai-persona' )
					),
					switcherEnabled
						? el( 'p', {}, sprintf( __( 'Switcher exposes %d persona(s).', 'ai-persona' ), personaOptionsSelection.length ) )
						: null
				)
			);
		},
		save: () => null,
	} );
} )();
