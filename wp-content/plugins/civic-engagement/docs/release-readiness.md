# Release Readiness

This document is the source of truth for the remaining work and public lifecycle rules before Version 1.0.

It reflects the current source implementation and does not implement any pending feature.

## Release Status

Current Phase:

Release Candidate (RC)

Current Focus:

- Complete remaining Version 1.0 features.
- Final UI refinement.
- Release testing.
- Documentation completion.

Target:

Version 1.0

## Completed Features

Core completed features:

- Representation submission workflow.
- Representation image upload.
- Representation to Schedule conversion prefill workflow.
- Shared CAPTCHA infrastructure and public form integration using Cloudflare Turnstile. Production verification pending deployment to the live hostname.
- Consultation listing and detail shortcodes.
- Consultation response submission.
- Consultation lifecycle enforcement: archived consultations remain public but read-only.
- Consultation custom fields.
- Starting Response Count display.
- Event listing and detail shortcodes.
- Event registration workflow.
- Event lifecycle enforcement: archived events remain public but read-only.
- Event custom registration fields.
- Schedule listing and detail shortcodes.
- Schedule notes.
- Contact management.
- Shared XLSX export framework for Contacts, Representations, Consultations, Consultation Responses, Events, Event Registrations, and Schedules.
- Cumulative communication consent storage.
- Activity logging for major public participation workflows.
- Civic Dashboard.
- Public Statistics shortcode.
- Public Statistics Service.
- Shared Civic Search shortcodes, configurable Search Results Page setting, and `SearchService`.
- Shared Civic Form design system.
- Shared Civic Card structure for main lists and detail views.
- Media module for consultation, event, and schedule image galleries.
- Canonical slug routing for consultations, events, and schedules.
- Short URL routing using `/go/{short_code}`.
- Civic Manager role and capabilities.
- Custom wp-admin Civic operational menus.
- Civic Platform login branding and `/civic-admin` login entry point.
- Fixed branded Civic admin header with dynamic plugin version, Documentation action, and "Visit Website" action.
- System admin group with Documentation and Security settings.
- Civic theme homepage implementation.
- Theme/plugin responsibility split documented.
- CSS ownership documented.

## Pending Version 1.0 Features

### Core Features

No remaining core workflow features are currently marked pending.

## CAPTCHA

Version 1.0 uses a shared CAPTCHA service.

The shared infrastructure is implemented in `CaptchaService`.

Initial implementation uses Cloudflare Turnstile.

The service renders Turnstile widgets, verifies submitted Turnstile tokens server-side, and exposes a reusable validation API for public forms.

CAPTCHA settings are configured from Civic Manager > Dashboard > Security.

Representation submission, Consultation responses, and Event registrations render the shared Turnstile widget when CAPTCHA is enabled and validate the submitted token before processing the workflow.

Live Turnstile verification depends on the configured Cloudflare keys and may not be testable in a local development environment when production-restricted keys are used.

#### Representation to Schedule Workflow

A Representation may be converted into a Schedule by an administrator.

The implemented conversion:

- prefill the Schedule form using Representation data,
- allow administrator review and editing,
- create a normal Schedule,
- maintain a `source_type=rep` / `source_id` reference to the originating Representation,
- persist the created Schedule ID on the originating Representation,
- prevent duplicate conversions for the same Representation,
- avoid automatic workflow progression.

The workflow starts from the Representation detail screen and opens the normal Schedule create screen. No Schedule is created until the administrator submits the Schedule form.

### Homepage / Public UI

- Finalise homepage card layouts.
- Complete final visual styling for `civic-cards-home-list` and `civic-cards-main-list`.
- Review sidebar widgets and homepage consistency.
- Final UI polish and responsive review.

### Administration

- Final admin UI polish.

## Login and Administration Branding

Version 1.0 uses WordPress authentication with Civic Platform branding.

Implemented behavior:

- the default WordPress login logo is replaced with Civic Platform branding,
- the login page uses `assets/css/civic-login.css`,
- `/civic-admin` is the preferred administrator login entry point,
- unauthenticated `/civic-admin` requests redirect to WordPress login with the Civic Dashboard as the redirect target,
- logged-in Civic users visiting `/civic-admin` are redirected to the Civic Dashboard,
- logged-in non-Civic users visiting `/civic-admin` are redirected to normal WordPress admin,
- Civic Managers visiting `/wp-admin` are redirected to the Civic Dashboard,
- WordPress Administrators visiting `/wp-admin` remain on the normal WordPress Dashboard,
- WordPress Administrators have a top-level Civic Admin menu item that opens the Civic Dashboard,
- standard WordPress admin pages show only the single top-level Civic Admin entry from the Civic Platform,
- Civic admin pages hide standard WordPress operational menus and show Civic Platform menus,
- Civic admin pages display a fixed branded header containing the Civic Platform logo, platform title, dynamic plugin version, Documentation action, Administrator-only "WP Admin" action and "Visit Website" action,
- Civic admin stylesheets load only on admin pages whose page slug starts with `civic-`,
- normal WordPress admin pages do not load Civic admin stylesheets,
- the "Visit Website" action opens the public site in a new browser tab,
- the System admin group contains Documentation and Security settings,
- the Documentation page provides a lightweight Civic Manager user manual,
- the branded header is scoped to Civic Platform admin page slugs only.

### Public Website

- Add sidebar HTML widget encouraging visitors to submit a Representation.

## Deferred Features

Deferred beyond Version 1.0 unless explicitly reprioritised:

- SMS integration.
- WhatsApp integration.
- Payment systems.
- Event capacity/waitlist management.
- Advanced moderation workflows.
- Advanced CRM segmentation.
- Marketing campaign automation.
- Unsubscribe workflow.
- Analytics/open tracking.
- QR code generation.
- URL click analytics.
- Advanced GIS.
- Workflow automation engine.
- Public user accounts.
- Public schedule comments.

## Public Content Lifecycle

Public content should be split into Active and Archived presentation groups.

Main listing pages should:

1. Show Active items first.
2. Show Archived items in a separate section beneath the Active list.
3. Use full cards only for Active items.
4. Use simplified title/link presentation for Archived items.

This lifecycle rule applies to consultations, events, and schedules.

The repository layer now exposes Public Active and Public Archived query methods for these modules, current public listings use the Active methods, and standalone archive shortcodes are implemented for consultations, events, and schedules.

Archive shortcodes:

- `[civic_threads_archive]`
- `[civic_events_archive]`
- `[civic_schedules_archive]`

Archive shortcodes render full cards with pagination when no `limit` is supplied. With `limit`, they render compact title/link lists without pagination.

## Presentation Rules

Active items

- displayed using full civic-card layout
- participate in pagination
- appear first

Archived items

- displayed after Active items
- simplified title/link presentation
- separate heading
- excluded from Active pagination

The Active and Archived lists represent the public history of the Civic Platform. They are not administrative lists. Only content intended for public visibility may appear in either Active or Archived listings.

## Public Visibility Rule

Active and Archived listings are public listings.

Only records that are intended for public viewing participate in these listings.

Draft, unpublished, private, disabled or otherwise non-public records must never appear in either the Active or Archived public lists.

## Consultation Lifecycle

A consultation is Active when:

- it is published/public,
- it is accepting responses,
- and the current date is on or before the consultation end date when an end date is set.

A consultation is Archived when:

- it is no longer accepting responses,
- or its end date has passed,
- or it is otherwise closed by an administrator.

Public consultation listings should reserve full `civic-card` presentation for Active consultations. Standalone archive shortcodes can render full archive cards or limited compact title/link lists.

Archived consultations remain publicly viewable through their detail pages, but they are read-only. The public response form and "Have Your Say" action are shown only while the consultation is Active. Response submissions are also blocked in the response workflow once the consultation is no longer Active.

## Event Lifecycle

An event is Active when:

- it is public,
- it is published/active,
- and the current date is before or on the event end date when an end date is set.

An event is Archived when:

- the event end date has passed,
- or the event status is closed,
- or the event is no longer public.

Public event listings should reserve full `civic-card` presentation for Active events. Standalone archive shortcodes can render full archive cards or limited compact title/link lists.

Archived events remain publicly viewable through their detail pages, but they are read-only. The public registration form is shown only while the event is Active. Registration submissions are also blocked in the registration workflow once the event is no longer Active.

## Schedule Lifecycle

A schedule is Active when:

- it is public,
- it is not marked archived,
- and its status is one of the operational active statuses such as open, pending, or scheduled.

A schedule is Archived when:

- it is manually marked archived,
- or its status is completed or cancelled,
- or the relevant status/end date has passed according to the final Version 1.0 listing rules.

Public schedule listings should reserve full `civic-card` presentation for Active schedules. Standalone archive shortcodes can render full archive cards or limited compact title/link lists.

## Documentation Inconsistencies Discovered

Resolved during this review:

- `docs/shortcodes.md` referenced outdated `[civic_schedule_list]`; current source uses `[civic_schedules]`.
- `docs/shortcodes.md` referenced a standalone `[civic_event_registration]`; current source renders event registration inside `[civic_event_detail]`.
- `docs/shortcodes.md` described global public slug uniqueness and `SlugService`; current source uses module-local slug uniqueness and `ShortUrlService` for global short code checks.
- Earlier CAPTCHA documentation described infrastructure-only status; current public representation, consultation response, and event registration forms now call the shared service.
- Active/Archived lifecycle rules were missing as a source-of-truth decision.

Remaining known documentation concerns:

- Some older documents still describe future frontend administration as a strategic direction while current implementation remains wp-admin based.
- Some older documents intentionally describe future possibilities; they should not be read as current implementation unless explicitly marked implemented.

## Missing Documentation Before Version 1.0

Recommended documentation still to add before Version 1.0:

- Final CAPTCHA deployment notes after production Cloudflare Turnstile keys are configured and verified.
- Login/admin branding architecture after implementation.
- Final public listing lifecycle implementation notes after Active/Archived sections are built.
- Final visual UI component contract for `civic-cards-home-list` and `civic-cards-main-list`.
- Release checklist with manual validation steps for public forms, admin workflows, routing, short URLs, and media uploads.

# Homepage Rules

The homepage is a summary page.

Latest Consultations:
3 items

Latest Events:
3 items

Latest Schedules:
3 items

Homepage listings use
civic-cards-home-list.

Full listing pages use
civic-cards-main-list.

# Version 1.0 Scope Freeze

The following items define the functional scope of Version 1.0.

Only defects, committed features and UI refinement should be added after this point.

All new feature requests should be evaluated for Version 1.1 unless they are required to satisfy an existing client commitment.
