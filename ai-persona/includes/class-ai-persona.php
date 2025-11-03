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
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/frontend/chat-widget.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/frontend/api-endpoints.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/interface-provider.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/class-null-provider.php';
		require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/class-ollama-provider.php';
	}

	/**
	 * Attach global hooks.
	 */
	private function register_hooks() {
		add_action( 'init', array( $this, 'register_persona_post_type' ) );
		add_action( 'init', array( $this, 'register_block_types' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_filter( 'ai_persona_resolve_provider', array( $this, 'resolve_provider' ) );
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
			'capability_type'    => 'post',
			'menu_position'      => 26,
			'menu_icon'          => 'dashicons-robot',
		);

		register_post_type( 'ai_persona', $args );
	}

	/**
	 * Register dynamic Gutenberg blocks.
	 */
	public function register_block_types() {
		register_block_type(
			AI_PERSONA_PLUGIN_DIR . 'blocks/ai-persona-chat',
			array(
				'render_callback' => 'Ai_Persona\\Frontend\\render_chat_block',
			)
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

		$base_url = get_option( 'ai_persona_provider_base_url', 'http://localhost:11434' );
		$model    = get_option( 'ai_persona_provider_model', 'minimax-m2:cloud' );

		return new \Ai_Persona\Providers\Ollama_Provider( $base_url, $model );
	}

	/**
	 * Enqueue editor scripts and styles.
	 */
	public function enqueue_editor_assets() {
		$asset_path = plugin_dir_url( AI_PERSONA_PLUGIN_FILE ) . 'blocks/ai-persona-chat/index.js';
		wp_enqueue_script(
			'ai-persona-block-editor',
			$asset_path,
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor' ),
			AI_PERSONA_VERSION,
			true
		);
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
			array( 'wp-element' ),
			AI_PERSONA_VERSION,
			true
		);
	}
}
