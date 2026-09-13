# CLAUDE.md — GeoPress

This file provides context for AI assistants working in this repository.

## Project Overview

**GeoPress** is a WordPress plugin (v3.0.1) that adds geographic tagging capabilities to blog posts and pages. It enables:

- Tagging posts/pages with geographic coordinates or addresses
- Embedding interactive maps using multiple providers (Google, Microsoft, OpenStreetMap, OpenLayers)
- Exporting location data as GeoRSS, KML, and GPX feeds
- Outputting geo microformats (geo, adr, hCard) for semantic web compatibility

**Author:** Andrew Turner, Mikel Maron  
**License:** GNU General Public License v2+  
**Target:** WordPress 6.0+ (tested to 7.0), PHP 8.0+

---

## Specs come first

Behaviour is specified in `openspec/specs/<capability>/spec.md`. **Those specs are
the source of truth for what GeoPress does** — when this file and a spec disagree,
the spec wins, and this file should be corrected.

Capabilities: `post-geotagging`, `geocoding`, `location-management`,
`map-embedding`, `geo-feeds`, `theme-template-api`, `map-configuration`.

Work through the OpenSpec workflow rather than editing specs by hand:

```bash
npx @fission-ai/openspec list --specs        # inventory
npx @fission-ai/openspec show <capability>   # read one
npx @fission-ai/openspec validate --specs --all --strict
```

Slash commands (`/opsx:propose`, `/opsx:apply`, `/opsx:archive`, …) and their
backing skills live in `.claude/`. Project conventions the workflow feeds to
agents are in `openspec/config.yaml`; keep that file and this one consistent.

When you change behaviour, update the relevant spec in the same change.

---

## Repository Structure

```
geopress/
├── geopress.php        # Thin bootstrap: constants, includes, hook registration
├── includes/           # All PHP logic (see Architecture below)
├── geopress.js         # Frontend + editor JavaScript for map interaction
├── mapstraction.js     # Third-party multi-provider mapping library (do not edit)
├── images/marker.svg   # Default map marker
├── wp-kml.php          # KML 2.2 feed output
├── wp-gpx.php          # GPX 1.0 feed output
├── wp-kml-link.php     # KML NetworkLink wrapper (for Google Earth auto-refresh)
├── openspec/           # Capability specs + change proposals (source of truth)
├── tests/              # PHPUnit unit suite (Brain Monkey + Mockery)
├── .github/workflows/  # CI: lint + unit tests on PHP 8.0–8.4
├── README.md           # User and developer documentation
└── CHANGES.TXT         # Version history
```

There is no production build step. Dev tooling is Composer + PHPUnit; CI runs on
GitHub Actions. `composer.lock` is deliberately not committed — see `.gitignore`.

---

## Architecture

### PHP — `includes/`

`geopress.php` is a thin bootstrap. The logic is split by concern:

| File | Responsibility |
|------|----------------|
| `includes/class-geopress.php` | Core: DB, query hooks, `save_post`, content filters, enqueue |
| `includes/class-geopress-admin.php` | Admin pages (`GeoPress_Admin`) and the editor Location metabox |
| `includes/class-geopress-maps.php` | Map rendering (`GeoPress_Maps`) + theme-facing map functions |
| `includes/class-geopress-feeds.php` | GeoRSS namespaces + Atom/RSS entries (`GeoPress_Feeds`) |
| `includes/geocoding.php` | Nominatim geocoder + deprecated `yahoo_*` shims |
| `includes/template-functions.php` | Theme globals |

#### GeoPress class (key static methods)

| Method | Purpose |
|--------|---------|
| `install()` | Creates `{prefix}_geopress` DB table, initializes options |
| `save_geo(...)` | Saves a location record to the database |
| `get_geo($post_id)` | Gets location data for a specific post |
| `get_location($loc_id)` | Retrieves a location by ID |
| `get_locations($number)` | Lists all stored locations |
| `delete_location($loc_id)` | Deletes a location and clears post references |
| `update_post($post_id)` | Saves location when a post is saved |
| `embed_map_inpost($content)` | Processes `INSERT_MAP` and `GEOPRESS_LOCATION` tags in post content |
| `mapstraction_map_format()` | Returns configured map provider |
| `mapstraction_map_type()` | Returns map view type (road/satellite/hybrid) |
| `mapstraction_map_controls()` | Builds map control object |
| `join_clause($join)` | Modifies SQL JOIN for location-filtered queries |
| `where_clause($where)` | Modifies SQL WHERE for location filtering |


Admin screens are on `GeoPress_Admin` (`geopress_options_page()`,
`geopress_locations_page()`, `geopress_maps_page()`, `register_meta_boxes()`);
feed output is on `GeoPress_Feeds` (`geopress_namespace()`, `atom_entry()`,
`rss2_item()`).

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
| `map_format` | tinytext | Provider: google, microsoft, openstreetmap, openlayers |
| `map_zoom` | tinyint | Default zoom level |
| `map_type` | tinytext | View: road, satellite, hybrid |

Post-to-location relationships are stored in the standard WordPress `postmeta` table under the key `_geopress_id`.

### WordPress Options (wp_options)

| Key | Default | Purpose |
|-----|---------|---------|
| `_geopress_mapwidth` | 400 | Default map width (px) |
| `_geopress_mapheight` | 200 | Default map height (px) |
| `_geopress_marker` | `images/marker.svg` | Marker icon URL |
| `_geopress_rss_enable` | true | Enable GeoRSS in feeds |
| `_geopress_rss_format` | simple | GeoRSS format: simple, w3c, gml |
| `_geopress_map_format` | openlayers | Map provider |
| `_geopress_map_type` | hybrid | Map type |
| `_geopress_default_zoom_level` | 11 | Zoom level (1–18) |
| `_geopress_google_apikey` | "" | Google Maps v3 API key |
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

All registration lives in `geopress.php`.

**Actions:**
- activation (via `register_activation_hook()`) → `GeoPress::install()`
- `save_post` → `GeoPress::update_post()`
- `add_meta_boxes` → `GeoPress_Admin::register_meta_boxes()`
- `template_redirect` → `GeoPress::location_redirect()`
- `admin_menu` → `GeoPress_Admin::admin_menu()`
- `wp_enqueue_scripts` → `GeoPress::enqueue_scripts()`
- `admin_enqueue_scripts` → `GeoPress::enqueue_admin_scripts()`
- `atom_ns`, `rss2_ns`, `rdf_ns`, `rss_ns` → `GeoPress_Feeds::geopress_namespace()`
- `atom_entry` → `GeoPress_Feeds::atom_entry()`
- `rss2_item`, `rdf_item`, `rss_item` → `GeoPress_Feeds::rss2_item()`

**Filters:**
- `the_content` → `GeoPress::embed_map_inpost()`, then `GeoPress::embed_data_inpost()`
- `posts_join` → `GeoPress::join_clause()`
- `posts_where` → `GeoPress::where_clause()`

**Shortcodes:** `[geopress_map]`, `[geopress_post_map]`, `[geopress_page_map]` —
these are the Block editor path to the `INSERT_MAP` tags above.

---

## Development Conventions

### PHP
- Core logic is static methods on the `GeoPress` class; admin, map and feed code lives on `GeoPress_Admin`, `GeoPress_Maps` and `GeoPress_Feeds`. Keep new code on the class that owns the concern.
- Theme-facing functions are global PHP functions in `includes/template-functions.php`. Their names are a public contract — themes call them directly, so a rename is a silent breakage at render time.
- WordPress coding style: snake_case for functions and variables, tabs in PHP files, no strict typing.
- Never pre-encode a value passed to `add_query_arg()` — it does its own encoding, and pre-encoding corrupts the result.
- All SQL goes through `$wpdb->prepare()`, `$wpdb->insert()` or `$wpdb->update()`. Never build a query by concatenating request data.
- Settings are read/written with `get_option()` / `update_option()`.

### JavaScript
- Uses global state (the `geo_maps` array). Avoid adding module systems.
- Mapstraction API: use `Mapstraction`, `LatLonPoint`, and `Marker` objects from `mapstraction.js`.
- jQuery is not used — vanilla JS only.

### Security Considerations
- Every query goes through `$wpdb->prepare()`, `$wpdb->insert()` or `$wpdb->update()`. Never concatenate request data into SQL.
- Unslash and sanitize all request input (`wp_unslash()` then `sanitize_text_field()`, `absint()`, `floatval()`) before use or storage.
- Escape all output for its context: `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`.
- Every state-changing form is nonce-protected, including the post metabox. Keep it that way when adding forms.
- Verify a nonce with `wp_unslash()` only — never pass it through `sanitize_key()` or similar, which can alter the value and make verification fail.

---

## Development Workflow

There is no production build step. To develop:

1. Place the `geopress/` directory in a WordPress installation's `wp-content/plugins/` folder.
2. Activate the plugin via the WordPress admin.
3. Edit PHP and JavaScript files directly.
4. Run the unit suite, then verify in the WordPress admin and front-end.

```bash
composer install   # dev dependencies (PHPUnit + Brain Monkey)
composer test      # unit suite
```

The suite stubs WordPress, so no live install is needed. It does **not** cover the
editor UI, map rendering or feed output — verify those manually against a real
WordPress install.

Two constraints when adding tests:

- Declare the WordPress functions a test needs with Brain Monkey, in the test.
  Never add stubs to `tests/bootstrap.php`: Patchwork cannot redefine a function
  declared in a file that was already executing when it loaded, so a stub there
  becomes permanently unmockable and every test that mocks it fails.
- Plugin functions (`geocode()`, the `yahoo_*` shims) cannot be mocked for the
  same reason. Stub the WordPress calls they make instead, e.g. `wp_remote_get()`.

---

## Known Issues & Limitations

- Single coordinate support only — no polylines, polygons, or multiple points per post.
- Yahoo Maps is removed. The `yahoo_geocode()`, `yahoo_zoom()` and `yahoo_mapurl()`
  functions survive only as deprecated shims for themes written against 2.x;
  GeoPress itself must not call them.
- Microsoft Maps is still offered as a provider but its API is legacy.
- Google Maps requires a v3 API key; the provider is opt-in and OpenLayers /
  OpenStreetMap is the keyless default.
- Geocoding depends on the public Nominatim service, which rate-limits and
  requires an identifying `User-Agent`.
- The functional paths (Block editor metabox, per-provider map rendering,
  GeoRSS/KML/GPX output) have no automated coverage.

---

## Feed Endpoints

| File | MIME Type | Format |
|------|-----------|--------|
| `wp-kml.php` | `application/vnd.google-earth.kml+xml` | KML 2.2 |
| `wp-gpx.php` | `application/gpx+xml` | GPX 1.0 |
| `wp-kml-link.php` | `application/vnd.google-earth.kml+xml` | KML NetworkLink |

These files bootstrap WordPress (require `wp-load.php`) and output XML directly.
