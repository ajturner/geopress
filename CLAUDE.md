# CLAUDE.md — GeoPress

This file provides context for AI assistants working in this repository.

## Project Overview

**GeoPress** is a WordPress plugin (v2.5-beta) that adds geographic tagging capabilities to blog posts and pages. It enables:

- Tagging posts/pages with geographic coordinates or addresses
- Embedding interactive maps using multiple providers (Google, Yahoo, Microsoft, OpenStreetMap, OpenLayers)
- Exporting location data as GeoRSS, KML, and GPX feeds
- Outputting geo microformats (geo, adr, hCard) for semantic web compatibility

**Author:** Andrew Turner, Mikel Maron  
**License:** GNU General Public License v2+  
**Target:** WordPress 2.0–2.8 era plugin

---

## Repository Structure

```
geopress/
├── geopress.php        # Main plugin file — all core PHP logic (~1,600 lines)
├── geopress.js         # Frontend JavaScript for map interaction (~163 lines)
├── mapstraction.js     # Third-party multi-provider mapping abstraction library (~4,500 lines, do not edit)
├── wp-kml.php          # KML 2.2 feed output
├── wp-gpx.php          # GPX 1.0 feed output
├── wp-kml-link.php     # KML NetworkLink wrapper (for Google Earth auto-refresh)
├── README.TXT          # User-facing documentation
└── CHANGES.TXT         # Version history
```

There is no build system, no package.json, no composer.json, no test suite, and no CI configuration. This is a single-file PHP plugin with minimal tooling.

---

## Architecture

### PHP — `geopress.php`

All PHP logic lives in one file. The code is organized into:

1. **`GeoPress` class** — static methods for all plugin functionality
2. **Standalone geocoding functions** — `geocode()`, `yahoo_geocode()`, `yahoo_mapurl()`, `yahoo_zoom()`
3. **Template API functions** — global functions intended for use in WordPress theme templates

#### GeoPress Class (key static methods)

| Method | Purpose |
|--------|---------|
| `install()` | Creates `{prefix}_geopress` DB table, initializes options |
| `save_geo(...)` | Saves a location record to the database |
| `get_geo($post_id)` | Gets location data for a specific post |
| `get_location($loc_id)` | Retrieves a location by ID |
| `get_locations($number)` | Lists all stored locations |
| `location_edit_form()` | Renders location metabox in post editor |
| `update_post($post_id)` | Saves location when a post is saved |
| `embed_map_inpost($content)` | Processes `INSERT_MAP` and `GEOPRESS_LOCATION` tags in post content |
| `geopress_options_page()` | Admin settings page |
| `geopress_locations_page()` | Admin location management page |
| `geopress_maps_page()` | Admin map configuration page |
| `mapstraction_map_format()` | Returns configured map provider |
| `mapstraction_map_type()` | Returns map view type (road/satellite/hybrid) |
| `mapstraction_map_controls()` | Builds map control object |
| `join_clause($join)` | Modifies SQL JOIN for location-filtered queries |
| `where_clause($where)` | Modifies SQL WHERE for location filtering |
| `geopress_namespace()` | Outputs XML namespace declarations in feeds |
| `atom_entry($post_id)` | Outputs GeoRSS in Atom feeds |
| `rss2_item($post_id)` | Outputs GeoRSS in RSS 2.0 feeds |

#### Template API Functions (for theme use in the_loop)

```php
has_location()              // Check if current post has a location
the_coord()                 // Return "lat lon" string
the_address()               // Return stored address
the_location_name()         // Return saved location name
the_geo_mf()                // Return geo microformat HTML
the_adr_mf()                // Return adr microformat HTML
the_loc_mf()                // Return hCard microformat HTML
the_coord_rss()             // Output GeoRSS coordinates
geopress_map(...)           // Output a multi-location map
geopress_post_map(...)      // Output single-post map
geopress_page_map(...)      // Output map for a page and its children
geopress_locations_list()   // Output list of all locations with links
geopress_kml_link()         // Output KML feed link
```

### Database Schema

GeoPress creates one custom table: `{prefix}_geopress`

| Column | Type | Description |
|--------|------|-------------|
| `id` | int, auto-increment PK | Location ID |
| `name` | tinytext | Human label (e.g., "Home") |
| `loc` | tinytext | Address or location string |
| `warn` | tinytext | Geocoding warnings |
| `mapurl` | tinytext | Static map image URL |
| `coord` | text | Lat/lon as `"lat lon"` (space-separated) |
| `geom` | varchar(16) | Geometry type (e.g., `"point"`) |
| `relationshiptag` | tinytext | GeoRSS relationship |
| `featuretypetag` | tinytext | GeoRSS feature type |
| `elev` | float | Elevation |
| `floor` | float | Floor level |
| `radius` | float | Accuracy radius |
| `visible` | tinyint | Visibility flag |
| `map_format` | tinytext | Provider: google, yahoo, microsoft, openstreetmap, openlayers |
| `map_zoom` | tinyint | Default zoom level |
| `map_type` | tinytext | View: road, satellite, hybrid |

Post-to-location relationships are stored in the standard WordPress `postmeta` table under the key `_geopress_id`.

### WordPress Options (wp_options)

| Key | Default | Purpose |
|-----|---------|---------|
| `_geopress_mapwidth` | 400 | Default map width (px) |
| `_geopress_mapheight` | 200 | Default map height (px) |
| `_geopress_marker` | flag.png | Custom marker icon URL |
| `_geopress_rss_enable` | true | Enable GeoRSS in feeds |
| `_geopress_rss_format` | simple | GeoRSS format: simple, w3c, gml |
| `_geopress_map_format` | openlayers | Map provider |
| `_geopress_map_type` | hybrid | Map type |
| `_geopress_default_zoom_level` | 11 | Zoom level (1–18) |
| `_geopress_google_apikey` | "" | Google Maps v2 API key |
| `_geopress_yahoo_appid` | "" | Yahoo Maps App ID |
| `_geopress_controls_*` | varies | UI controls: pan, zoom, overview, scale, map_type |

### JavaScript — `geopress.js`

Handles the post editor map widget and frontend map rendering via Mapstraction.

Key globals:
- `geo_maps[]` — array of map instances
- `num_maps` — instance counter

Key functions:
- `geopress_makemap(map_id, name, lat, lon, map_format, ...)` — initializes a Mapstraction map
- `geopress_setmap()` — resets map state
- `setMapPoint(point)` — centers map and adds marker
- `setClickPoint(point)` — handles click-to-set-location in post editor
- `showLocation(addr, geometry)` — geocodes address and pans map
- `geopress_loadsaved(elem)` — loads a saved location from admin dropdown
- `geopress_resetMap()` — resets to blank map

`mapstraction.js` is a third-party library — **do not edit it**.

---

## Post Content Tags

GeoPress processes special tags in post content via `embed_map_inpost()`:

| Tag | Output |
|-----|--------|
| `INSERT_MAP` | Interactive map at post's location |
| `INSERT_MAP(h,w)` | Map with custom height/width |
| `INSERT_MAP(h,w,url)` | Map with KML/GeoRSS overlay |
| `INSERT_GEOPRESS_MAP(h,w)` | Map of all geotagged posts |
| `INSERT_COORDS` | Location coordinates in geo microformat |
| `INSERT_ADDRESS` | Address in adr microformat |
| `INSERT_LOCATION` | Location name in hCard microformat |
| `GEOPRESS_LOCATION(Address)` | Define inline location by address |
| `GEOPRESS_LOCATION([lat,lon])` | Define inline location by coordinates |

Machine tags in WordPress post tags are also supported: `geo:lat=60.15`, `geo:lon=24.94`.

---

## WordPress Hooks Used

**Actions:**
- `activate_geopress/geopress.php` → `GeoPress::install()`
- `save_post`, `edit_post`, `publish_post` → `GeoPress::update_post()`
- `the_content` → `GeoPress::embed_map_inpost()`
- `edit_form_advanced`, `simple_edit_form`, `edit_page_form` → `GeoPress::location_edit_form()`
- `template_redirect` → `GeoPress::location_redirect()`
- `admin_menu` → registers admin pages
- `wp_head`, `admin_head` → outputs scripts/styles
- `atom_ns`, `rss2_ns`, `rdf_ns`, `rss_ns` → `GeoPress::geopress_namespace()`
- `atom_entry`, `rss2_item`, `rdf_item` → outputs feed location data

**Filters:**
- `posts_join` → `GeoPress::join_clause()`
- `posts_where` → `GeoPress::where_clause()`

---

## Development Conventions

### PHP
- All plugin logic is static methods on the `GeoPress` class — maintain this pattern for core functionality.
- Template/theme-facing functions are global PHP functions defined after the class.
- WordPress coding style: snake_case for functions and variables, no strict typing.
- SQL is built with string concatenation and `$wpdb->prefix`. The codebase uses deprecated `mysql_real_escape_string()` — when modifying SQL, prefer `$wpdb->prepare()` and `$wpdb->get_results()` instead.
- Settings are read/written with `get_option()` / `update_option()`.

### JavaScript
- Uses global state (the `geo_maps` array). Avoid adding module systems.
- Mapstraction API: use `Mapstraction`, `LatLonPoint`, and `Marker` objects from `mapstraction.js`.
- jQuery is not used — vanilla JS only.

### Security Considerations
- The codebase predates WordPress's `$wpdb->prepare()` adoption. When adding or modifying any SQL, **always use `$wpdb->prepare()`** to prevent SQL injection.
- Sanitize all user input with `sanitize_text_field()`, `intval()`, `floatval()` etc. before saving.
- Escape all output with `esc_html()`, `esc_attr()`, `esc_url()` as appropriate.
- Nonces should be used for any form submissions (some existing forms lack them — add when modifying).

---

## Development Workflow

There is no build step. To develop:

1. Place the `geopress/` directory in a WordPress installation's `wp-content/plugins/` folder.
2. Activate the plugin via the WordPress admin.
3. Edit PHP and JavaScript files directly.
4. Test via the WordPress admin and front-end.

There is no test suite. Manual testing is required.

### Git Branch

Development for AI-assisted changes happens on: `claude/add-claude-documentation-aKOQj`

---

## Known Issues & Limitations

- Uses deprecated `mysql_*` PHP functions (pre-MySQLi). Any new DB code must use `$wpdb`.
- Single coordinate support only — no polylines, polygons, or multiple points per post.
- Yahoo Maps and Microsoft Maps APIs referenced are legacy/discontinued.
- Google Maps API v2 key required (v2 is long deprecated).
- A potential SQL injection point is noted in the original code with a `// SQL INJECTION POSSIBLE?` comment near line 377 — treat any nearby query code with caution.
- Some URL rewrite rules are commented out (lines 1092–1103 in `geopress.php`).

---

## Feed Endpoints

| File | MIME Type | Format |
|------|-----------|--------|
| `wp-kml.php` | `application/vnd.google-earth.kml+xml` | KML 2.2 |
| `wp-gpx.php` | `application/gpx+xml` | GPX 1.0 |
| `wp-kml-link.php` | `application/vnd.google-earth.kml+xml` | KML NetworkLink |

These files bootstrap WordPress (require `wp-load.php`) and output XML directly.
