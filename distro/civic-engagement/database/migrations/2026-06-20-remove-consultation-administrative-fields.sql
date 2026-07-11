-- Apply only when the earlier administrative refinements migration added these
-- fields to wp_civic_threads. They belong on individual consultation responses.
-- Replace wp_ with the active WordPress table prefix when necessary.

ALTER TABLE wp_civic_threads
    DROP COLUMN IF EXISTS administrative_status;

ALTER TABLE wp_civic_threads
    DROP COLUMN IF EXISTS internal_comment;
