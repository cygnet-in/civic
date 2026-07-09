# Admin UI

This document describes the currently implemented Civic administration experience.

The current admin UI is built on WordPress admin screens. It is simplified and customized for Civic workflows, but it is not a separate frontend administration application.

## Civic Manager Admin

The project has two related admin customization layers:

1. Plugin restricted Civic admin layer
   - Adds `body.civic-admin`.
   - Enqueues `assets/css/civic-admin.css`.
   - Applies to restricted Civic users detected by `DashboardAdmin`.

2. Theme Civic Manager admin skin
   - Adds `body.civic-manager-admin`.
   - Enqueues `wp-content/themes/civic/assets/css/civic-manager-admin.css`.
   - Applies to users whose role array includes `civic_manager`.

Both layers currently affect wp-admin presentation. The plugin layer focuses on Civic dashboard cleanup and restricted-user experience. The theme layer visually skins the broader wp-admin workspace.

## Roles and Capabilities

Capabilities are registered centrally in:

```text
app/Core/Capabilities.php
```

Role:

- `civic_manager`

Capabilities:

- `manage_civic_reps`
- `manage_civic_threads`
- `manage_civic_events`
- `manage_civic_schedules`
- `view_civic_activities`
- `manage_civic_contacts`

Administrators are also granted these capabilities.

## Dashboard

Dashboard owner:

```text
app/Modules/Dashboard
```

Admin page:

```text
admin.php?page=civic-dashboard
```

The dashboard renders:

- welcome text
- count cards for representations, consultations, responses, events, registrations, schedules, and contacts
- recent activity sections for latest representations, latest consultation responses, upcoming schedules, and latest events

Dashboard class names:

- `civic-dashboard`
- `civic-dashboard__welcome`
- `civic-dashboard__cards`
- `civic-dashboard__card`
- `civic-dashboard__card-label`
- `civic-dashboard__count`
- `civic-dashboard__card-link`
- `civic-dashboard__recent-heading`
- `civic-dashboard__recent-grid`
- `civic-dashboard__recent-section`

## Navigation

The plugin registers module-oriented admin menus:

| Menu | Capability | Main slug |
| --- | --- | --- |
| Dashboard | `manage_civic_reps` | `civic-dashboard` |
| Account | `manage_civic_reps` | `civic-account` |
| Representations | `manage_civic_reps` | `civic-platform` |
| Consultations | `manage_civic_threads` | `civic-threads` |
| Events | `manage_civic_events` | `civic-events` |
| Schedules | `manage_civic_schedules` | `civic-schedules` |
| Contacts | `manage_civic_contacts` | `civic-contacts` |

Activities are registered as a submenu under the representations/Civic Platform area:

- `civic-activities`

Several internal add/edit/detail pages are registered as submenu pages and then hidden from the visible menu using `AdminMenuHelper` or module-specific hide methods. They remain accessible by direct URL.

## Custom Admin Styling

Plugin stylesheet:

```text
assets/css/civic-admin.css
```

Responsibilities:

- dashboard card layout
- recent section layout
- notice cleanup
- reduced WordPress admin noise for restricted Civic users

Theme stylesheet:

```text
wp-content/themes/civic/assets/css/civic-manager-admin.css
```

Responsibilities:

- admin bar visual styling
- left menu visual styling
- admin content spacing
- table, form, button, notice, card, and postbox styling
- footer cleanup

## Release Readiness Admin Work

The following admin-related items remain pending for Version 1.0:

- custom login page
- replacement of WordPress login branding
- `/civic-admin` login URL support
- "Visit Website" link in admin
- branded admin header
- final admin UI polish

These are not implemented in the current source.

## Admin Page Structure

Admin pages are OOP classes under module `Admin` folders.

Common patterns:

- capability check at render/action level
- request validation
- nonce validation for state-changing actions
- repository/service delegation
- `wrap` containers
- WordPress `widefat` tables
- search and filter controls
- pagination
- hidden direct-access detail/edit pages

Examples:

- `RepsListPage`
- `RepDetailPage`
- `ThreadsListPage`
- `ThreadCreatePage`
- `ThreadEditPage`
- `ThreadDetailPage`
- `ThreadResponsesListPage`
- `ThreadResponseDetailPage`
- `EventsListPage`
- `EventEditPage`
- `EventRegistrationsListPage`
- `EventRegistrationDetailPage`
- `SchedulesListPage`
- `ScheduleEditPage`
- `ContactsListPage`
- `ActivitiesListPage`

## Reusable Admin Components

Currently reusable admin pieces include:

- `DateHelper` for formatting dates.
- `StatusLabelHelper` for status labels.
- `AdminMenuHelper` for hiding internal submenu pages.
- `MediaAdminPanel` for image upload/caption/delete controls on consultations, events, and schedules.
- `BaseRepository` pagination/filter/search helpers used by repositories feeding admin lists.

## Module Admin Features

Representations:

- listing
- search
- pagination
- detail view
- status/internal comment update
- optional uploaded image display

Consultations:

- listing
- create/edit/detail
- custom fields
- response listing
- response detail/moderation
- media panel
- slug and short URL fields

Events:

- listing
- create/edit
- custom registration fields
- registration listing/detail
- media panel
- slug and short URL fields

Schedules:

- listing
- create/edit
- archive/visibility/status fields
- recent update and priority
- schedule notes
- media panel
- slug and short URL fields

Contacts:

- listing
- search
- consent filtering
- CSV export

Activities:

- contact-centric activity listing

Account:

- password change
- logout menu entry
- profile menu cleanup for Civic users
