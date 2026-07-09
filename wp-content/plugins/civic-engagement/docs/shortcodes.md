# Shortcode Strategy

## Purpose

The project uses a shortcode-based frontend rendering strategy.

WordPress pages provide:

- SEO
- menu placement
- editable page content
- page hierarchy
- theme layout

Civic operational functionality is injected through plugin shortcodes.

This keeps frontend workflows modular and allows the plugin to own workflow rendering while the theme owns presentation and page composition.

## Current Implemented Shortcodes

| Shortcode | Module | Purpose |
| --- | --- | --- |
| `[civic_rep_form]` | Reps | Public representation submission form. |
| `[civic_rep_detail]` | Reps | Public representation detail display using `rep_id`. |
| `[civic_threads]` | Threads | Public consultation listing. |
| `[civic_thread_detail]` | Threads | Public consultation detail and consultation response form. |
| `[civic_events]` | Events | Public event listing. |
| `[civic_event_detail]` | Events | Public event detail and event registration form when registration is enabled. |
| `[civic_schedules]` | Schedules | Public schedule listing. |
| `[civic_schedule_detail]` | Schedules | Public schedule detail. |
| `[civic_statistics]` | Dashboard | Public platform statistics cards. |

There is no standalone `[civic_event_registration]` shortcode in the current source. Event registration is rendered inside `[civic_event_detail]`.

The current schedule listing shortcode is `[civic_schedules]`. Older references to `[civic_schedule_list]` are outdated.

## Public Page Strategy

Recommended public pages:

| Page | Shortcode |
| --- | --- |
| Submit Representation | `[civic_rep_form]` |
| Public Consultations | `[civic_threads]` |
| Consultation Detail | `[civic_thread_detail]` |
| Public Events | `[civic_events]` |
| Event Detail | `[civic_event_detail]` |
| Public Schedules | `[civic_schedules]` |
| Schedule Detail | `[civic_schedule_detail]` |

The canonical slug router searches published pages for the relevant detail shortcode and uses that page as the rendering target for prefixed public URLs.

## Shortcode Attributes

Current list shortcodes support:

- `limit`
- `pagination`
- `detail_page_id`

Examples:

```text
[civic_threads limit="3"]
[civic_events limit="10" pagination="1"]
[civic_schedules detail_page_id="42"]
```

`[civic_thread_detail]` supports:

- `show_public_responses`

Example:

```text
[civic_thread_detail show_public_responses="1"]
```

Public response rendering remains disabled unless this attribute is enabled.

## Pagination

Public listing pagination is lightweight and query-string based.

Current page variables:

- `thread_page`
- `event_page`
- `schedule_page`

When a `limit` is supplied and `pagination` is not explicitly enabled, the list is treated as a compact limited list.

## Rendering Rules

Shortcode classes should:

- remain lightweight
- query repositories or services rather than using raw SQL
- sanitize shortcode attributes
- escape output
- render through dedicated frontend classes or templates
- avoid business workflow logic in templates

Public form shortcodes should:

- validate request intent
- validate nonces
- sanitize request data
- delegate workflow handling to services
- preserve namespaced request fields

## Current Public URL Routing

Canonical public routes are implemented for:

- `/consultation/{slug}/`
- `/event/{slug}/`
- `/schedule/{slug}/`

Short URLs are implemented for:

- `/go/{short_code}/`

The short URL prefix defaults to `go` and is filterable through `civic_short_url_prefix`.

## Slug Rules

Current source behavior:

- slugs are module-local, not globally unique
- consultations, events, and schedules each validate slugs within their own table
- root-level civic slug routing is not implemented
- prefixed canonical URLs are used to avoid WordPress page/post/category conflicts
- legacy numeric detail URLs redirect permanently to canonical slug URLs when possible

Current canonical examples:

```text
/consultation/housing/
/event/community-meeting/
/schedule/public-update/
```

## Short URL Rules

Short URL codes:

- are optional
- are stored as `NULL` or an empty value when not set, depending on module save flow
- may contain lowercase letters, numbers, and hyphens
- are globally checked across consultations, events, and schedules
- redirect permanently to the canonical slug URL when valid
- return normal 404 behavior when invalid or not public

## Form Request Rules

All frontend form request fields must remain namespaced.

Current public forms use:

```text
civic_rep[name]
civic_thread_response[name]
civic_event_registration[name]
```

Avoid raw top-level request field names such as:

- `name`
- `email`
- `category`
- `year`
- `page`
- `author`

This prevents conflicts with WordPress query parsing and permalink routing.

## Widget Relationship

The plugin also implements sidebar widgets:

- Civic: Latest Consultations
- Civic: Latest Events
- Civic: Upcoming Schedules

Widgets are not shortcodes. They use the same repositories and canonical URL helpers as the shortcode renderers.

## Future Possibilities

Potential future frontend rendering options:

- Gutenberg blocks
- template override system
- refined archive sections for public listings
- shared shortcode view helpers
- richer pagination controls

These are not currently implemented.

