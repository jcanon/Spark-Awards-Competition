<?php

namespace App\Entities\Judging;

use CodeIgniter\Entity\Entity;

class WinnerLevel extends Entity
{
    protected $casts = [
        'winner_level_id' => 'integer',
    ];
}