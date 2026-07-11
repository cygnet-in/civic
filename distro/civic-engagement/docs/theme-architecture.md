# Theme Architecture

This document describes the currently implemented Civic theme.

Theme path:

```text
wp-content/themes/civic
```

The Civic theme is a GeneratePress child theme. It is responsible for site presentation, homepage composition, branding, typography, layout, and a theme-level admin skin for users with the `civic_manager` role.

## Theme Files

Current theme files:

| File | Responsibility |
| --- | --- |
| `style.css` | Active child theme stylesheet, design tokens, GeneratePress refinements, homepage layout, inner page polish, plugin component presentation overrides. |
| `style-old.css` | Older retained stylesheet. Not enqueued by the theme code. |
| `functions.php` | Adds Civic Manager admin body class and conditionally enqueues `assets/css/civic-manager-admin.css`. |
| `front-page.php` | Custom homepage template. |
| `assets/css/civic-manager-admin.css` | Theme-level Civic Manager wp-admin visual skin. |
| `assets/images/*` | Homepage profile images and feature icons. |
| `assets/fonts/*` | Inter font files used by `style.css`. |

## Template Hierarchy

The theme only defines a custom `front-page.php`.

Other public pages fall back to GeneratePress templates. This means:

- WordPress pages host Civic shortcodes.
- GeneratePress controls normal page templates, headers, content wrappers, sidebars, and footer rendering.
- The Civic theme overrides styling rather than replacing the full theme hierarchy.

The theme does not define custom templates for consultations, events, schedules, or representations. Those are rendered by plugin shortcodes inside normal WordPress pages.

## Homepage Structure

The homepage template is:

```text
wp-content/themes/civic/front-page.php
```

Implemented sections:

1. Hero
   - Uses `civic-home-hero`.
   - Displays councillor name, tagline, text, action links, highlights, and image.

2. Quick Actions
   - Uses `civic-home-services`.
   - Provides cards for representation, consultations, events, and schedules.

3. About
   - Uses `civic-home-about`.
   - Displays a profile image and biographical content.

4. Statistics
   - Uses `civic-home-stats`.
   - Embeds `[civic_statistics]`.

5. Latest Activity
   - Uses `civic-home-latest`.
   - Embeds:
     - `[civic_threads limit="3"]`
     - `[civic_events limit="3"]`
     - `[civic_schedules limit="3"]`

6. Bottom CTA
   - Uses `civic-home-cta`.
   - Links to the representation page.

The homepage is manually composed in PHP and uses hardcoded page paths such as `/representation/`, `/threads/`, `/events/`, and `/schedules/`.

Homepage card layouts remain a Version 1.0 release-readiness item. The current homepage uses `civic-home-latest` blocks with compact shortcode output. Listing shortcodes with pagination disabled now expose `civic-cards-home-list` for homepage preview styling.

## Sidebar Implementation

The Civic theme relies on GeneratePress sidebar behavior for non-home pages.

Theme CSS styles sidebar widgets with:

- `.sidebar .widget`
- `.sidebar .widget-title`
- `.civic-widget__item`
- `.civic-widget__view-all`

The plugin registers Civic widgets for latest consultations, latest events, and upcoming schedules. The theme styles their output but does not create the widget data.

On the homepage, the sidebar is disabled through CSS:

```css
.home .sidebar {
    display: none;
}
```

## Layout Responsibilities

Theme responsibilities:

- Overall site visual identity.
- GeneratePress header and navigation refinements.
- Homepage sections and layout.
- Inner page container polish.
- Sidebar widget presentation.
- Public card presentation overrides.
- Public statistics presentation.
- Responsive layout behavior.

Plugin responsibilities:

- Public workflow markup.
- Shortcode output.
- Data retrieval.
- Form processing.
- Routing.
- Admin screens.

The theme currently styles several plugin classes directly, including `civic-card`, `civic-form`, `civic-widget`, and `civic-stat-card`.

## Navigation

Navigation is inherited from GeneratePress.

The theme customizes:

- Header background.
- Border and shadow.
- Site title typography.
- Navigation link color and spacing.
- Active menu underline.
- Hover and current item colors.

The homepage itself also contains action links and quick-action cards that serve as prominent navigation into Civic workflows.

## Footer

The theme does not define a custom footer template. Footer rendering remains GeneratePress-owned.

The homepage includes a bottom CTA band before `get_footer()`.

## Theme-Specific Functionality

`functions.php` provides:

- `civic_is_civic_manager_user()`
- `admin_body_class` filtering to add `civic-manager-admin`
- conditional enqueue of `assets/css/civic-manager-admin.css` for civic manager users

This is separate from the plugin's own Civic admin styling, which uses the `civic-admin` and `civic-admin-page` body classes with `assets/css/civic-admin.css`, and from plugin-owned login branding in `assets/css/civic-login.css`.

## Assets

Theme image assets:

- `thomas-joseph.jpg`
- `thomas-joseph-2.jpg`
- icon images for representation, consultation, event, and schedule cards

Theme fonts:

- Inter Regular
- Inter Medium
- Inter SemiBold
- Inter Bold

The theme also contains icon assets that overlap with plugin icon assets under `assets/css/icons`.
