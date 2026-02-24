<?php

namespace App\Entities\Judging;

use CodeIgniter\Entity\Entity;

class Judging extends Entity
{
    protected $dates = ['date_udged'];

    protected $casts = [
        'entry_score' => 'integer',
    ];
}