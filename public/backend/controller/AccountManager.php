<?php


/**
 * \brief The AccountManager class manages Account information for the logged in user.
 *
 * V1.05
 */

class AccountManager{
	/**
	 * Get account information from the given user
	 * @param $accountName Name of the target account
	 * @return Account Detail model populated with only account level detail, including account name, due balance, last invoice date, last payment date and last payment amount
	 */
	public static function getAccountDetail($accId){
		$zapi;
		$accountDetail = new Summary_Account();
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			$accountDetail->success = false;
			$accountDetail->error = 'INVALID_ZLOGIN';
			return $accountDetail;
		}
		if($accId == NULL){
			$accountDetail->success = false;
			$accountDetail->error = 'USER_DOESNT_EXIST';
			return $accountDetail;
		}
		$accResult = $zapi->zQuery("SELECT Name,Balance,LastInvoiceDate,CreditBalance,Currency,BillCycleDay FROM Account WHERE Id='".$accId."'");

		//Get Account Info
		foreach($accResult->result->records as $acc){
			$accountDetail->accountId = $accId;
			$accountDetail->Name = $acc->Name;
			$accountDetail->Balance = $acc->Balance;
			$accountDetail->CreditBalance = $acc->CreditBalance;
			$accountDetail->Currency = $acc->Currency;
			$accountDetail->BillCycleDay = $acc->BillCycleDay;
			$accountDetail->LastInvoiceDate = isset($acc->LastInvoiceDate) ? $acc->LastInvoiceDate : NULL;

			$paymentResult = $zapi->zQuery("SELECT Amount,EffectiveDate,CreatedDate FROM Payment WHERE AccountId='".$accId."'");
			if($paymentResult->result->size==0){
				$accountDetail->LastPaymentAmount = null;
				$accountDetail->LastPaymentDate = null;
			} else {
				usort($paymentResult->result->records, "AccountManager::cmpPayments");
				$accountDetail->LastPaymentAmount = $paymentResult->result->records[0]->Amount;
				$accountDetail->LastPaymentDate = $paymentResult->result->records[0]->EffectiveDate;
			}
		}

		$accountDetail->success = true;
		return $accountDetail;
	}

	/**
	 * Get contact information from the given user, including address information
	 * @param $accId Name of the target account
	 * @return Contact Detail model populated with a single contact on this account
	 */
	public static function getContactDetail($accId){
		$contactDetail = new Summary_Contact();
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			$contactDetail->success = false;
			$contactDetail->error = 'INVALID_ZLOGIN';
			return $contactDetail;
		}

		if($accId == NULL){
			$contactDetail->success = false;
			$contactDetail->error = 'USER_DOESNT_EXIST';
			return $contactDetail;
		}

		//Get Contact with this email
		$conResult = $zapi->zQuery("SELECT Id,FirstName,LastName,Address1,Address2,City,State,PostalCode,Country FROM Contact WHERE AccountId='".$accId."'");
		if(count($conResult)==0){
			$contactDetail->success = false;
			$contactDetail->error = 'CONTACT_DOESNT_EXIST';
			return $contactDetail;
		}
		foreach($conResult->result->records as $con){
			$contactDetail->Id = $con->Id;
			$contactDetail->FirstName = $con->FirstName;
			$contactDetail->LastName = $con->LastName;
			$contactDetail->Country = $con->Country;
			$contactDetail->State = isset($con->State) ? $con->State : "";
			$contactDetail->Address1 = isset($con->Address1) ? $con->Address1 : "";
			$contactDetail->Address2 = isset($con->Address2) ? $con->Address2 : "";
			$contactDetail->City = isset($con->City) ? $con->City : "";
			$contactDetail->PostalCode = isset($con->PostalCode) ? $con->PostalCode : "";

			$contactDetail->success = true;
			return $contactDetail;
		}
	}

	/**
	 * Get all payment method information tied to the given user
	 * @param $accountName Name of the target account
	 * @return Account Detail model populated with a list of Payment Method Detail records, including Zuora ID, Card Holder Name, Masked Card Number, Expiration Year, Expiration Month, Card Type, and whether it is currently set as the Account's default payment method
	 */
	public static function getPaymentMethodDetail($accId){
		$zapi;
		$accountDetail = new Summary_Account();
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			$accountDetail->success = false;
			$accountDetail->error = 'INVALID_ZLOGIN';
			return $accountDetail;
		}

		//Get Default Payment Method Id for this account
		$defaultPmId;
		$accResult = $zapi->zQuery("SELECT DefaultPaymentMethodId FROM Account WHERE Id='".$accId."'");

		if($accResult->result->size==0){
			// throw new Exception('SUBSCRIPTION_DOES_NOT_EXIST');
			return 'PAYMENT_METHOD_DOES_NOT_EXIST';
		}
		foreach($accResult->result->records as $acc){
			$defaultPmId = $acc->DefaultPaymentMethodId;
		}

		//Get PaymentMethods with this Account Id
		$pmResult = $zapi->zQuery("	SELECT Id,CreditCardHolderName,CreditCardMaskNumber,CreditCardExpirationYear,CreditCardExpirationMonth,CreditCardType,Type, AchAccountName, AchAccountNumberMask, AchAccountType, AchBankName from PaymentMethod WHERE AccountId='".$accId."'");

		date_default_timezone_set('America/Los_Angeles');
		$todayDate = new DateTime(date('Y-m-d H:i:s'));

		$pmArray = array();
		foreach($pmResult->result->records as $pm){
			$pmDetail = new Summary_PaymentMethod();
			$pmDetail->Id = $pm->Id;
			if ($pm->Type == 'CreditCard') {
				$cardExpirationDate = new DateTime($pm->CreditCardExpirationYear . '-' . $pm->CreditCardExpirationMonth . '-01');
				$daysToExpiration = $cardExpirationDate->diff($todayDate)->days;
				$pmDetail->CardHolderName = htmlentities($pm->CreditCardHolderName);
				$pmDetail->MaskedNumber = $pm->CreditCardMaskNumber;
				$pmDetail->ExpirationYear = $pm->CreditCardExpirationYear;
				$pmDetail->ExpirationMonth = $pm->CreditCardExpirationMonth;
				$pmDetail->CardType = $pm->CreditCardType;
				$pmDetail->isDefault = ($pm->Id==$defaultPmId);
				$pmDetail->expiresSoon = $daysToExpiration <= 60; // check if the card expires with 60 days

				array_push($pmArray, $pmDetail);
			}
		}
		$ACHarray = array();
		foreach($pmResult->result->records as $ach){
			$ACHdetail = new Summary_PaymentMethod();
			$ACHdetail->Id = $ach->Id;
			if ($ach->Type == 'ACH') {
				$ACHdetail->AchAccountName = htmlentities($ach->AchAccountName);
				$ACHdetail->AchAccountNumberMask = $ach->AchAccountNumberMask;
				$ACHdetail->AchAccountType = $ach->AchAccountType;
				$ACHdetail->AchBankName = $ach->AchBankName;
				$ACHdetail->isDefault = ($ach->Id==$defaultPmId);

				array_push($ACHarray, $ACHdetail);
			}
		}

		if (count($pmArray) > 0) {
			$accountDetail->paymentMethodSummaries = $pmArray;
			$accountDetail->hasPaymentMethods = true;
		}

		if (count($ACHarray) > 0) {
			$accountDetail->ACHpaymentMethodSummaries = $ACHarray;
			$accountDetail->hasPaymentMethods = true;
		}

		$accountDetail->success = true;
		return $accountDetail;
	}

	public static function getInvoiceDetail($accId){
		$zapi;
		$accountDetail = new Summary_Account();
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			$accountDetail->success = false;
			$accountDetail->error = 'INVALID_ZLOGIN';
			return $accountDetail;
		}
		if($accId == NULL){
			$accountDetail->success = false;
			$accountDetail->error = 'USER_DOESNT_EXIST';
			return $accountDetail;
		}
		//Get Invoice Data for this account
		$invoiceResult = $zapi->zQuery("SELECT Id, InvoiceNumber, InvoiceDate, DueDate, Amount, AmountWithoutTax, TaxAmount, PaymentAmount, RefundAmount, Balance, Status FROM Invoice WHERE AccountId='".$accId."'");
		if(count($invoiceResult) == 0){
			return 'INVOICES_DONT_EXIST';
		}
		$invoiceContainer = new Invoice_Container();
		$invoiceArray = array();
		usort($invoiceResult->result->records, "AccountManager::cmpInvoices");
		foreach($invoiceResult->result->records as $invoiceRecord){
			$invoiceDetail = new Summary_Invoice();
			$invoiceDetail->Id = $invoiceRecord->Id;
			$invoiceDetail->InvoiceNumber = $invoiceRecord->InvoiceNumber;
			$invoiceDetail->InvoiceDate = $invoiceRecord->InvoiceDate;
			$invoiceDetail->DueDate = $invoiceRecord->DueDate;
			$invoiceDetail->Amount = $invoiceRecord->Amount;
			$invoiceDetail->AmountWithoutTax = $invoiceRecord->AmountWithoutTax;
			$invoiceDetail->TaxAmount = $invoiceRecord->TaxAmount;
			$invoiceDetail->PaymentAmount = $invoiceRecord->PaymentAmount;
			$invoiceDetail->RefundAmount = $invoiceRecord->RefundAmount;
			$invoiceDetail->Balance = $invoiceRecord->Balance;
			$invoiceDetail->FullyPaid = $invoiceRecord->Balance == 0;
			$invoiceDetail->Status = $invoiceRecord->Status;
			array_push($invoiceArray, $invoiceDetail);
		}

		if (count($invoiceArray) > 0) {
			$accountDetail->invoiceRecords = $invoiceArray;
		}

		// End of get invoices section

		$accountDetail->success = true;
		return $accountDetail;
	}

	public static function getBillingPreview($accId){
		$zapi;
		$accountDetail = new Summary_Account();
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			$accountDetail->success = false;
			$accountDetail->error = 'INVALID_ZLOGIN';
			return $accountDetail;
		}
		if($accId == NULL){
			$accountDetail->success = false;
			$accountDetail->error = 'USER_DOESNT_EXIST';
			return $accountDetail;
		}

		date_default_timezone_set('America/Los_Angeles');
		$todayDate = date('Y-m-d\TH:i:s',time());
		$targetDate = strtotime("+6 months", strtotime($todayDate));

		$excludedCharges = 'OneTime,Recurring';

		//Get billingPreview for this account
		$billingPreviewRequest = array(
			"AccountId"=>$accId,
			"ChargeTypeToExclude"=>$excludedCharges,
			"TargetDate"=>$targetDate,
			"IncludingEvergreenSubscription"=>true
		);

		$billingPreviewResult = $zapi->zBillingPreview($billingPreviewRequest);
		$billingPreviewArray = array();
		$TotalBillingPreview = 0;
		if ($billingPreviewResult->results->InvoiceItem){
			foreach($billingPreviewResult->results->InvoiceItem as $billingPreviewRecord){
				if ($billingPreviewRecord->ChargeType == 'Usage' && $billingPreviewRecord->ChargeAmount != "0" && $billingPreviewRecord->Quantity != "0"){
					$billingPreviewDetail = new Summary_Forecast();
					$billingPreviewDetail->Id = $billingPreviewRecord->Id;
					$billingPreviewDetail->ChargeAmount = $billingPreviewRecord->ChargeAmount;
					$billingPreviewDetail->ChargeDate = $billingPreviewRecord->ChargeDate;
					$billingPreviewDetail->ChargeType = $billingPreviewRecord->ChargeType;
					$billingPreviewDetail->Quantity = $billingPreviewRecord->Quantity;
					$billingPreviewDetail->RatePlanChargeId = $billingPreviewRecord->RatePlanChargeId;
					$billingPreviewDetail->ServiceEndDate = $billingPreviewRecord->ServiceEndDate;
					$billingPreviewDetail->ServiceStartDate = $billingPreviewRecord->ServiceStartDate;
					$billingPreviewDetail->SubscriptionId = $billingPreviewRecord->SubscriptionId;
					$billingPreviewDetail->UOM = $billingPreviewRecord->UOM;
					$TotalBillingPreview += $billingPreviewRecord->ChargeAmount;
					array_push($billingPreviewArray, $billingPreviewDetail);
				}
			}
		}

		if (count($billingPreviewArray) > 0) {
			$accountDetail->billingPreviewRecords = $billingPreviewArray;
		}

		$accountDetail->success = true;
		return $accountDetail;
	}

	public static function getUsageDetail($accId){
		$zapi;
		$accountDetail = new Summary_Account();
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			$accountDetail->success = false;
			$accountDetail->error = 'INVALID_ZLOGIN';
			return $accountDetail;
		}
		if($accId == NULL){
			$accountDetail->success = false;
			$accountDetail->error = 'USER_DOESNT_EXIST';
			return $accountDetail;
		}
		$usageResult = $zapi->zQuery("SELECT Id, Description, EndDateTime, Quantity, RbeStatus, StartDateTime,SubscriptionNumber, UOM FROM Usage WHERE AccountId='".$accId."'");
		if(count($usageResult) == 0){
			return 'USAGE_DOESNT_EXIST';
		}
		$usageArray = array();
		usort($usageResult->result->records, "AccountManager::cmpUsage");
		foreach($usageResult->result->records as $useRecord){
			$usageDetail = new Summary_Usage();
			$usageDetail->Id = $useRecord->Id;
			$usageDetail->SubscriptionNumber = $useRecord->SubscriptionNumber;
			$usageDetail->StartDateTime = $useRecord->StartDateTime;
			$usageDetail->EndDateTime = isset($useRecord->EndDateTime) ? $useRecord->EndDateTime : NULL;
			$usageDetail->Quantity = $useRecord->Quantity;
			$usageDetail->RbeStatus = $useRecord->RbeStatus;
			$usageDetail->UOM = $useRecord->UOM;
			$usageDetail->Description = $useRecord->Description;
			array_push($usageArray, $usageDetail);
		}

		if (count($usageArray) > 0) {
			$accountDetail->usageRecords = $usageArray;
		}
		// End usage summary function

		$accountDetail->success = true;
		return $accountDetail;
	}

	/**
	 * Update the given user's information
	 * @param $accountName Name of the target account
	 * @param $firstName user's new first name
	 * @param $lastName user's new last name
	 * @param $address user's new address
	 * @param $city user's new first city
	 * @param $state user's new first state
	 * @param $postalCode user's new postalCode
	 * @param $country user's new country
	 * @return SaveResult
	 */
	public static function updateContact($contactId, $firstName, $lastName, $address, $city, $state, $postalCode, $country){
		$zapi;
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			return 'INVALID_ZLOGIN';
		}

		if($contactId == NULL){
			return 'USER_DOESNT_EXIST';
		}

		//Get Contact with this contactId
		$conResult = $zapi->zQuery("SELECT Id,FirstName,LastName,Country,Address1,City,State,PostalCode FROM Contact WHERE Id='".$contactId."'");
		if(count($conResult)==0){
			return 'CONTACT_DOESNT_EXIST';
		}
		$con = NULL;
		foreach($conResult->result->records as $icon){
			$con = $icon;
		}

		//Create a Contact record with this ID, and all parameters that were passed in.
		$updCon = array(
			"Id"=>$contactId
		);

		if($firstName != NULL && $con->FirstName!=$firstName) $updCon["FirstName"] = $firstName;
		if($lastName != NULL && $con->LastName!=$lastName) $updCon["LastName"] = $lastName;
		if($country != NULL && (!isset($con->Country)  || $con->Country!=$country)) $updCon["Country"] = $country;
		if($address != NULL && (!isset($con->Address1) || $con->Address1!=$address)) $updCon["Address1"] = $address;
		if($postalCode != NULL && (!isset($con->postalCode) || $con->PostalCode!=$postalCode)) $updCon["PostalCode"] = $postalCode;
		if($city != NULL && (!isset($con->city) || $con->City!=$city)) $updCon["City"] = $city;
		if($state != NULL && (!isset($con->state) || $con->State!=$state)) $updCon["State"] = $state;

		$updCons = array($updCon);

		$updRes = $zapi->zCreateUpdate('update',$updCons,'Contact');

		return $updRes->result;
	}

	/**
	 * Determines whether there is already a Contact in Zuora with the given email address
	 * @param $targetEmail
	 * @return true if unique, false if not unique
	 */
	public static function checkEmailAvailability($targetEmail){
		$zapi;
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			return null;
		}

		//Disallow apostrophes
		if (strpos($targetEmail, "'") !== false) {
 		   return false;
		}

		//Get Contact with this email
		$accResult = $zapi->zQuery("SELECT Id FROM Account WHERE Name='".$targetEmail."'");

		foreach($accResult->result->records as $acc){
			return false;
		}
		return true;
	}

	/**
	 * Comparator to sort payments by effective date
	 */
	private static function cmpPayments($a, $b)
	{
		if ($a->CreatedDate == $b->CreatedDate) {
			return 0;
		}
		return ($a->CreatedDate > $b->CreatedDate) ? -1 : 1;
	}
	/**
	 * Comparator to sort invoices by invoice date
	 */
	private static function cmpInvoices($a, $b)
	{
		if ($a->InvoiceDate == $b->InvoiceDate) {
			return 0;
		}
		return ($a->InvoiceDate > $b->InvoiceDate) ? -1 : 1;
	}

	/**
	 * Comparator to sort usage records by start date
	 */
	private static function cmpUsage($a, $b)
	{
		if ($a->StartDateTime == $b->StartDateTime) {
			return 0;
		}
		return ($a->StartDateTime > $b->StartDateTime) ? -1 : 1;
	}

	// start of partner login
	public static function partnerLogin($targetEmail){
		$zapi;
		//echo 'targetEmail: '.$targetEmail;
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			return false;
		}

		//Disallow apostrophes
		if (strpos($targetEmail, "'") !== false) {
		   return false;
		}
		//echo 'targetEmail: '.$targetEmail;
		//Get Contact with this email
		$accResult = $zapi->zQuery("SELECT Id FROM Account WHERE Name='".$targetEmail."'");

		foreach($accResult->result->records as $acc){
			return $acc->Id;
		}
		return false;
	}
	// partner login end

	// start of get hierarchy function
	public static function buildHierarchy($partnerID, $parentName, $parentId, $acctName, $acctId, $parentHierarchy){
		$zapi;
		try{
			$zapi = new zApi();
		} catch(Exception $e){
			return false;
		}

		$chldHierarchy = new Summary_Hierarchy();//$parentName, $parentID);
		$chldHierarchy->partnerId = $partnerID;
		$chldHierarchy->ParentName = $parentName;
		$chldHierarchy->ParentId = $parentId;
		$chldHierarchy->accountId = $acctId;
		$chldHierarchy->acctName = $acctName;
		$chldHierarchy->sub_Hierarchies = array();
		$hierarchy = $chldHierarchy;
		$acctResult = $zapi->zQuery("Select Id, Name from Account where ParentId = '".$acctId."'");

		foreach($acctResult->result->records as $acc) {
			AccountManager::buildHierarchy($partnerID, $acctName, $acctId, $acc->Name, $acc->Id, $hierarchy);
		}

		if ($parentHierarchy != 'null') {
			array_push($parentHierarchy->sub_Hierarchies, $hierarchy);
		}

		return $hierarchy;
	}

	// begin show account hierarchy view
	public static function displayAccountView($email, $accountId, $parentId){
		$_SESSION['email'] 	= $email;
		$_SESSION['parentId'] = $parentId;
		$_SESSION['accountId'] = $accountId;

		if ($parentId == 'null') {
			$_SESSION['parentId'] = $_SESSION['partnerId'];
		}
		// header('Location: ../account_view.html');
	}

}

?>
