<?php

namespace App\Entities\Entries;

use CodeIgniter\Entity\Entity;

class UserEntry extends Entity
{
    protected $dates = ['date_created', 'last_updated'];

    protected $casts = [
        'comp_id' => 'integer',
        'winner_level' => 'integer',
        'shortlist' => 'integer',
        'photo_id' => 'integer',
    ];

    public function getIsGalleryHidden(): bool
    {
        return ($this->attributes['gallery_hide'] ?? 'No') === 'Yes';
    }

    public function getIsSeries(): bool
    {
        return ($this->attributes['series'] ?? 'No') === 'Yes';
    }

    public function getIsReturnDesign(): bool
    {
        return ($this->attributes['return_design'] ?? 'No') === 'Yes';
    }

    public function getIsNonFinalist(): bool
    {
        return ($this->attributes['entry_non_finalist'] ?? 'No') === 'Yes';
    }
}
