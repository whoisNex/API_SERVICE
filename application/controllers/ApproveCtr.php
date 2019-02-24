<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    class ApproveCtr extends CI_Controller
    {
    public $tokenCode;
    public $tokenExpireTime;
	function ApproveCtr()
	{
	    parent::__construct();
	    //$this->tokenCode = '';
	    $this->tokenExpireTime = 900;
	    $this->load->model('api_services/ApproveMod');
	  
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

		$sqlQry = "SELECT * FROM APPS_MAINTENANCE_PLAN";
		$getResult = $this->db->query($sqlQry)->result_array();		
		$maintenanceMode = $getResult[0]['MP_ACTIVE_YN'];		
		if($maintenanceMode=='Y' && $this->uri->segment(2)!="admin"){			
			$data['MP_FROM_DT'] = $getResult[0]['MP_FROM_DT'];
			$data['MP_UPTO_DT'] = $getResult[0]['MP_UPTO_DT'];
			$data['MP_TITLE'] = $getResult[0]['MP_TITLE'];
			$data['MP_MSG'] = $getResult[0]['MP_MSG'];
			$data['MP_MSG'] = $getResult[0]['MP_MSG'];
			$result = array('status'=>'maintenance', 'result'=>$data);
		}else{
			//http://stackoverflow.com/questions/15485354/angular-http-post-to-php-and-undefined
			$postdata = file_get_contents("php://input");
			$resultData = array();
			if (isset($postdata)) {
				$request = $this->makeDecode();
				$resultData = $this->ApproveMod->loginAuthentication($request->user_id,$request->user_passwd,$request->token);
				if($resultData['flag'] === FALSE)
				{
					$result = array('status'=>'error', 'msg'=>'Invalid User Id and Password Please check it', 'result'=>array('0'=>''));
				}
				else
				{							
					$USER_COMP_CODE = $resultData[0]['USER_COMP_CODE'];
					$USER_ID = $resultData[0]['USER_ID'];
					$USER_PERS_CODE = $resultData[0]['USER_PERS_CODE'];
					$USER_TYPE = $resultData[0]['USER_TYPE'];
					$EMP_CODE = $resultData[0]['EM_CODE'];						
					$USER_LOCN_CODE = $resultData[0]['USER_LOCN_CODE'];
				    $tokenValue =  $resultData['newToken'];
					$returnResultArray['login_user'] = array('COMP_CODE'=>$USER_COMP_CODE,'USER_ID'=>$USER_ID,'USER_PERS_CODE'=>$USER_PERS_COMP,'USER_TYPE'=>$USER_TYPE,'EMP_CODE'=>$EMP_CODE, 'LOCN_CODE'=>$USER_LOCN_CODE, 'TOKEN'=>$tokenValue);					

					$result = array('status'=>'success', 'msg'=>'Success fully login.', 'result'=>array($returnResultArray));	
				}				
			} else {				
				$result = array('status'=>'error', 'msg'=>'Invalid User Id and Password Please check it', 'result'=>array('0'=>''));
			}			
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

	function deleteExistingUnUsedRecord(){
		header('Content-Type: application/json');
		$this->ApproveMod->deleteExistingUnUsedRecord();
	}

	function checkingAvailableOptions(){
		header('Content-Type: application/json');
		$this->ApproveMod->checkingAvailableOptions();
	}

	function checkingTxnAvailableToApprove(){
		header('Content-Type: application/json');
		$this->ApproveMod->checkingTxnAvailableToApprove();
	}

	function pendingApproveCount(){
		header('Content-Type: application/json');
		$this->ApproveMod->pendingApproveCount();
	}

	function pendingApproveData(){
		header('Content-Type: application/json');
		$this->ApproveMod->pendingApproveData();
	}

	function particularApproveData(){
		header('Content-Type: application/json');
		$this->ApproveMod->particularApproveData();
	}


	 //*******************************************APPROVE FUNCTIONALITY START*******************************************************

	// commom approve functionality


	function approveFunCommon(){
		header('Content-Type: application/json');
		$this->ApproveMod->approveFunCommon();
	}        
    //************************************* APPROVE FUNCTIONALITY END *************************************************            

	function LogOut()
	{
		header('Content-Type: application/json');
		$this->ApproveMod->LogOut();
		echo json_encode(array('status'=>'success', 'msg'=>'Logged Out successfully', 'result'=>array('0'=>'')));					
	}
	
}