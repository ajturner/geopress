# geo-feeds

## Purpose

Publish the site's location data in the standard geospatial syndication formats, so that feed readers, Google Earth and GPS software can consume where posts are.

## Requirements

### Requirement: Feeds carry location data when enabled

GeoRSS output in the site's Atom and RSS feeds MUST be controlled by a setting, so a site can opt out.

#### Scenario: GeoRSS is enabled and the post has coordinates

- **WHEN** a feed item is emitted for a post with stored coordinates and GeoRSS output is enabled
- **THEN** the item includes the post's coordinates

#### Scenario: GeoRSS is disabled

- **WHEN** GeoRSS output is disabled
- **THEN** no location elements are emitted

#### Scenario: Post has no location

- **WHEN** a feed item is emitted for a post with no stored coordinates
- **THEN** no location elements are emitted

### Requirement: The coordinate encoding is selectable

The feed encoding MUST be selectable between GeoRSS Simple, W3C Basic Geo and GML, and the declared XML namespaces MUST match the selected encoding. A namespace declared without matching elements, or elements emitted without their namespace, produce a feed consumers cannot parse.

#### Scenario: Simple format is selected

- **WHEN** the format is `simple`
- **THEN** the GeoRSS namespace is declared
- **AND** coordinates are emitted as a single GeoRSS point

#### Scenario: W3C format is selected

- **WHEN** the format is `w3c`
- **THEN** the WGS84 geo namespace is declared
- **AND** latitude and longitude are emitted as separate elements

#### Scenario: GML format is selected

- **WHEN** the format is `gml`
- **THEN** both the GeoRSS and GML namespaces are declared
- **AND** coordinates are emitted as GML inside a GeoRSS element

### Requirement: Namespaces are declared on every feed type

The namespace hook MUST fire for Atom, RSS 2.0, RSS and RDF feeds, so location elements are never emitted into a feed that has not declared their namespace.

#### Scenario: Any supported feed is requested

- **WHEN** an Atom, RSS 2.0, RSS or RDF feed is generated
- **THEN** the configured geo namespaces are declared on the feed root

### Requirement: KML and GPX endpoints are available

Dedicated endpoints MUST publish locations as KML and GPX with their correct media types, so Google Earth and GPS devices can consume them directly.

#### Scenario: KML feed is requested

- **WHEN** the KML endpoint is requested
- **THEN** KML 2.2 is returned with the Google Earth KML media type

#### Scenario: GPX feed is requested

- **WHEN** the GPX endpoint is requested
- **THEN** GPX 1.0 is returned with the GPX media type

#### Scenario: A network link is requested

- **WHEN** the KML network-link endpoint is requested
- **THEN** a KML NetworkLink wrapping the KML feed URL is returned, so Google Earth can refresh it

### Requirement: Feed endpoints bootstrap WordPress robustly

Each standalone feed endpoint MUST locate and load WordPress by walking up from its own directory to find `wp-load.php`, rather than assuming a fixed relative path. A plugin directory can be moved or symlinked, and a hard-coded path breaks when it is.

#### Scenario: Plugin lives in a non-default location

- **WHEN** a feed endpoint is requested and the plugin is not at the conventional depth
- **THEN** WordPress is still located and loaded

### Requirement: Feeds are not truncated by the posts-per-page setting

A geospatial feed MUST return all matching posts rather than one page of them, so a map built from the feed is not silently missing points.

#### Scenario: The site has more posts than one page

- **WHEN** a KML feed is generated on a site with more geotagged posts than the page size
- **THEN** every geotagged post is included

### Requirement: Feed query parameters are sanitized individually

When an endpoint forwards a query string, each key and value MUST be parsed and sanitized separately. Sanitizing the whole query string at once corrupts the separators and loses parameters.

#### Scenario: A network link forwards query parameters

- **WHEN** a query string is forwarded to the KML feed URL
- **THEN** each parameter survives with its own key and value intact
