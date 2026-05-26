<?php
/**
 * Core GeoPress class.
 *
 * Handles database operations, WordPress query hooks, post saving, content
 * filters, and script enqueuing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GeoPress {

	// ── Installation ──────────────────────────────────────────────────────────

	/**
	 * Creates the database table and sets default options on plugin activation.
	 */
	public static function install() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'geopress';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			geopress_id     int(11)     NOT NULL AUTO_INCREMENT,
			name            tinytext    NOT NULL,
			loc             tinytext,
			warn            tinytext,
			mapurl          tinytext,
			coord           text        NOT NULL,
			geom            varchar(16) NOT NULL,
			relationshiptag tinytext,
			featuretypetag  tinytext,
			elev            float,
			floor           float,
			radius          float,
			visible         tinyint(4)  DEFAULT 1,
			map_format      tinytext    DEFAULT '',
			map_zoom        tinyint(4)  DEFAULT 0,
			map_type        tinytext    DEFAULT '',
			UNIQUE KEY geopress_id (geopress_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// add_option() is a no-op when the option already exists.
		add_option( '_geopress_mapwidth',          '400' );
		add_option( '_geopress_mapheight',         '200' );
		add_option( '_geopress_marker',            GEOPRESS_URL . 'images/marker.svg' );
		add_option( '_geopress_rss_enable',        'true' );
		add_option( '_geopress_rss_format',        'simple' );
		add_option( '_geopress_map_format',        'openlayers' );
		add_option( '_geopress_map_type',          'hybrid' );
		add_option( '_geopress_controls_pan',      true );
		add_option( '_geopress_controls_map_type', true );
		add_option( '_geopress_controls_zoom',     'small' );
		add_option( '_geopress_controls_overview', false );
		add_option( '_geopress_controls_scale',    true );
		add_option( '_geopress_default_add_map',   0 );
		add_option( '_geopress_default_zoom_level','11' );
		add_option( '_geopress_google_apikey',     '' );

		// ArcGIS Maps SDK v5 options.
		add_option( '_geopress_arcgis_portal_url',          'https://www.arcgis.com' );
		add_option( '_geopress_arcgis_api_key',             '' );
		add_option( '_geopress_arcgis_basemap',             'osm' );
		add_option( '_geopress_arcgis_webmap_item_id',      '' );
		add_option( '_geopress_arcgis_webscene_item_id',    '' );
		add_option( '_geopress_arcgis_feature_layer_url',   '' );
		add_option( '_geopress_arcgis_feature_layer_item_id', '' );

		$ping_sites = get_option( 'ping_sites', '' );
		if ( false === strpos( $ping_sites, 'mapufacture' ) ) {
			update_option( 'ping_sites', $ping_sites . "\nhttps://mapufacture.com/georss/ping/api" );
		}
	}

	// ── Database helpers ──────────────────────────────────────────────────────

	/**
	 * Returns locations keyed by name, each value an array of associated posts.
	 *
	 * @param int $number  Max number of results, or -1 for all.
	 * @return array
	 */
	public static function get_location_posts( $number = -1 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'geopress';

		$sql = "SELECT {$table}.*, {$wpdb->posts}.*, {$wpdb->postmeta}.*
				FROM {$table}
				INNER JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.meta_key = '_geopress_id'
					AND {$wpdb->postmeta}.meta_value = {$table}.geopress_id
				INNER JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
				WHERE {$wpdb->posts}.post_status = 'publish'
				AND   {$table}.coord != ''";

		if ( $number >= 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $number );
		}

		$result    = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$locations = array();

		foreach ( $result as $loc ) {
			if ( ! isset( $locations[ $loc->name ] ) ) {
				$locations[ $loc->name ] = array();
			}
			$locations[ $loc->name ][] = $loc;
		}

		return $locations;
	}

	/**
	 * Returns all distinct locations that have coordinates and published posts.
	 *
	 * @param int $number  Max number of results, or -1 for all.
	 * @return array
	 */
	public static function get_locations( $number = -1 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'geopress';

		$sql = "SELECT {$table}.*
				FROM {$table}
				INNER JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.meta_key = '_geopress_id'
					AND {$wpdb->postmeta}.meta_value = {$table}.geopress_id
				INNER JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
				WHERE {$table}.coord != ''
				AND   {$wpdb->posts}.post_status = 'publish'
				GROUP BY {$table}.geopress_id";

		if ( $number >= 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $number );
		}

		return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Returns locations from the current WP_Query loop, keyed by coordinate.
	 *
	 * @param int $limit  Max posts to iterate, or -1 for all.
	 * @return array
	 */
	public static function get_loop_locations( $limit = -1 ) {
		$result = array();
		$i      = 0;

		while ( have_posts() ) {
			the_post();
			$geo = self::get_geo( get_the_ID() );
			if ( $geo ) {
				$result[] = $geo;
				$i++;
				if ( $limit > 0 && $i >= $limit ) {
					break;
				}
			}
		}
		rewind_posts();

		$locations = array();
		foreach ( $result as $loc ) {
			if ( ! isset( $locations[ $loc->coord ] ) ) {
				$locations[ $loc->coord ] = array();
			}
			$locations[ $loc->coord ][] = $loc;
		}

		return $locations;
	}

	/**
	 * Returns the location record for a given post ID, or null.
	 *
	 * @param int $post_id
	 * @return object|null
	 */
	public static function get_geo( $post_id ) {
		global $wpdb;

		$post_id = (int) $post_id;
		$geo_id  = (int) get_post_meta( $post_id, '_geopress_id', true );

		if ( ! $geo_id ) {
			return null;
		}

		$table = $wpdb->prefix . 'geopress';

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT {$table}.*
				 FROM {$table}
				 INNER JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.meta_value = {$table}.geopress_id
				 WHERE {$wpdb->postmeta}.meta_key = '_geopress_id'
				 AND   {$wpdb->postmeta}.post_id  = %d
				 AND   {$table}.geopress_id       = %d",
				$post_id,
				$geo_id
			)
		);
	}

	/**
	 * Returns a single location record by its geopress_id.
	 *
	 * @param int $loc_id
	 * @return object|null
	 */
	public static function get_location( $loc_id ) {
		global $wpdb;

		$loc_id = (int) $loc_id;
		if ( $loc_id <= 0 ) {
			return null;
		}

		$table = $wpdb->prefix . 'geopress';

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE geopress_id = %d", $loc_id )
		);
	}

	/**
	 * Returns the address of the first stored location.
	 *
	 * @return string
	 */
	public static function default_loc() {
		global $wpdb;

		$table = $wpdb->prefix . 'geopress';
		$row   = $wpdb->get_row( "SELECT loc FROM {$table} LIMIT 1" );

		return $row ? (string) $row->loc : '';
	}

	/**
	 * Saves (inserts or updates) a location record.
	 *
	 * @param int    $id          Existing geopress_id to update, or -1 to insert/find-by-value.
	 * @param string $name
	 * @param string $loc         Address string.
	 * @param string $coord       "lat lon".
	 * @param string $geom        Geometry type (e.g. "point").
	 * @param string $warn
	 * @param string $mapurl
	 * @param int    $visible
	 * @param string $map_format
	 * @param int    $map_zoom
	 * @param string $map_type
	 * @return int                The geopress_id of the saved record.
	 */
	public static function save_geo(
		$id,
		$name,
		$loc,
		$coord,
		$geom,
		$warn,
		$mapurl,
		$visible    = 1,
		$map_format = '',
		$map_zoom   = 0,
		$map_type   = ''
	) {
		global $wpdb;

		// Sanitize all inputs before touching the DB.
		$id         = (int) $id;
		$name       = sanitize_text_field( $name );
		$loc        = sanitize_text_field( $loc );
		$coord      = sanitize_text_field( $coord );
		$geom       = sanitize_text_field( $geom );
		$warn       = sanitize_text_field( $warn );
		$mapurl     = esc_url_raw( $mapurl );
		$visible    = '' === $name ? 0 : (int) $visible;
		$map_format = sanitize_key( $map_format );
		$map_zoom   = (int) $map_zoom;
		$map_type   = sanitize_key( $map_type );

		$table = $wpdb->prefix . 'geopress';

		// Find existing record.
		if ( $id > 0 ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare( "SELECT geopress_id FROM {$table} WHERE geopress_id = %d", $id )
			);
		} else {
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT geopress_id FROM {$table} WHERE (name = %s AND coord = %s) OR loc = %s",
					$name,
					$coord,
					$loc
				)
			);
		}

		$data    = array(
			'name'       => $name,
			'loc'        => $loc,
			'coord'      => $coord,
			'geom'       => $geom,
			'warn'       => $warn,
			'mapurl'     => $mapurl,
			'visible'    => $visible,
			'map_format' => $map_format,
			'map_zoom'   => $map_zoom,
			'map_type'   => $map_type,
		);
		$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s' );

		if ( $existing ) {
			$geo_id = (int) $existing->geopress_id;
			$wpdb->update( $table, $data, array( 'geopress_id' => $geo_id ), $formats, array( '%d' ) );
		} else {
			$wpdb->insert( $table, $data, $formats );
			$geo_id = (int) $wpdb->insert_id;
		}

		return $geo_id;
	}

	/**
	 * Deletes a location record and removes any associated post-meta references.
	 *
	 * @param int $loc_id
	 */
	public static function delete_location( $loc_id ) {
		global $wpdb;

		$loc_id = (int) $loc_id;
		if ( ! $loc_id ) {
			return;
		}

		$table = $wpdb->prefix . 'geopress';
		$wpdb->delete( $table, array( 'geopress_id' => $loc_id ), array( '%d' ) );
		$wpdb->delete(
			$wpdb->postmeta,
			array(
				'meta_key'   => '_geopress_id',
				'meta_value' => $loc_id,
			),
			array( '%s', '%d' )
		);
	}

	// ── Dropdown helper ───────────────────────────────────────────────────────

	/**
	 * Outputs <option> elements for all visible saved locations.
	 */
	public static function select_saved_geo() {
		foreach ( self::select_saved_geo_array() as $row ) {
			printf(
				'<option value="%s">%s</option>' . "\n",
				esc_attr( $row->loc ),
				esc_html( $row->name )
			);
		}
	}

	/**
	 * Returns an array of visible saved location objects (geopress_id, loc, name).
	 *
	 * @return array
	 */
	public static function select_saved_geo_array() {
		global $wpdb;
		$table = $wpdb->prefix . 'geopress';
		return $wpdb->get_results( "SELECT geopress_id, loc, name FROM {$table} WHERE visible = 1" );
	}

	// ── Post save ─────────────────────────────────────────────────────────────

	/**
	 * Fired on save_post. Reads location from POST data or post content and saves.
	 *
	 * @param int $post_id
	 */
	public static function update_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Nonce guard — the nonce is output by GeoPress_Admin::geopress_new_location_form().
		if (
			! isset( $_POST['geopress_nonce'] ) ||
			! wp_verify_nonce( wp_unslash( $_POST['geopress_nonce'] ), 'geopress_save_location' )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post = get_post( (int) $post_id );
		if ( ! $post ) {
			return;
		}

		$addr     = isset( $_POST['addr'] )     ? sanitize_text_field( wp_unslash( $_POST['addr'] ) )     : '';
		$geometry = isset( $_POST['geometry'] ) ? sanitize_text_field( wp_unslash( $_POST['geometry'] ) ) : '';
		$locname  = isset( $_POST['locname'] )  ? sanitize_text_field( wp_unslash( $_POST['locname'] ) )  : '';

		// Allow location to be defined inside the post body.
		if ( preg_match_all( '/GEOPRESS_LOCATION\((.+)\)/', $post->post_content, $matches ) > 0 ) {
			$addr = $matches[1][0];
		} elseif ( preg_match_all( '/geo:lat=([-\d\.]+)(.*)?geo:lon[g]?=([-\d\.]+)/', $post->post_content, $matches ) > 0 ) {
			$addr = '[' . $matches[1][0] . ',' . $matches[3][0] . ']';
		}

		if ( ! $addr ) {
			return;
		}

		// Resolve to lat/lon. Accept both "[lat,lon]", "lat, lon" (comma) and
		// "lat lon" (space-separated) — the stored coord format is space-separated.
		if ( preg_match( '/\[(.+),[ ]?(.+)\]/', $addr, $matches ) ) {
			$lat = trim( $matches[1] );
			$lon = trim( $matches[2] );
		} elseif ( preg_match( '/^([-\d.]+)[,\s]+([-\d.]+)$/', trim( $geometry ), $matches ) ) {
			$lat = trim( $matches[1] );
			$lon = trim( $matches[2] );
		} else {
			list( $lat, $lon ) = geocode( $addr );
		}

		list( $warn, $mapurl ) = yahoo_mapurl( $addr );

		$geo_id = self::save_geo( -1, $locname, $addr, "{$lat} {$lon}", 'point', $warn, $mapurl, 1 );

		if ( $geo_id ) {
			if ( ! update_post_meta( $post_id, '_geopress_id', $geo_id ) ) {
				add_post_meta( $post_id, '_geopress_id', $geo_id );
			}
		}
	}

	// ── Content filters ───────────────────────────────────────────────────────

	/**
	 * Replaces INSERT_MAP / INSERT_GEOPRESS_MAP tags in post content.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function embed_map_inpost( $content ) {
		$default_add_map = (int) get_option( '_geopress_default_add_map', 0 );

		// INSERT_ARCGIS_MAP — standalone ArcGIS embed by portal item ID. The item
		// type (web map vs web scene) is resolved against the configured portal
		// and cached in a transient, so authors only need a single tag.
		if ( preg_match( '/INSERT_ARCGIS_MAP/', $content ) ) {
			$content = preg_replace_callback(
				'/INSERT_ARCGIS_MAP\(([^,)]+),[ ]?(\d+),[ ]?(\d+)\)/',
				function ( $m ) { return geopress_arcgis_map_embed( trim( $m[1] ), (int) $m[2], (int) $m[3] ); },
				$content
			);
			$content = preg_replace_callback(
				'/INSERT_ARCGIS_MAP\(([^)]+)\)/',
				function ( $m ) { return geopress_arcgis_map_embed( trim( $m[1] ) ); },
				$content
			);
		}

		if ( preg_match( '/INSERT_MAP/', $content ) ) {
			// INSERT_MAP(h,w,url)
			$content = preg_replace_callback(
				'/INSERT_MAP\((\d+),[ ]?(\d+),[ ]?([^)]+)\)/',
				function ( $m ) { return geopress_post_map( (int) $m[1], (int) $m[2], true, $m[3] ); },
				$content
			);
			// INSERT_MAP(h,w)
			$content = preg_replace_callback(
				'/INSERT_MAP\((\d+),[ ]?(\d+)\)/',
				function ( $m ) { return geopress_post_map( (int) $m[1], (int) $m[2] ); },
				$content
			);
			// Bare INSERT_MAP
			$content = str_replace( 'INSERT_MAP', geopress_post_map(), $content );

		} elseif ( preg_match( '/INSERT_GEOPRESS_MAP/', $content ) ) {
			$content = preg_replace_callback(
				'/INSERT_GEOPRESS_MAP\((\d+),[ ]?(\d+)\)/',
				function ( $m ) { return geopress_map( (int) $m[1], (int) $m[2] ); },
				$content
			);
			$content = str_replace( 'INSERT_GEOPRESS_MAP', geopress_map(), $content );

		} elseif ( 2 === $default_add_map || ( is_single() && 1 === $default_add_map ) ) {
			$content .= geopress_post_map();
		}

		// Strip any remaining GEOPRESS_LOCATION() tags.
		$content = preg_replace( '/GEOPRESS_LOCATION\([^)]+\)/', '', $content );

		return $content;
	}

	/**
	 * Replaces INSERT_COORDS / INSERT_ADDRESS / INSERT_LOCATION tags.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function embed_data_inpost( $content ) {
		$content = str_replace( 'INSERT_COORDS',   the_geo_mf(),  $content );
		$content = str_replace( 'INSERT_ADDRESS',  the_adr_mf(),  $content );
		$content = str_replace( 'INSERT_LOCATION', the_loc_mf(),  $content );
		return $content;
	}

	// ── Query hooks ───────────────────────────────────────────────────────────

	/**
	 * Adds a JOIN clause when filtering posts by location.
	 *
	 * @param string $join
	 * @return string
	 */
	public static function join_clause( $join ) {
		$location = self::get_location_query_var();
		if ( '' === $location ) {
			return $join;
		}

		global $wpdb;
		$table  = $wpdb->prefix . 'geopress';
		$join  .= " , {$wpdb->postmeta}, {$table} ";

		return $join;
	}

	/**
	 * Adds a WHERE clause when filtering posts by location.
	 *
	 * @param string $where
	 * @return string
	 */
	public static function where_clause( $where ) {
		$location = self::get_location_query_var();
		if ( '' === $location ) {
			return $where;
		}

		global $wpdb;
		$table  = $wpdb->prefix . 'geopress';

		$where .= " AND {$table}.geopress_id = {$wpdb->postmeta}.meta_value"
		        . " AND {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id"
		        . " AND {$wpdb->postmeta}.meta_key = '_geopress_id'";

		if ( ctype_digit( $location ) ) {
			$where .= $wpdb->prepare( " AND {$wpdb->postmeta}.meta_value = %d", (int) $location );
		} else {
			$where .= $wpdb->prepare( " AND {$table}.name = %s", $location );
		}

		return $where;
	}

	/**
	 * Includes a location-specific theme template when ?location= is set.
	 */
	public static function location_redirect() {
		$location = self::get_location_query_var();
		if ( '' !== $location ) {
			$template = get_query_template( 'location' );
			if ( $template ) {
				include $template;
				exit;
			}
		}
	}

	/**
	 * Returns the sanitized ?location= query parameter, or empty string.
	 *
	 * @return string
	 */
	private static function get_location_query_var() {
		if ( isset( $_GET['location'] ) && '' !== $_GET['location'] ) {
			return sanitize_text_field( wp_unslash( $_GET['location'] ) );
		}
		return '';
	}

	// ── Map configuration helpers ─────────────────────────────────────────────

	/**
	 * Returns the active map provider identifier.
	 *
	 * @param string $map_format_type  Override value (empty = use saved option).
	 * @return string
	 */
	public static function mapstraction_map_format( $map_format_type = '' ) {
		return '' !== $map_format_type
			? $map_format_type
			: get_option( '_geopress_map_format', 'openlayers' );
	}

	/**
	 * Returns the Mapstraction constant string for the given map type.
	 *
	 * @param string $map_view_type  'road', 'satellite', 'hybrid', or '' for saved option.
	 * @return string
	 */
	public static function mapstraction_map_type( $map_view_type = '' ) {
		if ( '' === $map_view_type ) {
			$map_view_type = get_option( '_geopress_map_type', 'hybrid' );
		}
		switch ( $map_view_type ) {
			case 'road':      return 'Mapstraction.ROAD';
			case 'satellite': return 'Mapstraction.SATELLITE';
			case 'hybrid':
			default:          return 'Mapstraction.HYBRID';
		}
	}

	/**
	 * Returns a Mapstraction controls object literal as a JS string.
	 *
	 * @param string $pan
	 * @param string $zoom
	 * @param string $overview
	 * @param string $scale
	 * @param string $map_type
	 * @return string
	 */
	public static function mapstraction_map_controls( $pan = '', $zoom = '', $overview = '', $scale = '', $map_type = '' ) {
		$pan      = '' !== $pan      ? $pan      : ( get_option( '_geopress_controls_pan' )      ? 'true' : 'false' );
		$zoom     = '' !== $zoom     ? $zoom     : get_option( '_geopress_controls_zoom', 'small' );
		$overview = '' !== $overview ? $overview : ( get_option( '_geopress_controls_overview' ) ? 'true' : 'false' );
		$scale    = '' !== $scale    ? $scale    : ( get_option( '_geopress_controls_scale' )    ? 'true' : 'false' );
		$map_type = '' !== $map_type ? $map_type : ( get_option( '_geopress_controls_map_type' ) ? 'true' : 'false' );

		return "{\n\tpan: {$pan},\n\tzoom: '{$zoom}',\n\toverview: {$overview},\n\tscale: {$scale},\n\tmap_type: {$map_type}\n\t}";
	}

	/**
	 * Returns the active zoom level.
	 *
	 * @param int $map_zoom  Override value (0 = use saved option).
	 * @return int
	 */
	public static function mapstraction_map_zoom( $map_zoom = 0 ) {
		$map_zoom = (int) $map_zoom;
		return $map_zoom > 0 ? $map_zoom : (int) get_option( '_geopress_default_zoom_level', 11 );
	}

	// ── Script enqueuing ──────────────────────────────────────────────────────

	/**
	 * Enqueue front-end scripts.
	 */
	public static function enqueue_scripts() {
		self::register_scripts();
		if ( 'arcgis' !== get_option( '_geopress_map_format', 'openlayers' ) ) {
			wp_enqueue_script( 'geopress-mapstraction' );
			wp_enqueue_script( 'geopress-js' );
		}
		self::enqueue_map_api_scripts();
	}

	/**
	 * Enqueue admin-side scripts (post editor only).
	 *
	 * @param string $hook  Current admin page hook.
	 */
	public static function enqueue_admin_scripts( $hook ) {
		$is_post_editor    = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$is_geopress_admin = false !== strpos( $hook, 'geopress' );
		if ( ! $is_post_editor && ! $is_geopress_admin ) {
			return;
		}
		self::register_scripts();
		// Always enqueue Mapstraction for the admin location-picker regardless of format.
		wp_enqueue_script( 'geopress-mapstraction' );
		wp_enqueue_script( 'geopress-js' );
		self::enqueue_map_api_scripts();
	}

	/**
	 * Registers all GeoPress scripts with WordPress without enqueuing them.
	 */
	private static function register_scripts() {
		wp_register_script(
			'geopress-openlayers',
			'https://openlayers.org/api/OpenLayers.js',
			array(),
			null,
			false
		);
		wp_register_script(
			'geopress-mapstraction',
			GEOPRESS_URL . 'mapstraction.js',
			array(),
			GEOPRESS_VERSION,
			false
		);
		wp_register_script(
			'geopress-js',
			GEOPRESS_URL . 'geopress.js',
			array( 'geopress-mapstraction' ),
			GEOPRESS_VERSION,
			false
		);

		// ArcGIS Maps SDK v5 — web components bundle and our ES-module helper.
		wp_register_style(
			'geopress-arcgis-css',
			'https://js.arcgis.com/5.0/esri/themes/light/main.css',
			array(),
			null
		);
		wp_register_script(
			'geopress-arcgis-components',
			'https://js.arcgis.com/5.0/',
			array(),
			null,
			false
		);
		wp_register_script(
			'geopress-arcgis-js',
			GEOPRESS_URL . 'arcgis-map.js',
			array( 'geopress-arcgis-components' ),
			GEOPRESS_VERSION,
			false
		);

		// Both ArcGIS scripts must be served as ES modules.
		add_filter(
			'script_loader_tag',
			function ( $tag, $handle ) {
				if ( in_array( $handle, array( 'geopress-arcgis-components', 'geopress-arcgis-js' ), true ) ) {
					return str_replace( ' src=', ' type="module" src=', $tag );
				}
				return $tag;
			},
			10,
			2
		);
	}

	/**
	 * Enqueues the map API library scripts based on the configured map provider.
	 */
	private static function enqueue_map_api_scripts() {
		$map_format = get_option( '_geopress_map_format', 'openlayers' );

		// ArcGIS Maps SDK v5 — web components path (bypasses Mapstraction entirely).
		if ( 'arcgis' === $map_format ) {
			wp_enqueue_style( 'geopress-arcgis-css' );
			wp_enqueue_script( 'geopress-arcgis-components' );
			wp_enqueue_script( 'geopress-arcgis-js' );
			wp_add_inline_script(
				'geopress-arcgis-js',
				'window.geopressArcGISConfig = ' . wp_json_encode(
					array(
						'apiKey'    => get_option( '_geopress_arcgis_api_key', '' ),
						'portalUrl' => get_option( '_geopress_arcgis_portal_url', 'https://www.arcgis.com' ),
					)
				) . ';',
				'before'
			);
			return;
		}

		// Enqueue the OpenLayers library only when it is the active provider.
		if ( in_array( $map_format, array( 'openlayers', 'openstreetmap' ), true ) ) {
			wp_enqueue_script( 'geopress-openlayers' );
		}

		if ( 'microsoft' === $map_format ) {
			wp_enqueue_script(
				'geopress-bing',
				'https://dev.virtualearth.net/mapcontrol/v3/mapcontrol.js',
				array(),
				null,
				false
			);
		}

		if ( 'google' === $map_format ) {
			$google_apikey = get_option( '_geopress_google_apikey', '' );
			$google_url    = 'https://maps.googleapis.com/maps/api/js';
			if ( '' !== $google_apikey ) {
				$google_url = add_query_arg( 'key', $google_apikey, $google_url );
			}
			wp_enqueue_script(
				'geopress-google-maps',
				$google_url,
				array(),
				null,
				false
			);
		}
	}
}
