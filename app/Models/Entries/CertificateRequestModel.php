<?php

namespace App\Models\Entries;

use CodeIgniter\Model;

class CertificateRequestModel extends Model
{
    protected $table = 'comp_entry_certificate_requests';
    protected $primaryKey = 'certificate_request_id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'entry_id',
        'user_id',
        'certificate_quantity',
        'designer_names',
        'additional_persons',
        'printed_organization',
        'contact_person',
        'contact_phone',
        'contact_email',
        'shipping_method',
        'shipping_company',
        'shipping_address1',
        'shipping_address2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'lamination_requested',
        'special_instructions',
        'request_status',
        'admin_notes',
        'requested_at',
        'reviewed_at',
        'reviewed_by',
        'updated_at',
    ];
}
