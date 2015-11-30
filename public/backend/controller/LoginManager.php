<?php

class LoginManager{
	/**
	 * Retrieve all details of the current and removed rateplans on the given user's subscription. The Subscription summary that gets returned will contain a list of Active plans, and removed plans.
	 * @param $accountName Name of the target account
	 * @return Subscription details
	 */

	public static function loginAttempt($username, $partnerLogin){
		session_start();
		session_regenerate_id();
		$_SESSION = array();

		$loginValid = LoginManager::validateUsername($username);

		if($loginValid){
			$loginResult = LoginManager::loginSuccess($username, $partnerLogin);
		} else {
			$loginResult = false;
		}
		return $loginResult;
	}

	// validates that the account is an actual Zuora billing account
	public static function validateUsername($username){
		$zapi;
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			return null;
		}
		//Disallow apostrophes
		if (strpos($username, "'") !== false) {
 		   return false;
		}
		//Get Account ID with this email
		$accResult = $zapi->zQuery("SELECT Id FROM Account WHERE Name='".$username."'");
		foreach($accResult->result->records as $acc){
			$_SESSION['accountId'] = $acc->Id;
			return true;
		}
		return false;
	}

	// resets the session and sets the Username and ID SESSION variable values
	public static function loginSuccess($username, $partnerLogin){
		$_SESSION['email'] = $username;
		$_SESSION['partnerLogin'] = $partnerLogin;
		if ($partnerLogin == 'true'){
			$partnerId = AccountManager::partnerLogin($username);
			$_SESSION['partnerId'] = $partnerId;
			$_SESSION['partnerEmail'] = $username;
		} else {
			$_SESSION['partnerId'] = null;
			$_SESSION['partnerEmail'] = null;
		}
		return true;
	}
}

?>
