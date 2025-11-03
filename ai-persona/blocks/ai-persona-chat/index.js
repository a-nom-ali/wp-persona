import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	TextControl,
	Spinner,
} from '@wordpress/components';
import { useMemo } from '@wordpress/element';

registerBlockType( 'ai-persona/chat', {
	edit: ( { attributes, setAttributes } ) => {
		const {
			personaId = 0,
			showHeader = true,
			headerTitle = 'Chat with persona',
		} = attributes;

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
					label: 'Select a persona',
					value: 0,
				},
			];

			if ( Array.isArray( personas ) ) {
				personas.forEach( ( persona ) => {
					options.push( {
						label: persona.title?.rendered || `Persona #${ persona.id }`,
						value: persona.id,
					} );
				} );
			}

			return options;
		}, [ personas ] );

		const selectedPersona = personaOptions.find(
			( option ) => option.value === personaId
		);

		return (
			<>
				<InspectorControls>
					<PanelBody title="Persona" initialOpen>
						{ Array.isArray( personas ) ? (
							<SelectControl
								label="Persona"
								value={ personaId }
								options={ personaOptions }
								onChange={ ( value ) =>
									setAttributes( {
										personaId: parseInt( value, 10 ) || 0,
									} )
								}
								help="Pick which persona powers this chat block."
							/>
						) : (
							<div className="ai-persona-chat__loading">
								<Spinner />
								<span>Loading personas…</span>
							</div>
						) }
					</PanelBody>
					<PanelBody title="Display" initialOpen={ false }>
						<ToggleControl
							label="Show header"
							checked={ !! showHeader }
							onChange={ ( value ) =>
								setAttributes( {
									showHeader: value,
								} )
							}
						/>
						{ showHeader && (
							<TextControl
								label="Header title"
								value={ headerTitle }
								onChange={ ( value ) =>
									setAttributes( {
										headerTitle: value,
									} )
								}
							/>
						) }
					</PanelBody>
				</InspectorControls>
				<div className="ai-persona-chat-placeholder">
					<p>
						{ selectedPersona?.label
							? `AI Persona Chat (${ selectedPersona.label })`
							: 'AI Persona Chat will render on the front end.' }
					</p>
					<p>
						{ showHeader
							? `Header: “${ headerTitle }”`
							: 'Header hidden' }
					</p>
				</div>
			</>
		);
	},
	save: () => null,
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
			default: 'Chat with persona',
		},
	},
} );
