<?php

namespace App\Entities\Entries;

use CodeIgniter\Entity\Entity;

class EntryQuestion extends Entity
{
    protected $casts = [
        'entry_question_id' => 'integer',
        'entry_question_order' => 'integer',
    ];
}