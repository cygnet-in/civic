# PROJECT-BRIEF.md

## Civic Engagement Platform

**Version:** 1.0
**Status:** Feature Complete Beta (Core Platform)

---

# Project Overview

The Civic Engagement Platform is a modular WordPress plugin developed to help elected representatives engage effectively with their communities.

The platform enables residents to:

* Submit community representations.
* Participate in public consultations.
* Register for events.
* View progress updates on community issues.
* Follow schedules and announcements.

The platform is intended to serve as a long-term civic engagement solution rather than a political campaign website.

---

# Project Objectives

The primary objectives are:

* Improve communication between elected representatives and constituents.
* Increase public participation.
* Track community issues from submission through progress updates.
* Provide a transparent record of consultations, events and actions.
* Deliver a modern, professional and easy-to-use civic platform.

---

# Core Modules

The following modules are implemented.

## Public Participation

* Representations
* Consultations
* Consultation Responses
* Events
* Event Registrations
* Schedules
* Contacts

## Shared Services

* Media Management
* Contact Management
* Consent Recording
* Dashboard
* Authentication
* Short URL Routing
* Canonical Slug Routing

---

# Major Features Completed

* Multiple image support with captions.
* WordPress Media Library integration.
* Civic Dashboard.
* Public statistics shortcode.
* Custom Civic administration experience.
* Canonical slug routing.

Examples:

/consultation/{slug}

/event/{slug}

/schedule/{slug}

* Short URL routing.

Examples:

/go/{short_code}

The prefix is architected to be configurable in the future.

* Contact management.
* Communication consent recording.
* Media gallery support.
* Dashboard statistics.
* Administrative status management.
* Custom account menu.
* Password change.
* Proper logout behaviour.

---

# Important Architectural Decisions

## URL Strategy

Canonical URLs:

/consultation/{slug}

/event/{slug}

/schedule/{slug}

Short URLs redirect permanently to canonical URLs.

Root-level routing has intentionally been avoided because of potential conflicts with WordPress pages, posts, categories and plugins.

---

## Media Storage

Images are stored in the standard WordPress Media Library.

Only attachment IDs are stored within Civic Platform tables.

Deleting an image from a module removes only the association, not the Media Library asset.

---

## Contact Management

Contacts are matched primarily by email address.

Consent status is recorded.

The platform currently supports:

* Consent recording
* Contact filtering
* Contact export

The following are intentionally outside the current scope:

* Bulk email campaigns
* SMS campaigns
* Campaign management
* Unsubscribe workflow

Dedicated communication platforms such as Brevo are recommended for these functions.

---

## Administration

The platform provides a customised Civic administration interface built on top of the standard WordPress administration.

WordPress remains the underlying framework while the user experience is simplified for Civic users.

---

# Technology

* WordPress Plugin
* PHP
* MySQL
* Modular Architecture
* Repository Pattern
* Service Layer
* Shared Components
* WordPress Media Library
* WordPress Authentication

---

# Coding Principles

Future development should:

* Reuse existing repositories and services whenever possible.
* Avoid duplicated business logic.
* Prefer shared services over module-specific implementations.
* Preserve backward compatibility where practical.
* Follow the established modular architecture.
* Keep modules loosely coupled.
* Document all schema changes.
* Provide migration scripts for database updates.

---

# Current Priorities

The current focus of development is:

1. Public website UI/UX refinement.
2. Branding and design system.
3. Client-requested enhancements.
4. Performance optimisation.
5. Testing and documentation.
6. Version 1.0 release readiness tasks documented in `docs/release-readiness.md`.

Pending Version 1.0 work includes final homepage/card/sidebar polish, final admin UI polish, and the sidebar representation prompt widget documented in `docs/release-readiness.md`.

---

# Future Enhancements

Potential future enhancements include:

* Configurable Short URL prefix.
* Civic design system.
* Enhanced public homepage.
* Email platform integration.
* QR code generation for short URLs.
* Analytics for public participation.
* Advanced reporting.
* Mobile application support.

---

# Reference Documents

This document provides a high-level overview only.

Detailed information is available in:

* ARCHITECTURE.md
* PROJECT-STATUS.md
* DATABASE.md
* DEVELOPMENT-GUIDELINES.md
* SCOPE-UPDATE-2026-06.md

These documents should always be consulted before making architectural or schema changes.
