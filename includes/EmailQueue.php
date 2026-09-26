<?php
require_once __DIR__ . '/Mailer.php';

class EmailQueue {
    public const CATEGORIES = [
        'proposal' => 'proposals',
        'report' => 'reports',
        'program' => 'assignments',
        'project' => 'assignments',
        'component' => 'assignments',
        'activity' => 'assignments',
    ];

    public function __construct(private PDO $db, private ?Mailer $mailer = null) {
        $this->mailer ??= new Mailer($db);
    }

    private function ensureTable(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS email_queue (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                notification_id INT NOT NULL,
                category VARCHAR(30) NOT NULL,
                status ENUM('pending', 'sending', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
                attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_error VARCHAR(300) DEFAULT NULL,
                sent_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_email_notification (notification_id),
                KEY idx_email_delivery (status, available_at),
                CONSTRAINT fk_email_notification FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function enqueue(int $notificationId, ?string $entityType): bool {
        $this->ensureTable();
        $category = self::CATEGORIES[$entityType ?? ''] ?? null;
        if ($category === null) return false;
        $config = $this->mailer->settings();
        if ($config['mail_enabled'] !== '1' || $config['mail_' . $category] !== '1') return false;
        $stmt = $this->db->prepare('SELECT u.email FROM notifications n JOIN users u ON u.id = n.user_id WHERE n.id = ? AND u.is_active = 1 AND u.deleted_at IS NULL');
        $stmt->execute([$notificationId]);
        if (!filter_var($stmt->fetchColumn(), FILTER_VALIDATE_EMAIL)) return false;
        $stmt = $this->db->prepare('INSERT INTO email_queue (notification_id, category) VALUES (?, ?) ON DUPLICATE KEY UPDATE notification_id = VALUES(notification_id)');
        $stmt->execute([$notificationId, $category]);
        return true;
    }

    public function status(): array {
        $this->ensureTable();
        $result = ['available' => true, 'pending' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0, 'cancelled' => 0, 'recent' => []];
        try {
            foreach ($this->db->query('SELECT status, COUNT(*) AS total FROM email_queue GROUP BY status') as $row) $result[$row['status']] = (int)$row['total'];
            $result['recent'] = $this->db->query('SELECT q.status, q.attempts, q.last_error, q.created_at, n.title, u.username FROM email_queue q JOIN notifications n ON n.id = q.notification_id JOIN users u ON u.id = n.user_id ORDER BY q.id DESC LIMIT 10')->fetchAll();
        } catch (PDOException $e) {
            $result['available'] = false;
        }
        $config = $this->mailer->settings();
        $result['last_run'] = $config['mail_worker_last_run'] ?? null;
        return $result;
    }

    public function retryFailed(): int {
        $this->ensureTable();
        return $this->db->exec("UPDATE email_queue SET status = 'pending', attempts = 0, last_error = NULL, available_at = NOW() WHERE status = 'failed'");
    }

    public function process(int $limit = 25): array {
        $this->ensureTable();
        if ($this->db->inTransaction()) throw new RuntimeException('Mail delivery must run outside a database transaction.');
        $result = ['sent' => 0, 'retrying' => 0, 'failed' => 0, 'cancelled' => 0, 'paused' => false, 'busy' => false];
        $config = $this->mailer->settings();
        if ($config['mail_enabled'] !== '1') { $result['paused'] = true; return $result; }
        $lockName = 'isu_mail_' . sha1((string)$this->db->query('SELECT DATABASE()')->fetchColumn());
        $lock = $this->db->prepare('SELECT GET_LOCK(?, 0)');
        $lock->execute([$lockName]);
        if ((int)$lock->fetchColumn() !== 1) { $result['busy'] = true; return $result; }
        try {
            $this->db->exec("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES ('mail_worker_last_run', NOW(), 'mail') ON DUPLICATE KEY UPDATE setting_value = NOW()");
            // A crashed worker may leave a claimed message behind; the DB lock prevents parallel sends.
            $this->db->exec("UPDATE email_queue SET status = IF(attempts >= 3, 'failed', 'pending'), last_error = 'Delivery was interrupted; check whether the recipient received it.', available_at = NOW() WHERE status = 'sending' AND updated_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
            $limit = max(1, min(100, $limit));
            $rows = $this->db->query("SELECT q.id, q.category, q.attempts, n.title, n.message, n.action_url, u.email, u.username, u.is_active, u.deleted_at FROM email_queue q JOIN notifications n ON n.id = q.notification_id JOIN users u ON u.id = n.user_id WHERE q.status = 'pending' AND q.available_at <= NOW() ORDER BY q.id LIMIT {$limit}")->fetchAll();
            foreach ($rows as $row) {
                $config = $this->mailer->settings();
                if ($config['mail_enabled'] !== '1') { $result['paused'] = true; break; }
                if (!$row['is_active'] || $row['deleted_at'] !== null || ($config['mail_' . $row['category']] ?? '0') !== '1' || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                    $this->db->prepare("UPDATE email_queue SET status = 'cancelled', last_error = 'Recipient unavailable or notification type disabled.' WHERE id = ?")->execute([$row['id']]);
                    $result['cancelled']++;
                    continue;
                }
                $this->db->prepare("UPDATE email_queue SET status = 'sending', attempts = attempts + 1 WHERE id = ?")->execute([$row['id']]);
                try {
                    $this->mailer->send($row['email'], $row['username'], $row['title'], $row['message'], $row['action_url'] ?? '');
                } catch (Throwable $e) {
                    $attempts = (int)$row['attempts'] + 1;
                    $status = $attempts >= 3 ? 'failed' : 'pending';
                    $delay = $attempts * 5;
                    $error = $e instanceof RuntimeException || $e instanceof InvalidArgumentException ? $e->getMessage() : 'Email delivery failed. Check mail settings and the server configuration.';
                    $this->db->prepare("UPDATE email_queue SET status = ?, last_error = ?, available_at = DATE_ADD(NOW(), INTERVAL {$delay} MINUTE) WHERE id = ?")->execute([$status, substr($error, 0, 300), $row['id']]);
                    $result[$status === 'failed' ? 'failed' : 'retrying']++;
                    continue;
                }
                $this->db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW(), last_error = NULL WHERE id = ?")->execute([$row['id']]);
                $result['sent']++;
            }
        } finally {
            $this->db->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);
        }
        return $result;
    }
}
