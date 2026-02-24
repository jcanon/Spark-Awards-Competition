<?php

namespace App\Models\Judging;

use CodeIgniter\Model;
use App\Entities\Judging\Judging;

class JudgingModel extends Model
{
    protected $table = 'comp_judging';
    protected $primaryKey = null;
    protected $useAutoIncrement = false;
    protected $returnType = Judging::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'entry_id',
        'entry_phase',
        'entry_score',
        'date_judged',
        'entry_comments',
    ];

    protected $validationRules = [
        'user_id' => 'required|max_length[35]',
        'entry_id' => 'required|max_length[35]',
        'entry_phase' => 'required|in_list[1,2,AllSpark]',
        'entry_score' => 'required|integer',
        'date_judged' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'entry_comments' => 'permit_empty|string',
    ];
}
