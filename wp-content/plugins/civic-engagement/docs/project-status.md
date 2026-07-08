# Current Development Stage

Milestone: Post Client Review Refinements Completed

Implemented:
- Subject label
- Mandatory name/email
- WhatsApp sync
- Have Your Say
- Response count offset
- Representation status/comment
- Schedule recent update
- Schedule priority
- Consent management V1
- Representation image upload V1
- Civic media support V1
- Civic dashboard V1
- Public statistics shortcode V1
- Civic admin cleanup V1
- Civic account cleanup V1
- Canonical slug routing V1
- Short URL routing V1

Deferred:
- Schedule comments

---

# Project

**Civic Engagement Platform (WordPress Plugin)**

---

# Development Philosophy

The project prioritizes:

1. Complete business workflows first.
2. Technical refinements later.
3. Repository + Service architecture.
4. Immutable citizen submissions.
5. Frontend administration in the future.
6. Minimal dependency on wp-admin implementation details.

Current wp-admin screens are considered operational implementations used to validate workflows and business rules.

---

# Current Functional Status

## Contacts

Status: Operational

Features:

* Contact repository
* Contact service
* Email-based contact matching
* Contact updates on subsequent submissions
* Snapshot preservation on submissions

Important Rule:

Contact records may change over time.

Submission records must preserve immutable snapshots.

Consent Management V1:

* email, call, SMS, and post consent stored on contacts
* consent is cumulative: public forms promote No to Yes and cannot revoke existing consent
* consent filtering and CSV export available in contact administration

---

## Activities

Status: Operational

Purpose:

Tracks contact-related activities.

Examples:

* representation submission
* consultation response
* event registration

Important Rule:

Activities are contact-centric.

Activities are not intended as a generic system logging framework.

---

## Electoral Areas

Status: Operational

Table:

`wp_civic_electoral_areas`

Current Approach:

* manually managed reference data
* repository access
* no admin CRUD

Integrated Into:

* representations
* consultation responses
* event registrations

---

## Representation Module

Status: Operational

Public Features:

* representation submission
* representation detail shortcode with optional image

Admin Features:

* listing
* search
* pagination
* detail view
* uploaded image thumbnail linking to the full image

Important Rule:

Representation detail pages display immutable contact snapshots.

Future Enhancement:

* display current contact record alongside snapshot

---

## Consultation Module

Status: Operational

Admin Features:

* consultation CRUD
* consultation fields
* consultation responses
* response moderation

Public Features:

* consultation listing
* consultation detail
* response submission
* response viewing

Implemented:

* custom fields
* response moderation
* response count display
* response counts
* anchor navigation
* multiple image media support

Administrative refinements:

* configurable starting response count
* public response rendering disabled by default

Important Rule:

Citizen responses are immutable.

Moderation controls visibility only.

---

## Consultation Custom Fields

Status: Operational

Supported Types:

* text
* textarea
* dropdown

Implemented:

* field management
* field rendering
* validation
* storage
* admin display
* public display

Storage:

Stored inside:

`response_data['custom_fields']`

Using stable field keys.

---

## Event Module

Status: Operational

### Event Administration

Implemented:

* event CRUD
* event visibility control
* registration control
* location support

### Public Event Listing

Implemented:

* event listing shortcode
* event detail shortcode
* registration status display

### Event Registration

Implemented:

* no-login registration
* contact matching
* snapshot preservation
* electoral area integration

### Event Registration Administration

Implemented:

* registration listing
* event filtering
* search
* pagination
* registration detail view
* multiple image media support

### Event Custom Fields

Implemented:

* field management
* field keys
* duplicate key validation
* field rendering
* validation
* storage
* registration detail display

Supported Types:

* text
* textarea
* dropdown

Storage:

Stored inside:

`registration_data['custom_fields']`

Using stable field keys.

---

# Deferred Enhancements

## Operational

* response pagination improvements
* registration pagination improvements
* export functionality
* advanced filtering

## UX

* public response styling
* moderation indicators
* success message improvements
* current contact display on detail pages

## Technical

* frontend administration
* performance optimisation

---

# Current Priority

1. Deliver prototype for client testing.
2. Gather workflow feedback.
3. Revisit deferred enhancements.
4. Begin frontend administration phase.

---

# Important Architectural Rules

* Repository pattern throughout.
* Service layer for workflows.
* No direct SQL in admin/frontend screens.
* Preserve immutable submission snapshots.
* Use namespaced request fields.
* Use shared reference data repositories.
* Prefer workflow completion over technical refinement.
