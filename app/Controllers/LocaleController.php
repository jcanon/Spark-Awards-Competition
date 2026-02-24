<?php

namespace App\Controllers;

class LocaleController extends BaseController
{
    public function switch(string $locale)
    {
        $locale = trim($locale);
        $supported = config('App')->supportedLocales ?? ['en'];
        if (!in_array($locale, $supported, true)) {
            $locale = 'en';
        }

        session()->set('app_locale', $locale);

        $back = $this->request->getServer('HTTP_REFERER');
        if (!is_string($back) || trim($back) === '') {
            return redirect()->to('/home');
        }

        return redirect()->to($back);
    }
}
