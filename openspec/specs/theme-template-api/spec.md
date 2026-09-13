# theme-template-api

## Purpose

Expose a stable set of global template functions that theme authors can call inside the post loop to read and render a post's location.

## Requirements

### Requirement: The template function names are a stable contract

The theme-facing functions MUST remain global functions with unchanged names and signatures across the 3.x line, because themes call them directly and a rename is a silent breakage at render time.

#### Scenario: A 2.x theme is used with 3.x

- **WHEN** a theme calls `has_location()`, `the_coord()`, `the_address()`, `the_location_name()`, `the_geo_mf()`, `the_adr_mf()`, `the_loc_mf()` or `the_coord_rss()`
- **THEN** the call resolves without the theme being modified

### Requirement: Loop functions are safe outside a post context

Every function that reads the current post MUST check that a post is actually available before dereferencing it. Under PHP 8 reading a property of `null` is a fatal error, so an unguarded call on an archive page or in a widget would take the whole page down.

#### Scenario: Called with no current post

- **WHEN** `has_location()`, `the_coord()`, `the_address()` or `the_location_name()` is called with no global post, or a post with no ID
- **THEN** it returns an empty or false result without raising an error

#### Scenario: Called inside the loop

- **WHEN** one of those functions is called for a post that has a location
- **THEN** it returns that post's location data

### Requirement: Location data is available as microformats

The location MUST be renderable as `geo`, `adr` and `hCard` microformat markup, so published pages carry machine-readable location data.

#### Scenario: Post has coordinates

- **WHEN** `the_geo_mf()` is called for a post with coordinates
- **THEN** it returns markup with the `geo` class wrapping `latitude` and `longitude` values

#### Scenario: Post has no coordinates

- **WHEN** `the_geo_mf()` is called for a post without coordinates
- **THEN** it returns an empty string

### Requirement: Microformat output is escaped

Stored location values MUST be escaped when rendered into markup, because a location name or address is author-supplied text.

#### Scenario: Stored address contains markup

- **WHEN** a location whose address contains HTML is rendered through `the_adr_mf()`
- **THEN** the markup does not survive into the output

### Requirement: A map can be rendered for a set of locations

Theme functions MUST be able to render a map of one post, of a page and its children, and of many locations, so a theme can place maps outside post content.

#### Scenario: Theme renders a single post's map

- **WHEN** `geopress_post_map()` is called for a post with a location
- **THEN** a map centred on that location is returned

#### Scenario: Theme renders a travel map for a page

- **WHEN** `geopress_page_map()` is called for a page with geotagged children
- **THEN** a map is returned with a marker per child and a path connecting them

### Requirement: Building a loop map preserves the caller's loop

A function that iterates posts to collect locations MUST rewind the post loop when it finishes. Leaving the loop consumed makes the theme's own loop render nothing after the map.

#### Scenario: A map is built from the current loop

- **WHEN** locations are collected by iterating the current query
- **THEN** the post loop is rewound afterwards
- **AND** the caller's subsequent loop still iterates every post

### Requirement: Location filter parameters are validated

A location identifier arriving from the request MUST be unslashed and coerced to a non-negative integer before use.

#### Scenario: A location filter is requested

- **WHEN** a request carries a location identifier
- **THEN** it is unslashed and converted to an absolute integer before being used in a query

### Requirement: Location links are built without double encoding

Links that carry a location name MUST pass the raw value to `add_query_arg()`, which encodes it. Pre-encoding produces a link that no longer matches the stored name.

#### Scenario: A locations list is rendered

- **WHEN** `geopress_locations_list()` renders a link for a location whose name contains a space
- **THEN** the name is encoded exactly once in the resulting URL
