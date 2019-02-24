<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    class ApproveMod extends CI_Model
    {

//**********************************************************************************************************************
//********************************** BASIC FUNCTIONS STARTED ******************************************************
//************************************************************************************************************************

// ERROR WRITTEN HERE IS UNIQUE TO UNDERSTAND WHERE ERROR HAPPENED
	//$this->passArrayOfValue($value);
	function makeDecode(){
	$postdata = file_get_contents("php://input");
	if (isset($postdata)) {
		$request = json_decode($postdata);
		$token = $request->token;
		$user_id = $request->user_id;

		$sql = " SELECT TOKEN_LAST_ACTIVITY_TIME, TOKEN_EXPIRE_TIME, LOCALTIMESTAMP AS NOW FROM APPS_USER_TOKEN 
			WHERE 
			TOKEN_USER_ID = '$user_id' AND TOKEN_CODE = '$token' 
			";
		$sqlResult = $this->db->query($sql);
		if($sqlResult->num_rows() == 1 ){
			$dataToken = $sqlResult->result_array();
			$lastActivity = strtotime($dataToken[0]['TOKEN_LAST_ACTIVITY_TIME']);
			$expireTime = floatval($dataToken[0]['TOKEN_EXPIRE_TIME']);
			$now = strtotime($dataToken[0]['NOW']);
			// CHECKING CURRENT TIME AND LAST ACTIVITY TIME WITH SESSION EXPIRIRATION TIME
			if($now > floatval($lastActivity + $expireTime)){
				$sql = " DELETE FROM APPS_USER_TOKEN WHERE 
					TOKEN_USER_ID = '$user_id' AND TOKEN_CODE = '$token' 
					";
					$sqlResult = $this->db->query($sql);
					$resultCheck = ($this->db->affected_rows() != 1) ? FALSE : TRUE;
					if($resultCheck === FALSE){
						$result = array('status'=>'error', 'msg'=>'Error : 5001 - Please contact support team', 'result'=>array('0'=>''));
						echo json_encode($result);exit;
					}
				$result = array('status'=>'error', 'msg'=>'SESSION_EXPIRED', 'result'=>array('0'=>''));
				echo json_encode($result);
				exit;
			}

			$sql = " UPDATE APPS_USER_TOKEN SET TOKEN_LAST_ACTIVITY_TIME = LOCALTIMESTAMP 
			WHERE 
			TOKEN_USER_ID = '$user_id' AND TOKEN_CODE = '$token' 
			";
			$sqlResult = $this->db->query($sql);
			$resultCheck = ($this->db->affected_rows() != 1) ? FALSE : TRUE;
			if($resultCheck === FALSE){
				$result = array('status'=>'error', 'msg'=>'Error : 5002 - Please contact support team', 'result'=>array('0'=>''));
				echo json_encode($result);exit;
			}
			$this->tokenCode = $token;
			return $request;
			// method getting success here 
		}else{
			$result = array('status'=>'error', 'msg'=>'INVALID_REQUEST', 'result'=>array('0'=>''));
			echo json_encode($result);
			exit;
		}
		
	}else {
		$result = array('status'=>'error', 'msg'=>'INVALID_REQUEST', 'result'=>array('0'=>''));
		echo json_encode($result);
		exit;
	}
	}
	// function for debugging time
	function passArrayOfValue($value){
		echo json_encode(array(
			"data"		=> 		$value
		));
		exit;
	}
	
	function loginAuthentication($user_id,$user_passwd,$token)
	{
	    $user_id = $user_id;//$this->input->get('user_id');
	    $password = md5($user_passwd);//md5($this->security->xss_clean($this->input->get('user_passwd')));
	    $date = date('d-M-y');
	    		
		$sql="SELECT USER_ID, USER_DESC, USER_PERS_CODE, USER_LOCN_CODE, USER_COMP_CODE,USER_RESET_PW_YN, USER_IMAGE_FILE, USER_PW_CHANGE_YN, USER_TYPE, 
			USER_SHOW_SALE_PRICE_YN, USER_SHOW_COST_PRICE_YN,
			USER_FEEDBACK_YN,USER_SALE_DISC_PCT, NVL(USER_FORCE_SEC_YN,'N') USER_FORCE_SEC_YN, USER_MULTI_COMP_YN 
			FROM APPS_USER 
			WHERE LOWER(USER_ID)=LOWER('$user_id') AND USER_PASSWD='$password' AND USER_ACTIVE_YN='Y' 
			AND TRUNC(SYSDATE) BETWEEN TRUNC(USER_FROM_DT) AND TRUNC(USER_UPTO_DT)
			";
				
	    $query = $this->db->query($sql);
	    if($query->num_rows > 0)
	    {
	    	$result = $query->result_array();
	    	$result['newToken'] = $this->getNewToken($result);
	    	$result['flag'] = TRUE;
			return $result;
	    }
	    else
	    {
	    	$result['flag'] = FALSE;
			return $result;
	    }
	}

	function getNewToken($result){
		$user_id = $result[0]['USER_ID'];
		$sql ="SELECT APPS_LOGIN.NEXTVAL AS SYS_ID  FROM DUAL";
		$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=FALSE);
		$sysID = $sqlResult->result_array();
		$tokenSysID = $sysID[0]['SYS_ID'];
		if($tokenSysID !="" && $tokenSysID != NULL){

			$timeNow = date('d-M-Y h:m:s');
			$tokenCode   = md5(rand( 99, 99999).$tokenSysID.$timeNow);
			$this->tokenCode = $tokenCode;
			$sql ="INSERT INTO APPS_USER_TOKEN (TOKEN_SYS_ID, TOKEN_USER_ID, TOKEN_CODE, TOKEN_EXPIRE_TIME, TOKEN_LAST_ACTIVITY_TIME) VALUES ($tokenSysID,'$user_id', '$tokenCode', '$this->tokenExpireTime', LOCALTIMESTAMP) ";
			$sqlResult = $this->db->query($sql);
			$resultCheck = ($this->db->affected_rows() != 1) ? FALSE : TRUE;
			if($resultCheck === FALSE){
				// $this->db->txn_have_error();
    // 			$this->db->txn_end_now();
				$result = array('status'=>'error', 'msg'=>'Error : 5003 - Please contact support team', 'result'=>array('0'=>''));
				echo json_encode($result);exit;
			}
			return $tokenCode;
		}else{
			$result = array('status'=>'error', 'msg'=>'Error : 5004 - Please contact support team', 'result'=>array('0'=>''));
			echo json_encode($result);exit;
		}
	}

	function deleteExistingUnUsedRecord($daysGap = 2){
		// delete all the record except past two days data

		$result = $this->makeDecode();
		$sql = "SELECT TOKEN_CODE FROM APPS_USER_TOKEN WHERE TOKEN_LAST_ACTIVITY_TIME < (LOCALTIMESTAMP - $daysGap )";
		$sqlResult = $this->db->query($sql)->num_rows();
		if($sqlResult == 0){
			$result = array('status'=>'success', 'msg'=>'No Data Delete', 'result'=>array('0'=>''));
			echo json_encode($result);
			exit;
		}
		$sql = "DELETE  FROM APPS_USER_TOKEN WHERE TOKEN_LAST_ACTIVITY_TIME < (LOCALTIMESTAMP - $daysGap )";
		$sqlResult = $this->db->query($sql);
		$resultCheck = ($this->db->affected_rows() != 1) ? FALSE : TRUE;
		if($resultCheck === FALSE){
			$result = array('status'=>'error', 'msg'=>'Error : 5999 - Please contact support team', 'result'=>array('0'=>''));
			echo json_encode($result);exit;
		}
		$result = array('status'=>'success', 'msg'=>'Deleted successfully', 'result'=>array('0'=>''));
		echo json_encode($result);exit;
	}

	public function removeNullinJsonEncode($result){
		array_walk_recursive($result, function (&$item, $key) {
		    $item = null === $item ? '' : $item;
		});
		return $result;
	}


	function time_ago_in_php($time, $timestamp){
		$time_ago        = strtotime($time);
		$current_time    = strtotime($timestamp);
		$time_difference = $current_time - $time_ago;
		$seconds         = $time_difference;

		$minutes = round($seconds / 60); // value 60 is seconds  
		$hours   = round($seconds / 3600); //value 3600 is 60 minutes * 60 sec  
		$days    = round($seconds / 86400); //86400 = 24 * 60 * 60;  
		$weeks   = round($seconds / 604800); // 7*24*60*60;  
		$months  = round($seconds / 2629440); //((365+365+365+365+366)/5/12)*24*60*60  
		$years   = round($seconds / 31553280); //(365+365+365+365+366)/5 * 24 * 60 * 60
		            
		if ($seconds <= 60){

			return "Just Now";

		} else if ($minutes <= 60){

			if ($minutes == 1){

			  return "one minute ago";

			} else {

			  return "$minutes minutes ago";

			}

		} else if ($hours <= 24){

			if ($hours == 1){

			  return "an hour ago";

			} else {

			  return "$hours hrs ago";

			}

		} else if ($days <= 7){

			if ($days == 1){

			  return "yesterday";

			} else {

			  return "$days days ago";

			}

		} else if ($weeks <= 4.3){

			if ($weeks == 1){

			  return "a week ago";

			} else {

			  return "$weeks weeks ago";

			}

		} else if ($months <= 12){

			if ($months == 1){

			  return "a month ago";

			} else {

			  return "$months months ago";

			}

		} else {

			if ($years == 1){

			  return "one year ago";

			} else {

			  return "$years years ago";

			}
		}
	}


	function LogOut(){
		$result = $this->makeDecode();
		$token = $result->token;
		$user_id = $result->user_id;

		$sql = " DELETE FROM APPS_USER_TOKEN WHERE TOKEN_USER_ID = '$user_id' AND TOKEN_CODE = '$token' ";
			$sqlResult = $this->db->query($sql);
			$resultCheck = ($this->db->affected_rows() != 1) ? FALSE : TRUE;
			if($resultCheck === FALSE){
				$result = array('status'=>'error', 'msg'=>'Error : 5100 - Please contact support team', 'result'=>array('0'=>''));
				echo json_encode($result);exit;
			}
		echo json_encode(array('status'=>'success', 'msg'=>'Logged Out successfully', 'result'=>array('0'=>'')));					
		exit;
	}
//**********************************************************************************************************************
//********************************** BASIC FUNCTIONS ENDED ******************************************************
//************************************************************************************************************************

//**********************************************************************************************************************
//**********************************Functionality  Starting here for APP ******************************************************
//************************************************************************************************************************


	// THIS FUNCTION USED TO IMPLODE IN QUERY AND TO USE APPOVED TXNS IN APP
	function txnGotApproveToUseInAPP(){
		//$arrayTxn = array("'RP'","'SO'");// this is the format 
		// $arrayTxn = array("'RP'","'SO'");
		$arrayTxn = array("'RP'");
		return $arrayTxn;
	}
	function txnGotApproveToUseInAPPNormal(){
		$arrayTxn = array("RP");
		//print_r($arrayTxn);exit;
		return $arrayTxn;
	}

	function checkingAvailableOptions()
	{
		$result = $this->makeDecode();
		$comp_code = $result->comp_code;
		$user_id = $result->user_id;
		//$this->db->txn_start_now();

		$txnS = $this->txnGotApproveToUseInAPP();
		$txnCode = implode(",",$txnS);
		$txnCode = '(' . $txnCode .')';
		$sql="SELECT MENU_TXN_CODE, MENU_DESC from
				(SELECT MENU_TXN_CODE, MENU_DESC
				FROM APPS_USER_RESP_LINES,
				APPS_MENU
				WHERE USRL_MENU_CODE = MENU_CODE
				AND MENU_CODE        = USRL_MENU_CODE
				AND USRL_COMP_CODE   = '$comp_code'
				AND USRL_USER_ID     = '$user_id'
				AND MENU_PARA_03    IS NOT NULL
				AND NVL(USRL_APPROVE_YN,'N') = 'Y'
				AND SYSDATE BETWEEN USRL_FROM_DT AND USRL_UPTO_DT
				AND MENU_TXN_CODE    IN   $txnCode
				--AND ROWNUM=1
				) ";
		//echo $sql;exit;
		$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=TRUE);
	    $data = $sqlResult->result_array();
	    $txnCount = $sqlResult->num_rows();

	    if($txnCount > 0){
	    	$PENDING_APPROVE_YN = 'Y';
	    }else{
	    	$PENDING_APPROVE_YN = 'N';
	    }

	    $sqlCount="SELECT COUNT(*) NO_OF_TASK
					FROM APPS_USER_TODO
					WHERE UT_COMP_CODE   = '$comp_code'
					AND UT_USER_ID       = '$user_id'
					AND UT_TXN_CODE		 IN $txnCode
					AND NVL(UT_STATUS,1) = 1 ";
			//echo $sqlCount;exit;
		   	$sqlResultCount = $this->db->query($sqlCount,$binds=FALSE,$object=TRUE,$noNeedNumRow=FALSE);
		    $dataCount = $sqlResultCount->result_array();

	    echo json_encode(array(
	    		"PENDING_APPROVE_YN"				=>		$PENDING_APPROVE_YN,
	    		"SALES_YN"							=>		'Y',
	    		"CASH_FLOW_YN"						=>		'Y',
	    		"BANK_BALANCE_YN"					=>		'Y',
	    		"PENIDNG_APPROVAL_COUNT" 			=>		$dataCount,
	    		"AVAILABLE_TXNS" 					=>		$data,
	    	));
	    exit;
	}

	function checkingTxnAvailableToApprove()
	{// CURRENTLY WE ARE NOT USING THIS TXN
		$result = $this->makeDecode();
		$comp_code = $result->comp_code;
		$user_id = $result->user_id;
		//$this->db->txn_start_now();

		$txnS = $this->txnGotApproveToUseInAPP();
		$txnCode = implode(",",$txnS);
		$txnCode = '(' . $txnCode .')';
		$sql="SELECT MENU_TXN_CODE, MENU_DESC from
				(SELECT MENU_TXN_CODE, MENU_DESC
				FROM APPS_USER_RESP_LINES,
				APPS_MENU
				WHERE USRL_MENU_CODE = MENU_CODE
				AND MENU_CODE        = USRL_MENU_CODE
				AND USRL_COMP_CODE   = '$comp_code'
				AND USRL_USER_ID     = '$user_id'
				AND MENU_PARA_03    IS NOT NULL
				AND NVL(USRL_APPROVE_YN,'N') = 'Y'
				AND SYSDATE BETWEEN USRL_FROM_DT AND USRL_UPTO_DT
				AND MENU_TXN_CODE    IN   $txnCode
				--AND ROWNUM=1
				) ";
		//echo $sql;exit;
		$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=TRUE);
	    $data = $sqlResult->result_array();
	    $dataCount = $sqlResult->num_rows();
	    echo json_encode(array(
	    		"dataCount" 		=>		$dataCount,
	    		"data" 				=>		$data
	    	));
	    exit;
	}

	function pendingApproveCount()
	{
		$result = $this->makeDecode();

		$comp_code = $result->comp_code;
		$user_id = $result->user_id;
		$txn_code = $result->txn_code;
		$locn_code = $result->locn_code;
		//$this->db->txn_start_now();
		$collectionOfTxnCode = $this->txnGotApproveToUseInAPPNormal();
		
		//print_r($collectionOfTxnCode);exit;
		// if (in_array($txn_code, $collectionOfTxnCode)){
		// 	echo json_encode(array(
		//     		"dataCount" 	=>		0,
		//     		"data" 			=>		array()
		//     	));
		//     exit;
		// }
		$sql="SELECT TAH_NO_OF_LEVELS, TAH_SYS_ID, TAL_LEVEL_NO FROM APPS_TXN_APPROVAL_HEAD, APPS_TXN_APPROVAL_LINES WHERE TAH_COMP_CODE='$comp_code' AND TAH_LOCN_CODE='$locn_code' AND TAH_TXN_CODE='$txn_code' AND TAH_ACTIVE_YN ='Y'  
					AND TAL_COMP_CODE = TAH_COMP_CODE
					AND TAL_LOCN_CODE  = TAH_LOCN_CODE
					AND TAL_TXN_CODE   = TAH_TXN_CODE
					AND TAL_ACTIVE_YN  = TAH_ACTIVE_YN
					AND TAL_USER_ID    = '$user_id'
					AND TAL_TAH_SYS_ID = TAH_SYS_ID 
					";
		//echo $sql;exit;
	   	$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=TRUE);
	    $data = $sqlResult->result_array();
	    $dataCount = $sqlResult->num_rows();
	    if($dataCount > 0){
		    $TAH_SYS_ID = $data[0]['TAH_SYS_ID'];
		    $TAH_NO_OF_LEVELS = $data[0]['TAH_NO_OF_LEVELS'];
		    $TAL_LEVEL_NO_RIGHTS = $data[0]['TAL_LEVEL_NO'];
		}else{
			$TAH_SYS_ID = '';
		}

	    //if($dataCount == 0 || $TAH_NO_OF_LEVELS == 1){
	    	// CASE : 1 : not given level time or only one level given
	    	$sql="SELECT COUNT(*) NO_OF_TASK
					FROM APPS_USER_TODO
					WHERE UT_COMP_CODE   = '$comp_code'
					AND UT_USER_ID       = '$user_id'
					AND UT_TXN_CODE		 = '$txn_code'
					AND NVL(UT_STATUS,1) = 1 ";
			//echo $sql;exit;
		   	$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=FALSE);
		    $data = $sqlResult->result_array();

	  //   }else if($TAH_SYS_ID !="" && $TAH_NO_OF_LEVELS > 1 && $TAL_LEVEL_NO_RIGHTS !=""){// having levels here
	  //   	// CASE : 2 : 2 or more levels
	    	
		 //    $sql="SELECT COUNT(*) NO_OF_TASK
			// 		FROM APPS_USER_TODO
			// 		WHERE UT_COMP_CODE   = '$comp_code'
			// 		AND UT_USER_ID       = '$user_id'
			// 		AND UT_TXN_CODE		 = '$txn_code'
			// 		AND NVL(UT_STATUS,$TAL_LEVEL_NO_RIGHTS) = $TAL_LEVEL_NO_RIGHTS
			// 		";
			// //echo $sql;exit;
		 //   	$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=FALSE);
		 //    $data = $sqlResult->result_array();
	  //   }

	    echo json_encode(array(
	    		"TXN_CODE" 						=>		$txn_code,
	    		"PENIDNG_APPROVAL_COUNT" 		=>		$data
	    	));
	    exit;
	}

	function pendingApproveData()
	{
		$result = $this->makeDecode();

		$comp_code = $result->comp_code;
		$user_id = $result->user_id;
		$txn_code = $result->txn_code;
		$locn_code = $result->locn_code;
		//$this->db->txn_start_now();
		$collectionOfTxnCode = $this->txnGotApproveToUseInAPPNormal();
		
		//print_r($collectionOfTxnCode);exit;
		// if (in_array($txn_code, $collectionOfTxnCode)){
		// 	echo json_encode(array(
		//     		"dataCount" 	=>		0,
		//     		"data" 			=>		array()
		//     	));
		//     exit;
		// }
		$sql="SELECT TAH_NO_OF_LEVELS, TAH_SYS_ID, TAL_LEVEL_NO FROM APPS_TXN_APPROVAL_HEAD, APPS_TXN_APPROVAL_LINES WHERE TAH_COMP_CODE='$comp_code' AND TAH_LOCN_CODE='$locn_code' AND TAH_TXN_CODE='$txn_code' AND TAH_ACTIVE_YN ='Y'  
					AND TAL_COMP_CODE = TAH_COMP_CODE
					AND TAL_LOCN_CODE  = TAH_LOCN_CODE
					AND TAL_TXN_CODE   = TAH_TXN_CODE
					AND TAL_ACTIVE_YN  = TAH_ACTIVE_YN
					AND TAL_USER_ID    = '$user_id'
					AND TAL_TAH_SYS_ID = TAH_SYS_ID ";
		//echo $sql;exit;
	   	$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=TRUE);
	    $data = $sqlResult->result_array();
	    $dataCount = $sqlResult->num_rows();
	    if($dataCount > 0){
		    $TAH_SYS_ID = $data[0]['TAH_SYS_ID'];
		    $TAH_NO_OF_LEVELS = $data[0]['TAH_NO_OF_LEVELS'];
		    $TAL_LEVEL_NO_RIGHTS = $data[0]['TAL_LEVEL_NO'];
		    $flagLevel = 'Y';
		}else{
			$TAH_SYS_ID = '';
			$flagLevel = 'N';
		}

	    //if($dataCount == 0 || $TAH_NO_OF_LEVELS == 1){
	    	// CASE : 1 : not given level time or only one level given
	    	//TO_CHAR(UT_LOG_DATE,'DD-MM-YYYY HH:MI:SS AM')
	    	$sql="SELECT UT_LOG_DATE, LOCALTIMESTAMP, UT_PROCESS, UT_SUBJECT, UT_TXN_CODE, UT_TXN_SYS_ID, UT_LINK, UT_SYS_ID, PRH_TOTAL_AMT_FC AS AMOUNT, REQUESTED_BY, PRH_CCY_CODE
					FROM APPS_USER_TODO, FINC_V_PAYMENT_REQ_HEAD
					WHERE UT_COMP_CODE   = '$comp_code'
					AND UT_USER_ID       = '$user_id'
					AND UT_TXN_CODE      = '$txn_code'
					AND UT_TXN_SYS_ID	 =  PRH_SYS_ID
					AND UT_COMP_CODE     =  PRH_COMP_CODE
					AND NVL(UT_STATUS,1) = 1
					ORDER BY UT_SYS_ID DESC ";
			//echo $sql;exit;
		   	$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=TRUE);
		    $data = $sqlResult->result_array();
		    $dataCount = $sqlResult->num_rows();
	    	//exit;
	  //   }else if($TAH_SYS_ID !="" && $TAH_NO_OF_LEVELS > 1 && $TAL_LEVEL_NO_RIGHTS !=""){// having levels here
	  //   	// CASE : 2 : 2 or more levels
	  //   	//TO_CHAR(UT_LOG_DATE,'DD-MM-YYYY HH:MI:SS AM')
		 //    $sql="SELECT UT_LOG_DATE, LOCALTIMESTAMP, UT_PROCESS, UT_SUBJECT, UT_TXN_CODE, UT_TXN_SYS_ID, UT_LINK, UT_SYS_ID, PRH_TOTAL_AMT_FC AS AMOUNT, REQUESTED_BY, PRH_CCY_CODE
			// 		FROM APPS_USER_TODO, FINC_V_PAYMENT_REQ_HEAD
			// 		WHERE UT_COMP_CODE   = '$comp_code'
			// 		AND UT_USER_ID       = '$user_id'
			// 		AND UT_TXN_CODE      = '$txn_code'
			// 		AND UT_TXN_SYS_ID	 =  PRH_SYS_ID
			// 		AND UT_COMP_CODE     =  PRH_COMP_CODE
			// 		AND NVL(UT_STATUS,$TAL_LEVEL_NO_RIGHTS) = $TAL_LEVEL_NO_RIGHTS
			// 		ORDER BY UT_SYS_ID DESC ";
			// //echo $sql;exit;
		 //   	$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=TRUE);
		 //    $data = $sqlResult->result_array();
		 //    $dataCount = $sqlResult->num_rows();
	  //   }

	    // $result = $this->removeNullinJsonEncode($data);

	    if(count($data) > 1){
	    	foreach ($data as $key => $value) {
	    		//echo "<br>";
	    		$timeLog = $value['UT_LOG_DATE'];
	    		$LOCALTIMESTAMP = $value['LOCALTIMESTAMP'];
	    		$data[$key]['UT_LOG_DATE'] = $this->time_ago_in_php($timeLog, $LOCALTIMESTAMP);
	    	}
	    	//exit;
	    	
	    }
	    $resultToRemoveNull = array(
	    		"DATA_TOTAL_COUNT" 					=>		$dataCount,
	    		"PENIDNG_APPROVAL_DATA_LIST" 		=>		$data,
	    		"LEVEL_YN"						=>		$flagLevel,
	    		"TOTAL_LEVEL"					=>		$TAH_NO_OF_LEVELS,
	    		"CURRENT_LEVEL"					=>		$TAL_LEVEL_NO_RIGHTS
	    	);
	  
	    $result = $this->removeNullinJsonEncode($resultToRemoveNull);
	    echo json_encode($result);
	    exit;
	}

	function particularApproveData(){
		$result = $this->makeDecode();

		$comp_code = $result->comp_code;
		$user_id = $result->user_id;
		$txn_code = $result->txn_code;
		$sys_id = $result->sys_id;
		switch ($txn_code) {
			case "RP":
			$sql = "SELECT * FROM FINC_V_PAYMENT_REQ_HEAD WHERE PRH_SYS_ID = '$sys_id' AND PRH_COMP_CODE ='$comp_code' ";
			$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=TRUE);
		    $data['HEAD'] = $sqlResult->result_array();
		    $dataCount = $sqlResult->num_rows();
		    $PRH_TYPE = $data['HEAD'][0]['PRH_TYPE'];

	        if($PRH_TYPE == 'SUPPLIER'){
	            $sql="SELECT PRL_SYS_ID, PRL_AMOUNT_FC, PRL_PAYMENT_DT  FROM FINC_T_PAYMENT_REQ_LINES WHERE PRL_COMP_CODE = '$comp_code' AND PRL_PRH_SYS_ID='$sys_id' ORDER BY PRL_SYS_ID ASC ";    
	        }else if($PRH_TYPE == 'EMPLOYEE'){
	            $sql="SELECT PRE_SYS_ID, PRE_AMOUNT_FC, (SELECT EM_ID ||'-'|| EM_NAME FROM HRMS_M_EMPLOYEE WHERE EM_COMP_CODE = '$comp_code'
        		AND (EM_CODE = PRE_EM_CODE OR EM_ID = PRE_EM_CODE) AND EM_ACTIVE_YN = 'Y' ) EMPLOYEE_DETAIL 
               FROM FINC_T_PAYMENT_REQ_EMP WHERE PRE_COMP_CODE = '$comp_code' AND PRE_PRH_SYS_ID='$sys_id' ORDER BY PRE_SYS_ID ASC ";
	        }
        
        	$sqlResult = $this->db->query($sql,$binds=FALSE,$object=TRUE,$noNeedNumRow=TRUE);
		    $data['LINES'] = $sqlResult->result_array();
		    
			break;

			default:
			$sql = "";
		}
		if($sql ==""){
			 echo json_encode(array(
	    		"DATA_COUNT" 					=>		0,
	    		"DATA" 							=>		array()
	    	));
	    	exit;
		}

		$result = $this->removeNullinJsonEncode($data);
		
	    echo json_encode(array(
	    		"DATA_COUNT" 					=>		$dataCount,
	    		"DATA" 							=>		$result
	    	));
	    exit;
	}
	///**********************************APPROVE CONCEPT START***************************************************


    function approveFunCommon(){		
		$result = $this->makeDecode();
    	
    	$comp_code = $result->comp_code;
    	$sys_id = $result->sys_id;
		$user_id = $result->user_id;
		$txn_code = $result->txn_code;
		$txn_status = $result->txn_status;
		$decision = $result->decision;
		$reject_code = $result->reject_code;
		$reject_desc = $result->reject_desc;
		$level_yn = $result->level_yn;
		$levelNo = $result->level_no;
		$noOfLevels = $result->no_of_levels;
		if($level_yn != 'Y'){
			$levelNo = 0;
			$noOfLevels = 0;
		}
		

		$cFlag = 0;
		$comments=$_POST['comments'];

		if($decision=='yes')
		{
			if($txn_status=='Incomplete'){
				if($noOfLevels >1){
					$txn_status_show='PRESEND';
					$txn_yn_status='1';
				}else{
					$txn_status_show='SEND';
					$txn_yn_status='Y';
				}
				
			}else if($txn_status=='Pre approval'){
				$levelNo = $levelNo + 1 ;
				$txn_status_show='PREAPPROVE';
				$txn_yn_status=$levelNo;
				$cFlag = 1;
			}else if($txn_status=='Pre Approved'){

				if($noOfLevels == $levelNo){
					$txn_status_show='APPROVE';
					$txn_yn_status='Y'; 
				}else{
					$levelNo = $levelNo + 1 ;
					$txn_status_show='PREAPPROVE';
					$txn_yn_status=$levelNo; 
				}				
				
			}else if($txn_status=='Send for approval'){
				$txn_status_show='APPROVE';
				$txn_yn_status='Y';
				$cFlag = 1;
			}else if($txn_status=='Rejected'){
				if($noOfLevels >1){
					$txn_status_show='PRESEND';
					$txn_yn_status='1';
				}else{
					$txn_status_show='SEND';
					$txn_yn_status='Y';
				}
			}else if($txn_status=='Approved'){
				$txn_status_show='APPROVE';
				$txn_yn_status='N';
				$comments = $reject_desc;
			}else if($txn_status=='Amended'){
				if($noOfLevels >1){
					$txn_status_show='PRESEND';
					$txn_yn_status='1';
				}else{
					$txn_status_show='SEND';
					$txn_yn_status='Y';
				}
			}
		}else if($decision=='no')
		{
			if($txn_status=='Send for approval' || $txn_status=='Pre approval' ||  $txn_status=='Pre Approved'){
				if($noOfLevels >1){
					$txn_status_show='PREREJECT';
					$txn_yn_status='N';
				}else{
					$txn_status_show='SEND';
					$txn_yn_status='N';
				}    
				$comments = $reject_desc;                
			}
		}
		
		switch ($txn_code) {
			case "RP":
			$DUM_Table="FINC_T_PAYMENT_REQ_HEAD";
			$DUM_Sys_Id="PRH_SYS_ID";
			$prefix = 'PRH';
			break;
		}
		
	$params = array(
		array('name'=>':P_COMP_CODE',  'value'=>$comp_code, 'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_LANG_CODE',  'value'=>'en', 'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_TABLE_NAME', 'value'=>$DUM_Table, 'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_FIELD_PFIX', 'value'=>$prefix, 'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_STATUS_COL',   'value'=>$txn_status_show, 'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_STATUS_YN',   'value'=>$txn_yn_status,'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_SYS_ID_ATR',   'value'=>$DUM_Sys_Id,'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_SYS_ID',   'value'=>$sys_id,'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_USER_ID',   'value'=>$user_id,'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_REASON_CODE',   'value'=>$reject_code,'type'=>SQLT_CHR, 'length'=>300),
	    array('name'=>':P_REASON_DESC',   'value'=>$comments,'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_STATUS_MSG',   'value'=>&$status_msg, 'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_ERR_NUM',   'value'=>&$return_status, 'type'=>SQLT_CHR, 'length'=>300),
		array('name'=>':P_ERR_MSG',   'value'=>&$error_message, 'type'=>SQLT_CHR, 'length'=>300)
	);
		$this->db->txn_start_now();
		// txn started
		$this->db->stored_procedure('SPINE_APPS','APPS_PROC_TXN_STATUS', $params);
		$result = array("return_status"=>$return_status,"error_message"=>$error_message );
		if($return_status != '0')
	    {
	    	// txn  have error
	    	$result['params'] = $params;
	    	$this->db->txn_have_error();
	    	$this->db->txn_end_now();
	    	echo  json_encode($result);
	    	exit;
		}
		$this->db->txn_end_now();
    	echo  json_encode($result);
    	exit;
    }


	//************************************ APPROVE CONCEPT END ******************************************************************


}
