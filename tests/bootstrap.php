<?php
/**
 * GeoPress test bootstrap.
 *
 * Loads Composer autoloader (which brings in Brain Monkey / Mockery via
 * yoast/wp-test-utils), then defines the WordPress stub functions that
 * GeoPress calls so unit tests can run without a real WordPress installation.
 */

// Composer autoloader — required before anything else.
$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( ! file_exists( $autoloader ) ) {
    echo "Run `composer install` before running the tests.\n";
    exit( 1 );
}
require_once $autoloader;

// Plugin constants needed by the includes files.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
define( 'GEOPRESS_VERSION',              '3.0' );
define( 'GEOPRESS_DIR',                  dirname( __DIR__ ) . '/' );
define( 'GEOPRESS_URL',                  'http://example.com/wp-content/plugins/geopress/' );
define( 'GEOPRESS_BASENAME',             'geopress/geopress.php' );
define( 'GEOPRESS_FETCH_TIMEOUT',        5 );
define( 'GEOPRESS_USER_AGENT',           'GeoPress/3.0' );
define( 'GEOPRESS_GOOGLE_GEOCODER',      'https://maps.google.com/maps/geo?q=' );
define( 'GEOPRESS_GOOGLE_REGEXP',        '<coordinates>(.*),(.*),0</coordinates>' );
define( 'GEOPRESS_YAHOO_REGEXP',         '<Latitude>(.*)<\/Latitude>.*<Longitude>(.*)<\/Longitude>' );
define( 'GEOPRESS_YAHOO_GEOCODER',       'https://api.local.yahoo.com/MapsService/V1/geocode?appid=geocodewordpress&location=' );
define( 'GEOPRESS_YAHOO_ANNOTATEDMAPS',  'https://api.maps.yahoo.com/Maps/V1/AnnotatedMaps?appid=geocodewordpress&xmlsrc=' );
define( 'GEOPRESS_YAHOO_EMBEDPNGMAPURL', 'https://api.local.yahoo.com/MapsService/V1/mapImage?appid=geocodewordpress&' );

// ── WordPress function stubs ───────────────────────────────────────────────────
// These are minimal stubs so the plugin's include files can be loaded.
// Tests use Brain Monkey to set expectations on these stubs per-test.

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) { return strip_tags( trim( $str ) ); }
}
if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $str ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $str ) ); }
}
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $str ) { return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $str ) { return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ) ?: ''; }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ) ?: ''; }
}
if ( ! function_exists( 'esc_js' ) ) {
    function esc_js( $str ) { return addslashes( $str ); }
}
if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( $value );
    }
}
if ( ! function_exists( 'absint' ) ) {
    function absint( $n ) { return abs( (int) $n ); }
}
if ( ! function_exists( 'add_option' ) ) {
    function add_option( $key, $value = '' ) {}
}
if ( ! function_exists( 'update_option' ) ) {
    function update_option( $key, $value ) {}
}
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $key, $default = false ) { return $default; }
}
if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta( $post_id, $key = '', $single = false ) { return $single ? '' : array(); }
}
if ( ! function_exists( 'update_post_meta' ) ) {
    function update_post_meta( $post_id, $key, $value ) { return true; }
}
if ( ! function_exists( 'add_post_meta' ) ) {
    function add_post_meta( $post_id, $key, $value ) { return 1; }
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce( $nonce, $action = -1 ) { return 1; }
}
if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( $cap ) { return true; }
}
if ( ! function_exists( 'get_post' ) ) {
    function get_post( $post_id ) { return null; }
}
if ( ! function_exists( 'is_single' ) ) {
    function is_single() { return false; }
}
if ( ! function_exists( 'is_feed' ) ) {
    function is_feed() { return false; }
}
if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '/' ) { return 'http://example.com' . $path; }
}
if ( ! function_exists( 'site_url' ) ) {
    function site_url( $path = '' ) { return 'http://example.com' . $path; }
}
if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( $args, $url = '' ) { return $url . '?' . http_build_query( $args ); }
}
if ( ! function_exists( 'wp_rand' ) ) {
    function wp_rand( $min = 0, $max = PHP_INT_MAX ) { return rand( $min, $max ); }
}
if ( ! function_exists( 'dbDelta' ) ) {
    function dbDelta( $sql ) {}
}
if ( ! function_exists( 'wp_remote_get' ) ) {
    function wp_remote_get( $url, $args = array() ) { return array( 'body' => '', 'response' => array( 'code' => 200 ) ); }
}
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ) { return is_array( $response ) ? ( $response['body'] ?? '' ) : ''; }
}
if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ) { return false; }
}

// ── Load plugin classes (no WordPress bootstrap needed) ───────────────────────

require_once GEOPRESS_DIR . 'includes/geocoding.php';
require_once GEOPRESS_DIR . 'includes/class-geopress.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-admin.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-maps.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-feeds.php';
require_once GEOPRESS_DIR . 'includes/template-functions.php';
