# Civic Engagement & Communication Platform

## Architecture Notes

This document describes the basic architecture and development conventions for the project.

The project is a WordPress-based civic engagement and communication platform developed using a custom plugin architecture.

The goal is to keep the system:

* practical,
* modular,
* maintainable,
* and easy to extend later.

---

# Core Principles

* WordPress is used mainly for CMS/content management and authentication.
* Civic workflows and operational data use custom database tables.
* Frontend operational/admin pages are preferred instead of relying fully on wp-admin.
* Email address is used as the primary unique identifier for contacts/users.
* Every rep, thread response, and event registration stores a snapshot of submitted user details.
* A centralized contact/activity structure is maintained separately.

---

# Main Modules

## 1. Reps Module

Purpose:

* Public representation/issue submissions.

Features:

* form submission,
* photo upload,
* optional map pin,
* admin listing,
* export,
* create schedule from rep.

---

## 2. Threads Module

Purpose:

* Public consultations/discussions.

Features:

* configurable response forms,
* public/private response display,
* admin moderation,
* response export,
* create schedule from thread.

---

## 3. Events Module

Purpose:

* Event registration system.

Features:

* registration forms,
* participant export,
* grouped communication.

---

## 4. Schedule Module

Purpose:

* Public/private schedules/calendar entries.

Features:

* start/end dates,
* automatic archive,
* comments/updates,
* status tracking.

---

## 5. User & Activity Module

Purpose:

* Centralized contact/activity management.

Features:

* user/contact records,
* activity history,
* grouped interactions,
* user activity listing.

---

## 6. Communication Module

Purpose:

* Future email communication and grouped contact workflows.

Current implementation:

* There is no standalone Communication module in source.
* Communication-related implementation currently consists of contact consent capture, cumulative consent storage, consent filtering and contact export.
* Grouped email sending, campaign management and unsubscribe workflows are not implemented.

---

# User Identification Logic

Email address is treated as the primary unique identifier.

Workflow:

1. User submits rep/thread/event form.
2. System checks whether email already exists.
3. If exists:

   * update latest contact details.
4. If not:

   * create new contact record.
5. Store snapshot of submitted details within activity table.

---

# Activity Types

Supported activity types:

* rep
* thread_response
* event_registration
* schedule

Public submission workflows currently create activities for representations, consultation responses and event registrations. Schedule activity support exists in shared activity code, but schedule creation is still an administrative workflow.

---

# Frontend Admin Strategy

The system should use:

* frontend operational pages,
* lightweight admin dashboards,
* custom tables/views,
  instead of relying completely on wp-admin.

Supported roles:

* Main Admin
* Staff/Admin User

---

# Database Design Principles

* Use custom tables for operational data.
* tables use WordPress prefix + civic_ prefix.
* Avoid excessive use of postmeta for structured workflows.
* Store snapshot data for historical consistency.
* Use relationships through IDs rather than serialized structures.

---

# Naming Conventions

Database Tables:

* wp_civic_contacts
* wp_civic_activities
* wp_civic_reps
* wp_civic_threads
* wp_civic_thread_responses
* wp_civic_events
* wp_civic_event_registrations
* wp_civic_schedules

Function Prefix:

* civic_

Option Prefix:

* civic_

---

# Security Principles

* Sanitize all inputs.
* Escape all outputs.
* Use WordPress nonce validation.
* Use capability checks for admin operations.
* Never trust frontend input directly.

---

# Development Approach

* Develop module-by-module.
* Commit frequently using Git.
* Keep workflows simple unless explicitly required.
* Avoid overengineering.
* Focus on operational usability.

---

# Future Expansion Possibilities

Possible future additions:

* SMS/WhatsApp integration
* Advanced GIS
* Multi-councillor support
* Advanced CRM features
* Advanced reporting/analytics
* Workflow automation

---

# Shared Civic Reference Data

The platform may use shared civic reference datasets across modules.

Examples include:

- electoral areas
- civic categories
- consultation metadata
- future geographic structures

Reference datasets are intended to remain lightweight during early platform stages.

The initial implementation prioritizes:

- workflow stability
- repository abstraction
- operational simplicity

over:

- complex management interfaces
- GIS systems
- hierarchical geography engines

---

# Current Public UI Architecture

The current public UI is shortcode-driven.

Implemented public shortcodes include:

- `civic_rep_form`
- `civic_rep_detail`
- `civic_threads`
- `civic_thread_detail`
- `civic_events`
- `civic_event_detail`
- `civic_schedules`
- `civic_schedule_detail`
- `civic_statistics`

Public list views for consultations, events and schedules use shared Civic Card markup with `civic-card`, `civic-list-card`, and `civic-cards-main-list`.

Public forms use the shared Civic Form design system with `civic-form`, `civic-form__title`, `civic-form__form`, `civic-form__field`, `civic-form__field--full`, `civic-form__consent`, and `civic-form__actions`.

The Civic theme owns homepage composition, branding, page layout, and public presentation refinements. The plugin owns workflows, shortcode rendering, repositories, services, routing, and admin screens.

---

# Current Routing Decisions

Canonical public routes are module-prefixed:

- `/consultation/{slug}`
- `/event/{slug}`
- `/schedule/{slug}`

Root-level public slug routing is not implemented.

Slug uniqueness is module-local. Short URL codes are globally checked across consultations, events and schedules.

Short URLs use `/go/{short_code}` by default and redirect permanently to the canonical slug URL when valid.

---

# Release Readiness Source of Truth

Remaining work before Version 1.0 and public Active/Archived lifecycle rules are documented in:

- `docs/release-readiness.md`

Those lifecycle rules define how consultations, events and schedules should distinguish Active items from Archived items in future listing implementations.
