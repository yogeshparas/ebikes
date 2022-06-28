<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class CardModel extends Model {
    protected $table = 'user_cards';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [ 
        'user_id',
        'name',
        'email',
        'type',
        'card_no',
        'expiry',
        'payment_token'
    ];
    
    protected $createdField = 'created_at';

    protected $beforeInsert = ['beforeInsert'];

    protected function beforeInsert(array $data): array {
        $data['data']['created_at'] = date('Y-m-d H:i:s');
        return $data;
    }

}