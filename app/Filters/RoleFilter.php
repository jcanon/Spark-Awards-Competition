<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roles = array_values(array_filter($arguments ?? [], static fn ($v) => is_string($v) && $v !== ''));
        if (!$roles) {
            return null; // nothing to enforce
        }

        $role = (string)(session('role') ?? '');
        if (in_array($role, $roles, true)) {
            return null;
        }

        return redirect()->to('/')->with('error', 'Not authorized to access this area.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
