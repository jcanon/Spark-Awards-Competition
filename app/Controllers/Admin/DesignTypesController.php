<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Admin\CompetitionTypeModel;
use App\Models\Admin\DesignTypeModel;

class DesignTypesController extends BaseController
{
    private DesignTypeModel $designTypes;
    private CompetitionTypeModel $competitionTypes;

    public function __construct()
    {
        $this->designTypes = new DesignTypeModel();
        $this->competitionTypes = new CompetitionTypeModel();
    }

    public function index()
    {
        $types = $this->competitionTypes
            ->where('archived', 0)
            ->orderBy('comp_type_name', 'ASC')
            ->findAll();

        $selectedCompTypeId = (int)($this->request->getGet('comp_type_id') ?? 0);
        if ($selectedCompTypeId <= 0 && !empty($types)) {
            $selectedCompTypeId = (int)$types[0]->comp_type_id;
        }

        $rows = [];
        if ($selectedCompTypeId > 0) {
            $rows = $this->designTypes
                ->where('comp_type_id', $selectedCompTypeId)
                ->orderBy('design_type_name', 'ASC')
                ->findAll();
        }

        return view('admin/design_types/index', [
            'types' => $types,
            'selectedCompTypeId' => $selectedCompTypeId,
            'rows' => $rows,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function store()
    {
        $compTypeId = (int)$this->request->getPost('comp_type_id');
        $name = trim((string)$this->request->getPost('design_type_name'));

        if (!$this->isValidCompType($compTypeId)) {
            return redirect()->back()->withInput()->with('error', 'Please select a valid competition category.');
        }
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Design type name is required.');
        }
        if ($this->existsDuplicate($compTypeId, $name)) {
            return redirect()->back()->withInput()->with('error', 'This design type already exists for the selected competition category.');
        }

        if (!$this->designTypes->insert([
            'comp_type_id' => $compTypeId,
            'design_type_name' => $name,
        ])) {
            return redirect()->back()->withInput()->with('error', $this->formatModelErrors());
        }

        return redirect()->to('/admin/design-types?comp_type_id=' . $compTypeId)->with('success', 'Design type added.');
    }

    public function edit(int $designTypeId)
    {
        $row = $this->designTypes->find($designTypeId);
        if (!$row) {
            return redirect()->to('/admin/design-types')->with('error', 'Design type not found.');
        }

        $types = $this->competitionTypes
            ->where('archived', 0)
            ->orderBy('comp_type_name', 'ASC')
            ->findAll();

        return view('admin/design_types/form', [
            'row' => $row,
            'types' => $types,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function update(int $designTypeId)
    {
        $row = $this->designTypes->find($designTypeId);
        if (!$row) {
            return redirect()->to('/admin/design-types')->with('error', 'Design type not found.');
        }

        $compTypeId = (int)$this->request->getPost('comp_type_id');
        $name = trim((string)$this->request->getPost('design_type_name'));

        if (!$this->isValidCompType($compTypeId)) {
            return redirect()->back()->withInput()->with('error', 'Please select a valid competition category.');
        }
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Design type name is required.');
        }
        if ($this->existsDuplicate($compTypeId, $name, $designTypeId)) {
            return redirect()->back()->withInput()->with('error', 'This design type already exists for the selected competition category.');
        }

        if (!$this->designTypes->update($designTypeId, [
            'comp_type_id' => $compTypeId,
            'design_type_name' => $name,
        ])) {
            return redirect()->back()->withInput()->with('error', $this->formatModelErrors());
        }

        return redirect()->to('/admin/design-types/edit/' . $designTypeId)->with('success', 'Design type updated.');
    }

    public function delete(int $designTypeId)
    {
        if (!$this->canDelete()) {
            return redirect()->to('/admin/design-types')->with('error', 'Editors are not allowed to delete records.');
        }

        $row = $this->designTypes->find($designTypeId);
        if (!$row) {
            return redirect()->to('/admin/design-types')->with('error', 'Design type not found.');
        }

        if (!$this->designTypes->delete($designTypeId)) {
            return redirect()->to('/admin/design-types?comp_type_id=' . (int)$row->comp_type_id)->with('error', 'Design type could not be deleted.');
        }

        return redirect()->to('/admin/design-types?comp_type_id=' . (int)$row->comp_type_id)->with('success', 'Design type deleted.');
    }

    private function canDelete(): bool
    {
        return (string)session('role') !== 'editor';
    }

    private function isValidCompType(int $compTypeId): bool
    {
        if ($compTypeId <= 0) {
            return false;
        }

        $row = $this->competitionTypes->find($compTypeId);
        if (!$row) {
            return false;
        }

        return (int)($row->archived ?? 0) === 0;
    }

    private function existsDuplicate(int $compTypeId, string $name, ?int $excludeId = null): bool
    {
        $q = $this->designTypes
            ->where('comp_type_id', $compTypeId)
            ->where('design_type_name', $name);

        if ($excludeId !== null && $excludeId > 0) {
            $q->where('design_type_id !=', $excludeId);
        }

        return (bool)$q->first();
    }

    private function formatModelErrors(): string
    {
        $errors = $this->designTypes->errors();
        if (empty($errors)) {
            return 'Design type data is invalid.';
        }

        return implode(' ', array_values($errors));
    }
}

