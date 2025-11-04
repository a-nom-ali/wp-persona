<?php
/**
 * Plugin Name: AI Persona
 * Plugin URI: https://example.com
 * Description: Provides structured AI persona management via WordPress.
 * Version: 1.0.0
 * Author: AI Persona Contributors
 * Author URI: https://example.com
 * License: GPLv2 or later
 * Text Domain: ai-persona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AI_PERSONA_VERSION', '1.0.0' );
define( 'AI_PERSONA_PLUGIN_FILE', __FILE__ );
define( 'AI_PERSONA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once AI_PERSONA_PLUGIN_DIR . 'includes/capabilities.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/class-ai-persona.php';

/**
 * Initialize plugin services.
 */
function ai_persona_bootstrap() {
	\Ai_Persona\Plugin::instance()->boot();
}

add_action( 'plugins_loaded', 'ai_persona_bootstrap' );

register_activation_hook(
	__FILE__,
	function() {
		\Ai_Persona\Capabilities\add_role_capabilities();
	}
);

register_deactivation_hook(
	__FILE__,
	function() {
		\Ai_Persona\Capabilities\remove_role_capabilities();
	}
);
