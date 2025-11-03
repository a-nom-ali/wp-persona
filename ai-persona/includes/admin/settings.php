<?php

namespace Ai_Persona\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the settings page.
 */
function register_settings_page() {
	add_options_page(
		__( 'AI Persona Settings', 'ai-persona' ),
		__( 'AI Persona', 'ai-persona' ),
		'manage_options',
		'ai-persona-settings',
		__NAMESPACE__ . '\\render_settings_page'
	);
}
add_action( 'admin_menu', __NAMESPACE__ . '\\register_settings_page' );

/**
 * Register plugin settings.
 */
function register_settings() {
	register_setting(
		'ai_persona',
		'ai_persona_api_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	add_settings_section(
		'ai_persona_general',
		__( 'General', 'ai-persona' ),
		'__return_false',
		'ai-persona-settings'
	);

	add_settings_field(
		'ai_persona_api_key',
		__( 'API Key', 'ai-persona' ),
		__NAMESPACE__ . '\\render_api_key_field',
		'ai-persona-settings',
		'ai_persona_general'
	);
}
add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );

/**
 * Render the settings screen.
 */
function render_settings_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'AI Persona Settings', 'ai-persona' ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'ai_persona' );
			do_settings_sections( 'ai-persona-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Render the API key field.
 */
function render_api_key_field() {
	$value = get_option( 'ai_persona_api_key', '' );
	?>
	<input type="text" name="ai_persona_api_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off" />
	<p class="description">
		<?php esc_html_e( 'Store your AI provider API key. Consider overriding the storage mechanism via filters for custom handling.', 'ai-persona' ); ?>
	</p>
	<?php
}
