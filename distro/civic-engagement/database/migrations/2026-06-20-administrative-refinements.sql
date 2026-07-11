-- Apply once to existing Civic Platform installations.
-- Replace wp_ with the active WordPress table prefix when necessary.

ALTER TABLE wp_civic_reps
    ADD COLUMN IF NOT EXISTS internal_comment LONGTEXT NULL AFTER status;

ALTER TABLE wp_civic_threads
    ADD COLUMN IF NOT EXISTS starting_response_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_public;

ALTER TABLE wp_civic_schedules
    ADD COLUMN IF NOT EXISTS recent_update LONGTEXT NULL AFTER internal_comment,
    ADD COLUMN IF NOT EXISTS priority INT UNSIGNED NOT NULL DEFAULT 0 AFTER recent_update,
    ADD INDEX IF NOT EXISTS idx_schedule_priority_start_date (priority, start_date);
