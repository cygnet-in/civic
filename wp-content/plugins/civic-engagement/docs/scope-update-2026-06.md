# Scope Update – June 2026

## Purpose

This document records scope clarifications and agreements reached after the first client review of the prototype.

This document supplements the original proposal and requirements documents.

Where conflicts exist between the original requirements and the review discussions, this document takes precedence.

---

# Current Development Status

Core modules are operational:

* Representations
* Consultations
* Consultation Responses
* Consultation Custom Fields
* Events
* Event Registrations
* Event Custom Fields
* Schedules
* Schedule Notes
* Contact Management
* Activity Tracking
* Frontend Widgets

Current focus:

* Client review refinements
* UI improvements
* Testing and stabilisation

---

# Accepted For Current Release

## Representations

### Subject Label

Replace:

Title

with:

Subject

### Mandatory Fields

Make mandatory:

* Name
* Email

### WhatsApp Support

Copy phone number to WhatsApp field automatically.

User may edit the value.

### Image Upload

Support:

* Single image upload
* Optional field
* Representation only

No image galleries.

### Administrative Status Tracking

Add:

* Status
* Internal Comment

Suggested statuses:

* New
* Seen
* In Progress
* Completed
* Closed

No workflow automation.

No audit trail.

No history tracking.

Status applies to the individual Representation.

---

## Consultations

### Button Label

Replace:

Respond to this Consultation

with:

Have Your Say

### Public Responses

Disable public response listings by default.

Moderation functionality remains available.

### Response Count Offset

Add configurable field:

starting_response_count

Displayed count:

starting_response_count + actual_responses_received

---

## Consultation Responses

### Administrative Status Tracking

Add:

* Status
* Internal Comment

Suggested statuses:

* New
* Seen
* In Progress
* Completed
* Closed

No workflow automation.

No audit trail.

No history tracking.

Status applies to the individual Consultation Response.

---

## Events

### Volunteer Area

No code change required.

The client was referring to an Event Custom Field in sample data rather than a core Event module field.

The field may be removed from the relevant event configuration where appropriate.

### Participant Limits

Not included.

Current version allows unrestricted registrations.

---

## Schedules

### Recent Update

Add editable field:

recent_update

Visible in public schedule listing.

### Status Date

Rename:

End Date

to:

Status Date

### Priority

Add simple priority field.

Used for schedule sorting only.

No workflow functionality.

### Review Date

Deferred.

Not included in current release.

---

## Contacts

### Communication Consent

Add consent option to public forms.

Store consent against contact record.

### Consent Filtering

Allow filtering contacts by consent status.

### Contact Export

Allow export of consented contacts.

### Contact Matching

Continue using email-based contact matching.

---

## Homepage / UI

### Homepage Hero

Support hero image or video section.

### Theme Refinement

Apply client branding and colour preferences.

---

## Spam Prevention

Implement CAPTCHA or equivalent spam-prevention mechanism.

---

# Deferred Until Core Enhancements Are Complete

## Media Support

Consultations, Events and Schedules require separate review for:

* Single image
* Multiple images
* Galleries
* Video support

No commitment has been made regarding implementation approach.

Representation image upload remains part of the current release.

---

## Schedule Comments

Public schedule comments require separate review.

Not included in current release.

If implemented in the future, status tracking requirements will be reviewed separately.

---

## URL Routing

Basic slug support already exists:

* slug field
* slug generation
* slug storage

Routing implementation remains deferred.

Final URL structure has not been agreed.

Options under consideration:

/civic/event/{slug}

/civic/schedule/{slug}

/civic/consultation/{slug}

or

/civic/{slug}

Root-level routing:

/{slug}

is not recommended because it may conflict with:

* WordPress pages
* WordPress posts
* Categories
* Plugins
* Future website content

If module-independent URLs are chosen:

/civic/{slug}

then global slug uniqueness must be enforced across:

* Consultations
* Events
* Schedules

---

# Not Included In Current Release

The following items are not currently planned:

* Email campaign management
* SMS campaign management
* Unsubscribe workflow management
* Advanced workflow automation
* Audit trails
* Process history tracking
* Event capacity management

---

# Open Decisions

The following items require client confirmation before implementation:

* Final URL structure
* Media strategy for consultations/events/schedules
* Public schedule comments

---

# Development Order

1. Administrative status tracking for Representations
2. Administrative status tracking for Consultation Responses
3. Response Count Offset
4. Recent Update
5. Priority
6. Consent Management
7. Representation Image Upload
8. UI Refinement
9. Client Review
10. Routing / Media Decisions
