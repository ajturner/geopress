<?php
/**
 * Geocoding functions for GeoPress.
 *
 * Uses the WordPress HTTP API (wp_remote_get) instead of the removed Snoopy
 * class, and SimpleXML instead of the removed domxml_open_file().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Geocode a location string using the specified geocoder (google or yahoo).
 * If $location is in [lat,lon] format it is parsed directly without HTTP.
 *
 * @param string $location  Address or "[lat, lon]" string.
 * @param string $geocoder  'google' or 'yahoo'.
 * @return array            [ $lat, $lon ] — both empty strings on failure.
 */
function geocode( $location, $geocoder = 'yahoo' ) {
	// Direct coordinates bypass the network call.
	if ( preg_match( '/\[(.+),[ ]?(.+)\]/', $location, $matches ) ) {
		return array( trim( $matches[1] ), trim( $matches[2] ) );
	}

	if ( 'google' === $geocoder ) {
		$url    = GEOPRESS_GOOGLE_GEOCODER . rawurlencode( $location );
		$regexp = GEOPRESS_GOOGLE_REGEXP;
	} else {
		$url    = GEOPRESS_YAHOO_GEOCODER . rawurlencode( $location );
		$regexp = GEOPRESS_YAHOO_REGEXP;
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout'    => GEOPRESS_FETCH_TIMEOUT,
			'user-agent' => GEOPRESS_USER_AGENT,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( '', '' );
	}

	$xml = wp_remote_retrieve_body( $response );
	$lat = '';
	$lon = '';

	if ( preg_match( '/' . $regexp . '/s', $xml, $latlong ) ) {
		$lat = trim( $latlong[1] );
		$lon = trim( $latlong[2] );
	}

	return array( $lat, $lon );
}

/**
 * Geocode using Yahoo's API, parsed via SimpleXML.
 *
 * @param string $location
 * @return array [ $lat, $lon ]
 */
function yahoo_geocode( $location ) {
	if ( preg_match( '/\[(.+),[ ]?(.+)\]/', $location, $matches ) ) {
		return array( trim( $matches[1] ), trim( $matches[2] ) );
	}

	$url = GEOPRESS_YAHOO_GEOCODER . rawurlencode( $location );

	$response = wp_remote_get(
		$url,
		array(
			'timeout'    => GEOPRESS_FETCH_TIMEOUT,
			'user-agent' => GEOPRESS_USER_AGENT,
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( '', '' );
	}

	$xml_string = wp_remote_retrieve_body( $response );
	$lat        = '';
	$lon        = '';

	libxml_use_internal_errors( true );
	$xml = simplexml_load_string( $xml_string );
	if ( $xml && isset( $xml->Result ) ) {
		$lat = (string) $xml->Result->Latitude;
		$lon = (string) $xml->Result->Longitude;
	}

	return array( $lat, $lon );
}

/**
 * Convert GeoPress zoom (1=world, 18=closest) to Yahoo zoom (1=close, 12=country).
 *
 * @param int $zoom
 * @return int
 */
function yahoo_zoom( $zoom ) {
	return (int) ceil( 12 / max( 1, (int) $zoom ) );
}

/**
 * Fetch a static map image URL from Yahoo Maps.
 *
 * @param string $location  Address or "[lat, lon]" string.
 * @return array            [ $warn, $mapurl ]
 */
function yahoo_mapurl( $location ) {
	$mapwidth  = (int) get_option( '_geopress_mapwidth', 400 );
	$mapheight = (int) get_option( '_geopress_mapheight', 200 );

	$url  = GEOPRESS_YAHOO_EMBEDPNGMAPURL;
	$url .= 'image_width=' . $mapwidth . '&image_height=' . $mapheight;
	$url .= '&zoom=' . yahoo_zoom( GeoPress::mapstraction_map_zoom() );

	if ( preg_match( '/\[(.+),[ ]?(.+)\]/', $location, $matches ) ) {
		$url .= '&latitude=' . rawurlencode( trim( $matches[1] ) ) . '&longitude=' . rawurlencode( trim( $matches[2] ) );
	} else {
		$url .= '&location=' . rawurlencode( $location );
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout'    => GEOPRESS_FETCH_TIMEOUT,
			'user-agent' => GEOPRESS_USER_AGENT,
		)
	);

	$warn   = '';
	$mapurl = '';

	if ( ! is_wp_error( $response ) ) {
		$xml = wp_remote_retrieve_body( $response );
		if ( preg_match( '/<Result xmlns:xsi="[^"]*"( warning="[^"]*")?>(.*?)<\/Result>/s', $xml, $mapinfo ) ) {
			$warn   = $mapinfo[1];
			$mapurl = $mapinfo[2];
		}
	}

	return array( $warn, $mapurl );
}
