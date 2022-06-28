<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

class UserModel extends Model {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [ 
        'name', 
        'email', 
        'password', 
        'phone_code', 
        'phone', 
        'photo', 
        'license_no',
        'license_photo', 
        'street', 
        'city', 
        'state', 
        'country', 
        'device_id', 
        'device_name', 
        'device_token', 
        'reset_pin',
        'facebook_id',
        'google_id',
        'apple_id'
    ];
    
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $beforeInsert = ['beforeInsert'];
    protected $beforeUpdate = ['beforeUpdate'];

    protected function beforeInsert(array $data): array {
        $data['data']['created_at'] = date('Y-m-d H:i:s');
        return $this->getUpdatedDataWithHashedPassword($data);
    }

    protected function beforeUpdate(array $data): array {
        $data['data']['updated_at'] = date('Y-m-d H:i:s');
        return $this->getUpdatedDataWithHashedPassword($data);
    }

    private function getUpdatedDataWithHashedPassword(array $data): array {
        if (isset($data['data']['password'])) {
            $plaintextPassword = $data['data']['password'];
            $data['data']['password'] = $this->hashPassword($plaintextPassword);
        }
        return $data;
    }

    private function hashPassword(string $plaintextPassword): string {
        return password_hash($plaintextPassword, PASSWORD_BCRYPT);
    }
                                      
    public function findUserByEmailAddress(string $emailAddress) {
        $user = $this
            ->asArray()
            ->where(['email' => $emailAddress])
            ->first();

        if ( ! $user ) 
            throw new Exception('User does not exist for specified email address');

        return $user;
    }
    
    public function findUserById ( string $userId ) {
        $user = $this->asArray()->where(['id' => $userId])->first();
        if ( ! $user ) 
            throw new Exception('User not found');
            
        return $user;
    }
    
    public function get_user ( $userId ) {
        $user = $this->asArray()->where(['id' => $userId])->first();
        return $user;
    }
    
    public function get_user_by_email ( $email ) {
    
    	if ( $email ) {
			$user = $this->asArray()->where(['email' => $email])->first();
            return $user;
		}
    }
    
    public function get_user_by_social ( $arg ) {
        $user = $this->asArray()->where( $arg )->first();
        return $user;
    }
    
}