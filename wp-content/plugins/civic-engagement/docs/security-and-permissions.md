# Security and Permissions Architecture

## Purpose

This document defines the security, role, and permission conventions used throughout the Civic Engagement Platform.

The platform uses native WordPress roles and capabilities combined with plugin-managed permission checks.

The goal is to keep the system:

- lightweight
- maintainable
- modular
- operationally secure
- independent from third-party role/capability plugins

---

# Role Strategy

## administrator

The default WordPress administrator role retains full access to:

- WordPress administration
- plugin settings
- all civic platform modules
- future system administration tools

Administrators bypass normal civic capability restrictions.

---

## civic_manager

A custom operational role intended for:

- councillors
- office staff
- authorized civic administrators

This role should have access only to operational civic modules and workflows.

The role is managed internally by the plugin.

---

# Capability Strategy

The platform uses native WordPress capabilities.

Capabilities must be:

- registered internally by the plugin
- checked explicitly in code
- enforced at both menu and action level

Third-party role/capability plugins are not required for normal platform operation.

---

# Initial Core Capabilities

## Representations

- manage_civic_reps

Access to:

- rep listing
- rep detail pages
- rep status updates
- rep exports

---

## Threads / Consultations

- manage_civic_threads

Access to:

- consultation threads
- response moderation
- consultation management

---

## Events

- manage_civic_events

Access to:

- event creation
- registration management
- registration exports

---

## Schedules / Calendar

- manage_civic_schedules

Access to:

- public schedules
- private schedules
- schedule publishing
- archive management

---

## Activities

- view_civic_activities

Access to:

- activity logs
- operational audit views
- relationship tracking

---

## Settings

- manage_civic_settings

Access to:

- platform configuration
- future notification settings
- future integration settings
- CAPTCHA / Turnstile security settings

---

# Security Architecture Principles

## Principle 1: Explicit Capability Checks

All protected operations must verify permissions using:

current_user_can()

Examples:

- admin pages
- exports
- workflow actions
- moderation actions
- status updates

Do not rely only on hidden menus.

# Principle 2: Module-Oriented Permissions

Permissions should remain module-oriented.

Examples:

- manage_civic_reps
- manage_civic_events

Avoid premature granular permissions unless operationally necessary.

# Principle 3: Frontend and Backend Separation

Frontend citizen workflows must remain independent from wp-admin permissions.

Public submissions:

- do not require login
- do not use admin capabilities
- must use nonce validation
- must sanitize all request data

# Principle 4: Backend Security

All admin actions must:

- verify capability
- validate request data
- validate nonce when appropriate
- avoid direct SQL execution outside repositories

# CAPTCHA Architecture

Version 1.0 introduces shared CAPTCHA infrastructure using Cloudflare Turnstile.

Implementation:

- `CaptchaService` renders the Turnstile widget for Civic frontend forms.
- `CaptchaService` verifies submitted Turnstile tokens server-side.
- `CivicSettingsService` reads and writes the shared Security settings.
- `SecuritySettingsPage` provides the Civic Manager admin configuration screen.

Configured settings:

- Enable CAPTCHA
- Cloudflare Site Key
- Cloudflare Secret Key

Representation submission, Consultation response, and Event registration forms integrate CAPTCHA by rendering the shared widget and calling the shared validation API before processing submissions. Provider-specific rendering and verification logic remains centralized in `CaptchaService`.

# Principle 5: Centralized Permission Management

Roles and capabilities should be registered centrally.

Recommended location:

app/Core/Capabilities.php

Responsibilities:

- role creation
- capability assignment
- future capability migrations

# Admin Menu Convention

All admin menus should:

- remain module-oriented
- use capability checks
- use dedicated admin controllers
- avoid procedural admin page logic

Recommended structure:

Civic Platform
 ├── Representations
 ├── Threads
 ├── Events
 ├── Schedules
 ├── Activities
 └── Settings

# Admin Controller Convention

Admin functionality should remain separated from frontend workflows.

Recommended structure:

Modules/Reps/Admin/
Modules/Threads/Admin/
Modules/Events/Admin/

Avoid mixing:

- frontend controllers
- admin controllers
- workflow services

# Export Security

All exports must:

- verify capability
- sanitize filters
- avoid unrestricted data exposure
- remain admin-only operations
- use the shared export framework where practical
- let modules provide rows, column definitions and filenames rather than streaming ad hoc files

# Contact Activity Tracking

The activity system tracks civic participation activities associated with contacts.

Examples:

- rep submission
- consultation response
- event registration

The activity table is intended for:

- contact participation history
- civic interaction tracking
- relationship inspection
- operational visibility

Administrative or system-level actions are not part of this module.

Examples not tracked here:

- consultation creation
- schedule publishing
- admin configuration changes

# Future Expansion Notes

The current permission model is intentionally lightweight.

Granular permissions may be introduced later only if:

- multiple staff hierarchies emerge
- operational complexity increases
- multi-constituency deployment is required

Avoid premature ACL complexity.

# Architectural Philosophy

The Civic Engagement Platform is designed as:

- operational civic workflow software
- not a generic CMS extension
- not a public social network
- not a highly dynamic editorial system

# The security architecture prioritizes:

- clarity
- maintainability
- explicit permission checks
- predictable operational workflows
