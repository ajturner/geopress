<?php
/**
 * Plugin Name: GeoPress
 * Plugin URI:  https://georss.org/geopress/
 * Description: GeoPress adds geographic tagging of your posts and blog. Enter an address or latitude/longitude, embed interactive maps, and export GeoRSS/KML/GPX feeds.
 * Version:     3.1
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

define( 'GEOPRESS_VERSION',              '3.1' );
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

// ── Post editor metabox (Classic + Gutenberg) ────────────────────────────────

add_action( 'add_meta_boxes', array( 'GeoPress_Admin', 'register_meta_boxes' ) );

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

// ── Shortcodes (for use in Gutenberg's Shortcode block) ───────────────────────
// [geopress_map height="" width="" locations="-1" zoom_level="-1" url=""]
add_shortcode( 'geopress_map', function ( $atts ) {
	$a = shortcode_atts( array(
		'height'     => '',
		'width'      => '',
		'locations'  => -1,
		'zoom_level' => -1,
		'url'        => '',
	), $atts, 'geopress_map' );
	return geopress_map( $a['height'], $a['width'], (int) $a['locations'], true, false, (int) $a['zoom_level'], $a['url'] );
} );

// [geopress_post_map height="" width="" overlay=""]
add_shortcode( 'geopress_post_map', function ( $atts ) {
	$a = shortcode_atts( array(
		'height'  => '',
		'width'   => '',
		'overlay' => '',
	), $atts, 'geopress_post_map' );
	return geopress_post_map( $a['height'], $a['width'], true, $a['overlay'] );
} );

// [geopress_page_map height="" width=""]
add_shortcode( 'geopress_page_map', function ( $atts ) {
	$a = shortcode_atts( array(
		'height' => '',
		'width'  => '',
	), $atts, 'geopress_page_map' );
	return geopress_page_map( $a['height'], $a['width'] );
} );

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
