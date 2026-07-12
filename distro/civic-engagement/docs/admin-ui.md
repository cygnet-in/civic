# Admin UI

This document describes the currently implemented Civic administration experience.

The current admin UI is built on WordPress admin screens. It is simplified and customized for Civic workflows, but it is not a separate frontend administration application.

## Civic Manager Admin

The project has two related admin customization layers:

1. Plugin Civic admin layer
   - Adds `body.civic-admin`.
   - Adds `body.civic-admin-page` on Civic Platform admin pages.
   - Adds `body.civic-manager-admin` on Civic Platform admin pages so the theme admin skin applies to both Administrators and Civic Managers.
   - Enqueues plugin `assets/css/civic-admin.css` only on Civic Platform admin pages.
   - Enqueues theme `wp-content/themes/civic/assets/css/civic-manager-admin.css` only on Civic Platform admin pages.
   - Applies restricted-user cleanup to restricted Civic users detected by `DashboardAdmin`.
   - Applies the fixed branded Civic header to Civic Platform admin pages only.

2. Theme Civic Manager admin skin
   - Adds `body.civic-manager-admin` only on Civic Platform admin pages.
   - Provides `wp-content/themes/civic/assets/css/civic-manager-admin.css`.
   - The stylesheet is loaded by the plugin on Civic Platform admin pages so the Civic Platform behaves as a standalone administration interface for both Administrators and Civic Managers.

Both layers affect only Civic Platform admin pages. Normal WordPress admin pages do not receive the Civic admin stylesheets from the plugin.

The WordPress login screen is branded by the plugin through `DashboardAdmin` login hooks and `assets/css/civic-login.css`. The preferred Civic administration entry point is `/civic-admin`, which redirects through the normal WordPress login flow and then to the Civic Dashboard when appropriate. Civic Managers who access `/wp-admin` are redirected to the Civic Dashboard. WordPress Administrators remain on the normal WordPress Dashboard and use the top-level Civic Admin menu item to open Civic Platform administration.

WordPress Admin and Civic Admin are treated as distinct visible navigation contexts. In the standard WordPress admin interface, Administrators see only a single top-level Civic Admin entry for entering the Civic Platform; Civic operational menus are hidden. In the Civic Platform interface, standard WordPress operational menus are hidden so the visible menu contains Civic Platform sections only.

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
| Civic Admin / Dashboard | `manage_civic_reps` | `civic-dashboard` |
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

Security Settings are registered under the System menu. The page also contains the public Search Results Page setting used by `[civic_search_form]`. The Documentation page is the System menu landing page and provides a lightweight Civic Manager user manual.

Several internal add/edit/detail pages are registered as submenu pages and then hidden from the visible menu using `AdminMenuHelper` or module-specific hide methods. They remain accessible by direct URL.

`DashboardAdmin` performs the final context-based menu cleanup after module menu registration:

- Standard WordPress admin context: remove top-level `civic-` menus except `civic-dashboard`.
- Civic admin context: remove top-level menus whose slug does not start with `civic-`.
- Permissions and page callbacks are unchanged; only visible navigation changes.

## Custom Admin Styling

Plugin stylesheet:

```text
assets/css/civic-admin.css
```

Responsibilities:

- fixed branded Civic Platform header on Civic admin pages
- Civic Platform logo, title, dynamic plugin version, Documentation action, Administrator-only "WP Admin" action, and "Visit Website" action
- Civic Manager documentation card layout
- dashboard card layout
- recent section layout
- notice cleanup
- reduced WordPress admin noise for restricted Civic users
- loaded only when the current admin page slug starts with `civic-`

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
- loaded only when the current admin page slug starts with `civic-`

## Login and Admin Branding

Implemented Version 1.0 branding behavior:

- WordPress login branding is replaced with Civic Platform branding.
- `/civic-admin` is supported as the preferred admin login entry point.
- `/civic-admin` preserves WordPress authentication by redirecting to `wp-login.php` with the Civic Dashboard as the redirect target.
- Logged-in Civic users visiting `/civic-admin` are sent to the Civic Dashboard.
- Logged-in non-Civic users are sent to the normal WordPress admin area.
- Civic Managers visiting `/wp-admin` are redirected to the Civic Dashboard.
- Administrators visiting `/wp-admin` remain on the normal WordPress Dashboard.
- Administrators have a top-level Civic Admin menu item that opens the Civic Dashboard.
- Standard WordPress admin pages display only the single top-level Civic Admin entry from the Civic Platform.
- Civic admin pages display Civic Platform menus and hide standard WordPress operational menus.
- Civic admin pages display a fixed branded header with the Civic Platform logo, title, dynamic plugin version, Documentation action, Administrator-only "WP Admin" action, and "Visit Website" action.
- The "WP Admin" action is hidden from Civic Managers.
- The "Visit Website" action opens the public site in a new browser tab.
- The branded header is scoped to Civic admin page slugs and does not appear on unrelated WordPress admin screens.
- The standard WordPress admin bar is hidden only on Civic admin pages, and the default WordPress toolbar top spacing is removed there.
- Civic admin stylesheets are scoped by current page slug rather than by user role.

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
- XLSX export through the shared export framework
- detail view
- status/internal comment update
- optional uploaded image display

Consultations:

- listing
- create/edit/detail
- custom fields
- response listing
- response detail/moderation
- XLSX export for consultations and consultation responses through the shared export framework
- media panel
- slug and short URL fields

Events:

- listing
- create/edit
- custom registration fields
- registration listing/detail
- XLSX export for events and event registrations through the shared export framework
- media panel
- slug and short URL fields

Schedules:

- listing
- create/edit
- archive/visibility/status fields
- recent update and priority
- schedule notes
- XLSX export through the shared export framework
- media panel
- slug and short URL fields

Contacts:

- listing
- search
- consent filtering
- XLSX export through the shared export framework

Activities:

- contact-centric activity listing

Account:

- password change
- logout menu entry
- profile menu cleanup for Civic users
