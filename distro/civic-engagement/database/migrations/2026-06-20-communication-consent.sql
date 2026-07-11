-- Apply once to existing Civic Platform installations.
-- Replace wp_ with the active WordPress table prefix when necessary.

ALTER TABLE wp_civic_contacts
    ADD COLUMN IF NOT EXISTS consent_email TINYINT(1) NOT NULL DEFAULT 0 AFTER latest_electoral_area,
    ADD COLUMN IF NOT EXISTS consent_call TINYINT(1) NOT NULL DEFAULT 0 AFTER consent_email,
    ADD COLUMN IF NOT EXISTS consent_sms TINYINT(1) NOT NULL DEFAULT 0 AFTER consent_call,
    ADD COLUMN IF NOT EXISTS consent_post TINYINT(1) NOT NULL DEFAULT 0 AFTER consent_sms,
    ADD COLUMN IF NOT EXISTS consent_updated_at DATETIME NULL AFTER consent_post;

ALTER TABLE wp_civic_contacts
    ADD INDEX IF NOT EXISTS idx_contact_consent_email (consent_email),
    ADD INDEX IF NOT EXISTS idx_contact_consent_call (consent_call),
    ADD INDEX IF NOT EXISTS idx_contact_consent_sms (consent_sms),
    ADD INDEX IF NOT EXISTS idx_contact_consent_post (consent_post);
