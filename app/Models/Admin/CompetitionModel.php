<?php

namespace App\Models\Admin;

use CodeIgniter\Model;
use App\Entities\Admin\Competition;

class CompetitionModel extends Model
{
    protected $table = 'comp_competitions';
    protected $primaryKey = 'comp_id';
    protected $returnType = Competition::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'comp_type_id',
        'comp_year',
        'shortlist_enabled',
        'comp_phase_1_open',
        'comp_regular_reg_open',
        'comp_late_reg_open',
        'comp_phase_1_close',
        'jury_phase_1_open',
        'jury_phase_1_close',
        'comp_phase_2_open',
        'comp_phase_2_close',
        'jury_phase_2_open',
        'jury_phase_2_close',
        'pro_early_reg_price',
        'pro_regular_reg_price',
        'pro_late_reg_price',
        'pro_finalist_price',
        'pro_winner_price',
        'pro_series_price',
        'student_early_reg_price',
        'student_regular_reg_price',
        'student_late_reg_price',
        'student_finalist_price',
        'student_winner_price',
        'student_series_price',
        'trophy_price',
        'additional_trophy_price',
    ];

    protected $validationRules = [
        'comp_type_id' => 'required|integer',
        'comp_year' => 'required|integer|greater_than_equal_to[2000]|less_than_equal_to[2100]',
        'shortlist_enabled' => 'permit_empty|in_list[0,1]|integer',
        'comp_phase_1_open' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'comp_regular_reg_open' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'comp_late_reg_open' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'comp_phase_1_close' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'jury_phase_1_open' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'jury_phase_1_close' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'comp_phase_2_open' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'comp_phase_2_close' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'jury_phase_2_open' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'jury_phase_2_close' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'pro_early_reg_price' => 'permit_empty|decimal|max_length[10]',
        'pro_regular_reg_price' => 'permit_empty|decimal|max_length[10]',
        'pro_late_reg_price' => 'permit_empty|decimal|max_length[10]',
        'pro_finalist_price' => 'permit_empty|decimal|max_length[10]',
        'pro_winner_price' => 'permit_empty|decimal|max_length[10]',
        'pro_series_price' => 'permit_empty|decimal|max_length[10]',
        'student_early_reg_price' => 'permit_empty|decimal|max_length[10]',
        'student_regular_reg_price' => 'permit_empty|decimal|max_length[10]',
        'student_late_reg_price' => 'permit_empty|decimal|max_length[10]',
        'student_finalist_price' => 'permit_empty|decimal|max_length[10]',
        'student_winner_price' => 'permit_empty|decimal|max_length[10]',
        'student_series_price' => 'permit_empty|decimal|max_length[10]',
        'trophy_price' => 'permit_empty|decimal|max_length[10]',
        'additional_trophy_price' => 'permit_empty|decimal|max_length[10]',
    ];
}
