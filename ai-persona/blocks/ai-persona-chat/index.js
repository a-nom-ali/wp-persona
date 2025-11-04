( () => {
	const { registerBlockType } = wp.blocks;
	const { InspectorControls } = wp.blockEditor || wp.editor;
	const { useSelect } = wp.data;
	const { PanelBody, SelectControl, ToggleControl, TextControl, Spinner } = wp.components;
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
		},
		edit: ( props ) => {
			const { attributes, setAttributes } = props;
			const { personaId = 0, showHeader = true, headerTitle = __( 'Chat with persona', 'ai-persona' ) } = attributes;

			const personas = useSelect(
				( select ) =>
					select( 'core' ).getEntityRecords( 'postType', 'ai_persona', {
						per_page: -1,
						context: 'view',
					} ),
				[]
			);

			const personaOptions = useMemo( () => {
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

			const selectedPersona = personaOptions.find( ( option ) => option.value === personaId );

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
								options: personaOptions,
								onChange: ( value ) => setAttributes( { personaId: parseInt( value, 10 ) || 0 } ),
								help: __( 'Pick which persona powers this chat block.', 'ai-persona' ),
							} )
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
				)
				),
				el(
				'div',
				{ className: 'ai-persona-chat-placeholder' },
				el( 'p', {},
				selectedPersona?.label
					? sprintf( __( 'AI Persona Chat (%s)', 'ai-persona' ), selectedPersona.label )
					: __( 'AI Persona Chat will render on the front end.', 'ai-persona' )
			),
				el( 'p', {},
				showHeader
					? sprintf( __( 'Header: “%s”', 'ai-persona' ), headerTitle )
					: __( 'Header hidden', 'ai-persona' )
			)
			)
			);
		},
		save: () => null,
	} );
} )();
