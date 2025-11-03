<?php

namespace Ai_Persona\Admin;

use function Ai_Persona\sanitize_persona_payload;
use function Ai_Persona\save_persona_data;

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
	<?php wp_nonce_field( 'ai_persona_import', 'ai_persona_import_nonce' ); ?>
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

	<div class="ai-persona-import">
		<label for="ai-persona-import-file"><strong><?php esc_html_e( 'Import Persona JSON', 'ai-persona' ); ?></strong></label>
		<input type="file" id="ai-persona-import-file" name="ai_persona_import_file" accept="application/json" />
		<p class="description">
			<?php esc_html_e( 'Uploading a persona export will overwrite the fields above before saving.', 'ai-persona' ); ?>
		</p>
	</div>
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

	$import_meta = array();

    if ( ! is_array( $payload ) && ! empty( $_FILES['ai_persona_import_file'] ) ) {
        $import_meta = ai_persona_handle_import_upload( $post_id );
    }

	if ( ! is_array( $payload ) && ! empty( $import_meta ) ) {
		$payload = $import_meta;
	}

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

	$sanitized = sanitize_persona_payload( $payload );
	save_persona_data( $post_id, $sanitized );
}
add_action( 'save_post_ai_persona', __NAMESPACE__ . '\\save_metabox' );
/**
 * Process persona import upload.
 *
 * @param int $post_id Post ID.
 * @return array
 */
function ai_persona_handle_import_upload( $post_id ) {
    if ( empty( $_FILES['ai_persona_import_file']['tmp_name'] ) ) {
        return array();
    }

    if ( ! isset( $_POST['ai_persona_import_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['ai_persona_import_nonce'] ), 'ai_persona_import' ) ) {
        return array();
	}

    $file = $_FILES['ai_persona_import_file'];

    if ( ! empty( $file['error'] ) ) {
        return array();
    }

    $filetype = wp_check_filetype( $file['name'] );

    if ( empty( $filetype['ext'] ) || 'json' !== strtolower( $filetype['ext'] ) ) {
        return array();
    }

	$contents = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( false === $contents ) {
		return array();
	}

	$decoded = json_decode( $contents, true );

	if ( ! is_array( $decoded ) ) {
		return array();
	}

	$role        = isset( $decoded['role'] ) ? (string) $decoded['role'] : '';
	$guidelines  = isset( $decoded['guidelines'] ) ? $decoded['guidelines'] : array();
	$constraints = isset( $decoded['constraints'] ) ? $decoded['constraints'] : array();
	$examples    = isset( $decoded['examples'] ) ? $decoded['examples'] : array();
	$variables   = isset( $decoded['variables'] ) ? $decoded['variables'] : array();

	return array(
		'role'        => $role,
		'guidelines'  => $guidelines,
		'constraints' => $constraints,
		'examples'    => $examples,
		'variables'   => $variables,
	);
}
