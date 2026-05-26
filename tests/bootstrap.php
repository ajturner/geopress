<?php
/**
 * GeoPress test bootstrap.
 *
 * Loads Composer autoloader (which brings in Brain Monkey / Mockery via
 * yoast/wp-test-utils), then defines the WordPress stub functions that
 * GeoPress calls so unit tests can run without a real WordPress installation.
 */

// Patchwork MUST load before anything else: it registers a stream wrapper that
// instruments every subsequent require/include so Brain Monkey can later
// redefine functions in those files. If we let Composer's autoloader pull
// Patchwork in via its `files` section, the autoloader itself runs un-
// instrumented and any WP-function stubs declared during bootstrap throw
// "DefinedTooEarly". Patchwork.php is idempotent: composer's later autoload
// will see it as already loaded and skip it.
$patchwork = dirname( __DIR__ ) . '/vendor/antecedent/patchwork/Patchwork.php';
if ( ! file_exists( $patchwork ) ) {
    echo "Run `composer install` before running the tests.\n";
    exit( 1 );
}
require_once $patchwork;

// Composer autoloader — Brain Monkey, Mockery, etc.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

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
// Stubs live in a separate file required AFTER the Composer autoloader so that
// Patchwork (loaded transitively via Brain Monkey) can instrument the
// definitions. Defining them inline here triggers Patchwork's "DefinedTooEarly"
// error: Patchwork only instruments files required after it has been loaded.

require_once __DIR__ . '/wp-stubs.php';

// ── Load plugin classes (no WordPress bootstrap needed) ───────────────────────

require_once GEOPRESS_DIR . 'includes/geocoding.php';
require_once GEOPRESS_DIR . 'includes/class-geopress.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-admin.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-maps.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-feeds.php';
require_once GEOPRESS_DIR . 'includes/template-functions.php';
