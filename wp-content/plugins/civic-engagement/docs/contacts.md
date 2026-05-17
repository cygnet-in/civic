# Contacts & Activity Module

## Purpose

Centralized contact/activity management.

This is NOT a public user account system.

---

# Current Simplifications

* No login/signup.
* No password management.
* No public profile system.
* Email used as primary unique identifier.

---

# Contact Fields

* name
* email
* phone
* WhatsApp number
* address
* Eircode
* electoral area

---

# Contact Update Logic

When:

* rep submitted,
* thread response added,
* event registration submitted,

then:

1. Check whether email exists.
2. If exists:

   * update latest contact details.
3. If not:

   * create new contact.

---

# Activity Tracking

Each contact may have:

* reps
* thread responses
* event registrations
* schedules

linked through civic_activities.

---

# User Listing Page

Admin can:

* search users,
* paginate users,
* open user detail page.

---

# User Detail Page

Displays:

* latest contact details,
* activity history table.

Activity table columns:

* Type
* Date
* Summary
* Related Item

---

# Important Rules

* Snapshot data remains with activities.
* Latest contact data stored separately.
* Email is primary identity field.

---

# Future Possibilities

* advanced CRM,
* segmentation,
* communication history,
* analytics.
