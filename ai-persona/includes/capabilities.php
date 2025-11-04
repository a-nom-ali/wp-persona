<?php

namespace Ai_Persona\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve the capabilities assigned to the ai_persona post type.
 *
 * @return array
 */
function get_persona_capabilities() {
	$default = array(
		'edit_post'              => 'edit_ai_persona',
		'read_post'              => 'read_ai_persona',
		'delete_post'            => 'delete_ai_persona',
		'edit_posts'             => 'edit_ai_personas',
		'edit_others_posts'      => 'edit_others_ai_personas',
		'publish_posts'          => 'publish_ai_personas',
		'read_private_posts'     => 'read_private_ai_personas',
		'delete_posts'           => 'delete_ai_personas',
		'delete_private_posts'   => 'delete_private_ai_personas',
		'delete_published_posts' => 'delete_published_ai_personas',
		'delete_others_posts'    => 'delete_others_ai_personas',
		'edit_private_posts'     => 'edit_private_ai_personas',
		'edit_published_posts'   => 'edit_published_ai_personas',
	);

	/**
	 * Filters the persona capability mapping configuration.
	 *
	 * @param array $default Default capabilities.
	 */
	return apply_filters( 'ai_persona_capability_map', $default );
}

/**
 * Default role assignments for persona capabilities.
 *
 * @return array
 */
function get_default_role_capabilities() {
	return array(
		'edit'    => array( 'administrator', 'editor' ),
		'publish' => array( 'administrator', 'editor' ),
		'delete'  => array( 'administrator' ),
		'read'    => array( 'administrator', 'editor', 'author' ),
	);
}

/**
 * Retrieve all registered role slugs.
 *
 * @return string[]
 */
function get_all_role_slugs() {
	$wp_roles = wp_roles();
	return array_keys( $wp_roles->roles );
}

/**
 * Sanitize the stored role capability configuration.
 *
 * @param array $value Raw option value.
 * @return array
 */
function sanitize_role_capability_option( $value ) {
	$defaults        = get_default_role_capabilities();
	$available_roles = get_all_role_slugs();
	$sanitized       = array();

	if ( ! is_array( $value ) ) {
		$value = array();
	}

	foreach ( $defaults as $group => $default_roles ) {
		$selected = isset( $value[ $group ] ) ? (array) $value[ $group ] : $default_roles;
		$selected = array_map( 'sanitize_key', $selected );
		$selected = array_values( array_intersect( $selected, $available_roles ) );

		if ( ! in_array( 'administrator', $selected, true ) ) {
			$selected[] = 'administrator';
		}

		$sanitized[ $group ] = array_values( array_unique( $selected ) );
	}

	return $sanitized;
}

/**
 * Fetch the persisted role capability configuration.
 *
 * @return array
 */
function get_role_settings() {
	$option = get_option( 'ai_persona_role_capabilities', null );

	if ( null === $option ) {
		$option = get_default_role_capabilities();
	}

	return sanitize_role_capability_option( $option );
}

/**
 * Apply persona capabilities to WordPress roles based on saved configuration.
 */
function sync_role_capabilities() {
	$caps      = get_persona_capabilities();
	$role_map  = get_role_settings();
	$roles     = get_all_role_slugs();
	$cap_groups = array(
		'read'    => array_unique(
			array(
				$caps['read_post'],
				$caps['read_private_posts'],
			)
		),
		'edit'    => array_unique(
			array(
				$caps['edit_post'],
				$caps['edit_posts'],
				$caps['edit_others_posts'],
				$caps['edit_published_posts'],
				$caps['edit_private_posts'],
			)
		),
		'publish' => array_unique(
			array(
				$caps['publish_posts'],
			)
		),
		'delete'  => array_unique(
			array(
				$caps['delete_post'],
				$caps['delete_posts'],
				$caps['delete_others_posts'],
				$caps['delete_published_posts'],
				$caps['delete_private_posts'],
			)
		),
	);

	foreach ( $roles as $role_slug ) {
		$role = get_role( $role_slug );

		if ( ! $role ) {
			continue;
		}

		foreach ( $cap_groups as $group => $capabilities ) {
			$has_access = in_array( $role_slug, $role_map[ $group ], true );

			foreach ( $capabilities as $capability ) {
				if ( $has_access ) {
					$role->add_cap( $capability );
				} else {
					$role->remove_cap( $capability );
				}
			}
		}
	}
}

/**
 * Apply persona capabilities to WordPress roles on activation.
 */
function add_role_capabilities() {
	sync_role_capabilities();
}

/**
 * Remove persona capabilities from roles on deactivation.
 */
function remove_role_capabilities() {
	$caps = get_persona_capabilities();
	$all_caps = array_unique( array_values( $caps ) );

	foreach ( get_all_role_slugs() as $role_slug ) {
		$role = get_role( $role_slug );

		if ( ! $role ) {
			continue;
		}

		foreach ( $all_caps as $capability ) {
			$role->remove_cap( $capability );
		}
	}
}

/**
 * Check whether the current user can manage persona settings.
 *
 * @return bool
 */
function current_user_can_manage_personas() {
	$caps = get_persona_capabilities();

	$required = array(
		$caps['edit_posts'],
		$caps['publish_posts'],
	);

	foreach ( $required as $cap ) {
		if ( ! current_user_can( $cap ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Ensure persona capabilities stay in sync on each request.
 */
function ensure_role_capabilities() {
	if ( ! did_action( 'ai_persona_roles_synced' ) ) {
		sync_role_capabilities();
		do_action( 'ai_persona_roles_synced' );
	}
}

add_action( 'init', __NAMESPACE__ . '\ensure_role_capabilities', 20 );
