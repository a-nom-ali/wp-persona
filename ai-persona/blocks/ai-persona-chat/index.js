import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';

registerBlockType( 'ai-persona/chat', {
	edit: ( { attributes, setAttributes } ) => {
		const { personaId = 0 } = attributes;

		return (
			<>
				<InspectorControls>
					<PanelBody title="Persona">
						<TextControl
							label="Persona ID"
							type="number"
							min={ 0 }
							value={ personaId }
							onChange={ ( value ) =>
								setAttributes( { personaId: parseInt( value, 10 ) || 0 } )
							}
						/>
					</PanelBody>
				</InspectorControls>
				<div className="ai-persona-chat-placeholder">
					<p>AI Persona Chat will render on the front end.</p>
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
	},
} );
