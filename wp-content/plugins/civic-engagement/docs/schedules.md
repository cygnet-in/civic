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
* create schedules from representations or consultations in the future.

---

# Public Visibility Rules

* Only public schedules are visible on the frontend.
* Archived schedules are hidden from active listings.
* Private schedules remain admin-only.
* Public schedules are ordered by priority, then start date.
* The first schedule image is displayed as the listing thumbnail; detail pages show the primary image and remaining images as selectable thumbnails.

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

## System Fields

* source_type
* source_id
* created_by
* created_at
* updated_at

### Notes

* source_type and source_id are reserved for future "Create Schedule from Representation/Consultation" functionality.
* created_by is automatically populated from the logged-in user.

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

Future functionality may allow:

* Create Schedule from Representation
* Create Schedule from Consultation

The system will prefill schedule details using source_type and source_id.

Administrators must review and edit the schedule before saving.

---

# Future Possibilities

* recurring schedules
* reminder system
* public calendar views
* advanced workflows
* team assignments
* activity integration
* automatic archive jobs
