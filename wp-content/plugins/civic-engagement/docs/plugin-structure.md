# Plugin Structure

This document describes the current implementation of the Civic Engagement plugin.

Plugin root:

```text
wp-content/plugins/civic-engagement
```

Main plugin file:

```text
civic-engagement.php
```

## Bootstrap Process

`civic-engagement.php`:

- defines plugin constants
  - `CIVIC_ENGAGEMENT_PLUGIN_FILE`
  - `CIVIC_ENGAGEMENT_PLUGIN_PATH`
  - `CIVIC_ENGAGEMENT_PLUGIN_URL`
  - `CIVIC_ENGAGEMENT_VERSION`
- registers a PSR-like autoloader for the `CivicPlatform\` namespace
- registers activation and deactivation hooks
- registers modules on `plugins_loaded`
- enqueues public frontend CSS on `wp_enqueue_scripts`

Activation:

- registers capabilities
- registers canonical rewrite rules
- flushes rewrite rules

Deactivation:

- flushes rewrite rules

Registered modules:

- Dashboard
- Account
- Reps
- Activities
- Threads
- Events
- Schedules
- Canonical Slug Router
- Contacts
- Capabilities

## Top-Level Structure

```text
app/
    Core/
    Helpers/
    Repositories/
    Services/
    Modules/

assets/
    css/
```

## Module Overview

### Dashboard

Purpose:

- Civic operational dashboard in wp-admin.
- Public statistics shortcode.
- Login and Civic admin branding hooks.

Key classes:

- `DashboardModule`
- `DashboardAdmin`
- `DashboardPage`
- `DocumentationPage`
- `SecuritySettingsPage`
- `PublicStatisticsService`
- `StatisticsShortcode`

Admin page:

- `admin.php?page=civic-dashboard`
- `admin.php?page=civic-system`
- `admin.php?page=civic-security-settings`

Admin/login branding:

- `DashboardAdmin` brands the WordPress login screen using `assets/css/civic-login.css`.
- `/civic-admin` redirects into the normal WordPress authentication flow with the Civic Dashboard as the target.
- Civic admin pages receive a fixed branded header with the Civic Platform logo, dynamic plugin version, Documentation action, and "Visit Website" action.
- The System admin group contains Documentation and Security.
- `DocumentationPage` renders a lightweight Civic Manager user manual.

Public shortcode:

- `[civic_statistics]`

### Shared Services

Shared platform services live under `app/Services`.

Export-related services:

- `ExportManager`
  - coordinates export providers and browser download responses
  - accepts module-provided rows, column definitions, filenames, and formats
- `XlsxExporter`
  - primary Version 1.0 export provider
  - generates native `.xlsx` workbooks
  - preserves Unicode using UTF-8 OpenXML worksheet content
- `ExporterInterface`
  - contract for future export providers such as CSV or PDF

Security-related services:

- `CivicSettingsService`
  - stores shared Civic security settings in WordPress options
  - currently manages CAPTCHA enabled state and Cloudflare Turnstile keys
- `CaptchaService`
  - renders a reusable Turnstile widget for Civic frontend forms
  - verifies Turnstile tokens server-side
  - exposes a validation API that public form controllers can call before processing submissions

### Account

Purpose:

- Simplified account menu for Civic users.
- Password change page.
- Logout shortcut.

Key classes:

- `AccountModule`
- `AccountAdmin`
- `ChangePasswordPage`

Admin menu:

- `civic-account`

### Reps

Purpose:

- Public representation submissions.
- Representation admin listing and detail.
- Optional representation image upload.

Key classes:

- `RepsModule`
- `RepFormController`
- `RepDetailShortcode`
- `RepsShortcodes`
- `RepRepository`
- `RepService`
- `RepsAdmin`
- `RepsListPage`
- `RepDetailPage`

Public shortcodes:

- `[civic_rep_form]`
- `[civic_rep_detail]`

Admin pages:

- `civic-platform`
- `civic-rep-view`

Admin export:

- filtered representation list export uses `ExportManager` and `XlsxExporter`

### Activities

Purpose:

- Contact-centric activity history.

Key classes:

- `ActivitiesModule`
- `ActivitiesAdmin`
- `ActivitiesListPage`
- `ActivityRepository`
- `ActivityService`

Admin page:

- `civic-activities`

### Threads

Purpose:

- Public consultations.
- Consultation custom fields.
- Public response submission.
- Response moderation/admin display.

Key classes:

- `ThreadsModule`
- `ThreadsListShortcode`
- `ThreadDetailShortcode`
- `ThreadResponseForm`
- `ThreadResponseService`
- `ThreadRepository`
- `ThreadFieldRepository`
- `ThreadResponseRepository`
- `ThreadsAdmin`
- `ThreadCreatePage`
- `ThreadEditPage`
- `ThreadDetailPage`
- `ThreadFieldsListPage`
- `ThreadFieldEditPage`
- `ThreadResponsesListPage`
- `ThreadResponseDetailPage`
- `LatestConsultationsWidget`

Public shortcodes:

- `[civic_threads]`
- `[civic_thread_detail]`

Widget:

- `Civic: Latest Consultations`

Admin pages:

- `civic-threads`
- `civic-thread-create`
- `civic-thread-view`
- `civic-thread-edit`
- `civic-thread-fields`
- `civic-thread-field-edit`
- `civic-thread-responses`
- `civic-thread-response-view`

Admin exports:

- filtered consultation list export uses `ExportManager` and `XlsxExporter`
- filtered consultation response export uses `ExportManager` and `XlsxExporter`

### Events

Purpose:

- Public events.
- Event custom registration fields.
- Event registrations.

Key classes:

- `EventsModule`
- `EventListShortcode`
- `EventDetailShortcode`
- `EventRegistrationForm`
- `EventRegistrationService`
- `EventRepository`
- `EventFieldRepository`
- `EventRegistrationRepository`
- `EventsAdmin`
- `EventsListPage`
- `EventEditPage`
- `EventFieldsListPage`
- `EventFieldEditPage`
- `EventRegistrationsListPage`
- `EventRegistrationDetailPage`
- `LatestEventsWidget`

Public shortcodes:

- `[civic_events]`
- `[civic_event_detail]`

Widget:

- `Civic: Latest Events`

Admin pages:

- `civic-events`
- `civic-event-edit`
- `civic-event-fields`
- `civic-event-field-edit`
- `civic-event-registrations`
- `civic-event-registration-view`

Admin exports:

- filtered event list export uses `ExportManager` and `XlsxExporter`
- filtered event registration export uses `ExportManager` and `XlsxExporter`

### Schedules

Purpose:

- Public/private schedules and activity entries.
- Schedule notes.
- Public schedule list/detail output.

Key classes:

- `SchedulesModule`
- `ScheduleListShortcode`
- `ScheduleDetailShortcode`
- `UpcomingSchedulesWidget`
- `ScheduleRepository`
- `ScheduleNoteRepository`
- `ScheduleService`
- `SchedulesAdmin`
- `SchedulesListPage`
- `ScheduleEditPage`

Public shortcodes:

- `[civic_schedules]`
- `[civic_schedule_detail]`

Widget:

- `Civic: Upcoming Schedules`

Admin pages:

- `civic-schedules`
- `civic-schedule-edit`

Admin export:

- filtered schedule list export uses `ExportManager` and `XlsxExporter`

### Users / Contacts

Purpose:

- Contact management.
- Email-based identity.
- Consent storage and export.

Key classes:

- `ContactsModule`
- `ContactsAdmin`
- `ContactsListPage`
- `ContactRepository`
- `ContactService`
- `ExportManager`
- `XlsxExporter`

Admin page:

- `civic-contacts`

Admin exports:

- filtered Contacts exports use the shared export framework
- filtered Representations, Consultations, Consultation Responses, Events, Event Registrations, and Schedules exports use the same framework
- current output format is native `.xlsx`
- admin modules provide row data, column definitions, and timestamped filenames; shared services generate and stream the workbook

### Media

Purpose:

- Shared image associations for consultations, events, and schedules.
- Admin image upload/caption/delete controls.
- Public list thumbnails and detail gallery rendering.

Key classes:

- `MediaRepository`
- `MediaService`
- `MediaAdminPanel`
- `MediaRenderer`

Representation image upload is separate and stores a single attachment ID directly on the representation row.

## Repository Layer

Repositories own SQL and custom table access.

Base class:

```text
app/Repositories/BaseRepository.php
```

The base repository provides:

- table prefixing
- prepared query helper
- pagination argument parsing
- safe order clause construction
- safe filter clause construction
- search clause construction
- pagination metadata
- safe SQL identifier checks

Module repositories include:

- `RepRepository`
- `ThreadRepository`
- `ThreadFieldRepository`
- `ThreadResponseRepository`
- `EventRepository`
- `EventFieldRepository`
- `EventRegistrationRepository`
- `ScheduleRepository`
- `ScheduleNoteRepository`
- `ContactRepository`
- `ActivityRepository`

Shared repositories include:

- `ElectoralAreaRepository`
- `MediaRepository`
- `ShortUrlRepository`

## Service Layer

Services coordinate workflows and keep business logic out of renderers and repositories.

Current services include:

- `RepService`
- `ThreadResponseService`
- `EventRegistrationService`
- `ScheduleService`
- `ContactService`
- `ActivityService`
- `MediaService`
- `ShortUrlService`

There are also older top-level services for thread, event, and schedule workflows under `app/Services`. Some module-specific services now exist under module folders.

Common workflow pattern:

1. Frontend/admin handler validates request intent and nonce.
2. Handler sanitizes input.
3. Handler delegates workflow to a service.
4. Service updates or creates contacts.
5. Service writes immutable submission/registration/response rows through repositories.
6. Service creates contact activity rows.

## Frontend Renderers

Frontend renderers are PHP classes or templates that output HTML.

Examples:

- `rep-form.php`
- `RepFormController`
- `RepDetailShortcode`
- `ThreadsListShortcode`
- `ThreadDetailShortcode`
- `ThreadResponseForm`
- `EventListShortcode`
- `EventDetailShortcode`
- `EventRegistrationForm`
- `ScheduleListShortcode`
- `ScheduleDetailShortcode`
- `StatisticsShortcode`
- `MediaRenderer`

Renderers should not contain SQL. Current list/detail renderers depend on repositories and services injected by module bootstrap classes.

## Admin Pages

Admin pages are class-based wp-admin pages. They use native WordPress menu APIs and capability checks.

Current admin implementation is operational wp-admin, not a separate frontend admin application.

Common admin features:

- list tables using `widefat`
- search
- pagination
- filters in some modules
- `Export (.xlsx)` actions for supported admin lists
- detail pages
- add/edit forms
- nonce checks for state changes
- capability checks

## Shared Helpers

Helpers:

- `DateHelper`: date/date-time formatting with empty-date handling.
- `FrontendPageResolver`: finds published pages containing a shortcode.
- `AdminMenuHelper`: hides submenu pages while preserving direct URL access.
- `StatusLabelHelper`: converts stored status keys into labels.

## Public Widgets

Widgets are implemented as `WP_Widget` subclasses:

- `LatestConsultationsWidget`
- `LatestEventsWidget`
- `UpcomingSchedulesWidget`

They use repositories for data, `FrontendPageResolver` for list/detail page URLs, and canonical slug URLs when records have slugs.

## Routing

Routing is implemented by:

```text
app/Core/CanonicalSlugRouter.php
```

Canonical routes:

- `/consultation/{slug}/`
- `/event/{slug}/`
- `/schedule/{slug}/`

Short URL route:

- `/go/{short_code}/`

The short URL prefix is filterable through `civic_short_url_prefix`.

The router:

- registers rewrite rules
- registers query vars
- resolves prefixed routes to existing pages that host detail shortcodes
- validates that records are public before resolving
- redirects legacy numeric detail URLs to canonical slug URLs
- redirects valid short URLs to canonical URLs
- prevents WordPress canonical redirects from replacing Civic prefixed routes with hosting page URLs

Slug uniqueness is enforced within each module. Short codes are globally checked across consultations, events, and schedules.
