<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;

class PasswordExpiryController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function show()
    {
        if (!session('uid')) {
            return redirect()->to('/auth/login');
        }

        if (!(bool)session('password_reset_required')) {
            return redirect()->to('/home');
        }

        return view('auth/password_expired', [
            'title' => 'Update Password',
        ]);
    }

    public function update()
    {
        $userId = (string)session('uid');
        if ($userId === '') {
            return redirect()->to('/auth/login');
        }
        $ip = (string)$this->request->getIPAddress();
        if (!service('authThrottle')->allowForcedPasswordUpdate($ip, $userId)) {
            return redirect()->back()->withInput()->with(
                'error',
                lang('Entrant.too_many_password_update_attempts')
            );
        }

        $plain = (string)$this->request->getPost('password');
        $confirm = (string)$this->request->getPost('password_confirm');
        $auth = service('auth');

        $pwStatus = $auth->checkPassword($plain);
        $confirmStatus = $auth->checkConfirmPassword($confirm, $plain);
        if ($pwStatus !== 'ok' || $confirmStatus !== 'ok') {
            $errorMsg = $auth->passwordErrorMessage($pwStatus);
            if ($confirmStatus === 'blank') {
                $errorMsg = lang('Entrant.password_confirmation_blank_with_period');
            } elseif ($confirmStatus === 'nomatch') {
                $errorMsg = lang('Entrant.passwords_do_not_match_with_period');
            }
            return redirect()->back()->withInput()->with('error', $errorMsg);
        }

        $setStatus = $auth->setUserPassword($userId, $plain, true, 'expiry_forced');
        if ($setStatus === 'reused') {
            return redirect()->back()->withInput()->with(
                'error',
                lang('Entrant.password_reuse_not_allowed')
            );
        }
        if ($setStatus !== 'ok') {
            return redirect()->back()->withInput()->with('error', lang('Entrant.unable_update_password'));
        }

        session()->set('password_reset_required', false);

        if (in_array((string)session('role'), ['admin', 'editor'], true) && !session('2fa_pass')) {
            return redirect()->to('/auth/2fa')->with('success', lang('Entrant.password_updated_continue_2fa'));
        }

        return redirect()->to('/home')->with('success', lang('Entrant.password_updated_successfully'));
    }
}
