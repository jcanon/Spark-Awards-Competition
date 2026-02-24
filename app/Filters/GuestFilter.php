<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GuestFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session('uid')) {
            return redirect()->to(site_url('home'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
