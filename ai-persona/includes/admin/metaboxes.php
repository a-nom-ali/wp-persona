<?php

namespace Ai_Persona\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register persona metaboxes.
 */
function register_metaboxes() {
	add_meta_box(
		'ai-persona-structure',
		__( 'Persona Structure', 'ai-persona' ),
		__NAMESPACE__ . '\\render_metabox',
		'ai_persona',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes_ai_persona', __NAMESPACE__ . '\\register_metaboxes' );

/**
 * Render the metabox UI.
 *
 * @param \WP_Post $post Current post object.
 */
function render_metabox( $post ) {
	wp_nonce_field( 'ai_persona_metabox', 'ai_persona_metabox_nonce' );

	$fields = array(
		'role'       => __( 'Role', 'ai-persona' ),
		'guidelines' => __( 'Guidelines', 'ai-persona' ),
		'constraints'=> __( 'Constraints', 'ai-persona' ),
		'examples'   => __( 'Examples', 'ai-persona' ),
	);

	foreach ( $fields as $key => $label ) {
		$value = get_post_meta( $post->ID, "ai_persona_{$key}", true );
		?>
		<p>
			<label for="ai-persona-<?php echo esc_attr( $key ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>
			<textarea id="ai-persona-<?php echo esc_attr( $key ); ?>" name="ai_persona[<?php echo esc_attr( $key ); ?>]" rows="4" class="widefat"><?php echo esc_textarea( $value ); ?></textarea>
		</p>
		<?php
	}
}

/**
 * Persist metabox values.
 *
 * @param int $post_id Post ID.
 */
function save_metabox( $post_id ) {
	if ( ! isset( $_POST['ai_persona_metabox_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['ai_persona_metabox_nonce'] ), 'ai_persona_metabox' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = isset( $_POST['ai_persona'] ) ? wp_unslash( $_POST['ai_persona'] ) : array();

	foreach ( $fields as $key => $value ) {
		update_post_meta( $post_id, "ai_persona_{$key}", sanitize_textarea_field( $value ) );
	}
}
add_action( 'save_post_ai_persona', __NAMESPACE__ . '\\save_metabox' );
