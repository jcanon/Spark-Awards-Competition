<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class UsersController extends BaseController
{
    public function index()
    {
        $opts = [
            'userType' => (string)($this->request->getGet('userType') ?? 'ALL'),
            'profileCompleted' => (string)($this->request->getGet('profileCompleted') ?? 'Yes'),
        ];

        return view('admin/users/index', [
            'opts' => $opts,
            'canDelete' => $this->canDelete(),
        ]);
    }

    public function data()
    {
        $draw = max(0, (int)$this->request->getGet('draw'));

        try {
            $opts = [
                'userType' => (string)($this->request->getGet('userType') ?? 'ALL'),
                'profileCompleted' => (string)($this->request->getGet('profileCompleted') ?? 'Yes'),
            ];

            $start = max(0, (int)$this->request->getGet('start'));
            $length = (int)$this->request->getGet('length');
            if ($length <= 0) {
                $length = 100;
            }

            $searchValue = '';
            $search = $this->request->getGet('search');
            if (is_array($search)) {
                $searchValue = trim((string)($search['value'] ?? ''));
            }

            $orderBy = 'last_name';
            $direction = 'ASC';

            $order = $this->request->getGet('order');
            if (is_array($order) && isset($order[0]) && is_array($order[0])) {
                $idx = (int)($order[0]['column'] ?? 0);
                $dir = strtoupper((string)($order[0]['dir'] ?? 'ASC'));
                $direction = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';
                $columns = [
                    0 => 'last_name',
                    1 => 'company_name',
                    2 => 'user_type_id',
                    3 => 'last_name',
                    4 => 'email_address',
                    5 => 'last_name',
                ];
                $orderBy = $columns[$idx] ?? 'last_name';
            }

            $result = service('profiles')->listUsersDataTable(
                $opts,
                $start,
                $length,
                $searchValue,
                $orderBy,
                $direction
            );

            return $this->response->setJSON([
                'draw' => $draw,
                'recordsTotal' => $result['recordsTotal'],
                'recordsFiltered' => $result['recordsFiltered'],
                'data' => $result['rows'],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Admin users data endpoint failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unable to load users. Please refresh the page.',
            ]);
        }
    }

    public function edit(string $userId)
    {
        $profiles = service('profiles');
        $user = $profiles->getUserDetails($userId);
        if (!$user) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }
        if ($this->isEditorRole() && $this->isElevatedUser($user)) {
            return redirect()->to('/admin/users')->with(
                'error',
                'Editors cannot edit Admin or Editor accounts.'
            );
        }

        $canImpersonate = (($user['is_admin'] ?? 'No') !== 'Yes') && (($user['is_editor'] ?? 'No') !== 'Yes');

        return view('admin/users/edit', [
            'row' => $user,
            'userSubmissions' => $profiles->getUserSubmissions((string)$user['user_id']),
            'judgeActivity' => (($user['is_judge'] ?? 'No') === 'Yes')
                ? $profiles->getUserJudgingActivity((string)$user['user_id'])
                : [],
            'userTypes' => $profiles->getPublicUserTypes(),
            'states' => $profiles->getStates(),
            'countries' => $profiles->getCountries(),
            'canDelete' => $this->canDelete(),
            'canImpersonate' => $canImpersonate,
            'canManageElevatedRoles' => $this->isAdminRole(),
        ]);
    }

    public function update(string $userId)
    {
        $target = service('profiles')->getUserDetails($userId);
        if (!$target) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }
        if ($this->isEditorRole() && $this->isElevatedUser($target)) {
            return redirect()->to('/admin/users')->with(
                'error',
                'Editors cannot update Admin or Editor accounts.'
            );
        }

        $email = trim((string)$this->request->getPost('email_address'));
        $password = (string)$this->request->getPost('password');
        $confirm = (string)$this->request->getPost('confirm_password');

        $auth = service('auth');
        $emailStatus = $auth->checkEmailExists($email, $userId);
        if ($emailStatus !== 'no') {
            return redirect()->back()->withInput()->with('error', 'Email is invalid or already in use.');
        }

        if ($password !== '') {
            $passwordStatus = $auth->checkPassword($password);
            if ($passwordStatus !== 'ok') {
                return redirect()->back()->withInput()->with('error', $auth->passwordErrorMessage($passwordStatus));
            }
            if ($auth->checkConfirmPassword($confirm, $password) !== 'ok') {
                return redirect()->back()->withInput()->with('error', 'Passwords do not match.');
            }
        }

        $form = $this->request->getPost();
        $form['is_admin'] = ($this->request->getPost('is_admin') === 'Yes') ? 'Yes' : 'No';
        $form['is_editor'] = ($this->request->getPost('is_editor') === 'Yes') ? 'Yes' : 'No';
        $form['is_judge'] = ($this->request->getPost('is_judge') === 'Yes') ? 'Yes' : 'No';
        $form['account_active'] = ($this->request->getPost('account_active') === 'No') ? 'No' : 'Yes';

        if ($this->isEditorRole() && ($form['is_admin'] === 'Yes' || $form['is_editor'] === 'Yes')) {
            return redirect()->back()->withInput()->with(
                'error',
                'Editors cannot assign Admin or Editor roles. Only Admins can grant elevated roles.'
            );
        }
        if ($this->isEditorRole()) {
            // Enforce policy server-side even if payload is tampered.
            $form['is_admin'] = 'No';
            $form['is_editor'] = 'No';
        }

        if ($form['is_admin'] === 'Yes' && $form['is_editor'] === 'Yes') {
            return redirect()->back()->withInput()->with('error', 'A user cannot be both Admin and Editor. Choose one role only.');
        }

        if (isset($form['email_newsletters']) && is_array($form['email_newsletters'])) {
            $form['email_newsletters'] = json_encode(array_values($form['email_newsletters']), JSON_UNESCAPED_UNICODE);
        }

        try {
            service('profiles')->updateUser($form, $userId, (string)session('role'));
        } catch (\Throwable $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'reuse')) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'You cannot reuse a previously used password for this user. Please choose a new password.'
                );
            }
            return redirect()->back()->withInput()->with('error', 'Unable to update user.');
        }

        return redirect()->to('/admin/users/edit/' . rawurlencode($userId))->with('success', 'User updated.');
    }

    public function impersonate(string $userId)
    {
        $target = service('profiles')->getUserDetails($userId);
        if (!$target) {
            return redirect()->to('/admin/users')->with('error', 'User not found.');
        }

        if (($target['is_admin'] ?? 'No') === 'Yes' || ($target['is_editor'] ?? 'No') === 'Yes') {
            return redirect()->back()->with('error', 'Impersonation is blocked for Admin and Editor accounts.');
        }

        if (($target['account_active'] ?? 'No') !== 'Yes') {
            return redirect()->back()->with('error', 'Cannot impersonate an inactive account.');
        }

        $displayName = trim((string)($target['first_name'] ?? '') . ' ' . (string)($target['last_name'] ?? ''));
        if ($displayName === '') {
            $displayName = (string)($target['company_name'] ?? $target['email_address'] ?? 'User');
        }

        $currentUid = (string)session('uid');
        $currentRole = (string)session('role');
        $currentName = (string)session('name');

        session()->regenerate(true);
        session()->set([
            'uid' => (string)$target['user_id'],
            'role' => (($target['is_judge'] ?? 'No') === 'Yes') ? 'judge' : 'user',
            'is_admin' => false,
            'is_editor' => false,
            'is_judge' => (($target['is_judge'] ?? 'No') === 'Yes'),
            '2fa_pass' => true,
            'name' => $displayName,
            'impersonating' => true,
            'impersonator_uid' => $currentUid,
            'impersonator_role' => $currentRole,
            'impersonator_name' => $currentName,
        ]);

        return redirect()->to('/home')->with('success', 'Now impersonating ' . $displayName . '.');
    }

    public function delete(string $userId)
    {
        if (!$this->canDelete()) {
            return redirect()->to('/admin/users')->with('error', 'Editors are not allowed to delete records.');
        }

        if (!service('profiles')->deleteUser($userId)) {
            return redirect()->to('/admin/users')->with('error', 'User could not be deleted.');
        }

        return redirect()->to('/admin/users')->with('success', 'User deleted.');
    }

    private function canDelete(): bool
    {
        return (string)session('role') !== 'editor';
    }

    private function isAdminRole(): bool
    {
        return (string)session('role') === 'admin';
    }

    private function isEditorRole(): bool
    {
        return (string)session('role') === 'editor';
    }

    private function isElevatedUser(array $user): bool
    {
        return (($user['is_admin'] ?? 'No') === 'Yes') || (($user['is_editor'] ?? 'No') === 'Yes');
    }
}
