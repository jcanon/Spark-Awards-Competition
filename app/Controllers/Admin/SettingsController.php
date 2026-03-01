<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class SettingsController extends BaseController
{
    public function index()
    {
        return view('admin/settings/index', [
            'row' => service('adminSettings')->getSettings(),
        ]);
    }

    public function update()
    {
        $rules = [
            'url' => 'required|max_length[100]',
            'email' => 'required|valid_email|max_length[100]',
            'title' => 'required|max_length[200]',
            'email_server' => 'permit_empty|max_length[200]',
            'email_username' => 'permit_empty|max_length[200]',
            'email_password' => 'permit_empty|max_length[200]',
            'site_maintenance' => 'required|in_list[0,1]',
            'site_maintenance_message' => 'permit_empty|max_length[2000]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Invalid settings data.');
        }

        if (!service('adminSettings')->updateSettings($this->request->getPost())) {
            return redirect()->back()->withInput()->with('error', 'Could not save settings.');
        }

        if (method_exists(service('settings'), 'reload')) {
            service('settings')->reload();
        }

        return redirect()->to('/admin/system-tools/settings')->with('success', 'Settings updated.');
    }
}
