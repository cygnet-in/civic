-- Short URL Routing V1: optional globally validated short codes.

ALTER TABLE wp_civic_threads
    ADD COLUMN short_code varchar(100) NULL AFTER slug,
    ADD UNIQUE KEY uniq_thread_short_code (short_code);

ALTER TABLE wp_civic_events
    ADD COLUMN short_code varchar(100) NULL AFTER slug,
    ADD UNIQUE KEY uniq_event_short_code (short_code);

ALTER TABLE wp_civic_schedules
    ADD COLUMN short_code varchar(100) NULL AFTER slug,
    ADD UNIQUE KEY uniq_schedule_short_code (short_code);
