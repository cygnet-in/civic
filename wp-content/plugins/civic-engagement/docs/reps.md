# Reps Module

## Purpose

Public representation/issue submission system.

Users can submit civic issues, requests, or complaints.

---

# Current Simplifications

* No citizen login.
* No ticket tracking.
* No public dashboard.
* Lightweight map support only.
* Contact snapshots remain private.

---

# Public User Flow

1. User opens rep form.
2. User enters:

   * name
   * email
   * phone
   * WhatsApp number (optional)
   * address
   * Eircode
   * electoral area
   * title
   * details
3. User optionally uploads image.
4. User optionally selects map location.
5. When CAPTCHA is enabled, user completes the shared Turnstile challenge.
6. User submits form.

---

# System Behavior

1. When CAPTCHA is enabled, validate the submitted Turnstile token before processing the representation.

2. Check whether email exists in civic_contacts.

3. If exists:

   * update latest contact details.

4. If not:

   * create new contact.

5. Store snapshot data in civic_reps.

6. If an image is provided, create one WordPress Media Library attachment and store its attachment ID on the representation.

7. Create civic_activities entry.

---

# Admin Flow

Admin can:

* list reps,
* search/filter reps,
* update administrative status and internal comment.
* view an uploaded representation image as a thumbnail linking to the full image.
* start Schedule creation from a Representation detail screen.
* view the linked Schedule after a Representation has been converted.

---

# Important Rules

* Snapshot data must remain unchanged even if contact data later changes.
* Administrative status/comment do not alter submitted snapshots.
* Email is the primary unique identifier.
* Public users cannot edit reps after submission.
* Representations support one optional JPG, JPEG, PNG, or WebP image. The Media Library attachment ID is stored in `image_attachment_id`.
* Public representation detail output excludes all contact snapshots.
* CAPTCHA is handled through the shared `CaptchaService`; the representation workflow does not duplicate Turnstile rendering or verification logic.

---

# Future Possibilities

* citizen tracking,
* SMS notifications,
* workflow escalation,
* GIS integration.

---

# Release Readiness Notes

Representation to Schedule conversion is implemented as a lightweight admin prefill workflow.

The workflow is:

1. Admin starts schedule creation from a representation.
2. Schedule fields are prefilled from representation data.
3. Admin manually reviews and edits the schedule.
4. Schedule is saved only after explicit admin action.

The Representation detail screen provides a "Convert to Schedule" action for users who can manage schedules. The action opens the normal Schedule create screen with `source_type=rep` and `source_id` set from the Representation. The Schedule save still uses the existing Schedule workflow, validation, and `ScheduleService`.

When the Schedule is created, the Representation stores the created Schedule ID in `schedule_id`. This is the persistent relationship for the one Representation to one Schedule workflow.

After conversion, the Representation detail screen replaces "Convert to Schedule" with a converted indication and a "View Schedule" action when the current administrator can manage schedules.

The linked Schedule detail page displays the originating Representation ID and subject and provides a "View Representation" action back to this Representation.

Duplicate conversions are prevented by checking the linked `schedule_id` and existing Schedule source references before rendering or accepting another conversion request.

This remains a lightweight prefill workflow and does not tightly couple representation state to schedule state. Creating the Schedule does not automatically change the Representation status.

The Representation internal comment is updated by appending an audit entry with the created Schedule ID and title. Existing internal comments are preserved with a blank line before the appended entry.
