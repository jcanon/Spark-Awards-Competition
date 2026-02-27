<?php

namespace App\Models\Judging;

use CodeIgniter\Model;

class EntryModel extends Model
{
    protected $table = 'comp_entries';
    protected $primaryKey = 'entry_id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'entry_id',
        'comp_id',
        'user_id',
        'design_name',
        'entry_status',
        'winner_level',
        'designer_first_name',
        'designer_last_name',
    ];
}
