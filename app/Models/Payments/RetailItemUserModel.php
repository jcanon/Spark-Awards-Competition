<?php

namespace App\Models\Payments;

use CodeIgniter\Model;
use App\Entities\Payments\RetailItemUser;

class RetailItemUserModel extends Model
{
    protected $table = 'comp_retail_item_user';
    protected $primaryKey = 'id';
    protected $returnType = RetailItemUser::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'retail_item_id',
        'entry_id',
        'quantity',
    ];

    protected $validationRules = [
        'retail_item_id' => 'required|integer',
        'entry_id' => 'required|max_length[35]',
        'quantity' => 'required|integer',
    ];

    public function findForEntryItem(string $entryId, int $addonId): array
    {
        $row = $this->asArray()
            ->where('entry_id', $entryId)
            ->where('retail_item_id', $addonId)
            ->first();

        return $row ?: [];
    }
}
