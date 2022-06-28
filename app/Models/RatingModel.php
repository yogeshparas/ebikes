<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class RatingModel extends Model {
    protected $table = 'ratings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [ 
        'user_id',
        'booking_id',
        'rating'
    ];
    
    protected $createdField = 'created_at';

    protected $beforeInsert = ['beforeInsert'];

    protected function beforeInsert(array $data): array {
        $data['data']['created_at'] = date('Y-m-d H:i:s');
        return $data;
    }

}