-- Apply once to existing Civic Platform installations.
-- Replace wp_ with the active WordPress table prefix when necessary.

ALTER TABLE wp_civic_reps
    ADD COLUMN IF NOT EXISTS image_attachment_id BIGINT UNSIGNED NULL AFTER details;
