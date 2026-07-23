<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class CompetitionsController extends BaseController
{
    public function index()
    {
        $svc = service('competitions');
        $currentYear = (int)date('Y');
        $rawYear = strtoupper(trim((string)$this->request->getGet('compYear')));
        $selectedYear = $rawYear === '' ? (string)$currentYear : $rawYear;
        $filterYear = ($selectedYear === 'ALL') ? null : (int)$selectedYear;
        if ($filterYear !== null && $filterYear <= 0) {
            $filterYear = $currentYear;
            $selectedYear = (string)$currentYear;
        }

        $years = $svc->getCompetitionYears();
        if (!in_array($currentYear, $years, true)) {
            $years[] = $currentYear;
            rsort($years, SORT_NUMERIC);
        }

        return view('admin/competitions/index', [
            'rows' => $svc->getAllCompetitions($filterYear),
            'years' => $years,
            'selectedYear' => $selectedYear,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function create()
    {
        $svc = service('competitions');
        return view('admin/competitions/form', [
            'row' => null,
            'types' => $svc->getActiveCompetitionTypes(),
            'existingTypeIdsByYear' => $svc->getExistingCompetitionTypeIdsByYear(),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function store()
    {
        $rules = $this->competitionRules(false);
        if (!$this->validate($rules)) {
            $errors = array_values($this->validator->getErrors());
            $message = $errors !== [] ? implode(' ', $errors) : 'Invalid competition data.';
            return redirect()->back()->withInput()->with('error', $message);
        }

        $selectedTypeIds = $this->selectedTypeIds();
        if ($selectedTypeIds === []) {
            return redirect()->back()->withInput()->with('error', 'At least one competition category is required.');
        }

        $activeTypeIds = array_map(
            static fn($type): int => (int)($type->comp_type_id ?? 0),
            service('competitions')->getActiveCompetitionTypes()
        );
        $activeTypeIds = array_values(array_unique(array_filter($activeTypeIds, static fn(int $id): bool => $id > 0)));

        foreach ($selectedTypeIds as $typeId) {
            if (!in_array($typeId, $activeTypeIds, true)) {
                return redirect()->back()->withInput()->with('error', 'One or more selected competition categories are invalid.');
            }
        }

        $svc = service('competitions');
        $form = $this->request->getPost();
        $createdCount = 0;
        $duplicateCount = 0;
        $firstCompId = 0;

        foreach ($selectedTypeIds as $typeId) {
            $form['comp_type_id'] = $typeId;
            $compId = $svc->createCompetition($form);
            if ($compId > 0) {
                $createdCount++;
                if ($firstCompId === 0) {
                    $firstCompId = $compId;
                }
            } else {
                $duplicateCount++;
            }
        }

        if ($createdCount === 0) {
            return redirect()->back()->withInput()->with('error', 'Competition already exists or could not be created.');
        }

        $message = $createdCount === 1
            ? '1 competition created.'
            : $createdCount . ' competitions created.';
        if ($duplicateCount > 0) {
            $message .= ' ' . $duplicateCount . ' skipped because they already exist.';
        }

        if ($createdCount === 1 && $firstCompId > 0) {
            return redirect()->to('/admin/competitions/edit/' . $firstCompId)->with('success', $message);
        }

        return redirect()->to('/admin/competitions')->with('success', $message);
    }

    public function edit(int $compId)
    {
        $svc = service('competitions');
        $row = $svc->getCompetitionByID($compId);
        if (!$row) {
            return redirect()->to('/admin/competitions')->with('error', 'Competition not found.');
        }

        return view('admin/competitions/form', [
            'row' => $row,
            'types' => $svc->getActiveCompetitionTypes(),
            'existingTypeIdsByYear' => $svc->getExistingCompetitionTypeIdsByYear(),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function update(int $compId)
    {
        $rules = $this->competitionRules(true);
        if (!$this->validate($rules)) {
            $errors = array_values($this->validator->getErrors());
            $message = $errors !== [] ? implode(' ', $errors) : 'Invalid competition data.';
            return redirect()->back()->withInput()->with('error', $message);
        }

        if (!service('competitions')->updateCompetition($compId, $this->request->getPost())) {
            return redirect()->back()->withInput()->with('error', 'Competition update failed.');
        }

        return redirect()->to('/admin/competitions/edit/' . $compId)->with('success', 'Competition updated.');
    }

    public function delete(int $compId)
    {
        if (!$this->canDelete()) {
            return redirect()->to('/admin/competitions')->with('error', 'Editors are not allowed to delete records.');
        }

        if (!service('competitions')->deleteCompetition($compId)) {
            return redirect()->to('/admin/competitions')->with('error', 'Competition could not be deleted.');
        }

        return redirect()->to('/admin/competitions')->with('success', 'Competition deleted.');
    }

    public function types()
    {
        return view('admin/competitions/types', [
            'rows' => service('competitions')->getAllCompetitionTypes(),
        ]);
    }

    public function storeType()
    {
        $name = trim((string)$this->request->getPost('comp_type_name'));
        $isStudent = (string)$this->request->getPost('is_student_comp');

        if (!$this->validate([
            'comp_type_name' => 'required|max_length[100]',
            'is_student_comp' => 'required|in_list[0,1]',
        ])) {
            return redirect()->back()->withInput()->with('error', 'Competition category name and type are required.');
        }

        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Competition category name is required.');
        }

        if (service('competitions')->checkCompetitionType([
            'comp_type_name' => $name,
            'is_student_comp' => $isStudent,
        ])) {
            return redirect()->back()->withInput()->with('error', 'This competition category/type already exists.');
        }

        $newId = service('competitions')->createCompetitionType([
            'comp_type_name' => $name,
            'is_student_comp' => $isStudent,
        ]);
        if ($newId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Competition category could not be added.');
        }

        return redirect()->to('/admin/competition-types')->with('success', 'Competition category added.');
    }

    public function archiveType(int $compTypeId)
    {
        if ($compTypeId <= 0) {
            return redirect()->to('/admin/competition-types')->with('error', 'Invalid competition category.');
        }

        if (!service('competitions')->archiveCompetitionType($compTypeId)) {
            return redirect()->to('/admin/competition-types')->with('error', 'Competition category could not be archived.');
        }

        return redirect()->to('/admin/competition-types')->with('success', 'Competition category archived.');
    }

    public function editType(int $compTypeId)
    {
        $row = service('competitions')->getCompetitionTypeByID($compTypeId);
        if (!$row) {
            return redirect()->to('/admin/competition-types')->with('error', 'Competition category not found.');
        }

        return view('admin/competitions/type_form', [
            'row' => $row,
        ]);
    }

    public function updateType(int $compTypeId)
    {
        $svc = service('competitions');
        $name = trim((string)$this->request->getPost('comp_type_name'));
        $isStudent = (string)$this->request->getPost('is_student_comp');

        if (!$this->validate([
            'comp_type_name' => 'required|max_length[100]',
            'is_student_comp' => 'required|in_list[0,1]',
        ])) {
            return redirect()->back()->withInput()->with('error', 'Competition category name and type are required.');
        }

        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Competition category name is required.');
        }

        if ($svc->checkCompetitionType([
            'comp_type_name' => $name,
            'is_student_comp' => $isStudent,
            'exclude_comp_type_id' => $compTypeId,
        ])) {
            return redirect()->back()->withInput()->with('error', 'This competition category/type already exists.');
        }

        if (!$svc->updateCompetitionType($compTypeId, [
            'comp_type_name' => $name,
            'is_student_comp' => $isStudent,
        ])) {
            $err = trim($svc->getLastError());
            return redirect()->back()->withInput()->with('error', $err !== '' ? $err : 'Competition category could not be updated.');
        }

        return redirect()->to('/admin/competition-types')->with('success', 'Competition category updated.');
    }

    private function canDelete(): bool
    {
        return (string)session('role') !== 'editor';
    }

    private function competitionRules(bool $isEdit): array
    {
        $moneyRule = 'required|regex_match[/^\$?\d{1,9}(,\d{3})*(\.\d{1,2})?$/]';

        $rules = [
            'comp_year' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]',
            'comp_phase_1_open' => 'required',
            'comp_regular_reg_open' => 'required',
            'comp_late_reg_open' => 'required',
            'comp_phase_1_close' => 'required',
            'jury_phase_1_open' => 'required',
            'jury_phase_1_close' => 'required',
            'comp_phase_2_open' => 'required',
            'comp_phase_2_close' => 'required',
            'jury_phase_2_open' => 'required',
            'jury_phase_2_close' => 'required',
            'late_payment_open' => 'permit_empty',
            'late_payment_close' => 'permit_empty',
            'pro_early_reg_price' => $moneyRule,
            'pro_regular_reg_price' => $moneyRule,
            'pro_late_reg_price' => $moneyRule,
            'pro_finalist_price' => $moneyRule,
            'pro_winner_price' => $moneyRule,
            'pro_series_price' => $moneyRule,
            'student_early_reg_price' => $moneyRule,
            'student_regular_reg_price' => $moneyRule,
            'student_late_reg_price' => $moneyRule,
            'student_finalist_price' => $moneyRule,
            'student_winner_price' => $moneyRule,
            'student_series_price' => $moneyRule,
            'trophy_price' => $moneyRule,
            'additional_trophy_price' => $moneyRule,
        ];

        if ($isEdit) {
            $rules['comp_type_id'] = 'required|integer';
        }

        return $rules;
    }

    /**
     * @return list<int>
     */
    private function selectedTypeIds(): array
    {
        $ids = $this->request->getPost('comp_type_ids');
        if (!is_array($ids)) {
            return [];
        }

        $normalized = [];
        foreach ($ids as $id) {
            $intId = (int)$id;
            if ($intId > 0) {
                $normalized[] = $intId;
            }
        }

        return array_values(array_unique($normalized));
    }
}
