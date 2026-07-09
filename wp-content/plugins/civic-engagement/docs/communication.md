# Communication

## Current Implementation

There is no standalone Communication module implemented in the current source.

The implemented communication-related functionality is contact consent management:

* public forms collect consent for email, call, SMS and post;
* consent values are stored on contact records;
* consent is cumulative in V1 and can only be promoted from No to Yes by public form submissions;
* contact administration supports filtering and export by consent fields.

## Current Simplifications

* No grouped email sending module.
* No SMS sending.
* No WhatsApp integration.
* No campaign automation.
* No unsubscribe workflow.
* No analytics or open tracking.

## Future Communication Direction

Future communication work may include a lightweight grouped email workflow, but it is not currently implemented.

Potential future recipient sources:

* event registrations
* consultation responses
* representations
* schedules
* electoral areas
* consent-filtered contacts

Potential future features:

* duplicate email filtering
* basic personalization
* simple reusable templates
* integration with a dedicated email platform

## Important Rules

Any future communication workflow must:

* respect stored consent fields;
* keep unsubscribe/opt-out requirements explicit;
* avoid bulk marketing complexity unless separately scoped;
* avoid SMS or WhatsApp integration unless explicitly approved;
* preserve contact and activity separation.
