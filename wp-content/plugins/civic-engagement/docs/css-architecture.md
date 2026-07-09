# CSS Architecture

This document describes the current CSS implementation across the Civic Engagement plugin and Civic theme.

## Stylesheet Responsibilities

### `assets/css/frontend.css`

Plugin stylesheet enqueued for all public frontend requests by `civic_enqueue_frontend_assets()`.

Current responsibilities:

- Shared public form design system.
- Public form grid, fields, consent groups, buttons, messages, and errors.
- Public statistics card baseline styles.
- Some older homepage-related styles that overlap with the active theme.

Key component classes:

- `civic-form`
- `civic-form__title`
- `civic-form__form`
- `civic-form__field`
- `civic-form__field--full`
- `civic-form__consent`
- `civic-form__actions`
- `civic-statistics`
- `civic-stat-card`

This file should be treated as the plugin-owned home for reusable frontend components produced by plugin shortcodes.

### `assets/css/civic-admin.css`

Plugin admin stylesheet enqueued by `DashboardAdmin` for restricted Civic users.

Current responsibilities:

- Adds polish to the simplified Civic admin experience.
- Hides selected WordPress notices and update noise for restricted Civic users.
- Styles dashboard cards and recent sections under `body.civic-admin`.
- Applies only when the plugin adds the `civic-admin` body class.

Key classes:

- `body.civic-admin`
- `civic-dashboard__cards`
- `civic-dashboard__card`
- `civic-dashboard__recent-grid`
- `civic-dashboard__recent-section`

### `assets/css/admin-dashboard.css`

Plugin stylesheet containing dashboard card and recent-grid styles.

Current status:

- Present in the plugin.
- No current enqueue reference was found in the plugin bootstrap or admin classes.
- Similar dashboard styling also exists in `assets/css/civic-admin.css`.

This file appears to be legacy or unused in the current runtime path.

### `assets/css/frontend.css.back`

Backup/older frontend stylesheet.

Current status:

- Present in the plugin.
- Not enqueued by the current plugin bootstrap.
- Contains older versions of public frontend styles, including form message styles.

This file should be treated as retained historical material unless explicitly reintroduced.

### `wp-content/themes/civic/style.css`

Active Civic child theme stylesheet.

Current responsibilities:

- Theme metadata for the GeneratePress child theme.
- Inter font declarations.
- Design tokens.
- GeneratePress header/navigation refinements.
- Homepage layout and presentation.
- Inner page container polish.
- Sidebar widget presentation.
- Theme-level presentation overrides for plugin public components.
- Card, statistics, form, media, and latest activity visual refinements.
- Responsive layout rules for the homepage and major public components.

This stylesheet currently styles both theme-owned classes and plugin-owned classes.

### `wp-content/themes/civic/style-old.css`

Older retained theme stylesheet.

Current status:

- Present in the theme.
- Not referenced by `functions.php`.
- Not the active child theme stylesheet.

### `wp-content/themes/civic/assets/css/civic-manager-admin.css`

Theme admin stylesheet conditionally enqueued by the Civic theme for users with the `civic_manager` role.

Current responsibilities:

- Full wp-admin visual skin for civic manager users.
- Admin bar styling.
- Left admin menu styling.
- Main content background and spacing.
- Tables, forms, buttons, notices, postboxes, and footer cleanup.

Key scope:

- `body.civic-manager-admin`

This stylesheet is theme-owned and separate from the plugin `civic-admin` restricted admin stylesheet.

## Plugin vs Theme Styling

Plugin CSS should own:

- Reusable shortcode component structure.
- Public form system.
- Public card baseline styles.
- Public statistics baseline styles.
- Public widget baseline styles.
- Public media gallery baseline styles.
- Admin UI styles required for plugin screens to work across themes.

Theme CSS should own:

- Branding.
- Typography.
- Site layout.
- Homepage layout.
- Header/navigation/footer presentation.
- Page container styling.
- Theme-specific visual overrides for plugin components.
- Responsive site composition.

## Component vs Layout Styling

Component styling:

- Reusable.
- Shortcode-safe.
- Should work on any WordPress page.
- Belongs primarily in plugin CSS.

Examples:

- `civic-form`
- `civic-card`
- `civic-stat-card`
- `civic-widget`
- `civic-media-gallery`

Layout styling:

- Site-specific.
- Page-specific.
- Concerned with section spacing, grids, homepage composition, and theme chrome.
- Belongs primarily in theme CSS.

Examples:

- `civic-home`
- `civic-home-hero`
- `civic-home-services`
- `civic-home-latest`
- GeneratePress header and navigation selectors

## Reusable CSS Conventions

The project mostly uses a BEM-like convention:

- Block: `civic-form`
- Element: `civic-form__field`
- Modifier: `civic-form__field--full`

Shared component classes should be combined with module-specific hooks:

```html
<article class="civic-card civic-list-card civic-events__item">
```

```html
<section class="civic-event-registration-form civic-form">
```

This preserves reusable styling while allowing module-specific targeting.

## Current CSS Duplication

Known duplication in the current implementation:

- Homepage styles exist in both plugin `frontend.css` and theme `style.css`.
- Statistics card styles exist in both plugin and theme CSS.
- Form focus/border styling exists in both plugin and theme CSS.
- Dashboard card styles exist in both `civic-admin.css` and `admin-dashboard.css`.
- Theme and plugin both contain representation, consultation, event, and schedule icon assets.

These duplications are documented here only. They are not refactored by this documentation pass.

## Current Runtime Enqueue Map

Public frontend:

- Plugin enqueues `assets/css/frontend.css`.
- Theme automatically loads `wp-content/themes/civic/style.css` as the active child theme stylesheet.

Plugin restricted admin:

- Plugin enqueues `assets/css/civic-admin.css` for restricted Civic users through `DashboardAdmin`.

Theme civic manager admin:

- Theme enqueues `assets/css/civic-manager-admin.css` for users with the `civic_manager` role.

No current runtime enqueue was found for:

- `assets/css/admin-dashboard.css`
- `assets/css/frontend.css.back`
- `wp-content/themes/civic/style-old.css`

