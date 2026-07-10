# Events Module

## Purpose

Public event registration system.

Examples:

* meetings,
* workshops,
* consultations,
* volunteer events.

---

# Current Simplifications

* No payment gateway.
* No QR attendance system.
* No waitlist workflow.

---

# Admin Flow

Admin can:

* create events,
* configure registration fields,
* export participants,
* close/unpublish events.
* add multiple event images and captions.

---

# Public User Flow

1. User opens event registration form.
2. User enters contact details.
3. User fills dynamic registration fields.
4. User submits registration.

---

# System Behavior

1. Check email in civic_contacts.
2. Update/create contact.
3. Store snapshot data in civic_event_registrations.
4. Create civic_activities entry.

---

# Dynamic Field Examples

* age group
* food preference
* volunteer category
* accessibility requirements

---

# Future Possibilities

* QR attendance,
* ticketing,
* payment gateway,
* automated reminders.

---

# Active / Archived Lifecycle

This section documents the agreed business rule used by public lifecycle repository methods.

An event is Active when:

* it is public,
* it is published/active,
* and the current date is before or on the event end date when an end date is set.

An event is Archived when:

* the event end date has passed,
* or the event status is closed,
* or the event is no longer public.

Main public event listings use Active events. `[civic_events_archive]` renders archived events with full cards and pagination by default, or a compact title/link list when `limit` is supplied.

Archived events remain publicly viewable, but they are read-only. The detail page hides the registration form once the event is no longer Active, and the registration workflow rejects direct submissions after closure.

---

## Public Event URLs

Events support public shareable URLs using `/event/{slug}`.

The admin workflow should:

- suggest slug values from titles
- allow manual slug editing
- validate slug uniqueness within events
- preserve stable public URLs after creation where possible

Only public published or closed events resolve through public slug URLs. Numeric ID detail URLs are retained temporarily and redirect to the canonical URL.

---

## Event Registrations

Registrations are accepted only while the event is public, published, registration-enabled, and not past its configured end date. Closed or archived events display a closed message instead of the public registration form.
