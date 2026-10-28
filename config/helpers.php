<?php
session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

function db() { return Database::getInstance()->getConnection(); }

function isLoggedIn() { return isset($_SESSION['user_id']); }

function requireLogin() {
    if (!isLoggedIn()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

function currentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'name' => $_SESSION['user_name'] ?? '',
    ];
}

function sanitize($input) {
    if (is_array($input)) return array_map('sanitize', $input);
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

function generateCode($prefix, $table, $column) {
    $year = date('Y');
    $sql = "SELECT COUNT(*) as count FROM {$table} WHERE YEAR(created_at) = ?";
    $stmt = db()->prepare($sql);
    $stmt->execute([$year]);
    $count = $stmt->fetch()['count'] + 1;
    return $prefix . '-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

function uploadFile($file, $directory, $allowedTypes = []) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    $uploadDir = UPLOAD_PATH . $directory . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    $fileName = uniqid() . '_' . time() . '.' . $fileType;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
        return ['success' => true, 'file_name' => $fileName, 'file_path' => $directory . '/' . $fileName, 'original_name' => $file['name']];
    }
    return ['success' => false, 'error' => 'Upload failed'];
}

function uploadFiles($files, $directory, $allowedTypes = []) {
    if (!isset($files['name'])) return [];
    $names = is_array($files['name']) ? $files['name'] : [$files['name']];
    $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $errors = is_array($files['error'] ?? null) ? $files['error'] : [($files['error'] ?? UPLOAD_ERR_OK)];
    $sizes = is_array($files['size'] ?? null) ? $files['size'] : [($files['size'] ?? 0)];
    $types = is_array($files['type'] ?? null) ? $files['type'] : [($files['type'] ?? '')];
    $uploaded = [];
    foreach ($names as $index => $name) {
        if ($name === '' || ($errors[$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $result = uploadFile([
            'name' => $name,
            'tmp_name' => $tmpNames[$index] ?? '',
            'error' => $errors[$index] ?? UPLOAD_ERR_OK,
            'size' => $sizes[$index] ?? 0,
            'type' => $types[$index] ?? '',
        ], $directory, $allowedTypes);
        if (!$result['success']) return ['success' => false, 'error' => $result['error'] ?? 'One of the files could not be uploaded.'];
        $uploaded[] = $result;
    }
    return $uploaded;
}

function attachmentPaths($row) {
    $paths = [];
    if (!empty($row['attachments'])) {
        $decoded = json_decode($row['attachments'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                $path = is_array($item) ? ($item['path'] ?? $item['file_path'] ?? '') : $item;
                if ($path) $paths[] = $path;
            }
        }
    }
    if (!empty($row['attachment']) && !in_array($row['attachment'], $paths, true)) array_unshift($paths, $row['attachment']);
    return array_values(array_unique($paths));
}

function formatCurrency($amount) { return '₱' . number_format($amount, 2); }
function formatDate($date, $format = DATE_FORMAT) { return empty($date) ? '-' : date($format, strtotime($date)); }
function formatDateTime($datetime, $format = DATETIME_FORMAT) { return empty($datetime) ? '-' : date($format, strtotime($datetime)); }

function timeAgo($datetime) {
    if (empty($datetime)) return '-';
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return formatDate($datetime);
}

function getStatusBadge($status) {
    $classes = [
        'draft' => 'bg-[#374151] text-[#E5E7EB]',
        'pending' => 'bg-[#78350F] text-[#FCD34D]',
        'submitted' => 'bg-[#1E3A8A] text-[#BFDBFE]',
        'under_review' => 'bg-[#1E3A8A] text-[#BFDBFE]',
        'active' => 'bg-[#14532D] text-[#86EFAC]',
        'ongoing' => 'bg-[#14532D] text-[#86EFAC]',
        'approved' => 'bg-[#14532D] text-[#86EFAC]',
        'completed' => 'bg-[#14532D] text-[#86EFAC]',
        'archived' => 'bg-[#374151] text-[#9CA3AF]',
        'returned' => 'bg-[#78350F] text-[#FCD34D]',
        'rejected' => 'bg-[#7F1D1D] text-[#FCA5A5]',
        'cancelled' => 'bg-[#7F1D1D] text-[#FCA5A5]',
        'expired' => 'bg-[#7F1D1D] text-[#FCA5A5]',
        'terminated' => 'bg-[#7F1D1D] text-[#FCA5A5]',
        'planned' => 'bg-[#374151] text-[#E5E7EB]',
        'in_progress' => 'bg-[#1E3A8A] text-[#BFDBFE]',
        'generated' => 'bg-[#1E3A8A] text-[#BFDBFE]',
        'printed' => 'bg-[#78350F] text-[#FCD34D]',
        'claimed' => 'bg-[#14532D] text-[#86EFAC]',
    ];
    $labels = ['under_review' => 'Under Review', 'in_progress' => 'In Progress'];
    $class = $classes[$status] ?? 'bg-[#374151] text-[#E5E7EB]';
    $label = $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    return '<span class="px-2.5 py-0.5 rounded-full text-xs font-medium ' . $class . '">' . $label . '</span>';
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function auditLog($action, $entityType = null, $entityId = null, $description = '', $isOverride = false) {
    try {
        $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, is_override) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        db()->prepare($sql)->execute([$_SESSION['user_id'] ?? null, $action, $entityType, $entityId, $description, $_SERVER['REMOTE_ADDR'] ?? '', $_SERVER['HTTP_USER_AGENT'] ?? '', $isOverride ? 1 : 0]);
    } catch (Exception $e) { error_log("Audit log error: " . $e->getMessage()); }
}

function createNotification($userId, $title, $message, $type = 'info', $entityType = null, $entityId = null, $actionUrl = '') {
    try {
        $sql = "INSERT INTO notifications (user_id, title, message, type, entity_type, entity_id, action_url) VALUES (?, ?, ?, ?, ?, ?, ?)";
        db()->prepare($sql)->execute([$userId, $title, $message, $type, $entityType, $entityId, $actionUrl]);
        $notificationId = (int)db()->lastInsertId();
    } catch (Exception $e) { error_log("Notification error: " . $e->getMessage()); }
    if (isset($notificationId)) {
        try {
            require_once __DIR__ . '/../includes/EmailQueue.php';
            (new EmailQueue(db()))->enqueue($notificationId, $entityType);
        } catch (Throwable $e) {
            error_log('Email queue could not accept notification ' . $notificationId . '. Check the mail migration and configuration.');
        }
    }
}

function sendAccountEmail($email, $name, $username, $status = 'approved') {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    try {
        require_once __DIR__ . '/../includes/Mailer.php';
        $mailer = new Mailer(db());
        $settings = $mailer->settings();
        if (($settings['mail_enabled'] ?? '0') !== '1' || ($settings['mail_accounts'] ?? '1') !== '1') return false;
        $isPending = $status === 'pending';
        $title = $isPending ? 'Registration received - ISU Project Hub' : 'Your ISU Project Hub account was created';
        $message = $isPending
            ? "Hello {$name},\n\nYour account registration for ISU Project Hub has been received. An Administrator will review and approve your account before you can sign in.\n\nUsername: {$username}"
            : "Hello {$name},\n\nAn Administrator created your ISU Project Hub account.\n\nUsername: {$username}\n\nPlease contact the Administrator for your temporary password or password instructions. You can sign in after receiving them.";
        $mailer->send($email, $name, $title, $message, '/index.php?module=notifications');
        return true;
    } catch (Throwable $e) {
        error_log('Account email delivery failed: ' . $e->getMessage());
        return false;
    }
}

function getUnreadNotificationCount($userId) {
    $stmt = db()->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetch()['count'];
}

function getSetting($key, $default = '') {
    $stmt = db()->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

function flash($key, $message = null) {
    if ($message !== null) { $_SESSION['flash'][$key] = $message; }
    else { $msg = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $msg; }
}
