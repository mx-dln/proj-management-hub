-- Development-only credentials for local testing.
-- Password for every account below: password
-- Replace these credentials before deploying to production.
UPDATE users
SET password = '$2y$12$8n9cOyUmSQAsk7O.qThQse9y.EabUE/Z1hl8Sofn1dBtUbdS3oX3e'
WHERE username IN ('admin', 'etshead', 'faculty1', 'faculty2', 'faculty3', 'faculty4', 'viewer')
  AND deleted_at IS NULL;
