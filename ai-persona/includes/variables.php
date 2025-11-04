<?php

namespace Ai_Persona\Variables;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve dynamic variables for a persona context.
 *
 * @param array $context Current request context.
 * @return string[]
 */
function get_dynamic_variables( $context = array() ) {
	$variables = array();

	// Current user details.
	if ( is_user_logged_in() ) {
		$user = wp_get_current_user();

		if ( $user && $user->exists() ) {
			$variables['current_user.display_name'] = $user->display_name;
			$variables['current_user.user_login']   = $user->user_login;
			$variables['current_user.user_email']   = $user->user_email;
			$variables['current_user.roles']        = implode( ', ', $user->roles );
		}
	}

	// Current post information.
	$queried = get_queried_object();

	if ( $queried && isset( $queried->post_type ) ) {
		$post = get_post( $queried );

		if ( $post ) {
			$variables['current_post.id']      = (string) $post->ID;
			$variables['current_post.title']   = get_the_title( $post );
			$variables['current_post.slug']    = $post->post_name;
			$variables['current_post.url']     = get_permalink( $post );
			$variables['current_post.excerpt'] = wp_trim_words( $post->post_excerpt ? $post->post_excerpt : wp_strip_all_tags( $post->post_content ), 30 );
		}
	}

	// Site details.
	$variables['site.name']        = get_bloginfo( 'name' );
	$variables['site.description'] = get_bloginfo( 'description' );
	$variables['site.url']         = home_url();

	/**
	 * Filter the available dynamic variables before they are exposed in the UI.
	 *
	 * @param string[] $variables Associative array of variable => description or value.
	 * @param array    $context   Current request context.
	 */
	return apply_filters( 'ai_persona_dynamic_variables', $variables, $context );
}
