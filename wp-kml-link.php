<?php
/**
 * GeoPress KML NetworkLink wrapper (for Google Earth auto-refresh).
 *
 * Copyright 2007  Barry Hunter - http://www.nearby.org.uk/blog/
 * Licensed under GPL-2.0+
 */

if ( empty( $wp ) ) {
	$wp_load_paths = array(
		dirname( __FILE__, 3 ) . '/wp-load.php',
		dirname( __FILE__, 4 ) . '/wp-load.php',
	);

	$wp_loaded = false;
	foreach ( $wp_load_paths as $wp_load_path ) {
		if ( file_exists( $wp_load_path ) ) {
			require_once $wp_load_path;
			$wp_loaded = true;
			break;
		}
	}

	if ( ! $wp_loaded ) {
		header( 'HTTP/1.1 500 Internal Server Error', true, 500 );
		exit;
	}
}

$blog_charset = get_option( 'blog_charset', 'UTF-8' );
header( 'Content-type: application/vnd.google-earth.kml+xml; charset=' . $blog_charset, true );

// wp-kml.php lives in the plugin root; build its URL from the plugin directory.
$kml_url = plugin_dir_url( __FILE__ ) . 'wp-kml.php';

if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
	$raw_qs = wp_parse_args( wp_unslash( $_SERVER['QUERY_STRING'] ) );

	$sanitized = array();
	foreach ( $raw_qs as $key => $value ) {
		$sanitized[ sanitize_key( $key ) ] = is_array( $value )
			? array_map( 'sanitize_text_field', $value )
			: sanitize_text_field( $value );
	}

	$kml_url = add_query_arg( $sanitized, $kml_url );
}

echo '<?xml version="1.0" encoding="' . esc_attr( $blog_charset ) . '"?>' . "\n";
?>
<!-- generator="wordpress/<?php bloginfo_rss( 'version' ); ?>/KMLpress" -->
<kml xmlns="http://earth.google.com/kml/2.0">
<NetworkLink>
	<name><?php bloginfo_rss( 'name' ); ?></name>
	<Snippet><![CDATA[<?php bloginfo_rss( 'url' ); ?>]]></Snippet>
	<description><?php bloginfo_rss( 'description' ); ?></description>
	<open>0</open>
	<Url>
		<href><?php echo esc_url( $kml_url ); ?></href>
		<refreshMode>onInterval</refreshMode>
		<refreshInterval>3600</refreshInterval>
	</Url>
	<visibility>1</visibility>
</NetworkLink>
</kml>
