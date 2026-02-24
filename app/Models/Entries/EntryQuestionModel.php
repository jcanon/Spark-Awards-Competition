<?php

namespace App\Models\Entries;

use CodeIgniter\Model;
use App\Entities\Entries\EntryQuestion;

class EntryQuestionModel extends Model
{
    protected $table = 'comp_entry_questions';
    protected $primaryKey = 'entry_question_id';
    protected $returnType = EntryQuestion::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'entry_question',
        'entry_question_order',
    ];

    protected $validationRules = [
        'entry_question' => 'required|max_length[200]',
        'entry_question_order' => 'permit_empty|integer',
    ];
}