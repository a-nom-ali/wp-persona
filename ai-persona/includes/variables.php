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

	// Current query insights.
	global $wp_query;

	if ( $wp_query instanceof \WP_Query ) {
		$variables['current_query.found_posts'] = (string) $wp_query->found_posts;
		$variables['current_query.max_num_pages'] = (string) $wp_query->max_num_pages;
		$variables['current_query.is_search'] = $wp_query->is_search() ? 'true' : 'false';
		$variables['current_query.search_terms'] = $wp_query->is_search() ? (string) get_search_query( false ) : '';
		$variables['current_query.is_archive'] = $wp_query->is_archive() ? 'true' : 'false';

		$post_ids = array();
		$post_titles = array();

		if ( ! empty( $wp_query->posts ) ) {
			foreach ( $wp_query->posts as $queried_post ) {
				if ( ! $queried_post instanceof \WP_Post ) {
					continue;
				}
				$post_ids[]    = (string) $queried_post->ID;
				$post_titles[] = get_the_title( $queried_post );
			}
		}

		if ( ! empty( $post_ids ) ) {
			$variables['current_query.post_ids'] = implode( ', ', $post_ids );
		}

		if ( ! empty( $post_titles ) ) {
			$variables['current_query.post_titles'] = implode( ', ', $post_titles );
		}

		$queried_object = $wp_query->get_queried_object();

		if ( $queried_object instanceof \WP_Term ) {
			$variables['current_term.id']          = (string) $queried_object->term_id;
			$variables['current_term.name']        = $queried_object->name;
			$variables['current_term.slug']        = $queried_object->slug;
			$variables['current_term.taxonomy']    = $queried_object->taxonomy;
			$variables['current_term.description'] = wp_trim_words( $queried_object->description, 30 );
		}
	}

	/**
	 * Filter the available dynamic variables before they are exposed in the UI.
	 *
	 * @param string[] $variables Associative array of variable => description or value.
	 * @param array    $context   Current request context.
	 */
	return apply_filters( 'ai_persona_dynamic_variables', $variables, $context );
}
