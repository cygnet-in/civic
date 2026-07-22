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
* export the filtered consultation list as a native `.xlsx` file.
* export the filtered consultation response list as a native `.xlsx` file.

New consultations are created as Draft. Administrators should add response fields before publishing from the Edit screen.

Consultation Start Date and End Date are managed as date-only admin fields using native browser date inputs. Submitted values are validated as `Y-m-d` and converted to MySQL datetime format (`Y-m-d 00:00:00`) before persistence.

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
3. User completes the consultation-specific Custom Fields.
4. User may optionally make response public.
5. When CAPTCHA is enabled, user completes the shared Turnstile challenge.
6. User submits response.

---

# System Behavior

1. Confirm the consultation is still accepting responses.
2. Confirm the consultation has at least one configured response field.
3. When CAPTCHA is enabled, validate the submitted Turnstile token before processing the response.
4. Check email in civic_contacts.
5. Update/create contact.
6. Store snapshot data in civic_thread_responses.
7. Create civic_activities entry.

---

# Public Visibility Rules

* Admin sees all details.
* Public display may show:

  * name
  * area
  * comment
* Email/phone/address remain private.
* Displayed response count is the starting response count plus actual submitted response records.
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

Responses are accepted only while the consultation is published/public, response-enabled, not past its configured end date, and has at least one configured response field. Closed or archived consultations display a closed message instead of the public response form. Consultations without response fields display a configuration message and reject direct submissions.

When CAPTCHA is enabled, the response form renders the shared Cloudflare Turnstile widget through `CaptchaService` and validates the submitted token before calling the response workflow.

Consultation and consultation response admin exports use the shared export framework. Admin list pages provide the active search/context filters, row data, column definitions, and timestamped filenames; `ExportManager` and `XlsxExporter` generate the workbooks.

Consultation response admin search matches response snapshot/data fields and the parent Consultation Title through the repository query.

---

## Consultation Custom Fields

Consultation-specific input is collected exclusively through Custom Fields. The public response form no longer includes a built-in free-text Response textarea.

A consultation that accepts public responses must have at least one configured response field. Administrators should create the consultation as a draft, configure its fields, then publish it with public responses enabled.

Supported field types (initial version):

- text
- textarea
- select

Field definitions belong to the consultation.

Submitted custom field values are stored within `response_data['custom_fields']` and become immutable after submission. Historical responses that contain `response_data['response_text']` remain supported for display.
