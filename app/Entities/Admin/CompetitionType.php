<?php

namespace App\Entities\Admin;

use CodeIgniter\Entity\Entity;

class CompetitionType extends Entity
{
    protected $casts = [
        'comp_type_id' => 'integer',
        'is_student_comp' => 'integer',
        'archived' => 'integer',
    ];

    public function isArchived(): bool
    {
        return (int)($this->attributes['archived'] ?? 0) === 1;
    }

    public function isStudentCompetition(): bool
    {
        return (int)($this->attributes['is_student_comp'] ?? 0) === 1;
    }
}