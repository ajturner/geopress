<?php
/**
 * GeoPress GPX 1.0 feed output.
 *
 * Copyright 2007  Barry Hunter - http://www.nearby.org.uk/blog/
 * Licensed under GPL-2.0+
 */

if ( empty( $wp ) ) {
	require_once 'wp-config.php';
	wp();
}

$blog_charset = get_option( 'blog_charset', 'UTF-8' );
header( 'Content-type: application/gpx+xml; charset=' . $blog_charset, true );
$more = 1;

echo '<?xml version="1.0" encoding="' . esc_attr( $blog_charset ) . '"?>' . "\n";
?>
<!-- generator="wordpress/<?php bloginfo_rss( 'version' ); ?>" -->
<gpx
	version="1.0"
	creator="GeoPress"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xmlns="http://www.topografix.com/GPX/1/0"
	xsi:schemaLocation="http://www.topografix.com/GPX/1/0 http://www.topografix.com/GPX/1/0/gpx.xsd">
<time><?php echo esc_html( current_time( 'c' ) ); ?></time>
<?php
if ( $posts ) {
	foreach ( $posts as $post ) {
		setup_postdata( $post );
		$coord = the_coord();
		if ( '' !== $coord ) {
			$coord_parts = explode( ' ', $coord, 2 );
			$gpx_lat = isset( $coord_parts[0] ) ? (float) $coord_parts[0] : 0;
			$gpx_lon = isset( $coord_parts[1] ) ? (float) $coord_parts[1] : 0;
			if ( $gpx_lat || $gpx_lon ) :
?>
	<wpt lat="<?php echo esc_attr( $gpx_lat ); ?>" lon="<?php echo esc_attr( $gpx_lon ); ?>">
		<name><?php the_title_rss(); ?></name>
		<desc><![CDATA[<?php the_content_rss(); ?>]]></desc>
		<type><?php foreach ( get_the_category() as $cat ) { echo esc_html( $cat->cat_name ) . ' '; } ?></type>
		<time><?php the_time( 'c' ); ?></time>
	</wpt>
<?php
			endif;
		}
	}
	wp_reset_postdata();
}
?>
</gpx>
