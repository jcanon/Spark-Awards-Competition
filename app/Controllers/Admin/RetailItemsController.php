<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Payments\RetailItemModel;

class RetailItemsController extends BaseController
{
    private RetailItemModel $model;

    public function __construct()
    {
        $this->model = new RetailItemModel();
    }

    public function index()
    {
        return view('admin/retail_items/index', [
            'rows' => $this->model->orderBy('phase', 'ASC')->orderBy('item_name', 'ASC')->findAll(),
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function create()
    {
        return view('admin/retail_items/form', [
            'row' => null,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function store()
    {
        $data = $this->sanitizePost();
        if (!$this->model->insert($data)) {
            return redirect()->back()->withInput()->with('error', $this->formatModelErrors());
        }

        return redirect()->to('/admin/retail-items')->with('success', 'Retail item created.');
    }

    public function edit(int $retailItemId)
    {
        $row = $this->model->find($retailItemId);
        if (!$row) {
            return redirect()->to('/admin/retail-items')->with('error', 'Retail item not found.');
        }

        return view('admin/retail_items/form', [
            'row' => $row,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function update(int $retailItemId)
    {
        $row = $this->model->find($retailItemId);
        if (!$row) {
            return redirect()->to('/admin/retail-items')->with('error', 'Retail item not found.');
        }

        $data = $this->sanitizePost();
        if (!$this->model->update($retailItemId, $data)) {
            return redirect()->back()->withInput()->with('error', $this->formatModelErrors());
        }

        return redirect()->to('/admin/retail-items/edit/' . $retailItemId)->with('success', 'Retail item updated.');
    }

    public function delete(int $retailItemId)
    {
        if (!$this->canDelete()) {
            return redirect()->to('/admin/retail-items')->with('error', 'Editors are not allowed to delete records.');
        }

        if (!$this->model->delete($retailItemId)) {
            return redirect()->to('/admin/retail-items')->with('error', 'Retail item could not be deleted.');
        }

        return redirect()->to('/admin/retail-items')->with('success', 'Retail item deleted.');
    }

    private function sanitizePost(): array
    {
        $phase = (int)($this->request->getPost('phase') ?? 0);
        if (!in_array($phase, [1, 2, 3], true)) {
            $phase = 1;
        }

        return [
            'item_name' => trim((string)$this->request->getPost('item_name')),
            'item_description' => trim((string)$this->request->getPost('item_description')),
            'item_price' => (int)$this->request->getPost('item_price'),
            'active' => ((string)$this->request->getPost('active') === 'N') ? 'N' : 'Y',
            'phase' => $phase,
        ];
    }

    private function formatModelErrors(): string
    {
        $errors = $this->model->errors();
        if (empty($errors)) {
            return 'Retail item data is invalid.';
        }

        return implode(' ', array_values($errors));
    }

    private function canDelete(): bool
    {
        return (string) session('role') !== 'editor';
    }
}

