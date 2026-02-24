<?php

namespace App\Models\Geo;

use CodeIgniter\Model;
use App\Entities\Geo\State;

class StateModel extends Model
{
    protected $table = 'states';
    protected $primaryKey = 'scode';
    protected $useAutoIncrement = false;
    protected $returnType = State::class;
    protected $useTimestamps = false;

    protected $allowedFields = ['scode', 'state'];

    protected $validationRules = [
        'scode' => 'required|exact_length[2]',
        'state' => 'required|max_length[32]',
    ];
}