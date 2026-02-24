<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Geo\StateModel;

class StatesController extends BaseController
{
    private StateModel $model;

    public function __construct()
    {
        $this->model = new StateModel();
    }

    public function index()
    {
        return view('admin/states/index', [
            'rows' => $this->model->orderBy('state', 'ASC')->findAll(),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function create()
    {
        return view('admin/states/form', [
            'row' => null,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function store()
    {
        $code = strtoupper(trim((string)$this->request->getPost('scode')));
        $name = trim((string)$this->request->getPost('state'));

        if (!$this->isValidCode($code)) {
            return redirect()->back()->withInput()->with('error', 'State code must be exactly 2 letters.');
        }
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'State name is required.');
        }
        if ($this->model->find($code)) {
            return redirect()->back()->withInput()->with('error', 'State code already exists.');
        }

        if (!$this->model->insert([
            'scode' => $code,
            'state' => $name,
        ])) {
            return redirect()->back()->withInput()->with('error', $this->formatModelErrors());
        }

        return redirect()->to('/admin/system-tools/states')->with('success', 'State created.');
    }

    public function edit(string $code)
    {
        $code = strtoupper(trim($code));
        $row = $this->model->find($code);
        if (!$row) {
            return redirect()->to('/admin/system-tools/states')->with('error', 'State not found.');
        }

        return view('admin/states/form', [
            'row' => $row,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function update(string $code)
    {
        $code = strtoupper(trim($code));
        $row = $this->model->find($code);
        if (!$row) {
            return redirect()->to('/admin/system-tools/states')->with('error', 'State not found.');
        }

        $newCode = strtoupper(trim((string)$this->request->getPost('scode')));
        $name = trim((string)$this->request->getPost('state'));

        if (!$this->isValidCode($newCode)) {
            return redirect()->back()->withInput()->with('error', 'State code must be exactly 2 letters.');
        }
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'State name is required.');
        }
        if ($newCode !== $code && $this->model->find($newCode)) {
            return redirect()->back()->withInput()->with('error', 'State code already exists.');
        }

        $ok = $this->model->builder()
            ->where('scode', $code)
            ->update([
                'scode' => $newCode,
                'state' => $name,
            ]);
        if (!$ok) {
            return redirect()->back()->withInput()->with('error', 'State update failed.');
        }

        return redirect()->to('/admin/system-tools/states/edit/' . rawurlencode($newCode))->with('success', 'State updated.');
    }

    public function delete(string $code)
    {
        if (!$this->canDelete()) {
            return redirect()->to('/admin/system-tools/states')->with('error', 'Editors are not allowed to delete records.');
        }

        $code = strtoupper(trim($code));
        if (!$this->model->find($code)) {
            return redirect()->to('/admin/system-tools/states')->with('error', 'State not found.');
        }

        if (!$this->model->delete($code)) {
            return redirect()->to('/admin/system-tools/states')->with('error', 'State could not be deleted.');
        }

        return redirect()->to('/admin/system-tools/states')->with('success', 'State deleted.');
    }

    private function canDelete(): bool
    {
        return (string)session('role') !== 'editor';
    }

    private function isValidCode(string $code): bool
    {
        return preg_match('/^[A-Z]{2}$/', $code) === 1;
    }

    private function formatModelErrors(): string
    {
        $errors = $this->model->errors();
        if (empty($errors)) {
            return 'State data is invalid.';
        }

        return implode(' ', array_values($errors));
    }
}
