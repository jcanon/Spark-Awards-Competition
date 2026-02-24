<?php

namespace App\Models\Judging;

use CodeIgniter\Model;
use App\Entities\Judging\WinnerLevel;

class WinnerLevelModel extends Model
{
    protected $table = 'comp_winner_levels';
    protected $primaryKey = 'winner_level_id';
    protected $returnType = WinnerLevel::class;
    protected $useTimestamps = false;

    protected $allowedFields = ['winner_level_name'];

    protected $validationRules = [
        'winner_level_name' => 'required|max_length[25]',
    ];
}