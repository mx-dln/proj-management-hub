<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/DocumentModel.php';

if (!isLoggedIn()) jsonResponse(['success' => false, 'message' => 'Sign in to continue.'], 401);
$stmt = db()->prepare('SELECT role FROM users WHERE id = ? AND is_active = 1 AND deleted_at IS NULL');
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();
if (!$role) jsonResponse(['success' => false, 'message' => 'Account unavailable.'], 403);
$_SESSION['role'] = $role;

try {
    $action = $_GET['action'] ?? '';
    if ($action === 'create') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('POST required.', 405);
        if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) throw new RuntimeException('Upload exceeds the server request limit. Choose a smaller file.', 413);
        if (empty($_SESSION['documents_csrf']) || !is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['documents_csrf'], $_POST['csrf'])) throw new RuntimeException('Session expired. Refresh and try again.', 403);
        $id = DocumentModel::upload($_POST, $_FILES['file'] ?? []);
        jsonResponse(['success' => true, 'message' => 'File uploaded.', 'id' => $id]);
    }
    if (!in_array($action, ['get', 'file'], true)) throw new RuntimeException('Invalid action.', 400);
    $document = DocumentModel::find((int)($_GET['id'] ?? 0));
    if (!DocumentModel::canRead($document)) throw new RuntimeException('Document not found or access denied.', 404);
    if ($action === 'get') {
        $document['has_file'] = false;
        $document['previewable'] = false;
        try {
            $path = DocumentModel::filePath($document);
            $document['has_file'] = true;
            $document['previewable'] = DocumentModel::previewMime($path) !== null;
        } catch (RuntimeException $e) { /* Keep metadata available for legacy missing attachments. */ }
        unset($document['file_path']);
        jsonResponse($document);
    }
    $path = DocumentModel::filePath($document);
    $mime = DocumentModel::previewMime($path);
    $inline = $mime && !isset($_GET['download']);
    header('Content-Type: ' . ($inline ? $mime : 'application/octet-stream'));
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . "; filename=\"download\"; filename*=UTF-8''" . rawurlencode($document['file_name']));
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    header("Content-Security-Policy: sandbox; default-src 'none'; style-src 'unsafe-inline'");
    session_write_close();
    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') readfile($path);
} catch (Throwable $e) {
    $code = in_array($e->getCode(), [400, 403, 404, 405, 413, 422], true) ? $e->getCode() : 500;
    if ($code === 500) error_log('Documents: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => $code === 500 ? 'Could not complete the request. Please try again.' : $e->getMessage()], $code);
}
