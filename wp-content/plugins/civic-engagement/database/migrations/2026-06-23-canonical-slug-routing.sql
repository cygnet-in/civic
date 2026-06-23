-- Canonical Slug Routing V1: schedule slugs for prefixed public URLs.
-- Existing schedules receive deterministic unique values and can then be
-- adjusted by administrators in the Schedule edit screen.

ALTER TABLE wp_civic_schedules
    ADD COLUMN slug varchar(255) NULL AFTER title;

UPDATE wp_civic_schedules
SET slug = CONCAT('schedule-', id)
WHERE slug IS NULL OR slug = '';

ALTER TABLE wp_civic_schedules
    MODIFY COLUMN slug varchar(255) NOT NULL,
    ADD UNIQUE KEY uniq_schedule_slug (slug);
