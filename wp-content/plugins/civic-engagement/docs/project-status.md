# Project Status

## Project

**Civic Engagement Platform (WordPress Plugin)**

---

# Development Philosophy

The project prioritizes:

1. Complete business workflows first.
2. Technical refinements later.
3. Repository + Service architecture.
4. Immutable citizen submissions.
5. Frontend administration in the future.
6. Minimal dependency on wp-admin implementation details.

Current wp-admin screens are considered operational implementations used to validate workflows and business rules.

---

# Core Architecture

## Implemented

* Repository pattern
* Service layer
* Capability framework
* Activity tracking
* Contact management
* Snapshot preservation
* Date helper utilities

## Roles

### civic_manager

Primary operational role for the platform.

Current permissions include:

* manage representations
* manage consultations
* moderate responses

---

# Contacts

## Status

Operational

## Features

* Contact repository
* Contact service
* Email-based contact matching
* Contact updates on subsequent submissions
* Snapshot preservation on submissions

## Important Rule

Contact records may change over time.

Submission records must preserve immutable snapshots.

---

# Activities

## Status

Operational

## Purpose

Tracks contact-related activities.

Examples:

* representation submission
* consultation response
* event registration (planned)

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

## Planned

* event registrations

---

# Representation Module

## Status

Operational

## Public Features

Representation submission form.

### Fields

* Name
* Email
* Phone
* Address
* Eircode
* Electoral Area
* Subject
* Message

## Admin Features

### Listing

* pagination
* search
* detail view

### Detail View

Displays:

* representation information
* submitted contact snapshots
* activity history

## Important Rule

Representation detail pages use snapshot values.

They do not use current contact values.

### Future Enhancement

Show current contact record separately.

---

# Consultation (Thread) Module

## Status

Operational

## Admin Features

### Consultation Listing

Supports:

* search
* pagination
* view
* edit
* fields
* responses

### Consultation Create/Edit

Fields:

* title
* slug
* summary
* description
* response_enabled
* status
* start_date
* end_date

## Public Features

### Consultation Listing

Displays:

* title
* summary
* created date
* read more link

### Consultation Detail

Displays:

* title
* summary
* description
* created date

Actions:

* Respond to this Consultation
* View Responses (X)

Anchor-based navigation implemented.

---

# Consultation Responses

## Status

Operational

## Public Response Form

### Fields

* Name
* Email
* Phone
* Address
* Eircode
* Electoral Area
* Response Text
* Custom Fields

## Features

* no login required
* contact matching
* snapshot preservation
* moderation workflow

## Admin Features

### Response Listing

Supports:

* search
* pagination
* consultation filtering

### Response Detail

Displays:

* consultation reference
* contact snapshots
* response content
* custom field values
* visibility state

### Moderation

Supported:

* Show Publicly
* Hide Publicly

Not Supported:

* editing citizen content
* deleting citizen content

## Important Rule

Citizen responses are immutable.

Moderation controls visibility only.

---

# Consultation Custom Fields

## Status

Operational

## Admin Features

Consultation-specific fields.

Supported field types:

* text
* textarea
* select

## Storage

Stored in `response_data`.

Example:

```json
{
  "response_text": "...",
  "custom_fields": {
    "field_key": "value"
  }
}
```

## Rendering

### Frontend

* field labels displayed
* field values displayed
* empty values hidden

### Admin

* field labels displayed
* field values displayed

## Naming Convention

Standard fields:

```text
civic_thread_response[name]
civic_thread_response[email]
```

Custom fields:

```text
civic_thread_response[custom_fields][field_key]
```

---

# Slug Support

## Status

Partially Implemented

### Implemented

* slug field
* slug generation
* slug storage

### Not Yet Implemented

* slug routing
* rewrite rules
* root-level public URLs

Client examples:

```text
/housing
/transport
/dub
```

Deferred until major workflows are complete.

---

# Event Module

## Status

Not Started

## Database Preparation

Event registration table updated to support:

* electoral_area_id

## Planned Workflow

### Admin

* create event
* edit event
* registration management

### Public

* event listing
* event detail
* event registration

---

# Frontend Administration

## Status

Deferred

## Long-Term Objective

* frontend operational dashboard
* frontend consultation management
* frontend moderation tools

Current wp-admin implementation is being used to stabilise workflows.

---

# Deferred Enhancements

## UX

* public response styling
* moderation badges
* success message improvements
* current contact display on representation details

## Operational

* response pagination
* export features
* advanced filtering

## Technical

* slug routing
* rewrite rules
* permalink integration
* performance optimisation

---

# Current Priority

1. Commit latest consultation enhancements.
2. Start Event Module.
3. Complete event registration workflow.
4. Revisit slug routing.
5. Revisit frontend administration.

---

# Important Architectural Rules

* Repository pattern throughout.
* Service layer for workflows.
* No direct SQL in admin/frontend screens.
* Preserve immutable submission snapshots.
* Use namespaced request fields.
* Use shared reference data repositories.
* Prefer workflow completion over technical refinement.
