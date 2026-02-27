<?php

namespace App\Models\Judging;

use CodeIgniter\Model;
use App\Entities\Judging\EntryPhoto;

class EntryPhotoModel extends Model
{
    protected $table = 'comp_entry_photos';
    protected $primaryKey = 'entry_photo_id';
    protected $returnType = EntryPhoto::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'entry_id',
        'entry_photo',
        'entry_photo_caption',
        'entry_photo_res',
        'entry_photo_order',
        'entry_certificate',
    ];

    protected $validationRules = [
        'entry_id' => 'required|max_length[35]',
        'entry_photo' => 'required|max_length[200]',
        'entry_photo_caption' => 'permit_empty|max_length[200]',
        'entry_photo_res' => 'required|in_list[High,PDF,Low]',
        'entry_photo_order' => 'permit_empty|integer',
        'entry_certificate' => 'permit_empty|max_length[200]',
    ];
}
