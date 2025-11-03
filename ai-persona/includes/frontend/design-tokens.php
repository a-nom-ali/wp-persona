<?php

namespace Ai_Persona\Frontend\Design_Tokens;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve the default design tokens as CSS custom properties.
 *
 * @return array<string,string>
 */
function get_default_tokens() {
	return array(
		'--ai-persona-color-surface'       => '#ffffff',
		'--ai-persona-color-border'        => '#d7d7d7',
		'--ai-persona-color-primary'       => '#1d4ed8',
		'--ai-persona-color-neutral'       => '#f5f5f5',
		'--ai-persona-color-text'          => '#111827',
		'--ai-persona-color-header-border' => 'rgba(15, 23, 42, 0.08)',
		'--ai-persona-shadow'              => '0 1px 2px rgba(0, 0, 0, 0.05)',
		'--ai-persona-outline'             => 'rgba(29, 78, 216, 0.35)',
		'--ai-persona-radius-base'         => '12px',
		'--ai-persona-radius-pill'         => '999px',
		'--ai-persona-font-family'         => 'inherit',
		'--ai-persona-font-size'           => '15px',
	);
}

/**
 * Retrieve design tokens with filters applied.
 *
 * @return array<string,string>
 */
function get_design_tokens() {
	$tokens = get_default_tokens();

	/**
	 * Filter the design tokens exposed as CSS variables.
	 *
	 * @param array<string,string> $tokens CSS custom properties mapped to values.
	 */
	return apply_filters( 'ai_persona_design_tokens', $tokens );
}

/**
 * Build CSS from a token array.
 *
 * @param array<string,string> $tokens CSS custom properties mapped to values.
 * @param string               $scope  Selector scope (default :root).
 * @return string
 */
function build_css_from_tokens( array $tokens, $scope = ':root' ) {
	if ( empty( $tokens ) ) {
		return '';
	}

	$lines = array( $scope . ' {' );

	foreach ( $tokens as $property => $value ) {
		$lines[] = sprintf( "\t%s: %s;", $property, $value );
	}

	$lines[] = '}';

	return implode( "\n", $lines );
}

/**
 * Generate CSS for the current token set.
 *
 * @return string
 */
function get_design_tokens_css() {
	$tokens = get_design_tokens();
	$css    = build_css_from_tokens( $tokens );

	/**
	 * Filter the compiled CSS rules for design tokens.
	 *
	 * @param string               $css    CSS string containing custom properties.
	 * @param array<string,string> $tokens Token map used to generate the CSS.
	 */
	return apply_filters( 'ai_persona_design_tokens_css', $css, $tokens );
}

/**
 * Inject tokens into the frontend stylesheet.
 */
function enqueue_frontend_tokens() {
	$css = get_design_tokens_css();

	if ( empty( $css ) ) {
		return;
	}

	wp_add_inline_style( 'ai-persona-frontend', $css );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_frontend_tokens', 20 );

/**
 * Inject tokens into the block editor.
 */
function enqueue_editor_tokens() {
	$css = get_design_tokens_css();

	if ( empty( $css ) ) {
		return;
	}

	if ( ! wp_style_is( 'ai-persona-editor-tokens', 'registered' ) ) {
		wp_register_style( 'ai-persona-editor-tokens', false, array(), AI_PERSONA_VERSION );
	}

	wp_enqueue_style( 'ai-persona-editor-tokens' );
	wp_add_inline_style( 'ai-persona-editor-tokens', $css );
}
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_editor_tokens', 20 );
