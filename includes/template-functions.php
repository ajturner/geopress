<?php
/**
 * GeoPress template / theme API functions.
 *
 * These global functions are intended for use in WordPress theme templates
 * within The Loop (where a $post global is available).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns true if the current post has a saved location.
 *
 * @return bool
 */
function has_location() {
	global $post;
	if ( empty( $post ) || empty( $post->ID ) ) {
		return false;
	}
	return (bool) GeoPress::get_geo( $post->ID );
}

/**
 * Returns the "lat lon" coordinate string for the current post, or ''.
 *
 * @return string
 */
function the_coord() {
	global $post;
	if ( empty( $post ) || empty( $post->ID ) ) {
		return '';
	}
	$geo = GeoPress::get_geo( $post->ID );
	return $geo ? (string) $geo->coord : '';
}

/**
 * Returns the geo microformat HTML for the current post's coordinates.
 *
 * @return string
 */
function the_geo_mf() {
	$coord = the_coord();
	if ( '' === $coord ) {
		return '';
	}

	$parts = explode( ' ', $coord, 2 );
	$lat   = isset( $parts[0] ) ? esc_html( $parts[0] ) : '';
	$lon   = isset( $parts[1] ) ? esc_html( $parts[1] ) : '';

	return "\n\t<div class='geo'><span class='latitude'>{$lat}</span>, <span class='longitude'>{$lon}</span></div>";
}

/**
 * Returns the address (loc field) for the current post.
 *
 * @return string
 */
function the_address() {
	global $post;
	if ( empty( $post ) || empty( $post->ID ) ) {
		return '';
	}
	$geo = GeoPress::get_geo( $post->ID );
	return $geo ? (string) $geo->loc : '';
}

/**
 * Returns the saved location name for the current post.
 *
 * @return string
 */
function the_location_name() {
	global $post;
	if ( empty( $post ) || empty( $post->ID ) ) {
		return '';
	}
	$geo = GeoPress::get_geo( $post->ID );
	return $geo ? (string) $geo->name : '';
}

/**
 * Returns the adr microformat HTML for the current post's address.
 *
 * @return string
 */
function the_adr_mf() {
	$addr = the_address();
	return '' !== $addr ? "\n\t<div class='adr'>" . esc_html( $addr ) . '</div>' : '';
}

/**
 * Returns the hCard microformat HTML for the current post's location name.
 *
 * @return string
 */
function the_loc_mf() {
	$loc_name = the_location_name();
	return '' !== $loc_name ? "\n\t<div class='vcard'><span class='fn'>" . esc_html( $loc_name ) . '</span></div>' : '';
}

/**
 * Returns the location name when a ?loc= ID is present in the URL.
 *
 * @return string
 */
function geopress_location_name() {
	if ( isset( $_GET['loc'] ) && '' !== $_GET['loc'] ) {
		$loc_id   = absint( wp_unslash( $_GET['loc'] ) );
		$location = GeoPress::get_location( $loc_id );
		return $location ? esc_html( $location->name ) : '';
	}
	return '';
}

/**
 * Outputs GeoRSS coordinate tags for the current post.
 * Format depends on the '_geopress_rss_format' option.
 */
function the_coord_rss() {
	$coord       = the_coord();
	$featurename = the_address();
	$rss_format  = get_option( '_geopress_rss_format', 'simple' );

	if ( '' === $coord ) {
		return;
	}

	switch ( $rss_format ) {
		case 'w3c':
			$parts = explode( ' ', $coord, 2 );
			$lat   = isset( $parts[0] ) ? esc_html( $parts[0] ) : '';
			$lon   = isset( $parts[1] ) ? esc_html( $parts[1] ) : '';
			echo "\t<geo:lat>{$lat}</geo:lat>\n\t\t<geo:lon>{$lon}</geo:lon>\n";
			break;

		case 'gml':
			echo "\t<georss:where>\n\t\t<gml:Point>\n\t\t\t<gml:pos>" . esc_html( $coord ) . "</gml:pos>\n\t\t</gml:Point>\n\t</georss:where>\n";
			break;

		case 'simple':
		default:
			echo "\t<georss:point>" . esc_html( $coord ) . "</georss:point>\n";
			if ( '' !== $featurename ) {
				echo "\t<georss:featurename>" . esc_html( $featurename ) . "</georss:featurename>\n";
			}
			break;
	}
}

/**
 * Returns a Yahoo Maps URL for the current post's coordinates.
 *
 * @return string
 */
function ymap_post_url() {
	$coord = the_coord();
	if ( '' === $coord ) {
		return '';
	}

	$parts = explode( ' ', $coord, 2 );
	$lat   = isset( $parts[0] ) ? rawurlencode( $parts[0] ) : '';
	$lon   = isset( $parts[1] ) ? rawurlencode( $parts[1] ) : '';

	return "https://maps.yahoo.com/int/index.php#lat={$lat}&lon={$lon}&mag=5&trf=0";
}

/**
 * Returns a Yahoo annotated maps URL pointing to the blog feed.
 *
 * @param string $type  Bloginfo key, e.g. 'rss2_url'.
 * @return string
 */
function ymap_blog_url( $type = 'rss2_url' ) {
	return GEOPRESS_YAHOO_ANNOTATEDMAPS . get_bloginfo( $type );
}

/**
 * Debug helper: returns an HTML-safe string representation of a locations array.
 *
 * @param array  $locations
 * @param string $msg
 * @return string
 */
function dump_locations( $locations, $msg = '' ) {
	$string = '+ Dumping: ' . esc_html( $msg ) . "<br />\n";
	if ( empty( $locations ) ) {
		$string .= "- Void locations<br />\n";
	}
	foreach ( (array) $locations as $loc ) {
		$string .= '- Location name: ' . esc_html( $loc->name ) . "<br />\n";
	}
	$string .= "+ End of locations<br />\n";
	return $string;
}
