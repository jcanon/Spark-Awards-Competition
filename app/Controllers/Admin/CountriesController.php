<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Geo\CountryModel;

class CountriesController extends BaseController
{
    private CountryModel $model;

    public function __construct()
    {
        $this->model = new CountryModel();
    }

    public function index()
    {
        return view('admin/countries/index', [
            'rows' => $this->model->orderBy('country', 'ASC')->findAll(),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function create()
    {
        return view('admin/countries/form', [
            'row' => null,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function store()
    {
        $code = strtoupper(trim((string)$this->request->getPost('ccode')));
        $name = trim((string)$this->request->getPost('country'));

        if (!$this->isValidCode($code)) {
            return redirect()->back()->withInput()->with('error', 'Country code must be exactly 2 letters.');
        }
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Country name is required.');
        }
        if ($this->model->find($code)) {
            return redirect()->back()->withInput()->with('error', 'Country code already exists.');
        }

        if (!$this->model->insert([
            'ccode' => $code,
            'country' => $name,
        ])) {
            return redirect()->back()->withInput()->with('error', $this->formatModelErrors());
        }

        return redirect()->to('/admin/system-tools/countries')->with('success', 'Country created.');
    }

    public function edit(string $code)
    {
        $code = strtoupper(trim($code));
        $row = $this->model->find($code);
        if (!$row) {
            return redirect()->to('/admin/system-tools/countries')->with('error', 'Country not found.');
        }

        return view('admin/countries/form', [
            'row' => $row,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function update(string $code)
    {
        $code = strtoupper(trim($code));
        $row = $this->model->find($code);
        if (!$row) {
            return redirect()->to('/admin/system-tools/countries')->with('error', 'Country not found.');
        }

        $newCode = strtoupper(trim((string)$this->request->getPost('ccode')));
        $name = trim((string)$this->request->getPost('country'));

        if (!$this->isValidCode($newCode)) {
            return redirect()->back()->withInput()->with('error', 'Country code must be exactly 2 letters.');
        }
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Country name is required.');
        }
        if ($newCode !== $code && $this->model->find($newCode)) {
            return redirect()->back()->withInput()->with('error', 'Country code already exists.');
        }

        $ok = $this->model->builder()
            ->where('ccode', $code)
            ->update([
                'ccode' => $newCode,
                'country' => $name,
            ]);
        if (!$ok) {
            return redirect()->back()->withInput()->with('error', 'Country update failed.');
        }

        return redirect()->to('/admin/system-tools/countries/edit/' . rawurlencode($newCode))->with('success', 'Country updated.');
    }

    public function delete(string $code)
    {
        if (!$this->canDelete()) {
            return redirect()->to('/admin/system-tools/countries')->with('error', 'Editors are not allowed to delete records.');
        }

        $code = strtoupper(trim($code));
        if (!$this->model->find($code)) {
            return redirect()->to('/admin/system-tools/countries')->with('error', 'Country not found.');
        }

        if (!$this->model->delete($code)) {
            return redirect()->to('/admin/system-tools/countries')->with('error', 'Country could not be deleted.');
        }

        return redirect()->to('/admin/system-tools/countries')->with('success', 'Country deleted.');
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
            return 'Country data is invalid.';
        }

        return implode(' ', array_values($errors));
    }
}
