<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseController extends Controller
{
    protected $helpers = ['url', 'entry_status', 'certificate_request'];

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        $settingsSvc = service('settings');
        $siteTitle = (string)$settingsSvc->getTitle();

        $ctxSvc = service('userctx');
        $ctx = method_exists($ctxSvc, 'get') ? $ctxSvc->get() : $ctxSvc->current();
        $user = is_array($ctx) ? ($ctx['user'] ?? null) : null;

        $renderer = service('renderer');
        $renderer->setVar('siteTitle', $siteTitle);
        $renderer->setVar('settings', [
            'url' => site_url('/'),
            'title' => $siteTitle,
        ]);
        $renderer->setVar('app', [
            'url' => site_url('/'),
            'title' => $siteTitle,
        ]);
        $renderer->setVar('ctx', $ctx);
        $renderer->setVar('user', $user);
    }
}
