<?php
/**
 * Plugin Name: GeoPress
 * Plugin URI:  https://georss.org/geopress/
 * Description: GeoPress adds geographic tagging of your posts and blog. Enter an address or latitude/longitude, embed interactive maps, and export GeoRSS/KML/GPX feeds.
 * Version:     3.0
 * Author:      Andrew Turner & Mikel Maron
 * Author URI:  https://highearthorbit.com
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: geopress
 *
 * Copyright 2006-8 Andrew Turner, Mikel Maron
 * Copyright 2005   Ravi Dronamraju
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Constants ────────────────────────────────────────────────────────────────

define( 'GEOPRESS_VERSION',              '3.0' );
define( 'GEOPRESS_DIR',                  plugin_dir_path( __FILE__ ) );
define( 'GEOPRESS_URL',                  plugin_dir_url( __FILE__ ) );
define( 'GEOPRESS_BASENAME',             plugin_basename( __FILE__ ) );
define( 'GEOPRESS_FETCH_TIMEOUT',        5 );
define( 'GEOPRESS_USER_AGENT',           'GeoPress/' . GEOPRESS_VERSION );

// Geocoder endpoints (Yahoo/Google APIs are legacy; kept for compatibility).
define( 'GEOPRESS_GOOGLE_GEOCODER',      'https://maps.google.com/maps/geo?q=' );
define( 'GEOPRESS_GOOGLE_REGEXP',        '<coordinates>(.*),(.*),0</coordinates>' );
define( 'GEOPRESS_YAHOO_REGEXP',         '<Latitude>(.*)<\/Latitude>.*<Longitude>(.*)<\/Longitude>' );
define( 'GEOPRESS_YAHOO_GEOCODER',       'https://api.local.yahoo.com/MapsService/V1/geocode?appid=geocodewordpress&location=' );
define( 'GEOPRESS_YAHOO_ANNOTATEDMAPS',  'https://api.maps.yahoo.com/Maps/V1/AnnotatedMaps?appid=geocodewordpress&xmlsrc=' );
define( 'GEOPRESS_YAHOO_EMBEDPNGMAPURL', 'https://api.local.yahoo.com/MapsService/V1/mapImage?appid=geocodewordpress&' );

// ── Includes ─────────────────────────────────────────────────────────────────

require_once GEOPRESS_DIR . 'includes/geocoding.php';
require_once GEOPRESS_DIR . 'includes/class-geopress.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-admin.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-maps.php';
require_once GEOPRESS_DIR . 'includes/class-geopress-feeds.php';
require_once GEOPRESS_DIR . 'includes/template-functions.php';

// ── Activation ───────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, array( 'GeoPress', 'install' ) );

// ── Post editor metabox ──────────────────────────────────────────────────────

add_action( 'edit_form_advanced',     array( 'GeoPress_Admin', 'location_edit_form' ) );
add_action( 'edit_form_after_editor', array( 'GeoPress_Admin', 'location_edit_form' ) );

// ── Location query hooks ─────────────────────────────────────────────────────

add_action( 'template_redirect', array( 'GeoPress', 'location_redirect' ) );
add_filter( 'posts_join',        array( 'GeoPress', 'join_clause' ) );
add_filter( 'posts_where',       array( 'GeoPress', 'where_clause' ) );

// ── Post save ────────────────────────────────────────────────────────────────

add_action( 'save_post', array( 'GeoPress', 'update_post' ) );

// ── Script / style enqueue ───────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts',    array( 'GeoPress', 'enqueue_scripts' ) );
add_action( 'admin_enqueue_scripts', array( 'GeoPress', 'enqueue_admin_scripts' ) );

// ── Content filters ──────────────────────────────────────────────────────────

add_filter( 'the_content', array( 'GeoPress', 'embed_map_inpost' ) );
add_filter( 'the_content', array( 'GeoPress', 'embed_data_inpost' ) );

// ── Admin menu ───────────────────────────────────────────────────────────────

add_action( 'admin_menu', array( 'GeoPress_Admin', 'admin_menu' ) );

// ── XML Feed hooks ───────────────────────────────────────────────────────────

add_action( 'atom_ns',    array( 'GeoPress_Feeds', 'geopress_namespace' ) );
add_action( 'atom_entry', array( 'GeoPress_Feeds', 'atom_entry' ) );
add_action( 'rss2_ns',    array( 'GeoPress_Feeds', 'geopress_namespace' ) );
add_action( 'rss2_item',  array( 'GeoPress_Feeds', 'rss2_item' ) );
add_action( 'rdf_ns',     array( 'GeoPress_Feeds', 'geopress_namespace' ) );
add_action( 'rdf_item',   array( 'GeoPress_Feeds', 'rss2_item' ) );
add_action( 'rss_ns',     array( 'GeoPress_Feeds', 'geopress_namespace' ) );
add_action( 'rss_item',   array( 'GeoPress_Feeds', 'rss2_item' ) );
