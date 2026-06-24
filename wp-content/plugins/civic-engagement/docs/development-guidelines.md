# Development Guidelines

## Civic Engagement & Communication Platform

This document describes the development conventions, architectural principles, coding practices, and workflow expectations for the project.

The goal is to maintain:

* consistency,
* maintainability,
* predictable workflows,
* and modular development,
  while avoiding unnecessary complexity.

This project prioritizes:

* operational simplicity,
* modularity,
* maintainability,
* and predictable workflows
  over excessive abstraction or enterprise-level architecture.

---

# Core Development Philosophy

* WordPress is primarily used as:

  * CMS,
  * authentication layer,
  * admin user management,
  * media management system.

* Operational workflows should use:

  * custom tables,
  * modular services,
  * repository-based database access.

* Avoid excessive use of:

  * postmeta,
  * procedural logic,
  * global state.

* Modules should remain:

  * isolated,
  * predictable,
  * and easy to extend.

---

# Folder Structure

## Main Structure

```text
app/

    Core/
    Helpers/
    Repositories/
    Services/
    Modules/
```

---

# Module Structure

Every module should follow a similar internal structure whenever possible.

Example:

```text
Modules/Reps/

    Admin/
    Frontend/
    Repository/
    Services/
    Templates/
```

---

# Module Responsibilities

## Modules

Modules represent business domains.

Examples:

* Reps
* Threads
* Events
* Schedules
* Users
* Activities
* Communication

Modules should own:

* business logic,
* admin/frontend handlers,
* templates,
* workflow-specific services.

---

# Repositories

Repositories handle:

* database queries,
* inserts,
* updates,
* pagination,
* filtering.

Repositories should:

* contain SQL logic,
* return structured arrays/objects,
* avoid HTML rendering.

Repositories should NOT:

* render templates,
* contain frontend logic,
* contain business workflow orchestration.

---

# Services

Services handle:

* business workflows,
* orchestration,
* coordination between repositories/modules.

Examples:

* Contact update logic
* Activity creation
* Email sending
* Schedule creation from rep/thread

---

# Controllers / Handlers

Controllers should:

* receive requests,
* validate input,
* call services,
* return responses.

Controllers should remain lightweight.

Avoid placing:

* database logic,
* heavy business logic,
  inside controllers.

---

# Templates

Templates should:

* display data only,
* avoid database queries,
* avoid business logic.

All processing should happen before rendering.

---

# Database Strategy

Operational workflows use:

* custom database tables.

Avoid:

* storing structured operational data heavily inside postmeta.

Use WordPress posts/pages only where content-oriented behavior is needed.

---

# Contact / User Strategy

This project does NOT use public user accounts.

Instead:

* email address acts as the primary public identifier.

When:

* rep submitted,
* thread response submitted,
* event registration submitted,

the system should:

1. check existing email,
2. update latest contact details,
3. preserve snapshot data within activity records.

---

# Snapshot Data Rules

Every activity table must store:

* submitted name,
* email,
* phone,
* address,
* electoral area,
  etc.

This ensures:

* historical consistency,
* activity integrity,
  even if contact data changes later.

---

# Activity Logging Rules

All major actions should create entries in:

* civic_activities

Supported activity types:

* rep
* thread_response
* event_registration

---

# Schedule Linking Rules

Schedules may originate from:

* reps
* threads

This is implemented using:

* source_type
* source_id

Important:

* schedules are NOT tightly coupled workflow objects,
* "Create Schedule" only prefills data,
* admin must manually review/edit.

---

# Frontend Admin Strategy

The system should prefer:

* frontend operational pages,
* lightweight admin interfaces,
  instead of relying fully on wp-admin.

Keep admin workflows:

* practical,
* operational,
* and simple.

Avoid:

* enterprise dashboards,
* overengineered admin systems.

---

# Coding Style

## General Rules

* Use namespaces.
* Prefer OOP architecture.
* Keep classes small and focused.
* Avoid procedural architecture for business logic.
* Avoid large God classes.

---

# Singleton Usage

Singleton pattern is allowed ONLY for:

* plugin bootstrap,
* application initialization,
* service registry if needed.

Avoid:

* singleton everywhere.

---

# Dependency Management

Prefer:

* constructor injection,
* explicit dependencies.

Avoid:

* global variables,
* hidden shared state.

---

# Naming Conventions

## Namespaces

```php
namespace CivicPlatform\Modules\Reps;
```

---

## Database Tables

Use:

```text
civic_
```

prefix.

Examples:

* civic_contacts
* civic_reps
* civic_activities

---

## PHP Classes

Use:

* PascalCase

Examples:

* RepRepository
* ContactService
* ActivityLogger

---

## Methods

Use:

* camelCase

Examples:

* findByEmail()
* createScheduleFromRep()

---

# Security Rules

All frontend/admin actions must:

* sanitize inputs,
* escape outputs,
* validate nonces,
* validate permissions,
* validate uploaded files.

Never trust frontend input directly.

---

# WordPress Practices

Use WordPress APIs whenever practical:

* wp_mail()
* wp_nonce_field()
* current_user_can()
* wp_upload_dir()

Avoid reinventing stable WP functionality.

---

# Database Practices

Use:

* $wpdb safely,
* prepared statements,
* indexed columns for searches/pagination.

Avoid:

* raw unprepared SQL,
* large unindexed searches,
* serialized relationship structures.

---

# Pagination & Search

All large listings should support:

* pagination,
* lightweight filtering,
* indexed searching.

Especially:

* contacts
* reps
* responses
* event registrations

---

# Dynamic Field Rules

Dynamic fields may be used for:

* threads
* events

Supported field types:

* text
* textarea
* dropdown
* radio
* checkbox

Field options may be stored as JSON.

---

# Communication Rules

Pilot version supports:

* email communication only.

Not included:

* SMS
* WhatsApp
* marketing automation

Grouped communication should remain:

* lightweight,
* operational,
* simple.

---

# Codex / AI Development Rules

AI tools should:

* follow existing module structure,
* avoid modifying unrelated modules,
* avoid generating raw SQL outside repositories,
* preserve separation of concerns,
* follow snapshot data rules,
* preserve centralized contact/activity logic.

AI-generated code must ALWAYS be:

* reviewed,
* tested,
* sanitized,
* and validated manually.

---

# Git Workflow

Commit frequently.

Recommended:

* one commit per stable feature/workflow.

Examples:

* contacts listing
* rep submission
* schedule creation

Avoid large unreviewed commits.

---

# Development Priorities

Recommended module development order:

1. Database layer
2. Base repository
3. Contacts module
4. Activities module
5. Reps module
6. Threads module
7. Events module
8. Schedules module
9. Frontend admin
10. Communication

---

# Current Simplifications

The current pilot version intentionally excludes:

* public user login
* SMS/WhatsApp
* advanced GIS
* workflow automation
* advanced CRM
* advanced analytics
* payment systems
* advanced moderation systems

Keep workflows simple unless explicitly expanded later.

---

# Important Principle

Practical operational clarity is more important than architectural perfection.

Prefer:

* maintainable code,
* predictable workflows,
* simple extensibility,
  over:
* unnecessary abstraction,
* enterprise patterns,
* excessive framework behavior.

---

## Slug Handling Conventions

Public-facing modules may use editable slugs for stable shareable URLs.

Rules:

- slugs should be sanitized using WordPress-compatible methods
- slugs should be lowercase
- spaces and special characters should be normalized
- slugs should remain stable after publication where possible
- frontend validation may assist users
- prefixed civic URLs use module-local uniqueness; global uniqueness is unnecessary
- repositories should provide the authoritative module-local slug checks and public slug retrieval
- frontend validation must never be treated as authoritative

## Short URL Conventions

Short URLs use a configurable prefix, currently `go`, and redirect to canonical module-prefixed slug URLs. Short-code validation belongs in `ShortUrlService`, with cross-module lookup delegated to `ShortUrlRepository`. Blank codes are stored as `NULL`; non-empty codes use lowercase letters, numbers, and hyphens and must be globally unique.

---

## Shared Civic Reference Data

Certain civic datasets may operate as shared reference tables across modules.

Examples:

- electoral areas
- event categories
- civic classifications
- future district/grouping structures

Rules:

- shared civic data should use repository access
- workflows should avoid hardcoded static arrays
- administrative CRUD systems may be postponed during pilot stages
- manually populated reference tables are acceptable during early stabilization phases
- repository/service abstraction should still be preserved for future extensibility

---

## Custom Field Display Rule

Custom field values must always be displayed with their corresponding field labels.

Field keys are storage identifiers only and must not be shown to users.

Empty custom field values should be omitted from display.

---

## Frontend Form Request Naming

All frontend forms must namespace request fields using module-specific array structures.

Examples:

civic_rep[name]
civic_rep[email]

civic_thread[name]
civic_thread[response]

civic_event[name]
civic_event[registration_data]

Avoid using raw field names directly in frontend requests, such as:
- name
- email
- category
- year
- p
- page
- author

Reason:
WordPress internally reserves several request variable names for query parsing and routing. Using raw field names may cause:
- unexpected 404 errors
- query conflicts
- permalink routing issues
- unpredictable frontend behavior

Controllers should access request data through structured arrays.

Example:

$_POST['civic_rep']['name'] ?? ''

This convention improves:
- request isolation
- frontend scalability
- validation consistency
- future extensibility
- WordPress compatibility

---

## Admin Screen Principle

Current wp-admin pages are operational implementations used to validate workflows and business rules.

Repositories, services, validation rules and permissions must remain independent of wp-admin rendering so they can be reused by future frontend administration screens.
