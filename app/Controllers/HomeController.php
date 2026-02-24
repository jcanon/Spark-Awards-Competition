<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Accounts\UserModel;

class HomeController extends BaseController
{
    public function index()
    {
        if (!session('uid')) {
            return redirect()->to('/auth/login');
        }

        $user = (new UserModel())->find((string)session('uid'));

        if ($user && ($user->isAdmin() || $user->isEditor())) {
            if (!session('2fa_pass')) {
                return redirect()->to('/auth/2fa');
            }
            return redirect()->to('/admin');
        }

        if ($user && $user->isJudge()) {
            return redirect()->to('/judging');
        }

        if (!$user || !$user->isProfileCompleted()) {
            return redirect()->to('/profile');
        }

        return redirect()->to('/competitions');
    }
}
