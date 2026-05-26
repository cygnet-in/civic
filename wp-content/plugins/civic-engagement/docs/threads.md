# Threads Module

## Purpose

Public consultation/discussion system.

Examples:

* traffic proposals,
* local development consultation,
* public opinion collection.

---

# Current Simplifications

* No forum-style replies.
* No advanced moderation workflow.
* CAPTCHA used instead of strict response restriction.

---

# Admin Flow

Admin can:

* create thread,
* configure response fields,
* publish/unpublish,
* export responses,
* create schedule from thread.

---

# Supported Field Types

* text
* textarea
* dropdown
* radio
* checkbox

---

# Public User Flow

1. User opens thread.
2. User enters contact details.
3. User submits response.
4. User may optionally make response public.

---

# System Behavior

1. Check email in civic_contacts.
2. Update/create contact.
3. Store snapshot data in civic_thread_responses.
4. Create civic_activities entry.

---

# Public Visibility Rules

* Admin sees all details.
* Public display may show:

  * name
  * area
  * comment
* Email/phone/address remain private.

---

# Future Possibilities

* threaded discussions,
* moderation queue,
* advanced public visibility control.

---

## Public Consultation URLs

Consultations support public shareable URLs.

Examples:

- /housing
- /dub
- /community-meeting

The admin workflow should:

- suggest slug values from titles
- allow manual slug editing
- validate slug uniqueness
- preserve stable public URLs after creation where possible

Slug validation and uniqueness are managed centrally through SlugService.

---

## Consultation Responses

Consultation responses may optionally capture electoral area information.

Electoral area values should originate from shared civic reference data where possible.

The initial implementation may use manually managed electoral area records while preserving repository abstraction for future extensibility.