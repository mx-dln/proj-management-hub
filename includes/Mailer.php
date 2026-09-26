<?php

use PHPMailer\PHPMailer\PHPMailer;

class Mailer {
    public const DEFAULTS = [
        'mail_enabled' => '0',
        'mail_host' => '',
        'mail_port' => '587',
        'mail_encryption' => 'tls',
        'mail_auth' => '1',
        'mail_username' => '',
        'mail_password' => '',
        'mail_from_email' => '',
        'mail_from_name' => 'ISU-Cauayan Extension Services',
        'mail_reply_to' => '',
        'mail_app_url' => '',
        'mail_proposals' => '1',
        'mail_reports' => '1',
        'mail_assignments' => '1',
    ];

    public function __construct(private PDO $db, private ?string $keyPath = null) {
        $this->keyPath ??= __DIR__ . '/../config/mail.key';
    }

    public function settings(): array {
        $values = $this->db->query("SELECT setting_key, setting_value FROM settings WHERE setting_group = 'mail'")->fetchAll(PDO::FETCH_KEY_PAIR);
        return array_replace(self::DEFAULTS, $values);
    }

    public function publicSettings(): array {
        $config = $this->settings();
        $config['password_configured'] = $config['mail_password'] !== '';
        unset($config['mail_password']);
        return $config;
    }

    public function save(array $input): void {
        $config = $this->settings();
        foreach (self::DEFAULTS as $key => $default) {
            if ($key === 'mail_password') continue;
            if (in_array($key, ['mail_enabled', 'mail_auth', 'mail_proposals', 'mail_reports', 'mail_assignments'], true)) {
                $config[$key] = ($input[$key] ?? '0') === '1' ? '1' : '0';
            } else {
                if (isset($input[$key]) && !is_string($input[$key])) throw new InvalidArgumentException('Invalid mail setting.');
                $config[$key] = trim($input[$key] ?? $default);
            }
        }
        if (!is_string($input['mail_password'] ?? '')) throw new InvalidArgumentException('Invalid SMTP password.');
        if (($input['clear_password'] ?? '') === '1') $config['mail_password'] = '';
        if (($input['mail_password'] ?? '') !== '') {
            if (strlen($input['mail_password']) > 4096) throw new InvalidArgumentException('SMTP password is too long.');
            $config['mail_password'] = $this->encrypt($input['mail_password']);
        }
        $config['mail_app_url'] = rtrim($config['mail_app_url'], '/');
        $this->validate($config, $config['mail_enabled'] === '1');
        if ($config['mail_enabled'] === '1' && $config['mail_auth'] === '1') $this->decrypt($config['mail_password']);
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, 'mail') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = 'mail'");
            foreach (self::DEFAULTS as $key => $default) $stmt->execute([$key, $config[$key]]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function validate(array $config, bool $required = true): void {
        foreach (['mail_host', 'mail_username', 'mail_from_email', 'mail_from_name', 'mail_reply_to', 'mail_app_url'] as $key) {
            if (strlen($config[$key]) > 255 || preg_match('/[\x00-\x1f\x7f]/', $config[$key])) {
                throw new InvalidArgumentException('Mail settings cannot contain control characters or exceed 255 characters.');
            }
        }
        if (!ctype_digit($config['mail_port']) || (int)$config['mail_port'] < 1 || (int)$config['mail_port'] > 65535) {
            throw new InvalidArgumentException('SMTP port must be between 1 and 65535.');
        }
        if (!in_array($config['mail_encryption'], ['tls', 'ssl', 'none'], true)) throw new InvalidArgumentException('Choose a valid SMTP encryption option.');
        $host = $config['mail_host'];
        if (($required || $host !== '') && !filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidArgumentException('Enter an SMTP hostname without a protocol or port.');
        }
        foreach (['mail_from_email' => $required, 'mail_reply_to' => false] as $key => $needed) {
            if (($needed || $config[$key] !== '') && !filter_var($config[$key], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid sender and reply-to email address.');
        }
        if ($required || $config['mail_app_url'] !== '') {
            $url = parse_url($config['mail_app_url']);
            if (!filter_var($config['mail_app_url'], FILTER_VALIDATE_URL) || !in_array($url['scheme'] ?? '', ['http', 'https'], true) || isset($url['user']) || isset($url['query']) || isset($url['fragment'])) {
                throw new InvalidArgumentException('Enter the application URL, for example https://projects.example.edu.');
            }
        }
        if ($required && $config['mail_auth'] === '1' && ($config['mail_username'] === '' || $config['mail_password'] === '')) throw new InvalidArgumentException('SMTP username and password are required when authentication is enabled.');
        if ($config['mail_auth'] === '1' && $config['mail_encryption'] === 'none') throw new InvalidArgumentException('Use STARTTLS or SSL/TLS when sending SMTP credentials.');
    }

    private function key(bool $create): string {
        if (!is_file($this->keyPath) && $create) {
            $handle = @fopen($this->keyPath, 'x');
            if ($handle) {
                chmod($this->keyPath, 0600);
                fwrite($handle, base64_encode(random_bytes(32)));
                fclose($handle);
            }
        }
        $key = is_file($this->keyPath) ? base64_decode(trim((string)file_get_contents($this->keyPath)), true) : false;
        if ($key === false || strlen($key) !== 32) throw new RuntimeException('SMTP encryption key is unavailable. Restore config/mail.key or clear and re-enter the SMTP password.');
        return $key;
    }

    private function encrypt(string $password): string {
        $nonce = random_bytes(12);
        $cipher = openssl_encrypt($password, 'aes-256-gcm', $this->key(true), OPENSSL_RAW_DATA, $nonce, $tag);
        if ($cipher === false) throw new RuntimeException('Could not protect the SMTP password.');
        return base64_encode($nonce . $tag . $cipher);
    }

    private function decrypt(string $cipher): string {
        $bytes = base64_decode($cipher, true);
        if ($bytes === false || strlen($bytes) < 29) throw new RuntimeException('Saved SMTP password is invalid. Re-enter it in Settings.');
        $password = openssl_decrypt(substr($bytes, 28), 'aes-256-gcm', $this->key(false), OPENSSL_RAW_DATA, substr($bytes, 0, 12), substr($bytes, 12, 16));
        if ($password === false) throw new RuntimeException('Saved SMTP password cannot be read. Re-enter it in Settings.');
        return $password;
    }

    public static function content(string $title, string $message, string $actionUrl, string $baseUrl): array {
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $message = html_entity_decode($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Only link to application pages; notification payloads cannot choose an external email link.
        $query = [];
        parse_str((string)parse_url($actionUrl, PHP_URL_QUERY), $query);
        $modules = ['proposals', 'reports', 'programs', 'projects', 'components', 'activities', 'partners', 'explorer', 'notifications'];
        $module = is_string($query['module'] ?? null) && in_array($query['module'], $modules, true) ? $query['module'] : 'notifications';
        $url = rtrim($baseUrl, '/') . '/index.php?module=' . rawurlencode($module);
        $escape = fn($s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#17212b;line-height:1.6">'
            . '<p style="color:#0f643a;font-weight:bold">ISU-Cauayan Extension Services</p>'
            . '<h2>' . $escape($title) . '</h2><p>' . nl2br($escape($message)) . '</p>'
            . '<p><a style="color:#0f643a" href="' . $escape($url) . '">Open Project Hub</a></p>'
            . '<p style="color:#68737d;font-size:12px">Sign in with your account to view the update.</p></body></html>';
        return ['subject' => preg_replace('/[\r\n]+/', ' ', $title), 'html' => $html, 'text' => $title . "\n\n" . $message . "\n\nOpen Project Hub: " . $url];
    }

    public function send(string $email, string $name, string $title, string $message, string $actionUrl): void {
        $config = $this->settings();
        $this->validate($config);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('The recipient does not have a valid email address.');
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) throw new RuntimeException('Mail library is unavailable. Run composer install on the server.');
        require_once $autoload;
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['mail_host'];
        $mail->Port = (int)$config['mail_port'];
        $mail->SMTPSecure = $config['mail_encryption'] === 'none' ? '' : $config['mail_encryption'];
        $mail->SMTPAutoTLS = $config['mail_encryption'] !== 'none';
        $mail->SMTPAuth = $config['mail_auth'] === '1';
        if ($mail->SMTPAuth) {
            $mail->Username = $config['mail_username'];
            $mail->Password = $this->decrypt($config['mail_password']);
        }
        $mail->Timeout = 10;
        $mail->getSMTPInstance()->Timelimit = 20;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($config['mail_from_email'], $config['mail_from_name']);
        if ($config['mail_reply_to'] !== '') $mail->addReplyTo($config['mail_reply_to']);
        $mail->addAddress($email, $name);
        $content = self::content($title, $message, $actionUrl, $config['mail_app_url']);
        $mail->isHTML(true);
        $mail->Subject = $content['subject'];
        $mail->Body = $content['html'];
        $mail->AltBody = $content['text'];
        try {
            $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            // Do not expose server responses or credentials in HTTP responses or queue logs.
            $error = strtolower($mail->ErrorInfo);
            $reason = str_contains($error, 'authenticat') ? 'SMTP authentication failed. Check the username and password.'
                : (str_contains($error, 'connect') ? 'SMTP connection failed. Check the host, port, TLS settings, and network access.'
                : 'SMTP server did not accept the email. Check the sender address and provider settings.');
            throw new RuntimeException($reason);
        } finally {
            $mail->smtpClose();
        }
    }
}
