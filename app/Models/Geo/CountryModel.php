<?php

namespace App\Models\Geo;

use CodeIgniter\Model;
use App\Entities\Geo\Country;

class CountryModel extends Model
{
    protected $table = 'countries';
    protected $primaryKey = 'ccode';
    protected $useAutoIncrement = false;
    protected $returnType = Country::class;
    protected $useTimestamps = false;

    protected $allowedFields = ['ccode', 'country'];

    protected $validationRules = [
        'ccode' => 'required|exact_length[2]',
        'country' => 'required|max_length[200]',
    ];
}