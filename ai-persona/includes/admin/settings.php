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

	register_setting(
		'ai_persona',
		'ai_persona_role_capabilities',
		array(
			'type'              => 'array',
			'sanitize_callback' => __NAMESPACE__ . '\\sanitize_role_capabilities_option',
			'default'           => \Ai_Persona\Capabilities\get_default_role_capabilities(),
		)
	);

	register_setting(
		'ai_persona',
		'ai_persona_webhook_endpoints',
		array(
			'type'              => 'array',
			'sanitize_callback' => '\\Ai_Persona\\Webhooks\\sanitize_endpoints_option',
			'default'           => array(),
		)
	);

	register_setting(
		'ai_persona',
		'ai_persona_logging_enabled',
		array(
			'type'              => 'boolean',
			'sanitize_callback' => '__return_bool',
			'default'           => false,
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

add_settings_section(
	'ai_persona_logging',
	__( 'Analytics & Logging', 'ai-persona' ),
	'__return_false',
	'ai-persona-settings'
);

add_settings_field(
	'ai_persona_logging_enabled',
	__( 'Enable analytics logging', 'ai-persona' ),
	__NAMESPACE__ . '\render_logging_field',
	'ai-persona-settings',
	'ai_persona_logging'
);

add_settings_section(
	'ai_persona_webhooks',
	__( 'Webhooks', 'ai-persona' ),
	'__return_false',
	'ai-persona-settings'
);

add_settings_field(
	'ai_persona_webhook_endpoints',
	__( 'Outgoing webhooks', 'ai-persona' ),
	__NAMESPACE__ . '\render_webhook_endpoints_field',
	'ai-persona-settings',
	'ai_persona_webhooks'
);

	add_settings_section(
		'ai_persona_permissions',
		__( 'Permissions', 'ai-persona' ),
		'__return_false',
		'ai-persona-settings'
	);

	add_settings_field(
		'ai_persona_role_capabilities',
		__( 'Role access', 'ai-persona' ),
		__NAMESPACE__ . '\\render_role_capabilities_field',
		'ai-persona-settings',
		'ai_persona_permissions'
	);

	add_settings_section(
		'ai_persona_auth',
		__( 'Automation Authentication', 'ai-persona' ),
		__NAMESPACE__ . '\\render_authentication_help',
		'ai-persona-settings'
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

function render_authentication_help() {
	$application_passwords_url = admin_url( 'profile.php#application-passwords-section' );
	?>
	<p><?php esc_html_e( 'Automations can authenticate with WordPress cookies + nonces or Application Passwords.', 'ai-persona' ); ?></p>
	<ol>
		<li><?php
		printf( esc_html__( 'Create an Application Password for the automation user via %s.', 'ai-persona' ), sprintf( '<a href="%1$s">%2$s</a>', esc_url( $application_passwords_url ), esc_html__( 'Users → Profile', 'ai-persona' ) ) );
		?></li>
		<li><?php esc_html_e( 'Store the username/password securely (environment variables, secrets manager).', 'ai-persona' ); ?></li>
		<li><?php esc_html_e( 'Use the bundled scripts or your own REST clients over HTTPS.', 'ai-persona' ); ?></li>
	</ol>
	<p class="description"><?php esc_html_e( 'Never commit plaintext credentials to source control.', 'ai-persona' ); ?></p>
	<?php
}

/**
 * Render the webhook endpoints textarea field.
 */
function render_webhook_endpoints_field() {
	$endpoints = get_option( 'ai_persona_webhook_endpoints', array() );

	if ( ! is_array( $endpoints ) ) {
		$endpoints = array();
	}

	$value = implode( "\n", array_map( 'esc_url', $endpoints ) );
	?>
	<textarea
		name="ai_persona_webhook_endpoints"
		rows="5"
		cols="60"
		class="large-text"
	><?php echo esc_textarea( $value ); ?></textarea>
	<p class="description">
		<?php esc_html_e( 'One HTTPS URL per line. Each successful persona response will POST a JSON payload to these endpoints.', 'ai-persona' ); ?>
	</p>
	<?php
}

/**
 * Sanitize the permissions matrix option.
 *
 * @param mixed $value Raw submitted value.
 * @return array
 */
function sanitize_role_capabilities_option( $value ) {
	return \Ai_Persona\Capabilities\sanitize_role_capability_option( $value );
}

/**
 * Render the role capability configuration field.
 */
function render_role_capabilities_field() {
	$wp_roles = wp_roles();
	$roles    = $wp_roles->roles;
	$config   = \Ai_Persona\Capabilities\get_role_settings();

	$columns = array(
		'edit'    => __( 'Create & edit personas', 'ai-persona' ),
		'publish' => __( 'Publish personas', 'ai-persona' ),
		'delete'  => __( 'Delete personas', 'ai-persona' ),
		'read'    => __( 'Read private personas', 'ai-persona' ),
	);
	?>
	<table class="ai-persona-role-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Role', 'ai-persona' ); ?></th>
				<?php foreach ( $columns as $column_key => $column_label ) : ?>
					<th><?php echo esc_html( $column_label ); ?></th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $roles as $role_slug => $role_details ) : ?>
				<tr>
					<td class="ai-persona-role-table__role"><?php echo esc_html( translate_user_role( $role_details['name'] ) ); ?></td>
					<?php foreach ( $columns as $column_key => $column_label ) : ?>
						<?php
						$input_name = sprintf( 'ai_persona_role_capabilities[%s][]', $column_key );
						$checked    = in_array( $role_slug, $config[ $column_key ], true );
						$disabled   = ( 'administrator' === $role_slug );

						$attributes = $checked ? ' checked="checked"' : '';
						if ( $disabled ) {
							$attributes .= ' disabled="disabled"';
						}
						?>
						<td>
							<label class="ai-persona-role-table__checkbox">
								<input type="checkbox" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $role_slug ); ?>"<?php echo $attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
								<span class="screen-reader-text">
									<?php
									printf(
										/* translators: %s: capability action label */
										esc_html__( '%s permission', 'ai-persona' ),
										esc_html( $column_label )
									);
									?>
								</span>
							</label>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="description">
		<?php esc_html_e( 'Administrators always retain full access. Adjust checkboxes to grant editors or other custom roles the ability to edit, publish, or delete personas.', 'ai-persona' ); ?>
	</p>
	<?php
}

/**
 * Re-sync role capabilities whenever the option changes.
 *
 * @param mixed $old_value Prior value.
 * @param mixed $value     New value.
 * @return void
 */
function handle_role_capabilities_option_change( $old_value = null, $value = null ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	\Ai_Persona\Capabilities\sync_role_capabilities();
}

add_action( 'update_option_ai_persona_role_capabilities', __NAMESPACE__ . '\\handle_role_capabilities_option_change', 10, 2 );
add_action( 'add_option_ai_persona_role_capabilities', __NAMESPACE__ . '\\handle_role_capabilities_option_change', 10, 2 );

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
 * Render logging toggle field.
 */
function render_logging_field() {
	$value = (bool) get_option( 'ai_persona_logging_enabled', false );
	?>
	<label>
		<input type="checkbox" name="ai_persona_logging_enabled" value="1" <?php checked( $value ); ?> />
		<?php esc_html_e( 'Capture persona generation events to a log file.', 'ai-persona' ); ?>
	</label>
	<p class="description">
		<?php esc_html_e( 'Logs are stored under wp-content/uploads/ai-persona/persona.log when enabled. Disable in production if analytics are not required.', 'ai-persona' ); ?>
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
