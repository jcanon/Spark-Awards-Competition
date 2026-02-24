<?php

namespace App\Entities\Payments;

use CodeIgniter\Entity\Entity;

class EntryPayment extends Entity
{
    protected $dates = ['payment_date'];

    protected $casts = [
        'payment_id' => 'integer',
        'payment_phase' => 'integer',
        'spark_trophy_qty' => 'integer',
    ];

    public function getHasWinnerCircle(): bool
    {
        return ($this->attributes['winner_circle'] ?? 'No') === 'Yes';
    }

    public function getHasSparkTrophy(): bool
    {
        return ($this->attributes['spark_trophy'] ?? 'No') === 'Yes';
    }
}
