<?php
/**
 * Plugin Name: AI Persona
 * Plugin URI: https://example.com
 * Description: Provides structured AI persona management via WordPress.
 * Version: 0.1.0
 * Author: AI Persona Contributors
 * Author URI: https://example.com
 * License: GPLv2 or later
 * Text Domain: ai-persona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AI_PERSONA_VERSION', '0.1.0' );
define( 'AI_PERSONA_PLUGIN_FILE', __FILE__ );
define( 'AI_PERSONA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once AI_PERSONA_PLUGIN_DIR . 'includes/class-ai-persona.php';

/**
 * Initialize plugin services.
 */
function ai_persona_bootstrap() {
	\Ai_Persona\Plugin::instance()->boot();
}

add_action( 'plugins_loaded', 'ai_persona_bootstrap' );
