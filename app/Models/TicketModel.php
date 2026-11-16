<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketModel extends Model
{
    protected $table            = 'tickets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $protectFields = true;

    protected $allowedFields = [
        'reservation_id',
        'payment_id',
        'ticket_number',
        'status',
        'issued_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'reservation_id' => 'required|is_natural_no_zero',
        'payment_id'     => 'required|is_natural_no_zero',
        'ticket_number'  => 'required|max_length[100]',
        'status'         => 'required|in_list[valid,cancelled,used]',
        'issued_at'      => 'required',
    ];
}