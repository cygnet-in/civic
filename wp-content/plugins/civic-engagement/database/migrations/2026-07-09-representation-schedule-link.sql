-- Apply once to existing Civic Platform installations.
-- Replace wp_ with the active WordPress table prefix when necessary.

ALTER TABLE wp_civic_reps
    ADD COLUMN IF NOT EXISTS schedule_id BIGINT UNSIGNED NULL AFTER internal_comment,
    ADD UNIQUE KEY IF NOT EXISTS uniq_rep_schedule (schedule_id);
