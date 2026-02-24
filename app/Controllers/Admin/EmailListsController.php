<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class EmailListsController extends BaseController
{
    private const LIST_OPTIONS = [
        'Student Design',
        'Experience Design',
        'Graphic Design',
        'Health & Medical Design',
        'Product Design',
        'Digital Design',
        'Spaces Design',
        'Transport & Mobility Design',
        'Cleantech Design',
        'Package Design',
        'Spark-E Design',
        'Wear Design',
    ];

    private const LEGACY_LIST_OPTIONS = [
        'Concept & Student',
        'Experience',
        'Communication',
        'Health',
        'Product',
        'Digital',
        'Spaces',
        'Transport',
    ];

    public function index()
    {
        $label = trim((string)($this->request->getGet('list') ?? ''));
        $emails = $label !== '' ? service('profiles')->getEmailListUsers($label) : [];

        return view('admin/email_lists/index', [
            'label' => $label,
            'emails' => $emails,
            'emailText' => implode(', ', $emails),
            'options' => array_merge(self::LIST_OPTIONS, self::LEGACY_LIST_OPTIONS),
        ]);
    }
}
