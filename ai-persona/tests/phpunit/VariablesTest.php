<?php

use PHPUnit\Framework\TestCase;

class VariablesTest extends TestCase {

    protected function setUp(): void {
        ai_persona_tests_reset_filters();
        ai_persona_tests_set_current_user( null );
        ai_persona_tests_set_queried_object( null );
        ai_persona_tests_set_search_query( '' );
        global $wp_query;
        $wp_query = null;
    }

    public function test_includes_wp_query_tokens() {
        $post = (object) array(
            'ID'           => 10,
            'post_title'   => 'First Post',
            'post_name'    => 'first-post',
            'post_content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'post_excerpt' => '',
        );

        ai_persona_tests_set_queried_object( $post );
        ai_persona_tests_set_search_query( 'wordpress ai' );

        $term = (object) array(
            'term_id'     => 5,
            'name'        => 'Announcements',
            'slug'        => 'announcements',
            'taxonomy'    => 'category',
            'description' => 'Latest company updates and product releases for our users across platforms.',
        );

        global $wp_query;
        $wp_query = new VariablesQueryStub(
            array( $post ),
            1,
            1,
            true,
            true,
            $term
        );

        $variables = Ai_Persona\Variables\get_dynamic_variables();

        $this->assertSame( 'First Post', $variables['current_post.title'] );
        $this->assertSame( '1', $variables['current_query.found_posts'] );
        $this->assertSame( 'true', $variables['current_query.is_search'] );
        $this->assertSame( 'wordpress ai', $variables['current_query.search_terms'] );
        $this->assertSame( 'First Post', $variables['current_query.post_titles'] );
        $this->assertSame( 'Announcements', $variables['current_term.name'] );
        $this->assertSame( 'category', $variables['current_term.taxonomy'] );
    }

    public function test_variables_without_query_do_not_include_query_tokens() {
        ai_persona_tests_set_queried_object( null );
        ai_persona_tests_set_search_query( '' );
        global $wp_query;
        $wp_query = null;

        $variables = Ai_Persona\Variables\get_dynamic_variables();

        $this->assertArrayHasKey( 'site.name', $variables );
        $this->assertArrayNotHasKey( 'current_query.post_titles', $variables );
        $this->assertArrayNotHasKey( 'current_term.name', $variables );
    }
}

class VariablesQueryStub {
    public $posts;
    public $found_posts;
    public $max_num_pages;
    private $is_search;
    private $is_archive;
    private $queried_object;

    public function __construct( $posts, $found_posts, $max_pages, $is_search, $is_archive, $queried_object ) {
        $this->posts          = $posts;
        $this->found_posts    = $found_posts;
        $this->max_num_pages  = $max_pages;
        $this->is_search      = $is_search;
        $this->is_archive     = $is_archive;
        $this->queried_object = $queried_object;
    }

    public function is_search() {
        return $this->is_search;
    }

    public function is_archive() {
        return $this->is_archive;
    }

    public function get_queried_object() {
        return $this->queried_object;
    }
}
