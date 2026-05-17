# Civic Engagement & Communication Platform

This project is a modular WordPress-based civic engagement and communication platform developed as a custom plugin.

The system focuses on:

* operational simplicity,
* maintainability,
* modular architecture,
* predictable workflows,
* and lightweight civic interaction management.

Avoid unnecessary enterprise complexity or overengineering.

---

# Important Documentation

Always refer to the following project documentation before implementing major changes.

## Core Documents

* docs/development-guidelines.md
* docs/architecture.md
* docs/database.md

---

# Module Workflow Documents

* docs/reps.md
* docs/threads.md
* docs/events.md
* docs/schedules.md
* docs/contacts.md
* docs/communication.md
* docs/admin.md

These files describe:

* workflow expectations,
* business rules,
* simplifications,
* operational assumptions,
* and module-specific behavior.

---

# Core Architectural Principles

* Use modular architecture.
* Keep modules isolated.
* Use custom tables for operational data.
* Avoid excessive use of postmeta for workflow data.
* Email is the primary public identity key.
* Snapshot data must remain stored with activities.
* Use repositories for database access.
* Use services for business workflows.
* Keep controllers lightweight.
* Avoid business logic inside templates.

---

# Folder Structure Rules

Main structure:

```text
app/

    Core/
    Helpers/
    Repositories/
    Services/
    Modules/
```

Every module should follow similar structure:

```text
Modules/Reps/

    Admin/
    Frontend/
    Repository/
    Services/
    Templates/
```

---

# Database Rules

* All SQL logic belongs inside repositories.
* Use $wpdb safely with prepared statements.
* Avoid raw SQL in controllers/templates.
* Use indexes for searchable/paginated listings.
* Preserve snapshot data in activity tables.

---

# Contact & Activity Rules

This system does NOT use public user accounts.

Instead:

* email acts as the primary identity field.

When reps/thread responses/event registrations are submitted:

1. Check existing email.
2. Update latest contact details.
3. Preserve submitted snapshot data.
4. Create activity entry.

All major actions should create entries in:

* civic_activities

---

# Schedule Rules

Schedules may originate from:

* reps
* threads

"Create Schedule" means:

* prefill schedule data only,
* admin must manually review/edit before save.

Avoid implementing tightly coupled workflow automation unless explicitly requested.

---

# Frontend Admin Rules

The system should prefer:

* frontend operational pages,
* lightweight admin workflows,
  instead of relying fully on wp-admin.

Avoid:

* enterprise dashboards,
* overcomplicated admin systems.

---

# Security Rules

Always:

* sanitize inputs,
* escape outputs,
* validate nonces,
* validate permissions,
* validate uploaded files.

Never trust frontend input directly.

---

# WordPress Development Rules

* Use namespaces.
* Prefer OOP architecture.
* Use singleton only for bootstrap/init.
* Avoid procedural architecture for business logic.
* Prefer constructor injection over globals.
* Keep classes focused and small.

---

# Codex / AI Development Rules

Before implementing features:

* review related module documentation,
* preserve module boundaries,
* avoid modifying unrelated modules.

Do NOT:

* generate raw SQL outside repositories,
* bypass services/workflow logic,
* place business logic inside templates,
* tightly couple unrelated modules.

AI-generated code must always be:

* reviewed,
* tested,
* and manually validated.

---

# Current Simplifications

The pilot version intentionally excludes:

* public login/signup
* SMS/WhatsApp integration
* advanced GIS
* workflow automation engines
* advanced CRM features
* payment systems
* advanced analytics
* advanced moderation systems

Keep workflows simple unless explicitly expanded later.

---

# Development Priorities

Recommended implementation order:

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

# Important Principle

Prefer:

* maintainable code,
* predictable workflows,
* practical operational simplicity

over:

* excessive abstraction,
* enterprise architectural patterns,
* or unnecessary framework behavior.
