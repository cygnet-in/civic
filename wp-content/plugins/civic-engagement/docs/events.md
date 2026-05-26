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

## Public Event URLs

Consultations are intended to support public shareable URLs.

Examples:

- /housing
- /dub
- /transport-plan

The admin workflow should:

- suggest slug values from titles
- allow manual slug editing
- validate slug uniqueness
- preserve stable public URLs after creation where possible

Slug uniqueness must always be enforced in the repository layer.
