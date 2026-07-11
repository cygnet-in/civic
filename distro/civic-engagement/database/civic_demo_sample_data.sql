
-- Civic Platform Meaningful Demo Data
-- Safe reset + insert script for the custom civic tables.
-- Run this after taking a database backup.

INSERT INTO wp_civic_electoral_areas (id, name, slug, is_active) VALUES
(1, 'Dublin Bay North', 'dublin-bay-north', 1),
(2, 'Dublin Bay South', 'dublin-bay-south', 1),
(3, 'Dublin Central', 'dublin-central', 1),
(4, 'Dublin Fingal East', 'dublin-fingal-east', 1),
(5, 'Dublin Fingal West', 'dublin-fingal-west', 1),
(6, 'Dublin Mid-West', 'dublin-mid-west', 1),
(7, 'Dublin North-West', 'dublin-north-west', 1),
(8, 'Dublin Rathdown', 'dublin-rathdown', 1),
(9, 'Dublin South-Central', 'dublin-south-central', 1),
(10, 'Dublin South-West', 'dublin-south-west', 1),
(11, 'Dublin West', 'dublin-west', 1),
(12, 'Dún Laoghaire', 'dun-laoghaire', 1),
(13, 'Outside DLRCC', 'outside-dlrcc', 1),
(14, 'Other Area', 'other-area', 0);

INSERT INTO wp_civic_contacts
(id, email, latest_name, latest_phone, latest_whatsapp, latest_address, latest_eircode, latest_electoral_area, created_at, updated_at) VALUES
(1, 'mary.obrien@example.com', 'Mary O''Brien', '087 555 2101', '087 555 2101', '12 Harbour View, Dún Laoghaire', 'A96 X2P4', 'Dún Laoghaire', '2026-05-18 09:15:00', '2026-06-08 10:20:00'),
(2, 'john.murphy@example.com', 'John Murphy', '086 555 1822', '086 555 1822', '44 Glenageary Road, Glenageary', 'A96 K7D3', 'Dublin Rathdown', '2026-05-19 11:05:00', '2026-06-07 16:10:00'),
(3, 'aisha.khan@example.com', 'Aisha Khan', '085 555 3344', '085 555 3344', '8 Library Road, Blackrock', 'A94 R2C1', 'Dublin Bay South', '2026-05-20 14:30:00', '2026-06-05 13:45:00'),
(4, 'liam.walsh@example.com', 'Liam Walsh', '089 555 7788', '', '21 Seafield Avenue, Monkstown', 'A94 V6T2', 'Dún Laoghaire', '2026-05-22 08:45:00', '2026-06-06 09:25:00'),
(5, 'siobhan.ryan@example.com', 'Siobhán Ryan', '087 555 9033', '087 555 9033', '5 Park Court, Cabinteely', 'D18 P9N8', 'Dublin Rathdown', '2026-05-23 12:10:00', '2026-06-09 12:00:00'),
(6, 'david.smith@example.com', 'David Smith', '086 555 4412', '', '17 Main Street, Shankill', 'D18 F3K7', 'Dublin Rathdown', '2026-05-24 17:20:00', '2026-06-09 15:20:00'),
(7, 'nora.byrne@example.com', 'Nora Byrne', '085 555 6621', '085 555 6621', '31 Station Road, Dalkey', 'A96 H5P9', 'Dún Laoghaire', '2026-05-26 10:40:00', '2026-06-10 09:10:00'),
(8, 'peter.doyle@example.com', 'Peter Doyle', '087 555 7012', '', '9 Meadow Grove, Killiney', 'A96 T8W1', 'Dublin Rathdown', '2026-05-27 18:00:00', '2026-06-10 09:20:00'),
(9, 'claire.nolan@example.com', 'Claire Nolan', '086 555 9344', '086 555 9344', '2 Village Green, Blackrock', 'A94 N6H2', 'Dublin Bay South', '2026-05-29 09:30:00', '2026-06-10 09:30:00'),
(10, 'mark.fitzgerald@example.com', 'Mark Fitzgerald', '089 555 1188', '', '6 Marine Road, Dún Laoghaire', 'A96 Y4E6', 'Dún Laoghaire', '2026-06-01 15:10:00', '2026-06-10 09:40:00');

INSERT INTO wp_civic_threads
(id, title, slug, summary, description, response_enabled, is_public, created_by, start_date, end_date, status, created_at, updated_at) VALUES
(1, 'Proposed Road Safety Improvements on Harbour Road', 'road-safety-harbour-road', 'Share your views on traffic calming, safer crossings, and improved pedestrian access near Harbour Road.', 'Councillor Thomas Joseph is collecting feedback from residents, businesses, pedestrians, cyclists, and parents regarding proposed road safety improvements on Harbour Road. Suggestions include additional pedestrian crossings, improved signage, traffic calming near the school entrance, and better evening lighting.', 1, 1, 1, '2026-06-01 00:00:00', '2026-06-30 23:59:59', 'published', '2026-05-25 10:00:00', '2026-06-01 09:00:00'),
(2, 'Community Park Redevelopment Consultation', 'community-park-redevelopment', 'Residents are invited to comment on proposed improvements to the local park and playground.', 'The proposed redevelopment includes new seating, accessible paths, improved lighting, a small play area for younger children, biodiversity planting, and better maintenance arrangements. Public feedback will help prioritise the works.', 1, 1, 1, '2026-06-05 00:00:00', '2026-07-05 23:59:59', 'published', '2026-05-28 12:00:00', '2026-06-05 09:00:00'),
(3, 'Village Centre Parking Review', 'village-centre-parking-review', 'A review of short-stay parking, disabled parking spaces, loading bays, and resident access in the village centre.', 'This consultation seeks practical suggestions on improving access and parking turnover in the village centre while supporting local businesses and residents.', 1, 1, 1, '2026-06-10 00:00:00', '2026-06-25 23:59:59', 'published', '2026-06-02 14:00:00', '2026-06-10 08:30:00'),
(4, 'Draft Coastal Walk Lighting Proposal', 'draft-coastal-walk-lighting', 'Draft proposal for improved lighting along the coastal walk. This item is currently not public.', 'Internal draft consultation prepared for review before publication. The proposal covers lighting, safety, biodiversity impact, and maintenance costs.', 0, 0, 1, '2026-07-01 00:00:00', '2026-07-31 23:59:59', 'draft', '2026-06-04 10:45:00', '2026-06-04 10:45:00'),
(5, 'Library Opening Hours Feedback', 'library-opening-hours-feedback', 'This consultation has closed. Thank you to all residents who submitted feedback.', 'Residents were asked whether Saturday afternoon opening, late weekday opening, or additional study-space hours should be prioritised.', 0, 1, 1, '2026-05-01 00:00:00', '2026-05-31 23:59:59', 'closed', '2026-04-25 09:00:00', '2026-06-01 10:00:00'),
(6, 'Unpublished Trial Consultation', 'unpublished-trial-consultation', 'Test consultation used for internal demonstration only.', 'This record demonstrates an unpublished consultation that should not appear publicly.', 1, 0, 1, NULL, NULL, 'unpublished', '2026-06-08 11:00:00', '2026-06-08 11:00:00');

INSERT INTO wp_civic_thread_fields
(id, thread_id, field_label, field_key, field_type, field_options, sort_order, is_required, created_at) VALUES
(1, 1, 'How often do you use this road?', 'road_usage_frequency', 'dropdown', '["Daily","Several times a week","Occasionally","Rarely"]', 1, 1, '2026-05-25 10:05:00'),
(2, 1, 'Main concern', 'main_concern', 'radio', '["Speeding","Unsafe crossing","Poor lighting","Parking obstruction","Other"]', 2, 1, '2026-05-25 10:06:00'),
(3, 1, 'Additional suggestions', 'additional_suggestions', 'textarea', '[]', 3, 0, '2026-05-25 10:07:00'),
(4, 2, 'Preferred improvement', 'preferred_improvement', 'checkbox', '["Playground","Seating","Lighting","Accessible paths","Biodiversity planting"]', 1, 1, '2026-05-28 12:05:00'),
(5, 2, 'General opinion', 'general_opinion', 'textarea', '[]', 2, 0, '2026-05-28 12:06:00'),
(6, 3, 'Do you support short-stay parking?', 'support_short_stay', 'radio', '["Yes","No","Unsure"]', 1, 1, '2026-06-02 14:05:00'),
(7, 3, 'Comments on parking access', 'parking_comments', 'textarea', '[]', 2, 0, '2026-06-02 14:06:00');

INSERT INTO wp_civic_thread_responses
(id, thread_id, contact_id, name_snapshot, email_snapshot, phone_snapshot, address_snapshot, eircode_snapshot, electoral_area_id, electoral_area_snapshot, response_data, is_public, created_at) VALUES
(1, 1, 1, 'Mary O''Brien', 'mary.obrien@example.com', '087 555 2101', '12 Harbour View, Dún Laoghaire', 'A96 X2P4', 12, 'Dún Laoghaire', '{"response_text":"The crossing near the school is difficult during morning traffic. A raised table or clearer zebra crossing would help families.","road_usage_frequency":"Daily","main_concern":"Unsafe crossing","additional_suggestions":"Please consider better lighting at the junction and a slower speed zone during school hours."}', 1, '2026-06-03 09:40:00'),
(2, 1, 4, 'Liam Walsh', 'liam.walsh@example.com', '089 555 7788', '21 Seafield Avenue, Monkstown', 'A94 V6T2', 12, 'Dún Laoghaire', '{"response_text":"Traffic speed has increased recently. I support traffic calming, but please avoid measures that block emergency access.","road_usage_frequency":"Several times a week","main_concern":"Speeding","additional_suggestions":"Use clear road markings and speed feedback signs."}', 1, '2026-06-04 16:10:00'),
(3, 1, 6, 'David Smith', 'david.smith@example.com', '086 555 4412', '17 Main Street, Shankill', 'D18 F3K7', 8, 'Dublin Rathdown', '{"response_text":"Parking near the junction is reducing visibility. Enforcement and better signage would help.","road_usage_frequency":"Occasionally","main_concern":"Parking obstruction","additional_suggestions":"Consider double yellow lines close to the bend."}', 0, '2026-06-06 12:20:00'),
(4, 2, 3, 'Aisha Khan', 'aisha.khan@example.com', '085 555 3344', '8 Library Road, Blackrock', 'A94 R2C1', 2, 'Dublin Bay South', '{"response_text":"The park needs safer paths for buggies and wheelchairs. Lighting would also improve confidence in winter evenings.","preferred_improvement":["Accessible paths","Lighting","Seating"],"general_opinion":"Please include native planting and retain mature trees."}', 1, '2026-06-07 11:30:00'),
(5, 2, 5, 'Siobhán Ryan', 'siobhan.ryan@example.com', '087 555 9033', '5 Park Court, Cabinteely', 'D18 P9N8', 8, 'Dublin Rathdown', '{"response_text":"A small playground for younger children would be very welcome.","preferred_improvement":["Playground","Seating","Biodiversity planting"],"general_opinion":"Please add more bins and regular maintenance after weekends."}', 1, '2026-06-08 17:45:00'),
(6, 3, 9, 'Claire Nolan', 'claire.nolan@example.com', '086 555 9344', '2 Village Green, Blackrock', 'A94 N6H2', 2, 'Dublin Bay South', '{"response_text":"Short-stay parking is needed for local shops, but residents also need protection from all-day commuter parking.","support_short_stay":"Yes","parking_comments":"A two-hour limit near shops and resident permits on side roads may be a fair balance."}', 1, '2026-06-10 09:30:00');

INSERT INTO wp_civic_events
(id, title, slug, summary, description, location, start_date, end_date, is_public, registration_enabled, status, created_at, updated_at) VALUES
(1, 'Community Clean-Up Day', 'community-clean-up-day', 'Join residents and volunteers for a Saturday morning clean-up of public spaces and walking routes.', 'Councillor Thomas Joseph invites residents, schools, tidy towns groups, and community volunteers to join a local clean-up day. Gloves, bags, and basic equipment will be provided. Families are welcome, and participants are encouraged to register so that supplies can be planned.', 'Meet at People''s Park main entrance, Dún Laoghaire', '2026-06-21 10:00:00', '2026-06-21 13:00:00', 1, 1, 'published', '2026-05-30 09:00:00', '2026-06-01 10:00:00'),
(2, 'Public Information Evening on Road Safety', 'public-information-evening-road-safety', 'An information evening for residents to discuss road safety priorities and proposed traffic calming measures.', 'This event will include a short presentation followed by questions and discussion. Residents who submitted representations or consultation responses are especially encouraged to attend.', 'Community Hall, Marine Road', '2026-06-28 18:30:00', '2026-06-28 20:00:00', 1, 1, 'published', '2026-06-01 10:15:00', '2026-06-02 09:30:00'),
(3, 'Older Persons Digital Help Clinic', 'older-persons-digital-help-clinic', 'A free drop-in support clinic for older residents needing help with phones, online forms, and local services.', 'Volunteers will assist residents with basic digital tasks including setting up email, accessing public services online, and using community information websites. Registration is optional but recommended.', 'Local Library Meeting Room', '2026-07-03 11:00:00', '2026-07-03 14:00:00', 1, 0, 'published', '2026-06-04 13:00:00', '2026-06-04 13:00:00'),
(4, 'Draft Youth Forum Workshop', 'draft-youth-forum-workshop', 'Draft event not yet visible to the public.', 'Internal planning record for a youth forum workshop with schools and local youth groups.', 'Venue to be confirmed', '2026-07-12 15:00:00', '2026-07-12 17:00:00', 0, 0, 'draft', '2026-06-05 10:00:00', '2026-06-05 10:00:00'),
(5, 'Summer Family Fun Morning', 'summer-family-fun-morning', 'A family-friendly community gathering with games, local groups, and information stands.', 'This published event has registration closed to demonstrate a public event that does not currently accept online registrations.', 'Seafront Green', '2026-07-20 10:30:00', '2026-07-20 13:30:00', 1, 0, 'published', '2026-06-06 12:00:00', '2026-06-06 12:00:00'),
(6, 'Closed Event: Volunteer Briefing', 'closed-volunteer-briefing', 'Past volunteer briefing retained for archive and admin demonstration.', 'This completed event demonstrates older event content.', 'Council Chamber', '2026-05-15 18:00:00', '2026-05-15 19:00:00', 1, 0, 'closed', '2026-05-01 09:00:00', '2026-05-16 09:00:00');

INSERT INTO wp_civic_event_fields
(id, event_id, field_label, field_key, field_type, field_options, sort_order, is_required, created_at) VALUES
(1, 1, 'Volunteer Area', 'volunteer_area', 'dropdown', '["Litter picking","Refreshment support","Sign-in desk","General support"]', 1, 1, '2026-05-30 09:10:00'),
(2, 1, 'Number of Participants', 'participants', 'text', '[]', 2, 1, '2026-05-30 09:11:00'),
(3, 1, 'Accessibility Requirements', 'accessibility_requirements', 'textarea', '[]', 3, 0, '2026-05-30 09:12:00'),
(4, 2, 'Main Area of Interest', 'area_of_interest', 'checkbox', '["Pedestrian crossings","Traffic speed","Parking","Cycling safety","Public transport access"]', 1, 1, '2026-06-01 10:20:00'),
(5, 2, 'Question for the meeting', 'meeting_question', 'textarea', '[]', 2, 0, '2026-06-01 10:21:00'),
(6, 3, 'Preferred Support Topic', 'support_topic', 'dropdown', '["Mobile phone basics","Email setup","Online services","Video calls","Other"]', 1, 0, '2026-06-04 13:05:00');

INSERT INTO wp_civic_event_registrations
(id, event_id, contact_id, name_snapshot, email_snapshot, phone_snapshot, address_snapshot, eircode_snapshot, electoral_area_id, electoral_area_snapshot, registration_data, created_at) VALUES
(1, 1, 1, 'Mary O''Brien', 'mary.obrien@example.com', '087 555 2101', '12 Harbour View, Dún Laoghaire', 'A96 X2P4', 12, 'Dún Laoghaire', '{"volunteer_area":"Sign-in desk","participants":"2","accessibility_requirements":""}', '2026-06-02 10:20:00'),
(2, 1, 7, 'Nora Byrne', 'nora.byrne@example.com', '085 555 6621', '31 Station Road, Dalkey', 'A96 H5P9', 12, 'Dún Laoghaire', '{"volunteer_area":"Litter picking","participants":"1","accessibility_requirements":"Prefer lighter walking route."}', '2026-06-03 14:35:00'),
(3, 1, 8, 'Peter Doyle', 'peter.doyle@example.com', '087 555 7012', '9 Meadow Grove, Killiney', 'A96 T8W1', 8, 'Dublin Rathdown', '{"volunteer_area":"General support","participants":"3","accessibility_requirements":""}', '2026-06-05 08:50:00'),
(4, 2, 2, 'John Murphy', 'john.murphy@example.com', '086 555 1822', '44 Glenageary Road, Glenageary', 'A96 K7D3', 8, 'Dublin Rathdown', '{"area_of_interest":["Traffic speed","Pedestrian crossings"],"meeting_question":"Can the council consider a 30 km/h zone near the school?"}', '2026-06-06 16:10:00'),
(5, 2, 4, 'Liam Walsh', 'liam.walsh@example.com', '089 555 7788', '21 Seafield Avenue, Monkstown', 'A94 V6T2', 12, 'Dún Laoghaire', '{"area_of_interest":["Parking","Cycling safety"],"meeting_question":"Will residents be consulted before parking changes are implemented?"}', '2026-06-08 09:05:00'),
(6, 3, 10, 'Mark Fitzgerald', 'mark.fitzgerald@example.com', '089 555 1188', '6 Marine Road, Dún Laoghaire', 'A96 Y4E6', 12, 'Dún Laoghaire', '{"support_topic":"Online services"}', '2026-06-09 15:40:00');

INSERT INTO wp_civic_reps
(id, contact_id, name_snapshot, email_snapshot, phone_snapshot, whatsapp_snapshot, address_snapshot, eircode_snapshot, electoral_area_id, electoral_area_snapshot, title, details, map_lat, map_lng, status, created_at, updated_at) VALUES
(1, 1, 'Mary O''Brien', 'mary.obrien@example.com', '087 555 2101', '087 555 2101', '12 Harbour View, Dún Laoghaire', 'A96 X2P4', 12, 'Dún Laoghaire', 'Broken footpath near school entrance', 'Several paving slabs are loose near the school entrance on Harbour Road. This is a trip hazard, especially during morning drop-off.', 53.2941000, -6.1344000, 'new', '2026-06-10 09:18:00', '2026-06-10 09:18:00'),
(2, 2, 'John Murphy', 'john.murphy@example.com', '086 555 1822', '086 555 1822', '44 Glenageary Road, Glenageary', 'A96 K7D3', 8, 'Dublin Rathdown', 'Request for traffic calming near Glenageary Road', 'Residents have reported frequent speeding in the evenings. Please review possible speed signage or traffic calming options.', 53.2816000, -6.1379000, 'in_progress', '2026-06-09 14:05:00', '2026-06-10 10:30:00'),
(3, 3, 'Aisha Khan', 'aisha.khan@example.com', '085 555 3344', '085 555 3344', '8 Library Road, Blackrock', 'A94 R2C1', 2, 'Dublin Bay South', 'Overflowing bins near bus stop', 'Bins near the main bus stop are regularly overflowing after weekends. Additional collection or a larger bin may be needed.', 53.3012000, -6.1778000, 'new', '2026-06-08 11:25:00', '2026-06-08 11:25:00'),
(4, 4, 'Liam Walsh', 'liam.walsh@example.com', '089 555 7788', '', '21 Seafield Avenue, Monkstown', 'A94 V6T2', 12, 'Dún Laoghaire', 'Street light not working on Seafield Avenue', 'The street light outside number 21 has not been working for more than a week. The area is very dark at night.', 53.2929000, -6.1573000, 'scheduled', '2026-06-07 19:40:00', '2026-06-09 09:15:00'),
(5, 5, 'Siobhán Ryan', 'siobhan.ryan@example.com', '087 555 9033', '087 555 9033', '5 Park Court, Cabinteely', 'D18 P9N8', 8, 'Dublin Rathdown', 'Tree branches blocking footpath', 'Low branches are blocking part of the footpath near the park entrance. Wheelchair users and parents with buggies are affected.', 53.2648000, -6.1532000, 'resolved', '2026-06-05 12:10:00', '2026-06-09 16:45:00'),
(6, 6, 'David Smith', 'david.smith@example.com', '086 555 4412', '', '17 Main Street, Shankill', 'D18 F3K7', 8, 'Dublin Rathdown', 'Noise concern from late-night works', 'Residents were not informed about late-night road works last week. Please request better advance communication in future.', NULL, NULL, 'closed', '2026-06-03 22:15:00', '2026-06-08 10:00:00'),
(7, 9, 'Claire Nolan', 'claire.nolan@example.com', '086 555 9344', '086 555 9344', '2 Village Green, Blackrock', 'A94 N6H2', 2, 'Dublin Bay South', 'Disabled parking bay request near clinic', 'A disabled parking space near the local clinic would improve access for older residents and people with mobility needs.', 53.3020000, -6.1786000, 'pending', '2026-06-02 10:30:00', '2026-06-04 13:30:00'),
(8, 10, 'Mark Fitzgerald', 'mark.fitzgerald@example.com', '089 555 1188', '', '6 Marine Road, Dún Laoghaire', 'A96 Y4E6', 12, 'Dún Laoghaire', 'Suggestion for more bicycle parking', 'More bicycle stands are needed near the station and the library. Existing stands are often full during commuting hours.', 53.2948000, -6.1357000, 'new', '2026-06-01 08:20:00', '2026-06-01 08:20:00');

INSERT INTO wp_civic_schedules
(id, type, title, details, status, internal_comment, is_public, is_archived, start_date, end_date, source_type, source_id, created_by, created_at, updated_at) VALUES
(1, 'meeting', 'Community Consultation Meeting on Harbour Road Safety', 'Public meeting to discuss feedback received through the Harbour Road safety consultation and agree the next steps for council follow-up.', 'scheduled', 'Prepare summary of consultation responses before the meeting.', 1, 0, '2026-06-24 18:30:00', '2026-06-24 20:00:00', 'thread', 1, 1, '2026-06-03 10:00:00', '2026-06-06 09:00:00'),
(2, 'rep_followup', 'Site Visit: Broken Footpath Near School Entrance', 'Follow-up site visit connected to resident representation about loose paving slabs near the school entrance.', 'open', 'Bring photos from the representation and note exact location.', 0, 0, '2026-06-14 09:30:00', '2026-06-14 10:00:00', 'rep', 1, 1, '2026-06-10 10:00:00', '2026-06-10 10:00:00'),
(3, 'public_announcement', 'Update on Park Redevelopment Consultation', 'Public update noting that consultation submissions are being reviewed and a summary will be published after the closing date.', 'pending', 'Draft public note after reviewing latest responses.', 1, 0, '2026-07-08 10:00:00', '2026-07-08 10:30:00', 'thread', 2, 1, '2026-06-08 09:20:00', '2026-06-08 09:20:00'),
(4, 'motion', 'Council Motion: Safer School Streets', 'Motion proposed for the next council meeting requesting a review of school-zone traffic calming and pedestrian safety improvements.', 'scheduled', 'Confirm final wording with office before submission deadline.', 0, 0, '2026-06-18 14:00:00', '2026-06-18 15:00:00', NULL, NULL, 1, '2026-06-06 11:15:00', '2026-06-06 11:15:00'),
(5, 'question', 'Council Question: Public Bin Collection Frequency', 'Question submitted regarding weekend overflow at busy bus stops and village-centre locations.', 'completed', 'Response received from council operations team.', 1, 0, '2026-06-06 11:00:00', '2026-06-06 11:15:00', 'rep', 3, 1, '2026-06-01 13:00:00', '2026-06-07 09:30:00'),
(6, 'meeting', 'Internal Planning Meeting for Youth Forum', 'Internal planning meeting for the draft youth forum event.', 'cancelled', 'Postponed until schools confirm availability.', 0, 0, '2026-06-20 15:00:00', '2026-06-20 16:00:00', 'event', 4, 1, '2026-06-05 10:30:00', '2026-06-09 10:30:00'),
(7, 'other', 'Archived Community Briefing', 'Older completed briefing retained to demonstrate archived schedule behaviour.', 'completed', 'Archived after completion.', 1, 1, '2026-05-10 18:00:00', '2026-05-10 19:00:00', NULL, NULL, 1, '2026-05-01 10:00:00', '2026-05-11 09:00:00');

INSERT INTO wp_civic_schedule_notes
(id, schedule_id, note, created_by, created_at) VALUES
(1, 1, 'Invite residents who submitted consultation responses and nearby school representatives.', 1, '2026-06-06 09:05:00'),
(2, 1, 'Prepare printed map showing proposed crossing and speed feedback sign locations.', 1, '2026-06-07 12:30:00'),
(3, 2, 'Resident has provided photographs showing the damaged paving slabs.', 1, '2026-06-10 10:05:00'),
(4, 5, 'Council response indicates additional weekend bin check is being considered.', 1, '2026-06-07 09:35:00'),
(5, 6, 'Mark as cancelled but keep for internal record.', 1, '2026-06-09 10:35:00');

INSERT INTO wp_civic_activities
(id, contact_id, activity_type, related_id, summary, created_at) VALUES
(1, 1, 'rep', 1, 'Representation submitted: Broken footpath near school entrance', '2026-06-10 09:18:00'),
(2, 2, 'rep', 2, 'Representation submitted: Request for traffic calming near Glenageary Road', '2026-06-09 14:05:00'),
(3, 3, 'rep', 3, 'Representation submitted: Overflowing bins near bus stop', '2026-06-08 11:25:00'),
(4, 4, 'rep', 4, 'Representation submitted: Street light not working on Seafield Avenue', '2026-06-07 19:40:00'),
(5, 5, 'rep', 5, 'Representation submitted: Tree branches blocking footpath', '2026-06-05 12:10:00'),
(6, 6, 'rep', 6, 'Representation submitted: Noise concern from late-night works', '2026-06-03 22:15:00'),
(7, 9, 'rep', 7, 'Representation submitted: Disabled parking bay request near clinic', '2026-06-02 10:30:00'),
(8, 10, 'rep', 8, 'Representation submitted: Suggestion for more bicycle parking', '2026-06-01 08:20:00'),
(9, 1, 'thread_response', 1, 'Consultation response submitted: Harbour Road safety', '2026-06-03 09:40:00'),
(10, 4, 'thread_response', 2, 'Consultation response submitted: Harbour Road safety', '2026-06-04 16:10:00'),
(11, 6, 'thread_response', 3, 'Consultation response submitted: Harbour Road safety', '2026-06-06 12:20:00'),
(12, 3, 'thread_response', 4, 'Consultation response submitted: Community park redevelopment', '2026-06-07 11:30:00'),
(13, 5, 'thread_response', 5, 'Consultation response submitted: Community park redevelopment', '2026-06-08 17:45:00'),
(14, 9, 'thread_response', 6, 'Consultation response submitted: Village Centre Parking Review', '2026-06-10 09:30:00'),
(15, 1, 'event_registration', 1, 'Registered for Community Clean-Up Day', '2026-06-02 10:20:00'),
(16, 7, 'event_registration', 2, 'Registered for Community Clean-Up Day', '2026-06-03 14:35:00'),
(17, 8, 'event_registration', 3, 'Registered for Community Clean-Up Day', '2026-06-05 08:50:00'),
(18, 2, 'event_registration', 4, 'Registered for Public Information Evening on Road Safety', '2026-06-06 16:10:00'),
(19, 4, 'event_registration', 5, 'Registered for Public Information Evening on Road Safety', '2026-06-08 09:05:00'),
(20, 10, 'event_registration', 6, 'Registered for Older Persons Digital Help Clinic', '2026-06-09 15:40:00'),
(21, 1, 'schedule', 2, 'Schedule created from representation: Site Visit', '2026-06-10 10:00:00'),
(22, 3, 'manual', NULL, 'Office called resident about public bin collection concern', '2026-06-09 12:15:00'),
(23, 5, 'manual', NULL, 'Resident informed that tree maintenance issue has been marked resolved', '2026-06-09 16:45:00');

ALTER TABLE wp_civic_activities AUTO_INCREMENT = 24;
ALTER TABLE wp_civic_contacts AUTO_INCREMENT = 11;
ALTER TABLE wp_civic_electoral_areas AUTO_INCREMENT = 15;
ALTER TABLE wp_civic_events AUTO_INCREMENT = 7;
ALTER TABLE wp_civic_event_fields AUTO_INCREMENT = 7;
ALTER TABLE wp_civic_event_registrations AUTO_INCREMENT = 7;
ALTER TABLE wp_civic_reps AUTO_INCREMENT = 9;
ALTER TABLE wp_civic_schedules AUTO_INCREMENT = 8;
ALTER TABLE wp_civic_schedule_notes AUTO_INCREMENT = 6;
ALTER TABLE wp_civic_threads AUTO_INCREMENT = 7;
ALTER TABLE wp_civic_thread_fields AUTO_INCREMENT = 8;
ALTER TABLE wp_civic_thread_responses AUTO_INCREMENT = 7;
