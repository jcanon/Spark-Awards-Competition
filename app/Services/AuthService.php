<?php

namespace App\Services;

use App\Models\Accounts\UserModel;
use App\Models\Auth\PasswordHistoryModel;
use App\Models\Auth\PasswordResetModel;
use CodeIgniter\Database\BaseConnection;
use Config\AuthPolicy;
use CodeIgniter\I18n\Time;

class AuthService
{
    private AuthPolicy $policy;

    public function __construct(?AuthPolicy $policy = null)
    {
        $this->policy = $policy ?? config(AuthPolicy::class);
    }

    public function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            return false;
        }

        $user = db_connect()->query(
            "SELECT *
               FROM comp_users
              WHERE LOWER(email_address) = ?
                AND account_active = 'Yes'
              LIMIT 1",
            [$email]
        )->getRowArray();

        if (!$user) {
            return false;
        }

        $stored = (string)($user['password'] ?? '');
        $salt = rtrim((string)($user['salt'] ?? ''), " \t\n\r\0\x0B");

        $info = password_get_info($stored);
        $isModernFormat = ($info['algo'] !== 0);
        $verifiedModern = $isModernFormat && password_verify($password, $stored);

        $verifiedLegacy = false;
        if (!$verifiedModern && $salt !== '' && strlen($stored) === 128 && ctype_xdigit($stored)) {
            $legacy = hash('sha512', $password . $salt);
            $verifiedLegacy = hash_equals(strtolower($stored), strtolower($legacy));
        }

        if (!$verifiedModern && !$verifiedLegacy) {
            return false;
        }

        if ($verifiedLegacy) {
            try {
                $updated = $this->setUserPassword((string)$user['user_id'], $password, false, 'legacy_migration');
                if ($updated === 'ok') {
                    $user['password_changed_at'] = Time::now()->toDateTimeString();
                }
            } catch (\Throwable $e) {
                // non-fatal
            }
        }

        $isAdmin = (($user['is_admin'] ?? 'No') === 'Yes');
        $isEditor = (($user['is_editor'] ?? 'No') === 'Yes');
        $isJudge = (($user['is_judge'] ?? 'No') === 'Yes');

        $role = $isAdmin ? 'admin' : ($isEditor ? 'editor' : ($isJudge ? 'judge' : 'user'));
        $requires2fa = $isAdmin || $isEditor;

        session()->regenerate(true);

        $name = trim(((string)($user['first_name'] ?? '')) . ' ' . ((string)($user['last_name'] ?? '')));
        if ($name === '') {
            $name = (string)($user['company_name'] ?? $user['email_address'] ?? '');
        }

        session()->set([
            'uid' => (string)$user['user_id'],
            'role' => $role,
            'is_admin' => $isAdmin,
            'is_editor' => $isEditor,
            'is_judge' => $isJudge,
            '2fa_pass' => !$requires2fa,
            'name' => $name,
            'password_reset_required' => $this->policy->enforcePasswordExpiry
                && $this->isPasswordExpired((string)($user['password_changed_at'] ?? '')),
        ]);

        // Removed last_login_at update to avoid empty-dataset error.
        // If you want it, add 'last_login_at' to UserModel::$allowedFields first.

        return true;
    }

    public function logout(): void
    {
        session()->destroy();
    }

    public function activateAccount(string $userId): string
    {
        $model = new UserModel();
        $user = $model->find($userId);
        if (!$user) {
            return 'doesNotExist';
        }
        if (strcasecmp((string)($user->account_active ?? 'No'), 'Yes') === 0) {
            return 'alreadyActive';
        }

        $model->update($userId, ['account_active' => 'Yes']);
        return 'active';
    }

    public function beginPasswordReset(string $email): void
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return;
        }

        $row = db_connect()->query(
            "SELECT *
               FROM comp_users
              WHERE LOWER(email_address) = ?
              LIMIT 1",
            [$normalized]
        )->getRowArray();
        if (!$row) {
            return;
        }

        $resetModel = new PasswordResetModel();
        $existing = $resetModel
            ->where('user_id', (string)$row['user_id'])
            ->where('used_at', null)
            ->findAll();

        foreach ($existing as $t) {
            $resetModel->update($t['id'], ['expires_at' => Time::now()->toDateTimeString()]);
        }

        $token = $resetModel->createToken((string)$row['user_id'], 60);
        $link = site_url('auth/reset/' . $token);

        $emailSvc = service('email');
        $settings = service('settings');
        $fromEmail = trim((string)$settings->get('email', ''));
        if ($fromEmail !== '' && filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $emailSvc->setFrom($fromEmail, $settings->getTitle());
        }

        $emailSvc->setTo((string)$row['email_address'])
            ->setSubject('Spark Awards Password Reset')
            ->setMessage(
                "<p>To reset your Spark Awards password, click the link below:</p>" .
                "<p><a href=\"{$link}\">{$link}</a></p>" .
                "<p>If you did not request this, you can ignore this email.</p>"
            );

        $emailSvc->send(false);
    }

    public function checkEmailExists(string $email, ?string $excludeUserId = null): string
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return 'blank';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'invalid';
        }

        $sql = "SELECT user_id
                  FROM comp_users
                 WHERE LOWER(email_address) = ?";
        $params = [$email];
        if ($excludeUserId !== null && $excludeUserId !== '') {
            $sql .= " AND user_id <> ?";
            $params[] = $excludeUserId;
        }
        $sql .= " LIMIT 1";

        $row = db_connect()->query($sql, $params)->getRowArray();
        return $row ? 'yes' : 'no';
    }

    public function checkPassword(string $password): string
    {
        if ($password === '') {
            return 'blank';
        }
        if (mb_strlen($password) < 8) {
            return 'short';
        }
        if (mb_strlen($password) > 128) {
            return 'long';
        }
        if (preg_match('/[A-Z]/', $password) !== 1) {
            return 'missing_upper';
        }
        if (preg_match('/[a-z]/', $password) !== 1) {
            return 'missing_lower';
        }
        if (preg_match('/[0-9]/', $password) !== 1) {
            return 'missing_number';
        }
        if (preg_match('/[^A-Za-z0-9\s]/', $password) !== 1) {
            return 'missing_symbol';
        }
        return 'ok';
    }

    public function passwordErrorMessage(string $status): string
    {
        return match ($status) {
            'blank' => 'Password cannot be blank.',
            'short' => 'Password must be at least 8 characters.',
            'long' => 'Password must be 128 characters or fewer.',
            'missing_upper' => 'Password must include at least 1 uppercase letter.',
            'missing_lower' => 'Password must include at least 1 lowercase letter.',
            'missing_number' => 'Password must include at least 1 number.',
            'missing_symbol' => 'Password must include at least 1 symbol.',
            default => 'Password must be at least 8 characters and include uppercase, lowercase, a number, and a symbol.',
        };
    }

    public function checkConfirmPassword(string $confirm, string $password): string
    {
        $confirm = trim($confirm);
        if ($confirm === '') {
            return 'blank';
        }
        return hash_equals($confirm, $password) ? 'ok' : 'nomatch';
    }

    public function verifyCurrentPassword(string $userId, string $currentPassword): bool
    {
        $userId = trim($userId);
        if ($userId === '' || $currentPassword === '') {
            return false;
        }

        $row = db_connect()->query(
            "SELECT password, salt
               FROM comp_users
              WHERE user_id = ?
              LIMIT 1",
            [$userId]
        )->getRowArray();
        if (!$row) {
            return false;
        }

        $stored = (string)($row['password'] ?? '');
        $salt = rtrim((string)($row['salt'] ?? ''), " \t\n\r\0\x0B");

        $info = password_get_info($stored);
        if (($info['algo'] !== 0) && password_verify($currentPassword, $stored)) {
            return true;
        }

        if ($salt !== '' && strlen($stored) === 128 && ctype_xdigit($stored)) {
            $legacy = hash('sha512', $currentPassword . $salt);
            return hash_equals(strtolower($stored), strtolower($legacy));
        }

        return false;
    }

    public function setUserPassword(
        string $userId,
        string $newPassword,
        bool $enforceHistory = true,
        string $source = 'change'
    ): string {
        $userId = trim($userId);
        if ($userId === '' || $newPassword === '') {
            return 'invalid';
        }

        $row = db_connect()->query(
            "SELECT user_id, password, salt
               FROM comp_users
              WHERE user_id = ?
              LIMIT 1",
            [$userId]
        )->getRowArray();
        if (!$row) {
            return 'not_found';
        }

        if ($enforceHistory && $this->isPasswordReused($userId, $newPassword)) {
            return 'reused';
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $now = Time::now()->toDateTimeString();
        $db = db_connect();

        $db->transStart();
        $db->table('comp_users')
            ->where('user_id', $userId)
            ->update([
                'password' => $hash,
                'salt' => '',
                'password_changed_at' => $now,
                'last_updated' => $now,
            ]);

        $db->table('comp_user_password_history')->insert([
            'user_id' => $userId,
            'password_hash' => $hash,
            'changed_at' => $now,
            'source' => substr(trim($source), 0, 20) ?: 'change',
        ]);
        $this->prunePasswordHistory($db, $userId);
        $db->transComplete();

        return $db->transStatus() ? 'ok' : 'failed';
    }

    public function isPasswordReused(string $userId, string $candidate): bool
    {
        $userId = trim($userId);
        if ($userId === '' || $candidate === '') {
            return false;
        }

        $user = db_connect()->query(
            "SELECT password, salt
               FROM comp_users
              WHERE user_id = ?
              LIMIT 1",
            [$userId]
        )->getRowArray();
        if ($user) {
            $stored = (string)($user['password'] ?? '');
            $salt = rtrim((string)($user['salt'] ?? ''), " \t\n\r\0\x0B");
            $info = password_get_info($stored);
            if (($info['algo'] !== 0) && password_verify($candidate, $stored)) {
                return true;
            }
            if ($salt !== '' && strlen($stored) === 128 && ctype_xdigit($stored)) {
                $legacy = hash('sha512', $candidate . $salt);
                if (hash_equals(strtolower($stored), strtolower($legacy))) {
                    return true;
                }
            }
        }

        try {
            $history = (new PasswordHistoryModel())
                ->where('user_id', $userId)
                ->orderBy('changed_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->findAll($this->policy->passwordHistoryLimit);
        } catch (\Throwable $e) {
            log_message('error', 'Password history check failed for {userId}: {message}', [
                'userId' => $userId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }

        foreach ($history as $row) {
            $hash = (string)($row['password_hash'] ?? '');
            if ($hash !== '' && password_verify($candidate, $hash)) {
                return true;
            }
        }

        return false;
    }

    public function isPasswordExpired(string $passwordChangedAt): bool
    {
        $passwordChangedAt = trim($passwordChangedAt);
        if ($passwordChangedAt === '') {
            return true;
        }

        try {
            $changed = Time::parse($passwordChangedAt);
        } catch (\Throwable $e) {
            return true;
        }

        return $changed->addMonths($this->policy->passwordExpiryMonths)->isBefore(Time::now());
    }

    private function prunePasswordHistory(BaseConnection $db, string $userId): void
    {
        $limit = max(1, (int)$this->policy->passwordHistoryLimit);
        $sql = "
            DELETE h
              FROM comp_user_password_history h
              LEFT JOIN (
                    SELECT id
                      FROM (
                            SELECT id
                              FROM comp_user_password_history
                             WHERE user_id = ?
                             ORDER BY changed_at DESC, id DESC
                             LIMIT {$limit}
                           ) AS keep_rows
                ) AS keepers
                ON keepers.id = h.id
             WHERE h.user_id = ?
               AND keepers.id IS NULL
        ";
        $db->query($sql, [$userId, $userId]);
    }
}
