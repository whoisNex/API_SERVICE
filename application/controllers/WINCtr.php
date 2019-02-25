<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    class WINCtr extends CI_Controller
    {
    public $tokenCode;
    public $tokenExpireTime;
	function WINCtr()
	{
	    parent::__construct();
	    //$this->tokenCode = '';
	    $this->tokenExpireTime = 2000;// in seconds
	    $this->load->model('WINMod');
	  
	    error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));
		
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			header('Access-Control-Allow-Credentials: true');
			header('Access-Control-Max-Age: 86400');    // cache for 1 day
		}
	
		// Access-Control headers are received during OPTIONS requests
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
				header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
	
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
				header("Access-Control-Allow-Headers:{$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
	
			exit(0);
		}    
	}


	public function index()
	{
		$postdata = file_get_contents("php://input");
		$resultData = array();
		if (isset($postdata)) {
			$request = $this->makeDecode();
			$resultData = $this->WINMod->loginAuthentication($request->user_id,$request->user_passwd,$request->token);
			if($resultData['flag'] === FALSE)
			{
				$result = array('status'=>'error', 'msg'=>'Invalid User Id and Password Please check it', 'result'=>array('0'=>''));
			}
			else
			{							
				
				$result = array('status'=>'success', 'msg'=>'Success fully login.', 'result'=>array($returnResultArray));	
			}				
		} else {				
			$result = array('status'=>'error', 'msg'=>'Invalid User Id and Password Please check it', 'result'=>array('0'=>''));
		}					

		echo json_encode($result);
		exit;
	}

	public function removeNullinJsonEncode($result){
		array_walk_recursive($result, function (&$item, $key) {
		    $item = null === $item ? '' : $item;
		});
		return $result;
	}

	// function to get the data
	public function makeDecode(){
		$postdata = file_get_contents("php://input");
		$resultData = array();
		if (isset($postdata)) {
			$request = json_decode($postdata);
			$token = $request->token;
			//$sessionToken = $this->session->userdata('USER_TOKEN_VALUE');
			// for login alone
			if ($token == 'yes')
			{
				return $request;
			}else{
				$result = array('status'=>'error', 'msg'=>'Invalid Request', 'result'=>array('0'=>''));
				echo json_encode($result);
				exit;
			}
		}
	}

	function signUp(){
		header('Content-Type: application/json');
		$this->WINMod->signUp();
	}

	function signIn(){
		header('Content-Type: application/json');
		$this->WINMod->signUp();
	}

	function signIn(){
		header('Content-Type: application/json');
		$this->WINMod->signUp();
	}            

	function LogOut()
	{
		header('Content-Type: application/json');
		$this->WINMod->LogOut();
		echo json_encode(array('status'=>'success', 'msg'=>'Logged Out successfully', 'result'=>array('0'=>'')));					
	}
	
}