<?php
require_once __DIR__ . '/../includes/Permissions.php';

class DocumentModel {
    public const ENTITY_TABLES = ['program' => 'programs', 'project' => 'projects', 'component' => 'components', 'activity' => 'extension_activities'];
    public const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

    public static function designationCategory() {
        return (int)db()->query("SELECT id FROM document_categories WHERE name = 'Designation' AND is_active = 1 ORDER BY id LIMIT 1")->fetchColumn();
    }

    public static function uploadLimit() {
        $limits = [100 * 1024 * 1024];
        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $bytes = ini_parse_quantity(ini_get($key));
            if ($bytes > 0) $limits[] = $key === 'post_max_size' ? max(0, $bytes - 65536) : $bytes;
        }
        return min($limits);
    }

    public static function entityExists($type, $id) {
        $table = self::ENTITY_TABLES[$type] ?? null;
        if (!$table || $id < 1) return false;
        $stmt = db()->prepare("SELECT id FROM {$table} WHERE id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        return (bool)$stmt->fetchColumn();
    }

    public static function canRead($document) {
        if (!$document || !self::entityExists($document['entity_type'], $document['entity_id'])) return false;
        return Permissions::isAdmin() || Permissions::isViewer()
            || Permissions::canUploadDocument($document['entity_type'], $document['entity_id']);
    }

    public static function uploadTargets() {
        if (!Permissions::isAdmin() && !Permissions::isFaculty()) return [];
        $facultyId = Permissions::isFaculty() ? Permissions::getFacultyId() : null;
        if (Permissions::isFaculty() && !$facultyId) return [];
        $targets = [];
        foreach (self::ENTITY_TABLES as $type => $table) {
            $sql = "SELECT id, title FROM {$table} WHERE deleted_at IS NULL";
            if ($facultyId) $sql .= " AND id IN (SELECT {$type}_id FROM {$type}_assignments WHERE faculty_id = ? AND is_active = 1)";
            $stmt = db()->prepare($sql . ' ORDER BY title');
            $stmt->execute($facultyId ? [$facultyId] : []);
            foreach ($stmt->fetchAll() as $row) {
                $targets[] = ['value' => $type . ':' . $row['id'], 'type' => $type, 'id' => (int)$row['id'], 'label' => ucfirst($type) . ': ' . $row['title']];
            }
        }
        return $targets;
    }

    public static function find($id) {
        $stmt = db()->prepare("SELECT d.*, dc.name AS category_name, u.username AS uploader_name FROM documents d LEFT JOIN document_categories dc ON d.category_id = dc.id LEFT JOIN users u ON d.uploaded_by = u.id WHERE d.id = ? AND d.deleted_at IS NULL");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function filePath($document) {
        $base = realpath(UPLOAD_PATH);
        $path = realpath(UPLOAD_PATH . $document['file_path']);
        if (!$base || !$path || !str_starts_with($path, $base . DIRECTORY_SEPARATOR) || !is_file($path)) throw new RuntimeException('The attachment is unavailable.', 404);
        return $path;
    }

    public static function previewMime($path) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        return in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'text/plain'], true) ? $mime : null;
    }

    public static function upload($input, $file) {
        $type = $input['entity_type'] ?? '';
        $id = (int)($input['entity_id'] ?? 0);
        if (!self::entityExists($type, $id)) throw new RuntimeException('Select an existing record.', 422);
        if (!Permissions::canUploadDocument($type, $id)) throw new RuntimeException('You cannot upload to this record.', 403);
        $isDesignation = ($input['scope'] ?? '') === 'designations';
        if ($isDesignation && !Permissions::isAdmin()) throw new RuntimeException('You cannot upload designation files.', 403);
        $category = $isDesignation ? self::designationCategory() : (int)($input['category_id'] ?? 0);
        $stmt = db()->prepare('SELECT id FROM document_categories WHERE id = ? AND is_active = 1');
        $stmt->execute([$category]);
        if (!$stmt->fetchColumn()) throw new RuntimeException('Select an active document category.', 422);
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) throw new RuntimeException('File exceeds the server upload limit.', 413);
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) throw new RuntimeException('Select a file and try again.', 422);
        $name = basename(str_replace('\\', '/', $file['name']));
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($extension, self::EXTENSIONS, true)) throw new RuntimeException('This file type is not supported.', 422);
        $size = filesize($file['tmp_name']);
        if ($size > self::uploadLimit()) throw new RuntimeException('File exceeds the current upload limit.', 413);
        if ($size === 0) throw new RuntimeException('The selected file is empty.', 422);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $expected = ['pdf' => ['application/pdf'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'txt' => ['text/plain']];
        if (isset($expected[$extension]) && !in_array($mime, $expected[$extension], true)) throw new RuntimeException('The file contents do not match its extension.', 422);
        // Office formats are downloaded, never rendered as active content.
        if (!isset($expected[$extension]) && !in_array($mime, ['application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/x-ole-storage', 'application/CDFV2', 'application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'], true)) throw new RuntimeException('Unsupported Office file contents.', 422);
        $title = trim($input['title'] ?? '') ?: $name;
        if (mb_strlen($title) > 255 || mb_strlen($name) > 255) throw new RuntimeException('Title and filename must be at most 255 characters.', 422);
        $result = uploadFile($file, 'documents/private', self::EXTENSIONS);
        if (!$result['success']) throw new RuntimeException('The file could not be stored.', 500);
        try {
            db()->prepare('INSERT INTO documents (title, category_id, entity_type, entity_id, file_path, file_name, file_type, file_size, description, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute([
                $title, $category, $type, $id, $result['file_path'], $name, $extension, $size, trim($input['description'] ?? ''), Permissions::getUserId(),
            ]);
        } catch (Throwable $e) {
            unlink(UPLOAD_PATH . $result['file_path']);
            throw $e;
        }
        $documentId = (int)db()->lastInsertId();
        auditLog('create', 'document', $documentId, 'Uploaded document: ' . $title);
        return $documentId;
    }
}
