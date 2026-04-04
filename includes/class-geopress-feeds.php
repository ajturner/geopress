<?php
/**
 * GeoPress feed output — GeoRSS, Atom, RSS 2.0, RDF.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GeoPress_Feeds {

	/**
	 * Outputs the XML namespace declaration for the active GeoRSS format.
	 * Attached to: atom_ns, rss2_ns, rdf_ns, rss_ns
	 */
	public static function geopress_namespace() {
		if ( 'true' !== get_option( '_geopress_rss_enable', 'true' ) ) {
			return;
		}

		switch ( get_option( '_geopress_rss_format', 'simple' ) ) {
			case 'w3c':
				echo 'xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"' . "\n";
				break;
			case 'gml':
			case 'simple':
			default:
				echo 'xmlns:georss="http://www.georss.org/georss" xmlns:gml="http://www.opengis.net/gml"' . "\n";
		}
	}

	/**
	 * Outputs GeoRSS coordinate tags for an Atom entry.
	 * Attached to: atom_entry
	 *
	 * @param int $post_id
	 */
	public static function atom_entry( $post_id ) {
		if ( 'true' !== get_option( '_geopress_rss_enable', 'true' ) ) {
			return;
		}
		$coord = the_coord();
		if ( '' !== $coord ) {
			the_coord_rss();
		}
	}

	/**
	 * Outputs GeoRSS coordinate tags for an RSS 2.0 / RDF item.
	 * Attached to: rss2_item, rdf_item, rss_item
	 *
	 * @param int $post_id
	 */
	public static function rss2_item( $post_id ) {
		if ( 'true' !== get_option( '_geopress_rss_enable', 'true' ) ) {
			return;
		}
		the_coord_rss();
	}
}
