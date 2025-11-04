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
			'id'               => 0,
			'show_header'      => true,
			'header_title'     => __( 'Chat with persona', 'ai-persona' ),
			'primary_color'    => '',
			'background_color' => '',
			'text_color'       => '',
			'border_radius'    => 0,
			'max_width'        => '',
			'font_size'        => '',
			'persona_options'  => array(),
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

	$attr_show_header = $atts['show_header'] ? 'true' : 'false';
	$attr_header      = sanitize_text_field( $atts['header_title'] );

	// Build inline styles for custom styling
	$inline_styles = array();

	if ( ! empty( $atts['primary_color'] ) ) {
		$inline_styles[] = '--ai-persona-color-primary: ' . esc_attr( $atts['primary_color'] );
	}

	if ( ! empty( $atts['background_color'] ) ) {
		$inline_styles[] = '--ai-persona-color-surface: ' . esc_attr( $atts['background_color'] );
	}

	if ( ! empty( $atts['text_color'] ) ) {
		$inline_styles[] = '--ai-persona-color-text: ' . esc_attr( $atts['text_color'] );
	}

	if ( ! empty( $atts['border_radius'] ) && $atts['border_radius'] > 0 ) {
		$inline_styles[] = '--ai-persona-radius-base: ' . absint( $atts['border_radius'] ) . 'px';
	}

	if ( ! empty( $atts['max_width'] ) ) {
		$inline_styles[] = 'max-width: ' . esc_attr( $atts['max_width'] );
	}

	if ( ! empty( $atts['font_size'] ) ) {
		$inline_styles[] = '--ai-persona-font-size: ' . esc_attr( $atts['font_size'] );
	}

	$style_attribute = ! empty( $inline_styles ) ? ' style="' . implode( '; ', $inline_styles ) . '"' : '';

	ob_start();
	?>
	<?php
	$persona_options = array();
	$raw_persona_options = $atts['persona_options'];

	if ( is_string( $raw_persona_options ) && '' !== trim( $raw_persona_options ) ) {
		$decoded = json_decode( $raw_persona_options, true );
		if ( is_array( $decoded ) ) {
			$raw_persona_options = $decoded;
		}
	}

	if ( is_array( $raw_persona_options ) ) {
		foreach ( $raw_persona_options as $option ) {
			if ( is_numeric( $option ) ) {
				$option = array( 'id' => absint( $option ) );
			}

			if ( ! is_array( $option ) || empty( $option['id'] ) ) {
				continue;
			}

			$id    = absint( $option['id'] );
			$label = isset( $option['label'] ) ? sanitize_text_field( (string) $option['label'] ) : '';

			if ( 0 === $id ) {
				continue;
			}

			if ( '' === $label ) {
				$label = sprintf(
					/* translators: %d: Persona ID. */
					__( 'Persona #%d', 'ai-persona' ),
					$id
				);
			}

			$persona_options[] = array(
				'id'    => $id,
				'label' => $label,
			);
		}
	}

	$persona_dataset = $persona_options ? ' data-persona-options="' . esc_attr( wp_json_encode( $persona_options ) ) . '"' : '';
	$active_persona_id = absint( $atts['id'] );

	if ( $active_persona_id && ! array_filter(
		$persona_options,
		static function ( $option ) use ( $active_persona_id ) {
			return isset( $option['id'] ) && (int) $option['id'] === $active_persona_id;
		}
	) ) {
		$label = get_the_title( $active_persona_id );

		if ( '' === $label ) {
			$label = sprintf(
				/* translators: %d: Persona ID. */
				__( 'Persona #%d', 'ai-persona' ),
				$active_persona_id
			);
		}

		$persona_options[] = array(
			'id'    => $active_persona_id,
			'label' => sanitize_text_field( $label ),
		);

		$persona_dataset = ' data-persona-options="' . esc_attr( wp_json_encode( $persona_options ) ) . '"';
	}
	?>
	<div
		class="ai-persona-chat"
		data-persona-id="<?php echo esc_attr( $atts['id'] ); ?>"
		data-show-header="<?php echo esc_attr( $attr_show_header ); ?>"
		data-header-title="<?php echo esc_attr( $attr_header ); ?>"<?php echo $style_attribute; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $persona_dataset; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	>
		<noscript>
			<?php esc_html_e( 'Enable JavaScript to use the AI Persona chat widget.', 'ai-persona' ); ?>
		</noscript>
	</div>
	<?php

	$output = ob_get_clean();

	/**
	 * Filter the rendered chat widget markup.
	 *
	 * @param string $output Rendered HTML output.
	 * @param array  $atts   Parsed shortcode attributes.
	 */
	return apply_filters( 'ai_persona_chat_html', $output, $atts );
}

/**
 * Server-side render callback for the Gutenberg block.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function render_chat_block( $attributes ) {
	$persona_id = isset( $attributes['personaId'] ) ? absint( $attributes['personaId'] ) : 0;
	$show_header = isset( $attributes['showHeader'] ) ? (bool) $attributes['showHeader'] : true;
	$header_title = isset( $attributes['headerTitle'] ) ? (string) $attributes['headerTitle'] : __( 'Chat with persona', 'ai-persona' );

	// Extract styling attributes
	$primary_color = isset( $attributes['primaryColor'] ) ? sanitize_hex_color( $attributes['primaryColor'] ) : '';
	$background_color = isset( $attributes['backgroundColor'] ) ? sanitize_hex_color( $attributes['backgroundColor'] ) : '';
	$text_color = isset( $attributes['textColor'] ) ? sanitize_hex_color( $attributes['textColor'] ) : '';
	$border_radius = isset( $attributes['borderRadius'] ) ? absint( $attributes['borderRadius'] ) : 0;
	$max_width = isset( $attributes['maxWidth'] ) ? sanitize_text_field( $attributes['maxWidth'] ) : '';
	$font_size = isset( $attributes['fontSize'] ) ? sanitize_text_field( $attributes['fontSize'] ) : '';
	$persona_options_attr = array();

	if ( ! empty( $attributes['personaOptions'] ) && is_array( $attributes['personaOptions'] ) ) {
		foreach ( $attributes['personaOptions'] as $option ) {
			if ( is_numeric( $option ) ) {
				$option = array( 'id' => absint( $option ) );
			}

			if ( ! is_array( $option ) || empty( $option['id'] ) ) {
				continue;
			}

			$id = absint( $option['id'] );

			if ( ! $id ) {
				continue;
			}

			$label = '';

			if ( isset( $option['label'] ) && '' !== $option['label'] ) {
				$label = sanitize_text_field( (string) $option['label'] );
			} else {
				$post = get_post( $id );

				if ( $post ) {
					$label = get_the_title( $post );
				}

				if ( '' === $label ) {
					$label = sprintf(
						/* translators: %d: Persona ID. */
						__( 'Persona #%d', 'ai-persona' ),
						$id
					);
				}
			}

			$persona_options_attr[] = array(
				'id'    => $id,
				'label' => $label,
			);
		}
	}

	return render_chat_widget(
		array(
			'id'               => $persona_id,
			'show_header'      => $show_header,
			'header_title'     => $header_title,
			'primary_color'    => $primary_color,
			'background_color' => $background_color,
			'text_color'       => $text_color,
			'border_radius'    => $border_radius,
			'max_width'        => $max_width,
			'font_size'        => $font_size,
			'persona_options'  => $persona_options_attr,
		)
	);
}
