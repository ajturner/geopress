# map-configuration

## Purpose

Hold the site-wide map and feed settings — provider, view type, zoom, controls, dimensions and marker — and apply them as the defaults for every map GeoPress renders.

## Requirements

### Requirement: Settings are seeded on activation

Activation MUST create the plugin's table and register default values for every setting, using a mechanism that leaves an existing value untouched, so reactivating never resets a configured site.

#### Scenario: Plugin is activated for the first time

- **WHEN** the plugin is activated on a site with no prior GeoPress data
- **THEN** the locations table is created
- **AND** every setting is seeded with its default

#### Scenario: Plugin is reactivated

- **WHEN** the plugin is activated on a site that already has settings
- **THEN** the existing values are preserved

### Requirement: The default provider needs no credentials

The default map provider MUST be one that works without an API key, so maps render on a fresh install with no configuration. Providers requiring a key MUST be opt-in.

#### Scenario: A fresh install renders a map

- **WHEN** a map is rendered on a newly activated site
- **THEN** it uses an OpenStreetMap-based provider
- **AND** no API key is required

### Requirement: Only supported providers are offered

The provider list MUST offer exactly the providers GeoPress can render. Yahoo Maps MUST NOT appear, and its setting MUST NOT exist, because its mapping and geocoding APIs are discontinued.

#### Scenario: Administrator opens the Maps page

- **WHEN** the provider dropdown is rendered
- **THEN** it lists Google, Microsoft, OpenStreetMap and OpenLayers
- **AND** it does not list Yahoo

#### Scenario: Options page is rendered

- **WHEN** the Options page is rendered
- **THEN** it contains no Yahoo application ID field

### Requirement: A default marker ships with the plugin

The default marker MUST be an asset bundled with the plugin, so markers appear without the administrator supplying an image. Every place that reads the marker setting MUST fall back to that asset.

#### Scenario: Marker is not configured

- **WHEN** a map is rendered and no custom marker is set
- **THEN** the bundled SVG marker is used

### Requirement: Map dimensions are editable and applied

Map width and height MUST be configurable, with input fields wide enough to show their value, and MUST be used as the default dimensions wherever a map is rendered without explicit ones.

#### Scenario: Administrator changes the default size

- **WHEN** the default width and height are saved
- **THEN** maps rendered without explicit dimensions use those values

### Requirement: View type, zoom and controls are configurable

The map view type, default zoom level and which controls appear MUST be configurable, and MUST be applied to rendered maps.

#### Scenario: Settings are applied to a map

- **WHEN** a map is rendered
- **THEN** it uses the configured view type, default zoom and control set

### Requirement: Settings pages are restricted and nonce-protected

Every GeoPress settings page MUST require the `manage_options` capability, and every settings form MUST verify a nonce and sanitize its input before saving.

#### Scenario: Settings are submitted

- **WHEN** a settings form is submitted with a valid nonce by a permitted user
- **THEN** the sanitized values are saved

#### Scenario: Nonce verification fails

- **WHEN** a settings submission fails nonce verification
- **THEN** no settings are changed

### Requirement: Stored values are escaped when rendered

Setting values MUST be escaped for their output context when redisplayed in a form or embedded in generated JavaScript, since they include administrator-supplied URLs and text.

#### Scenario: A configured marker URL is redisplayed

- **WHEN** the marker setting is rendered back into its form field
- **THEN** the value is escaped for an attribute context
