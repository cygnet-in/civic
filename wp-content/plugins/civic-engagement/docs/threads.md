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

---

# Admin Flow

Admin can:

* create thread,
* configure response fields,
* publish/unpublish,
* configure a starting response count.
* add multiple consultation images and captions.

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
3. User may optionally make response public.
4. When CAPTCHA is enabled, user completes the shared Turnstile challenge.
5. User submits response.

---

# System Behavior

1. Confirm the consultation is still accepting responses.
2. When CAPTCHA is enabled, validate the submitted Turnstile token before processing the response.
3. Check email in civic_contacts.
4. Update/create contact.
5. Store snapshot data in civic_thread_responses.
6. Create civic_activities entry.

---

# Public Visibility Rules

* Admin sees all details.
* Public display may show:

  * name
  * area
  * comment
* Email/phone/address remain private.
* Displayed response count is the starting response count plus actual submitted responses.
* Public response rendering is disabled by default.
* The first consultation image is displayed as the listing thumbnail; detail pages show the primary image and remaining images as selectable thumbnails.

---

# Active / Archived Lifecycle

This section documents the agreed business rule used by public lifecycle repository methods.

A consultation is Active when:

* it is published/public,
* it is accepting responses,
* and the current date is on or before the consultation end date when an end date is set.

A consultation is Archived when:

* it is no longer accepting responses,
* or its end date has passed,
* or it is otherwise closed by an administrator.

Main public consultation listings use Active consultations. `[civic_threads_archive]` renders archived consultations with full cards and pagination by default, or a compact title/link list when `limit` is supplied.

Archived consultations remain publicly viewable, but they are read-only. The detail page hides the response form and "Have Your Say" action once the consultation is no longer Active, and the response submission workflow rejects direct submissions after closure.

---

# Future Possibilities

* threaded discussions,
* moderation queue,
* advanced public visibility control.

---

## Public Consultation URLs

Consultations support public shareable URLs using `/consultation/{slug}`.

The admin workflow should:

- suggest slug values from titles
- generate slugs from titles during creation
- validate slug uniqueness within consultations
- preserve stable public URLs after creation where possible

Numeric ID detail URLs are retained temporarily and redirect to the canonical URL. Consultation slugs remain stable after creation.

---

## Consultation Responses

Consultation responses may optionally capture electoral area information.

Electoral area values should originate from shared civic reference data where possible.

The initial implementation may use manually managed electoral area records while preserving repository abstraction for future extensibility.

Responses are accepted only while the consultation is published/public, response-enabled, and not past its configured end date. Closed or archived consultations display a closed message instead of the public response form.

When CAPTCHA is enabled, the response form renders the shared Cloudflare Turnstile widget through `CaptchaService` and validates the submitted token before calling the response workflow.

---

## Consultation Custom Fields

Consultations may define additional response fields.

Supported field types (initial version):

- text
- textarea
- select

Field definitions belong to the consultation.

Submitted values are stored within response_data and become immutable after submission.
