<?php
/**
 * GeoPress KML NetworkLink wrapper (for Google Earth auto-refresh).
 *
 * Copyright 2007  Barry Hunter - http://www.nearby.org.uk/blog/
 * Licensed under GPL-2.0+
 */

if ( empty( $wp ) ) {
	require_once '../../../wp-config.php';
}

$blog_charset = get_option( 'blog_charset', 'UTF-8' );
header( 'Content-type: application/vnd.google-earth.kml+xml; charset=' . $blog_charset, true );

$kml_url = home_url( '/wp-kml.php' );
if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
	$kml_url = add_query_arg(
		wp_parse_args( sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) ),
		$kml_url
	);
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
