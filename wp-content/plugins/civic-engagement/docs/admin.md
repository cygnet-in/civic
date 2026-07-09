# Admin Interface

## Current Implementation

The current operational administration interface is implemented inside WordPress admin.

It is not a separate frontend admin application.

Implemented admin areas include:

* Civic Dashboard
* Representations
* Consultations
* Consultation Fields
* Consultation Responses
* Events
* Event Fields
* Event Registrations
* Schedules
* Contacts
* Activities
* Account / Change Password

## Current Simplifications

* Lightweight operational wp-admin interface.
* No enterprise dashboard system.
* No separate frontend admin application.
* No advanced analytics dashboards.
* No workflow automation console.

## Roles

### Administrator

Administrators retain full WordPress and Civic access.

### Civic Manager

The plugin registers a `civic_manager` role with Civic operational capabilities.

Current capabilities include:

* `manage_civic_reps`
* `manage_civic_threads`
* `manage_civic_events`
* `manage_civic_schedules`
* `view_civic_activities`
* `manage_civic_contacts`

## Dashboard

The Civic Dashboard is implemented by `DashboardModule`, `DashboardAdmin`, and `DashboardPage`.

It displays:

* count cards for major civic records;
* recent representations;
* recent consultation responses;
* upcoming schedules;
* latest events.

## Main Admin Sections

Current admin sections:

* Dashboard
* Account
* Representations
* Consultations
* Events
* Schedules
* Contacts
* Activities

Communication is not currently implemented as an admin section.

## Important Rules

* Use pagination for large lists.
* Use lightweight filtering/search.
* Keep admin workflows simple and operational.
* Validate capabilities and nonces on protected actions.
* Keep repositories, services, validation rules and permissions independent of wp-admin rendering so they can be reused by a future frontend admin if one is built.

## Version 1.0 Pending Admin Work

Pending release-readiness items:

* custom login page
* replace WordPress login branding
* support `/civic-admin` login URL
* add "Visit Website" link in admin
* add branded admin header
* final admin UI polish

## Future Possibilities

* frontend administration
* advanced dashboards
* analytics
* reporting widgets
* workflow management
