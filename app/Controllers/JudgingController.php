<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Judging\EntryPhotoModel;
use App\Services\JudgingService;
use App\Services\SubmissionsService;

class JudgingController extends BaseController
{
    private JudgingService $judging;

    public function __construct()
    {
        $this->judging = service('judging');
    }

    public function index()
    {
        return view('judging/index', [
            'openPhase1' => $this->judging->getOpenPhase1Judging(),
            'openPhase2' => $this->judging->getOpenPhase2Judging(),
            'upcomingPhase1' => $this->judging->getUpcomingPhase1Judging(),
            'upcomingPhase2' => $this->judging->getUpcomingPhase2Judging(),
            'closedPhase1' => $this->judging->getClosedPhase1Judging(),
            'closedPhase2' => $this->judging->getClosedPhase2Judging(),
        ]);
    }

    public function listByCompetition(int $compId, string $status = 'Entrant')
    {
        $status = $this->normalizeStatus($status);
        $phase = (string)($this->request->getGet('phase') ?? '1');
        $fullList = (bool)$this->request->getGet('all');
        $viewMode = (string)($this->request->getGet('view') ?? 'applications');
        if (!in_array($viewMode, ['applications', 'score'], true)) {
            $viewMode = 'applications';
        }

        if ($phase === 'AllSpark') {
            $comp = (new \App\Models\Admin\CompetitionModel())->asArray()->find($compId);
            if (!$comp) {
                return redirect()->to('/judging')->with('error', 'Competition not found.');
            }
            $entries = $this->judging->allSparkJudgingEntries((int)$comp['comp_year'], 'Winner');
            $status = 'Winner';
        } elseif ($fullList && $phase === '2') {
            $entries = [];
            foreach ($this->judging->getOpenPhase2Judging() as $comp) {
                $entries = array_merge($entries, $this->judging->judgingEntries((int)$comp['comp_id'], 'Finalist'));
            }
            if ($entries === []) {
                foreach ($this->judging->getUpcomingPhase2Judging() as $comp) {
                    $entries = array_merge($entries, $this->judging->judgingEntries((int)$comp['comp_id'], 'Finalist'));
                }
            }
            if ($entries === []) {
                foreach ($this->judging->getClosedPhase2Judging() as $comp) {
                    $entries = array_merge($entries, $this->judging->judgingEntries((int)$comp['comp_id'], 'Finalist'));
                }
            }
            $status = 'Finalist';
        } else {
            $entries = $this->judging->judgingEntries($compId, $status);

        }

        $entryIds = array_values(array_map(static fn (array $row): string => (string)($row['entry_id'] ?? ''), $entries));
        $scoreMap = $this->judging->judgingScoresByEntryIds($entryIds, $phase);
        $previewPhotos = $this->getPreviewPhotoIdsByEntry($entryIds);
        foreach ($entries as &$entryRow) {
            $entryId = (string)($entryRow['entry_id'] ?? '');
            $entryRow['_my_score'] = array_key_exists($entryId, $scoreMap) ? $scoreMap[$entryId] : null;
            $entryRow['_preview_photo_id'] = $previewPhotos[$entryId] ?? null;
        }
        unset($entryRow);

        if ($viewMode === 'score') {
            $rank = static function ($score): int {
                if ($score === 2 || $score === '2') {
                    return 0;
                }
                if ($score === 1 || $score === '1') {
                    return 1;
                }
                if ($score === 0 || $score === '0') {
                    return 2;
                }
                return 3;
            };

            usort($entries, static function (array $a, array $b) use ($rank): int {
                $aRank = $rank($a['_my_score'] ?? null);
                $bRank = $rank($b['_my_score'] ?? null);
                if ($aRank !== $bRank) {
                    return $aRank <=> $bRank;
                }

                return strcasecmp((string)($a['design_name'] ?? ''), (string)($b['design_name'] ?? ''));
            });
        }

        $competition = (new \App\Models\Admin\CompetitionModel())
            ->select('comp_competitions.comp_year, comp_competitions.jury_phase_1_open, comp_competitions.jury_phase_1_close, comp_competitions.jury_phase_2_open, comp_competitions.jury_phase_2_close, comp_type.comp_type_name, comp_type.comp_type_id')
            ->join('comp_type', 'comp_type.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_competitions.comp_id', $compId)
            ->asArray()
            ->first();
        $competitionLabel = null;
        $judgingContextLabel = null;
        if ($competition) {
            $competitionLabel = 'SPARK:' . strtoupper((string)($competition['comp_type_name'] ?? '')) . ' ' . (string)($competition['comp_year'] ?? '');
            if ($phase === '1') {
                $judgingContextLabel = $this->resolveJudgingContextLabel(
                    (string)($competition['jury_phase_1_open'] ?? ''),
                    (string)($competition['jury_phase_1_close'] ?? ''),
                    'Phase 1 Entrant Judging'
                );
            } elseif ($phase === '2') {
                $judgingContextLabel = $this->resolveJudgingContextLabel(
                    (string)($competition['jury_phase_2_open'] ?? ''),
                    (string)($competition['jury_phase_2_close'] ?? ''),
                    'Phase 2 Finalist Judging'
                );
            } elseif ($phase === 'AllSpark') {
                $judgingContextLabel = $this->resolveJudgingContextLabel(
                    (string)($competition['jury_phase_2_open'] ?? ''),
                    (string)($competition['jury_phase_2_close'] ?? ''),
                    'AllSpark Judging'
                );
            }
        }

        return view('judging/list', [
            'compId' => $compId,
            'status' => $status,
            'phase' => $phase,
            'entries' => $entries,
            'fullList' => $fullList,
            'viewMode' => $viewMode,
            'competitionLabel' => $competitionLabel,
            'judgingContextLabel' => $judgingContextLabel,
        ]);
    }

    public function entry(string $entryId, string $status = 'Entrant')
    {
        $status = $this->normalizeStatus($status);
        $phase = (string)($this->request->getGet('phase') ?? '1');
        $compId = (int)($this->request->getGet('comp') ?? 0);
        $fullList = (bool)$this->request->getGet('all');
        $viewMode = (string)($this->request->getGet('view') ?? 'applications');
        if (!in_array($viewMode, ['applications', 'score'], true)) {
            $viewMode = 'applications';
        }

        $entry = $this->judging->judgingEntryById($entryId, $status);
        if (!$entry && $phase === 'AllSpark') {
            $entry = $this->judging->judgingEntryById($entryId, 'Winner');
        }
        if (!$entry) {
            return redirect()->to('/judging')->with('error', 'Entry not found or unavailable.');
        }
        $contextCompId = $compId > 0 ? $compId : (int)($entry['comp_id'] ?? 0);
        $competition = (new \App\Models\Admin\CompetitionModel())
            ->select('comp_competitions.comp_year, comp_competitions.jury_phase_1_open, comp_competitions.jury_phase_1_close, comp_competitions.jury_phase_2_open, comp_competitions.jury_phase_2_close, comp_type.comp_type_name')
            ->join('comp_type', 'comp_type.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_competitions.comp_id', $contextCompId)
            ->asArray()
            ->first();
        $competitionLabel = null;
        $judgingContextLabel = null;
        if ($competition) {
            $competitionLabel = 'SPARK:' . strtoupper((string)($competition['comp_type_name'] ?? '')) . ' ' . (string)($competition['comp_year'] ?? '');
            if ($phase === '1') {
                $judgingContextLabel = $this->resolveJudgingContextLabel(
                    (string)($competition['jury_phase_1_open'] ?? ''),
                    (string)($competition['jury_phase_1_close'] ?? ''),
                    'Phase 1 Entrant Judging'
                );
            } elseif ($phase === '2') {
                $judgingContextLabel = $this->resolveJudgingContextLabel(
                    (string)($competition['jury_phase_2_open'] ?? ''),
                    (string)($competition['jury_phase_2_close'] ?? ''),
                    'Phase 2 Finalist Judging'
                );
            } elseif ($phase === 'AllSpark') {
                $judgingContextLabel = $this->resolveJudgingContextLabel(
                    (string)($competition['jury_phase_2_open'] ?? ''),
                    (string)($competition['jury_phase_2_close'] ?? ''),
                    'AllSpark Judging'
                );
            }
        }

        $entriesForNav = $this->entriesForNavigation($compId, $status, $phase, $fullList);
        if ($entriesForNav === [] && $phase === 'AllSpark') {
            $entryCompId = (int)($entry['comp_id'] ?? 0);
            if ($entryCompId > 0) {
                $entriesForNav = $this->entriesForNavigation($entryCompId, 'Winner', $phase, $fullList);
            }
        }

        $entryIds = array_values(array_map(static fn (array $r): string => (string)$r['entry_id'], $entriesForNav));
        $position = array_search($entryId, $entryIds, true);
        $prevEntry = ($position !== false && $position > 0) ? $entryIds[$position - 1] : null;
        $nextEntry = ($position !== false && $position < count($entryIds) - 1) ? $entryIds[$position + 1] : null;
        $scoreMap = $this->judging->judgingScoresByEntryIds($entryIds, $phase);
        $scoredCount = 0;
        foreach ($entryIds as $id) {
            if (array_key_exists($id, $scoreMap) && $scoreMap[$id] !== null) {
                $scoredCount++;
            }
        }

        $myScore = $this->judging->judgingScoreById($entryId, $phase);
        $votingOpen = $this->isVotingOpenForEntry($entryId, $phase);
        $skipTargetUrl = $this->resolveJudgingRedirectAfterCurrent(
            $entryId,
            $compId,
            $status,
            $phase,
            $fullList,
            $viewMode,
            false,
            true
        );

        $videoEmbed = (new SubmissionsService())->getVideoEmbedData((string)($entry['youtube_url'] ?? ''));

        return view('judging/entry', [
            'entry' => $entry,
            'phase' => $phase,
            'status' => $status,
            'compId' => $compId,
            'fullList' => $fullList,
            'viewMode' => $viewMode,
            'photos' => $this->judging->judgingPhotos($entryId, 'Low'),
            'myScore' => $myScore,
            'votingOpen' => $votingOpen,
            'prevEntry' => $prevEntry,
            'nextEntry' => $nextEntry,
            'skipTargetUrl' => $skipTargetUrl,
            'scoredCount' => $scoredCount,
            'entryCount' => count($entryIds),
            'entryPosition' => ($position === false) ? null : ($position + 1),
            'competitionLabel' => $competitionLabel,
            'judgingContextLabel' => $judgingContextLabel,
            'videoEmbed' => $videoEmbed,
        ]);
    }

    public function saveScore()
    {
        $rules = [
            'entry_id' => 'required|max_length[35]',
            'entry_phase' => 'required|in_list[1,2,AllSpark]',
            'entry_score' => 'required|integer|greater_than_equal_to[0]|less_than_equal_to[2]',
            'entry_comments' => 'permit_empty|max_length[5000]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please provide a valid score.');
        }

        $entryId = (string)$this->request->getPost('entry_id');
        $entryPhase = (string)$this->request->getPost('entry_phase');
        $entryScore = (int)$this->request->getPost('entry_score');
        $entryComments = trim((string)$this->request->getPost('entry_comments'));
        $compId = (int)$this->request->getPost('comp_id');
        $status = $this->normalizeStatus((string)$this->request->getPost('status'));
        $fullList = (string)$this->request->getPost('full_list') === '1';
        $viewMode = (string)$this->request->getPost('view_mode');
        if (!in_array($viewMode, ['applications', 'score'], true)) {
            $viewMode = 'applications';
        }

        if (!$this->isVotingOpenForEntry($entryId, $entryPhase)) {
            return redirect()->back()->withInput()->with('error', 'Voting is not currently open for this entry.');
        }

        try {
            $this->judging->setEntryScore($entryId, $entryPhase, $entryScore, $entryComments);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Unable to save score.');
        }

        $redirectUrl = $this->resolveJudgingRedirectAfterCurrent(
            $entryId,
            $compId,
            $status,
            $entryPhase,
            $fullList,
            $viewMode,
            true,
            true
        );

        return redirect()->to($redirectUrl)->with('success', 'Score saved.');
    }

    public function results()
    {
        $role = (string)(session('role') ?? '');
        if (!in_array($role, ['admin', 'editor'], true)) {
            return redirect()->to('/judging')->with('error', 'Not authorized.');
        }

        $year = (int)($this->request->getGet('year') ?? date('Y'));
        $type = (int)($this->request->getGet('type') ?? 0);
        $phase = (string)($this->request->getGet('phase') ?? '1');
        $userType = (string)($this->request->getGet('userType') ?? 'all');
        $status = (string)($this->request->getGet('status') ?? 'all');

        $rows = $this->judging->getScoreResults($year, $type, $phase, $userType, $status);

        return view('judging/results', [
            'year' => $year,
            'type' => $type,
            'phase' => $phase,
            'userType' => $userType,
            'status' => $status,
            'rows' => $rows,
        ]);
    }

    private function normalizeStatus(string $status): string
    {
        $status = ucfirst(strtolower(trim($status)));
        return in_array($status, ['Entrant', 'Finalist', 'Winner'], true) ? $status : 'Entrant';
    }

    private function getPreviewPhotoIdsByEntry(array $entryIds): array
    {
        $ids = array_values(array_filter(array_map(static fn ($id): string => trim((string)$id), $entryIds)));
        if ($ids === []) {
            return [];
        }

        $rows = (new EntryPhotoModel())
            ->asArray()
            ->select('entry_id, entry_photo_id, entry_photo_order')
            ->where('entry_photo_res', 'Low')
            ->whereIn('entry_id', $ids)
            ->orderBy('entry_photo_order', 'ASC')
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $entryId = (string)($row['entry_id'] ?? '');
            if ($entryId === '' || isset($map[$entryId])) {
                continue;
            }
            $map[$entryId] = (int)($row['entry_photo_id'] ?? 0);
        }

        return $map;
    }

    private function resolveJudgingRedirectAfterCurrent(
        string $currentEntryId,
        int $compId,
        string $status,
        string $phase,
        bool $fullList,
        string $viewMode,
        bool $treatCurrentAsScored,
        bool $nextMustBeUnscored
    ): string {
        $status = $this->normalizeStatus($status);
        if (!in_array($viewMode, ['applications', 'score'], true)) {
            $viewMode = 'applications';
        }

        if ($compId <= 0) {
            return site_url('judging');
        }

        $entriesForNav = $this->entriesForNavigation($compId, $status, $phase, $fullList);
        if ($entriesForNav === [] && $phase === 'AllSpark') {
            $entry = $this->judging->judgingEntryById($currentEntryId, 'Winner');
            $entryCompId = (int)($entry['comp_id'] ?? 0);
            if ($entryCompId > 0) {
                $entriesForNav = $this->entriesForNavigation($entryCompId, 'Winner', $phase, $fullList);
            }
        }

        $entryIds = array_values(array_map(static fn (array $r): string => (string)($r['entry_id'] ?? ''), $entriesForNav));
        if ($entryIds === []) {
            return $this->buildJudgingListUrl($compId, $status, $phase, $fullList, 'score');
        }

        $scoreMap = $this->judging->judgingScoresByEntryIds($entryIds, $phase);
        $isScored = static function (string $entryId) use ($scoreMap, $currentEntryId, $treatCurrentAsScored): bool {
            if ($treatCurrentAsScored && $entryId === $currentEntryId) {
                return true;
            }

            return array_key_exists($entryId, $scoreMap) && $scoreMap[$entryId] !== null;
        };

        $allScored = true;
        foreach ($entryIds as $entryId) {
            if (!$isScored($entryId)) {
                $allScored = false;
                break;
            }
        }

        if ($allScored) {
            return $this->buildJudgingListUrl($compId, $status, $phase, $fullList, 'score');
        }

        $position = array_search($currentEntryId, $entryIds, true);
        if ($position !== false && $position < (count($entryIds) - 1)) {
            for ($i = $position + 1, $len = count($entryIds); $i < $len; $i++) {
                $candidate = $entryIds[$i];
                if (!$nextMustBeUnscored || !$isScored($candidate)) {
                    return $this->buildJudgingEntryUrl($candidate, $status, $phase, $compId, $fullList, $viewMode);
                }
            }
        }

        foreach ($entryIds as $entryId) {
            if ($entryId === $currentEntryId) {
                continue;
            }
            if (!$isScored($entryId)) {
                return $this->buildJudgingEntryUrl($entryId, $status, $phase, $compId, $fullList, $viewMode);
            }
        }

        if (!$isScored($currentEntryId)) {
            return $this->buildJudgingEntryUrl($currentEntryId, $status, $phase, $compId, $fullList, $viewMode);
        }

        return $this->buildJudgingListUrl($compId, $status, $phase, $fullList, 'score');
    }

    private function buildJudgingEntryUrl(
        string $entryId,
        string $status,
        string $phase,
        int $compId,
        bool $fullList,
        string $viewMode
    ): string {
        $query = [
            'phase' => $phase,
            'comp' => $compId,
            'view' => $viewMode,
        ];
        if ($fullList) {
            $query['all'] = '1';
        }

        return site_url('judging/entry/' . rawurlencode($entryId) . '/status/' . rawurlencode($status)) . '?' . http_build_query($query);
    }

    private function buildJudgingListUrl(int $compId, string $status, string $phase, bool $fullList, string $viewMode): string
    {
        $query = [
            'phase' => $phase,
            'view' => $viewMode,
        ];
        if ($fullList) {
            $query['all'] = '1';
        }

        return site_url('judging/entries/competition/' . $compId . '/status/' . rawurlencode($status)) . '?' . http_build_query($query);
    }

    private function entriesForNavigation(int $compId, string $status, string $phase, bool $fullList): array
    {
        if ($phase === 'AllSpark') {
            $comp = (new \App\Models\Admin\CompetitionModel())->asArray()->find($compId);
            if (!$comp) {
                return [];
            }

            return $this->judging->allSparkJudgingEntries((int)$comp['comp_year'], 'Winner');
        }

        if ($fullList && $phase === '2') {
            $entries = [];
            foreach ($this->judging->getOpenPhase2Judging() as $comp) {
                $entries = array_merge($entries, $this->judging->judgingEntries((int)$comp['comp_id'], 'Finalist'));
            }
            if ($entries === []) {
                foreach ($this->judging->getUpcomingPhase2Judging() as $comp) {
                    $entries = array_merge($entries, $this->judging->judgingEntries((int)$comp['comp_id'], 'Finalist'));
                }
            }
            if ($entries === []) {
                foreach ($this->judging->getClosedPhase2Judging() as $comp) {
                    $entries = array_merge($entries, $this->judging->judgingEntries((int)$comp['comp_id'], 'Finalist'));
                }
            }

            return $entries;
        }

        return $this->judging->judgingEntries($compId, $status);
    }

    private function isVotingOpenForEntry(string $entryId, string $phase): bool
    {
        $row = db_connect()->table('comp_entries e')
            ->select('c.jury_phase_1_open, c.jury_phase_1_close, c.jury_phase_2_open, c.jury_phase_2_close')
            ->join('comp_competitions c', 'c.comp_id = e.comp_id')
            ->where('e.entry_id', $entryId)
            ->get()
            ->getRowArray();

        if (!$row) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        if ($phase === '1') {
            $open = (string)($row['jury_phase_1_open'] ?? '');
            $close = (string)($row['jury_phase_1_close'] ?? '');
            return $open !== '' && $close !== '' && $open <= $now && $close > $now;
        }

        $open = (string)($row['jury_phase_2_open'] ?? '');
        $close = (string)($row['jury_phase_2_close'] ?? '');
        return $open !== '' && $close !== '' && $open <= $now && $close > $now;
    }

    private function resolveJudgingContextLabel(string $openAt, string $closeAt, string $suffix): string
    {
        $now = date('Y-m-d H:i:s');

        if ($openAt !== '' && $closeAt !== '' && $openAt <= $now && $closeAt > $now) {
            return 'Open ' . $suffix;
        }

        if ($closeAt !== '' && $closeAt <= $now) {
            return 'Closed ' . $suffix;
        }

        return 'Upcoming ' . $suffix;
    }
}
