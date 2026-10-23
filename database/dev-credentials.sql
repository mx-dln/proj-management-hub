-- Development-only credentials for local testing.
-- Password for every account below: Dev@12345
-- Replace these credentials before deploying to production.
UPDATE users
SET password = '$2y$12$KaxEyuT4SHF28qjcgqDJ3utvI/4x0Fd9yd5E8ljQvi4Iz3QUlewom'
WHERE username IN ('admin', 'etshead', 'faculty1', 'faculty2', 'faculty3', 'faculty4', 'viewer')
  AND deleted_at IS NULL;
