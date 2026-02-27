<?php

namespace App\Models\Accounts;

use CodeIgniter\Model;
use App\Entities\Accounts\User;

class UserModel extends Model
{
    protected $table = 'comp_users';
    protected $primaryKey = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType = User::class;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'email_address',
        'password',
        'password_changed_at',
        'salt',
        'user_type_id',
        'is_admin',
        'is_editor',
        'is_judge',
        'salutation',
        'first_name',
        'last_name',
        'title',
        'phone',
        'mobile',
        'website',
        'company_name',
        'address1',
        'address2',
        'city',
        'state',
        'country',
        'zipcode',
        'how_did_you_find_us',
        'how_did_you_find_us_other',
        'email_newsletters',
        'account_created',
        'account_active',
        'profile_completed',
        'last_updated',
        'internal_notes',
    ];

    protected $validationRules = [
        'user_id' => 'required|max_length[35]',
        'email_address' => 'required|valid_email|max_length[100]',
        'password' => 'permit_empty|string',
        'password_changed_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'salt' => 'permit_empty|max_length[36]',
        'user_type_id' => 'permit_empty|integer',
        'is_admin' => 'required|in_list[No,Yes]',
        'is_editor' => 'required|in_list[No,Yes]',
        'is_judge' => 'required|in_list[No,Yes]',
        'salutation' => 'permit_empty|max_length[5]',
        'first_name' => 'permit_empty|max_length[100]',
        'last_name' => 'permit_empty|max_length[100]',
        'title' => 'permit_empty|max_length[100]',
        'phone' => 'permit_empty|max_length[25]',
        'mobile' => 'permit_empty|max_length[25]',
        'website' => 'permit_empty|max_length[100]',
        'company_name' => 'permit_empty|max_length[100]',
        'address1' => 'permit_empty|max_length[100]',
        'address2' => 'permit_empty|max_length[100]',
        'city' => 'permit_empty|max_length[100]',
        'state' => 'permit_empty|max_length[5]',
        'country' => 'permit_empty|max_length[5]',
        'zipcode' => 'permit_empty|max_length[25]',
        'how_did_you_find_us' => 'permit_empty|max_length[100]',
        'how_did_you_find_us_other' => 'permit_empty|max_length[100]',
        'email_newsletters' => 'permit_empty|string',
        'account_created' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'account_active' => 'required|in_list[No,Yes]',
        'profile_completed' => 'required|in_list[No,Yes]',
        'last_updated' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'internal_notes' => 'permit_empty|string',
    ];

    public function getAllowed(): array
    {
        return $this->allowedFields;
    }
}
