# Project Explorer

Explorer uses the existing PHP/PDO, session authentication, role/assignment rules,
Font Awesome, and vanilla JavaScript. Its only client assets are
`assets/js/explorer.js` and `assets/css/explorer.css`; requests use `ajax/drive.php`.
The route remains `index.php?module=explorer&folder=<node-id>`.

## Organizational placement

The existing business schema is Program > Project > Component > Activity.
Explorer presents Project > Program > Components and Project > Program >
Activities through references in
`explorer_nodes`. Existing project/program foreign keys are not reversed or
rewritten. A shared existing program has a placement under each linked project;
each placement shows only that project's components and activities. The
`Components` and `Activities` folders are Explorer system folders, not document
categories. New organizational nodes create actual business records. Explorer
renames, moves and deletes change the Explorer placement only, preserving other
modules' business relationships.

The migration imports existing active projects and their linked programs,
components and activities into the two program system folders. Projects without programs remain empty until a
program is created. A project with components but no active program aborts the
initial placement transaction with an actionable message. Programs that are not
linked to any project are preserved in their original module. No document
category records or uploads are imported.

After initial migration, Explorer is the creation workflow for new placements.
Business records created separately by old module routes are not automatically
placed into Explorer. The migration has a version marker and will not overwrite
later folder moves when rerun.

## Setup

1. Run `php database/backup-explorer.php` before migrating.
2. Run `php database/migrate-explorer.php` once per database.
3. Deploy the root `.htaccess` and `uploads/explorer/.htaccess` with the code.

The backup writes a consistent database SQL snapshot and a ZIP containing the
workspace, uploads, and Git data under `database/backups/explorer-<timestamp>`.
The SQL is for restoration into an empty database, not importing over live data.
The database and backup directories must not be publicly served.

Apache rules deny direct access to Explorer upload bytes. Other web servers must
deny `/uploads/explorer/` and the existing private directories equivalently.
Files are streamed through the authenticated API with MIME checks and range
support. Storage uses the existing upload helper under organizational IDs and
an immutable upload-folder ID; moving a file updates metadata without copying
bytes. Supported files include common Office, PDF, image, media, ZIP, CSV and text
formats. The application limit is 100 MB; PHP upload/post limits may be lower.
Explorer uploads use a client-side queue with per-file progress. Users can keep
navigating inside Explorer while queued uploads continue, and the floating queue
panel stays visible until the active uploads finish. Browsers do not allow a page
script to continue uploads after a full page unload; Explorer warns before
leaving the page while uploads are active. File drops and folder drops are
accepted in uploadable Component, Activity, and normal folder locations. Folder
drops recreate the dropped folder path below the target folder.

For a local PHP preview, create `logs/sessions` and a writable upload temporary
directory, then start PHP with `database/explorer-router.php` as the router. This
router applies the private-directory restrictions that `.htaccess` normally
provides. Configure PHP's `upload_tmp_dir` to a writable absolute path.

## Permissions And Deletion

Administrators manage organizational placements. Viewers have read-only access.
Faculty can browse assigned entities and their ancestors; activity folder/file
writes use the existing activity upload permission. Only administrators can move
items across activities. Every file read, listing, detail lookup and mutation is
authorized server-side. Mutations require POST and a session CSRF token.

Moves are serialized with a database advisory lock and validate destination type,
self moves, descendants, duplicate names and destination permission. Folder depth
has no explicit limit. Deletion requires a dialog confirmation, then marks the
visible subtree with `deleted_at`; physical bytes and business records remain.
There is no automatic purge. Administrative recovery can clear `deleted_at` for
the explicitly selected deleted rows and their deleted ancestors, after checking
for destination name collisions. A full recovery can use the pre-migration SQL
and workspace archive in an isolated restoration environment.

## Verification

`php database/test-explorer.php` creates and drops a randomly named test database;
it checks creation of all organizational levels, 72 nested folders, move cycle
protection, duplicate/traversal names, scoped search, assigned faculty, read-only
viewers, deleted ancestors, and recursive soft deletion.

`php database/test-explorer-http.php http://127.0.0.1:8088` uses the local demo login
and disposable Explorer folders. It checks CSRF, the complete hierarchy, upload,
preview/download bytes, range requests, rename/move, deleted-file denial, and
direct-storage denial. Its fixtures are soft-deleted in a finally block.
