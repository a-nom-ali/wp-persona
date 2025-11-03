<?php

namespace Ai_Persona\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register shortcode for embedding the chat widget.
 */
function register_shortcode() {
	add_shortcode( 'ai_persona_chat', __NAMESPACE__ . '\\render_chat_widget' );
}
add_action( 'init', __NAMESPACE__ . '\\register_shortcode' );

/**
 * Render the chat widget container.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function render_chat_widget( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'ai_persona_chat'
	);

	/**
	 * Allow consumers to adjust chat container attributes.
	 *
	 * @param array $atts Parsed shortcode attributes.
	 */
	$atts = apply_filters( 'ai_persona_chat_attributes', $atts );

	ob_start();
	?>
	<div class="ai-persona-chat" data-persona-id="<?php echo esc_attr( $atts['id'] ); ?>"></div>
	<?php

	return ob_get_clean();
}

/**
 * Server-side render callback for the Gutenberg block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function render_chat_block( $attributes ) {
	$persona_id = isset( $attributes['personaId'] ) ? absint( $attributes['personaId'] ) : 0;

	return render_chat_widget(
		array(
			'id' => $persona_id,
		)
	);
}
