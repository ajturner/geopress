<?php
/**
 * GeoPress admin pages and post editor metabox.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GeoPress_Admin {

	// ── Admin menu ────────────────────────────────────────────────────────────

	public static function admin_menu() {
		$slug = 'geopress/geopress.php';

		add_menu_page(
			__( 'Customize GeoPress', 'geopress' ),
			__( 'GeoPress', 'geopress' ),
			'manage_options',
			$slug,
			array( __CLASS__, 'geopress_options_page' )
		);
		add_submenu_page(
			$slug,
			__( 'Locations', 'geopress' ),
			__( 'Locations', 'geopress' ),
			'manage_options',
			'geopress_locations',
			array( __CLASS__, 'geopress_locations_page' )
		);
		add_submenu_page(
			$slug,
			__( 'Maps', 'geopress' ),
			__( 'Maps', 'geopress' ),
			'manage_options',
			'geopress_maps',
			array( __CLASS__, 'geopress_maps_page' )
		);
		add_submenu_page(
			$slug,
			__( 'Documentation', 'geopress' ),
			__( 'Documentation', 'geopress' ),
			'manage_options',
			'geopress_documentation',
			array( __CLASS__, 'geopress_documentation_page' )
		);
	}

	// ── Post editor metabox ───────────────────────────────────────────────────

	public static function location_edit_form() {
		global $post_ID;
		$geo = GeoPress::get_geo( (int) $post_ID );
		self::geopress_new_location_form( $geo );
	}

	public static function geopress_new_location_form( $geo ) {
		$loc      = $geo ? esc_attr( $geo->loc )   : '';
		$geometry = $geo ? esc_attr( $geo->coord )  : '';
		$locname  = $geo ? esc_attr( $geo->name )   : '';
		?>
		<div id="locationdiv" class="postbox">
			<h3><a href="https://www.georss.org/"><?php esc_html_e( 'Location', 'geopress' ); ?></a></h3>
			<div class="inside">
				<?php wp_nonce_field( 'geopress_save_location', 'geopress_nonce' ); ?>
				<table width="100%" cellpadding="3" cellspacing="3">
					<thead>
						<tr>
							<th scope="col" align="left"><?php esc_html_e( 'Saved Name', 'geopress' ); ?></th>
							<th scope="col" colspan="3" align="left"><?php esc_html_e( 'Location Name, Address, or [Latitude, Longitude]', 'geopress' ); ?></th>
							<th scope="col" colspan="3" align="left"></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td width="15%">
								<input size="10" type="text" value="<?php echo $locname; ?>" name="locname" id="locname" />
							</td>
							<td width="50%">
								<input size="50" type="text" value="<?php echo $loc; ?>" name="addr" id="addr" onkeypress="return checkEnter(event);" />
							</td>
							<td width="20%">
								<a href="#" onclick="geocode();return false;" title="<?php esc_attr_e( 'Geocode this address', 'geopress' ); ?>">
									<?php esc_html_e( 'Map Location', 'geopress' ); ?>
								</a>
							</td>
						</tr>
					</tbody>
				</table>
				<input type="hidden" value="<?php echo $geometry; ?>" name="geometry" id="geometry" />
				<table width="30%" cellpadding="3" cellspacing="3">
					<tbody>
						<tr>
							<td align="left"><?php esc_html_e( 'Saved Locations', 'geopress' ); ?></td>
							<td rowspan="3"><?php echo geopress_map_select(); ?></td>
						</tr>
						<tr>
							<td>
								<label for="geopress_select">
									<select id="geopress_select" onchange="geopress_loadsaved(this);showLocation('addr','geometry');">
										<option value=""><?php esc_html_e( '--choose one--', 'geopress' ); ?></option>
										<?php GeoPress::select_saved_geo(); ?>
									</select>
								</label>
							</td>
						</tr>
						<tr>
							<td width="20%" height="200px">
								<a href="#" onclick="geopress_resetMap();return false;" title="<?php esc_attr_e( 'Zoom out and center map', 'geopress' ); ?>">
									<?php esc_html_e( 'Reset Map', 'geopress' ); ?>
								</a>
							</td>
						</tr>
					</tbody>
				</table>
			</div><!-- .inside -->
			<?php if ( $geo ) : ?>
			<script type="text/javascript">
			geopress_addEvent(window, 'load', function() { showLocation(); });
			</script>
			<?php endif; ?>
		</div>
		<?php
	}

	// ── Locations page ────────────────────────────────────────────────────────

	public static function geopress_locations_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'geopress' ) );
		}

		if ( isset( $_POST['Options'] ) && check_admin_referer( 'geopress_locations_nonce', 'geopress_nonce' ) ) {
			$locnames    = isset( $_POST['locname'] )    ? (array) wp_unslash( $_POST['locname'] )    : array();
			$locaddrs    = isset( $_POST['locaddr'] )    ? (array) wp_unslash( $_POST['locaddr'] )    : array();
			$locids      = isset( $_POST['locid'] )      ? (array) wp_unslash( $_POST['locid'] )      : array();
			$locvisibles = isset( $_POST['locvisible'] ) ? (array) wp_unslash( $_POST['locvisible'] ) : array();

			foreach ( $locnames as $i => $raw_name ) {
				$name = sanitize_text_field( $raw_name );
				$addr = sanitize_text_field( $locaddrs[ $i ] ?? '' );
				$lid  = (int) ( $locids[ $i ] ?? 0 );
				$vis  = isset( $locvisibles[ $i ] ) ? 1 : 0;

				if ( preg_match( '/\[(.+),[ ]?(.+)\]/', $addr, $m ) ) {
					$lat = trim( $m[1] );
					$lon = trim( $m[2] );
				} else {
					list( $lat, $lon ) = geocode( $addr );
				}

				list( $warn, $mapurl ) = yahoo_mapurl( $addr );
				GeoPress::save_geo( $lid, $name, $addr, "{$lat} {$lon}", 'point', $warn, $mapurl, $vis );
			}

			echo '<div class="updated"><p><strong>' . esc_html__( 'Locations updated.', 'geopress' ) . '</strong></p></div>';
		}

		global $wpdb;
		$table  = $wpdb->prefix . 'geopress';
		$result = $wpdb->get_results( "SELECT * FROM {$table}" );

		echo '<div class="wrap"><h2>' . esc_html__( 'Configure Locations', 'geopress' ) . '</h2>';
		echo '<form method="post">';
		wp_nonce_field( 'geopress_locations_nonce', 'geopress_nonce' );
		?>
		<div style="width: 70%; float: left;">
			<table width="100%" cellpadding="3" cellspacing="3">
				<thead>
					<tr>
						<th scope="col" align="left"><?php esc_html_e( 'Show', 'geopress' ); ?></th>
						<th scope="col" align="left"><?php esc_html_e( 'Name', 'geopress' ); ?></th>
						<th scope="col" align="left"><?php esc_html_e( 'Address', 'geopress' ); ?></th>
						<th scope="col" align="left"><?php esc_html_e( 'Geometry', 'geopress' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php $i = -1; foreach ( $result as $loc ) : $i++; ?>
					<tr>
						<td width="5%">
							<input type="hidden" name="locid[<?php echo $i; ?>]" value="<?php echo esc_attr( $loc->geopress_id ); ?>" />
							<input type="checkbox" value="1" name="locvisible[<?php echo $i; ?>]" <?php checked( (bool) $loc->visible ); ?> />
						</td>
						<td width="15%">
							<input size="10" type="text" value="<?php echo esc_attr( $loc->name ); ?>" name="locname[<?php echo $i; ?>]" />
						</td>
						<td width="50%">
							<input size="40" type="text" value="<?php echo esc_attr( $loc->loc ); ?>" name="locaddr[<?php echo $i; ?>]" />
						</td>
						<td>
							<input type="text" disabled="disabled" value="<?php echo esc_attr( $loc->coord ); ?>" name="loccoord[<?php echo $i; ?>]" />
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<div class="submit">
				<input type="submit" name="Options" value="<?php echo esc_attr__( 'Save Locations', 'geopress' ); ?> &raquo;" />
			</div>
		</div>
		<?php
		GeoPress_Maps::map_saved_locations( $result );
		echo '</form></div>';
	}

	// ── Maps page ─────────────────────────────────────────────────────────────

	public static function geopress_maps_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'geopress' ) );
		}

		if ( isset( $_POST['Options'] ) && check_admin_referer( 'geopress_maps_nonce', 'geopress_nonce' ) ) {
			update_option( '_geopress_map_format',         sanitize_key( wp_unslash( $_POST['map_format'] ?? 'openlayers' ) ) );
			update_option( '_geopress_mapwidth',           (int) ( $_POST['default_mapwidth'] ?? 400 ) );
			update_option( '_geopress_mapheight',          (int) ( $_POST['default_mapheight'] ?? 200 ) );
			update_option( '_geopress_marker',             esc_url_raw( wp_unslash( $_POST['default_marker'] ?? '' ) ) );
			update_option( '_geopress_map_type',           sanitize_key( wp_unslash( $_POST['map_view_type'] ?? 'hybrid' ) ) );
			update_option( '_geopress_controls_pan',       isset( $_POST['map_controls_pan'] ) );
			update_option( '_geopress_controls_map_type',  isset( $_POST['map_controls_map_type'] ) );
			update_option( '_geopress_controls_zoom',      sanitize_key( wp_unslash( $_POST['map_controls_zoom'] ?? 'small' ) ) );
			update_option( '_geopress_controls_overview',  isset( $_POST['map_controls_overview'] ) );
			update_option( '_geopress_controls_scale',     isset( $_POST['map_controls_scale'] ) );
			update_option( '_geopress_default_zoom_level', (int) ( $_POST['default_zoom_level'] ?? 11 ) );

			// ArcGIS Maps SDK v5 settings.
			update_option( '_geopress_arcgis_portal_url',           esc_url_raw( wp_unslash( $_POST['arcgis_portal_url'] ?? 'https://www.arcgis.com' ) ) );
			update_option( '_geopress_arcgis_api_key',              sanitize_text_field( wp_unslash( $_POST['arcgis_api_key'] ?? '' ) ) );
			update_option( '_geopress_arcgis_basemap',              sanitize_text_field( wp_unslash( $_POST['arcgis_basemap'] ?? 'arcgis/navigation' ) ) );
			update_option( '_geopress_arcgis_webmap_item_id',       sanitize_text_field( wp_unslash( $_POST['arcgis_webmap_item_id'] ?? '' ) ) );
			update_option( '_geopress_arcgis_webscene_item_id',     sanitize_text_field( wp_unslash( $_POST['arcgis_webscene_item_id'] ?? '' ) ) );
			update_option( '_geopress_arcgis_feature_layer_url',    esc_url_raw( wp_unslash( $_POST['arcgis_feature_layer_url'] ?? '' ) ) );
			update_option( '_geopress_arcgis_feature_layer_item_id', sanitize_text_field( wp_unslash( $_POST['arcgis_feature_layer_item_id'] ?? '' ) ) );

			echo '<div class="updated"><p><strong>' . esc_html__( 'Map layout updated.', 'geopress' ) . '</strong></p></div>';
		}

		$map_format         = get_option( '_geopress_map_format', 'openlayers' );
		$default_mapwidth   = (int) get_option( '_geopress_mapwidth', 400 );
		$default_mapheight  = (int) get_option( '_geopress_mapheight', 200 );
		$default_marker     = get_option( '_geopress_marker', GEOPRESS_URL . 'flag.png' );
		$default_zoom_level = (int) get_option( '_geopress_default_zoom_level', 11 );
		$map_view_type      = get_option( '_geopress_map_type', 'hybrid' );
		$map_controls_zoom  = get_option( '_geopress_controls_zoom', 'small' );
		$pan_checked        = get_option( '_geopress_controls_pan' )      ? 'checked="checked"' : '';
		$overview_checked   = get_option( '_geopress_controls_overview' ) ? 'checked="checked"' : '';
		$scale_checked      = get_option( '_geopress_controls_scale' )    ? 'checked="checked"' : '';
		$maptype_checked    = get_option( '_geopress_controls_map_type' ) ? 'checked="checked"' : '';
		?>
		<div class="wrap">
		<h2><?php esc_html_e( 'Configure Map Layout', 'geopress' ); ?></h2>
		<p><?php esc_html_e( 'This page configures the default map that will appear with posts and when you use INSERT_MAP.', 'geopress' ); ?></p>
		<h3><?php esc_html_e( 'Default Map', 'geopress' ); ?></h3>
		<form method="post">
		<?php wp_nonce_field( 'geopress_maps_nonce', 'geopress_nonce' ); ?>
		<div style="float:left;"><?php echo geopress_map( '', '', 1, false ); ?></div>
		<fieldset class="options">
		<table width="100%" cellspacing="2" cellpadding="5" class="editform">
			<tr valign="top">
				<th width="33%" scope="row"><?php esc_html_e( 'Map Size', 'geopress' ); ?>:</th>
				<td>
					<dl>
						<dt><label for="default_mapwidth"><?php esc_html_e( 'Map Width', 'geopress' ); ?>:</label></dt>
						<dd><input type="number" name="default_mapwidth" id="default_mapwidth" value="<?php echo esc_attr( $default_mapwidth ); ?>" style="width:10%" /> px</dd>
						<dt><label for="default_mapheight"><?php esc_html_e( 'Map Height', 'geopress' ); ?>:</label></dt>
						<dd><input type="number" name="default_mapheight" id="default_mapheight" value="<?php echo esc_attr( $default_mapheight ); ?>" style="width:10%" /> px</dd>
						<dt><label for="default_zoom_level"><?php esc_html_e( 'Default Zoom', 'geopress' ); ?>:</label></dt>
						<dd>
							<select name="default_zoom_level" id="default_zoom_level" onchange="geopress_change_zoom();">
								<?php
								$zoom_levels = array(
									18 => __( 'Zoomed In', 'geopress' ),
									17 => __( 'Single Block', 'geopress' ),
									16 => __( 'Neighborhood', 'geopress' ),
									15 => '15', 14 => __( 'Several Blocks', 'geopress' ),
									13 => '13', 12 => '12',
									11 => __( 'City', 'geopress' ),
									10 => '10', 9 => '9', 8 => '8',
									7  => __( 'Region', 'geopress' ),
									6  => '6', 5 => '5', 4 => '4',
									3  => __( 'Continent', 'geopress' ),
									2  => '2',
									1  => __( 'Zoomed Out', 'geopress' ),
								);
								foreach ( $zoom_levels as $value => $label ) {
									printf(
										'<option value="%d"%s>%s</option>',
										$value,
										selected( $default_zoom_level, $value, false ),
										esc_html( $label )
									);
								}
								?>
							</select>
						</dd>
					</dl>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Map Marker', 'geopress' ); ?>:</th>
				<td>
					<input type="text" name="default_marker" id="default_marker" value="<?php echo esc_attr( $default_marker ); ?>" />
					&nbsp;
					<label for="default_marker">
						<img src="<?php echo esc_url( $default_marker ); ?>" alt="<?php esc_attr_e( 'Default GeoPress marker', 'geopress' ); ?>" style="padding:2px; background-color: white; border: 1px solid #888;" />
					</label>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Map Format', 'geopress' ); ?>:</th>
				<td>
					<select name="map_format" id="map_format" onchange="geopress_change_map_format()">
						<?php
						$formats = array(
							'google'        => 'Google',
							'yahoo'         => 'Yahoo',
							'microsoft'     => 'Microsoft',
							'openstreetmap' => 'OpenStreetMap',
							'openlayers'    => 'OpenLayers',
							'arcgis'        => 'ArcGIS',
						);
						foreach ( $formats as $val => $label ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $val ),
								selected( $map_format, $val, false ),
								esc_html( $label )
							);
						}
						?>
					</select>
					<em><?php esc_html_e( 'Changing to Microsoft Maps requires saving your options', 'geopress' ); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Map Type', 'geopress' ); ?>:</th>
				<td>
					<select name="map_view_type" id="map_view_type" onchange="geopress_change_view()">
						<?php
						$types = array(
							'road'      => __( 'Road', 'geopress' ),
							'satellite' => __( 'Satellite', 'geopress' ),
							'hybrid'    => __( 'Hybrid', 'geopress' ),
						);
						foreach ( $types as $val => $label ) {
							printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $map_view_type, $val, false ), esc_html( $label ) );
						}
						?>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php esc_html_e( 'Controls', 'geopress' ); ?>:</th>
				<td>
					<select name="map_controls_zoom" id="map_controls_zoom" onchange="geopress_change_controls(this)">
						<?php
						$zoom_opts = array(
							'false' => __( 'None', 'geopress' ),
							'small' => __( 'Small', 'geopress' ),
							'large' => __( 'Large', 'geopress' ),
						);
						foreach ( $zoom_opts as $val => $label ) {
							printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $map_controls_zoom, $val, false ), esc_html( $label ) );
						}
						?>
					</select>
					<label for="map_controls_zoom"><?php esc_html_e( 'Zoom control size', 'geopress' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td><input name="map_controls_pan" type="checkbox" id="map_controls_pan" onchange="geopress_change_controls(this)" value="true" <?php echo $pan_checked; ?> /> <label for="map_controls_pan"><?php esc_html_e( 'Pan control', 'geopress' ); ?></label> <em>(Yahoo)</em></td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td><input name="map_controls_map_type" type="checkbox" id="map_controls_map_type" onchange="geopress_change_controls(this)" value="true" <?php echo $maptype_checked; ?> /> <label for="map_controls_map_type"><?php esc_html_e( 'Map Type', 'geopress' ); ?></label> <em>(Google)</em></td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td><input name="map_controls_overview" type="checkbox" id="map_controls_overview" onchange="geopress_change_controls(this)" value="true" <?php echo $overview_checked; ?> /> <label for="map_controls_overview"><?php esc_html_e( 'Overview', 'geopress' ); ?></label> <em>(Google)</em></td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td><input name="map_controls_scale" type="checkbox" id="map_controls_scale" onchange="geopress_change_controls(this)" value="true" <?php echo $scale_checked; ?> /> <label for="map_controls_scale"><?php esc_html_e( 'Scale', 'geopress' ); ?></label> <em>(Google)</em></td>
			</tr>
		</table>
		</fieldset>

		<?php
		$arcgis_portal_url          = get_option( '_geopress_arcgis_portal_url', 'https://www.arcgis.com' );
		$arcgis_api_key             = get_option( '_geopress_arcgis_api_key', '' );
		$arcgis_basemap             = get_option( '_geopress_arcgis_basemap', 'arcgis/navigation' );
		$arcgis_webmap_item_id      = get_option( '_geopress_arcgis_webmap_item_id', '' );
		$arcgis_webscene_item_id    = get_option( '_geopress_arcgis_webscene_item_id', '' );
		$arcgis_fl_url              = get_option( '_geopress_arcgis_feature_layer_url', '' );
		$arcgis_fl_item_id          = get_option( '_geopress_arcgis_feature_layer_item_id', '' );
		?>
		<h3><?php esc_html_e( 'ArcGIS Settings', 'geopress' ); ?></h3>
		<p><em><?php esc_html_e( 'These settings apply when Map Format is set to ArcGIS. They allow loading web maps, web scenes, and feature layers from ArcGIS Online or an ArcGIS Enterprise portal.', 'geopress' ); ?></em></p>
		<fieldset class="options">
		<table width="100%" cellspacing="2" cellpadding="5" class="editform">
			<tr valign="top">
				<th width="33%" scope="row"><label for="arcgis_portal_url"><?php esc_html_e( 'Portal URL', 'geopress' ); ?>:</label></th>
				<td>
					<input type="url" name="arcgis_portal_url" id="arcgis_portal_url" value="<?php echo esc_attr( $arcgis_portal_url ); ?>" style="width:50%;" />
					<br /><em><?php esc_html_e( 'ArcGIS Online or Enterprise portal URL. Default: https://www.arcgis.com', 'geopress' ); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="arcgis_api_key"><?php esc_html_e( 'API Key', 'geopress' ); ?>:</label></th>
				<td>
					<input type="text" name="arcgis_api_key" id="arcgis_api_key" value="<?php echo esc_attr( $arcgis_api_key ); ?>" style="width:50%;" />
					<br /><em><?php esc_html_e( 'ArcGIS Platform API key. Required for accessing private content or using ArcGIS basemap styles.', 'geopress' ); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="arcgis_basemap"><?php esc_html_e( 'Default Basemap', 'geopress' ); ?>:</label></th>
				<td>
					<select name="arcgis_basemap" id="arcgis_basemap">
						<?php
						$basemaps = array(
							'arcgis/navigation'  => __( 'Navigation (streets)', 'geopress' ),
							'arcgis/streets'     => __( 'Streets', 'geopress' ),
							'arcgis/imagery'     => __( 'Imagery (satellite)', 'geopress' ),
							'arcgis/terrain'     => __( 'Terrain', 'geopress' ),
							'arcgis/oceans'      => __( 'Oceans', 'geopress' ),
							'arcgis/community'   => __( 'Community', 'geopress' ),
							'arcgis/light-gray'  => __( 'Light Gray', 'geopress' ),
							'arcgis/dark-gray'   => __( 'Dark Gray', 'geopress' ),
						);
						foreach ( $basemaps as $val => $label ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $val ),
								selected( $arcgis_basemap, $val, false ),
								esc_html( $label )
							);
						}
						?>
					</select>
					<br /><em><?php esc_html_e( 'Used when no Web Map Item ID is specified.', 'geopress' ); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="arcgis_webmap_item_id"><?php esc_html_e( 'Web Map Item ID', 'geopress' ); ?>:</label></th>
				<td>
					<input type="text" name="arcgis_webmap_item_id" id="arcgis_webmap_item_id" value="<?php echo esc_attr( $arcgis_webmap_item_id ); ?>" style="width:40%;" />
					<br /><em><?php esc_html_e( 'Portal item ID of a Web Map to use as the default map base. Overrides the basemap setting above. Leave blank to use the default basemap.', 'geopress' ); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="arcgis_webscene_item_id"><?php esc_html_e( 'Web Scene Item ID', 'geopress' ); ?>:</label></th>
				<td>
					<input type="text" name="arcgis_webscene_item_id" id="arcgis_webscene_item_id" value="<?php echo esc_attr( $arcgis_webscene_item_id ); ?>" style="width:40%;" />
					<br /><em><?php esc_html_e( 'Portal item ID of a 3D Web Scene. When set, INSERT_MAP will render a 3D SceneView instead of a 2D MapView.', 'geopress' ); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="arcgis_feature_layer_url"><?php esc_html_e( 'Feature Layer URL', 'geopress' ); ?>:</label></th>
				<td>
					<input type="url" name="arcgis_feature_layer_url" id="arcgis_feature_layer_url" value="<?php echo esc_attr( $arcgis_fl_url ); ?>" style="width:60%;" />
					<br /><em><?php esc_html_e( 'URL of a Feature Layer service to overlay on all ArcGIS maps. Takes precedence over Feature Layer Item ID if both are set.', 'geopress' ); ?></em>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="arcgis_feature_layer_item_id"><?php esc_html_e( 'Feature Layer Item ID', 'geopress' ); ?>:</label></th>
				<td>
					<input type="text" name="arcgis_feature_layer_item_id" id="arcgis_feature_layer_item_id" value="<?php echo esc_attr( $arcgis_fl_item_id ); ?>" style="width:40%;" />
					<br /><em><?php esc_html_e( 'Portal item ID of a Feature Layer to overlay on all ArcGIS maps. Used only when Feature Layer URL is empty.', 'geopress' ); ?></em>
				</td>
			</tr>
		</table>
		</fieldset>

		<div class="submit">
			<input type="submit" name="Options" value="<?php echo esc_attr__( 'Save Map Layout', 'geopress' ); ?> &raquo;" />
		</div>
		</form>
		</div>
		<?php
	}

	// ── Options page ──────────────────────────────────────────────────────────

	public static function geopress_options_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'geopress' ) );
		}

		if ( isset( $_POST['Options'] ) && check_admin_referer( 'geopress_options_nonce', 'geopress_nonce' ) ) {
			update_option( '_geopress_rss_enable',     isset( $_POST['georss_enable'] ) ? 'true' : 'false' );
			update_option( '_geopress_rss_format',     sanitize_key( wp_unslash( $_POST['georss_format'] ?? 'simple' ) ) );
			update_option( '_geopress_default_add_map',(int) ( $_POST['default_add_map'] ?? 0 ) );
			update_option( '_geopress_google_apikey',  sanitize_text_field( wp_unslash( $_POST['google_apikey'] ?? '' ) ) );
			update_option( '_geopress_yahoo_appid',    sanitize_text_field( wp_unslash( $_POST['yahoo_appid'] ?? '' ) ) );
			echo '<div class="updated"><p><strong>' . esc_html__( 'Map options updated.', 'geopress' ) . '</strong></p></div>';
		}

		$rss_enable      = get_option( '_geopress_rss_enable', 'true' ) === 'true' ? 'checked="checked"' : '';
		$default_add_map = (int) get_option( '_geopress_default_add_map', 0 );
		$rss_format      = get_option( '_geopress_rss_format', 'simple' );
		$google_apikey   = get_option( '_geopress_google_apikey', '' );
		$yahoo_appid     = get_option( '_geopress_yahoo_appid', '' );
		?>
		<div class="wrap">
		<h2><?php esc_html_e( 'Customize GeoPress', 'geopress' ); ?></h2>
		<form method="post">
		<?php wp_nonce_field( 'geopress_options_nonce', 'geopress_nonce' ); ?>
		<p><?php esc_html_e( 'Welcome to GeoPress. Configure your API keys and default settings below. Then go and start writing geotagged posts!', 'geopress' ); ?></p>
		<fieldset class="options">
			<legend><?php esc_html_e( 'Map', 'geopress' ); ?></legend>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform">
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'GoogleMaps Key', 'geopress' ); ?>:</th>
					<td>
						<input name="google_apikey" type="text" id="google_apikey" style="width: 95%" value="<?php echo esc_attr( $google_apikey ); ?>" size="45" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Yahoo AppID', 'geopress' ); ?>:</th>
					<td>
						<input name="yahoo_appid" type="text" id="yahoo_appid" style="width: 95%" value="<?php echo esc_attr( $yahoo_appid ); ?>" size="45" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Add Maps', 'geopress' ); ?>:</th>
					<td>
						<select name="default_add_map" id="default_add_map">
							<?php
							$add_map_opts = array(
								0 => __( "I'll do it myself, thanks", 'geopress' ),
								1 => __( 'Only on single post pages', 'geopress' ),
								2 => __( 'Give me everything — any post, any page', 'geopress' ),
							);
							foreach ( $add_map_opts as $val => $label ) {
								printf(
									'<option value="%d"%s>%s</option>',
									$val,
									selected( $default_add_map, $val, false ),
									esc_html( $label )
								);
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		</fieldset>
		<fieldset>
			<legend><?php esc_html_e( 'GeoRSS Feeds', 'geopress' ); ?></legend>
			<table width="100%" cellspacing="2" cellpadding="5" class="editform">
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'GeoRSS Feeds', 'geopress' ); ?>:</th>
					<td>
						<label for="georss_enable">
							<input name="georss_enable" type="checkbox" id="georss_enable" value="true" <?php echo $rss_enable; ?> />
							<?php esc_html_e( 'Enable GeoRSS tags in feeds', 'geopress' ); ?>
						</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php esc_html_e( 'Feed Format', 'geopress' ); ?>:</th>
					<td>
						<select name="georss_format" id="georss_format">
							<?php
							$feed_formats = array(
								'simple' => 'Simple &lt;georss:point&gt;',
								'gml'    => 'GML &lt;gml:pos&gt;',
								'w3c'    => 'W3C &lt;geo:lat&gt;',
							);
							foreach ( $feed_formats as $val => $label ) {
								printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $rss_format, $val, false ), $label );
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		</fieldset>
		<div class="submit">
			<input type="submit" name="Options" value="<?php echo esc_attr__( 'Update Options', 'geopress' ); ?> &raquo;" />
		</div>
		</form>
		</div>
		<?php
	}

	// ── Documentation page ────────────────────────────────────────────────────

	public static function geopress_documentation_page() {
		echo '<div class="wrap"><h2>' . esc_html__( 'GeoPress Documentation', 'geopress' ) . '</h2>';
		?>
		<h3><?php esc_html_e( 'About GeoPress', 'geopress' ); ?></h3>
		<p><?php esc_html_e( 'GeoPress adds geographic tagging to your posts. Enter a location name or address in the post editor, or use [latitude, longitude] format. GeoPress stores the location and can embed interactive maps, and export GeoRSS/KML/GPX feeds.', 'geopress' ); ?></p>
		<h4><?php esc_html_e( 'Post Content Tags', 'geopress' ); ?></h4>
		<ul>
			<li><code>INSERT_MAP</code> — <?php esc_html_e( 'embeds a map at the post\'s location', 'geopress' ); ?></li>
			<li><code>INSERT_MAP(height,width)</code> — <?php esc_html_e( 'map with custom size', 'geopress' ); ?></li>
			<li><code>INSERT_MAP(height,width,url)</code> — <?php esc_html_e( 'map with KML/GeoRSS overlay', 'geopress' ); ?></li>
			<li><code>INSERT_GEOPRESS_MAP(height,width)</code> — <?php esc_html_e( 'map of all geotagged posts', 'geopress' ); ?></li>
			<li><code>INSERT_COORDS</code> — <?php esc_html_e( 'geo microformat coordinates', 'geopress' ); ?></li>
			<li><code>INSERT_ADDRESS</code> — <?php esc_html_e( 'address in adr microformat', 'geopress' ); ?></li>
			<li><code>INSERT_LOCATION</code> — <?php esc_html_e( 'location name in hCard microformat', 'geopress' ); ?></li>
			<li><code>GEOPRESS_LOCATION(Address)</code> — <?php esc_html_e( 'set location inline by address', 'geopress' ); ?></li>
			<li><code>GEOPRESS_LOCATION([lat,lon])</code> — <?php esc_html_e( 'set location inline by coordinates', 'geopress' ); ?></li>
		</ul>
		<h4><?php esc_html_e( 'Template Functions', 'geopress' ); ?></h4>
		<ul>
			<li><code>has_location()</code></li>
			<li><code>the_coord()</code></li>
			<li><code>the_address()</code></li>
			<li><code>the_location_name()</code></li>
			<li><code>the_geo_mf()</code></li>
			<li><code>the_adr_mf()</code></li>
			<li><code>the_loc_mf()</code></li>
			<li><code>geopress_map($height, $width, $num_locs)</code></li>
			<li><code>geopress_post_map($height, $width, $controls)</code></li>
			<li><code>geopress_page_map($height, $width, $controls)</code></li>
			<li><code>geopress_locations_list()</code></li>
			<li><code>geopress_kml_link()</code></li>
		</ul>
		<?php
		echo '</div>';
	}
}
