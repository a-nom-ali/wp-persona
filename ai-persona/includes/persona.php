<?php

namespace Ai_Persona;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve structured persona data.
 *
 * @param int $post_id Persona post ID.
 * @return array|null
 */
function get_persona_data( $post_id ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		return null;
	}

	$role        = (string) get_post_meta( $post_id, 'ai_persona_role', true );
	$guidelines  = get_post_meta( $post_id, 'ai_persona_guidelines', true );
	$constraints = get_post_meta( $post_id, 'ai_persona_constraints', true );
	$examples    = get_post_meta( $post_id, 'ai_persona_examples', true );
	$variables   = get_post_meta( $post_id, 'ai_persona_variables', true );

	$guidelines  = normalize_string_list( $guidelines );
	$constraints = normalize_string_list( $constraints );
	$examples    = normalize_examples( $examples );
	$variables   = normalize_variables( $variables );

	$data = array(
		'id'          => $post_id,
		'role'        => $role,
		'guidelines'  => $guidelines,
		'constraints' => $constraints,
		'examples'    => $examples,
		'variables'   => $variables,
	);

	/**
	 * Filter structured persona data before use.
	 *
	 * @param array|null $data    Persona data or null when unavailable.
	 * @param int        $post_id Persona post ID.
	 */
	return apply_filters( 'ai_persona_persona_data', $data, $post_id );
}

/**
 * Compile persona data into a prompt string.
 *
 * @param array $persona Persona data from get_persona_data().
 * @param array $context Optional context (persona_id, user_input, etc.).
 * @return string
 */
function compile_persona_prompt( array $persona, array $context = array() ) {
	$segments = array();

	if ( ! empty( $persona['role'] ) ) {
		$segments[] = trim( $persona['role'] );
	}

	if ( ! empty( $persona['guidelines'] ) ) {
		$guidelines = implode(
			"\n",
			array_map(
				static function ( $item ) {
					return '- ' . $item;
				},
				$persona['guidelines']
			)
		);
		$segments[] = "Guidelines:\n{$guidelines}";
	}

	if ( ! empty( $persona['constraints'] ) ) {
		$constraints = implode(
			"\n",
			array_map(
				static function ( $item ) {
					return '- ' . $item;
				},
				$persona['constraints']
			)
		);
		$segments[] = "Constraints:\n{$constraints}";
	}

	if ( ! empty( $persona['variables'] ) ) {
		$variables = implode(
			"\n",
			array_map(
				static function ( $variable ) {
					$description = $variable['description'] ? ' – ' . $variable['description'] : '';
					return '{{' . $variable['name'] . '}}' . $description;
				},
				$persona['variables']
			)
		);
		$segments[] = "Dynamic context tokens:\n{$variables}";
	}

	if ( ! empty( $persona['examples'] ) ) {
		$examples = implode(
			"\n\n",
			array_map(
				static function ( $index, $example ) {
					$input  = $example['input'] ? $example['input'] : 'n/a';
					$output = $example['output'] ? $example['output'] : 'n/a';

					return sprintf(
						"%d. User: %s\n   Assistant: %s",
						$index + 1,
						$input,
						$output
					);
				},
				array_keys( $persona['examples'] ),
				$persona['examples']
			)
		);
		$segments[] = "Examples:\n{$examples}";
	}

	$prompt = trim( implode( "\n\n", array_filter( $segments ) ) );

	/**
	 * Filter the compiled persona prompt.
	 *
	 * @param string $prompt  Prompt string.
	 * @param array  $persona Persona data.
	 * @param array  $context Additional context.
	 */
	return apply_filters( 'ai_persona_compiled_prompt', $prompt, $persona, $context );
}

/**
 * Normalize text list meta to array of trimmed strings.
 *
 * @param mixed $value Stored meta value.
 * @return array
 */
function normalize_string_list( $value ) {
	if ( empty( $value ) ) {
		return array();
	}

	if ( is_string( $value ) ) {
		$value = preg_split( '/\r\n|\r|\n/', $value );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	$normalized = array();

	foreach ( $value as $item ) {
		$item = trim( (string) $item );

		if ( '' !== $item ) {
			$normalized[] = $item;
		}
	}

	return array_values( $normalized );
}

/**
 * Normalize examples.
 *
 * @param mixed $value Stored meta value.
 * @return array
 */
function normalize_examples( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$examples = array();

	foreach ( $value as $example ) {
		if ( ! is_array( $example ) ) {
			continue;
		}

		$input  = trim( isset( $example['input'] ) ? (string) $example['input'] : '' );
		$output = trim( isset( $example['output'] ) ? (string) $example['output'] : '' );

		if ( '' === $input && '' === $output ) {
			continue;
		}

		$examples[] = array(
			'input'  => $input,
			'output' => $output,
		);
	}

	return $examples;
}

/**
 * Normalize variables.
 *
 * @param mixed $value Stored meta value.
 * @return array
 */
function normalize_variables( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$variables = array();

	foreach ( $value as $variable ) {
		if ( ! is_array( $variable ) ) {
			continue;
		}

		$name = sanitize_key( isset( $variable['name'] ) ? (string) $variable['name'] : '' );

		if ( '' === $name ) {
			continue;
		}

		$variables[] = array(
			'name'        => $name,
			'description' => trim( isset( $variable['description'] ) ? (string) $variable['description'] : '' ),
		);
	}

	return $variables;
}
