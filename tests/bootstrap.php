<?php
/**
 * GeoPress test bootstrap.
 *
 * Loads the Composer autoloader (which brings in Brain Monkey / Mockery via
 * yoast/wp-test-utils) and the plugin's include files, so unit tests can run
 * without a real WordPress installation.
 *
 * WordPress functions are deliberately NOT stubbed here. Brain Monkey defines
 * them on demand via Patchwork when a test calls Functions\when()/stubs()/
 * expect(). Declaring them in this file would make them undefinable: Patchwork
 * can only redefine functions in files it instruments, and this bootstrap is
 * already executing by the time Patchwork initialises — which yields
 * "DefinedTooEarly" errors for every test that stubs them.
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
define( 'GEOPRESS_VERSION',              '3.0.1' );
define( 'GEOPRESS_DIR',                  dirname( __DIR__ ) . '/' );
define( 'GEOPRESS_URL',                  'http://example.com/wp-content/plugins/geopress/' );
define( 'GEOPRESS_BASENAME',             'geopress/geopress.php' );
define( 'GEOPRESS_FETCH_TIMEOUT',        5 );
define( 'GEOPRESS_USER_AGENT',           'GeoPress/3.0.1' );
define( 'GEOPRESS_GOOGLE_GEOCODER',      'https://maps.google.com/maps/geo?q=' );
define( 'GEOPRESS_GOOGLE_REGEXP',        '<coordinates>(.*),(.*),0</coordinates>' );
define( 'GEOPRESS_YAHOO_REGEXP',         '<Latitude>(.*)<\/Latitude>.*<Longitude>(.*)<\/Longitude>' );
define( 'GEOPRESS_YAHOO_GEOCODER',       'https://api.local.yahoo.com/MapsService/V1/geocode?appid=geocodewordpress&location=' );
define( 'GEOPRESS_YAHOO_ANNOTATEDMAPS',  'https://api.maps.yahoo.com/Maps/V1/AnnotatedMaps?appid=geocodewordpress&xmlsrc=' );
define( 'GEOPRESS_YAHOO_EMBEDPNGMAPURL', 'https://api.local.yahoo.com/MapsService/V1/mapImage?appid=geocodewordpress&' );

// ── Load plugin classes (no WordPress bootstrap needed) ───────────────────────

require_once GEOPRESS_DIR . 'includes/geocoding.php';
require_once GEOPRESS_DIR . 'includes/class-geopress.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-admin.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-maps.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-feeds.php';
require_once GEOPRESS_DIR . 'includes/template-functions.php';
