<?php

namespace Ai_Persona;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin orchestrator.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Retrieve the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent direct construction.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent proper unserialize.
	 */
	public function __wakeup() {}

	/**
	 * Set up plugin services.
	 */
	public function boot() {
		$this->register_includes();
		$this->register_hooks();
	}

	/**
	 * Load required files.
	 */
	private function register_includes() {
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/class-api.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/admin/metaboxes.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/admin/settings.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/admin/templates.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/webhooks.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/admin/analytics.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/frontend/chat-widget.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/frontend/design-tokens.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/frontend/api-endpoints.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/capabilities.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/persona.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/logging.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/interface-provider.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/class-null-provider.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/class-ollama-provider.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/class-openai-provider.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/class-anthropic-provider.php';
	}

	/**
	 * Attach global hooks.
	 */
	private function register_hooks() {
		add_action( 'init', array( $this, 'register_persona_post_type' ) );
		add_action( 'init', array( $this, 'register_block_types' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter( 'ai_persona_resolve_provider', array( $this, 'resolve_provider' ) );
		add_action( 'ai_persona_response_after_generate', array( $this, 'handle_logging' ), 10, 3 );
		add_action( 'ai_persona_response_after_generate', 'Ai_Persona\\Webhooks\\dispatch_response_webhooks', 20, 3 );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Register the persona custom post type.
	 */
	public function register_persona_post_type() {
		$labels = array(
			'name'          => __( 'AI Personas', 'ai-persona' ),
			'singular_name' => __( 'AI Persona', 'ai-persona' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'supports'           => array( 'title', 'editor' ),
			'capability_type'    => 'ai_persona',
			'map_meta_cap'       => true,
			'menu_position'      => 26,
			'menu_icon'          => 'dashicons-robot',
		);

		register_post_type( 'ai_persona', $args );
	}

	/**
	 * Register dynamic Gutenberg blocks.
	 */
	public function register_block_types() {
		$args = array(
			'render_callback' => 'Ai_Persona\\Frontend\\render_chat_block',
		);

		/**
		 * Filter the registration arguments for the chat block.
		 *
		 * @param array  $args  Block registration arguments.
		 * @param string $block Block name.
		 */
		$args = apply_filters( 'ai_persona_block_registration_args', $args, 'ai-persona/chat' );

		register_block_type(
			AI_PERSONA_PLUGIN_DIR . 'blocks/ai-persona-chat',
			$args
		);
	}

	/**
	 * Provide the default provider instance.
	 *
	 * @param \Ai_Persona\Providers\Provider_Interface|null $provider Existing provider instance.
	 * @return \Ai_Persona\Providers\Provider_Interface
	 */
	public function resolve_provider( $provider ) {
		if ( $provider instanceof \Ai_Persona\Providers\Provider_Interface ) {
			return $provider;
		}

		$provider = get_option( 'ai_persona_provider', 'ollama' );
		$model    = get_option( 'ai_persona_provider_model', '' );
		$base_url = get_option( 'ai_persona_provider_base_url', '' );
		$api_key  = get_option( 'ai_persona_api_key', '' );

		switch ( $provider ) {
			case 'openai':
				$model    = $model ?: 'gpt-4o-mini';
				$base_url = $base_url ?: 'https://api.openai.com/v1';

				return new \Ai_Persona\Providers\OpenAI_Provider( $api_key, $model, $base_url );

			case 'anthropic':
				$model    = $model ?: 'claude-3-haiku-20240307';
				$base_url = $base_url ?: 'https://api.anthropic.com/v1';

				return new \Ai_Persona\Providers\Anthropic_Provider( $api_key, $model, $base_url );

			case 'ollama':
			default:
				$model    = $model ?: 'minimax-m2:cloud';
				$base_url = $base_url ?: 'http://localhost:11434';

				return new \Ai_Persona\Providers\Ollama_Provider( $base_url, $model );
		}
	}

	/**
	 * Enqueue editor scripts and styles.
	 */
	public function enqueue_editor_assets() {
		$asset_path = plugin_dir_url( AI_PERSONA_PLUGIN_FILE ) . 'blocks/ai-persona-chat/index.js';
		wp_enqueue_script(
			'ai-persona-block-editor',
			$asset_path,
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wp-data', 'wp-block-editor', 'wp-editor' ),
			AI_PERSONA_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'ai-persona-block-editor', 'ai-persona', AI_PERSONA_PLUGIN_DIR . 'languages' );
		}
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets() {
		$base_url = plugin_dir_url( AI_PERSONA_PLUGIN_FILE );

		wp_enqueue_style(
			'ai-persona-frontend',
			$base_url . 'assets/css/styles.css',
			array(),
			AI_PERSONA_VERSION
		);

		wp_enqueue_script(
			'ai-persona-frontend',
			$base_url . 'assets/js/chat.js',
			array(),
			AI_PERSONA_VERSION,
			true
		);

		$settings = array(
			'restUrl' => esc_url_raw( trailingslashit( rest_url( 'ai-persona/v1' ) ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		);

		/**
		 * Filter the localized settings delivered to the chat widget.
		 *
		 * @param array $settings Localized script data.
		 */
		$settings = apply_filters( 'ai_persona_frontend_settings', $settings );

		wp_localize_script(
			'ai-persona-frontend',
			'AiPersonaSettings',
			$settings
		);
	}

	/**
	 * Enqueue admin scripts for persona authoring UI.
	 *
	 * @param string $hook Current admin hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'ai_persona' !== $screen->post_type ) {
			return;
		}

		$base_url = plugin_dir_url( AI_PERSONA_PLUGIN_FILE );

		wp_enqueue_style(
			'ai-persona-admin',
			$base_url . 'assets/css/admin.css',
			array( 'wp-components' ),
			AI_PERSONA_VERSION
		);

		wp_enqueue_script(
			'ai-persona-admin',
			$base_url . 'assets/js/admin.js',
			array( 'wp-element', 'wp-components', 'wp-i18n', 'wp-data', 'wp-compose' ),
			AI_PERSONA_VERSION,
			true
		);

		$templates = \Ai_Persona\Admin\get_persona_templates();
		$cap_map   = \Ai_Persona\Capabilities\get_persona_capabilities();
		$permissions = array(
			'canEdit'    => current_user_can( $cap_map['edit_posts'] ),
			'canPublish' => current_user_can( $cap_map['publish_posts'] ),
			'canDelete'  => current_user_can( $cap_map['delete_posts'] ),
		);

		wp_localize_script(
			'ai-persona-admin',
			'AiPersonaAdmin',
			array(
				'templates'   => $templates,
				'capabilities'=> $cap_map,
				'permissions' => $permissions,
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'ai-persona-admin', 'ai-persona', AI_PERSONA_PLUGIN_DIR . 'languages' );
		}
	}

	/**
	 * Conditionally log persona generation events when analytics are enabled.
	 *
	 * @param array  $response Provider response payload.
	 * @param string $prompt   Compiled prompt string.
	 * @param array  $context  Request context data.
	 */
	public function handle_logging( $response, $prompt, $context ) {
		\Ai_Persona\Logging\log_generation_event( $response, $prompt, $context );
	}

	/**
	 * Load plugin text domain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'ai-persona', false, dirname( plugin_basename( AI_PERSONA_PLUGIN_FILE ) ) . '/languages/' );
	}
}
