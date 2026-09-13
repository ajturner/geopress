# location-management

## Purpose

Give administrators a place to curate the site's stored locations directly — adding, editing and removing them — independently of any post that references them.

## Requirements

### Requirement: Locations are managed from an admin page

A Locations page MUST exist under the GeoPress admin menu, restricted to users who can `manage_options`, listing every stored location.

#### Scenario: Administrator opens the Locations page

- **WHEN** a user with `manage_options` opens the Locations page
- **THEN** all stored locations are listed with their name, address, coordinates and visibility

#### Scenario: A user without the capability requests the page

- **WHEN** a user lacking `manage_options` requests the page
- **THEN** access is refused

### Requirement: Adding a location verifies it by geocoding

A new location MUST be geocoded at the point of adding, and the resolved coordinates stored, so an unresolvable address is caught immediately rather than silently saved without a position.

#### Scenario: Address resolves

- **WHEN** an administrator adds a location with a resolvable address
- **THEN** the address is geocoded
- **AND** the location is stored with the resolved coordinates
- **AND** a confirmation reporting the coordinates is shown

#### Scenario: Address does not resolve

- **WHEN** the submitted address cannot be geocoded
- **THEN** no location is stored
- **AND** an error explains that the address could not be geocoded and that `[lat, lon]` may be used instead

#### Scenario: Address is empty

- **WHEN** the address field is empty
- **THEN** no location is stored
- **AND** an error states that an address is required

### Requirement: Editing re-geocodes only a changed address

Saving edits MUST preserve existing coordinates when the address has not changed, and MUST re-resolve them when it has. This avoids both needless service calls and stale coordinates.

#### Scenario: Address is edited

- **WHEN** a location's address is changed and saved
- **THEN** the new address is geocoded and the coordinates updated

#### Scenario: Only the name or visibility is edited

- **WHEN** a location's address is unchanged
- **THEN** its existing coordinates are retained

### Requirement: Deletion is confirmed and cleans up

Deleting a location MUST require confirmation, and MUST remove both the record and any post references to it.

#### Scenario: Administrator deletes a location

- **WHEN** a delete is submitted and confirmed
- **THEN** the location record is removed
- **AND** post meta referencing it is removed

### Requirement: The page shows the locations on a map

The Locations page MUST render a map of all stored locations using the site's configured provider and dimensions, so an administrator can see at a glance whether anything geocoded to the wrong place.

#### Scenario: Locations exist

- **WHEN** the Locations page is rendered with stored locations
- **THEN** a display-only map below the table shows a marker for each location
- **AND** it uses the configured map provider and dimensions

#### Scenario: The map coexists with other maps

- **WHEN** the admin locations map is rendered
- **THEN** it uses its own container and its own instance variable, so it does not collide with any other map on the page

### Requirement: Admin forms are nonce-protected

Every state-changing submission on the page MUST carry and verify a nonce, and MUST sanitize its inputs.

#### Scenario: A form is submitted without a valid nonce

- **WHEN** an add, edit or delete submission fails nonce verification
- **THEN** no change is made

### Requirement: Editor scripts load on GeoPress admin pages

Scripts needed by GeoPress admin screens MUST be enqueued on those screens, not only on the post editor, so the Locations and Maps pages work.

#### Scenario: An administrator opens a GeoPress admin page

- **WHEN** the current admin page belongs to GeoPress
- **THEN** the GeoPress admin scripts are enqueued
