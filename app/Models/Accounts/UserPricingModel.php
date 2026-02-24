<?php

namespace App\Models\Accounts;

use CodeIgniter\Model;

class UserPricingModel extends Model
{
    // We will look up pricing from comp_user_type to avoid entity usage.
    protected $returnType = 'array';

    /**
     * Return pricing information for a given user type id as an array.
     * Assumes pricing is stored on comp_user_type.user_type_pricing.
     */
    public function forType(int $userTypeId): ?array
    {
        $row = db_connect()->query(
            'SELECT user_type_id, user_type_name, user_type_pricing
               FROM comp_user_type
              WHERE user_type_id = ?
              LIMIT 1',
            [$userTypeId]
        )->getRowArray();

        return $row ?: null;
    }
}