<?php

namespace App\Models\Payments;

use CodeIgniter\Model;
use App\Entities\Payments\RetailItem;

class RetailItemModel extends Model
{
    protected $table = 'comp_retail_items';
    protected $primaryKey = 'retail_item_id';
    protected $returnType = RetailItem::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'item_name',
        'item_description',
        'item_price',
        'active',
        'phase',
    ];

    protected $validationRules = [
        'item_name' => 'required|max_length[200]',
        'item_description' => 'permit_empty|string',
        'item_price' => 'required|integer',
        'active' => 'permit_empty|in_list[Y,N,1,0]',
        'phase' => 'permit_empty|integer',
    ];

    public function getActive(?int $phase = null): array
    {
        $builder = $this->asArray()
            ->whereIn('active', ['Y', '1', 1]);

        if ($phase !== null) {
            $builder->where('phase', $phase);
        }

        return $builder->orderBy('item_name', 'ASC')->findAll();
    }
}
