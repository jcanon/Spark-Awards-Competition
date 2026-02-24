<?php

namespace App\Entities\Accounts;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $dates = ['account_created', 'last_updated'];

    protected $casts = [
        'user_type_id' => 'integer',
    ];

    public function isAdmin(): bool
    {
        return ($this->attributes['is_admin'] ?? 'No') === 'Yes';
    }

    public function isEditor(): bool
    {
        return ($this->attributes['is_editor'] ?? 'No') === 'Yes';
    }

    public function isJudge(): bool
    {
        return ($this->attributes['is_judge'] ?? 'No') === 'Yes';
    }

    public function isActive(): bool
    {
        return ($this->attributes['account_active'] ?? 'No') === 'Yes';
    }

    public function isProfileCompleted(): bool
    {
        return ($this->attributes['profile_completed'] ?? 'No') === 'Yes';
    }
}