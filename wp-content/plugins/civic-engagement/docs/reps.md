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
* Reps remain private.

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
5. User submits form.

---

# System Behavior

1. Check whether email exists in civic_contacts.

2. If exists:

   * update latest contact details.

3. If not:

   * create new contact.

4. Store snapshot data in civic_reps.

5. Create civic_activities entry.

---

# Admin Flow

Admin can:

* list reps,
* search/filter reps,
* edit reps,
* export reps,
* create schedule from rep.

---

# Important Rules

* Snapshot data must remain unchanged even if contact data later changes.
* Email is the primary unique identifier.
* Public users cannot edit reps after submission.

---

# Future Possibilities

* citizen tracking,
* SMS notifications,
* workflow escalation,
* GIS integration.
