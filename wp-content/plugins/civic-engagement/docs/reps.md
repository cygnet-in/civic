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
5. User submits form.

---

# System Behavior

1. Check whether email exists in civic_contacts.

2. If exists:

   * update latest contact details.

3. If not:

   * create new contact.

4. Store snapshot data in civic_reps.

5. If an image is provided, create one WordPress Media Library attachment and store its attachment ID on the representation.

6. Create civic_activities entry.

---

# Admin Flow

Admin can:

* list reps,
* search/filter reps,
* update administrative status and internal comment.
* view an uploaded representation image as a thumbnail linking to the full image.

---

# Important Rules

* Snapshot data must remain unchanged even if contact data later changes.
* Administrative status/comment do not alter submitted snapshots.
* Email is the primary unique identifier.
* Public users cannot edit reps after submission.
* Representations support one optional JPG, JPEG, PNG, or WebP image. The Media Library attachment ID is stored in `image_attachment_id`.
* Public representation detail output excludes all contact snapshots.

---

# Future Possibilities

* citizen tracking,
* SMS notifications,
* workflow escalation,
* GIS integration.
