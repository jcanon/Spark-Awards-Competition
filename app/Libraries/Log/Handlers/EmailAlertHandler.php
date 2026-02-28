<?php

declare(strict_types=1);

namespace App\Libraries\Log\Handlers;

use CodeIgniter\Log\Handlers\BaseHandler;
use Config\Services;
use Throwable;

class EmailAlertHandler extends BaseHandler
{
    private bool $enabled;
    private bool $productionOnly;
    private string $toEmail;
    private string $appName;
    private int $throttleSeconds;
    private string $throttleFile;

    /**
     * @param array{
     *   handles?: list<string>,
     *   enabled?: bool,
     *   productionOnly?: bool,
     *   toEmail?: string,
     *   appName?: string,
     *   throttleSeconds?: int,
     *   throttleFile?: string
     * } $config
     */
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->enabled = (bool)($config['enabled'] ?? true);
        $this->productionOnly = (bool)($config['productionOnly'] ?? true);
        $this->toEmail = trim((string)($config['toEmail'] ?? ''));
        $this->appName = trim((string)($config['appName'] ?? 'Application'));
        $this->throttleSeconds = max(60, (int)($config['throttleSeconds'] ?? 600));
        $this->throttleFile = (string)($config['throttleFile'] ?? (rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'error-alert-throttle.json'));
    }

    /**
     * @param string $level
     * @param string $message
     */
    public function handle($level, $message): bool
    {
        if (!$this->enabled || $this->toEmail === '') {
            return true;
        }
        if ($this->productionOnly && ENVIRONMENT !== 'production') {
            return true;
        }
        if (!$this->canHandle((string)$level)) {
            return true;
        }

        $level = strtolower(trim((string)$level));
        $message = trim((string)$message);
        $context = $this->buildContext();
        $fingerprint = hash('sha256', $level . '|' . $this->normalizeMessage($message) . '|' . ($context['url'] ?? ''));

        if (!$this->shouldSend($fingerprint)) {
            return true;
        }

        $subject = sprintf('[%s][%s] %s', $this->appName, strtoupper((string)ENVIRONMENT), strtoupper($level));
        $body = $this->buildBody($level, $message, $context);

        try {
            $email = Services::email(null, false);
            $email->setTo($this->toEmail);
            $email->setSubject($subject);
            $email->setMessage($body);
            $email->send(false);
        } catch (Throwable $e) {
            error_log('EmailAlertHandler failed to send error email: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function buildContext(): array
    {
        $context = [
            'time' => date($this->dateFormat),
            'env' => (string)ENVIRONMENT,
            'method' => '',
            'url' => '',
            'ip' => '',
            'user_id' => '',
        ];

        try {
            $request = Services::request();
            if ($request !== null) {
                if (method_exists($request, 'getMethod')) {
                    $context['method'] = strtoupper((string)$request->getMethod());
                }
                if (method_exists($request, 'getUri')) {
                    $uri = $request->getUri();
                    if ($uri !== null) {
                        $context['url'] = (string)$uri;
                    }
                }
                if ($context['url'] === '' && method_exists($request, 'getUriString')) {
                    $context['url'] = (string)$request->getUriString();
                }
                if (method_exists($request, 'getIPAddress')) {
                    $context['ip'] = (string)$request->getIPAddress();
                }
            }
        } catch (Throwable $e) {
            // Best effort only.
        }

        try {
            $session = Services::session();
            if ($session !== null) {
                $context['user_id'] = (string)($session->get('user_id') ?? '');
            }
        } catch (Throwable $e) {
            // Best effort only.
        }

        return $context;
    }

    private function normalizeMessage(string $message): string
    {
        $message = preg_replace('/\s+/', ' ', $message) ?? $message;
        $message = trim($message);

        return mb_substr($message, 0, 1000);
    }

    /**
     * @param array<string, string> $context
     */
    private function buildBody(string $level, string $message, array $context): string
    {
        return implode("\n", [
            'System Error Alert',
            'Application: ' . $this->appName,
            'Environment: ' . ($context['env'] ?? (string)ENVIRONMENT),
            'Level: ' . strtoupper($level),
            'Time: ' . ($context['time'] ?? date($this->dateFormat)),
            'Method: ' . (($context['method'] ?? '') !== '' ? $context['method'] : 'N/A'),
            'URL: ' . (($context['url'] ?? '') !== '' ? $context['url'] : 'N/A'),
            'IP: ' . (($context['ip'] ?? '') !== '' ? $context['ip'] : 'N/A'),
            'User ID: ' . (($context['user_id'] ?? '') !== '' ? $context['user_id'] : 'N/A'),
            '',
            'Message:',
            $message,
        ]);
    }

    private function shouldSend(string $fingerprint): bool
    {
        $dir = dirname($this->throttleFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $now = time();
        $records = [];

        if (is_file($this->throttleFile)) {
            $raw = (string)@file_get_contents($this->throttleFile);
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $ts) {
                        if (is_string($key) && is_numeric($ts)) {
                            $records[$key] = (int)$ts;
                        }
                    }
                }
            }
        }

        $lastSent = (int)($records[$fingerprint] ?? 0);
        if ($lastSent > 0 && ($now - $lastSent) < $this->throttleSeconds) {
            return false;
        }

        $records[$fingerprint] = $now;
        $maxAge = max(3600, $this->throttleSeconds * 24);
        foreach ($records as $key => $ts) {
            if (($now - (int)$ts) > $maxAge) {
                unset($records[$key]);
            }
        }

        @file_put_contents($this->throttleFile, json_encode($records, JSON_PRETTY_PRINT), LOCK_EX);

        return true;
    }
}

