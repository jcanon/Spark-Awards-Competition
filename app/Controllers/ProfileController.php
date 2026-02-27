<?php

namespace App\Controllers;

use App\Models\Accounts\UserModel;

class ProfileController extends BaseController
{
    public function index()
    {
        $currentUserId = (string)session('uid');
        if ($currentUserId === '') {
            return redirect()->to('/auth/login');
        }

        /** @var \App\Entities\Accounts\User|null $user */
        $user = (new UserModel())->find($currentUserId);
        if (!$user) {
            return redirect()->back()->with('errors', ['general' => 'Account not found.']);
        }

        $dropdowns = [
            'userTypes' => service('profiles')->getPublicUserTypes(),
            'states' => service('profiles')->getStates(),
            'countries' => service('profiles')->getCountries(),
        ];

        return view('profile/index', [
            'user' => $user,
            'dropdowns' => $dropdowns,
            'canManageRecoveryCodes' => in_array((string)session('role'), ['admin', 'editor'], true),
            'hasRecoveryCodes' => service('recoveryCodes')->hasActiveCodes($currentUserId),
            'remainingRecoveryCodes' => service('recoveryCodes')->countRemainingCodes($currentUserId),
        ]);
    }

    public function update()
    {
        $currentUserId = (string)session('uid');
        if ($currentUserId === '') {
            return redirect()->back()->withInput()->with(
                'errors',
                ['general' => 'Invalid session. Please log in again.']
            );
        }

        $form = $this->request->getPost();
        $email = strtolower(trim((string)($form['email_address'] ?? '')));
        $password = trim((string)($form['password'] ?? ''));
        $confirm = trim((string)($form['confirm_password'] ?? ''));
        $currentPassword = (string)($form['current_password'] ?? '');
        $auth = service('auth');

        $emailStatus = $auth->checkEmailExists($email, $currentUserId);
        if ($emailStatus !== 'no') {
            $msg = match ($emailStatus) {
                'blank' => 'Email address is required.',
                'invalid' => 'Please enter a valid email address.',
                'yes' => 'That email address is already in use.',
                default => 'Email is invalid or already in use.',
            };
            return redirect()->back()->withInput()->with('errors', ['email_address' => $msg]);
        }
        $form['email_address'] = $email;

        if ($password !== '') {
            if (!$auth->verifyCurrentPassword($currentUserId, $currentPassword)) {
                return redirect()->back()->withInput()->with(
                    'errors',
                    ['general' => 'Current password is incorrect.']
                );
            }
            $passwordStatus = $auth->checkPassword($password);
            if ($passwordStatus !== 'ok') {
                return redirect()->back()->withInput()->with('errors', ['general' => $auth->passwordErrorMessage($passwordStatus)]);
            }
            if ($auth->checkConfirmPassword($confirm, $password) !== 'ok') {
                return redirect()->back()->withInput()->with('errors', ['general' => 'Password confirmation does not match.']);
            }
        }

        if (isset($form['email_newsletters']) && is_array($form['email_newsletters'])) {
            $form['email_newsletters'] = json_encode(array_values($form['email_newsletters']), JSON_UNESCAPED_UNICODE);
        }

        try {
            service('profiles')->updateUser($form, $currentUserId, (string)session('role'));
        } catch (\Throwable $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'reuse')) {
                return redirect()->back()->withInput()->with(
                    'errors',
                    ['general' => 'You cannot reuse a previously used password. Please choose a new password.']
                );
            }
            return redirect()->back()->withInput()->with('errors', ['general' => 'Could not update profile at this time.']);
        }

        return redirect()->to('/profile')->with('success', 'Profile updated');
    }

    public function regenerateRecoveryCodes()
    {
        $currentUserId = (string)session('uid');
        if ($currentUserId === '') {
            return redirect()->to('/auth/login');
        }

        if (!in_array((string)session('role'), ['admin', 'editor'], true)) {
            return redirect()->to('/profile')->with('errors', ['general' => 'Recovery codes are only available for Admin and Editor accounts.']);
        }

        $currentPassword = (string)$this->request->getPost('recovery_current_password');
        if (!service('auth')->verifyCurrentPassword($currentUserId, $currentPassword)) {
            return redirect()->to('/profile')->with('errors', ['general' => 'Current password is required to generate recovery codes.']);
        }

        $codes = service('recoveryCodes')->regenerateCodes($currentUserId, 10, 12);
        if ($codes === []) {
            return redirect()->to('/profile')->with('errors', ['general' => 'Unable to generate recovery codes right now.']);
        }

        return redirect()->to('/profile')
            ->with('success', 'Recovery codes generated. Save them now; they will only be shown once.')
            ->with('recovery_codes', $codes);
    }
}
