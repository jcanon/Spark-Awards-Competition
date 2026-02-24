<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class NotificationsController extends BaseController
{
    public function index()
    {
        $svc = service('notifications');
        if (!$svc->isSchemaReady()) {
            return view('admin/notifications/index', [
                'rows' => [],
                'canDelete' => $this->canDelete(),
                'schemaReady' => false,
            ]);
        }

        return view('admin/notifications/index', [
            'rows' => $svc->listAdmin(),
            'canDelete' => $this->canDelete(),
            'schemaReady' => true,
        ]);
    }

    public function create()
    {
        return view('admin/notifications/form', [
            'row' => null,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function store()
    {
        if (!$this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('error', 'Invalid notification data.');
        }

        $form = $this->request->getPost();
        if (!$this->isDateRangeValid((string)($form['starts_at'] ?? ''), (string)($form['ends_at'] ?? ''))) {
            return redirect()->back()->withInput()->with('error', 'End date must be on or after start date.');
        }

        $form['is_active'] = $this->request->getPost('is_active') ? 1 : 0;
        $newId = service('notifications')->createAdmin($form, (string)session('uid'));
        if ($newId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Notification could not be created.');
        }

        return redirect()->to('/admin/system-tools/notifications/edit/' . $newId)->with('success', 'Notification created.');
    }

    public function edit(int $notificationId)
    {
        $row = service('notifications')->findAdmin($notificationId);
        if (!$row) {
            return redirect()->to('/admin/system-tools/notifications')->with('error', 'Notification not found.');
        }

        return view('admin/notifications/form', [
            'row' => $row,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function update(int $notificationId)
    {
        if (!$this->validate($this->rules())) {
            return redirect()->back()->withInput()->with('error', 'Invalid notification data.');
        }

        $form = $this->request->getPost();
        if (!$this->isDateRangeValid((string)($form['starts_at'] ?? ''), (string)($form['ends_at'] ?? ''))) {
            return redirect()->back()->withInput()->with('error', 'End date must be on or after start date.');
        }

        $form['is_active'] = $this->request->getPost('is_active') ? 1 : 0;
        $ok = service('notifications')->updateAdmin($notificationId, $form);
        if (!$ok) {
            return redirect()->back()->withInput()->with('error', 'Notification update failed.');
        }

        return redirect()->to('/admin/system-tools/notifications/edit/' . $notificationId)->with('success', 'Notification updated.');
    }

    public function delete(int $notificationId)
    {
        if (!$this->canDelete()) {
            return redirect()->to('/admin/system-tools/notifications')->with('error', 'Editors are not allowed to delete records.');
        }

        if (!service('notifications')->deleteAdmin($notificationId)) {
            return redirect()->to('/admin/system-tools/notifications')->with('error', 'Notification could not be deleted.');
        }

        return redirect()->to('/admin/system-tools/notifications')->with('success', 'Notification deleted.');
    }

    private function rules(): array
    {
        return [
            'title' => 'required|max_length[150]',
            'message' => 'required',
            'level' => 'required|in_list[info,success,warning,danger]',
            'audience' => 'required|in_list[all,admin,editor,judge,user]',
            'link_url' => 'permit_empty|max_length[255]',
            'link_text' => 'permit_empty|max_length[60]',
            'starts_at' => 'permit_empty',
            'ends_at' => 'permit_empty',
            'sort_order' => 'permit_empty|integer',
        ];
    }

    private function isDateRangeValid(string $startsAt, string $endsAt): bool
    {
        $startsAt = trim($startsAt);
        $endsAt = trim($endsAt);
        if ($startsAt === '' || $endsAt === '') {
            return true;
        }

        $startTs = strtotime($startsAt);
        $endTs = strtotime($endsAt);
        if ($startTs === false || $endTs === false) {
            return true;
        }

        return $endTs >= $startTs;
    }

    private function canDelete(): bool
    {
        return (string)session('role') !== 'editor';
    }
}
