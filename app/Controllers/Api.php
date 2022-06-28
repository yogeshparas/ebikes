<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\BookingModel;
use App\Models\ProductModel;
use App\Models\ProductAddonModel;
use App\Models\RatingModel;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RequestInterface;
use Exception;
use ReflectionException;
use App\Libraries\Functions;
use Stripe;

class Api extends BaseController {
    
    protected $request;
    
    public function __construct() {
        $this->UserModel    = new UserModel();
        $this->BookingModel = new BookingModel();
        $this->ProductModel = new ProductModel();
        $this->AddonModel   = new ProductAddonModel();
        $this->RatingModel  = new RatingModel();
        $this->functions    = new Functions();
    }
    
    function reset_password ( ) {
	    
	    $response = array();
	    
	    $current_user_id = $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
    	    $rules = [
                'password'  => 'required|min_length[8]|max_length[255]'
            ];
    		
    		$input = $this->getRequestInput( $this->request );
    
            if ( ! $this->validateRequest ( $input, $rules ) ) {
                $response		    = array (
    				'success'	    => 0,
    				'msg'		    => $this->getValidationError ( ),
    				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
    			);
            } else {
    			
    			$update_user = array( 'reset_pin' => '', 'password' => $input['password'] );
				$this->UserModel->update ( $current_user_id, $update_user );
    		    $user                   = $this->UserModel->get_user ( $current_user_id );
    		    
    		    $response				= array (
    				'success'			=> 1,
    				'user'				=> $user,
    				'msg'               => "Success!",
    				'http_status'		=> ResponseInterface::HTTP_OK
    			);
    		}
    			
	    } else {
			$response				= array (
				'success'			=> 0,
				'msg'				=> $current_user_id,
				'http_status'	    => ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
		return $this->output ( $response );
		
	}
	
	/**
     * Logout a User
     * @return Response
     */
    public function logout ( ) {
		$response			= array ( );
		$current_user_id	= $this->validate_token ( );
        
	    if ( $current_user_id > 0 ) {
	        
            $data = array (
                'device_id'     => '',
                'device_token'  => ''
            );
            $this->UserModel->update( $current_user_id, $data );
            
			$response			= array (
				'success'		=> 1,
				'msg'           => 'Logout Successfully',
				'http_status'	=> ResponseInterface::HTTP_OK
			);
			
		} else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
	
		return $this->output ( $response );
	}
	
	public function create_profile ( ) {
		
		$response	= $data = array ( );
	    $current_user_id	= $this->validate_token ( );
		
		if ( $current_user_id > 0 ) {
    		
    		$input = $this->getRequestInput ( $this->request );
    		
    		if ( isset ( $_FILES['license_photo'] ) && ! empty ( $_FILES['license_photo']['name'] ) ) {
    			$res = $this->upload_file_on_server ( 'user_files', 'license_photo' );
    			if ( ! empty ( $res ) && ( $res['success'] == 1 ) ) {
    			    $input['license_photo']	= $res['file_name'];
    			} else {
    			    $response				= array (
        				'success'			=> 0,
        				'msg' 			    => $res['msg'],
        				'http_status'		=> ResponseInterface::HTTP_BAD_REQUEST
        			);
        			return $this->output ( $response );
    			}
    	    }
		    
		    if ( ! empty ( $input ) ) { 
		        $this->UserModel->update( $current_user_id, $input );
    			$user	                = $this->UserModel->get_user ( $current_user_id );
			    $response				= array (
    				'success'			=> 1,
    				'user'				=> $user,
    				'msg' 			    => "Updated successfully.",
    				'http_status'		=> ResponseInterface::HTTP_OK
    			);
		    } else {
		        $response				= array (
    				'success'			=> 0,
    				'msg' 			    => "Nothing to update",
					'http_status'		=> ResponseInterface::HTTP_BAD_REQUEST
    			);
		    }
		    
		} else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
		
		return $this->output ( $response );
				        
	}
	
	public function upload_profile_photo ( ) {
	    
		$current_user_id	= $this->validate_token ( );
		if ( $current_user_id > 0 ) {
    		$response = $this->upload_file_on_server (); 
			if ( $response['success'] == '1' ) {
			    $this->UserModel->update( $current_user_id, array ( 'photo' => $response['file_name'] ) );
				$response							= array (
    				'success'						=> 0,
    				'user'                          => $this->UserModel->get_user ( $current_user_id ),
    				'msg'							=> 'Success',
    				'http_status'					=> ResponseInterface::HTTP_OK
    			); 
			} else {
    		   $response							= array (
    				'success'						=> 0,
    				'msg'							=> $response['msg'],
    				'http_status'					=> ResponseInterface::HTTP_BAD_REQUEST
    			); 
    		}
		}  else {
			$response							= array (
				'success'						=> 0,
				'error'							=> $current_user_id,
				'http_status'					=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
	
		return $this->output ( $response );
	}
	
	public function get_products ( ) {
		$response			= array ( );
		$args               = array ();
		$current_user_id	= $this->validate_token ( );
		
		if ( $current_user_id > 0 ) {
		    
		    $args = $this->getRequestInput ( $this->request );
	
			$args['user_id'] = $current_user_id;
		
			if ( isset ( $args['height'] ) && strstr ( $args['height'], '-' ) !== false ) {
				$args['height'] = str_replace ( '-', ',', $args['height'] );
			}
		    
		    if ( isset ( $args['limit'] ) && ( $args['limit'] > 0 ) ) {
			    $limit	= $args['limit'];
		    } else {
		        $limit	= 20;
		    }
			$current_page	= ( isset ( $args['page'] ) ) ? $args['page'] : 1;
			$args['offset']	= ( $current_page - 1 ) * $limit;
			$items			= $this->ProductModel->get_products ( $args );
			
			if( ! empty ( $items ) ) {
			    foreach( $items as $k => $item ) {
			        if ( $item['addon'] == 1 ) {
			            $items[ $k ]['addons'] = $this->AddonModel->where( 'product_id', $item['id'] )->find (1);
			        }
			    }
			}
		
			$args['count']	= true;
			$total_items	= $this->ProductModel->get_products ( $args );
		
			if ( ! empty ( $total_items ) ) {
				$total_items	= $total_items['count'];
			} else {
				$total_items	= "0";
			}
	
			$response		    = array ( 
			    'success'       => 1, 
			    'items'         => $items, 
			    'total_items'   => ( int ) $total_items, 
			    'current_page'  => ( int ) $current_page, 
			    'http_status'   => ResponseInterface::HTTP_OK 
		    );
		
		    
		} else {
			$response			    = array (
				'success'		    => 0,
				'msg'			    => $current_user_id,
				'http_status'	    => ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
	
		return $this->output ( $response );
	}
	
	public function create_booking ( ) {
		
		$response	= $data = array ( );
	    $current_user_id	= $this->validate_token ( );
		
		if ( $current_user_id > 0 ) {
    		
    		$input = $this->getRequestInput ( $this->request );
    		
    		$rules = [
                'payment_type'  => 'required',
                'product_id'    => 'required'
            ];
            
            if ( isset( $input['payment_type'] ) && ( $input['payment_type'] == 'hourly' ) ) {
                $rules['pickup_time'] = 'required';
                $rules['return_time'] = 'required';
            }
    		
    		if ( ! $this->validateRequest ( $input, $rules ) ) {
                $response		    = array (
    				'success'	    => 0,
    				'msg'		    => $this->getValidationError ( ),
    				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
    			);
            } else {
                $product            = $this->ProductModel->find( $input['product_id'] );
                $input              = array_merge ( $input, $product );
                $input['user_id']   = $current_user_id;
		        $this->BookingModel->insert( $input );
		        $id                 = $this->BookingModel->insertID();
    			$booking	        = $this->BookingModel->find ( $id );
			    $response			= array (
    				'success'		=> 1,
    				'item'			=> $booking,
    				'msg' 			=> "Booked successfully.",
    				'http_status'	=> ResponseInterface::HTTP_OK
    			);
		    }
		    
		} else {
			$response			    = array (
				'success'		    => 0,
				'msg'			    => $current_user_id,
				'http_status'	    => ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
		
		return $this->output ( $response );
				        
	}
	
	public function update_booking ( ) {
		
		$response	= $data = array ( );
	    $current_user_id	= $this->validate_token ( );
		
		if ( $current_user_id > 0 ) {
    		
    		$input = $this->getRequestInput ( $this->request );
    		
    		$rules = [
                'booking_id'  => 'required'
            ];
            
            if ( ! $this->validateRequest ( $input, $rules ) ) {
                $response		    = array (
    				'success'	    => 0,
    				'msg'		    => $this->getValidationError ( ),
    				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
    			);
            } else {

        		if ( ! empty ( $input ) ) {
        		    $booking_id = $input['booking_id'];
    		        $this->BookingModel->update( $booking_id, $input );
        			$item	                = $this->BookingModel->find ( $booking_id );
    			    $response				= array (
        				'success'			=> 1,
        				'item'				=> $item,
        				'msg' 			    => "Updated successfully.",
        				'http_status'		=> ResponseInterface::HTTP_OK
        			);
    		    } else {
    		        $response				= array (
        				'success'			=> 0,
        				'msg' 			    => "Nothing to update",
    					'http_status'		=> ResponseInterface::HTTP_BAD_REQUEST
        			);
    		    }
            }
		    
		} else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
		
		return $this->output ( $response );
				        
	}
	
	public function add_rating ( ) {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
    	    $input = $this->getRequestInput ( $this->request );
    		
    		$rules = [
                'booking_id'    => 'required',
                'rating'        => 'required'
            ];
            
            if ( ! $this->validateRequest ( $input, $rules ) ) {
                $response		    = array (
    				'success'	    => 0,
    				'msg'		    => $this->getValidationError ( ),
    				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
    			);
            } else {
                $input['user_id']   = $current_user_id;
                $this->RatingModel->save($input);
                $response				= array (
                    'success'			=> 1,
                    'msg'               => 'Rating saved successfully.',
                    'http_status'		=> ResponseInterface::HTTP_OK
                );
            }
            
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function get_rating () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $input = $this->getRequestInput ( $this->request );
    		
    		$rules = [
                'booking_id'  => 'required'
            ];
            
            if ( ! $this->validateRequest ( $input, $rules ) ) {
                $response		    = array (
    				'success'	    => 0,
    				'msg'		    => $this->getValidationError ( ),
    				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
    			);
            } else {
	        
                $items                  = $this->RatingModel->where( 'booking_id', $input['booking_id'] )->find (1);
                $response				= array (
                    'success'			=> 1,
                    'item'             => $item,
                    'http_status'		=> ResponseInterface::HTTP_OK
                );
            }
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function add_card () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function get_cards () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
        /*$stripe = new \Stripe\StripeClient( STRIPE_SECRET );
        $token = $stripe->tokens->create([
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 6,
                'exp_year' => 2023,
                'cvc' => '314',
            ],
        ]);*/
	    
	    Stripe\Stripe::setApiKey(STRIPE_SECRET);
      
        $stripe = Stripe\Charge::create ([
                "amount" => 70 * 100,
                "currency" => "usd",
                "source" => 'tok_1LD7Gh2eZvKYlo2C5eUk9TYE',
                "description" => "Test payment via Stripe" 
        ]);

        // after successfull payment, you can store payment related information into 
        // your database

        $data = array('success' => true, 'data' => $stripe);
        echo json_encode($data); die;
        
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function save_like () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function save_feedback () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function save_help () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function save_transaction () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function get_transactions () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function get_bookings () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function get_notifications () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function save_notification_settings () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function send_emergency_notification () {
	    $response = array();
	    $current_user_id =  $this->validate_token ( );
	    
	    if ( $current_user_id > 0 ) {
	        
	        $response				= array (
                'success'			=> 1,
                'http_status'		=> ResponseInterface::HTTP_OK
            );
       
	    } else {
			$response			= array (
				'success'		=> 0,
				'msg'			=> $current_user_id,
				'http_status'	=> ResponseInterface::HTTP_UNAUTHORIZED
			);
		}
        return $this->output($response);
	    
	}
	
	public function get_states ( ) {
		$states             = $this->functions->get_states ();
		$response			= array (
			'success'		=> 1,
			'items'         => $states,
			'http_status'   => ResponseInterface::HTTP_OK
		);
		return $this->output ( $response );
	}
	
	public function get_cities () {
	    
	    $rules = [
                'state_id'  => 'required'
        ];
		
		$input = $this->getRequestInput( $this->request );

        if ( ! $this->validateRequest ( $input, $rules ) ) {
            $response		    = array (
				'success'	    => 0,
				'msg'		    => $this->getValidationError ( ),
				'http_status'	=> ResponseInterface::HTTP_BAD_REQUEST
			);
        } else {
		
    		$states                 = $this->functions->get_cities ( $input['state_id'] );
    		$response				= array (
    			'success'			=> 1,
    			'msg'				=> 'Success',
    			'items'             => $states,
    			'http_status'		=> ResponseInterface::HTTP_OK
    		);
        }
	
		return $this->output ( $response );
	}

}