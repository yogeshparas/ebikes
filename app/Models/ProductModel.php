<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class ProductModel extends Model {
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [ 
        'name',
        'number',
        'photo',
        'price_per_hour',
        'price_per_day',
        'pickup_location',
        'return_location',
        'pickup_lat',
        'pickup_lng',
        'return_lat',
        'return_lng',
        'availability',
        'start_time',
        'end_time',
        'description',
        'addon'
    ];
    
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    protected function beforeInsert (array $data): array {
        $data['data']['created_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    protected function beforeUpdate (array $data): array {
        $data['data']['updated_at'] = date('Y-m-d H:i:s');
        return $data;
    }

    public function get_products ( array $param ) {
        if ( ! isset ( $param['count'] ) || ! $param['count'] ) { 
		    $fields = "p.*";
		}  else {
			$fields = "count(p.id) as count";
		}
		
		$sql = "SELECT $fields FROM products as p";
		
		if ( ! isset ( $param['count'] ) || ! $param['count'] ) {
			if ( isset ( $param['limit'] ) && ( $param['limit'] > 0 ) ) {
				$sql .= " LIMIT {$param['limit']}";
			}
			
			if ( isset ( $param['offset'] ) && ( $param['offset'] > 0 ) ) {
				$sql .= " OFFSET {$param['offset']}";
			}
		}
		
        $query = $this->query( $sql );
        if ( ! isset ( $param['count'] ) || ! $param['count'] ) { 
			$results = $query->getResultArray();
		} else {
		    $results = $query->getRowArray();
		}
        return $results;
    }
    
}