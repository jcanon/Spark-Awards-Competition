<?php

namespace App\Entities\Judging;

use CodeIgniter\Entity\Entity;

class EntryPhoto extends Entity
{
    protected $casts = [
        'entry_photo_id' => 'integer',
        'entry_photo_order' => 'integer',
    ];

    public function getIsPdf(): bool
    {
        return ($this->attributes['entry_photo_res'] ?? '') === 'PDF';
    }
}