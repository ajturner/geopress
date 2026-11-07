# map-embedding

## Purpose

Render interactive maps inside post and page content, both for authors writing in the Classic editor's text tags and for authors using the Block editor.

## Requirements

### Requirement: Content tags expand to maps

Legacy `INSERT_MAP` tags in post content MUST continue to expand, so content authored against GeoPress 2.x keeps rendering.

#### Scenario: Bare map tag

- **WHEN** post content contains `INSERT_MAP`
- **THEN** it is replaced by a map centred on the post's location

#### Scenario: Map tag with dimensions

- **WHEN** post content contains `INSERT_MAP(<height>,<width>)`
- **THEN** the map is rendered at those dimensions

#### Scenario: Map tag with an overlay

- **WHEN** post content contains `INSERT_MAP(<height>,<width>,<url>)`
- **THEN** the map is rendered at those dimensions
- **AND** the KML or GeoRSS resource at the URL is added as an overlay

#### Scenario: Map of all geotagged posts

- **WHEN** post content contains `INSERT_GEOPRESS_MAP`, with or without dimensions
- **THEN** it is replaced by a map showing every geotagged post

### Requirement: Shortcodes provide a Block editor path

Because typing bare text tags is awkward in the Block editor, equivalent shortcodes MUST be registered so authors can insert maps through the Shortcode block.

#### Scenario: Author inserts a post map

- **WHEN** `[geopress_post_map]` appears in content, optionally with `height`, `width` or `overlay`
- **THEN** a map of the post's location is rendered

#### Scenario: Author inserts a multi-location map

- **WHEN** `[geopress_map]` appears in content, optionally with `height`, `width`, `locations`, `zoom_level` or `url`
- **THEN** a map of the matching locations is rendered

#### Scenario: Author inserts a page map

- **WHEN** `[geopress_page_map]` appears in content, optionally with `height` or `width`
- **THEN** a map of the page and its child pages is rendered

### Requirement: Microformat tags expand to marked-up location data

Tags that emit the post's location as text MUST expand to microformat markup, so the location is machine-readable.

#### Scenario: Coordinates, address and name tags

- **WHEN** post content contains `INSERT_COORDS`, `INSERT_ADDRESS` or `INSERT_LOCATION`
- **THEN** each is replaced by the `geo`, `adr` or `hCard` microformat for the post's location

### Requirement: Only the active provider's API is loaded

Map provider scripts MUST be enqueued only for the configured provider, so a site does not download and execute mapping APIs it never uses.

#### Scenario: OpenStreetMap is configured

- **WHEN** the configured provider is `openlayers` or `openstreetmap`
- **THEN** the OpenLayers library is enqueued

#### Scenario: Google Maps is configured

- **WHEN** the configured provider is `google`
- **THEN** the Google Maps v3 API is enqueued with the configured key applied as a query argument
- **AND** the key is not pre-encoded before being added to the URL

### Requirement: Each map on a page is independent

Multiple maps MUST be able to coexist on one page. Map instances MUST be tracked in a registry and referred to by index rather than by a single global, so a second map does not disturb the first.

#### Scenario: A post map carries an overlay

- **WHEN** a map is rendered with an overlay
- **THEN** the overlay is attached to that map instance from inside its load handler

### Requirement: Editor map code tolerates a missing map

The editor's JavaScript helpers MUST return harmlessly when no map widget is present, because the Location box does not always render one.

#### Scenario: Location box has no map widget

- **WHEN** an editor helper such as `showLocation()` or `setMapPoint()` runs with no map instance
- **THEN** it returns without raising an error

### Requirement: Geometry is cleared only by an explicit new point

Stored coordinates MUST NOT be discarded as a side effect of re-displaying a location. Only choosing a new point on the map may clear them.

#### Scenario: Author reopens a post with a saved location

- **WHEN** the editor redisplays an existing location
- **THEN** the geometry field retains its stored coordinates

#### Scenario: Author clicks a new point

- **WHEN** the author clicks a point on the editor map
- **THEN** the geometry field is cleared and the address is replaced with the clicked coordinates
