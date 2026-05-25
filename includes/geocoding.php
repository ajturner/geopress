<?php
/**
 * Geocoding functions for GeoPress.
 *
 * Uses the Nominatim (OpenStreetMap) geocoding API via the WordPress HTTP API.
 * The defunct Yahoo and Google geocoders have been removed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Geocode a location string using Nominatim (OpenStreetMap).
 * If $location is in [lat,lon] format it is parsed directly without HTTP.
 *
 * @param string $location  Address or "[lat, lon]" string.
 * @param string $geocoder  Ignored; kept for backward-compat signature.
 * @return array            [ $lat, $lon ] — both empty strings on failure.
 */
function geocode( $location, $geocoder = 'nominatim' ) {
	// Direct coordinates bypass the network call.
	if ( preg_match( '/\[(.+),[ ]?(.+)\]/', $location, $matches ) ) {
		return array( trim( $matches[1] ), trim( $matches[2] ) );
	}

	$url = add_query_arg(
		array(
			'q'              => sanitize_text_field( $location ),
			'format'         => 'json',
			'limit'          => '1',
			'addressdetails' => '0',
		),
		'https://nominatim.openstreetmap.org/search'
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout'    => GEOPRESS_FETCH_TIMEOUT,
			'user-agent' => GEOPRESS_USER_AGENT,
			'headers'    => array( 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return array( '', '' );
	}

	$body    = wp_remote_retrieve_body( $response );
	$results = json_decode( $body, true );

	if ( empty( $results ) || ! isset( $results[0]['lat'], $results[0]['lon'] ) ) {
		return array( '', '' );
	}

	return array( (string) $results[0]['lat'], (string) $results[0]['lon'] );
}

/**
 * Backward-compatible wrapper — delegates to geocode().
 *
 * @param string $location
 * @return array [ $lat, $lon ]
 */
function yahoo_geocode( $location ) {
	return geocode( $location );
}

/**
 * Convert GeoPress zoom (1=world, 18=closest) to a legacy Yahoo zoom value.
 * Kept for backward compatibility; callers can treat the return as a generic zoom.
 *
 * @param int $zoom
 * @return int
 */
function yahoo_zoom( $zoom ) {
	return (int) ceil( 12 / max( 1, (int) $zoom ) );
}

/**
 * Yahoo static-map API is defunct. Returns empty strings.
 *
 * @param string $location  Unused.
 * @return array            [ '', '' ]
 */
function yahoo_mapurl( $location ) {
	return array( '', '' );
}
