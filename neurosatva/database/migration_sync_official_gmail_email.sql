USE neurosatva;

UPDATE tutors
SET email = official_gmail
WHERE official_gmail IS NOT NULL
    AND official_gmail <> ''
    AND gmail_verified = 1
    AND email <> official_gmail;
