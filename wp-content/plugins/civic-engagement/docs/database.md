# Database Structure

## Overview

The system uses custom WordPress database tables for operational workflows.

WordPress core tables are used for:

* authentication,
* CMS/pages/posts,
* media,
* admin users.

Custom tables are used for:

* reps,
* threads,
* events,
* schedules,
* contacts,
* activities,
* registrations,
* responses.

The system is relationship-driven and uses email address as the primary public identity key.

---

# Core Design Principles

* Email address is the primary unique identifier for contacts.
* Snapshot data is always stored with activities.
* Latest contact information is stored separately.
* Activities are linked using IDs.
* Frontend admin workflows depend heavily on structured relationships.
* Avoid excessive use of postmeta for operational data.

---

# Table Relationships

## Main Relationship Structure

```text
civic_contacts
    ↓
civic_activities
    ↓
reps / thread_responses / event_registrations / schedules
```

---

# Table: wp_civic_contacts

## Purpose

Stores latest known contact information.

This is NOT a login/account table.

---

## Important Rules

* Email must be unique.
* Contact details may change over time.
* Latest details are updated during new submissions.
* Historical snapshot data remains inside activity tables.

---

## Fields

| Field                 | Purpose                   |
| --------------------- | ------------------------- |
| id                    | internal contact ID       |
| email                 | primary unique identifier |
| latest_name           | latest known name         |
| latest_phone          | latest known phone        |
| latest_whatsapp       | latest known WhatsApp     |
| latest_address        | latest known address      |
| latest_eircode        | latest known Eircode      |
| latest_electoral_area | latest known area         |
| consent_email         | email contact consent     |
| consent_call          | call contact consent      |
| consent_sms           | SMS contact consent       |
| consent_post          | post contact consent      |
| consent_updated_at    | latest consent promotion  |
| created_at            | created timestamp         |
| updated_at            | last update timestamp     |

---

# Table: wp_civic_activities

## Purpose

Unified activity log for contacts.

Used for:

* user activity history,
* dashboards,
* grouped listings,
* future analytics.

---

## Activity Types

Supported types:

* rep
* thread_response
* event_registration
* schedule

---

## Important Rules

* Every rep/thread/event public participation workflow should create an activity entry.
* Schedule activity type support exists in code for schedule-related workflows.
* related_id links to actual module table.
* summary should contain lightweight readable info.

---

## Fields

| Field         | Purpose              |
| ------------- | -------------------- |
| id            | activity ID          |
| contact_id    | linked contact       |
| activity_type | rep/thread/event etc |
| related_id    | linked module row    |
| summary       | readable summary     |
| created_at    | activity timestamp   |

---

# Table: wp_civic_reps

## Purpose

Stores public representation submissions.

---

# Table: wp_civic_media

## Purpose

Stores ordered WordPress Media Library image associations for consultations, events, and schedules.

## Fields

| Field | Purpose |
| --- | --- |
| id | media association ID |
| entity_type | consultation, event, or schedule |
| entity_id | associated civic entity ID |
| attachment_id | WordPress attachment ID |
| caption | optional public image caption |
| sort_order | stable display order; first image is primary |
| created_by | administrator who added the image |
| created_at | creation timestamp |
| updated_at | last caption update timestamp |

---

## Important Rules

* Snapshot user data must remain unchanged.
* Contact latest data stored separately in civic_contacts.
* Reps remain private by default.

---

## Fields

| Field                   | Purpose              |
| ----------------------- | -------------------- |
| id                      | rep ID               |
| contact_id              | linked contact       |
| name_snapshot           | submitted name       |
| email_snapshot          | submitted email      |
| phone_snapshot          | submitted phone      |
| whatsapp_snapshot       | submitted WhatsApp   |
| address_snapshot        | submitted address    |
| eircode_snapshot        | submitted Eircode    |
| electoral_area_id       | Electoral Area ID |
| electoral_area_snapshot | submitted area       |
| title                   | rep title            |
| details                 | rep details          |
| image_attachment_id     | WordPress attachment ID for the optional representation image |
| map_lat                 | optional latitude    |
| map_lng                 | optional longitude   |
| status                  | workflow status      |
| internal_comment        | admin-only comment   |
| schedule_id             | linked Schedule created from this Representation |
| created_at              | submission timestamp |
| updated_at              | update timestamp     |

## Representation to Schedule Link

`schedule_id` stores the persistent one-to-one relationship created by the Representation to Schedule conversion workflow.

Rules:

* A Representation may link to one created Schedule.
* The linked Schedule also stores `source_type = rep` and `source_id = {representation_id}` for generic source lookup.
* Duplicate conversions should be prevented by checking the Representation `schedule_id` before creating another Schedule.
* Internal comments are audit notes only and are not the persistent relationship.

---

# Table: wp_civic_threads

## Purpose

Stores consultation thread definitions.

---

## Important Rules

* Threads define response structure.
* Actual responses stored separately.
* Public visibility controlled by admin.

---

## Fields

| Field            | Purpose                                      |
| -----------      | ------------------                           |
| id               | thread ID                                    |
| title            | thread title                                 |
| slug             | public shareable consultation URL identifier |
| short_code       | optional globally unique short URL code      |
| summary          | Listing Preview                              |
| description      | thread description                           |
| response_enabled | allow participation                          |
| is_public        | visibility                                   |
| starting_response_count | count offset added to received responses |
| created_by       | admin user                                   |
| start_date       | start date                                   |
| end_date         | end date                                     |
| status           | draft/published/closed                       |
| created_at       | created timestamp                            |
| updated_at       | updated timestamp                            |

---

# Table: wp_civic_thread_fields

## Purpose

Stores configurable response fields for threads.

---

## Supported Field Types

* text
* textarea
* dropdown
* radio
* checkbox

---

## Important Rules

* Field options may be stored as JSON.
* sort_order controls display order.

---

## Fields

| Field         | Purpose       |
| ------------- | ------------- |
| id            | field ID      |
| thread_id     | linked thread |
| field_label   | label         |
| field_key     | field Key     |
| field_type    | type          |
| field_options | JSON options  |
| sort_order    | display order |
| is_required   | validation    |
| created_at    | timestamp     |

---

# Table: wp_civic_thread_responses

## Purpose

Stores public thread responses.

---

## Important Rules

* Snapshot contact data required.
* response_data stored as JSON.
* Public visibility controlled separately.

---

## Fields

| Field                   | Purpose           |
| ----------------------- | ----------------- |
| id                      | response ID       |
| thread_id               | linked thread     |
| contact_id              | linked contact    |
| name_snapshot           | submitted name    |
| email_snapshot          | submitted email   |
| phone_snapshot          | submitted phone   |
| address_snapshot        | submitted address |
| eircode_snapshot        | submitted Eircode |
| electoral_area_id       | Electoral Area ID |
| electoral_area_snapshot | submitted area    |
| response_data           | JSON response     |
| is_public               | public visibility |
| created_at              | timestamp         |

---

# Table: wp_civic_events

## Purpose

Stores event definitions.

---

## Important Rules

* Separate from schedules.
* Events are registration-oriented.

---

## Fields

| Field                | Purpose                                      |
| -----------          | -------------                                |
| id                   | event ID                                     |
| title                | event title                                  |
| slug                 | public shareable event URL identifier        |
| short_code           | optional globally unique short URL code      |
| summary              | Summary of the event                         |
| description          | details                                      |
| location             | location                                     |
| start_date           | start                                        |
| end_date             | end                                          |
| is_public            | visibility                                   |
| registration_enabled | To allow deny public registrations           |
| status               | active/closed                                |
| created_at           | timestamp                                    |
| updated_at           | timestamp                                    |

---

# Table: wp_civic_event_fields

## Purpose

Stores configurable event registration fields.

Structure similar to civic_thread_fields.

---

# Table: wp_civic_event_registrations

## Purpose

Stores event registrations.

---

## Important Rules

* Snapshot user data required.
* Dynamic registration data stored as JSON.

---

## Fields

| Field                   | Purpose           |
| ----------------------- | ----------------- |
| id                      | registration ID   |
| event_id                | linked event      |
| contact_id              | linked contact    |
| name_snapshot           | submitted name    |
| email_snapshot          | submitted email   |
| phone_snapshot          | submitted phone   |
| address_snapshot        | submitted address |
| eircode_snapshot        | submitted Eircode |
| electoral_area_id       | Electoral Area ID |
| electoral_area_snapshot | submitted area    |
| registration_data       | JSON registration |
| created_at              | timestamp         |

---

# Table: wp_civic_schedules

## Purpose

Stores public/private schedules and calendar entries.

---

## Important Rules

* Public schedules visible from start date.
* Expired schedules move to archive.
* Schedule may originate from rep/thread.

---

## Source Linking

Optional source reference:

* source_type
* source_id

For schedules created from a Representation, the source reference is paired with `wp_civic_reps.schedule_id`.

Examples:

* rep
* thread

---

## Fields

| Field            | Purpose                  |
| ---------------- | ------------------------ |
| id               | schedule ID              |
| type             | schedule type            | ENUM(    'meeting',    'motion',    question',    'rep_followup',    'public_announcement',    'other')
| title            | heading                  |
| slug             | public shareable schedule URL identifier |
| short_code       | optional globally unique short URL code |
| details          | details                  |
| status           | status                   | ENUM ('open',    'pending',    'scheduled',    'completed',    'cancelled')
| internal_comment | admin-only comment       |
| recent_update    | public-facing latest update |
| priority         | sort priority             |
| is_public        | visibility               |
| start_date       | start                    |
| end_date         | end                      |
| is_archived      | Archived or not          |
| source_type      | rep/thread               |
| source_id        | linked item              |
| created_by       | admin user               |
| created_at       | timestamp                |
| updated_at       | timestamp                |

---

# Table: wp_civic_schedule_notes

## Purpose

To record the reason for edits in wp_civic_schedules (optional) like git commit comment

## Source Linking

With the id of schedule table

## Fields

|id          | ID                               |
|schedule_id | Foreign key to wp_civic_schedules.id |
|note        | Note                             |
|created_by  | admin user                       |
|created_at  | timestamp                        |

# Index Recommendations

Recommended indexes:

## civic_contacts

* UNIQUE(email)
* INDEX(consent_email)
* INDEX(consent_call)
* INDEX(consent_sms)
* INDEX(consent_post)

## civic_media

* INDEX(entity_type, entity_id, sort_order)
* INDEX(attachment_id)

## civic_activities

* INDEX(contact_id)
* INDEX(activity_type)

## civic_reps

* INDEX(contact_id)

## civic_thread_responses

* INDEX(contact_id)
* INDEX(thread_id)

## civic_event_registrations

* INDEX(contact_id)
* INDEX(event_id)

## civic_schedules

* INDEX(start_date)
* INDEX(is_public)
* INDEX(priority, start_date)

---

# Future Expansion Possibilities

Possible future additions:

* communication logs
* SMS logs
* WhatsApp logs
* advanced CRM
* GIS tables
* audit/history tables
* notification queue system

---

# Important Development Notes

* Use WordPress $wpdb safely.
* Sanitize all inputs.
* Escape outputs.
* Use pagination for listings.
* Avoid large unindexed searches.
* Prefer explicit relationships over serialized structures.

---

## Slug Rules

Public civic entities may use editable slugs for public-facing shareable URLs.

Canonical public URLs use module prefixes:

- `/consultation/{slug}`
- `/event/{slug}`
- `/schedule/{slug}`

Rules:

- slugs must be unique within their own module table
- slugs are initially generated from titles and administrators may edit event and schedule slugs
- slugs should be lowercase, with spaces and special characters normalized
- public slug lookups must enforce each module's visibility rules
- numeric ID detail URLs remain supported temporarily and redirect permanently to their canonical slug URL
- global slug uniqueness and root-level slug routing are not implemented

## Short URL Rules

Short URLs redirect permanently to their canonical slug URL. The default prefix is `/go/`, with a filter-ready implementation for future configuration.

- short codes are optional and stored as `NULL` when blank
- codes may contain only lowercase letters, numbers, and hyphens
- non-empty codes must be globally unique across consultations, events, and schedules
- invalid or unavailable short URLs return a normal 404 response

---

# Electoral Areas

The platform may use a shared electoral area reference table for civic workflows.

Initial implementation uses:

```text
wp_civic_electoral_areas
```

This table is intended to support:

- representations
- consultation responses
- event participation
- future civic reporting workflows

The initial pilot implementation uses manually managed database records.

Administrative CRUD management for electoral areas is intentionally postponed until workflow requirements stabilize.

---

## Suggested Fields

| Field | Purpose |
|---|---|
| id | primary key |
| name | public display name |
| slug | optional future routing identifier |
| is_active | operational visibility |

---

## Architectural Notes

Electoral areas are treated as shared civic reference data.

Application code should:

- use repository/service lookup
- avoid hardcoded PHP arrays
- preserve snapshot values where operationally required

The pilot implementation intentionally avoids:

- hierarchy management
- geographic mapping
- GIS integration
- constituency nesting
- admin CRUD interfaces
- import/export systems

