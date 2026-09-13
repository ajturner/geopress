# post-geotagging

## Purpose

Let an author attach a geographic location to a post or page from the editor, and persist that location so it can be mapped, listed and syndicated.

## Requirements

### Requirement: Location editor is available in both editors

A Location box MUST be registered as a post meta box so authors can set a location without leaving the editor. It MUST use the `side` context, because a side meta box renders outside the block editor's iframed canvas and therefore appears in the document sidebar in the Block editor as well as the Classic editor.

#### Scenario: Author edits a post in the Block editor

- **WHEN** a post is opened in the Block editor
- **THEN** the Location box appears in the document sidebar

#### Scenario: Author edits a post in the Classic editor

- **WHEN** a post is opened in the Classic editor
- **THEN** the Location box appears in the sidebar with the saved-locations dropdown

### Requirement: Saving a post persists its location

On `save_post`, a submitted location MUST be resolved to coordinates, stored as a location record, and linked to the post through the `_geopress_id` post meta key.

#### Scenario: Author saves a new address

- **WHEN** a post is saved with an address and no prior coordinates
- **THEN** the address is geocoded
- **AND** a location record is written with the coordinates
- **AND** `_geopress_id` on the post references that record

#### Scenario: Author saves an unchanged location

- **WHEN** a post is saved and the geometry field already holds a coordinate pair
- **THEN** the stored coordinates are reused
- **AND** the geocoding service is not called again

### Requirement: Coordinates are stored space-separated

A location's coordinates MUST be persisted as `"lat lon"` separated by a space. Every reader of the field MUST accept both that form and a comma-separated form, so that a value written by an older version or typed by hand still parses.

#### Scenario: Coordinates are read back for editing

- **WHEN** a stored coordinate string of the form `51.5 -0.1` is read
- **THEN** it parses into latitude `51.5` and longitude `-0.1`

### Requirement: Location can be declared in the post body

An author MUST be able to set a post's location from the content itself, so a location can be authored inline without using the sidebar.

#### Scenario: Body contains a location tag

- **WHEN** the post content contains `GEOPRESS_LOCATION(<address>)`
- **THEN** that address is used as the post's location
- **AND** the tag is removed from the rendered output

#### Scenario: Body contains geo machine tags

- **WHEN** the post content contains `geo:lat=` and `geo:lon=` values
- **THEN** those values are used as the post's coordinates

### Requirement: Saving is guarded

The save handler MUST refuse to act unless the request is a genuine, authorised edit, and MUST leave existing data untouched when it refuses.

#### Scenario: Request is an autosave

- **WHEN** `DOING_AUTOSAVE` is set
- **THEN** no location is written

#### Scenario: Nonce is missing or invalid

- **WHEN** the `geopress_nonce` field is absent or fails verification
- **THEN** no location is written

#### Scenario: User lacks permission

- **WHEN** the current user cannot `edit_post` the target post
- **THEN** no location is written

#### Scenario: No location was submitted

- **WHEN** no address is present in the request or the post body
- **THEN** no location is written

### Requirement: Submitted values are sanitized

All location fields arriving from the request MUST be unslashed and sanitized before use or storage. The nonce MUST be unslashed but MUST NOT be passed through a sanitizer that can alter its value.

#### Scenario: Address contains markup

- **WHEN** an address is submitted containing HTML tags
- **THEN** the stored address has the markup removed

### Requirement: Deleting a location clears its references

Removing a location record MUST also remove the post meta that points at it, so posts are never left referencing a location that no longer exists.

#### Scenario: A referenced location is deleted

- **WHEN** a location record is deleted
- **THEN** the record is removed from the locations table
- **AND** every `_geopress_id` meta value referencing it is removed
