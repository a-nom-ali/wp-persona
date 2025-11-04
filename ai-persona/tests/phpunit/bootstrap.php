<?php

if ( ! function_exists( '__return_bool' ) ) {
    function __return_bool( $value ) { // phpcs:ignore
        return (bool) $value;
    }
}

// Minimal bootstrap providing WordPress-like helpers for isolated unit tests.

$plugin_root = dirname( dirname( __DIR__ ) );

if ( ! defined( 'AI_PERSONA_PLUGIN_DIR' ) ) {
    define( 'AI_PERSONA_PLUGIN_DIR', $plugin_root . '/' );
}

if ( ! defined( 'AI_PERSONA_PLUGIN_FILE' ) ) {
    define( 'AI_PERSONA_PLUGIN_FILE', AI_PERSONA_PLUGIN_DIR . 'ai-persona.php' );
}

if ( ! defined( 'AI_PERSONA_VERSION' ) ) {
    define( 'AI_PERSONA_VERSION', '0.1.0-tests' );
}

require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/interface-provider.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/class-null-provider.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/class-ollama-provider.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/class-openai-provider.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/providers/class-anthropic-provider.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/persona.php';
require_once AI_PERSONA_PLUGIN_DIR . 'includes/frontend/design-tokens.php';

// -----------------------------------------------------------------------------
// Core utility shims.
// -----------------------------------------------------------------------------

if ( ! function_exists( 'trailingslashit' ) ) {
    function trailingslashit( $string ) {
        return rtrim( $string, '/\\' ) . '/';
    }
}

if ( ! function_exists( 'untrailingslashit' ) ) {
    function untrailingslashit( $string ) {
        return rtrim( $string, '/\\' );
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return trailingslashit( dirname( $file ) );
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) {
        return 'http://example.com/' . basename( dirname( $file ) ) . '/';
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $message;
        private $data;

        public function __construct( $code = '', $message = '', $data = null ) {
            $this->message = $message;
            $this->data    = $data;
        }

        public function get_error_message() {
            return $this->message;
        }

        public function get_error_data() {
            return $this->data;
        }
    }
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
    class WP_REST_Request {
        private $params = array();
        private $headers = array();

        public function __construct( $params = array() ) {
            $this->params = $params;
        }

        public function get_param( $key ) {
            return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null;
        }

        public function set_header( $key, $value ) {
            $this->headers[ strtolower( $key ) ] = $value;
        }

        public function get_header( $key ) {
            $key = strtolower( $key );
            return isset( $this->headers[ $key ] ) ? $this->headers[ $key ] : null;
        }
    }
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
    class WP_REST_Response {
        private $data;
        private $status;

        public function __construct( $data = null, $status = 200 ) {
            $this->data   = $data;
            $this->status = $status;
        }

        public function get_data() {
            return $this->data;
        }

        public function get_status() {
            return $this->status;
        }
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) { // phpcs:ignore
        return $text;
    }
}

// -----------------------------------------------------------------------------
// Filter/action registry.
// -----------------------------------------------------------------------------

global $ai_persona_tests_filters;
$ai_persona_tests_filters = array();

function ai_persona_tests_reset_filters() {
    global $ai_persona_tests_filters;
    $ai_persona_tests_filters = array();
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        global $ai_persona_tests_filters;
        $ai_persona_tests_filters[ $tag ][ $priority ][] = array( $function_to_add, $accepted_args );
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        add_filter( $tag, $function_to_add, $priority, $accepted_args );
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        global $ai_persona_tests_filters;

        $args = func_get_args();
        $value = $args[1];

        if ( empty( $ai_persona_tests_filters[ $tag ] ) ) {
            return $value;
        }

        ksort( $ai_persona_tests_filters[ $tag ] );

        $extra_args = array_slice( $args, 2 );

        foreach ( $ai_persona_tests_filters[ $tag ] as $priority => $callbacks ) {
            foreach ( $callbacks as $callback ) {
                $params = array_slice( array_merge( array( $value ), $extra_args ), 0, $callback[1] );
                $value  = call_user_func_array( $callback[0], $params );
            }
        }

        return $value;
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( $tag, ...$args ) {
        global $ai_persona_tests_filters;

        if ( empty( $ai_persona_tests_filters[ $tag ] ) ) {
            return;
        }

        ksort( $ai_persona_tests_filters[ $tag ] );

        foreach ( $ai_persona_tests_filters[ $tag ] as $priority => $callbacks ) {
            foreach ( $callbacks as $callback ) {
                call_user_func_array( $callback[0], array_slice( $args, 0, $callback[1] ) );
            }
        }
    }
}

// -----------------------------------------------------------------------------
// Option helpers.
// -----------------------------------------------------------------------------

global $ai_persona_tests_options;
$ai_persona_tests_options = array();

function ai_persona_tests_reset_options() {
    global $ai_persona_tests_options;
    $ai_persona_tests_options = array();
}

function ai_persona_tests_set_option( $key, $value ) {
    global $ai_persona_tests_options;
    $ai_persona_tests_options[ $key ] = $value;
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        global $ai_persona_tests_options;
        return array_key_exists( $option, $ai_persona_tests_options ) ? $ai_persona_tests_options[ $option ] : $default;
    }

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value ) { // phpcs:ignore
        global $ai_persona_tests_options;
        $ai_persona_tests_options[ $option ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $option ) { // phpcs:ignore
        global $ai_persona_tests_options;
        unset( $ai_persona_tests_options[ $option ] );
        return true;
    }
}

}

// -----------------------------------------------------------------------------
// Capability and nonce helpers.
// -----------------------------------------------------------------------------

global $ai_persona_tests_current_user_can;
$ai_persona_tests_current_user_can = false;

function ai_persona_tests_set_current_user_can( $value ) {
    global $ai_persona_tests_current_user_can;
    $ai_persona_tests_current_user_can = (bool) $value;
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $capability ) {
        global $ai_persona_tests_current_user_can;
        return (bool) $ai_persona_tests_current_user_can;
    }
}

global $ai_persona_tests_valid_nonce;
$ai_persona_tests_valid_nonce = 'valid-nonce';

function ai_persona_tests_set_valid_nonce( $nonce ) {
    global $ai_persona_tests_valid_nonce;
    $ai_persona_tests_valid_nonce = $nonce;
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce( $action = '' ) {
        global $ai_persona_tests_valid_nonce;
        return $ai_persona_tests_valid_nonce;
    }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action = '' ) {
        global $ai_persona_tests_valid_nonce;
        return $nonce === $ai_persona_tests_valid_nonce;
    }
}

// -----------------------------------------------------------------------------
// Misc WordPress helpers.
// -----------------------------------------------------------------------------

if ( ! function_exists( 'rest_url' ) ) {
    function rest_url( $path = '' ) {
        return 'http://example.com/wp-json/' . ltrim( $path, '/' );
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data, $options = 0, $depth = 512 ) { // phpcs:ignore
        return json_encode( $data, $options, $depth );
    }
}

if ( ! function_exists( 'rest_authorization_required_code' ) ) {
    function rest_authorization_required_code() {
        return 401;
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) { // phpcs:ignore
        return $value;
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) { // phpcs:ignore
        $str = strip_tags( (string) $str );
        $str = preg_replace( '/[\r\n\t]+/', ' ', $str );
        return trim( $str );
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $str ) { // phpcs:ignore
        $str = strip_tags( (string) $str );
        return trim( $str );
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) { // phpcs:ignore
        $key = strtolower( (string) $key );
        return preg_replace( '/[^a-z0-9_\-]/', '', $key );
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ) { // phpcs:ignore
        return isset( $response['body'] ) ? $response['body'] : '';
    }
}

if ( ! function_exists( 'register_post_type' ) ) {
    function register_post_type( $post_type, $args = array() ) {} // phpcs:ignore
}

if ( ! function_exists( 'register_block_type' ) ) {
    function register_block_type( $block, $args = array() ) {} // phpcs:ignore
}

if ( ! function_exists( 'register_rest_route' ) ) {
    function register_rest_route( $namespace, $route, $args = array(), $override = false ) {} // phpcs:ignore
}

if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode( $tag, $callback ) {} // phpcs:ignore
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
    function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {} // phpcs:ignore
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
    function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {} // phpcs:ignore
}

if ( ! function_exists( 'wp_localize_script' ) ) {
    function wp_localize_script( $handle, $name, $data ) {} // phpcs:ignore
}

if ( ! function_exists( 'status_header' ) ) {
    function status_header( $code ) {} // phpcs:ignore
}

if ( ! function_exists( 'wp_upload_dir' ) ) {
    function wp_upload_dir() { // phpcs:ignore
        $temp = sys_get_temp_dir() . '/ai-persona-tests';
        if ( ! is_dir( $temp ) ) {
            mkdir( $temp, 0777, true );
        }
        return array( 'basedir' => $temp );
    }
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
    function wp_mkdir_p( $dir ) { // phpcs:ignore
        if ( is_dir( $dir ) ) {
            return true;
        }
        return mkdir( $dir, 0777, true );
    }
}

if ( ! function_exists( 'wp_check_filetype' ) ) {
    function wp_check_filetype( $filename, $mimes = null ) { // phpcs:ignore
        $ext  = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        $type = 'application/octet-stream';

        if ( 'json' === $ext ) {
            $type = 'application/json';
        }

        return array(
            'ext'  => $ext,
            'type' => $type,
        );
    }
}

if ( ! function_exists( 'wp_insert_post' ) ) {
    function wp_insert_post( $postarr, $wp_error = false ) { // phpcs:ignore
        global $ai_persona_tests_posts, $ai_persona_tests_post_id_counter;

        $ai_persona_tests_post_id_counter++;
        $post_id = $ai_persona_tests_post_id_counter;

        $defaults = array(
            'post_title'   => '',
            'post_status'  => 'draft',
            'post_type'    => 'post',
            'post_content' => '',
        );

        $ai_persona_tests_posts[ $post_id ] = array_merge( $defaults, $postarr, array( 'ID' => $post_id ) );

        return $post_id;
    }
}

if ( ! function_exists( 'wp_update_post' ) ) {
    function wp_update_post( $postarr, $wp_error = false ) { // phpcs:ignore
        global $ai_persona_tests_posts;

        $post_id = isset( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0;

        if ( ! $post_id || ! isset( $ai_persona_tests_posts[ $post_id ] ) ) {
            if ( $wp_error ) {
                return new WP_Error( 'not_found', 'Post not found.' );
            }

            return 0;
        }

        $ai_persona_tests_posts[ $post_id ] = array_merge( $ai_persona_tests_posts[ $post_id ], $postarr );

        return $post_id;
    }
}

if ( ! function_exists( 'get_post_type' ) ) {
    function get_post_type( $post_id ) { // phpcs:ignore
        global $ai_persona_tests_posts;

        return isset( $ai_persona_tests_posts[ $post_id ] ) ? $ai_persona_tests_posts[ $post_id ]['post_type'] : null;
    }
}

if ( ! function_exists( 'get_post_status' ) ) {
    function get_post_status( $post_id ) { // phpcs:ignore
        global $ai_persona_tests_posts;

        return isset( $ai_persona_tests_posts[ $post_id ] ) ? $ai_persona_tests_posts[ $post_id ]['post_status'] : 'draft';
    }
}

if ( ! function_exists( 'get_post' ) ) {
    function get_post( $post_id ) { // phpcs:ignore
        global $ai_persona_tests_posts;

        if ( ! isset( $ai_persona_tests_posts[ $post_id ] ) ) {
            return null;
        }

        return (object) $ai_persona_tests_posts[ $post_id ];
    }
}

if ( ! function_exists( 'wp_delete_post' ) ) {
    function wp_delete_post( $post_id, $force_delete = false ) { // phpcs:ignore
        global $ai_persona_tests_posts, $ai_persona_tests_meta_store;

        $post_id = absint( $post_id );

        if ( ! isset( $ai_persona_tests_posts[ $post_id ] ) ) {
            return false;
        }

        unset( $ai_persona_tests_posts[ $post_id ] );
        unset( $ai_persona_tests_meta_store[ $post_id ] );

        return true;
    }
}

// -----------------------------------------------------------------------------
// HTTP stub for controlling wp_remote_post responses.
// -----------------------------------------------------------------------------

class Ai_Persona_Tests_HTTP_Stub {
    public static $next_response = null;

    public static function reset() {
        self::$next_response = null;
    }

    public static function queue( $response ) {
        self::$next_response = $response;
    }
}

if ( ! function_exists( 'wp_remote_post' ) ) {
    function wp_remote_post( $url, $args = array() ) { // phpcs:ignore
        $response = Ai_Persona_Tests_HTTP_Stub::$next_response;

        if ( is_callable( $response ) ) {
            return call_user_func( $response, $url, $args );
        }

        return $response;
    }
}

// -----------------------------------------------------------------------------
// Post meta helpers.
// -----------------------------------------------------------------------------

global $ai_persona_tests_meta_store;
$ai_persona_tests_meta_store = array();

global $ai_persona_tests_posts;
$ai_persona_tests_posts = array();

global $ai_persona_tests_post_id_counter;
$ai_persona_tests_post_id_counter = 1000;

function ai_persona_tests_reset_posts() {
    global $ai_persona_tests_posts, $ai_persona_tests_post_id_counter;

    $ai_persona_tests_posts          = array();
    $ai_persona_tests_post_id_counter = 1000;
}

function ai_persona_tests_get_post( $post_id ) {
    global $ai_persona_tests_posts;

    return isset( $ai_persona_tests_posts[ $post_id ] ) ? $ai_persona_tests_posts[ $post_id ] : null;
}

function ai_persona_tests_set_post_meta( $post_id, $key, $value ) {
    update_post_meta( $post_id, $key, $value );
}

function ai_persona_tests_reset_uploads() {
    $uploads = wp_upload_dir();
    if ( empty( $uploads['basedir'] ) || ! is_dir( $uploads['basedir'] ) ) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $uploads['basedir'], RecursiveDirectoryIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ( $iterator as $fileinfo ) {
        if ( $fileinfo->isDir() ) {
            rmdir( $fileinfo->getRealPath() );
        } else {
            unlink( $fileinfo->getRealPath() );
        }
    }
}

function ai_persona_tests_reset_meta() {
    global $ai_persona_tests_meta_store;
    $ai_persona_tests_meta_store = array();
}

if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta( $post_id, $key = '', $single = false ) {
        global $ai_persona_tests_meta_store;

        $post_id = absint( $post_id );

        if ( ! isset( $ai_persona_tests_meta_store[ $post_id ] ) ) {
            return $single ? '' : array();
        }

        if ( '' === $key ) {
            return $ai_persona_tests_meta_store[ $post_id ];
        }

        if ( ! array_key_exists( $key, $ai_persona_tests_meta_store[ $post_id ] ) ) {
            return $single ? '' : array();
        }

        $value = $ai_persona_tests_meta_store[ $post_id ][ $key ];

        if ( $single ) {
            return is_array( $value ) ? reset( $value ) : $value;
        }

        return $value;
    }
}

if ( ! function_exists( 'update_post_meta' ) ) {
    function update_post_meta( $post_id, $meta_key, $meta_value ) { // phpcs:ignore
        global $ai_persona_tests_meta_store;

        $post_id = absint( $post_id );

        if ( ! isset( $ai_persona_tests_meta_store[ $post_id ] ) ) {
            $ai_persona_tests_meta_store[ $post_id ] = array();
        }

        $ai_persona_tests_meta_store[ $post_id ][ $meta_key ] = $meta_value;

        return true;
    }
}

if ( ! function_exists( 'delete_post_meta' ) ) {
    function delete_post_meta( $post_id, $meta_key ) { // phpcs:ignore
        global $ai_persona_tests_meta_store;

        $post_id = absint( $post_id );

        if ( isset( $ai_persona_tests_meta_store[ $post_id ][ $meta_key ] ) ) {
            unset( $ai_persona_tests_meta_store[ $post_id ][ $meta_key ] );
        }

        return true;
    }
}
