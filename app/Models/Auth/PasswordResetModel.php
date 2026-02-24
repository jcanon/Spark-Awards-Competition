<?php

namespace App\Models\Auth;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class PasswordResetModel extends Model
{
    protected $table = 'comp_password_resets';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'token', 'selector', 'token_hash', 'expires_at', 'used_at', 'created_at'];
    protected $returnType = 'array';
    public $useTimestamps = false;

    public function createToken(string $userId, int $minutes = 60): string
    {
        $selector = bin2hex(random_bytes(8));
        $verifier = bin2hex(random_bytes(32));
        $token = $selector . '.' . $verifier;
        $legacyCompatToken = bin2hex(random_bytes(32));
        $this->insert([
            'user_id' => $userId,
            // Keep token unique for schemas that still enforce UNIQUE(token).
            'token' => $legacyCompatToken,
            'selector' => $selector,
            'token_hash' => password_hash($verifier, PASSWORD_DEFAULT),
            'expires_at' => Time::now()->addMinutes($minutes)->toDateTimeString(),
            'created_at' => Time::now()->toDateTimeString(),
            'used_at' => null,
        ]);
        return $token;
    }
}
