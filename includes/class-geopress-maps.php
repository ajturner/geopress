<?php
/**
 * GeoPress map rendering.
 *
 * Contains the GeoPress_Maps class (used internally by the admin) and
 * the global template functions used by themes and post content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GeoPress_Maps {

	/**
	 * Outputs a JS-driven map of a set of location rows (used on the admin
	 * Locations page alongside the location table).
	 *
	 * @param array|null $locations  Array of location objects, or null to query all.
	 */
	public static function map_saved_locations( $locations = null ) {
		global $wpdb;

		if ( null === $locations ) {
			$table     = $wpdb->prefix . 'geopress';
			$locations = $wpdb->get_results( "SELECT * FROM {$table}" );
		}

		$geopress_marker = get_option( '_geopress_marker', GEOPRESS_URL . 'images/marker.svg' );

		$output  = geopress_map_select( 250, 250, 'float:right;' );
		$output .= '<script type="text/javascript">' . "\n";
		$output .= "geopress_addEvent(window,'load', function() {\n";

		foreach ( $locations as $row ) {
			if ( '' === trim( $row->coord ) ) {
				continue;
			}
			$coords  = preg_split( '/\s+/', trim( $row->coord ) );
			$lat     = isset( $coords[0] ) ? (float) $coords[0] : 0;
			$lon     = isset( $coords[1] ) ? (float) $coords[1] : 0;
			$label   = esc_js( $row->name );
			$icon    = esc_js( $geopress_marker );
			$blank   = esc_js( GEOPRESS_URL . 'blank.gif' );

			$output .= "\tvar myPoint = new LatLonPoint({$lat}, {$lon});\n";
			$output .= "\tvar marker = new Marker(myPoint);\n";
			$output .= "\tgeo_map.addMarkerWithData(marker,{ infoBubble: \"{$label}\", icon:\"{$icon}\", iconSize:[24,24], iconShadow:\"{$blank}\", iconShadowSize:[0,0] });\n";
		}

		$output .= "});\n</script>\n";

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	/**
	 * Outputs a display-only map of all stored locations using the configured
	 * map provider and dimensions. Used on the admin Locations page.
	 *
	 * @param array $locations  Array of location objects from the geopress table.
	 */
	public static function admin_locations_map( $locations ) {
		$width      = (int) get_option( '_geopress_mapwidth', 400 );
		$height     = (int) get_option( '_geopress_mapheight', 200 );
		$format     = GeoPress::mapstraction_map_format();
		$marker     = get_option( '_geopress_marker', GEOPRESS_URL . 'images/marker.svg' );
		$has_coords = false;

		$rows_js = '';
		foreach ( $locations as $row ) {
			if ( '' === trim( $row->coord ) ) {
				continue;
			}
			$has_coords = true;
			$coords     = preg_split( '/\s+/', trim( $row->coord ) );
			$lat        = isset( $coords[0] ) ? (float) $coords[0] : 0;
			$lon        = isset( $coords[1] ) ? (float) $coords[1] : 0;
			$label      = esc_js( $row->name );
			$icon       = esc_js( $marker );
			$rows_js   .= "var mp=new Marker(new LatLonPoint({$lat},{$lon}));\n";
			$rows_js   .= "lmap.addMarkerWithData(mp,{infoBubble:\"{$label}\",icon:\"{$icon}\",iconSize:[24,24]});\n";
		}

		if ( ! $has_coords ) {
			return;
		}
		?>
		<h3><?php esc_html_e( 'Locations Map', 'geopress' ); ?></h3>
		<div id="gp_locs_map" class="mapstraction"
			style="width:<?php echo $width; ?>px; height:<?php echo $height; ?>px; margin: 0.5em 0 1em;"></div>
		<script type="text/javascript">
		geopress_addEvent(window, 'load', function() {
			var lmap = new Mapstraction('gp_locs_map', '<?php echo esc_js( $format ); ?>');
			lmap.addControls({pan:true, zoom:'small', overview:false, scale:true, map_type:true});
			<?php echo $rows_js; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			lmap.autoCenterAndZoom();
		});
		</script>
		<?php
	}
}

// ── Standalone map template functions ─────────────────────────────────────────

/**
 * Returns an HTML/JS map of the last N geotagged posts.
 *
 * @param string $height          Map height in px ('' = use saved option).
 * @param string $width           Map width in px ('' = use saved option).
 * @param int    $locations       Number of locations to show (-1 = all).
 * @param bool   $unique_id       Whether to append a random suffix to the map element ID.
 * @param bool   $loop_locations  True to pull locations from the current WP loop.
 * @param int    $zoom_level      Override zoom level (-1 = auto).
 * @param string $url             Optional KML/GeoRSS overlay URL.
 * @return string
 */
function geopress_map( $height = '', $width = '', $locations = -1, $unique_id = false, $loop_locations = false, $zoom_level = -1, $url = '' ) {
	$map_format      = GeoPress::mapstraction_map_format();
	$geopress_marker = get_option( '_geopress_marker', GEOPRESS_URL . 'images/marker.svg' );

	if ( '' === $height || '' === $width ) {
		$height = (int) get_option( '_geopress_mapheight', 200 );
		$width  = (int) get_option( '_geopress_mapwidth', 400 );
	}

	$map_id = $unique_id ? geopress_rand_id() : '';

	$locs = $loop_locations
		? GeoPress::get_loop_locations( $locations )
		: GeoPress::get_location_posts( $locations );

	$output  = '<div id="geo_map' . esc_attr( $map_id ) . '" class="mapstraction" style="height: ' . (int) $height . 'px; width: ' . (int) $width . 'px;"></div>' . "\n";
	$output .= '<!-- GeoPress Map --><script type="text/javascript">' . "\n";
	$output .= "//<![CDATA[\n";
	$output .= "var geo_map;\ngeopress_addEvent(window,'load', function() {\n";
	$output .= 'geo_map' . $map_id . ' = new Mapstraction("geo_map' . $map_id . '","' . esc_js( $map_format ) . '");' . "\n";
	$output .= 'geo_map' . $map_id . '.setCenterAndZoom(new LatLonPoint(0,0), 1);' . "\n";
	$output .= 'geo_map' . $map_id . '.addControls(' . GeoPress::mapstraction_map_controls() . ');' . "\n";
	if ( 'openstreetmap' !== $map_format ) {
		$output .= 'geo_map' . $map_id . '.setMapType(' . GeoPress::mapstraction_map_type() . ');' . "\n";
	}
	$output .= "var markers = []; var i = 0;\n";

	foreach ( $locs as $posts ) {
		$loc    = $posts[0];
		$coords = preg_split( '/\s+/', trim( $loc->coord ) );
		$lat    = isset( $coords[0] ) ? (float) $coords[0] : 0;
		$lon    = isset( $coords[1] ) ? (float) $coords[1] : 0;

		$output .= "i = markers.push(new Marker(new LatLonPoint({$lat}, {$lon})));\n";

		$details  = ' @ <strong>' . esc_js( $loc->name ) . '</strong><br/>';
		$blog_url = site_url();
		foreach ( $posts as $post ) {
			$details .= "<a href='" . esc_url( $blog_url . '/?p=' . $post->ID ) . "' title='" . esc_js( $post->post_title ) . "'>" . esc_js( $post->post_title ) . "</a><br/>";
		}

		$marker_icon = esc_js( $geopress_marker );
		$blank_gif   = esc_js( GEOPRESS_URL . 'blank.gif' );
		$post_date   = esc_js( $post->post_date );
		$output .= "\tgeo_map{$map_id}.addMarkerWithData(markers[i-1],{ infoBubble: \"{$details}\", date: \"new Date('{$post_date}')\", icon:\"{$marker_icon}\", iconSize:[24,24], iconShadow:\"{$blank_gif}\", iconShadowSize:[0,0] });\n";
	}

	$output .= "geo_map{$map_id}.autoCenterAndZoom();\n";

	if ( (int) $zoom_level > 0 ) {
		$output .= "geo_map{$map_id}.setZoom(" . (int) $zoom_level . ");\n";
	}

	if ( '' !== $url ) {
		$output .= 'geo_map' . $map_id . '.addOverlay("' . esc_js( $url ) . '");' . "\n";
	}

	$output .= "});\n//]]>\n</script><!-- end GeoPress Map -->\n";

	return $output;
}

/**
 * Returns a map of posts in the current WP_Query loop.
 *
 * @param string $height
 * @param string $width
 * @param int    $locations
 * @param int    $zoom_level
 * @return string
 */
function geopress_map_loop( $height = '', $width = '', $locations = -1, $zoom_level = -1 ) {
	return geopress_map( $height, $width, $locations, false, true, $zoom_level );
}

/**
 * Returns a map for the current page and its child pages.
 *
 * @param string $height
 * @param string $width
 * @param bool   $controls
 * @return string
 */
function geopress_page_map( $height = '', $width = '', $controls = true ) {
	global $post, $geopress_map_index;

	$children = get_children( array(
		'post_parent' => $post->ID,
		'post_type'   => 'page',
		'orderby'     => 'menu_order',
		'order'       => 'ASC',
	) );

	if ( empty( $children ) ) {
		return geopress_post_map( $height, $width, $controls );
	}

	if ( '' === $height || '' === $width ) {
		$width  = (int) get_option( '_geopress_mapwidth', 400 );
		$height = (int) get_option( '_geopress_mapheight', 200 ) * 2;
	}

	$map_id       = $post->ID . $geopress_map_index;
	$map_controls = $controls ? GeoPress::mapstraction_map_controls() : 'false';
	$geo          = GeoPress::get_geo( $post->ID );

	$output  = '<div id="geo_map' . esc_attr( $map_id ) . '" class="mapstraction" style="height: ' . (int) $height . 'px; width: ' . (int) $width . 'px;"></div>';
	$output .= '<!-- GeoPress Map --><script type="text/javascript">';
	$output .= "geopress_addEvent(window,'load', function() { geopress_maketravelmap(";
	$output .= $map_id . ',{';

	$point_list = array();
	foreach ( $children as $key => $value ) {
		$child_geo = GeoPress::get_geo( (int) $key );
		if ( ! $child_geo ) {
			continue;
		}
		$coords = preg_split( '/\s+/', trim( $child_geo->coord ) );
		$lat    = isset( $coords[0] ) ? (float) $coords[0] : 0;
		$lon    = isset( $coords[1] ) ? (float) $coords[1] : 0;
		$point_list[] = $key . ':{lat:' . $lat . ',lng:' . $lon . ',name:"' . esc_js( $child_geo->name ) . '"}';
	}

	$output .= implode( ',', $point_list );
	$output .= '},"' . esc_js( GeoPress::mapstraction_map_format( $geo ? $geo->map_format : '' ) ) . '",';
	$output .= GeoPress::mapstraction_map_type( $geo ? $geo->map_type : '' ) . ', ' . $map_controls . ')';
	$output .= "}); </script><!-- end GeoPress Map -->\n";

	return $output;
}

/** Global map index to generate unique element IDs for multiple maps per page. */
$geopress_map_index = 1;

/**
 * Returns a map for the current single post.
 *
 * @param string $height
 * @param string $width
 * @param bool   $controls
 * @param string $overlay  Optional KML/GeoRSS overlay URL.
 * @return string
 */
function geopress_post_map( $height = '', $width = '', $controls = true, $overlay = '' ) {
	global $post, $geopress_map_index;

	$geopress_marker = get_option( '_geopress_marker', GEOPRESS_URL . 'images/marker.svg' );
	$geo             = GeoPress::get_geo( $post->ID );

	if ( ! $geo ) {
		return '';
	}

	if ( is_feed() ) {
		return '<img src="' . esc_url( $geo->mapurl ) . '" title="' . esc_attr( 'GeoPress map of ' . $geo->name ) . '" />';
	}

	if ( '' === $height || '' === $width ) {
		$height = (int) get_option( '_geopress_mapheight', 200 );
		$width  = (int) get_option( '_geopress_mapwidth', 400 );
	}

	$map_id       = $post->ID . $geopress_map_index;
	$coords       = preg_split( '/\s+/', trim( $geo->coord ) );
	$lat          = isset( $coords[0] ) ? (float) $coords[0] : 0;
	$lon          = isset( $coords[1] ) ? (float) $coords[1] : 0;
	$map_controls = $controls ? GeoPress::mapstraction_map_controls() : 'false';

	$output  = '<div id="geo_map' . esc_attr( $map_id ) . '" class="mapstraction" style="height: ' . (int) $height . 'px; width: ' . (int) $width . 'px;"></div>';
	$output .= '<!-- GeoPress Map --><script type="text/javascript">';
	$output .= 'geopress_addEvent(window,"load", function() { geopress_makemap(';
	$output .= $map_id . ',"' . esc_js( $geo->name ) . '",' . $lat . ',' . $lon . ',"';
	$output .= esc_js( GeoPress::mapstraction_map_format( $geo->map_format ) ) . '",';
	$output .= GeoPress::mapstraction_map_type( $geo->map_type ) . ', ' . $map_controls . ',';
	$output .= GeoPress::mapstraction_map_zoom( $geo->map_zoom ) . ', "' . esc_js( $geopress_marker ) . '"';
	$output .= ')';

	if ( '' !== $overlay ) {
		// geo_maps[num_maps] is the map just created by geopress_makemap above.
		$output .= '; geo_maps[num_maps].addOverlay("' . esc_js( $overlay ) . '")';
	}

	$output .= '; });';
	$output .= "</script><!-- end GeoPress Map -->\n";

	$geopress_map_index++;

	return $output;
}

/**
 * Returns the small admin-side location-picker map (used in the post editor
 * and the Locations admin page).
 *
 * @param int    $height
 * @param int    $width
 * @param string $style  CSS inline style for the map div.
 * @return string
 */
function geopress_map_select( $height = 250, $width = 400, $style = 'float: left;' ) {
	$map_format    = GeoPress::mapstraction_map_format();
	$map_view_type = get_option( '_geopress_map_type', 'hybrid' );

	$output  = '<div id="geo_map" class="mapstraction" style="width: ' . (int) $width . 'px; height: ' . (int) $height . 'px; ' . esc_attr( $style ) . '"></div>';
	$output .= '<!-- GeoPress Map --><script type="text/javascript">';
	$output .= "//<![CDATA[\n";
	$output .= "var geo_map;\ngeopress_addEvent(window,'load', function() {\n";
	$output .= 'geo_map = new Mapstraction("geo_map","' . esc_js( $map_format ) . '");' . "\n";
	$output .= "var myPoint = new LatLonPoint(20,-20);\n";
	$output .= 'geo_map.addControls(' . GeoPress::mapstraction_map_controls( 'true', 'small', 'false', 'true', 'true' ) . ');' . "\n";
	$output .= "geo_map.setCenterAndZoom(myPoint,1);\n";
	$output .= 'geo_map.setMapType(' . GeoPress::mapstraction_map_type( $map_view_type ) . ');' . "\n";
	$output .= 'geo_map.addEventListener("click", function(p){ setClickPoint(p); });' . "\n";
	$output .= "});\n//]]>\n</script><!-- end GeoPress Map -->\n";

	return $output;
}

/**
 * Returns a random integer for unique HTML element IDs.
 *
 * @return int
 */
function geopress_rand_id() {
	return wp_rand( 0, 1000000 );
}

/**
 * Outputs an HTML link to the KML NetworkLink file.
 */
function geopress_kml_link() {
	$url = GEOPRESS_URL . 'wp-kml-link.php';
	echo '<a href="' . esc_url( $url ) . '" title="KML Link">KML</a>';
}

/**
 * Outputs an unordered list of saved locations with query links.
 */
function geopress_locations_list() {
	$locations = GeoPress::get_locations();
	foreach ( $locations as $loc ) {
		$url = add_query_arg( 'location', $loc->name, home_url( '/' ) );
		echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $loc->name ) . '</a></li>';
	}
}
