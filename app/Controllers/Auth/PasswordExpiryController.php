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
                'Too many password update attempts. Please wait a few minutes and try again.'
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
                $errorMsg = 'Password confirmation cannot be blank.';
            } elseif ($confirmStatus === 'nomatch') {
                $errorMsg = 'Passwords do not match.';
            }
            return redirect()->back()->withInput()->with('error', $errorMsg);
        }

        $setStatus = $auth->setUserPassword($userId, $plain, true, 'expiry_forced');
        if ($setStatus === 'reused') {
            return redirect()->back()->withInput()->with(
                'error',
                'You cannot reuse a previously used password. Please choose a new password.'
            );
        }
        if ($setStatus !== 'ok') {
            return redirect()->back()->withInput()->with('error', 'Unable to update password right now. Please try again.');
        }

        session()->set('password_reset_required', false);

        if (in_array((string)session('role'), ['admin', 'editor'], true) && !session('2fa_pass')) {
            return redirect()->to('/auth/2fa')->with('success', 'Password updated. Continue with two-factor verification.');
        }

        return redirect()->to('/home')->with('success', 'Password updated successfully.');
    }
}
