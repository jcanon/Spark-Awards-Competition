<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class LocaleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $isAdmin = (bool) session('is_admin');
        $isEditor = (bool) session('is_editor');

        // Admin/editor private roles are always English.
        if ($isAdmin || $isEditor) {
            service('request')->setLocale('en');
            return;
        }

        $locale = (string)(session('app_locale') ?? '');
        if ($locale === '') {
            return;
        }

        $supported = config('App')->supportedLocales ?? ['en'];
        if (!in_array($locale, $supported, true)) {
            return;
        }

        service('request')->setLocale($locale);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}
