<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class BookingModel extends Model {
    protected $table = 'bookings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [ 
        'user_id',
        'product_id',
        'pickup_location',
        'return_location',
        'pickup_lat',
        'pickup_lng',
        'return_lat',
        'return_lng',
        'payment_type',
        'price',
        'total_price',
        'pickup_time',
        'return_time',
        'addons',
        'status'
    ];
    
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    protected function beforeInsert(array $data): array {
        $data['data']['created_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    protected function beforeUpdate(array $data): array {
        $data['data']['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }
    
}