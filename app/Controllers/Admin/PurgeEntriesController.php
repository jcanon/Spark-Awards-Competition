<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Admin\SubmissionsAdminService;

class PurgeEntriesController extends BaseController
{
    private SubmissionsAdminService $adminSubs;

    public function __construct()
    {
        $this->adminSubs = new SubmissionsAdminService();
    }

    public function index()
    {
        $entrantCutoff = (int)date('Y') - 2;
        $finalistCutoff = (int)date('Y') - 2;

        $entrantRows = $this->findPurgeCandidates(['Draft', 'Entrant'], $entrantCutoff);
        $finalistRows = $this->findPurgeCandidates(['Finalist'], $finalistCutoff);

        return view('admin/purge/index', [
            'entrantCutoff' => $entrantCutoff,
            'finalistCutoff' => $finalistCutoff,
            'entrantCount' => count($entrantRows),
            'finalistCount' => count($finalistRows),
            'canDelete' => (string)session('role') !== 'editor',
        ]);
    }

    public function run()
    {
        if ((string)session('role') === 'editor') {
            return redirect()->to('/admin/purge-entries')->with('error', 'Editors are not allowed to delete records.');
        }

        $entrantCutoff = (int)date('Y') - 2;
        $finalistCutoff = (int)date('Y') - 2;

        $entrantRows = $this->findPurgeCandidates(['Draft', 'Entrant'], $entrantCutoff);
        $finalistRows = $this->findPurgeCandidates(['Finalist'], $finalistCutoff);

        $deleted = 0;
        foreach (array_merge($entrantRows, $finalistRows) as $row) {
            if ($this->adminSubs->delete((string)$row['entry_id'])) {
                $deleted++;
            }
        }

        return redirect()->to('/admin/purge-entries')->with('success', 'Purge complete. Deleted ' . $deleted . ' entries.');
    }

    private function findPurgeCandidates(array $statuses, int $cutoffYear): array
    {
        return db_connect()->table('comp_entries')
            ->select('comp_entries.entry_id')
            ->join('comp_competitions', 'comp_entries.comp_id = comp_competitions.comp_id')
            ->whereIn('comp_entries.entry_status', $statuses)
            ->where('comp_competitions.comp_year <=', $cutoffYear)
            ->get()
            ->getResultArray();
    }
}
