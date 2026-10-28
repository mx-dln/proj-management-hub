<?php
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/includes/Permissions.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }
$error = '';
$success = '';
$departments = db()->query("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'faculty');
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $employeeId = sanitize($_POST['employee_id'] ?? '');
    $departmentId = (int)($_POST['department_id'] ?? 0) ?: null;

    if (!$username || !$email || !$password || !$firstName || !$lastName) $error = 'Please complete all required fields.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Please enter a valid email address.';
    elseif (!in_array($role, ['faculty', 'ets_head'], true)) $error = 'Invalid registration role.';
    elseif (strlen($password) < 6) $error = 'Password must be at least 6 characters.';
    elseif ($password !== $confirmPassword) $error = 'Passwords do not match.';
    else {
        $existing = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $existing->execute([$username, $email]);
        if ($existing->fetch()) $error = 'Username or email is already registered.';
    }

    if (!$error) {
        try {
            db()->beginTransaction();
            $stmt = db()->prepare("INSERT INTO users (username, email, password, role, is_active, registration_status) VALUES (?, ?, ?, ?, 0, 'pending')");
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role === 'ets_head' ? 'admin' : 'faculty']);
            $userId = (int)db()->lastInsertId();
            db()->prepare("INSERT INTO faculty_profiles (user_id, employee_id, first_name, last_name, department_id, email, position) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$userId, $employeeId ?: 'PENDING-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT), $firstName, $lastName, $departmentId, $email, $role === 'ets_head' ? 'ETS Head' : 'Faculty']);
            db()->commit();
            sendAccountEmail($email, trim($firstName . ' ' . $lastName), $username, 'pending');
            $success = 'Registration submitted. An Administrator must approve your account before you can sign in.';
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
            $error = 'Registration could not be submitted. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ETS Project Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-[#0A4A2B] via-[#0F643A] to-[#1E7A4B] flex items-center justify-center p-4">
    <main class="w-full max-w-2xl bg-white/95 backdrop-blur-sm rounded-2xl shadow-2xl p-6 md:p-8">
        <div class="text-center mb-6"><h1 class="text-2xl font-bold text-[#0F643A]">Create an Account</h1><p class="text-[#5C6B63] text-sm mt-1">Register as ETS Head or Faculty</p></div>
        <?php if ($error): ?><div class="mb-4 p-3 rounded-xl bg-[#FEE2E2] border border-[#FECACA] text-[#991B1B] text-sm"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="mb-4 p-3 rounded-xl bg-[#DCFCE7] border border-[#BBF7D0] text-[#166534] text-sm"><?= e($success) ?></div><?php endif; ?>
        <?php if (!$success): ?>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="text-sm font-medium text-[#1A1A1A]">First Name *<input name="first_name" required class="mt-1 w-full rounded-lg border border-[#D8E3DA] p-2.5" value="<?= e($_POST['first_name'] ?? '') ?>"></label>
            <label class="text-sm font-medium text-[#1A1A1A]">Last Name *<input name="last_name" required class="mt-1 w-full rounded-lg border border-[#D8E3DA] p-2.5" value="<?= e($_POST['last_name'] ?? '') ?>"></label>
            <label class="text-sm font-medium text-[#1A1A1A]">Username *<input name="username" required class="mt-1 w-full rounded-lg border border-[#D8E3DA] p-2.5" value="<?= e($_POST['username'] ?? '') ?>"></label>
            <label class="text-sm font-medium text-[#1A1A1A]">Email *<input name="email" type="email" required class="mt-1 w-full rounded-lg border border-[#D8E3DA] p-2.5" value="<?= e($_POST['email'] ?? '') ?>"></label>
            <label class="text-sm font-medium text-[#1A1A1A]">Account Type *<select name="role" class="mt-1 w-full rounded-lg border border-[#D8E3DA] p-2.5"><option value="faculty">Faculty</option><option value="ets_head" <?= ($_POST['role'] ?? '') === 'ets_head' ? 'selected' : '' ?>>ETS Head</option></select></label>
            <label class="text-sm font-medium text-[#1A1A1A]">Employee ID<input name="employee_id" class="mt-1 w-full rounded-lg border border-[#D8E3DA] p-2.5" value="<?= e($_POST['employee_id'] ?? '') ?>"></label>
            <label class="text-sm font-medium text-[#1A1A1A] md:col-span-2">Department<select name="department_id" class="mt-1 w-full rounded-lg border border-[#D8E3DA] p-2.5"><option value="">Select department</option><?php foreach ($departments as $department): ?><option value="<?= (int)$department['id'] ?>" <?= (string)($_POST['department_id'] ?? '') === (string)$department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select></label>
            <label class="text-sm font-medium text-[#1A1A1A]">Password *<input name="password" type="password" required class="mt-1 w-full rounded-lg border border-[#D8E3DA] p-2.5"></label>
            <label class="text-sm font-medium text-[#1A1A1A]">Confirm Password *<input name="confirm_password" type="password" required class="mt-1 w-full rounded-lg border border-[#D8E3DA] p-2.5"></label>
            <div class="md:col-span-2 flex flex-wrap justify-end gap-3 pt-2"><a href="<?= SITE_URL ?>/login.php" class="px-4 py-2.5 rounded-lg border border-[#D8E3DA] text-[#0F643A]">Back to Login</a><button class="px-4 py-2.5 rounded-lg bg-[#0F643A] text-white font-semibold" type="submit"><i class="fas fa-user-plus mr-1"></i> Submit Registration</button></div>
        </form>
        <?php else: ?><div class="text-center"><a href="<?= SITE_URL ?>/login.php" class="inline-flex px-4 py-2.5 rounded-lg bg-[#0F643A] text-white font-semibold">Back to Login</a></div><?php endif; ?>
    </main>
</body>
</html>
