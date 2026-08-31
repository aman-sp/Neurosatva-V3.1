USE neurosatva;

ALTER TABLE tutors
    ADD COLUMN IF NOT EXISTS gmail_otp_hash VARCHAR(255) NULL AFTER gmail_verification_token,
    ADD COLUMN IF NOT EXISTS gmail_otp_expires_at TIMESTAMP NULL AFTER gmail_otp_hash,
    ADD COLUMN IF NOT EXISTS gmail_otp_attempts INT NOT NULL DEFAULT 0 AFTER gmail_otp_expires_at;
