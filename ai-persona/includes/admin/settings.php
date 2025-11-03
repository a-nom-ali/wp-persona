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
		'ai_persona_provider',
		array(
			'type'              => 'string',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_provider_option',
			'default'           => 'ollama',
		)
	);

	register_setting(
		'ai_persona',
		'ai_persona_api_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	register_setting(
		'ai_persona',
		'ai_persona_provider_base_url',
		array(
			'type'              => 'string',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_url_option',
			'default'           => '',
		)
	);

	register_setting(
		'ai_persona',
		'ai_persona_provider_model',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	add_settings_section(
		'ai_persona_general',
		__( 'General', 'ai-persona' ),
		'__return_false',
		'ai-persona-settings'
	);

	add_settings_field(
		'ai_persona_provider',
		__( 'Provider', 'ai-persona' ),
		__NAMESPACE__ . '\\render_provider_field',
		'ai-persona-settings',
		'ai_persona_general'
	);

	add_settings_field(
		'ai_persona_api_key',
		__( 'API Key', 'ai-persona' ),
		__NAMESPACE__ . '\\render_api_key_field',
		'ai-persona-settings',
		'ai_persona_general'
	);

	add_settings_field(
		'ai_persona_provider_base_url',
		__( 'Provider Base URL', 'ai-persona' ),
		__NAMESPACE__ . '\\render_provider_base_url_field',
		'ai-persona-settings',
		'ai_persona_general'
	);

	add_settings_field(
		'ai_persona_provider_model',
		__( 'Model Identifier', 'ai-persona' ),
		__NAMESPACE__ . '\\render_provider_model_field',
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
	$value    = get_option( 'ai_persona_api_key', '' );
	$provider = get_option( 'ai_persona_provider', 'ollama' );
	$required = in_array( $provider, array( 'openai', 'anthropic' ), true );
	?>
	<input type="text" name="ai_persona_api_key" value="<?php echo esc_attr( $value ); ?>" class="regular-text" autocomplete="off" />
	<p class="description">
		<?php
		if ( $required ) {
			esc_html_e( 'Required. Keep this secret and rotate regularly.', 'ai-persona' );
		} else {
			esc_html_e( 'Optional for local Ollama deployments.', 'ai-persona' );
		}
		?>
	</p>
	<?php
}

/**
 * Render provider choice field.
 */
function render_provider_field() {
	$provider = get_option( 'ai_persona_provider', 'ollama' );
	?>
	<select name="ai_persona_provider">
		<option value="ollama" <?php selected( $provider, 'ollama' ); ?>><?php esc_html_e( 'Ollama (local)', 'ai-persona' ); ?></option>
		<option value="openai" <?php selected( $provider, 'openai' ); ?>><?php esc_html_e( 'OpenAI (remote)', 'ai-persona' ); ?></option>
		<option value="anthropic" <?php selected( $provider, 'anthropic' ); ?>><?php esc_html_e( 'Anthropic (remote)', 'ai-persona' ); ?></option>
	</select>
	<p class="description">
		<?php esc_html_e( 'Choose your active AI backend. Remote providers require a valid API key.', 'ai-persona' ); ?>
	</p>
	<?php
}

/**
 * Render provider base URL field.
 */
function render_provider_base_url_field() {
	$provider = get_option( 'ai_persona_provider', 'ollama' );
	$default  = 'http://localhost:11434';

	if ( 'openai' === $provider ) {
		$default = 'https://api.openai.com/v1';
	} elseif ( 'anthropic' === $provider ) {
		$default = 'https://api.anthropic.com/v1';
	}

	$value = get_option( 'ai_persona_provider_base_url', '' );
	?>
	<input type="url" name="ai_persona_provider_base_url" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $default ); ?>" class="regular-text code" />
	<p class="description">
		<?php
		printf(
			esc_html__( 'Optional override. Leave blank to use the provider default (%s).', 'ai-persona' ),
			esc_html( $default )
		);
		?>
	</p>
	<?php
}

/**
 * Render provider model field.
 */
function render_provider_model_field() {
	$provider = get_option( 'ai_persona_provider', 'ollama' );
	$default  = 'minimax-m2:cloud';

	if ( 'openai' === $provider ) {
		$default = 'gpt-4o-mini';
	} elseif ( 'anthropic' === $provider ) {
		$default = 'claude-3-haiku-20240307';
	}

	$value = get_option( 'ai_persona_provider_model', '' );
	?>
	<input type="text" name="ai_persona_provider_model" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $default ); ?>" class="regular-text" />
	<p class="description">
		<?php
		printf(
			esc_html__( 'Model identifier recognised by the selected provider (default %s).', 'ai-persona' ),
			esc_html( $default )
		);
		?>
	</p>
	<?php
}

/**
 * Sanitize URL option values.
 *
 * @param string $value Raw URL.
 * @return string
 */
function sanitize_url_option( $value ) {
	$value = trim( $value );

	if ( empty( $value ) ) {
		return '';
	}

	return esc_url_raw( $value );
}

/**
 * Sanitize provider option.
 *
 * @param string $value Raw provider value.
 * @return string
 */
function sanitize_provider_option( $value ) {
	$allowed = array( 'ollama', 'openai', 'anthropic' );

	if ( ! in_array( $value, $allowed, true ) ) {
		return 'ollama';
	}

	return $value;
}
