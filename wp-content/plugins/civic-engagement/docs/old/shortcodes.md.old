# Shortcode Strategy

## Purpose

The project uses a shortcode-based frontend rendering strategy.

WordPress pages are used for:

* SEO,
* menus,
* content management,
* page hierarchy.

Operational functionality is injected using shortcodes.

This approach keeps:

* frontend workflows modular,
* templates reusable,
* and module ownership clear.

---

# General Rules

* Each major module should expose its own shortcode(s).
* Shortcodes should remain lightweight.
* Rendering should happen through templates.
* Business logic should NOT exist inside templates.
* Controllers/services should handle processing before rendering.

---

# Shortcode Naming Rules

Use:

```text
civic_
```

prefix for all shortcodes.

Examples:

* civic_rep_form
* civic_thread_list
* civic_event_registration

Avoid:

* generic names
* inconsistent naming patterns

---

# Rendering Rules

Shortcodes should:

* call controllers/services,
* load templates using output buffering,
* avoid inline HTML generation inside methods.

Example:

```php
ob_start();

include MODULE_PATH . '/Templates/rep-form.php';

return ob_get_clean();
```

---

# Page Strategy

Pages should be created normally in WordPress.

Example:

| Page            | Shortcode               |
| --------------- | ----------------------- |
| Submit Rep      | [civic_rep_form]        |
| Public Threads  | [civic_thread_list]     |
| Public Schedule | [civic_schedule_list]   |
| Admin Dashboard | [civic_admin_dashboard] |

This allows:

* editable page content,
* SEO flexibility,
* menu flexibility,
* layout customization.

---

# Recommended Shortcodes

## Reps Module

```text
[civic_rep_form]
```

Optional future:

```text
[civic_rep_list]
[civic_rep_detail]
```

---

# Threads Module

```text
[civic_thread_list]
[civic_thread_response id="5"]
```

Optional future:

```text
[civic_thread_detail id="5"]
```

---

# Events Module

```text
[civic_event_list]
[civic_event_registration id="10"]
```

Optional future:

```text
[civic_event_detail id="10"]
```

---

# Schedule Module

```text
[civic_schedule_list]
```

Optional future:

```text
[civic_schedule_detail id="5"]
```

---

# Admin Module

```text
[civic_admin_dashboard]
[civic_contact_list]
[civic_rep_admin]
[civic_thread_admin]
```

---

# Attribute Rules

Use shortcode attributes for:

* IDs,
* filtering,
* display behavior.

Examples:

```text
[civic_event_registration id="10"]

[civic_schedule_list type="public"]

[civic_thread_response id="5"]
```

---

# Template Rules

Templates should:

* remain presentation-focused,
* avoid direct database queries,
* avoid workflow/business logic.

Templates belong inside module folders.

Example:

```text
Modules/Reps/Templates/
```

---

# Future Expansion Possibilities

Possible future support:

* Gutenberg blocks
* template overrides
* custom routing
* frontend widgets

Current pilot version intentionally uses:

* lightweight shortcode architecture
  for simplicity and maintainability.
