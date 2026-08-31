USE neurosatva;

ALTER TABLE tutors
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NULL AFTER email;

ALTER TABLE tutor_registration_requests
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20) NOT NULL DEFAULT '' AFTER email;
