<?php

namespace App\Entities\Accounts;

use CodeIgniter\Entity\Entity;

class UserType extends Entity
{
    protected $casts = [
        'user_type_id' => 'integer',
    ];

    public function isStudent(): bool
    {
        return ($this->attributes['user_type_pricing'] ?? '') === 'Student';
    }

    public function isPro(): bool
    {
        return ($this->attributes['user_type_pricing'] ?? '') === 'Pro';
    }
}