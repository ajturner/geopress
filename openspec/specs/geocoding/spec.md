# geocoding

## Purpose

Turn a human-entered location string into a latitude/longitude pair, so that every other GeoPress capability can work with coordinates regardless of whether the author typed an address or raw coordinates.

## Requirements

### Requirement: Explicit coordinates bypass the geocoding service

A location string already containing coordinates MUST be parsed locally and MUST NOT trigger a network request. This lets authors pin an exact point and keeps the plugin usable when the geocoding service is unreachable.

#### Scenario: Author enters bracketed coordinates

- **WHEN** the location string matches `[lat, lon]` (with or without a space after the comma)
- **THEN** the latitude and longitude are taken from the brackets
- **AND** no HTTP request is made

#### Scenario: Stored coordinate string is re-read

- **WHEN** a location string is a bare coordinate pair separated by a comma or whitespace, such as `51.5 -0.1`
- **THEN** it is parsed into latitude and longitude directly

### Requirement: Addresses resolve through Nominatim

A free-text address MUST be resolved using the OpenStreetMap Nominatim service over `wp_remote_get()`, requesting a single JSON result. Nominatim requires no API key, so geocoding MUST work on a fresh install with no configuration.

#### Scenario: Address resolves successfully

- **WHEN** an address is submitted and Nominatim returns at least one result
- **THEN** the latitude and longitude of the first result are returned as strings

#### Scenario: Request identifies the caller

- **WHEN** a geocoding request is issued
- **THEN** it sends a `User-Agent` identifying GeoPress and its version
- **AND** it applies a bounded timeout

### Requirement: Geocoding failure is non-fatal

A failed lookup MUST return empty coordinates rather than raising an error or halting the save, so a bad address never costs the author their post.

#### Scenario: Service is unreachable

- **WHEN** the HTTP request returns a `WP_Error`
- **THEN** empty latitude and longitude are returned

#### Scenario: No match found

- **WHEN** Nominatim returns an empty result set, or a result without `lat`/`lon`
- **THEN** empty latitude and longitude are returned

### Requirement: Query values are not double-encoded

Query parameters MUST be passed to `add_query_arg()` unencoded, because that function performs its own encoding. Pre-encoding corrupts multi-word addresses.

#### Scenario: Multi-word address is requested

- **WHEN** an address containing spaces is geocoded
- **THEN** the request URL encodes each space exactly once

### Requirement: Legacy Yahoo helpers remain callable

The `yahoo_geocode()`, `yahoo_zoom()` and `yahoo_mapurl()` functions MUST remain defined as deprecated shims, because themes written against GeoPress 2.x may still call them. GeoPress itself MUST NOT call them.

#### Scenario: A theme calls the legacy geocoder

- **WHEN** a theme calls `yahoo_geocode()`
- **THEN** the call delegates to the current geocoder and returns coordinates

#### Scenario: A theme calls the legacy static-map helper

- **WHEN** a theme calls `yahoo_mapurl()`
- **THEN** it returns a pair of empty strings without making a request
