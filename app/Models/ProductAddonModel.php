<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class ProductAddonModel extends Model {
    protected $table = 'product_addon';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [ 
        'product_id',
        'name',
        'price'
    ];
    
}