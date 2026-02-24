<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Entries\EntryQuestionModel;

class SubmissionQuestionsController extends BaseController
{
    public function index()
    {
        $rows = (new EntryQuestionModel())
            ->asArray()
            ->orderBy('entry_question_order', 'ASC')
            ->orderBy('entry_question_id', 'ASC')
            ->findAll();

        return view('admin/submission_questions/index', [
            'rows' => $rows,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function store()
    {
        $rules = [
            'entry_question' => 'required|max_length[200]',
            'entry_question_order' => 'required|integer|greater_than_equal_to[1]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please provide a valid question and order.');
        }

        $question = trim((string)$this->request->getPost('entry_question'));
        $order = (int)$this->request->getPost('entry_question_order');

        $ok = (new EntryQuestionModel())->insert([
            'entry_question' => $question,
            'entry_question_order' => $order,
        ], false);

        if (!$ok) {
            return redirect()->back()->withInput()->with('error', 'Question could not be added.');
        }

        return redirect()->to('/admin/submission-questions')->with('success', 'Question added.');
    }

    public function update(int $questionId)
    {
        $model = new EntryQuestionModel();
        $existing = $model->find($questionId);
        if (!$existing) {
            return redirect()->to('/admin/submission-questions')->with('error', 'Question not found.');
        }

        $rules = [
            'entry_question' => 'required|max_length[200]',
            'entry_question_order' => 'required|integer|greater_than_equal_to[1]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please provide a valid question and order.');
        }

        $ok = $model->update($questionId, [
            'entry_question' => trim((string)$this->request->getPost('entry_question')),
            'entry_question_order' => (int)$this->request->getPost('entry_question_order'),
        ]);

        if (!$ok) {
            return redirect()->back()->withInput()->with('error', 'Question could not be updated.');
        }

        return redirect()->to('/admin/submission-questions')->with('success', 'Question updated.');
    }

    public function delete(int $questionId)
    {
        if (!$this->canDelete()) {
            return redirect()->to('/admin/submission-questions')->with('error', 'Editors are not allowed to delete records.');
        }

        $model = new EntryQuestionModel();
        $existing = $model->find($questionId);
        if (!$existing) {
            return redirect()->to('/admin/submission-questions')->with('error', 'Question not found.');
        }

        $db = db_connect();
        $db->transStart();
        $db->table('comp_entry_answers')->where('entry_question_id', $questionId)->delete();
        $model->delete($questionId);
        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->to('/admin/submission-questions')->with('error', 'Question could not be deleted.');
        }

        return redirect()->to('/admin/submission-questions')->with('success', 'Question deleted. Related answers were also deleted.');
    }

    private function canDelete(): bool
    {
        return (string)session('role') !== 'editor';
    }
}
