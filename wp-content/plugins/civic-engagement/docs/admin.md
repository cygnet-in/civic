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
* Security Settings
* System / Documentation
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
* `manage_civic_settings`

## Dashboard

The Civic Dashboard is implemented by `DashboardModule`, `DashboardAdmin`, and `DashboardPage`.

It displays:

* count cards for major civic records;
* recent representations;
* recent consultation responses;
* upcoming schedules;
* latest events.

## Security Settings

Security Settings are implemented inside the Civic Manager administration area, not as a standard WordPress Settings API page.

The page is registered under the System menu:

* `admin.php?page=civic-security-settings`

It is implemented by `SecuritySettingsPage` and stores shared public form security configuration through `CivicSettingsService`.

Current settings:

* Enable CAPTCHA
* Cloudflare Turnstile Site Key
* Cloudflare Turnstile Secret Key

Security Settings are grouped under the System admin menu.

## System Documentation

The System menu includes a Documentation page:

* `admin.php?page=civic-system`

It is implemented by `DocumentationPage` and provides a lightweight Civic Manager user manual covering Dashboard, Representations, Consultations, Events, Schedules, Contacts, Activities, System, Security and Account workflows.

## Login and Branding

Version 1.0 uses the normal WordPress authentication system with Civic Platform branding applied by `DashboardAdmin`.

Implemented behavior:

* the WordPress login logo is replaced with Civic Platform branding
* `assets/css/civic-login.css` styles the login screen
* `/civic-admin` is the preferred administrator login entry point
* `/civic-admin` redirects through WordPress login with the Civic Dashboard as the redirect target
* logged-in Civic users visiting `/civic-admin` are sent to the Civic Dashboard
* logged-in non-Civic users visiting `/civic-admin` are sent to normal WordPress admin
* Civic admin pages display a fixed branded Civic Platform header with logo, title, plugin version, Documentation action and "Visit Website" action
* the branded header is scoped to Civic Platform admin pages only
* the "Visit Website" action opens the public site in a new browser tab

## Main Admin Sections

Current admin sections:

* Dashboard
* System
* Documentation
* Security Settings
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

* final admin UI polish

## Future Possibilities

* frontend administration
* advanced dashboards
* analytics
* reporting widgets
* workflow management
