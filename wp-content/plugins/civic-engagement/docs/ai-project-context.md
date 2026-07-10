# AI Project Context

This document is intended for future AI assistants working on the Civic Engagement Platform.

It describes the current project shape, required context, and consistency rules to preserve during implementation.

## Documents to Review Before Implementation

Always review these before major changes:

- `docs/project-brief.md`
- `docs/project-status.md`
- `docs/development-guidelines.md`
- `docs/architecture.md`
- `docs/database.md`
- `docs/frontend-architecture.md`
- `docs/plugin-structure.md`
- `docs/css-architecture.md`
- `docs/theme-architecture.md`
- `docs/admin-ui.md`
- `docs/release-readiness.md`

Also review module-specific docs when touching a module:

- `docs/reps.md`
- `docs/threads.md`
- `docs/events.md`
- `docs/schedules.md`
- `docs/contacts.md`
- `docs/communication.md`
- `docs/admin.md`
- `docs/security-and-permissions.md`
- `docs/shortcodes.md`

When implementation differs from older docs, use the current source code as the source of truth and update documentation as part of the change.

## Overall Architecture

The project is a custom WordPress plugin plus a GeneratePress child theme.

The plugin owns:

- civic workflows
- custom table access
- repositories
- services
- public shortcodes
- public forms
- widgets
- canonical routing
- short URLs
- wp-admin operational screens
- login/admin branding hooks
- capabilities

The theme owns:

- branding
- homepage composition
- typography
- site layout
- GeneratePress visual refinements
- presentation overrides
- theme-level Civic Manager admin skin

## Repository to Service to Renderer Separation

Preserve this separation:

1. Repositories
   - own SQL
   - use `$wpdb`
   - prepare queries
   - return structured data
   - do not render HTML

2. Services
   - coordinate workflows
   - update/create contacts
   - preserve submitted snapshots
   - create activities
   - call repositories
   - do not render templates

3. Renderers/controllers/admin pages
   - validate request intent
   - check nonces/capabilities
   - sanitize request data
   - call services/repositories
   - render HTML
   - avoid raw SQL

Templates should display prepared data only.

## Theme vs Plugin Responsibilities

Plugin:

- reusable component markup
- shortcode rendering
- form processing
- data workflows
- module admin screens
- route handling
- shared frontend component CSS where practical

Theme:

- homepage design
- page layout
- header/navigation/footer styling
- brand colors and typography
- responsive site composition
- theme-specific overrides

Do not move workflow logic into the theme.

## CSS Responsibilities

Plugin CSS should contain reusable public components:

- `civic-form`
- `civic-card`
- `civic-stat-card`
- `civic-widget`
- `civic-media-gallery`

Theme CSS should contain:

- `civic-home`
- section layout
- GeneratePress refinements
- site chrome
- branding
- page-level composition

When adding CSS, avoid duplicating rules across plugin and theme unless there is a clear reason.

## Card Design Conventions

Public cards should use:

- `civic-card`
- `civic-list-card` for list output
- `civic-card-main-details` for detail output
- `civic-card__media`
- `civic-card__content`
- `civic-card__title`
- `civic-card__summary`
- `civic-card__description`
- `civic-card__meta`
- `civic-card__footer`
- `civic-card__actions`

Also include module-specific hooks:

- `civic-threads__item`
- `civic-events__item`
- `civic-schedules__item`
- `civic-thread-detail__content`
- `civic-event-detail__content`
- `civic-schedule-detail__content`

## Form Design Conventions

Public forms should use:

- `civic-form`
- `civic-form__title`
- `civic-form__form`
- `civic-form__field`
- `civic-form__field--full`
- `civic-form__consent`
- `civic-form__actions`
- `civic-form__message`
- `civic-form__error`
- `civic-form__captcha`

Keep module-specific classes alongside shared classes:

- `civic-rep-form`
- `civic-thread-response-form`
- `civic-event-registration-form`

Never change field names, IDs, hidden fields, nonce fields, validation attributes, form actions, or request structure unless the workflow explicitly requires it.

Public request fields must remain namespaced:

- `civic_rep[...]`
- `civic_thread_response[...]`
- `civic_event_registration[...]`

Shared CAPTCHA handling belongs in `CaptchaService`. Representation submission, Consultation responses, and Event registrations render the shared Cloudflare Turnstile widget and validate the submitted token before processing when CAPTCHA is enabled.

## Statistics Component Conventions

Statistics markup:

- `civic-statistics`
- `civic-stat-card`
- `civic-stat-card__number`
- `civic-stat-card__title`

Data belongs in `PublicStatisticsService`; rendering belongs in `StatisticsShortcode`.

## Coding Standards

Follow current project conventions:

- Use namespaces.
- Prefer OOP.
- Keep classes focused.
- Use repositories for SQL.
- Use services for workflows.
- Keep controllers/admin pages lightweight.
- Sanitize input.
- Escape output.
- Validate nonces.
- Validate capabilities on admin actions.
- Use WordPress APIs where practical.
- Preserve immutable submitted snapshots.
- Match contacts by email address.
- Keep public users accountless.

## Documentation Update Policy

Update documentation when:

- a module workflow changes
- a public shortcode changes
- a public form changes
- route behavior changes
- CSS ownership changes
- admin behavior changes
- database schema changes
- theme responsibilities change
- release readiness status changes
- public content lifecycle rules change

New features should update both the module-specific doc and any relevant architecture doc.

Before Version 1.0, update `docs/release-readiness.md` whenever a pending release item is completed, deferred, or re-scoped.

## Backward Compatibility Expectations

Preserve:

- shortcode names
- public URL structures
- canonical slug routes
- short URL behavior
- request field names
- nonce field names
- hidden action values
- database table contracts
- public form IDs
- admin page slugs where practical
- module-specific CSS hooks

Login and admin branding are implemented in `DashboardAdmin`. `/civic-admin` should remain a lightweight redirect into normal WordPress authentication rather than a separate authentication system. Civic admin headers should remain scoped to Civic Platform admin page slugs.

The Civic admin header is fixed and includes the platform title, plugin version, Documentation action and a Visit Website action. System-level pages are grouped under the System menu; Documentation is the System landing page and Security remains available as `civic-security-settings` under System.

When adding shared classes, add them alongside existing classes rather than replacing existing hooks.

## Preferred Development Workflow

1. Read relevant docs.
2. Inspect current source implementation.
3. Identify module boundaries.
4. Make the smallest change that satisfies the requirement.
5. Avoid unrelated refactors.
6. Run syntax checks/tests where available.
7. Update docs when behavior changes.
8. Summarize files changed and workflow impact.

## General Principles

Preserve:

- operational simplicity
- modularity
- predictable workflows
- repository/service separation
- custom table ownership
- email-based identity
- immutable snapshot records
- lightweight admin flows
- shortcode-based public rendering
- theme/plugin separation
- backward compatibility

Avoid:

- enterprise workflow engines
- public account systems
- raw SQL outside repositories
- business logic inside templates
- theme-owned workflow logic
- unnecessary abstractions
- tight coupling between modules
- large unrelated styling rewrites

## Release Readiness Notes

Pending Version 1.0 work is tracked in `docs/release-readiness.md`.

Do not describe a pending item as implemented until the source code exists and has been reviewed. Current pending items include final public UI polish and a sidebar representation prompt widget.

Active/Archived lifecycle rules for consultations, events, and schedules are documented in `docs/release-readiness.md` and the relevant module documents. Those rules are documentation source-of-truth for future implementation.
