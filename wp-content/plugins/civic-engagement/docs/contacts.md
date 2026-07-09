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
* email consent
* call consent
* SMS consent
* post consent

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
   * promote consent values from No to Yes only.
   * retain existing positive consent when an option is unselected.
   * update the consent timestamp only when a consent value is promoted.
3. If not:

   * create new contact.

---

# Activity Tracking

Each contact may have:

* reps
* thread responses
* event registrations
* schedule-related activity where explicitly created by schedule workflows

linked through civic_activities.

---

# User Listing Page

Admin can:

* search users,
* paginate users,
* filter contacts by consent type,
* export filtered contacts including consent fields.

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
* Consent values are stored only on the latest contact record and are cumulative: they may be promoted from No to Yes, but are never revoked by an unchecked public form option.

---

# Future Possibilities

* advanced CRM,
* segmentation,
* communication history,
* analytics.
