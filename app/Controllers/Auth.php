<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use ReflectionException;
use App\Libraries\Functions;

class Auth extends BaseController {
     
    public function __construct () {
        helper( 'jwt' );
        $this->UserModel = new UserModel();
        $this->functions = new Functions();
        
    }
    
    /**
     * Register a new user
     * @return Response
     * @throws ReflectionException
     */
    public function register () { 
        $response = array ();
        $rules = [
            'email'     => 'required|min_length[6]|max_length[50]|valid_email|is_unique[users.email]',
            'password'  => 'required|min_length[8]|max_length[255]'
        ];

        $input = $this->getRequestInput ( $this->request );
       
        if ( ! $this->validateRequest ( $input, $rules ) ) {
            $response				= array (
				'success'			=> 0,
				'msg'				=> $this->getValidationError ( ),
				'http_status'	    => ResponseInterface::HTTP_BAD_REQUEST
			);
        } else {
            
            try {
                
                $this->UserModel->save ( $input );
                
                $user = $this->UserModel->findUserByEmailAddress ( $input['email'] );
                
                $response = array (
                    'success'       => 1,
                    'msg'           => 'Your account has to be confirmed by an administrator before you can login.',
                    'user'          => $user,
                    'access_token'  => getSignedJWTForUser ( $user['id'] ),
                    'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
                );
            } catch ( Exception $e ) {
                $response				= array (
    				'success'			=> 0,
    				'msg'				=>  $e->getMessage(),
    				'http_status'	    => ResponseInterface::HTTP_BAD_REQUEST
    			);
            }
        }
        
        return $this->output ( $response);

    }

    /**
     * Authenticate Existing User
     * @return Response
     */
    public function login () {
        $response = array ();
        $rules = [
            'email'     => 'required|min_length[6]|max_length[50]|valid_email',
            'password'  => 'required|min_length[8]|max_length[255]|validateUser[email, password]'
        ];

        $errors = [
            'password' => [
                'validateUser' => 'Invalid login credentials provided'
            ]
        ];

        $input = $this->getRequestInput( $this->request );

        if ( ! $this->validateRequest ( $input, $rules, $errors ) ) {
            $response		= array (
				'success'	=> 0,
				'msg'		=> $this->getValidationError ( ),
				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
			);
        } else {
        
            $user = $this->UserModel->findUserByEmailAddress ( $input['email'] );
            
            if ( $user['status'] == 0 ) {
                $response		    = array (
    				'success'	    => 0,
    				'msg'		    => 'You account is not verified by administrator.',
    				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
    			);
            } else if ( $user['status'] > 1 ) {
                $response		    = array (
    				'success'	    => 0,
    				'msg'		    => 'You account is blocked by administrator.',
    				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
    			);
            } else if ( $user['status'] == 1 ) {
                $response = array (
                    'success'       => 1,
                    'msg'           => 'User authenticated successfully',
                    'user'          => $user,
                    'access_token'  => getSignedJWTForUser ( $user['id'] ),
                    'http_status'	=> ResponseInterface::HTTP_OK
                );
            }
        }
      
       return $this->output ( $response );
       
    }
    
	public function forgot_password () {

		$response = array();

	    $rules = [
            'email'     => 'required|min_length[6]|max_length[50]|valid_email'
        ];
		
		$input = $this->getRequestInput( $this->request );

        if ( ! $this->validateRequest ( $input, $rules ) ) {
            $response		    = array (
				'success'	    => 0,
				'msg'		    => $this->getValidationError ( ),
				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
			);
        } else { 
			$email = $input['email'];
			$user = $this->UserModel->findUserByEmailAddress ( $email );
			if ( ! empty ( $user )  ) {
			    
			    $rand = rand( 1000, 9999 );
			    
				$user_id = $user['id'];
				
				$update_user = array ( ); 
				$update_user['reset_pin'] = $rand;
				$this->UserModel->update ( $user_id, $update_user );
				
				$search_arr = array(
					'{name}'    => $user['name'],
					'{pin}'     => $rand
				);
				$temp_id = 1;
				$this->functions->send_email( $email, $temp_id, $search_arr );
			    
			    $response			= array (
    				'success'		=> 1,
    				'msg'           => "Change password pin is sent to your email. If you do not receive the email please check your spam.",
    				'http_status'	=> ResponseInterface::HTTP_OK
    			);

			} else {
				$response			= array (
    				'success'		=> 0,
    				'msg'			=> "Email not found.",
    				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
    			);
			}
		}
		
		return $this->output ( $response );
		
	}
	
	function reset_pin_authenticate ( ) {
	    
	    $response = array();

	    $rules = [
            'email'     => 'required|min_length[6]|max_length[50]|valid_email',
            'pin'       => 'required|min_length[4]'
        ];
		
		$input = $this->getRequestInput( $this->request );

        if ( ! $this->validateRequest ( $input, $rules ) ) {
            $response		    = array (
				'success'	    => 0,
				'msg'		    => $this->getValidationError ( ),
				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
			);
        } else {
		    $email = $input['email'];
		    $user = $this->UserModel->findUserByEmailAddress ( $email );
			
			if ( ! empty ( $user ) ) {
				if ( $user['reset_pin'] == $input['pin'] ) {
				    $current_user_id    = $user['id'];
				    $response				= array (
    					'success'			=> 1,
    					'user'				=> $user,
    					'access_token'      => getSignedJWTForUser ( $current_user_id ),
    					'msg'               => "Success!",
    					'http_status'		=> ResponseInterface::HTTP_OK
    				);
				} else {
					$response			= array (
        				'success'		=> 0,
        				'msg'           => "Invalid/expired PIN!",
        				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
        			);
				}
			} else {
				$response			= array (
    				'success'		=> 0,
    				'msg'           => "Invalid user!",
    				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
    			);
			}
		}
		
		return $this->output ( $response );
		
	}
	
	public function social_login ( ) {
		
		$response						= array ( );
		$rules = [
		    'social_source' => 'required|min_length[4]',
		    'social_id'     => 'required|min_length[4]',
            //'email'         => 'required|min_length[6]|max_length[50]|valid_email'
        ];
		$input = $this->getRequestInput( $this->request );
        if ( ! $this->validateRequest ( $input, $rules ) ) {
            $response		    = array (
				'success'	    => 0,
				'msg'		    => $this->getValidationError ( ),
				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
			);
        } else {
		    $social_source  =  $input['social_source']; 
    		$social_id      =  $input['social_id'];  
    		
    		if( $social_source == 'facebook' || $social_source == 'google' || $social_source == 'apple' ) {
    		    $field[ $social_source.'_id' ] = $social_id;
    		}
		    
		    if( ! empty ( $field ) ) {
		       
    			$user	= $this->UserModel->get_user_by_social ( $field );
    			
    			$input  = array_merge( $field, $input ) ;
    			
    			if( ! empty ( $user ) ) {
    			    
    			    if ( $user['status'] > 2 ) {
    			        $response				= array (
            				'success'			=> 0,
            				'msg'               => "Your account is blocked. Please contact administrator.",
            				'http_status'		=> ResponseInterface::HTTP_BAD_REQUEST
            			);
    			    } else {
        				$response				= array (
        					'success'			=> 1,
        					'user'				=> $user,
        					'http_status'		=> ResponseInterface::HTTP_OK
        				);
    			    }
    			    
    			} else {
    			    
    			    $user = array ();
    				
    				if ( isset ( $input['email'] ) && ! empty ( $input['email'] ) ) {
    				    
    				    $user	= $this->UserModel->get_user_by_email ( $input['email'] );
    				    if ( ! empty ( $user ) ) {
    				        $current_user_id = $user['id'];
    					    $this->UserModel->update ( $current_user_id, $input );
    				    } else {
    				        $this->UserModel->save ( $input );
    				        $current_user_id    = $this->UserModel->insertID();
    				    }
    				} else {
    				    $current_user_id        = $this->UserModel->save ( $input );
    				    $current_user_id        = $this->UserModel->insertID();
    				}
    				if ( $current_user_id > 0 ) {
    				    $user	                = $this->UserModel->get_user ( $current_user_id );
        				$response				= array (
        					'success'			=> 1,
        					'user'				=> $user,
        					'http_status'		=> ResponseInterface::HTTP_OK
        				);
    				} else {
    				    $response				= array (
        					'success'			=> 0,
        					'error'				=> 'Something went wrong.',
        					'http_status'		=> ResponseInterface::HTTP_BAD_REQUEST
        				);
    				}
    	
    			}
    			
			} else {
    				    
			    $response				= array (
					'success'			=> 0,
					'msg'               => "Parameters are not correct.",
					'http_status'		=> ResponseInterface::HTTP_BAD_REQUEST
				);
				
			}
			
		}
	
		return $this->output ( $response );
	}
	

}
