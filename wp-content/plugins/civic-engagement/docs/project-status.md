# Current Development Stage

Current milestone:
Event module completion.

Completed:

- Representations
- Consultations
- Consultation Responses
- Consultation Custom Fields
- Electoral Areas
- Event Administration
- Public Event Listing
- Public Event Detail
- Event Registration Submission
- Event Registration Administration

Next milestone:

- Event Custom Fields

Deferred:

- slug routing
- response pagination
- registration pagination
- frontend administration

---

# Activities

## Status

Operational

## Purpose

Tracks contact-related activities.

Examples:

* representation submission
* consultation response
* event registration

## Important Rule

Activities are contact-centric.

Activities are not intended as a generic system logging framework.

---

# Electoral Areas

## Status

Operational

## Table

`wp_civic_electoral_areas`

## Current Approach

* manually managed reference data
* repository access
* no admin CRUD

## Implemented

* ElectoralAreaRepository
* active area lookup

## Integrated Into

* representations
* consultation responses
* event registrations

---

# Event Module

## Status

Operational

## Admin Features

### Event Listing

- search
- pagination
- view
- edit
- registrations

### Event Create/Edit

Fields:

- title
- slug
- summary
- description
- location
- is_public
- registration_enabled
- status
- start_date
- end_date

### Registration Listing

Supports:

- search
- pagination
- event filtering
- registration count
- detail view

### Registration Detail

Displays:

- event information
- registration date
- submitted contact snapshots
- custom registration field values

## Public Features

### Event Listing

Shortcode:

[civic_events]

Displays:

- title
- summary
- location
- date
- registration status
- read more link

### Event Detail

Shortcode:

[civic_event_detail]

Displays:

- title
- summary
- description
- location
- date information
- registration status

### Event Registration

Status: Operational

Supported Fields:

- Name
- Email
- Phone
- Address
- Eircode
- Electoral Area

Features:

- no login required
- contact matching
- contact updates
- snapshot preservation
- nonce validation

Stores:

- contact_id
- name_snapshot
- email_snapshot
- phone_snapshot
- address_snapshot
- eircode_snapshot
- electoral_area_snapshot
- registration_data

## Not Yet Implemented

- Event Custom Fields
- Registration custom field rendering
- Registration export
- Attendance tracking
- Registration limits
- Slug routing