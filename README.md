# GeoPress

> Geotag posts and pages, embed interactive maps, output geo microformats, and publish GeoRSS, KML, and GPX feeds.

![Version](https://img.shields.io/badge/version-3.1-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)

GeoPress is a WordPress plugin that adds geographic tagging to your posts and pages. Give a post a location — by place name, street address, or `[latitude, longitude]` — and GeoPress geocodes it, stores it, and lets you embed maps and publish geodata feeds.

- 🗺️ **Interactive maps** via the [Mapstraction](https://github.com/mapstraction/mxn) library (OpenLayers, OpenStreetMap, Google, Microsoft) or the [ArcGIS Maps SDK for JavaScript 5.0](https://developers.arcgis.com/javascript/latest/) (web components — `<arcgis-map>`, `<arcgis-scene>`, web maps and 3D scenes by portal item ID, feature-layer overlays)
- 📡 **GeoRSS** in your Atom / RSS 2.0 / RDF feeds (Simple, W3C, or GML)
- 🌍 **KML and GPX** feeds for Google Earth and GPS tools
- 🏷️ **Microformats** (geo, adr, hCard) for the semantic web
- ✍️ Works in both the **Block (Gutenberg)** and **Classic** editors
- 🔑 **No API key required** — geocoding uses OpenStreetMap Nominatim, and OpenLayers/OSM is the default map provider

Project home: <https://georss.org/geopress/>

## Requirements

| | |
|---|---|
| WordPress | 6.0+ |
| PHP | 8.0+ |
| API keys | None required (Google Maps key optional) |

## Installation

1. Install through **Plugins → Add New**, or copy the `geopress` folder into `wp-content/plugins/`.
2. Activate **GeoPress** from the Plugins screen. Activation creates the `{prefix}_geopress` database table.
3. *(Optional)* Open **GeoPress → Options** to set the default map provider, size, zoom, GeoRSS format, and UI controls. The defaults (OpenLayers / OpenStreetMap with Nominatim geocoding) need no configuration.
4. *(Optional)* To use Google Maps as the provider, add a Google Maps JavaScript API key under **GeoPress → Options**.
5. Edit a post or page. In the **Location** box, enter a place name, address, or `[lat, lon]` — or click the map to drop a point. The location is geocoded and saved when you save the post.

## Usage

### Adding a map to a post

| Method | How |
|--------|-----|
| Automatic | Set **Add Maps** in GeoPress → Options to append a map to located posts |
| Shortcode (Block editor) | Add a Shortcode block with `[geopress_post_map]` |
| Inline tag | Type `INSERT_MAP` in the post body |
| Theme template | Call `geopress_post_map()` in your theme |

### Post content tags

| Tag | Output |
|-----|--------|
| `INSERT_MAP` | Interactive map at the post's location |
| `INSERT_MAP(h,w)` | Map with custom height/width |
| `INSERT_MAP(h,w,url)` | Map with a KML/GeoRSS overlay |
| `INSERT_GEOPRESS_MAP(h,w)` | Map of all geotagged posts |
| `INSERT_COORDS` | Coordinates as a geo microformat |
| `INSERT_ADDRESS` | Address as an adr microformat |
| `INSERT_LOCATION` | Location name as an hCard microformat |
| `GEOPRESS_LOCATION(Address)` | Set the post's location inline by address |
| `GEOPRESS_LOCATION([lat,lon])` | Set the post's location inline by coordinates |
| `INSERT_ARCGIS_WEBMAP(item_id,h,w)` | Embed an ArcGIS Online/Enterprise web map by portal item ID (height/width optional) |
| `INSERT_ARCGIS_WEBSCENE(item_id,h,w)` | Embed an ArcGIS Online/Enterprise 3D web scene by portal item ID |

Machine tags in WordPress post tags also work: `geo:lat=60.15`, `geo:lon=24.94`.

### Shortcodes

```text
[geopress_map height="" width="" locations="-1" zoom_level="-1" url=""]
[geopress_post_map height="" width="" overlay=""]
[geopress_page_map height="" width=""]
```

### Template functions

Use these global functions in your theme, inside The Loop.

**Maps**

```php
geopress_map( $height, $width, $locations, $unique_id, $loop_locations, $zoom_level, $url );
geopress_post_map( $height, $width, $controls, $overlay );
geopress_page_map( $height, $width, $controls );
geopress_locations_list(); // echoes an <li> list of links to location pages
geopress_kml_link();       // outputs a link to the KML feed
```

For `geopress_map()`: `$locations` is the max number of markers (`-1` = all); set `$loop_locations` to `true` to show only the current query's markers (category, search, archive); a `$zoom_level` of `-1` fits all markers.

**Per-post data**

```php
has_location();        // true if the current post has a saved location
the_coord();           // "lat lon" string
the_address();         // stored address
the_location_name();   // saved location name
the_geo_mf();          // geo microformat HTML
the_adr_mf();          // adr microformat HTML
the_loc_mf();          // hCard microformat HTML
the_coord_rss();       // echoes GeoRSS coordinate tags in the configured format
```

## Feed endpoints

| File | Format | MIME type |
|------|--------|-----------|
| `wp-kml.php` | KML 2.2 (Google Earth) | `application/vnd.google-earth.kml+xml` |
| `wp-gpx.php` | GPX 1.0 (GPS devices) | `application/gpx+xml` |
| `wp-kml-link.php` | KML NetworkLink (auto-refresh wrapper) | `application/vnd.google-earth.kml+xml` |

## Development

There is no production build step. Dev tooling is Composer + PHPUnit.

```bash
composer install        # install dev dependencies (PHPUnit + Brain Monkey)
composer test           # run the unit test suite
composer test:coverage  # generate a coverage report in coverage/
```

The test suite uses [Brain Monkey](https://github.com/Brain-WP/BrainMonkey) to stub WordPress functions and Mockery to mock `$wpdb`, so no live WordPress install is required. Manual UI testing (the editor metabox, map rendering, feed output) is still needed for any UI/JS change.

`mapstraction.js` is a vendored third-party library — **do not edit it**.

## Architecture

`geopress.php` is a thin bootstrap (constants, includes, hook registration); all logic lives in `includes/`:

| File | Responsibility |
|------|----------------|
| `includes/class-geopress.php` | Core: DB, query hooks, `save_post`, content filters, enqueue |
| `includes/class-geopress-admin.php` | Admin pages and the post-editor Location metabox |
| `includes/class-geopress-maps.php` | Map rendering + theme-facing map functions |
| `includes/class-geopress-feeds.php` | GeoRSS namespace + Atom/RSS entry hooks |
| `includes/geocoding.php` | Nominatim geocoder |
| `includes/template-functions.php` | Theme globals (`has_location`, `the_coord`, …) |

## Changelog

See [CHANGES.TXT](CHANGES.TXT) for the full history.

**3.1** adds the [ArcGIS Maps SDK for JavaScript 5.0](https://developers.arcgis.com/javascript/latest/) as a new map provider — web maps, 3D scenes, feature layers, and the new `INSERT_ARCGIS_WEBMAP` / `INSERT_ARCGIS_WEBSCENE` content tags. Uses the single 5.0 CDN module entry with `$arcgis.import()` runtime loading; defaults to OpenStreetMap so no API key is required out of the box. Also unbreaks the PHPUnit suite (Patchwork bootstrap order) and fixes a latent `get_location()` negative-ID bug.

**3.0** modernized the plugin for WordPress 6.x / PHP 8.0+, refactored the single-file plugin into modular classes, added Block editor support and a test suite, switched geocoding to Nominatim, and removed the discontinued Yahoo Maps provider.

## Credits

**Authors:** Andrew Turner, Mikel Maron

Licensed under the [GNU General Public License v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
