<?php

namespace App\Services;

use App\Models\Admin\CompetitionModel;
use App\Models\Admin\CompetitionTypeModel;

/**
 * Entity-first browsing for competitions.
 * Returns Competition entities (with joined type fields available) where applicable.
 */
class CompetitionBrowseService
{
    private function shouldShowAllCompetitionTypesForCurrentUser(): bool
    {
        return (bool)session('is_admin') || (bool)session('is_editor');
    }

    /**
     * Is the given user a student based on comp_user_type.user_type_pricing.
     * Kept as a small direct query for performance.
     */
    public function userIsStudent(string $userId): bool
    {
        $db = db_connect();
        $row = $db->query(
            "SELECT b.user_type_pricing
               FROM comp_users a
               INNER JOIN comp_user_type b ON a.user_type_id = b.user_type_id
              WHERE a.user_id = ?",
            [$userId]
        )->getRowArray();

        return $row ? strcasecmp((string)$row['user_type_pricing'], 'Student') === 0 : false;
    }

    private function currentUserId(): ?string
    {
        $uid = session('uid');
        return is_string($uid) && $uid !== '' ? $uid : null;
    }

    public function getOpenPhase1ForCurrentUser(): array
    {
        $uid = $this->currentUserId();
        if (!$uid) {
            return [];
        }
        $now = date('Y-m-d H:i:s');
        $builder = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_phase_1_open <=', $now)
            ->where('comp_phase_1_close >', $now);

        if (!$this->shouldShowAllCompetitionTypesForCurrentUser()) {
            $builder->where('b.is_student_comp', $this->userIsStudent($uid) ? 1 : 0);
        }

        return $builder
            ->orderBy('comp_phase_1_close ASC, b.comp_type_name ASC')
            ->findAll(); // array<Competition>
    }

    public function getOpenPhase2ForCurrentUser(): array
    {
        $uid = $this->currentUserId();
        if (!$uid) {
            return [];
        }
        $now = date('Y-m-d H:i:s');
        $builder = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_phase_2_open <=', $now)
            ->where('comp_phase_2_close >', $now);

        if (!$this->shouldShowAllCompetitionTypesForCurrentUser()) {
            $builder->where('b.is_student_comp', $this->userIsStudent($uid) ? 1 : 0);
        }

        return $builder
            ->orderBy('comp_phase_2_close ASC, b.comp_type_name ASC')
            ->findAll();
    }

    public function getUpcomingPhase1ForCurrentUser(): array
    {
        $uid = $this->currentUserId();
        if (!$uid) {
            return [];
        }
        $builder = (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_phase_1_open >', date('Y-m-d H:i:s'));

        if (!$this->shouldShowAllCompetitionTypesForCurrentUser()) {
            $builder->where('b.is_student_comp', $this->userIsStudent($uid) ? 1 : 0);
        }

        return $builder
            ->orderBy('comp_phase_1_open ASC, b.comp_type_name ASC')
            ->findAll();
    }

    public function getCompetitionById(int $compId)
    {
        return (new CompetitionModel())
            ->join('comp_type b', 'b.comp_type_id = comp_competitions.comp_type_id')
            ->where('comp_id', $compId)
            ->first(); // Competition
    }

    public function getCompetitionTypes(): array
    {
        return (new CompetitionTypeModel())
            ->orderBy('comp_type_name', 'ASC')
            ->findAll(); // array<CompetitionType>
    }

    public function getCompetitionTypesNotArchived(): array
    {
        return (new CompetitionTypeModel())
            ->where('archived', 0)
            ->orderBy('comp_type_name', 'ASC')
            ->findAll();
    }
}
