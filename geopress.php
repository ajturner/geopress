<?php
/*
GeoPress
Plugin Name: GeoPress 
Plugin URI:  http://georss.org/geopress/
Description: GeoPress adds geographic tagging of your posts and blog. You can enter an address, points on a map, upload a GPX log, or enter latitude & longitude. You can then embed Maps, location tags, and ground tracks in your site and your blog entries. Makes your feeds GeoRSS compatible and adds KML output. (http://georss.org/geopress)
Version: 2.5-beta
Author: Andrew Turner & Mikel Maron
Author URI: http://mapufacture.com
Author URI: http://highearthorbit.com
Author URI: http://brainoff.com

*/

/*  Copyright 2006-8  Andrew Turner, Mikel Maron

	Copyright 2005  Ravi Dronamraju
	
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

	Additional Contributors: 
	Barry () - added KML output [3/1/07]
	David Fraga (comerporlapatilla.com) - Added map for locations in the main wordpress loop [4/11/07]
	
	Supported by:
	Allan () - by post zoom/maptype settings
*/

define('google_geocoder', 'http://maps.google.com/maps/geo?q=', true);
define('google_regexp', "<coordinates>(.*),(.*),0</coordinates>", true);
define('yahoo_regexp', "<Latitude>(.*)<\/Latitude>.*<Longitude>(.*)<\/Longitude>", true);
define('yahoo_geocoder', 'http://api.local.yahoo.com/MapsService/V1/geocode?appid=geocodewordpress&location=', true);
define('yahoo_annotatedmaps', 'http://api.maps.yahoo.com/Maps/V1/AnnotatedMaps?appid=geocodewordpress&xmlsrc=', true);
define('yahoo_embedpngmapurl', 'http://api.local.yahoo.com/MapsService/V1/mapImage?appid=geocodewordpress&', true);
define('GEOPRESS_USE_ZIP', true);
define('GEOPRESS_FETCH_TIMEOUT', 2);
define('GEOPRESS_USER_AGENT',  'GeoPress2.0', false);
define('GEOPRESS_LOCATION', 'geopress/', false);
define('GEOPRESS_VERSION', '2.4.3', false);

if ( !function_exists('Snoopy') ) { 
  require_once(ABSPATH.WPINC.'/class-snoopy.php');
  error_reporting(E_ERROR);
}

function geocode($location, $geocoder) {
  if($geocoder == null) {
    $geocoder = "yahoo";
  }
  
  if( !preg_match('/\[(.+),[ ]?(.+)\]/', $location, $matches) ) { 
    $client = new Snoopy();
    $client->agent = GEOPRESS_USER_AGENT;
    $client->read_timeout = GEOPRESS_FETCH_TIMEOUT;
    $client->use_gzip = GEOPRESS_USE_GZIP;
    if($geocoder == 'google') {
      $url = google_geocode . urlencode($location);
      $regexp = google_regexp;
    }
    elseif($geocoder == 'yahoo') {
      $url = yahoo_geocoder . urlencode($location);
      $regexp = yahoo_regexp;
    }

    @$client->fetch($url);
    $xml = $client->results;

    $lat = "";
    $lon = "";
    $latlong = "";

    if ($geocoder == 'google' && preg_match("/$regexp/", $xml, $latlong)) { 
      $lat = trim($latlong[1]);
      $lon = trim($latlong[2]);
    } 
    elseif ($geocoder == 'yahoo' && preg_match("/$regexp/", $xml, $latlong)) { 
      $lat = trim($latlong[1]);
      $lon = trim($latlong[2]);
    }
  }
  else {
    $lat = trim($matches[1]);
    $lon = trim($matches[2]);
  }
  return array($lat, $lon);
}

function yahoo_geocode($location) {
  if( !preg_match('/\[(.+),[ ]?(.+)\]/', $location, $matches) ) { 

    $client = new Snoopy();
    $client->agent = GEOPRESS_USER_AGENT;
    $client->read_timeout = GEOPRESS_FETCH_TIMEOUT;
    $client->use_gzip = GEOPRESS_USE_GZIP;
    $url = yahoo_geocoder . urlencode($location);

    @$client->fetch($url);
    $xml = $client->results;
	$dom = domxml_open_file($xml); 


    $lat = "";
    $lon = "";

	// $lat = $dom->get_elements_by_tagname('Latitude')[0].child_nodes()[0]->node_value();
	// $lon = $dom->get_elements_by_tagname('Longitude')[0].child_nodes()[0]->node_value();

    // $latlong = "";
    // if (preg_match("/<Latitude>(.*)<\/Latitude>.*<Longitude>(.*)<\/Longitude>/", $xml, $latlong)) { 
    //   $lat = $latlong[1];
    //   $lon = $latlong[2];
    // } 
  }
  else {
    $lat = $matches[1];
    $lon = $matches[2];
  }
  return array($lat, $lon);
}
// Converts a zoom from 1 (world) to 18 (closest)  to Yahoo coords: 1 (close) 12(country)
function yahoo_zoom($zoom) {
	return ceil(12 / $zoom);
}
function yahoo_mapurl($location) { 

    $client = new Snoopy();
    $client->agent = GEOPRESS_USER_AGENT;
    $client->read_timeout = GEOPRESS_FETCH_TIMEOUT;
    $client->use_gzip = GEOPRESS_USE_GZIP;
    $mapwidth = get_settings('_geopress_mapwidth', true);
    $mapheight= get_settings('_geopress_mapheight', true);
    $url = yahoo_embedpngmapurl . "image_width=" . $mapwidth . "&image_height=" . $mapheight;
 	$url .= "&zoom=" . (yahoo_zoom( GeoPress::mapstraction_map_zoom())); // TODO: put in an appropriate conversion function
	
	// Get the image for a location, or just lat/lon
	if( !preg_match('/\[(.+),[ ]?(.+)\]/', $location, $matches) ) { 
		$url .= "&location=" . urlencode($location);
	} else {
		$url .= "&latitude=" . $matches[1] . "&longitude=" . $matches[2];
	}

    @$client->fetch($url);
    $xml = $client->results;
    
    $mapinfo = "";
    if (preg_match("/<Result xmlns:xsi=\"[^\"]*\"( warning=\"[^\"]*\")?>(.*)<\/Result>/", $xml, $mapinfo)) { 
      $warn = $mapinfo[1];
      $mapurl = $mapinfo[2];
    }

    return array($warn, $mapurl);
      
}

class GeoPress {  
  function install() {
  	global $table_prefix, $wpdb;

  // Do a dbDelta to make any necessary updates to the database depending on previous GeoPress version
  $table_name = $table_prefix . "geopress";
  $sql = "CREATE TABLE $table_name (
	id int(11) NOT NULL AUTO_INCREMENT,
	name tinytext NOT NULL,
	loc	tinytext,
	warn tinytext,
	mapurl tinytext,
	coord text NOT NULL,
	geom varchar(16) NOT NULL,
	relationshiptag tinytext,
	featuretypetag tinytext,
	elev float,
	floor float,
	radius float,
	visible tinyint(4) DEFAULT 1,
	map_format tinytext DEFAULT '',
	map_zoom tinyint(4) DEFAULT 0,
	map_type tinytext DEFAULT '',
	UNIQUE KEY id (id)
	);";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);

	// One time change necessary to convert from id to geopress_id
	$update_to_geopress_id = 0;
	$sql = "DESCRIBE $table_name;";
	$tablefields = $wpdb->get_results( $sql );
	foreach($tablefields as $tablefield) {	
		if(strtolower($tablefield->Field) == "id")
		{
			$update_to_geopress_id = 1;
			break;
		}
	}
	if($update_to_geopress_id)
	{
		$sql = "ALTER TABLE $table_name CHANGE id geopress_id int(11) NOT NULL auto_increment;";
		$result = $wpdb->get_results( $sql );
	}
	// default options
	add_option('_geopress_mapwidth', "400");
	add_option('_geopress_mapheight', "200");
	add_option('_geopress_marker', $plugindir."/flag.png");
	add_option('_geopress_rss_enable', "true");
	add_option('_geopress_rss_format', "simple");
	add_option('_geopress_map_format', "openlayers");

	add_option('_geopress_map_type', 'hybrid');
	add_option('_geopress_controls_pan', true);
	add_option('_geopress_controls_map_type', true);
	add_option('_geopress_controls_zoom', "small");
	add_option('_geopress_controls_overview', false);
	add_option('_geopress_controls_scale', true);
	add_option('_geopress_default_add_map', 0);
	add_option('_geopress_default_zoom_level', "11");

	$ping_sites = get_option("ping_sites");
	if( !preg_match('/mapufacture/', $ping_sites, $matches) ) { 
		update_option("ping_sites", $ping_sites . "\n" . "http://mapufacture.com/georss/ping/api");
    }
  }

  // Returns an array of locations, each key containing the array of posts at the location
  function get_location_posts ($number = -1) {
    global $table_prefix, $wpdb;
    $geopress_table = $table_prefix . "geopress";

    $sql = "SELECT * FROM $geopress_table, $wpdb->postmeta";
    $sql .= " INNER JOIN $wpdb->posts ON $wpdb->posts.id = $wpdb->postmeta.post_id";
    $sql .= " WHERE $wpdb->postmeta.meta_key = '_geopress_id'";
    $sql .= " AND $wpdb->postmeta.meta_value = $geopress_table.geopress_id";
	$sql .= " AND $wpdb->posts.post_status = 'publish'";
    $sql .= " AND coord != ''";
    if($number >= 0) {
      $sql .= " LIMIT ".$number;
    }
    $result = $wpdb->get_results( $sql );

    //  Build a hash of Location => Posts @ location
    $locations = array();
    foreach ($result as $loc) {
        if($locations[$loc->name] == null) {
            $locations[$loc->name] = array();
        }
        array_push($locations[$loc->name], $loc);
    }
    return $locations;
  }
  function get_locations ($number = -1) {
    global $table_prefix, $wpdb;
    $geopress_table = $table_prefix . "geopress";

    $sql = "SELECT * FROM $geopress_table";
    $sql .= " INNER JOIN $wpdb->postmeta ON $wpdb->postmeta.meta_key = '_geopress_id'";
    $sql .= " AND $wpdb->postmeta.meta_value = $geopress_table.geopress_id";
    $sql .= " WHERE coord != '' GROUP BY 'name'";
	$sql .= " AND $wpdb->posts.post_status = 'publish'";
    if($number >= 0) {
      $sql .= " LIMIT ".$number;
    }
    $result = $wpdb->get_results( $sql );
    // echo $sql; // debug
    return $result;
  }
  // dfraga - Getting loop locations 
  function get_loop_locations ($locations = -1) {
      $result = "";
      $i = 0;
      while (have_posts()) { the_post();
          // echo "get_loop_locations: -> ".get_the_ID()." -> ".get_the_title()."<br />";
          $geo = GeoPress::get_geo(get_the_ID());
          if ($geo != "") {
              $result[] = $geo;
              $i++;
              if ($i == $locations) {
                  break;
              }
          }
      }
    //  Build a hash of Location => Posts @ location
    $locations = array();
    foreach ($result as $loc) {
        if($locations[$loc->coord] == null) {
            $locations[$loc->coord] = array();
        }
        array_push($locations[$loc->coord], $loc);

    }
    return $locations;	
  }

  // function get_bounds($locations) {
  //   //  lat = 0;
  //   //  lon = 0;
  //   //  zoom = 1;
  //   latbounds = [MAXINT, -MAXINT];
  //   lonbounds = [MAXINT, -MAXINT];
  //   if(count($locations) > 0) {
  //     foreach($locations as $loc) {
  //       $coords = split(" ",$loc->coord);
  //       lat += $loc->coords[0];
  //       lon += $loc->coords[0];
  //       if(lat > latbounds[1]) latbounds[1] = lat;
  //       if(lat < latbounds[0]) latbounds[0] = lat;
  //       if(lon > lonbounds[1]) lonbounds[1] = lon;
  //       if(lon < lonbounds[0]) lonbounds[0] = lon;
  // 
  //     }
  //     //    lat = lat / count($locations);
  //     //    lon = lon / count($locations);
  //   }
  //   return [latbounds[0],lonbounds[0],latbounds[1],lonbounds[1]];
  // }

function get_geo ($id) {
  global $table_prefix, $wpdb;
    // 
    // $sql = "SELECT * FROM $geopress_table";
    // $sql .= " INNER JOIN $wpdb->postmeta ON $wpdb->postmeta.meta_key = '_geopress_id'";
    // $sql .= " AND $wpdb->postmeta.meta_value = $geopress_table.geopress_id";
    // $sql .= " WHERE AND coord != '' GROUP BY 'name'";
    // if($number >= 0) {
    //   $sql .= " LIMIT ".$number;
    // }

  $geopress_table = $table_prefix . "geopress";
  $geo_id = get_post_meta($id,'_geopress_id',true);
  if ($geo_id) {
    $sql = "SELECT * FROM $geopress_table, $wpdb->postmeta";
    $sql .= " INNER JOIN $wpdb->posts ON $wpdb->posts.id = $wpdb->postmeta.post_id";
    $sql .= " WHERE $wpdb->postmeta.meta_key = '_geopress_id'";
    $sql .= " AND $wpdb->postmeta.meta_value = $geopress_table.geopress_id";
    $sql .= " AND $wpdb->postmeta.post_id = $id AND $geopress_table.geopress_id = $geo_id";
    $row = $wpdb->get_results( $sql );
    return $row[0];
  }
}

function get_location ($loc_id) {
  if ($loc_id) {
    global $table_prefix, $wpdb;

    $table_name = $table_prefix . "geopress";

    $sql = "SELECT * FROM ".$table_name." WHERE geopress_id = ".$loc_id;
    $row = $wpdb->get_row( $sql );
    return $row;
  }
}

// Store the location and map parameters to the database
function save_geo ($id, $name,$loc,$coord,$geom,$warn,$mapurl,$visible = 1,$map_format = '', $map_zoom = 0, $map_type = '') {
  global $table_prefix, $wpdb;

  if($name == "") { $visible = 0; }

  $table_name = $table_prefix . "geopress";

  if($id && $id != -1) {
  	$sql = "SELECT * FROM $table_name WHERE geopress_id = $id";
  } else {
  	$sql = "SELECT * FROM $table_name WHERE (name = '$name' AND coord = '$coord') OR loc = '$loc'";
  }
  $row = $wpdb->get_row( $sql );
  //TODO SQL INJECTION POSSIBLE?
  if ($row) {
    $geo_id = $row->geopress_id;
    $sql = "UPDATE ".$table_name." SET name = '$name', loc = '$loc', coord = '$coord', geom = '$geom', warn = '$warn', mapurl = '$mapurl', visible = '$visible', map_format = '$map_format', map_zoom = '$map_zoom', map_type = '$map_type' WHERE geopress_id = '$geo_id'";
    $wpdb->query( $sql );
  } else {
    $sql = "INSERT INTO ".$table_name." VALUES (NULL,'$name','$loc','$warn','$mapurl','$coord','$geom',NULL,NULL,NULL,NULL,NULL,'$visible','$map_format','$map_zoom','$map_type')";
    $wpdb->query( $sql );
    $geo_id = mysql_insert_id();
  }
  return $geo_id;
}

  function default_loc() {
    global $table_prefix, $wpdb;

    $table_name = $table_prefix . "geopress";
    $sql = "SELECT * FROM ".$table_name." LIMIT 1";	
    $result = $wpdb->get_results( $sql );
    foreach ($result as $row) {
      return $row->loc;
    }	
  }
  function select_saved_geo () {
    global $table_prefix, $wpdb;

    $table_name = $table_prefix . "geopress";
    $sql = "SELECT * FROM ".$table_name." WHERE visible = 1";
    $result = $wpdb->get_results( $sql );
    foreach ($result as $row) {
      echo "<option value=\"" . $row->loc . "\"";
      echo ">" . $row->name . "</option>\n";
    }
  }
  function map_saved_locations ($locations) {
    global $table_prefix, $wpdb;

    $output = geopress_map_select(250, 250, "float:right;");
    $output .= "<script type='text/javascript'>\n";
    
    if($locations == null) {
        $table_name = $table_prefix . "geopress";
        $sql = "SELECT * FROM ".$table_name;
        $locations = $wpdb->get_results( $sql );
    }
	
	$geopress_marker = get_settings('_geopress_marker', true);
    $output .= "geopress_addEvent(window,'load', function() { \n";
    foreach ($locations as $row) {
      if($row->coord != " ") {
        $coords = preg_split('/\s+/',$row->coord);
        $output .= "\tvar myPoint = new LatLonPoint( $coords[0], $coords[1]);\n";
        $output .= "\tvar marker = new Marker(myPoint);\n";
        $output .= "\tgeo_map$map_id.addMarkerWithData(marker,{ infoBubble: \"" . htmlentities($row->name) . "\", icon:\"$geopress_marker\", iconSize:[24,24], iconShadow:\"".$plugindir."/blank.gif\", iconShadowSize:[0,0] });\n";
      }
    }
    $output .= "});\n</script>";

    echo $output;
  }

  function location_edit_form () { 
    global $post_ID;

	$geo = GeoPress::get_geo($post_ID);
?>

<?php
	GeoPress::geopress_new_location_form($geo);
  }
  
  function geopress_new_location_form($geo) {
	echo '<div id="locationdiv" class="postbox">
          <h3> <a href="http://www.georss.org/">' . __('Location','GeoPress') . '</a></h3>';
    $loc = $geo->loc;
    $geometry = $geo->coord;
    $locname = $geo->name;
    echo '<div class="inside">
          <table width="100%" cellpadding="3" cellspacing="3">
               <thead>
                  <tr>
                  <th scope="col" align=left>'.__('Saved Name', 'GeoPress').'</th>
                  <th scope="col" colspan=3 align=left>'.__('Location Name, Address, or [Latitude, Longitude]', 'GeoPress').'</th>
				  <th scope="col" colspan=3 align=left></th>
			     </tr>
                </thead>
           <tbody>
                 <tr>
          ';
    echo '<td width=15%> <input size="10" type="text" value="' . $locname . '" name="locname" id="locname" /></td> ';
    echo '<td width=50%> <input size="50" type="text" value="' . $loc . '" name="addr" id="addr" onKeyPress="return checkEnter(event);"/></td> ';
    echo "<td width=20%> <a href='#' onclick='geocode();return false;' title='Geocode this address' id='geocode'>Map Location</a></td>";
    echo '</tr>
          </tbody>
          </table>';
    echo '<input size="50" type="hidden" value="' . $geometry . '" name="geometry" id="geometry" style="hidden" />';
    echo '
          <p>
          <table width="30%" cellpadding="3" cellspacing="3">
          <tbody>
                  <tr>
                    <td scope="col" align=left>'.__('Saved Locations', 'GeoPress').'</td>
					<td rowspan="3">';
	echo geopress_map_select();

	echo '</td>		
                  </tr>
          <tr>
          	<td><label for="geopress_select"> <select id="geopress_select" onchange="geopress_loadsaved(this);showLocation(\'addr\',\'geometry\');"><option value="">--choose one--</option>';
			GeoPress::select_saved_geo();
		    echo '</td>';
    echo '
          </tr>
		  <tr><td width="20%" height="200px"> <a href="#" onclick="geopress_resetMap();return false;" title="Zoom out and center map" id="geocode">Reset Map</a></td></tr>
          </tbody>
          </table>
		</div> <!-- class="inside" -->';        
	
    // echo '
    //  <input type="text" id="geopress_map_format" name="geopress_map_format" value=""/>
    //  <input type="text" id="geopress_map_zoom" name="geopress_map_zoom" value="0"/>
    //  <input type="text" id="geopress_map_type" name="geopress_map_type" value=""/>
    // ';
	// If there is already a geo location - map it
	if($geo) {
?>
	<script type="text/javascript">
		geopress_addEvent(window,'load', function() {showLocation();});
	</script>
<?php
	}
	?>
	
	<?php
    echo '</div>';
  }
  function geopress_admin_page() { 
	  echo "<h2>Locations</h2>";
  }

  function geopress_documentation_page() { 
	  echo '<div class="wrap"><h2>GeoPress Documentation</h2>';
	  ?>
	  <h3>About GeoPress</h3>
	  <p>GeoPress is a tool to help you embed geographic locations into your blog posts, and also include this information in your RSS/Atom syndicated feeds using the <a href="http://georss.org" title="GeoRSS.org website">GeoRSS</a> standard.</p>
	  <p>To begin using GeoPress, write a new article and enter a location name a geographic address in the appropriate fields. Press <em>enter</em>, or click the <em>Geocode</em> button to verify on the map that this is the appropriate location. Additionally, you can click on the map to set a location. Once you save your post, the geographic location is stored with the post entry. If you want to just enter latitude and longitude, then enter <code>[latitude, longitude]</code> into the address field.</p>
	  <p>Notice to users of WordPress 2.1+: there are now default privacy settings that prevent your blog from pinging Blog aggregators like <a href="http://technorati.com/">Technorati</a> or <a href="http://mapufacture.com/">Mapufacture</a>, or being searched by <a href="http://www.google.com/">Google</a>. To change your privacy settings, go to "Options" -> "Privacy" and allow your blog to be visible by anyone. This will let aggregators and search engines allow users and readers to find your blog.</p>
	  <h4>Adding to your post</h4>
	  <p>You can insert a dynamic map into your post automatically by selecting "Automatically add a map after any post" in the GeoPress options tab. This map will be inserted at the end of any post that has a location set for it.</p>
	  <p>Alternatively, you can manually insert a map by putting <code>INSERT_MAP</code> anywhere in your post text. The map will use the default map size as sent in your GeoPress options. You can override this size by passing in INSERT_MAP(height,width), where height and width are the size of the map, in pixels.</p>
	  <p>You can also insert the geographic coordinates, or address of the post by using <code>INSERT_COORDS</code>, and <code>INSERT_ADDRESS</code>, respectively. These will be output using <a href="http://microformats.org" title="Microformats homepage">Microformat</a> styling. </p>
	  <p>INSERT_LOCATION will put in the stored name of the location into a post.</p>
	  <p>INSERT_MAP(height,width,url) will add a map for the post and also a KML or GeoRSS Overlay from the URL.</p>
	  <p>A map of all your geotagged posts can be inserted by using INSERT_GEOPRESS_MAP(height,width).</p>
	  <p>You can set the location of a post within the body of the post itself by using <code>GEOPRESS_LOCATION(Location String)</code>, where <em>Location String</em> can be any text that normally works in the GeoPress location box. For example address, city, region, country, etc. You can also post coordinates by doing <code>GEOPRESS_LOCATION([latitude,longitude]). An alternative is to use machine-tags in the Post, like <code>tags: geo:long=24.9419260025024 geo:lat=60.1587851399795</code>, for example. These two mechanisms make it easy to add GeoPress locations when using an offline blog client, or when posting by email or SMS.</p>
	  <h4>Limitations</h4>
	  <p>Currently, GeoPress only supports a single geographic coordinate. In the future it will support lines, polygons, and multiple points.</p>
	  <h3>Template Functions</h3>
	  <p>These functions are available from GeoPress to further customize embedding geographic information into your blog. The <strong>Post</strong> functions return information about a specific post, or entry, and should be placed within the <em>the_post()</em> section of your templates. <strong>General</strong> functions can be used anywhere in your blog template and will return information pertaining to all of your geographic locations (such as maps, lists, links to locations)</p>
	  <h4>General Functions</h4>
	  <p>The following functions <em>return</em> the output. This allows you to perform any processing on the return text that you may want. To finally place the result in your template, use <code>echo</code>. For example, to output the stored address: <code>&lt;?php echo the_address(); ?&gt;</code></p>
	  <ul>
	   <li><code>geopress_map(height, width, num_locs)</code>: returns a GeoPress map of the last <code>num_locs</code> number of locations. If no value is set for <code>num_locs</code>, then all locations are plotted. <em>caution</em>: plotting all locations could slow down/prevent people viewing your blog.</li>
	  </ul>
	  <h4>Post Functions</h4>
	  <ul>
	   <li><code>has_location()</code>: returns 'true' if the post has a location, 'false' if no location was set</li>
	   <li><code>geopress_post_map(height, width, controls)</code>: returns a GeoPress map of the current post's location. <code>height, width</code> sets the map size in pixels. <code>controls</code> is boolean if you want controls or no controls</li>
	   <li><code>the_coord()</code>: returns the coordinates for the post as an array, latitude, longitude</li>
	   <li><code>the_address()</code>: returns the address for the post</li>
	   <li><code>the_location_name()</code>: returns the saved name for the post's location</li>
	   <li><code>the_geo_mf()</code>: returns the coordinates of the post in <a href="http://microformats.org/wiki/geo" title="Microformats Wiki: geo">Microformat geo</a> format </li>
	   <li><code>the_adr_mf()</code>: returns the address of the post in <a href="http://microformats.org/wiki/adr" title="Microformats Wiki: adr">Microformat adr</a> format </li>
	   <li><code>the_loc_mf()</code>: returns the location name of the post in <a href="http://microformats.org/wiki/hcard" title="Microformats Wiki: hCard">Microformat hCard</a> format </li>
	  </ul>
	  <h4>Template Functions</h4>
	  <p>GeoPress provides the ability to view all the posts at a specific location by putting "location=#" in the url, where # is the id number of the location, or "location=savedname", where <code>savedname</code> is the the name of the location (e.g. Home or Trafalgar Square)</p>
	  <ul>
	   <li><code>geopress_location_name()</code>: prints out the name of the location if it is passed in by the url..</li>
	   <li><code>geopress_locations_list()</code>: prints out an unordered list of locations and links to display posts at that location.</li>
	  </ul>
	  <?php
	  echo '</div> <!-- wrap -->';
  }
  function geopress_locations_page() { 
    if(isset($_POST['Options'])) { 
      for($i = 0; $i < count($_POST['locname']);$i++) {
        // If the user set the locations via the web interface, don't change it here.
        if( !preg_match('/\[(.+),[ ]?(.+)\]/', $_POST['geometry'], $matches) ) { 
          list($lat, $lon) = geocode($_POST['locaddr'][$i]);
        }
        else {
          $lat = $matches[1];
          $lon = $matches[2];          
        }
        list($warn, $mapurl) = yahoo_mapurl($_POST['locaddr'][$i]);
        $geo_id = GeoPress::save_geo($_POST['locid'][$i], $_POST['locname'][$i], $_POST['locaddr'][$i], "$lat $lon", "point", $warn, $mapurl, $_POST['locvisible'][$i]);
      }
      echo '<div class="updated"><p><strong>' . __('Locations updated.', 'GeoPress') . '</strong></p></div>';
    }   

	echo '<div class="wrap"><h2>Configure Locations</h2>';
	echo '<form method="post">';
	global $table_prefix, $wpdb;


	$table_name = $table_prefix . "geopress";
	$sql = "SELECT * FROM ".$table_name;
	$result = $wpdb->get_results( $sql );
    echo '<div style="width: 70%; float: left;">
          <table width="100%" cellpadding="3" cellspacing="3" style="">
               <thead>
                  <tr>
                  <th scope="col" align=left>'.__('Show', 'GeoPress').'</th>
                  <th scope="col" align=left>'.__('Name', 'GeoPress').'</th>
                  <th scope="col" align=left>'.__('Address', 'GeoPress').'</th>
				  <th scope="col" align=left>'.__('Geometry', 'GeoPress').'</th>
			     </tr>
                </thead>
           <tbody>';

	$i = -1;
	foreach ($result as $loc) {
		$i++;
		if($loc->visible) { $checked = "checked='checked'";}
		else {$checked = "";}
?>
	    <tr>
			<td width=5%><input type="hidden" name="locid[<?php echo $i?>]" value="<? echo $loc->geopress_id?>"/><input type="checkbox" value="1" name='locvisible[<?php echo $i?>]' <?php echo $checked?>/></td>
	    	<td width=15%> <input size="10" type="text" value="<?php echo $loc->name?>" name='locname[<?php echo $i?>]' /></td>
	    	<td width=50%> <input size="40" type="text" value="<?php echo $loc->loc?>" name='locaddr[<?php echo $i?>]' /></td>
	    	<td> <input type="text" disabled="disabled" value="<?php echo $loc->coord?>" name='loccoord[<?php echo $i?>]' /></td>

<?php
	    echo "</tr>\n";
	}	
    echo '</tbody>
          </table>';	
	echo '<div class="submit"><input type="submit" name="Options" value="'. __('Save Locations', 'GeoPress') . '&raquo;" /></div></div>';
     GeoPress::map_saved_locations($result);
    
  }
  function geopress_maps_page() { 
    if(isset($_POST['Options'])) { 
      $default_mapwidth = $_POST['default_mapwidth'];
      $default_mapheight = $_POST['default_mapheight'];
      $default_marker = $_POST['default_marker'];
      $default_zoom_level = $_POST['default_zoom_level'];
      $map_controls_type = $_POST['map_controls_type'];
      $map_view_type = $_POST['map_view_type'];

      $map_controls_pan = $_POST['map_controls_pan'];
      $map_controls_map_type = $_POST['map_controls_map_type'];
      $map_controls_zoom = $_POST['map_controls_zoom'];
      $map_controls_overview = $_POST['map_controls_overview'];
      $map_controls_scale = $_POST['map_controls_scale'];
      $map_format = $_POST['map_format'];


      update_option('_geopress_map_format', $map_format);
      update_option('_geopress_mapwidth', $default_mapwidth);
      update_option('_geopress_mapheight', $default_mapheight);
      update_option('_geopress_marker', $default_marker);
      update_option('_geopress_map_type', $map_view_type);
      update_option('_geopress_controls_pan', $map_controls_pan);
      update_option('_geopress_controls_map_type', $map_controls_map_type);
      update_option('_geopress_controls_zoom', $map_controls_zoom);
      update_option('_geopress_controls_overview', $map_controls_overview);
      update_option('_geopress_controls_scale', $map_controls_scale);
      update_option('_geopress_default_zoom_level', $default_zoom_level);

      echo '<div class="updated"><p><strong>' . __('Map layout updated.', 'GeoPress') . '</strong></p></div>';
    }

    $map_format = get_settings('_geopress_map_format', true);
    $default_mapwidth = get_settings('_geopress_mapwidth', true);
    $default_mapheight = get_settings('_geopress_mapheight', true);
    $default_marker = get_settings('_geopress_marker', $plugindir."/flag.png");
    $default_zoom_level = get_settings('_geopress_default_zoom_level', true);
    $map_view_type = get_settings('_geopress_map_type', true);
    $map_controls_zoom = get_settings('_geopress_controls_zoom', true);
    $map_controls_pan = get_settings('_geopress_controls_pan ', true) ? 'checked="checked"' : '';
    $map_controls_overview = get_settings('_geopress_controls_overview', true) ? 'checked="checked"' : '';
    $map_controls_scale = get_settings('_geopress_controls_scale', true) ? 'checked="checked"' : '';
    $map_controls_map_type = get_settings('_geopress_controls_map_type', true) ? 'checked="checked"' : '';


	echo '<div class="wrap"><h2>Configure Map Layout</h2>';
?>
<h3>About</h3>
<div><p>This page configures the default map that will appear with posts and when you use INSERT_MAP. By setting the map size, default zoom level, and various controls that appear, you can customize how the maps on your site look.</p>
<p>Unfortunately, not all mapping providers (Google, Yahoo, Microsoft, or OpenStreetMap) support turning on or off some of the controls to the right. Therefore, some of your settings may not appear correct when displayed on certain mapping providers. For example, Yahoo maps doesn't currently allow for removing the zoom control.</p>
</div>
<h3>Default Map</h3>
<?php
	global $table_prefix, $wpdb;
	echo '<form method="post">';
	echo "<div style='float:left;'>".geopress_map('','',1,false)."</div>\n";
?>
<fieldset class="options">
    <table width="100%" cellspacing="2" cellpadding="5" class="editform">
	<tr valign="top">
	        <th width="33%" scope="row"><?php _e('Map Size', 'GeoPress')?>:</th>
	        <td>
	                <dl>
	                <dt><label for="default_mapwidth"><?php _e('Map Width', 'GeoPress') ?>:</label></dt>
	                <dd><input type="text" name="default_mapwidth" value="<?php echo $default_mapwidth ?>" style="width: 10%"/> px</dd>
	                <dt><label for="default_mapheight"><?php _e('Map Height', 'Geo') ?>:</label></dt>
	                <dd><input type="text" name="default_mapheight" value="<?php echo $default_mapheight ?>" style="width: 10%"/> px</dd>
					<dt><label for="default_zoom_level"><?php _e('Default Zoom', 'GeoPress') ?>:</label></dt>
					<dd><?php
					$select = "<select name='default_zoom_level' id='default_zoom_level' onchange='geopress_change_zoom();'>
						<option value='18'>Zoomed In</option>
						<option value='17'>Single Block</option>
						<option value='16'>Neighborhood</option>
						<option value='15'>15</option>
						<option value='14'>Several blocks</option>
						<option value='13'>13</option>
						<option value='12'>12</option>
						<option value='11'>City</option>
						<option value='10'>10</option>
						<option value='9'>9</option>
						<option value='8'>8</option>
						<option value='7'>Region</option>
						<option value='6'>6</option>
						<option value='5'>5</option>
						<option value='4'>4</option>
						<option value='3'>Continent</option>
						<option value='2'>2</option>
						<option value='1'>Zoomed Out</option>
					</select>";
					echo str_replace("value='$default_zoom_level'>","value='$default_zoom_level' selected='selected'>", $select);
					?>
					
					</dd>					
	                </dl>
	        </td>
	</tr>	
	<tr valign="top">
		<th scope="row"><?php _e('Map Marker', 'GeoPress') ?>:</th>			
		<td>
			<input type="text" name="default_marker" value="<?php echo $default_marker ?>" />&nbsp;<label for="default_marker"><img src="<?php echo $default_marker ?>" alt="Default GeoPress marker"  style="padding:2px; background-color: white; border: 1px solid #888;"/></label>
		</td>
	</tr>	
	<tr valign="top">
		<th scope="row"><?php _e('Map Format', 'GeoPress') ?>:</th>			
		<td>
		<?php
		$select = "<select name='map_format' id='map_format' onchange='geopress_change_map_format()'>
			<option value='google'>Google</option>
			<option value='yahoo'>Yahoo</option>
			<option value='microsoft'>Microsoft</option>
			<option value='openstreetmap'>OpenStreetMap</option>
			<option value='openlayers'>OpenLayers</option>			
		</select>";
		echo str_replace("value='$map_format'>","value='$map_format' selected='selected'>", $select);
		?>
		<em>Changing to Microsoft Maps requires saving your options</em></td>
	</tr>
	<tr valign="top">
		<th scope="row"><?php _e('Map Type', 'GeoPress') ?>:</th>			
		<td>
		<?php
		$select = "<select name='map_view_type' id='map_view_type' onchange='geopress_change_view()'>
			<option value='road'>Road</option>
			<option value='satellite'>Satellite</option>
			<option value='hybrid'>Hybrid</option>
		</select>";
		echo str_replace("value='$map_view_type'>","value='$map_view_type' selected='selected'>", $select);
		?>
		</td>
	</tr>		
	<tr valign="top">
		<th scope="row"><?php _e('Controls', 'GeoPress') ?>:</th>			
		<td>
		<?php
		$select = "<select name='map_controls_zoom' id='map_controls_zoom' onchange='geopress_change_controls(this)' >
			<option value='false'>None</option>
			<option value='small'>Small</option>
			<option value='large'>Large</option>
		</select>";
		echo str_replace("value='$map_controls_zoom'>","value='$map_controls_zoom' selected='selected'>", $select);
		?>
		<label for="map_controls_zoom"><?php _e('Zoom control size', 'GeoPress') ?></label>
		</td>
	</tr>	
	<tr>
		<th scope="row"></th> 
		<td>
		<input name="map_controls_pan" type="checkbox" id="map_controls_pan" onchange="geopress_change_controls(this)" value="true" <?php echo $map_controls_pan ?> /> 
		<label for="map_controls_pan"><?php _e('Pan control', 'GeoPress') ?></label> <em>(Yahoo)</em>
		</td>
	</tr>	
	<tr>
		<th scope="row"></th> 
		<td>
		<input name="map_controls_map_type" type="checkbox" id="map_controls_map_type" onchange="geopress_change_controls(this)" value="true" <?php echo $map_controls_map_type ?> /> 
		<label for="map_controls_map_type"><?php _e('Map Type', 'GeoPress') ?></label> <em>(Google)</em>
		</td>
	</tr>
	<tr>
		<th scope="row"></th> 
		<td>
		<input name="map_controls_overview" type="checkbox" id="map_controls_overview" onchange="geopress_change_controls(this)" value="true" <?php echo $map_controls_overview ?> /> 
		<label for="map_controls_overview"><?php _e('Overview', 'GeoPress') ?></label> <em>(Google)</em>
		</td>
	</tr>
	<tr>
		<th scope="row"></th> 
		<td>
		<input name="map_controls_scale" type="checkbox" id="map_controls_scale" onchange="geopress_change_controls(this)" value="true" <?php echo $map_controls_scale ?> /> 
		<label for="map_controls_scale"><?php _e('Scale', 'GeoPress') ?></label> <em>(Google)</em>
		</td>
	</tr>
	</table>
</fieldset>

<?php
	
	echo '<div class="submit"><input type="submit" name="Options" value="'. __('Save Map Layout', 'GeoPress') . '&raquo;" /></div>';

  }

  function geopress_options_page() { 

	if(isset($_POST['Options'])) { 
		$default_rss_enable = $_POST['georss_enable'];
		$default_add_map = $_POST['default_add_map'];
		$rss_format = $_POST['georss_format'];
		$google_apikey = $_POST['google_apikey'];
		$yahoo_appid = $_POST['yahoo_appid'];
		update_option('_geopress_rss_enable', $default_rss_enable);
		update_option('_geopress_rss_format', $rss_format);
		update_option('_geopress_default_add_map', $default_add_map);
		update_option('_geopress_google_apikey', $google_apikey);
		update_option('_geopress_yahoo_appid', $yahoo_appid);
		echo '<div class="updated"><p><strong>' . __('Map options updated.', 'GeoPress') . '</strong></p></div>';
	}

    $default_rss_enable = get_settings('_geopress_rss_enable', true) ? 'checked="checked"' : '';
    $default_add_map = get_settings('_geopress_default_add_map', true);
    $rss_format = get_settings('_geopress_rss_format', true);
    $google_apikey = get_settings('_geopress_google_apikey', true);
    $yahoo_appid = get_settings('_geopress_yahoo_appid', true);
	?>
                        <div class="wrap">
                        <h2><?php _e('Customize GeoPress', 'GeoPress') ?></h2>
                        <form method="post">
				<p>Welcome to GeoPress. To begin using GeoPress, please obtain and enter a GoogleMaps and Yahoo AppID as shown below. You can customize your default map view using the "Maps" tab above. Or just go and start writing your posts!</p>
				<fieldset class="options">
				<legend><?php _e('Map', 'GeoPress') ?></legend>
                                <table width="100%" cellspacing="2" cellpadding="5" class="editform">			
					<tr valign="top"> 
						<th scope="row">GoogleMaps Key:</th> 
						<td>
							<input name="google_apikey" type="text" id="google_apikey" style="width: 95%" value="<?php echo $google_apikey ?>" size="45" />
							<br />
							<a href="http://www.google.com/apis/maps/signup.html" title="GoogleMaps API Registration">GoogleMaps API Registration</a> - <?php _e('Enter your blog url as the GoogleMaps URL', 'GeoPress') ?>
						</td> 
					</tr> 
					<tr valign="top"> 
						<th scope="row">Yahoo AppID:</th> 
						<td>
							<input name="yahoo_appid" type="text" id="yahoo_appid" style="width: 95%" value="<?php echo $yahoo_appid ?>" size="45" />
							<br />
							<a href="http://api.search.yahoo.com/webservices/register_application" title="Yahoo! Developer Registration">Yahoo! Developer Registration</a>
						</td> 
					</tr>	
 					<tr valign="top">
						<th scope="row"><?php _e('Add Maps', 'GeoPress') ?>:</th>
						<td>
						<?php _e('Automatically add a map after posts?', 'GeoPress') ?> <br/>
						<select name="default_add_map" id="default_add_map">
							<?php
							$select = "<option value='0'>I'll do it myself, thanks</option>
							<option value='1'>Only on single post pages</option>
							<option value='2'>Give me everything .. any post, any page</option>";
							echo str_replace("value='$default_add_map'>","value='$default_add_map' selected='selected'>", $select);
							?>
						</select>
						</td>
					</tr>
					</table>
				</fieldset>
				<fieldset>
				<legend><?php _e('GeoRSS Feeds', 'GeoPress') ?></legend>
				<table width="100%" cellspacing="2" cellpadding="5" class="editform">
					<tr valign="top">
						<th width="33%" scope="row"><?php _e('GeoRSS Feeds', 'GeoPress') ?>:</th>
						<td>
						<label for="georss_enable">
						<input name="georss_enable" type="checkbox" id="georss_enable" value="true" <?php echo $default_rss_enable ?> /> <?php _e('Enable GeoRSS tags in feeds', 'GeoPress') ?></label>
						</td>
					</tr>
 					<tr valign="top">
						<th scope="row"><?php _e('Feed Format', 'GeoPress') ?>:</th>
						<td>
						<select name="georss_format" id="georss_format">
							<?php
							$select = "<option value='simple'>Simple &lt;georss:point&gt;</option>
							<option value='gml'>GML &lt;gml:pos&gt;</option>
							<option value='w3c'>W3C &lt;geo:lat&gt;</option>";
							echo str_replace("value='$rss_format'>","value='$rss_format' selected='selected'>", $select);
							echo $rss_format;
							?>
						</select><br/>
						<?php _e('The format of your syndication feeds (Simple is recommended)', 'GeoPress') ?> 
					</td>
					</tr>
                </table>
				</fieldset>
                                <div class="submit"><input type="submit" name="Options" value="<?php _e('Update Options', 'GeoPress') ?> &raquo;" /></div>
                        </form>
                        </div>
                <?php
  }

	// Adds administration menu options
	function admin_menu() { 
	    add_menu_page(__('Customize GeoPress', 'GeoPress'), __('GeoPress', 'GeoPress'), 5, GEOPRESS_LOCATION.basename(__FILE__), array('GeoPress', 'geopress_options_page'));
	//      add_menu_page(__('GeoPress Locations', 'GeoPress'), __('GeoPress', 'GeoPress'), 5, GEOPRESS_LOCATION.basename(__FILE__), array('GeoPress', 'geopress_options_page'));
	    add_submenu_page(GEOPRESS_LOCATION.basename(__FILE__),__('Locations', 'GeoPress'), __('Locations', 'GeoPress'), 5, 'geopress_locations', array('GeoPress', 'geopress_locations_page'));
	    add_submenu_page(GEOPRESS_LOCATION.basename(__FILE__),__('Maps', 'GeoPress'), __('Maps', 'GeoPress'), 5, 'geopress_maps', array('GeoPress', 'geopress_maps_page'));
	    add_submenu_page(GEOPRESS_LOCATION.basename(__FILE__),__('Documentation', 'GeoPress'), __('Documentation', 'GeoPress'), 5, 'geopress_documentation', array('GeoPress', 'geopress_documentation_page'));
	}

	function admin_head($unused) { 
	  /* Use this function to output javascript needed for the post page. 
	     the js function updates the text boxes from saved locations */
		// if ( strstr($_SERVER['REQUEST_URI'], 'post.php')) { 
		// 	echo geopress_header();
		// }
	}


  // This function is called just before a post is updated
  // Replaces INSERT_MAP with a dynamic map

  // ^(.+)[[:space:]]+[T|t]ags:[[:space:]]*([.\n]+)$
  function update_post($id) { 
    // delete_post_meta($id, '_geopress_id'); 
    global $wpdb;

    $postdata = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID = '$id'");

    $addr = $_POST['addr'];
    $geometry = $_POST['geometry'];
    $locname = $_POST['locname'];
    // Allow the location to be set within the post body
    if ((preg_match_all('/GEOPRESS_LOCATION\((.+)\)/', $postdata->post_content, $matches) > 0) ) {
      // $locname = $matches[1];
      $addr = $matches[1][0];
    }
    // tags: geo:long=24.9419260025024 geo:lat=60.1587851399795
    elseif ((preg_match_all('/geo:lat=([-\d\.]+)(.*)?geo:lon[g]?=([-\d\.]+)/', $postdata->post_content, $matches) > 0) ) {
      // $locname = $matches[1];
      $addr = "[".$matches[1][0].",".$matches[3][0]."]";
    }
    else {
    }

    // $map_format = $_POST['geopress_map_format'];
    // $map_zoom = $_POST['geopress_map_zoom'];
    // $map_type = $_POST['geopress_map_type'];

    if ( $addr ) {
      // if just lat/lon coordinates were given, don't geocode
      if( !preg_match('/\[(.+),[ ]?(.+)\]/', $addr, $matches) ) { 
        
        // If the user set the coordinates via the web interface (using the geocoder), don't change it here.
        if( preg_match('/(.+),[ ]?(.+)/', $geometry, $matches) ) { 
          $lat = $matches[1];
          $lon = $matches[2];          
        }
        else {
          list($lat, $lon) = geocode($addr);
        }
      } else {
        $lat = $matches[1];
        $lon = $matches[2];                  
      }        
      list($warn, $mapurl) = yahoo_mapurl($addr);
      $coords = "$lat $lon";
      $coord_type = "point";
        
      // Create a new loc - therefore -1
      $geo_id = GeoPress::save_geo(-1, $locname, $addr, $coords, $coord_type, $warn, $mapurl, 1, $map_format, $map_zoom, $map_type);
	  $updated = update_post_meta($id, '_geopress_id', $geo_id);
	  if(!$updated) {
      	add_post_meta($id, '_geopress_id', $geo_id);
      }
    }
  }
  
  // Replaces INSERT_MAP with a geopress map
  function embed_map_inpost($content) { 
    $default_add_map = get_settings('_geopress_default_add_map', true);

    // If the user explicitly wants to insert a map
    if(preg_match_all('/INSERT_MAP/', $content, $matches) > 0) {
      $content = preg_replace("/INSERT_MAP\((\d+),[ ]?(\d+)\)/", geopress_post_map('\1','\2'), $content);
      $content = preg_replace("/INSERT_OVERLAY_MAP\((\d+),[ ]?(\d+),[ ]?(.+)\)/", geopress_post_map('\1','\2',true,'\3'), $content);

      $content = preg_replace("/INSERT_MAP/", geopress_post_map(), $content);

      // This can probably be made into a single preg_replace with ? optionals - ajturner //
      } elseif (preg_match_all('/INSERT_GEOPRESS_MAP/', $content, $matches) > 0) {
        $content = preg_replace("/INSERT_GEOPRESS_MAP\((\d+),[ ]?(\d+)\)/", geopress_map('\1','\2'), $content);
        $content = preg_replace("/INSERT_GEOPRESS_MAP/", geopress_map(), $content);
        // This can probably be made into a single preg_replace with ? optionals - ajturner //
      } elseif (($default_add_map == 2) || ( is_single() && ($default_add_map == 1))) {
        // Add a map to the end of the post if "automatically add map" is enabled
        $content .= geopress_post_map();
      }
      $content = preg_replace("/GEOPRESS_LOCATION\((.+)\)/", "", $content);

      return $content;
    }

  // Replaces INSERT_COORDS or INSERT_ADDRESS with the geopress information
  function embed_data_inpost($content) { 
    $content = preg_replace("/INSERT_COORDS/", the_geo_mf(), $content);
    $content = preg_replace("/INSERT_ADDRESS/", the_adr_mf(), $content);
    $content = preg_replace("/INSERT_LOCATION/", the_loc_mf(), $content);
	  return $content;
  }

  ///
  /// Syndication Functions
  ///
  function atom_entry($post_ID) {
    if(get_settings('_geopress_rss_enable', true))
    {
      $coord = the_coord();
      if($coord != "") {
        the_coord_rss();
      }
    }
  }

  function rss2_item($post_ID) {
		if(get_settings('_geopress_rss_enable', true))
		{
			the_coord_rss();
		}
  }
  function geopress_namespace() {
		if(get_settings('_geopress_rss_enable', true))
		{
			switch(get_settings('_geopress_rss_format', true)) {
				case "w3c":
					echo 'xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"'."\n";
					break;
				case "gml":
				case "simple":
				default:
					echo 'xmlns:georss="http://www.georss.org/georss" xmlns:gml="http://www.opengis.net/gml"'."\n";
			}
		}
  }

  function wp_head() {
		echo geopress_header();
  }

  // If the location is queried, JOIN with the postmeta table 
  function join_clause($join) {
	if (((isset($_GET['location']) || $wp->query_vars['location'] != null)  && $_GET['location'] != "") OR ((isset($_GET['loc']) || $wp->query_vars['loc'] != null)  && $_GET['loc'] != "")) {
		global $wpdb, $id, $post, $posts;
		global $table_prefix;
		$geo_table_name = $table_prefix . "geopress";

		$join .= " , $wpdb->postmeta, $geo_table_name ";
	}
	return $join;	
  }

	  // If the location is queried, add to the WHERE clause
	  function where_clause($where) {
		if ((isset($_GET['location']) || $wp->query_vars['location'] != null) && $_GET['location'] != "") {
			global $wpdb, $id, $post, $posts;
			global $table_prefix;
			$geo_table_name = $table_prefix . "geopress";
			$post_table_name = $table_prefix . "posts";
			$postmeta_table_name = $table_prefix . "postmeta";
		
			$location = $_GET['location'];
				
			$where.= " AND $geo_table_name.geopress_id = $wpdb->postmeta.meta_value AND $wpdb->posts.id = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '_geopress_id'"; 
			// If the location= is a number, assume they're referring to the location id
			if( preg_match('/[0-9]+/', $location, $matches )) {
				$where .= " AND $wpdb->postmeta.meta_value=".mysql_real_escape_string($location);
			}
			// otherwise, look for the name
			else {
				$where .= " AND $geo_table_name.name='".mysql_real_escape_string($location)."'";	
			}
		}
		return $where;
	  }

	  // If the location is requested, and there exists a "location.php" template in 
	  //  the theme, then use it
	  function location_redirect() {
		if ((isset($_GET['location']) || $wp->query_vars['location'] != null) && $_GET['location'] != "") {
			global $posts;
			// $location = $wp->query_vars['loc'];
			$location = $_GET['location'];
			if($template = get_query_template('location')) {
				include($template);
				exit;
			}
		}
		return;
	  }
		// TODO: ajturner - I'm not sure if these work properly
        // function register_query_var($vars) {
        //         $vars[] = 'loc';
        //         return $vars;
        // }
        // function add_rewrite_tag() {
        //         global $wp_rewrite;
        //         $wp_rewrite->add_rewrite_tag('%loc%', '([0-9]{2})', "loc=");
        // }
        // function filter_query_string($query_string) {
        //         return preg_replace_callback("#loc=([0-9]{2})#", array('GeoPress', 'query_string_callback'), $query_string);
        // }


  // Returns the map format (google, yahoo, microsoft, osm, etc) set in the defaults, or passed by the optional parameter
  function mapstraction_map_format($map_format_type = "") {
	if($map_format_type == "")
	 	return get_settings('_geopress_map_format', true);
	else
		return $map_format_type;
  }
	
  // Returns the map type (road, satellite, hybrid) set in the defaults, or passed by the optional parameter
  function mapstraction_map_type($map_view_type = "") {
		if($map_view_type == "")
			$map_view_type = get_settings('_geopress_map_type', true);

		switch($map_view_type) {
			case "hybrid":
				return 'Mapstraction.HYBRID';
				break;
			case "road":
				return 'Mapstraction.ROAD';
				break;
			case "satellite":
				return 'Mapstraction.SATELLITE';
				break;
			default :
				return 'Mapstraction.HYBRID';
			break;		
		}
  }

  // Returns the map controls set in the defaults, or passed by the optional parameter
/*     pan:      true,
 *     zoom:     'large' || 'small',
 *     overview: true,
 *     scale:    true,
 *     map_type: true,
*/
  function mapstraction_map_controls($pan = "", $zoom = "", $overview = "", $scale = "", $map_type = "") {

	if($pan == "") $map_controls_pan = get_settings('_geopress_controls_pan', true)  ? 'true' : 'false';
	else $map_controls_pan = $pan;
	if($zoom == "") $map_controls_zoom = get_settings('_geopress_controls_zoom', true);
	else $map_controls_zoom = $zoom;
	if($overview == "") $map_controls_overview = get_settings('_geopress_controls_overview', true)  ? 'true' : 'false';
	else $map_controls_overview = $overview;
	if($scale == "") $map_controls_scale = get_settings('_geopress_controls_scale', true)  ? 'true' : 'false';
	else $map_controls_scale = $scale;
	if($map_type == "") $map_controls_map_type = get_settings('_geopress_controls_map_type', true)  ? 'true' : 'false';
	else $map_controls_map_type = $map_type;

	$controls = "{\n";
	$controls .= "\tpan: $map_controls_pan,\n";
	$controls .= "\tzoom: '$map_controls_zoom',\n";
	$controls .= "\toverview: $map_controls_overview,\n";
	$controls .= "\tscale: $map_controls_scale,\n";
	$controls .= "\tmap_type: $map_controls_map_type\n";
	$controls .= "\t}";
	return $controls;
  }

  // Returns the map zoom level set in the defaults, or passed by the optional parameter
  function mapstraction_map_zoom($map_zoom = 0) {
	if($map_zoom == 0)
		return get_settings('_geopress_default_zoom_level', true);
	else
		return $map_zoom;
  }
}

///
/// Wordpress Plugin Hooks
///
add_action('activate_geopress/geopress.php', array('GeoPress', 'install'));

// Add form to post editing
add_action('edit_form_advanced', array('GeoPress', 'location_edit_form')); 
add_action('simple_edit_form', array('GeoPress', 'location_edit_form'));
// Add form to page editing
add_action('edit_page_form', array('GeoPress', 'location_edit_form'));


// Handles querying for a specific location
add_action('template_redirect', array('GeoPress', 'location_redirect'));
add_filter('posts_join', array('GeoPress','join_clause') );
add_filter('posts_where', array('GeoPress','where_clause') );
// add_filter('query_vars', array('GeoPress','register_query_var') );
// add_filter( 'init', array('GeoPress', 'add_rewrite_tag') );

add_action('admin_head', array('GeoPress', 'admin_head'));
add_action('save_post', array('GeoPress', 'update_post'));
add_action('edit_post', array('GeoPress', 'update_post'));
add_action('publish_post', array('GeoPress', 'update_post'));
add_filter('the_content', array('GeoPress', 'embed_map_inpost'));
add_action('admin_menu', array('GeoPress', 'admin_menu'));
add_action('option_menu', array('GeoPress', 'geopress_options_page'));
add_action('wp_head', array('GeoPress', 'wp_head'));
add_action('admin_head', array('GeoPress', 'wp_head'));

// XML Feed hooks //
add_action('atom_ns', array('GeoPress', 'geopress_namespace'));
add_action('atom_entry', array('GeoPress', 'atom_entry'));
add_action('rss2_ns', array('GeoPress', 'geopress_namespace'));
//add_action('rss2_head', array('GeoPress', 'rss2_head'));
add_action('rss2_item', array('GeoPress', 'rss2_item'));
add_action('rdf_ns', array('GeoPress', 'geopress_namespace'));
//add_action('rdf_head', array('GeoPress', 'rss2_head'));
add_action('rdf_item', array('GeoPress', 'rss2_item'));
add_action('rss_ns', array('GeoPress', 'geopress_namespace'));
//add_action('rss_head', array('GeoPress', 'rss2_head'));
add_action('rss_item', array('GeoPress', 'rss2_item'));


function geopress_header() {
	$map_format = get_settings('_geopress_map_format', true);
    $scripts = "<!-- Location provided by GeoPress v".GEOPRESS_VERSION." (http://georss.org/geopress) -->";
    $scripts .= "<meta name=\"plugin\" content=\"geopress\" />";
//	if($map_format == "yahoo" )
//	{
		$yahoo_appid = get_settings('_geopress_yahoo_appid', true);
		if($yahoo_appid != "") {
			$scripts .= "\n".'<script type="text/javascript" src="http://api.maps.yahoo.com/ajaxymap?v=3.4&amp;appid='. $yahoo_appid .'"></script>';
		}
//	}
	if($map_format == "microsoft")
	{
		$scripts .= "\n".' <script src="http://dev.virtualearth.net/mapcontrol/v3/mapcontrol.js"></script>';
	}

	$google_apikey = get_settings('_geopress_google_apikey', true);
	if($google_apikey != "") {
		$scripts .= "\n".'<script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;v=2&amp;key='. $google_apikey .'" ></script>';
	}
	$scripts .= "\n".'<script type="text/javascript" src="http://openlayers.org/api/OpenLayers.js"></script>';
	
	$plugindir = get_bloginfo('wpurl') . "/wp-content/plugins/geopress";
	$scripts .= "\n".'<script type="text/javascript" src="'.$plugindir.'/mapstraction.js"></script>';
	$scripts .= "\n".'<script type="text/javascript" src="'.$plugindir.'/geopress.js"></script>';
	return $scripts;
}

///
/// User/Template Functions
///

function geopress_locations_list() {
  $locations = GeoPress::get_locations();
  foreach ($locations as $loc) {
    echo '<li><a href="'.get_settings('home').'?location='.$loc->name.'">'.$loc->name.'</a></li>';
  }
  return;
}
// dfraga - Debugging function added
function dump_locations ($locations, $msg = "") {
  $string =  "+ Dumping: ".$msg."<br />\n";
  if ($locations == "") {
    $string .= "- Void locations<br />\n";
  }
  foreach ($locations as $loc) {
    $string .= "- Location name: ".$loc->name."<br />\n";
  }
  $string .= "+ End of locations<br />\n";
  return $string;
}



// dfraga - Loop mapping added
// Creates a dynamic map with the posts in "the_loop". Useful for category/search/single visualization.
// $height, $width are the h/w in pixels of the map
// $locations is the last N locations to put on the map, be default puts *all* locations
// $unique_id is a true/false if a unique_id is required
function geopress_map_loop($height = "", $width = "", $locations = -1, $zoom_level = -1) {
	return geopress_map ($height, $width, $locations, false, true, $zoom_level);
}


function geopress_page_map($height = "", $width = "", $controls = true) {
	global $post, $geopress_map_index;
	// $children = get_children("post_parent=".$post->post_id."&post_type=page&orderby=menu_order ASC post_date&order=ASC");
	$children = get_children("post_parent=".$post->ID."&orderby=menu_order ASC, post_date&order=ASC");
	$output='';
	if($children == false)
	{
		$output.= geopress_post_map($height, $width, $controls);
	}
	else
	{
		$map_format = get_settings('_geopress_map_format', true);
		if ($height == "" || $width == "" )
		{
			$width = get_settings('_geopress_mapwidth', true);
			$height = get_settings('_geopress_mapheight', true)*2;
		}

		$map_id = $post->ID . $geopress_map_index;

		$coords = split(" ",$geo->coord);

		$map_controls = $controls ? GeoPress::mapstraction_map_controls() : "false";
		$output = '<div id="geo_map'.$map_id.'" class="mapstraction" style="height: '.$height.'px; width: '.$width.'px;"></div>';
		$output .= '<!-- GeoPress Map --><script type="text/javascript">';
		$output .= 'geopress_addEvent(window,"load", function() { geopress_maketravelmap(';
		$output .=$map_id.',';
		$output .='{';

		$pointList = array();
		foreach($children as $key=>$value)
		{
			$line= "";
			$geo = GeoPress::get_geo($key);
			if($geo)
			{
				$line .= $key.':{';
				$coords = split(" ",$geo->coord);
				$line .= 'lat:'.$coords[0].',lng:'.$coords[1];
				$line .= ',name:"'.addslashes($geo->name).'"';
//				$line .= ',title:"'.'"guid."'title='".addslashes($value->post_title) . "'>".addslashes($value->post_title)."".'"';
				$line .='}';
				array_push($pointList, $line);
			}
		}
		$output .= implode(',' , $pointList );
		$output .= '},';
		$output .= '"'.GeoPress::mapstraction_map_format($geo->map_format) . '",' . GeoPress::mapstraction_map_type($geo->map_type).', '. $map_controls .')';
		$output .= "}); </script><!-- end GeoPress Map --> ";
	}
	return $output;
}

// Creates a dynamic map
// $height, $width are the h/w in pixels of the map
// $locations is the last N locations to put on the map, be default puts *all* locations
// $unique_id is a true/false if a unique_id is required
// $loop_locations set this to true if you're running this in a Post loop and want a map of the currently visible posts
// $zoom_level set the map zoom level for the overview. Default is to auto zoom to show all markers.
// $url is an option URL to a KML or GeoRSS file to include in the map
function geopress_map($height = "", $width = "", $locations = -1, $unique_id, $loop_locations = false, $zoom_level = -1, $url = "") {
	$plugindir = get_bloginfo('wpurl') . "/wp-content/plugins/geopress";		
  $map_format = get_settings('_geopress_map_format', true);
  $geopress_marker = get_settings('_geopress_marker', true);
  if ($height == "" || $width == "" )
  {
   	$height = get_settings('_geopress_mapheight', true);
   	$width = get_settings('_geopress_mapwidth', true);
  }
  
  // sometimes we don't want to deal with a unique ID b/c we know there will 
  // 	only be 1 map, like the select map
  if($unique_id)
  	$map_id = geopress_rand_id();
  else
	$map_id = "";

  // dfraga - Getting specific locations 
  if ($loop_locations == true) {
      $locs = GeoPress::get_loop_locations($locations);
  } else {
      $locs = GeoPress::get_location_posts($locations);
  }
  $output = '<div id="geo_map'.$map_id.'" class="mapstraction" style="height: '.$height.'px; width: '.$width.'px;"></div>';
  $output .= '<!-- GeoPress Map --> <script type="text/javascript">'."\n";
  $output .= " //<![CDATA[ \n";
  $output .= "var geo_map;\ngeopress_addEvent(window,'load', function() { ";
  $output .= 'geo_map'.$map_id.' = new Mapstraction("geo_map'.$map_id.'","'. $map_format .'");'."\n";
  $output .= 'geo_map'.$map_id.'.setCenterAndZoom(new LatLonPoint(0,0), 1);';
//  $output .= 'var geo_bounds = new BoundingBox();'."\n";
  $output .= 'geo_map'.$map_id.'.addControls('.GeoPress::mapstraction_map_controls().');'."\n";
  if($map_format != "openstreetmap")
  	$output .= 'geo_map'.$map_id.'.setMapType('.GeoPress::mapstraction_map_type().');'."\n";
  $output .= "var markers = new Array(); var i = 0;"."\n";

	//  Output one marker per location, but with all posts at that location
	// Todo - optionally do a clustering of a larger marker
  foreach ($locs as $posts) {
    $loc = $posts[0];
    $coords = split(" ",$loc->coord);
    $output .= "i = markers.push(new Marker(new LatLonPoint($coords[0], $coords[1])));\n";
    $details = " @ <strong>". htmlentities($loc->name)."</strong><br/>";
    $url = get_bloginfo('wpurl');
    foreach($posts as $post) {
        $details .= "<a href='".$url.'/?p='.$post->post_id."' title='". htmlentities($post->post_title)."'>".htmlentities($post->post_title)."</a><br/>";
    }
	$output .= "\tgeo_map$map_id.addMarkerWithData(markers[i-1],{ infoBubble: \"$details\", date : \"new Date($post->post_date)\", icon:\"$geopress_marker\", iconSize:[24,24], iconShadow:\"".$plugindir."/blank.gif\", iconShadowSize:[0,0] });\n";

    // $output .= "geo_map$map_id.addMarker(markers[i-1]);\n";
    //	$output .= 'geo_bounds.extend(markers[i-1].point);'."\n";
  }

  $output .= "geo_map$map_id.autoCenterAndZoom();";
//  $output .= 'geo_map'.$map_id.'.setZoom(map.getBoundsZoomLevel(bounds));';

  // dfraga - Zoom level setting added
  if ($zoom_level > 0) {
    $output .= "geo_map$map_id.setZoom(".$zoom_level.");\n";
  }

	if($url != "") {
		$output .= 'geo_map'.$map_id.'.addOverlay("'.$url.'");';
	}

  $output .= "}); \n // ]]> \n </script><!-- end GeoPress Map --> ";
  
  return $output;
}

$geopress_map_index = 1;
function geopress_post_map($height = "", $width = "", $controls = true, $overlay = "") {
    global $post, $geopress_map_index;
	$geopress_marker = get_settings('_geopress_marker', true);

    $geo = GeoPress::get_geo($post->ID);
    if($geo) {
        if(!is_feed()) {

            if ($height == "" || $width == "" ) {
                $height = get_settings('_geopress_mapheight', true);
                $width = get_settings('_geopress_mapwidth', true);
            }

            $map_id = $post->ID . $geopress_map_index;

            $coords = split(" ",$geo->coord);

            $map_controls = $controls ? GeoPress::mapstraction_map_controls() : "false"; 
            $output = '<div id="geo_map'.$map_id.'" class="mapstraction" style="height: '.$height.'px; width: '.$width.'px;"></div>';
            $output .= '<!-- GeoPress Map --><script type="text/javascript">';
            // $output .= " //<![CDATA[ ";
            $output .= 'geopress_addEvent(window,"load", function() { geopress_makemap('.$map_id.',"'. $geo->name .'",'.$coords[0].','.$coords[1].',"'.GeoPress::mapstraction_map_format($geo->map_format).'",'.GeoPress::mapstraction_map_type($geo->map_type).', '. $map_controls .','.GeoPress::mapstraction_map_zoom($geo->map_zoom).', "'.$geopress_marker.'") }); ';
			if($url != "") {
				$output .= 'geo_map'.$map_id.'.addOverlay("'.$url.'");';
			}
			$output .= "</script><!-- end GeoPress Map -->";
        }
        else
        {
            $output = '<img src="'.$geo->mapurl.'" title="GeoPress map of '.$geo->name.'"/>';
        }
        $geopress_map_index++;
    }
    return $output;

}

function geopress_map_select($height=250, $width=400, $style="float: left;") {   
  $map_format = get_settings('_geopress_map_format', true);
  $map_view_type = get_settings('_geopress_map_type', true);
  $output = '<div id="geo_map" class="mapstraction" style="width: '.$width.'px; height: '.$height.'px;'.$style.'"></div>';
  $output .= '<!-- GeoPress Map --><script type="text/javascript">';
  $output .= " //<![CDATA[ \n";
  $output .= "var geo_map;\ngeopress_addEvent(window,'load', function() { \n";
  $output .= 'geo_map = new Mapstraction("geo_map","'.$map_format.'"); ';
  $output .= "var myPoint = new LatLonPoint(20,-20);\n";
  $output .= "geo_map.addControls(".GeoPress::mapstraction_map_controls(true, 'small', false, true, true).");\n";
  $output .= "geo_map.setCenterAndZoom(myPoint,1);\n";
  $output .= "geo_map.setMapType(".GeoPress::mapstraction_map_type($map_view_type).");\n";
  $output .= 'geo_map.addEventListener("click", function(p){ setClickPoint(p); } );';
  $output .= 'geo_map.addEventListener("zoom", function(p){ alert("Zoomed!"); } );';

  $output .= "});\n // ]]> \n </script><!-- end GeoPress Map -->\n";
	
  return $output;

}
// Does the post have a location?
function has_location() { 

  global $post;
  $geo = GeoPress::get_geo($post->ID);
  if($geo)
    return true;
  else
    return false;
}
// Get the coordinates for a post
function the_coord() { 

  global $post;
  $geo = GeoPress::get_geo($post->ID);
  return $geo->coord;

}

// The Geographic coordinates in microformats
// see http://microformats.org/wiki/geo
function the_geo_mf() { 
  
  $coord = the_coord();
  $coord = split(" ", $coord);

  $coord_tag = "\n\t<div class='geo'><span class='latitude'>$coord[0]</span>, <span class='longitude'>$coord[1]</span></div>";
  
  return $coord_tag;

}

// Gets the name for a location when passed in via the URL query
function geopress_location_name() {
 	if(isset($_GET['loc']) && $_GET['loc'] != "") {
		$loc_id = $_GET['loc'];
		$location = GeoPress::get_location($loc_id);
		return $location->name;
	}
}

// Get the address (name) for a post
function the_location_name() {
  global $post;
  $geo = GeoPress::get_geo($post->ID);
  $addr = $geo->name;
  return $addr;
}

// Get the address (name) for a post
function the_address() {
  global $post;
  $geo = GeoPress::get_geo($post->ID);
  $addr = $geo->loc;
  return $addr;
}

// The Address in microformats
// see http://microformats.org/wiki/adr
function the_adr_mf() { 
  
  $addr = the_address();
  $addr_tag = "\n\t<div class='adr'>$addr</div>";
  
  return $addr_tag;

}
// The Location in microformats
// see http://microformats.org/wiki/adr
function the_loc_mf() { 
  
  $loc_name = the_location_name();
  $loc_tag = "\n\t<div class='vcard'><span class='fn'>$loc_name</span></div>";
  
  return $loc_tag;

}

function ymap_post_url() { 

  global $post;
  $coord = the_coord();
  list($lat, $lon) = split(" ", $coord);
  
  return "http://maps.yahoo.com/int/index.php#lat=$lat&lon=$lon&mag=5&trf=0";

}

function ymap_blog_url($type ='rss2_url') { 

  // Note this url won't produce a valid, plottable map if you haven't 
  //   modified your wp-rss file for the type of feed you want (wp-rss.php or wp-rss2.php)
  $url = yahoo_annotatedmaps . bloginfo($type); 
return "$url";

}

// Get the Coordinates in RSS format
// this doesn't need to be called directly, it is added by the Plugin hooks
function the_coord_rss() {
  $coord = the_coord();
  $featurename = the_address();
  $rss_format = get_settings('_geopress_rss_format', true);
  if($coord != "") {
	switch($rss_format) {
	case "w3c":
		  $coord = split(" ", $coord);
  		$coord_tag = "\t<geo:lat>$coord[0]</geo:lat>\n\t\t<geo:lon>$coord[1]</geo:lon>\n";
		break;
	case "gml":
  		$coord_tag = "\t<georss:where>\n\t\t<gml:Point>\n\t\t\t<gml:pos>$coord</gml:pos>\n\t\t</gml:Point>\n\t</georss:where>";
		break;
	case "simple": // cascade to default
	default:
		$coord_tag = "\t<georss:point>$coord</georss:point>\n";
		if($featurename != ""){
  			$coord_tag .= "\t<georss:featurename>$featurename</georss:featurename>\n";
		}		
 		break;
	}
  	echo $coord_tag;
  }
}

function the_addr_rss() { 
  
  $addr = the_address();
  $addr_tag = "\n\t<ymaps:Address>$addr</ymaps:Address>";
  
  echo $addr_tag;

}

// returns a random number used to ensure unique HTML element ids
function geopress_rand_id() {
  srand((double)microtime()*1000000);  
  return rand(0,1000); 
}

function geopress_kml_link() {
	$plugindir = get_bloginfo('wpurl') . "/wp-content/plugins/geopress";	
	echo "<a href=\"$plugindir/wp-kml-link.php\" title=\"KML Link\">KML</a>";
}

?>
