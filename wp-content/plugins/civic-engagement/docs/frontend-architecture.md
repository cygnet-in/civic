# Frontend Architecture

This document describes the public-facing frontend implementation that currently exists in the Civic Engagement plugin and Civic theme.

The frontend is primarily shortcode-driven. WordPress pages provide page ownership, menus, SEO, and page layout. Civic module classes render operational content into those pages through shortcodes and widgets.

## Public Shortcode Architecture

Implemented public shortcodes:

| Shortcode | Owner | Purpose |
| --- | --- | --- |
| `[civic_rep_form]` | Reps module | Public representation submission form. |
| `[civic_rep_detail]` | Reps module | Public representation detail by `rep_id`. |
| `[civic_threads]` | Threads module | Public consultation listing. |
| `[civic_thread_detail]` | Threads module | Public consultation detail and response form. |
| `[civic_events]` | Events module | Public event listing. |
| `[civic_event_detail]` | Events module | Public event detail and registration form when enabled. |
| `[civic_schedules]` | Schedules module | Public schedule listing. |
| `[civic_schedule_detail]` | Schedules module | Public schedule detail. |
| `[civic_statistics]` | Dashboard module | Public statistics cards. |

List shortcodes generally support:

- `limit`
- `pagination`
- `detail_page_id`

Pagination uses query-string page variables:

- `thread_page`
- `event_page`
- `schedule_page`

Detail shortcodes resolve records either from canonical slug routing through `civic_slug` or legacy numeric query parameters such as `thread_id`, `event_id`, and `schedule_id`.

## Card Component Structure

Public list and detail renderers use a shared card vocabulary centered on `civic-card`.

Common list structure:

```html
<article class="civic-card civic-list-card civic-{module}__item">
    <div class="civic-card__media"></div>
    <div class="civic-card__content">
        <h2 class="civic-card__title civic-{module}__title"></h2>
        <p class="civic-card__summary civic-{module}__summary"></p>
        <div class="civic-card__meta"></div>
        <div class="civic-card__footer">
            <span class="civic-card__left"></span>
            <span class="civic-card__actions civic-card__right"></span>
        </div>
    </div>
</article>
```

Current list-card renderers:

- `ThreadsListShortcode`
- `EventListShortcode`
- `ScheduleListShortcode`

Detail renderers use:

- `civic-card`
- `civic-card-main-details`
- `civic-card-detail__title`
- module detail classes such as `civic-thread-detail__content`

The theme currently provides much of the visible card styling in `wp-content/themes/civic/style.css`, while plugin renderers provide the markup.

## Form Design System

Public-facing forms now share the Civic form class vocabulary:

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

Implemented public forms:

- Representation submission: `app/Modules/Reps/Templates/rep-form.php`
- Consultation response: `app/Modules/Threads/Responses/Frontend/ThreadResponseForm.php`
- Event registration: `app/Modules/Events/Registrations/Frontend/EventRegistrationForm.php`

Module-specific classes remain alongside shared classes:

- `civic-rep-form`
- `civic-thread-response-form`
- `civic-event-registration-form`

These module classes are retained for compatibility with module-specific CSS, JavaScript, and future extension.

All public forms use namespaced request arrays:

- `civic_rep[...]`
- `civic_thread_response[...]`
- `civic_event_registration[...]`

All public forms include nonce validation and delegate workflow processing to frontend handlers and services rather than templates.

Shared CAPTCHA infrastructure is provided by `CaptchaService`. It renders a Cloudflare Turnstile widget inside the Civic Form design system using `civic-form__captcha` and validates submitted tokens server-side.

When CAPTCHA is enabled in Civic Manager > Dashboard > Security, Representation submission, Consultation response, and Event registration forms render the shared widget and validate the token before processing the workflow.

## Statistics Cards

The statistics component is rendered by:

```text
app/Modules/Dashboard/Frontend/StatisticsShortcode.php
```

Markup:

```html
<div class="civic-statistics">
    <div class="civic-stat-card">
        <div class="civic-stat-card__number"></div>
        <div class="civic-stat-card__title"></div>
    </div>
</div>
```

Data is supplied by `PublicStatisticsService`, which currently counts public activity across representations, consultations, consultation responses, and events.

The theme uses the statistics shortcode inside the homepage statistics section.

## Homepage Sections

The homepage is implemented in the Civic theme:

```text
wp-content/themes/civic/front-page.php
```

Sections:

- Hero section with councillor image, title, tagline, text, action buttons, and highlights.
- Quick action service cards linking to representation, consultations, events, and schedules.
- About section with image and biography text.
- Statistics section using `[civic_statistics]`.
- Latest activity section using `[civic_threads limit="3"]`, `[civic_events limit="3"]`, and `[civic_schedules limit="3"]`.
- Bottom call-to-action section linking to the representation form.

The homepage is theme-owned. It composes plugin shortcodes but does not own plugin workflows.

## Sidebar Widgets

Implemented frontend widgets:

- `Civic: Latest Consultations`
- `Civic: Latest Events`
- `Civic: Upcoming Schedules`

Widget classes:

- `LatestConsultationsWidget`
- `LatestEventsWidget`
- `UpcomingSchedulesWidget`

Widget markup uses:

- `civic-widget`
- `civic-widget--latest-consultations`
- `civic-widget--latest-events`
- `civic-widget--upcoming-schedules`
- `civic-widget__item`
- `civic-widget__date`
- `civic-widget__view-all`

Widgets resolve public pages by searching published pages for the relevant shortcode through `FrontendPageResolver`.

## Shared UI Components

Current shared public UI components include:

- `civic-card` list and detail card markup.
- `civic-form` public form markup.
- `civic-statistics` and `civic-stat-card`.
- `civic-widget` sidebar list markup.
- `civic-media-gallery` and `civic-card__media`.
- `civic-button` action links in consultation detail actions.

The shared media renderer is:

```text
app/Modules/Media/Frontend/MediaRenderer.php
```

It renders list thumbnails and detail galleries for consultations, events, and schedules.

## Layout Containers

Plugin-rendered public containers:

- `civic-threads`
- `civic-events`
- `civic-schedules`
- `civic-thread-detail`
- `civic-event-detail`
- `civic-schedule-detail`
- `civic-rep-detail`
- `civic-cards-main-list`
- `civic-cards-home-list`

Public consultation, event and schedule listing shortcodes choose the card list container from the existing `pagination` shortcode behavior. Pagination-enabled output uses `civic-cards-main-list`. Pagination-disabled preview output uses `civic-cards-home-list`.

Theme homepage containers:

- `civic-home`
- `civic-home__container`
- `civic-home-section`
- `civic-home-hero`
- `civic-home-services`
- `civic-home-about`
- `civic-home-stats`
- `civic-home-latest`
- `civic-home-cta`

The plugin owns functional markup. The theme owns overall page layout, homepage composition, branding, and presentation refinements.

## Responsive Design Conventions

The plugin frontend CSS includes responsive behavior for shared forms and older homepage-style classes.

The active theme contains the main responsive layout rules:

- Service cards collapse from four columns to two columns, then one column.
- Hero and about grids collapse to one column on smaller screens.
- Latest activity collapses from three columns to two, then one.
- Statistics cards collapse from four columns to two, then one.
- Home buttons become full width on small screens.

Breakpoints currently used include:

- `1100px`
- `980px`
- `700px`
- `640px`

## BEM Naming Conventions

The codebase mostly follows a BEM-like convention:

- Block: `civic-card`, `civic-form`, `civic-stat-card`, `civic-widget`
- Element: `civic-card__title`, `civic-form__field`, `civic-widget__item`
- Modifier: `civic-form__field--full`, `civic-card__status--open`

Module-specific blocks extend shared structures:

- `civic-threads__item`
- `civic-events__item`
- `civic-schedules__item`
- `civic-thread-detail__title`
- `civic-event-detail__registration-form`

Some older or theme-specific CSS is less consistent and should be treated as current implementation rather than final convention.
