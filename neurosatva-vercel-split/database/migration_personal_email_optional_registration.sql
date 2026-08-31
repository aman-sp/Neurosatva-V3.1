ALTER TABLE tutors
    ADD COLUMN IF NOT EXISTS personal_email VARCHAR(190) NULL AFTER email;

UPDATE tutors
LEFT JOIN tutor_registration_requests requests
    ON requests.full_name = tutors.name
    AND requests.phone = tutors.phone
    AND requests.status = 'Approved'
SET tutors.personal_email = COALESCE(requests.email, tutors.email)
WHERE tutors.personal_email IS NULL;

ALTER TABLE tutor_registration_requests
    MODIFY school_name VARCHAR(160) NULL,
    MODIFY gender VARCHAR(40) NULL;
