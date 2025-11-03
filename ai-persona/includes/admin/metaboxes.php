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

	$role        = (string) get_post_meta( $post->ID, 'ai_persona_role', true );
	$guidelines  = get_post_meta( $post->ID, 'ai_persona_guidelines', true );
	$constraints = get_post_meta( $post->ID, 'ai_persona_constraints', true );
	$examples    = get_post_meta( $post->ID, 'ai_persona_examples', true );
	$variables   = get_post_meta( $post->ID, 'ai_persona_variables', true );

	if ( is_string( $guidelines ) && $guidelines ) {
		$guidelines = preg_split( '/\r\n|\r|\n/', $guidelines );
	}

	if ( is_string( $constraints ) && $constraints ) {
		$constraints = preg_split( '/\r\n|\r|\n/', $constraints );
	}

	if ( empty( $guidelines ) ) {
		$guidelines = array();
	}

	if ( empty( $constraints ) ) {
		$constraints = array();
	}

	if ( empty( $examples ) || ! is_array( $examples ) ) {
		$examples = array();
	}

	$examples = array_map(
		static function ( $example ) {
			if ( ! is_array( $example ) ) {
				return array(
					'input'  => (string) $example,
					'output' => '',
				);
			}

			return array(
				'input'  => isset( $example['input'] ) ? (string) $example['input'] : '',
				'output' => isset( $example['output'] ) ? (string) $example['output'] : '',
			);
		},
		$examples
	);

	if ( empty( $variables ) || ! is_array( $variables ) ) {
		$variables = array();
	}

	$variables = array_map(
		static function ( $variable ) {
			if ( ! is_array( $variable ) ) {
				return array(
					'name'        => (string) $variable,
					'description' => '',
				);
			}

			return array(
				'name'        => isset( $variable['name'] ) ? (string) $variable['name'] : '',
				'description' => isset( $variable['description'] ) ? (string) $variable['description'] : '',
			);
		},
		$variables
	);

	$initial_state = array(
		'role'        => $role,
		'guidelines'  => array_values( $guidelines ),
		'constraints' => array_values( $constraints ),
		'examples'    => array_values( $examples ),
		'variables'   => array_values( $variables ),
	);

	$encoded_state = wp_json_encode( $initial_state );
	?>
	<div id="ai-persona-builder" data-initial-state="<?php echo esc_attr( $encoded_state ); ?>">
		<p><?php esc_html_e( 'Loading persona builder…', 'ai-persona' ); ?></p>
	</div>
	<input type="hidden" id="ai-persona-payload" name="ai_persona_payload" value="<?php echo esc_attr( $encoded_state ); ?>" />
	<noscript>
		<p><?php esc_html_e( 'JavaScript is disabled. Use the fallback fields below.', 'ai-persona' ); ?></p>
		<p>
			<label for="ai-persona-role-noscript"><strong><?php esc_html_e( 'Role', 'ai-persona' ); ?></strong></label>
			<textarea id="ai-persona-role-noscript" name="ai_persona_noscript[role]" rows="4" class="widefat"><?php echo esc_textarea( $role ); ?></textarea>
		</p>
		<p>
			<label for="ai-persona-guidelines-noscript"><strong><?php esc_html_e( 'Guidelines (one per line)', 'ai-persona' ); ?></strong></label>
			<textarea id="ai-persona-guidelines-noscript" name="ai_persona_noscript[guidelines]" rows="4" class="widefat"><?php echo esc_textarea( implode( "\n", $guidelines ) ); ?></textarea>
		</p>
		<p>
			<label for="ai-persona-constraints-noscript"><strong><?php esc_html_e( 'Constraints (one per line)', 'ai-persona' ); ?></strong></label>
			<textarea id="ai-persona-constraints-noscript" name="ai_persona_noscript[constraints]" rows="4" class="widefat"><?php echo esc_textarea( implode( "\n", $constraints ) ); ?></textarea>
		</p>
		<p>
			<label for="ai-persona-examples-noscript"><strong><?php esc_html_e( 'Examples', 'ai-persona' ); ?></strong></label>
			<textarea id="ai-persona-examples-noscript" name="ai_persona_noscript[examples]" rows="4" class="widefat"><?php echo esc_textarea( wp_json_encode( $examples ) ); ?></textarea>
		</p>
		<p>
			<label for="ai-persona-variables-noscript"><strong><?php esc_html_e( 'Variables (key => description per line)', 'ai-persona' ); ?></strong></label>
			<textarea id="ai-persona-variables-noscript" name="ai_persona_noscript[variables]" rows="4" class="widefat"><?php
			foreach ( $variables as $variable ) {
				echo esc_textarea( $variable['name'] . ' => ' . $variable['description'] . "\n" );
			}
			?></textarea>
		</p>
	</noscript>

	<?php if ( $post->ID ) :
		$export_url = add_query_arg(
			array( '_wpnonce' => wp_create_nonce( 'wp_rest' ) ),
			rest_url( 'ai-persona/v1/persona/' . $post->ID )
		);
		?>
		<p class="ai-persona-export">
			<a class="button button-secondary" href="<?php echo esc_url( $export_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Export Persona JSON', 'ai-persona' ); ?>
			</a>
		</p>
	<?php endif; ?>
	<?php
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

	$payload_raw = isset( $_POST['ai_persona_payload'] ) ? wp_unslash( $_POST['ai_persona_payload'] ) : '';
	$payload     = json_decode( $payload_raw, true );

	if ( ! is_array( $payload ) ) {
		$payload = array();

		if ( isset( $_POST['ai_persona_noscript'] ) && is_array( $_POST['ai_persona_noscript'] ) ) {
			$noscript = wp_unslash( $_POST['ai_persona_noscript'] );

			$payload['role']        = isset( $noscript['role'] ) ? (string) $noscript['role'] : '';
			$payload['guidelines']  = isset( $noscript['guidelines'] ) ? preg_split( '/\r\n|\r|\n/', (string) $noscript['guidelines'] ) : array();
			$payload['constraints'] = isset( $noscript['constraints'] ) ? preg_split( '/\r\n|\r|\n/', (string) $noscript['constraints'] ) : array();
			$payload['examples']    = isset( $noscript['examples'] ) ? json_decode( (string) $noscript['examples'], true ) : array();

			$variables = array();

			if ( ! empty( $noscript['variables'] ) ) {
				$lines = preg_split( '/\r\n|\r|\n/', (string) $noscript['variables'] );
				foreach ( $lines as $line ) {
					if ( strpos( $line, '=>' ) !== false ) {
						list( $name, $description ) = array_map( 'trim', explode( '=>', $line, 2 ) );
						$variables[] = array(
							'name'        => $name,
							'description' => $description,
						);
					}
				}
			}

			$payload['variables'] = $variables;
		}
	}

	$role = isset( $payload['role'] ) ? sanitize_textarea_field( $payload['role'] ) : '';

	$guidelines = array();
	if ( ! empty( $payload['guidelines'] ) && is_array( $payload['guidelines'] ) ) {
		foreach ( $payload['guidelines'] as $item ) {
			$item = sanitize_text_field( $item );
			if ( '' !== $item ) {
				$guidelines[] = $item;
			}
		}
	}

	$constraints = array();
	if ( ! empty( $payload['constraints'] ) && is_array( $payload['constraints'] ) ) {
		foreach ( $payload['constraints'] as $item ) {
			$item = sanitize_text_field( $item );
			if ( '' !== $item ) {
				$constraints[] = $item;
			}
		}
	}

	$examples = array();
	if ( ! empty( $payload['examples'] ) && is_array( $payload['examples'] ) ) {
		foreach ( $payload['examples'] as $example ) {
			if ( ! is_array( $example ) ) {
				continue;
			}

			$input  = isset( $example['input'] ) ? sanitize_textarea_field( $example['input'] ) : '';
			$output = isset( $example['output'] ) ? sanitize_textarea_field( $example['output'] ) : '';

			if ( '' === $input && '' === $output ) {
				continue;
			}

			$examples[] = array(
				'input'  => $input,
				'output' => $output,
			);
		}
	}

	$variables = array();
	if ( ! empty( $payload['variables'] ) && is_array( $payload['variables'] ) ) {
		foreach ( $payload['variables'] as $variable ) {
			if ( ! is_array( $variable ) ) {
				continue;
			}

			$name        = isset( $variable['name'] ) ? sanitize_key( $variable['name'] ) : '';
			$description = isset( $variable['description'] ) ? sanitize_textarea_field( $variable['description'] ) : '';

			if ( '' === $name ) {
				continue;
			}

			$variables[] = array(
				'name'        => $name,
				'description' => $description,
			);
		}
	}

	ai_persona_update_meta( $post_id, 'ai_persona_role', $role );
	ai_persona_update_meta( $post_id, 'ai_persona_guidelines', $guidelines );
	ai_persona_update_meta( $post_id, 'ai_persona_constraints', $constraints );
	ai_persona_update_meta( $post_id, 'ai_persona_examples', $examples );
	ai_persona_update_meta( $post_id, 'ai_persona_variables', $variables );
}
add_action( 'save_post_ai_persona', __NAMESPACE__ . '\\save_metabox' );

/**
 * Update or delete persona meta.
 *
 * @param int    $post_id Post ID.
 * @param string $meta_key Meta key.
 * @param mixed  $value Meta value.
 */
function ai_persona_update_meta( $post_id, $meta_key, $value ) {
	if ( empty( $value ) && '0' !== $value ) {
		delete_post_meta( $post_id, $meta_key );
		return;
	}

	update_post_meta( $post_id, $meta_key, $value );
}
