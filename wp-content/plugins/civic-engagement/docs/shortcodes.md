# Shortcode Strategy

## Purpose

The project uses a shortcode-based frontend rendering strategy.

WordPress pages are used for:

* SEO
* menus
* content management
* page hierarchy

Operational functionality is injected using shortcodes.

This approach keeps:

* frontend workflows modular
* templates reusable
* module ownership clear
* routing flexible

---

# General Rules

* Each major module should expose its own shortcode(s).
* Shortcodes should remain lightweight.
* Rendering should happen through templates or dedicated frontend handlers.
* Business logic should NOT exist inside templates.
* Controllers/services should handle processing before rendering.
* Shortcodes should prefer repository/service layers instead of direct database access.

---

# Shortcode Naming Rules

Use the `civic_` prefix for all shortcodes.

Examples:

* `civic_rep_form`
* `civic_threads`
* `civic_thread_detail`
* `civic_events`

Avoid:

* generic names
* inconsistent naming patterns

---

# Rendering Rules

Shortcodes should:

* call frontend handlers/services
* load templates using output buffering where appropriate
* avoid large inline HTML generation inside shortcode methods
* avoid direct SQL queries

Example:

```php
ob_start();

include MODULE_PATH . '/Templates/rep-form.php';

return ob_get_clean();
```

---

# Page Strategy

Pages should be created normally in WordPress.

| Page | Shortcode |
|---|---|
| Submit Representation | `[civic_rep_form]` |
| Public Consultations | `[civic_threads]` |
| Consultation Detail | `[civic_thread_detail]` |
| Public Events | `[civic_events]` |
| Public Schedule | `[civic_schedule_list]` |

This allows:

* editable page content
* SEO flexibility
* menu flexibility
* layout customization

---

# Frontend Routing Strategy

Public-facing civic entities may support slug-based URLs.

Examples:

* `/housing`
* `/dub`
* `/community-plan`

Slug-based routing is intended for:

* consultations
* events
* future public civic entities

Current implementation may temporarily use:

* `?slug=housing`

before introducing full WordPress rewrite/permalink routing.

---

# Slug Rules

* Public slugs are globally unique across civic entities.
* Slugs are editable by administrators.
* Slugs are initially suggested from titles.
* Slug validation may use lightweight AJAX checks.
* `SlugService` is responsible for uniqueness validation.
* Repository layer must not assume local/module-only slug uniqueness.
* Slugs should remain stable after publication where possible.

---

# Current Implemented Shortcodes

## Reps Module

### `[civic_rep_form]`

Purpose:

* public representation submission form

---

## Threads Module

### `[civic_threads]`

Purpose:

* frontend consultation listing

Supports attributes:

* `detail_page_id="42"`

Example:

```text
[civic_threads detail_page_id="42"]
```

---

### `[civic_thread_detail]`

Purpose:

* frontend consultation detail display

Supports:

* `thread_id` query parameter
* `slug` query parameter

Examples:

* `/consultation-detail/?thread_id=12`
* `/consultation-detail/?slug=housing`

---

## Events Module

* `[civic_events]`
* `[civic_event_detail]`
* `[civic_event_registration]`

---

# Planned / Future Shortcodes

## Schedule Module

* `[civic_schedule_list]`
* `[civic_schedule_detail]`

---

## Frontend Admin Module

* `[civic_admin_dashboard]`
* `[civic_contact_list]`
* `[civic_rep_admin]`
* `[civic_thread_admin]`

---

# Attribute Rules

Use shortcode attributes for:

* page references
* filtering
* display behavior
* pagination limits
* frontend behavior configuration

Examples:

```text
[civic_threads detail_page_id="42"]

[civic_schedule_list type="public"]

[civic_events limit="10"]
```

Avoid excessive shortcode complexity.

---

# Template Rules

Templates should:

* remain presentation-focused
* avoid direct database queries
* avoid business/workflow logic

Templates belong inside module folders.

Example:

```text
Modules/Reps/Templates/
Modules/Threads/Templates/
```

---

# Frontend Pagination Rules

Frontend public listings should support lightweight pagination.

Prefer:

* query parameter pagination
* lightweight indexed queries

Avoid:

* complex AJAX pagination
* infinite scroll
* heavy frontend frameworks

Example:

* `?thread_page=2`

---

# Frontend Form Request Rules

All frontend forms must namespace request fields using module-specific request arrays.

Examples:

```text
civic_rep[name]
civic_rep[email]

civic_thread[name]
civic_thread[response]

civic_event[name]
civic_event[registration_data]
```

Avoid raw field names directly in frontend requests.

Avoid:

* `name`
* `email`
* `category`
* `year`
* `page`
* `author`

Reason:

WordPress internally reserves several request variable names for query parsing and routing.

Using raw field names may cause:

* unexpected 404 errors
* query conflicts
* permalink routing issues
* unpredictable frontend behavior

---

# Future Expansion Possibilities

Possible future support:

* Gutenberg blocks
* template overrides
* rewrite/permalink routing
* frontend widgets
* SEO enhancements
* public sharing tools

The current pilot version intentionally uses:

* lightweight shortcode architecture
* simple routing
* modular frontend rendering

for maintainability and operational clarity.