<?php

namespace App\Services;

use App\Models\Entries\EntryAnswersModel;
use App\Models\Entries\EntryQuestionModel;

class EntryAnswerService
{
    public function listQuestions(): array
    {
        return (new EntryQuestionModel())
            ->orderBy('entry_question_order', 'ASC')
            ->findAll(); // array<EntryQuestion>
    }

    /**
     * Returns map<int questionId, string answer>
     */
    public function answersForEntry(string $entryId): array
    {
        $rows = (new EntryAnswersModel())
            ->where('entry_id', $entryId)
            ->asArray()
            ->findAll();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['entry_question_id']] = (string)($r['entry_answer'] ?? '');
        }
        return $map;
    }

    /**
     * Upserts answers for an entry from questionId => answer map.
     * Fully replaces existing answers for this entry.
     */
    public function upsertAnswers(string $entryId, array $answers): void
    {
        $m = new EntryAnswersModel();

        $m->where('entry_id', $entryId)->delete();

        foreach ($answers as $qid => $answer) {
            if (!is_numeric($qid)) {
                continue;
            }
            $m->insert([
                'entry_id' => $entryId,
                'entry_question_id' => (int)$qid,
                'entry_answer' => (string)$answer,
            ], false);
        }
    }

    public function deleteAnswers(string $entryId): void
    {
        (new EntryAnswersModel())->where('entry_id', $entryId)->delete();
    }
}