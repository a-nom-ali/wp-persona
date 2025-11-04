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
 * Apply persona capabilities to WordPress roles on activation.
 */
function add_role_capabilities() {
	$caps = get_persona_capabilities();

	$roles = array(
		'manage' => array( 'administrator' ),
		'edit'   => array( 'administrator', 'editor' ),
		'read'   => array( 'administrator', 'editor', 'author' ),
	);

	$manage_caps = array(
		$caps['delete_posts'],
		$caps['delete_others_posts'],
		$caps['delete_published_posts'],
		$caps['delete_private_posts'],
	);

	$edit_caps = array(
		$caps['edit_posts'],
		$caps['edit_others_posts'],
		$caps['edit_published_posts'],
		$caps['edit_private_posts'],
		$caps['publish_posts'],
	);

	$read_caps = array(
		$caps['read_post'],
		$caps['read_private_posts'],
	);

	foreach ( $roles['manage'] as $role_slug ) {
		if ( $role = get_role( $role_slug ) ) { // phpcs:ignore
			foreach ( array_merge( $manage_caps, $edit_caps, $read_caps ) as $capability ) {
				$role->add_cap( $capability );
			}
		}
	}

	foreach ( $roles['edit'] as $role_slug ) {
		if ( $role = get_role( $role_slug ) ) { // phpcs:ignore
			foreach ( array_merge( $edit_caps, $read_caps ) as $capability ) {
				$role->add_cap( $capability );
			}
		}
	}

	foreach ( $roles['read'] as $role_slug ) {
		if ( $role = get_role( $role_slug ) ) { // phpcs:ignore
			foreach ( $read_caps as $capability ) {
				$role->add_cap( $capability );
			}
		}
	}
}

/**
 * Remove persona capabilities from roles on deactivation.
 */
function remove_role_capabilities() {
	$caps = get_persona_capabilities();
	$all_caps = array_unique( array_values( $caps ) );

	$roles = array( 'administrator', 'editor', 'author' );

	foreach ( $roles as $role_slug ) {
		if ( $role = get_role( $role_slug ) ) { // phpcs:ignore
			foreach ( $all_caps as $capability ) {
				$role->remove_cap( $capability );
			}
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
