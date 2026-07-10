# Admin UI

This document describes the currently implemented Civic administration experience.

The current admin UI is built on WordPress admin screens. It is simplified and customized for Civic workflows, but it is not a separate frontend administration application.

## Civic Manager Admin

The project has two related admin customization layers:

1. Plugin restricted Civic admin layer
   - Adds `body.civic-admin`.
   - Adds `body.civic-admin-page` on Civic Platform admin pages.
   - Enqueues `assets/css/civic-admin.css`.
   - Applies restricted-user cleanup to restricted Civic users detected by `DashboardAdmin`.
   - Applies the fixed branded Civic header to Civic Platform admin pages only.

2. Theme Civic Manager admin skin
   - Adds `body.civic-manager-admin`.
   - Enqueues `wp-content/themes/civic/assets/css/civic-manager-admin.css`.
   - Applies to users whose role array includes `civic_manager`.

Both layers currently affect wp-admin presentation. The plugin layer focuses on Civic dashboard cleanup and restricted-user experience. The theme layer visually skins the broader wp-admin workspace.

The WordPress login screen is branded by the plugin through `DashboardAdmin` login hooks and `assets/css/civic-login.css`. The preferred administrator login entry point is `/civic-admin`, which redirects through the normal WordPress login flow and then to the Civic Dashboard when appropriate.

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
| System | `manage_civic_reps` | `civic-system` |
| Documentation | `manage_civic_reps` | `civic-system` |
| Security Settings | `manage_civic_settings` | `civic-security-settings` |
| Account | `manage_civic_reps` | `civic-account` |
| Representations | `manage_civic_reps` | `civic-platform` |
| Consultations | `manage_civic_threads` | `civic-threads` |
| Events | `manage_civic_events` | `civic-events` |
| Schedules | `manage_civic_schedules` | `civic-schedules` |
| Contacts | `manage_civic_contacts` | `civic-contacts` |

Activities are registered as a submenu under the representations/Civic Platform area:

- `civic-activities`

Security Settings are registered under the System menu. The Documentation page is the System menu landing page and provides a lightweight Civic Manager user manual.

Several internal add/edit/detail pages are registered as submenu pages and then hidden from the visible menu using `AdminMenuHelper` or module-specific hide methods. They remain accessible by direct URL.

## Custom Admin Styling

Plugin stylesheet:

```text
assets/css/civic-admin.css
```

Responsibilities:

- fixed branded Civic Platform header on Civic admin pages
- Civic Platform logo, title, dynamic plugin version, Documentation action, and "Visit Website" action
- Civic Manager documentation card layout
- dashboard card layout
- recent section layout
- notice cleanup
- reduced WordPress admin noise for restricted Civic users

Login stylesheet:

```text
assets/css/civic-login.css
```

Responsibilities:

- replaces the default WordPress login logo with Civic Platform branding
- applies Civic visual identity to the WordPress authentication screen
- preserves WordPress authentication behavior

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

## Login and Admin Branding

Implemented Version 1.0 branding behavior:

- WordPress login branding is replaced with Civic Platform branding.
- `/civic-admin` is supported as the preferred admin login entry point.
- `/civic-admin` preserves WordPress authentication by redirecting to `wp-login.php` with the Civic Dashboard as the redirect target.
- Logged-in Civic users visiting `/civic-admin` are sent to the Civic Dashboard.
- Logged-in non-Civic users are sent to the normal WordPress admin area.
- Civic admin pages display a fixed branded header with the Civic Platform logo, title, dynamic plugin version, Documentation action, and "Visit Website" action.
- The "Visit Website" action opens the public site in a new browser tab.
- The branded header is scoped to Civic admin page slugs and does not appear on unrelated WordPress admin screens.

Final admin UI polish remains a release-readiness task.

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
