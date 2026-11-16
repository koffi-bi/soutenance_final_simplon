<?php

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class UserModel extends ShieldUserModel
{
    protected $allowedFields = [
        // Champs natifs de CodeIgniter Shield
        'username',
        'status',
        'status_message',
        'active',
        'last_active',

        // Champs spécifiques à notre application
        'company_id',
        'first_name',
        'last_name',
        'phone',
        'role',
        'account_status',
        'email_verified_at',
    ];
}
