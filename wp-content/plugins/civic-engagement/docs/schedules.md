# Schedule Module

## Purpose

Public/private schedule and calendar management.

Separate from event registration.

Schedules may be used for:

* personal reminders,
* public announcements,
* representation follow-ups,
* consultation follow-ups,
* meetings,
* public service activities.

---

# Current Simplifications

* No recurring schedules.
* No workflow automation.
* No advanced lifecycle management.
* No notification/reminder engine.
* No team assignment system.

---

# Schedule Types

* meeting
* motion
* question
* rep_followup
* public_announcement
* other

---

# Schedule Status

* open
* pending
* scheduled
* completed
* cancelled

---

# Admin Flow

Admin can:

* create schedules,
* edit schedules,
* archive schedules,
* set public/private visibility,
* add internal comments,
* add a recent public update,
* set a simple sorting priority,
* add history notes,
* add multiple schedule images and captions,
* export the filtered schedule list as a native `.xlsx` file,
* create schedules from representations through a prefilled create form.

---

# Public Visibility Rules

* Only public schedules are visible on the frontend.
* Archived schedules are hidden from active listings.
* Private schedules remain admin-only.
* Public schedules are ordered by priority, then start date.
* The first schedule image is displayed as the listing thumbnail; detail pages show the primary image and remaining images as selectable thumbnails.
* Schedule admin export uses the shared export framework. The list page provides filtered rows, column definitions, and a timestamped filename; `ExportManager` and `XlsxExporter` generate the workbook.

---

# Active / Archived Lifecycle

This section documents the agreed business rule used by public lifecycle repository methods.

A schedule is Active when:

* it is public,
* it is not marked archived,
* and its status is one of the operational active statuses such as open, pending, or scheduled.

A schedule is Archived when:

* it is manually marked archived,
* or its status is completed or cancelled,
* or the relevant status/end date has passed according to the final Version 1.0 listing rules.

Main public schedule listings use Active schedules. `[civic_schedules_archive]` renders archived schedules with full cards and pagination by default, or a compact title/link list when `limit` is supplied.

---

# Schedule Fields

## Core Fields

* type
* title
* details
* status
* internal_comment
* recent_update
* priority
* is_public
* is_archived
* start_date
* end_date
* slug

## System Fields

* source_type
* source_id
* created_by
* created_at
* updated_at

### Notes

* source_type and source_id store the originating object for schedules created from another module.
* Representation-created schedules are also linked back from `civic_reps.schedule_id`.
* created_by is automatically populated from the logged-in user.

## Public Schedule URLs

Public schedules use `/schedule/{slug}`. Slugs are generated from titles when schedules are created, may be edited by administrators, and are unique within schedules. Public active and archived schedules resolve through this route. Numeric ID detail URLs remain temporarily supported and redirect to the canonical URL.

---

# Schedule Notes

## Purpose

Schedule notes provide a lightweight history mechanism similar to git commit comments.

They capture the reason for schedule changes without introducing a workflow engine.

## Table

`wp_civic_schedule_notes`

## Fields

* schedule_id
* note
* created_by
* created_at

## Rules

* Notes are optional.
* Notes are append-only.
* Notes are never edited.
* Notes are displayed newest first.
* Notes are not visible on public schedule listings.

## Examples

* "Meeting moved to June 20 due to council works."
* "Road closure extended by one day."
* "Venue changed to Community Hall."

---

# Internal Comment

The internal comment field stores the current private working note for the administrator.

Examples:

* "Waiting for confirmation from PWD."
* "Contractor expected to confirm next week."

Unlike Schedule Notes, the internal comment may be edited and replaced.

---

# Create From Representation / Consultation

Implemented:

* Create Schedule from Representation

The Representation detail screen links to the normal Schedule create screen with `source_type=rep` and `source_id` set to the Representation ID. The Schedule create form then preloads:

* type: `rep_followup`
* title: Representation title
* details: Representation details
* status: `pending`
* internal comment/history note identifying the source Representation by ID and title
* hidden source reference fields

Administrators must review and edit the schedule before saving.

The save process remains the normal Schedule workflow through `ScheduleEditPage`, `ScheduleService`, and `ScheduleRepository`. No Schedule is created until the administrator explicitly submits the Schedule form.

After creation, the originating Representation is linked to the created Schedule using `civic_reps.schedule_id`. The Representation internal comment receives an appended audit entry with the Schedule ID and title. If the Representation has already been converted, the create form is not shown and stale submissions are rejected by validation.

Schedule administration detail pages display the originating Representation ID and subject when the Schedule was created from a Representation. The detail table includes a "View Representation" action that links back to the Representation administration detail page.

Not yet implemented:

* Create Schedule from Consultation

---

# Future Possibilities

* recurring schedules
* reminder system
* public calendar views
* advanced workflows
* team assignments
* activity integration
* automatic archive jobs
