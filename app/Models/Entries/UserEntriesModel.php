<?php

namespace App\Models\Entries;

use CodeIgniter\Model;
use App\Entities\Entries\UserEntry;

class UserEntriesModel extends Model
{
    protected $table = 'comp_entries';
    protected $primaryKey = 'entry_id';
    protected $returnType = UserEntry::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'entry_id',
        'user_id',
        'comp_id',
        'entry_status',
        'winner_level',
        'phase_1_payment',
        'phase_2_payment',
        'phase_3_payment',
        'design_name',
        'design_type_list',
        'design_type',
        'company_name',
        'launch_year',
        'short_description',
        'full_description',
        'designer_salutation',
        'designer_first_name',
        'designer_last_name',
        'designer_title',
        'designer_phone',
        'designer_email_address',
        'additional_team_members',
        'series',
        'return_design',
        'return_number',
        'postal_carrier',
        'photo_id',
        'youtube_url',
        'date_created',
        'last_updated',
        'internal_notes',
        'gallery_hide',
        'referred_by',
        'design_stage',
        'judges_comments',
        'entry_non_finalist',
        'shortlist',
        'client_brandname',
    ];

    protected $validationRules = [
        'entry_id' => 'required|max_length[36]',
        'user_id' => 'required|max_length[36]',
        'comp_id' => 'required|integer',
        'entry_status' => 'required|in_list[Draft,Entrant,Finalist,Winner]',
        'winner_level' => 'permit_empty|integer',
        'phase_1_payment' => 'permit_empty|in_list[Paid,Unpaid]',
        'phase_2_payment' => 'permit_empty|in_list[Paid,Unpaid]',
        'phase_3_payment' => 'permit_empty|in_list[Paid,Unpaid]',
        'launch_year' => 'permit_empty|exact_length[4]|integer',
        'date_created' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'last_updated' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'gallery_hide' => 'permit_empty|in_list[No,Yes]',
        'series' => 'permit_empty|in_list[No,Yes]',
        'return_design' => 'permit_empty|in_list[No,Yes]',
        'entry_non_finalist' => 'permit_empty|in_list[No,Yes]',
        'shortlist' => 'permit_empty|integer',
    ];
}
