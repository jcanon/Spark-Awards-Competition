<?php

namespace App\Models\Entries;

use CodeIgniter\Model;

class EntryAnswersModel extends Model
{
    protected $table = 'comp_entry_answers';
    protected $primaryKey = null;
    protected $returnType = 'array';
    protected $useAutoIncrement = false;
    protected $useTimestamps = false;

    protected $allowedFields = ['entry_id', 'entry_question_id', 'entry_answer'];

    protected $validationRules = [
        'entry_id' => 'required|max_length[36]',
        'entry_question_id' => 'required|integer',
        'entry_answer' => 'permit_empty|string',
    ];
}