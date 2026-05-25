// GeoPress front-end JavaScript
// Global map registry
var geo_maps = [];
var num_maps = 0;
var geo_map;

/**
 * Creates a Mapstraction map instance for a single post.
 */
function geopress_makemap(map_id, name, lat, lon, map_format, map_type, map_controls, map_zoom, marker_icon) {
  num_maps = geo_maps.push(new Mapstraction("geo_map" + map_id, map_format)) - 1;
  var myPoint = new LatLonPoint(lat, lon);
  if (map_controls) {
    geo_maps[num_maps].addControls(map_controls);
  }
  geo_maps[num_maps].setCenterAndZoom(myPoint, map_zoom);
  geo_maps[num_maps].setMapType(map_type);
  var marker = new Marker(myPoint);
  marker.setInfoBubble(name);
  geo_maps[num_maps].addMarkerWithData(marker, {icon: marker_icon, iconSize: [24, 24]});
}

function geopress_setmap() {
  geo_map.removeAllMarkers();
  var myPoint = new LatLonPoint(30, -90);
  geo_map.setCenterAndZoom(myPoint, 8);
  var marker = new Marker(myPoint);
  marker.setInfoBubble("@ Pointed");
  geo_map.addMarker(marker);
}

/**
 * Adds a marker at a specific point.
 * Handles both coordinate setting via click and programmatic placement.
 */
function addPointToMap(point) {
  if (typeof geo_map === 'undefined' || !geo_map) return;
  geo_map.removeAllMarkers();
  var marker = new Marker(point);
  geo_map.setCenterAndZoom(point, 10);
  marker.setInfoBubble(point.toString());
  geo_map.addMarker(marker);
}

/**
 * Returns a DOM element by ID.
 */
function returnObjById(id) {
  return document.getElementById(id);
}

/**
 * showLocation() pans the editor map to match the current address/geometry fields.
 *
 * For [lat,lon] syntax or a previously geocoded geometry value, positioning is
 * done client-side. For plain text addresses, the user should save the post and
 * let GeoPress geocode server-side, then reload the editor.
 */
function showLocation(addr, geometry) {
  if (typeof geo_map === 'undefined' || !geo_map) return false;

  if (!addr)     addr     = 'addr';
  if (!geometry) geometry = 'geometry';

  var address = returnObjById(addr)     ? returnObjById(addr).value     : '';
  var geom    = returnObjById(geometry) ? returnObjById(geometry).value : '';

  // Accept stored "lat lon" (space) and display "lat, lon" (comma) formats.
  if (geom) {
    var matches = geom.match(/^([-\d.]+)[,\s]+([-\d.]+)$/);
    if (matches) {
      setMapPoint(new LatLonPoint(parseFloat(matches[1]), parseFloat(matches[2])));
      return false;
    }
  }

  // Direct [lat, lon] syntax in the address field.
  if (address) {
    var coordMatches = address.match(/\[(.+),[ ]?(.+)\]/);
    if (coordMatches) {
      setMapPoint(new LatLonPoint(parseFloat(coordMatches[1]), parseFloat(coordMatches[2])));
      return false;
    }
    // Plain text address: geocoding is handled server-side on post save.
    // No client-side geocoder is available (Google Maps v2 is defunct).
  }
  return false;
}

function geocode(element, geometry) {
  if (element == null) element = 'addr';
  // Do not clear the geometry field here — it holds the server-resolved
  // coordinate and should only be cleared when a new map point is clicked.
  showLocation(element, geometry);
}

function findLocation(address) {
  returnObjById('addr').value = address;
  showLocation();
}

/**
 * setMapPoint() centres the editor map on a LatLonPoint and places a marker.
 */
function setMapPoint(point) {
  if (typeof geo_map === 'undefined' || !geo_map) return;
  geo_map.removeAllMarkers();
  addPointToMap(point);
}

/**
 * setClickPoint() is called when the user clicks on the editor map.
 * It stores the clicked coordinate in the hidden geometry field.
 */
function setClickPoint(point) {
  returnObjById('geometry').value = '';
  returnObjById('addr').value = '[' + point + ']';
  return setMapPoint(point);
}

function geopress_resetMap() {
  geo_map.setCenterAndZoom(new LatLonPoint(0, 0), 1);
}

/**
 * Cross-browser event registration helper.
 */
function geopress_addEvent(obj, evType, fn) {
  if (obj.addEventListener) {
    obj.addEventListener(evType, fn, false);
    return true;
  } else if (obj.attachEvent) {
    return obj.attachEvent('on' + evType, fn);
  }
  return false;
}

/**
 * Loads a saved location from the dropdown into the address/name fields.
 */
function geopress_loadsaved(oSel) {
  var addr    = oSel.options[oSel.selectedIndex].value;
  var name    = oSel.options[oSel.selectedIndex].text;
  var addrobj    = document.getElementById('addr');
  var locnameobj = document.getElementById('locname');
  document.getElementById('geometry').value = '';
  if (addrobj)    addrobj.value    = addr;
  if (locnameobj) locnameobj.value = name;
  oSel.selectedIndex = 0;
}

/**
 * Intercepts Enter key in the address field to trigger geocoding instead of form submission.
 */
function checkEnter(e) {
  var characterCode = e ? (e.which || e.keyCode) : event.keyCode;
  if (characterCode === 13) {
    geocode();
    return false;
  }
  return true;
}

// ── Map Configuration Functions ───────────────────────────────────────────────

function geopress_change_controls(oSel) {
  var pan      = document.getElementById('map_controls_pan').checked;
  var zoom     = document.getElementById('map_controls_zoom').value;
  var overview = document.getElementById('map_controls_overview').checked;
  var maptype  = document.getElementById('map_controls_map_type').checked;
  var scale    = document.getElementById('map_controls_scale').checked;
  geo_map.addControls({
    pan:      pan,
    zoom:     zoom,
    overview: overview,
    scale:    scale,
    map_type: maptype
  });
}

function geopress_change_map_format() {
  var map_format = document.getElementById('map_format').value;
  geo_map.swap(map_format);
}

function geopress_change_view() {
  var type_string = document.getElementById('map_view_type').value;
  var type;
  switch (type_string) {
    case 'satellite': type = Mapstraction.SATELLITE; break;
    case 'road':      type = Mapstraction.ROAD;      break;
    case 'hybrid':
    default:          type = Mapstraction.HYBRID;    break;
  }
  geo_map.setMapType(type);
}

function geopress_change_zoom() {
  var zoom_level = document.getElementById('default_zoom_level').value;
  geo_map.setZoom(zoom_level);
}

/**
 * Creates a travel-path map for a page with child pages.
 */
function geopress_maketravelmap(map_id, points, map_format, map_type, map_controls) {
  var myMap = new Mapstraction('geo_map' + map_id, map_format);
  geo_maps.push(myMap);
  if (map_controls) {
    myMap.addControls(map_controls);
  }
  myMap.setCenterAndZoom(new LatLonPoint(0, 0), 5);
  myMap.setMapType(map_type);

  var polyPoints = [];
  for (var p in points) {
    if (!Object.prototype.hasOwnProperty.call(points, p)) continue;
    var point  = new LatLonPoint(points[p].lat, points[p].lng);
    polyPoints.push(point);
    var marker = new Marker(point);
    marker.setInfoBubble(points[p].title ? unescape(points[p].title) : '');
    marker.setLabel(points[p].name ? unescape(points[p].name) : '');
    myMap.addMarker(marker);
  }

  var polyline = new Polyline(polyPoints);
  polyline.setWidth(3);
  polyline.setOpacity(0.8);
  polyline.setColor('#fa7');
  myMap.addPolyline(polyline);
  myMap.autoCenterAndZoom();
}
