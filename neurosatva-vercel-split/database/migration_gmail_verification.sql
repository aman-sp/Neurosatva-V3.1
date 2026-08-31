USE neurosatva;

ALTER TABLE tutors
    ADD COLUMN IF NOT EXISTS gmail_verified_at TIMESTAMP NULL AFTER gmail_verified,
    ADD COLUMN IF NOT EXISTS gmail_verification_token VARCHAR(128) NULL AFTER gmail_verified_at;
