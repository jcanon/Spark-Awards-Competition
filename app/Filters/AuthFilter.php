<?php

namespace App\Filters;

use App\Models\Accounts\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $args = array_values(array_filter($arguments ?? [], static fn ($v) => is_string($v) && $v !== ''));
        $requiresJuror = in_array('juror', $args, true);
        $roles = array_values(array_filter(
            $args,
            static fn (string $v): bool => !in_array($v, ['2fa', 'juror'], true)
        ));
        $path = trim($request->getUri()->getPath(), '/');

        if (!session('uid')) {
            return redirect()->to('/auth/login')->with('error', 'Your session has expired. Please log in again.');
        }

        if ((bool)session('password_reset_required')) {
            $allowed = [
                'auth/password-expired',
                'auth/logout',
                'auth/impersonation/stop',
            ];

            $allowedHit = false;
            foreach ($allowed as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    $allowedHit = true;
                    break;
                }
            }

            if (!$allowedHit) {
                return redirect()->to('/auth/password-expired');
            }
        }

        $role = (string)(session('role') ?? '');
        if ($roles !== [] && !in_array($role, $roles, true)) {
            return redirect()->to('/')->with('error', 'Not authorized to access this page.');
        }

        if ($requiresJuror) {
            $uid = (string)session('uid');
            if ($uid === '') {
                return redirect()->to('/auth/login')->with('error', 'Your session has expired. Please log in again.');
            }

            $row = (new UserModel())->builder()
                ->select('is_judge')
                ->where('user_id', $uid)
                ->limit(1)
                ->get()
                ->getRowArray();

            $isJudge = (($row['is_judge'] ?? 'No') === 'Yes');
            session()->set('is_judge', $isJudge);

            if (!$isJudge) {
                return redirect()->to('/')->with('error', 'Not authorized to access this page.');
            }
        }

        $needs2faRole = in_array($role, ['admin', 'editor'], true);
        if ($needs2faRole && !session('2fa_pass')) {
            // Remember where they were headed, once
            $allowed = [
                'auth/2fa',
                'auth/2fa/callback',
                'auth/2fa/recovery',
                'auth/logout',
                'auth/password-expired',
            ];

            $allowedHit = false;
            foreach ($allowed as $prefix) {
                if (str_starts_with($path, $prefix)) {
                    $allowedHit = true;
                    break;
                }
            }

            if (!$allowedHit && !session('intended_url')) {
                $qs = $request->getUri()->getQuery();
                session()->set('intended_url', '/' . $path . ($qs !== '' ? '?' . $qs : ''));
            }

            if (!$allowedHit) {
                return redirect()->to('/auth/2fa');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
