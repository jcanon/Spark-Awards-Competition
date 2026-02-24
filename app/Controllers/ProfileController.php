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
            return redirect()->back()->with('errors', ['general' => lang('Entrant.account_not_found')]);
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
                ['general' => lang('Entrant.invalid_session_login_again')]
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
                'blank' => lang('Entrant.email_required'),
                'invalid' => lang('Entrant.enter_valid_email'),
                'yes' => lang('Entrant.email_already_used'),
                default => lang('Entrant.email_invalid_or_in_use'),
            };
            return redirect()->back()->withInput()->with('errors', ['email_address' => $msg]);
        }
        $form['email_address'] = $email;

        if ($password !== '') {
            if (!$auth->verifyCurrentPassword($currentUserId, $currentPassword)) {
                return redirect()->back()->withInput()->with(
                    'errors',
                    ['general' => lang('Entrant.current_password_incorrect')]
                );
            }
            $passwordStatus = $auth->checkPassword($password);
            if ($passwordStatus !== 'ok') {
                return redirect()->back()->withInput()->with('errors', ['general' => $auth->passwordErrorMessage($passwordStatus)]);
            }
            if ($auth->checkConfirmPassword($confirm, $password) !== 'ok') {
                return redirect()->back()->withInput()->with('errors', ['general' => lang('Entrant.password_confirmation_not_match')]);
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
                    ['general' => lang('Entrant.password_reuse_not_allowed')]
                );
            }
            return redirect()->back()->withInput()->with('errors', ['general' => lang('Entrant.unable_update_profile')]);
        }

        return redirect()->to('/profile')->with('success', lang('Entrant.profile_updated'));
    }

    public function regenerateRecoveryCodes()
    {
        $currentUserId = (string)session('uid');
        if ($currentUserId === '') {
            return redirect()->to('/auth/login');
        }

        if (!in_array((string)session('role'), ['admin', 'editor'], true)) {
            return redirect()->to('/profile')->with('errors', ['general' => lang('Entrant.recovery_codes_only_admin_editor')]);
        }

        $currentPassword = (string)$this->request->getPost('recovery_current_password');
        if (!service('auth')->verifyCurrentPassword($currentUserId, $currentPassword)) {
            return redirect()->to('/profile')->with('errors', ['general' => lang('Entrant.current_password_required_recovery')]);
        }

        $codes = service('recoveryCodes')->regenerateCodes($currentUserId, 10, 12);
        if ($codes === []) {
            return redirect()->to('/profile')->with('errors', ['general' => lang('Entrant.unable_generate_recovery_codes')]);
        }

        return redirect()->to('/profile')
            ->with('success', lang('Entrant.recovery_codes_generated_once'))
            ->with('recovery_codes', $codes);
    }
}
