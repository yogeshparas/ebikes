<?php
namespace App\Libraries;

use SendGrid\Mail\Mail;

class Functions  {
	function __construct() {
		$this->db = db_connect();
	}

	//--------------------------------------------------------
	// Paginaiton function 
	public function pagination_config($url,$count,$perpage) {
		$config = array();
		$config["base_url"] = $url;
		$config['reuse_query_string'] = true;
		$config["total_rows"] = $count;
		$config["per_page"] = $perpage;
		$config['full_tag_open'] = '<ul class="pagination pagination-split">';
		$config['full_tag_close'] = '</ul>';
		$config['prev_link'] = '<i class="fa fa-angle-left" aria-hidden="true"></i>';
		$config['prev_tag_open'] = '<li>';
		$config['prev_tag_close'] = '</li>';
		$config['next_link'] = '<i class="fa fa-angle-right" aria-hidden="true"></i>';
		$config['next_tag_open'] = '<li>';
		$config['next_tag_close'] = '</li>';
		$config['cur_tag_open'] = '<li class="active"><a href="#">';
		$config['cur_tag_close'] = '</a></li>';
		$config['num_tag_open'] = '<li>';
		$config['num_tag_close'] = '</li>';
		$config['first_tag_open'] = '<li>';
		$config['first_tag_close'] = '</li>';
		$config['last_tag_open'] = '<li>';
		$config['last_tag_close'] = '</li>';

		$config['first_link'] = '<i class="fa fa-angle-double-left" aria-hidden="true"></i>';
		$config['last_link'] = '<i class="fa fa-angle-double-right" aria-hidden="true"></i>';
		return $config;
	}
	
    public function send_email ( $to, $temp_id, $search_arr = array () ) {
		$query = $this->db->query( "SELECT * FROM email_templates WHERE id = $temp_id" );

        $et = $query->getRowArray();
		if( empty( $et ) ) {
			return false;
		}
		
		$message = str_replace (
			array_keys ( $search_arr ),
  			array_values ( $search_arr ),
			$et['template']
		);
		
		$message = '
			<div style="width:68%;float:left;margin:10px auto; padding:20px;">
				' . $message . '
			</div>
		';
		
		$message = '<div style="border-top:5px solid #eefaff;min-height:150px;background-color:#eefaff;padding:20px; font-family:arial; font-size:18px; line-height:18px;">' . $message . '</div>';

		$email = new \SendGrid\Mail\Mail();
		$email->setFrom( getenv( 'ADMIN_EMAIL' ), getenv( 'ADMIN_NAME' ) );
		$email->setSubject( $et['subject'] );

		$email->addTo( $to );
		
		$email->addContent( "text/html", $message );
	
		$sendgrid = new \SendGrid( getenv( 'SENDGRID_API_KEY' ) );
		try {
			$response = $sendgrid->send( $email );
			$status = $response->statusCode();

			if ( $status == '202' ) {
				return true;
			}
		} catch (Exception $e) {
			echo 'Caught exception: '. $e->getMessage();
		}
		return false;
	}

	public function send_notification ( $data ) {
		if ( ! empty ( $data ) ) {
			$db->select ( 'device_token, device_type' )->from ( 'users' )->where ( array ( 'id' => $data['user_id'] ) )->limit ( 1 );
			$query = $db->get ();
			if ( $query->num_rows() > 0 ) {
    			$row = $query->row_array ();
				if ( ! empty ( $row['device_token'] ) ) { 
					$url = 'https://fcm.googleapis.com/fcm/send';
					
					$fields = array (
						'to'			=> $row['device_token'],
						'notification' 	=> array (
							"title"	 	=> $data['title'],
							"body" 		=> ((isset($data['body']))?$data['body']:''),
							"type"		=> $data['type'],
							"sound"		=> 'default'
						),
						'data' => array (
							"title" 	=> $data['title'],
							"body" 		=> ((isset($data['body']))?$data['body']:''),
							"type"		=> $data['type']
						),
						"content_available"	=> true
					);
					
					if( $row['device_type'] != 'iOS' ) {
						unset($fields['notification']);
					} else if( ( $row['device_type'] == 'iOS' ) && ( isset ( $data['is_hidden'] ) && ( $data['is_hidden'] == 1 ) ) ) {
						unset($fields['notification']);
					}
					if( isset ( $data['sender_user_id'] ) && ( $data['sender_user_id'] > 0 ) ) {
					    $fields['data']['sender_user_id'] = $data['sender_user_id'];
					}
					if( isset ( $data['sender_image'] ) && ! empty ( $data['sender_image'] ) ) {
					    $fields['data']['sender_image'] = $data['sender_image'];
					}
					if( isset ( $data['sender_name'] ) && ! empty ( $data['sender_name'] ) ) {
					    $fields['data']['sender_name'] = $data['sender_name'];
					}
					
					//echo "<pre>"; print_r($fields); echo "</pre>"; die;
					$headers = array (
						'Authorization: key=AAAAwzz_vmg:APSPJzahSNAeen',
						'Content-Type: application/json'
					);
					
					$ch = curl_init ();
					curl_setopt ( $ch, CURLOPT_URL, $url );
					curl_setopt ( $ch, CURLOPT_POST, true );
					curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
					curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
					curl_setopt ( $ch, CURLOPT_POSTFIELDS, json_encode ( $fields ) );

					$result = curl_exec ( $ch );
					//echo "<pre>"; print_r($result); echo "</pre>"; die;
					curl_close ( $ch );
					
					return true;
				}
					
			}
		}
		
		return false;
	}
	
	function get_states () {
		$query = $this->db->query( "SELECT * FROM states" );
        return $query->getResultArray();
	}
	
	public function get_cities ( $state_id ) {
	    $query = $this->db->query( "SELECT * FROM cities WHERE ID_STATE = $state_id" );
		return $query->getResultArray();
	}

}
