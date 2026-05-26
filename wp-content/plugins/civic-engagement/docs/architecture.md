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

* Email communication and grouped bulk email.

Features:

* grouped sending,
* duplicate filtering,
* basic personalization.

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
* thread
* event

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