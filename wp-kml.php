<?php
/**
 * GeoPress KML 2.2 feed output.
 *
 * Copyright 2007  Barry Hunter - http://www.nearby.org.uk/blog/
 * Licensed under GPL-2.0+
 */

// Optional: set to a full URL of a small image file, or leave blank to disable.
$kml_icon = '';

// Bootstrap WordPress if not already loaded (direct URL access).
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
	wp( 'feed=1&posts_per_page=-1' );
}

$blog_charset = get_option( 'blog_charset', 'UTF-8' );
header( 'Content-type: application/vnd.google-earth.kml+xml; charset=' . $blog_charset, true );
$more = 1;

echo '<?xml version="1.0" encoding="' . esc_attr( $blog_charset ) . '"?>' . "\n";
?>
<!-- generator="wordpress/<?php bloginfo_rss( 'version' ); ?>/GeoPress" -->
<kml xmlns="http://earth.google.com/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
<Document>
	<Style id="defaultIcon">
		<LabelStyle>
			<scale>0</scale>
		</LabelStyle>
		<?php if ( $kml_icon ) : ?>
		<IconStyle>
			<Icon>
				<href><?php echo esc_url( $kml_icon ); ?></href>
			</Icon>
		</IconStyle>
		<?php endif; ?>
	</Style>
	<Style id="hoverIcon">
		<IconStyle>
			<scale>2.1</scale>
			<?php if ( $kml_icon ) : ?>
			<Icon>
				<href><?php echo esc_url( $kml_icon ); ?></href>
			</Icon>
			<?php endif; ?>
		</IconStyle>
	</Style>
	<StyleMap id="defaultStyle">
		<Pair>
			<key>normal</key>
			<styleUrl>#defaultIcon</styleUrl>
		</Pair>
		<Pair>
			<key>highlight</key>
			<styleUrl>#hoverIcon</styleUrl>
		</Pair>
	</StyleMap>
	<name><?php bloginfo_rss( 'name' ); ?></name>
	<Snippet><![CDATA[<?php bloginfo_rss( 'url' ); ?><br/><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?>]]></Snippet>
	<description><?php bloginfo_rss( 'description' ); ?></description>
	<atom:link href="<?php bloginfo( 'atom_url' ); ?>"/>
	<?php while ( have_posts() ) : the_post();
		$coord = the_coord();
		if ( '' !== $coord ) :
			$coord_parts = explode( ' ', $coord, 2 );
			$kml_lat = isset( $coord_parts[0] ) ? (float) $coord_parts[0] : 0;
			$kml_lon = isset( $coord_parts[1] ) ? (float) $coord_parts[1] : 0;
			if ( $kml_lat || $kml_lon ) : ?>
	<Placemark id="<?php the_ID(); ?>">
		<name><?php the_title_rss(); ?></name>
		<Snippet><![CDATA[<?php the_excerpt_rss(); ?>]]></Snippet>
		<?php if ( strlen( get_the_content() ) > 0 ) : ?>
		<description><![CDATA[<?php the_content( '', 0, '' ); ?>]]></description>
		<?php endif; ?>
		<atom:author>
			<atom:name><?php the_author(); ?></atom:name>
		</atom:author>
		<styleUrl>#defaultStyle</styleUrl>
		<visibility>1</visibility>
		<Point>
			<extrude>1</extrude>
			<altitudeMode>relativeToGround</altitudeMode>
			<coordinates><?php echo $kml_lon . ',' . $kml_lat; ?>,25</coordinates>
		</Point>
	</Placemark>
		<?php endif; endif; endwhile; ?>
</Document>
</kml>
