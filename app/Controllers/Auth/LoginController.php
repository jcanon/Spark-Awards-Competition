<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\Accounts\UserModel;

class LoginController extends BaseController
{
    protected $helpers = ['form', 'url'];

    public function show()
    {
        if (session('uid')) {
            return redirect()->to('/home');
        }

        return view('auth/login', [
            'title' => 'Login',
            'user' => service('userctx')->current(), // keep context available
            'invalid' => (bool)$this->request->getGet('invalid'),
            'sent' => (bool)$this->request->getGet('sent'),
            'captchaErr' => $this->request->getGet('captchaError') === '1',
            'siteKey' => service('captcha')->siteKey(),
        ]);
    }

    public function authenticate()
    {
        $email = trim((string)($this->request->getPost('email') ?? $this->request->getPost('email_address')));
        $password = (string)$this->request->getPost('password');
        $ip = (string)$this->request->getIPAddress();

        if (!service('authThrottle')->allowLogin($ip, $email)) {
            log_message('warning', 'Login throttled for {email_hash} from {ip}', [
                'email_hash' => hash('sha256', strtolower(trim($email))),
                'ip' => $ip,
            ]);
            return redirect()->back()->withInput()->with(
                'error',
                'Too many login attempts. Please wait a few minutes and try again.'
            );
        }

        $rules = [
            'email' => 'required|valid_email|max_length[100]',
            'password' => 'required|max_length[128]',
        ];
        if (!$this->validateData(['email' => $email, 'password' => $password], $rules)) {
            log_message('warning', 'Login validation failed for {email_hash} from {ip}', [
                'email_hash' => hash('sha256', strtolower(trim($email))),
                'ip' => $ip,
            ]);
            return redirect()->back()->withInput()->with('error', 'Your username or password is incorrect. Please try again.');
        }

        if (!service('auth')->attempt($email, $password)) {
            log_message('warning', 'Login failed for {email_hash} from {ip}', [
                'email_hash' => hash('sha256', strtolower(trim($email))),
                'ip' => $ip,
            ]);
            return redirect()->back()->withInput()->with('error', 'Your username or password is incorrect. Please try again.');
        }

        if ((bool)session('password_reset_required')) {
            return redirect()->to('/auth/password-expired');
        }

        if (in_array((string)session('role'), ['admin', 'editor'], true)) {
            session()->set('2fa_pass', false);
            return redirect()->to('/auth/2fa');
        }

        if ((bool)session('is_judge')) {
            return redirect()->to('/judging');
        }

        return redirect()->to('/home');
    }

    public function logout()
    {
        service('auth')->logout();
        return redirect()->to('/');
    }

    public function stopImpersonation()
    {
        if (!(bool)session('impersonating')) {
            return redirect()->to('/home')->with('error', 'No active impersonation session.');
        }

        $restoreUid = (string)session('impersonator_uid');
        $restoreRole = (string)session('impersonator_role');
        $restoreName = (string)session('impersonator_name');

        if ($restoreUid === '' || !in_array($restoreRole, ['admin', 'editor', 'judge', 'user'], true)) {
            service('auth')->logout();
            return redirect()->to('/')->with('error', 'Could not restore impersonation session. Please log in again.');
        }

        $isAdmin = $restoreRole === 'admin';
        $isEditor = $restoreRole === 'editor';
        $isJudge = $restoreRole === 'judge';

        session()->regenerate(true);
        session()->set([
            'uid' => $restoreUid,
            'role' => $restoreRole,
            'is_admin' => $isAdmin,
            'is_editor' => $isEditor,
            'is_judge' => $isJudge,
            '2fa_pass' => true,
            'name' => $restoreName,
            'impersonating' => false,
            'impersonator_uid' => null,
            'impersonator_role' => null,
            'impersonator_name' => null,
        ]);

        return redirect()->to('/admin/users')->with('success', 'Returned to your account.');
    }

    public function register()
    {
        $token = (string)$this->request->getPost('g-recaptcha-response');
        if (!service('captcha')->verify($token, $this->request->getIPAddress())) {
            return redirect()->back()->withInput()->with('error', 'Invalid CAPTCHA');
        }

        $email = strtolower(trim((string)$this->request->getPost('email')));
        $pass = (string)$this->request->getPost('password');
        $confirm = (string)$this->request->getPost('confirm_password');
        $auth = service('auth');
        $ip = (string)$this->request->getIPAddress();

        if (!service('authThrottle')->allowRegister($ip, $email)) {
            log_message('warning', 'Registration throttled for {email_hash} from {ip}', [
                'email_hash' => hash('sha256', strtolower(trim($email))),
                'ip' => $ip,
            ]);
            return redirect()->back()->withInput()->with(
                'error',
                'Too many account creation attempts. Please wait a few minutes and try again.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Invalid email address. Please try again.');
        }
        $emailStatus = $auth->checkEmailExists($email);
        if ($emailStatus === 'yes') {
            return redirect()->back()->withInput()->with(
                'error',
                'We could not create an account with that email. Try signing in or use a different email address.'
            );
        }
        if ($emailStatus !== 'no') {
            return redirect()->back()->withInput()->with('error', 'Invalid email address. Please try again.');
        }

        $passwordStatus = $auth->checkPassword($pass);
        if ($passwordStatus !== 'ok') {
            return redirect()->back()->withInput()->with('error', $auth->passwordErrorMessage($passwordStatus));
        }
        if (!hash_equals($pass, $confirm)) {
            return redirect()->back()->withInput()->with('error', 'Passwords do not match. Please try again.');
        }

        try {
            service('profiles')->createNewUser($email, $pass);
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            $message = strtolower($e->getMessage());
            if (str_contains($message, 'duplicate') || str_contains($message, 'already exists')) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'We could not create an account with that email. Try signing in or use a different email address.'
                );
            }
            log_message('error', 'Registration failed for {email}: {message}', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Unable to create your account right now. Please try again.');
        } catch (\Throwable $e) {
            log_message('error', 'Registration failed for {email}: {message}', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Unable to create your account right now. Please try again.');
        }

        // Auto-login the user after account creation
        if (service('auth')->attempt($email, $pass)) {
            return redirect()->to('/submissions');
        }

        return redirect()->to(site_url('auth/login'));
    }

    public function activate(string $id)
    {
        $users = new UserModel();
        /** @var \App\Entities\Accounts\User|null $row */
        $row = $users->find($id);

        $status = 'doesNotExist';
        if ($row) {
            $isActive = (string)($row->account_active ?? 'No') === 'Yes';
            $status = $isActive ? 'alreadyActive' : 'active';
            if (!$isActive) {
                $users->update($id, ['account_active' => 'Yes']);
            }
        }

        // Minimal activation page data: title and status
        return view('auth/activate', [
            'title' => 'Account Activation',
            'status' => $status,
        ]);
    }
}
