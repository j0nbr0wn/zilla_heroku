<?php
/**
 * \brief The SubscriptionManager class contains methods to create and and view details of the logged in user's Subscription.
 *
 * V1.05
 */

class SubscriptionManager{
	/**
	 * Retrieve all details of the current and removed rateplans on the given user's subscription. The Subscription summary that gets returned will contain a list of Active plans, and removed plans.
	 * @param $accountName Name of the target account
	 * @return Subscription details
	 */

public static function getCurrentSubscriptions($accountId){
		$zapi = new zApi();

		if($accountId==null){
			// throw new Exception('ACCOUNT_DOES_NOT_EXIST');
			return 'ACCOUNT_DOES_NOT_EXIST';
		}

		$subResult = $zapi->zQuery("SELECT Id,Name,Status,Version,PreviousSubscriptionId,ContractEffectiveDate,TermStartDate,TermEndDate,TermType,RenewalTerm FROM Subscription WHERE AccountId='".$accountId."' AND Status='Active'");

		if($subResult->result->size==0){
			// throw new Exception('SUBSCRIPTION_DOES_NOT_EXIST');
			return 'SUBSCRIPTION_DOES_NOT_EXIST';
		}

		date_default_timezone_set('America/Los_Angeles');
		$curDate = date("Y-m-d") .'T00:00:00.000-08:00';

		//Array for Active Subscriptions
		$activeSubArray = array();
		foreach($subResult->result->records as $sub){
			$activeSub = new Amender_Subscription();
			$activeSub->subId = $sub->Id;
			$activeSub->Name = $sub->Name;
			$activeSub->Version = $sub->Version;
			$activeSub->userEmail = $accountName;
			$activeSub->termType = $sub->TermType;
			$activeSub->RenewalTerm = $sub->RenewalTerm;
			$activeSub->invoicedThroughDate = date('Y-m-d\TH:i:s',time());
			$activeSub->startDate = $sub->TermStartDate;
			$activeSub->endDate = $sub->TermEndDate;
			if ($activeSub->termType == "TERMED") {
				$activeSub->subExpired = $curDate > $sub->TermEndDate;
			} else {
				$activeSub->subExpired = false;
			}
			//Get Existing Rate Plans
			$activeSub->active_plans = array();
			$activeSub->removed_plans = array();
			$rpResult = $zapi->zQuery("SELECT Id,Name,ProductRatePlanId FROM RatePlan WHERE SubscriptionId='".$activeSub->subId."'");
			// Get all rate plans
			foreach($rpResult->result->records as $rp){
				$newPlan = new Amender_Plan();
				$newPlan->Id = $rp->Id;
				$newPlan->Name = $rp->Name;
				//Get Product Name
				$prpResult = $zapi->zQuery("SELECT Description,ProductId FROM ProductRatePlan WHERE Id='".$rp->ProductRatePlanId."'");
				$newPlan->Description = (isset($prpResult->result->records[0]->Description) ? $prpResult->result->records[0]->Description : '');
				$newPlan->ProdId = $prpResult->result->records[0]->ProductId;
				$pResult = $zapi->zQuery("SELECT Name, UpgradePathSKU__c, DowngradePathSKU__c FROM Product WHERE Id='".$prpResult->result->records[0]->ProductId."'");
				$newPlan->ProductName = $pResult->result->records[0]->Name;
				$newPlan->UpgradePathSKU__c = $pResult->result->records[0]->UpgradePathSKU__c;
				$newPlan->DowngradePathSKU__c = $pResult->result->records[0]->DowngradePathSKU__c;

				//Get all charges
				$newPlan->amender_charges = array();
				$rpcResult = $zapi->zQuery("SELECT Id,Name,ProductRatePlanChargeId,ChargeModel,ChargeType,UOM,Quantity,ChargedThroughDate FROM RatePlanCharge WHERE IsLastSegment=true AND RatePlanId='".$rp->Id."'");
				foreach($rpcResult->result->records as $rpc){

					$newCharge = new Amender_Charge();
					$newCharge->Id = $rpc->Id;
					$newCharge->Name = $rpc->Name;
					$newCharge->ChargeModel = $rpc->ChargeModel;
					$newCharge->ChargeType = $rpc->ChargeType;
					$newCharge->amendableQty = ($newCharge->ChargeType == "Recurring" && ($newCharge->ChargeModel=='Per Unit Pricing' || $newCharge->ChargeModel=='Tiered Pricing' || $newCharge->ChargeModel=='Volume Pricing'));
					$newCharge->Quantity = $rpc->Quantity;
					$newCharge->Uom = $rpc->UOM;
					$newCharge->ChargedThroughDate = $rpc->ChargedThroughDate;
					$newCharge->adjustableQty = ($rpc->Quantity && $rpc->UOM);
					// $newCharge->Price = $rpc->Price; // Had to remove "Price" from the query bc ONLY FlatFee, PerUnit & Overage charge models are returned
					$newCharge->ProductRatePlanChargeId = $rpc->ProductRatePlanChargeId;
					// if($rpc->ChargeModel!='Flat Fee Pricing'){
					// 	$newPlan->uom = $rpc->UOM;
					// 	$newPlan->quantity = $rpc->Quantity;
					// 	$newCharge->Uom = $rpc->UOM;
					// 	$newCharge->Quantity = $rpc->Quantity;
					// }
					//For all charges, find maximum ChargedThroughDate
					if(isset($rpc->ChargedThroughDate)){
						if($rpc->ChargedThroughDate > $activeSub->invoicedThroughDate){
							$activeSub->invoicedThroughDate = $rpc->ChargedThroughDate;
						}
					}
					array_push($newPlan->amender_charges, $newCharge);
				}
				array_push($activeSub->active_plans, $newPlan);
			}
			//Get Removed Rate Plans
			$rpResult = $zapi->zQuery("SELECT Id,Name,AmendmentType,AmendmentId,ProductRatePlanId FROM RatePlan WHERE SubscriptionId='".$activeSub->subId."' AND AmendmentType='RemoveProduct'");
			foreach($rpResult->result->records as $rp){
				$newPlan = new Amender_Plan();
				$newPlan->Id = $rp->Id;
				$newPlan->Name = $rp->Name;

				//Get Product Name
				$prpResult = $zapi->zQuery("SELECT Description,ProductId FROM ProductRatePlan WHERE Id='".$rp->ProductRatePlanId."'");
				$newPlan->Description = (isset($prpResult->result->records[0]->Description) ? $prpResult->result->records[0]->Description : '');
				$pResult = $zapi->zQuery("SELECT Name FROM Product WHERE Id='".$prpResult->result->records[0]->ProductId."'");
				$newPlan->ProductName = $pResult->result->records[0]->Name;

				$newPlan->AmendmentId = $rp->AmendmentId;
				$newPlan->AmendmentType = $rp->AmendmentType;
				$newPlan->effectiveDate = 'end of current billing period.';

				//Query Amendment for this rate plan to get Effective Removal Date
				$amdResult = $zapi->zQuery("SELECT Id,ContractEffectiveDate FROM Amendment WHERE Id='".$newPlan->AmendmentId."'");
				foreach($amdResult->result->records as $amd){
					$newPlan->effectiveDate = $amd->ContractEffectiveDate;
				}

				//Get all charges
				$newPlan->amender_charges = array();
				$rpcResult = $zapi->zQuery("SELECT Id,Name,ProductRatePlanChargeId,ChargeModel,ChargeType,UOM,Quantity,ChargedThroughDate FROM RatePlanCharge WHERE RatePlanId='".$rp->Id."'");
				foreach($rpcResult->result->records as $rpc){
					$newCharge = new Amender_Charge();
					$newCharge->Id = $rpc->Id;
					$newCharge->Name = $rpc->Name;
					$newCharge->ChargeModel = $rpc->ChargeModel;
					$newCharge->ProductRatePlanChargeId = $rpc->ProductRatePlanChargeId;
					if(isset($rpc->UOM)){
						$rpc->Quantity=1;
						$newPlan->uom = $rpc->UOM;
						$newPlan->quantity = $rpc->Quantity;
						$newCharge->Uom = $rpc->UOM;
						$newCharge->Quantity = $rpc->Quantity;
					}
					//For all charges, find maximum ChargedThroughDate
					if(isset($rpc->ChargedThroughDate)){
						if($rpc->ChargedThroughDate > $activeSub->invoicedThroughDate){
							$activeSub->invoicedThroughDate = $rpc->ChargedThroughDate;
						}
					}
					array_push($newPlan->amender_charges, $newCharge);
				}
				array_push($activeSub->removed_plans, $newPlan);
			}
			array_push($activeSubArray, $activeSub);
		}
	usort($activeSubArray, "SubscriptionManager::cmpSubscriptions");
	return $activeSubArray;
	}

	public static function getAmendSubscription($currentSubId){
		$zapi = new zApi();

		$subResult = $zapi->zQuery("SELECT Id,Name,Status,Version,PreviousSubscriptionId,ContractEffectiveDate,TermStartDate,TermEndDate,TermType,RenewalTerm FROM Subscription WHERE Id='".$currentSubId."'");

		if($subResult->result->size==0){
			// throw new Exception('SUBSCRIPTION_DOES_NOT_EXIST');
			return 'SUBSCRIPTION_DOES_NOT_EXIST';
		}

		date_default_timezone_set('America/Los_Angeles');
		$curDate = date("Y-m-d") .'T00:00:00.000-08:00';

		//Array for Active Subscriptions
		$activeSubArray = array();
		foreach($subResult->result->records as $sub){
			$activeSub = new Amender_Subscription();
			$activeSub->subId = $sub->Id;
			$activeSub->Name = $sub->Name;
			$activeSub->startDate = $sub->TermStartDate;
			$activeSub->endDate = $sub->TermEndDate;
			$activeSub->active_plans = array();
			$activeSub->removed_plans = array();
			$rpResult = $zapi->zQuery("SELECT Id,Name,ProductRatePlanId FROM RatePlan WHERE SubscriptionId='".$activeSub->subId."'");
			// Get all rate plans
			foreach($rpResult->result->records as $rp){
				$newPlan = new Amender_Plan();
				$newPlan->Id = $rp->Id;
				$newPlan->Name = $rp->Name;
				//Get Product Name
				$prpResult = $zapi->zQuery("SELECT Description,ProductId FROM ProductRatePlan WHERE Id='".$rp->ProductRatePlanId."'");
				$newPlan->Description = (isset($prpResult->result->records[0]->Description) ? $prpResult->result->records[0]->Description : '');
				$newPlan->ProdId = $prpResult->result->records[0]->ProductId;
				$pResult = $zapi->zQuery("SELECT Name, UpgradePathSKU__c, DowngradePathSKU__c FROM Product WHERE Id='".$prpResult->result->records[0]->ProductId."'");
				$newPlan->ProductName = $pResult->result->records[0]->Name;

				array_push($activeSub->active_plans, $newPlan);
			}
			//Get Removed Rate Plans
			$rpResult = $zapi->zQuery("SELECT Id,Name,AmendmentType,AmendmentId,ProductRatePlanId FROM RatePlan WHERE SubscriptionId='".$activeSub->subId."' AND AmendmentType='RemoveProduct'");
			foreach($rpResult->result->records as $rp){
				$newPlan = new Amender_Plan();
				$newPlan->Id = $rp->Id;
				$newPlan->Name = $rp->Name;

				//Get Product Name
				$prpResult = $zapi->zQuery("SELECT Description,ProductId FROM ProductRatePlan WHERE Id='".$rp->ProductRatePlanId."'");
				$newPlan->Description = (isset($prpResult->result->records[0]->Description) ? $prpResult->result->records[0]->Description : '');
				$pResult = $zapi->zQuery("SELECT Name FROM Product WHERE Id='".$prpResult->result->records[0]->ProductId."'");
				$newPlan->ProductName = $pResult->result->records[0]->Name;

				$newPlan->AmendmentId = $rp->AmendmentId;
				$newPlan->AmendmentType = $rp->AmendmentType;
				$newPlan->effectiveDate = 'end of current billing period.';

				//Query Amendment for this rate plan to get Effective Removal Date
				$amdResult = $zapi->zQuery("SELECT Id,ContractEffectiveDate FROM Amendment WHERE Id='".$newPlan->AmendmentId."'");
				foreach($amdResult->result->records as $amd){
					$newPlan->effectiveDate = $amd->ContractEffectiveDate;
				}
				array_push($activeSub->removed_plans, $newPlan);
			}
			array_push($activeSubArray, $activeSub);
		}
	// usort($activeSubArray, "SubscriptionManager::cmpSubscriptions");
	return $activeSubArray;
	}

	// Partner subscribe function begins
	static function partnerSubscribe($subInfo, $cart){
		$zapi = new zApi();

		//$cartUnSer = unserialize($cart);
		/*
		if($cart==null || !isset($cart->cart_items)){
			return 'CART_NOT_INITIALIZED';
		}
		*/

		include("./config.php");

		date_default_timezone_set('America/Los_Angeles');
		$date = date('Y-m-d\TH:i:s',time());
		$today = getdate();
		$mday = $today['mday'];

		//Set up account
		$account = array(
			"Name" => $subInfo['accountName'],
			"Currency" => $defaultCurrency,
			"PaymentTerm" => $subInfo['paymentTerm'],
			"BillCycleDay" => $mday,
			"Batch" => "Batch1",
			"ParentId" => $subInfo['parentId']
		);


		//set up bill to contact
		$contact = array(
			"Address1" => $subInfo['address'],
			"City" => $subInfo['city'],
			"State" => $subInfo['state'],
			"Country" => $subInfo['country'],
			"PostalCode" => $subInfo['postalCode'],
			"FirstName" => $subInfo['firstName'],
			"LastName" => $subInfo['lastName'],
		);

		$SubscribeInvoiceProcessingOptions = array(
			"InvoiceDate"=>date('Y-m-d\TH:i:s',strtotime($subInfo['termStart'])),
			"InvoiceProcessingScope"=>'Subscription',
			"InvoiceTargetDate"=>date('Y-m-d\TH:i:s',strtotime($subInfo['termStart']))
		);

		$subscribeOptions = array(
			"GenerateInvoice"=>true,
			"ProcessPayments"=>false,
			"SubscribeInvoiceProcessingOptions"=>$SubscribeInvoiceProcessingOptions
		);

		//echo 'payment term: ' . $subInfo['paymentTerm'];

		$contractDt = date('Y-m-d\TH:i:s',strtotime($subInfo['contractEffdt']));
		$activationDt = date('Y-m-d\TH:i:s',strtotime($subInfo['activationDt']));
		$acceptanceDt = date('Y-m-d\TH:i:s',strtotime($subInfo['acceptanceDt']));
		$termStart = date('Y-m-d\TH:i:s',strtotime($subInfo['termStart']));

		echo 'invoice owner: ' . $subInfo['invoiceOwner'] . ' ';
		if ($subInfo['invoiceOwner'] == 'null') {
			$subscription = array(
				"ContractEffectiveDate" => $contractDt,
				"ServiceActivationDate" => $activationDt,
				"ContractAcceptanceDate" => $acceptanceDt,
				"TermStartDate" => $termStart,
				"TermType" => $subInfo['term'],
				"Status" => "Active",
				"InitialTerm" => $subInfo['initTerm'],
				"RenewalTerm" => $subInfo['renewTerm']
			);
		} else {
			$subscription = array(
				"ContractEffectiveDate" => $contractDt,
				"ServiceActivationDate" => $activationDt,
				"ContractAcceptanceDate" => $acceptanceDt,
				"TermStartDate" => $termStart,
				"TermType" => $subInfo['term'],
				"Status" => "Active",
				"InitialTerm" => $subInfo['initTerm'],
				"RenewalTerm" => $subInfo['renewTerm'],
				"InvoiceOwnerId" => $subInfo['invoiceOwner']
			);
			echo ' using invoice owner ';
		}

		$ratePlanData = SubscriptionManager::getRatePlanDataFromCart($cart);

		$subscriptionData = array(
			"Subscription" => $subscription,
			"RatePlanData" => $ratePlanData
		);

		$subscribeRequest = array(
			"Account"=>$account,
			"BillToContact"=>$contact,
			"SubscribeOptions"=>$subscribeOptions,
			"SubscriptionData"=>$subscriptionData
		);

		$subResult = $zapi->zSubscribe($subscribeRequest);

		return $subResult;
	}

	// End Partner Subscribe

	/**
	 * Creates a subscription after a user has successfully submitted their payment information. Subscribes using email address as account name, contact information from the created payment method, and rate plan data from the given Cart
	 * @param $userEmail User's given Email address
	 * @param $cart An instance of a Cart object that contains all rate plans and quantities that will be used in this subscription.
	 * @param $pmId PaymentMethod ID that was created in Zuora by the Z-Payments Page
	 * @return SubscribeResult. If the email has already been used in this tenant, returns the error string, 'DUPLICATE_EMAIL', If the Payment Method ID passed doesn't exist, returns the error string, 'INVALID_PMID'
	 */

	static function subscribeWithCart($userEmail, $pmId, $cart){

		$zapi = new zApi();

		if($cart==null || !isset($cart->cart_items)){
			return 'CART_NOT_INITIALIZED';
		}

		$firstName = isset($_SESSION['userFname']) ? $_SESSION['userFname'] : '';
		$lastName = isset($_SESSION['userLname']) ? $_SESSION['userLname'] : '';
		$Address1 = isset($_SESSION['userAddress1']) ? $_SESSION['userAddress1'] : '';
		// $Address2 = isset($pm->CreditCardAddress2) ? $pm->CreditCardAddress2 : '';
		$City = isset($_SESSION['userCity']) ? $_SESSION['userCity'] : '';
		$Country = isset($_SESSION['userCountry']) ? $_SESSION['userCountry'] : '';
		$PostalCode = isset($_SESSION['userPostalCode']) ? $_SESSION['userPostalCode'] : '';
		$State = isset($_SESSION['userState']) ? $_SESSION['userState'] : '';
		$Phone = isset($_SESSION['userPhone']) ? $_SESSION['userPhone'] : '';

		date_default_timezone_set('America/Los_Angeles');
		$date = date('Y-m-d\TH:i:s',time());
		$today = getdate();
		$mday = $today['mday'];

		include("./config.php");

		//Set up account
		$account = array(
			"AutoPay" => $defaultAutopay,
			"Currency" => $defaultCurrency,
			"Name" => $userEmail,
			"PaymentTerm" => $defaultPaymentTerm,
			"Batch" => $defaultBatch,
			"BillCycleDay" => $mday,
			"Status" => "Active"
		);

		if($makeSfdcAccount==true){
			try{
				//Integrate with Salesforce
				$sfdcResponse = sApi::convertSfdcLead();
				if($sfdcResponse->success){
					$account["CrmId"] = $sfdcResponse->accountId;
				}
			} catch (Exception $e){
				error_log('Account '.$userEmail.' could not be created in Salesforce: ' . $e->getMessage());
			}
		}

		//Set up Payment Method
		$pm = array(
			"Id"=> $pmId
		);

		//Set up contact
		$bcontact = array(
			"Address1" => $Address1,
			// "Address2" => $Address2,
			"City" => $City,
			"Country" => $Country,
			"FirstName" => $firstName,
			"LastName" => $lastName,
			"PostalCode" => $PostalCode,
			"State" => $State,
			"WorkEmail" => $userEmail,
			"WorkPhone" => $Phone
		);

		$subscribeOptions = array(
			"GenerateInvoice"=>true,
			"ProcessPayments"=>true,
		);
		$previewOptions = array(
			"EnablePreviewMode"=>false
		);

		//Set up subscription
		$subscription = array(
			"ContractEffectiveDate" => $date,
			"ServiceActivationDate" => $date,
			"ContractAcceptanceDate" => $date,
			"TermStartDate" => $date,
			"TermType" => "TERMED",
			"InitialTerm" => 12,
			"RenewalTerm" => 12,
			"AutoRenew" => true,
			"Status" => "Active",
		);

		$ratePlanData = SubscriptionManager::getRatePlanDataFromCart($cart);

		$subscriptionData = array(
			"Subscription" => $subscription,
			"RatePlanData" => $ratePlanData
		);

		$subscribeRequest = array(
			"Account"=>$account,
			"BillToContact"=>$bcontact,
			"PaymentMethod"=>$pm,
			"SubscribeOptions"=>$subscribeOptions,
			"SubscriptionData"=>$subscriptionData
		);

		$subResult = $zapi->zSubscribe($subscribeRequest);
		$subResult->sfdcResponse = $sfdcResponse;

		return $subResult;
	}

	/**
	 * Creates dummy subscription with given cart, used to determine the value of the first invoice. Error codes are as follows:
	 * 		EMPTY_CART: No items in the cart
	 * 		RATE_PLAN_DOESNT_EXIST: No match was found for a rate plan
	 * 		RATE_PLAN_EXPIRED: Rate Plan is outside of its effective period
	 * @param $cart An instance of a Cart object that contains all rate plans and quantities that will be used in this subscription.
	 * @return Subscribe_Preview Object with fields for invoice success result, invoiceAmount if successful, and error code if unsuccessful.
	 */

	static function previewCart($cart){

		// Need to FIX this to fail gracefully (not hard-code address info)
		// $firstName = isset($_SESSION['userFname']) ? $_SESSION['userFname'] : 'John';
		// $lastName = isset($_SESSION['userLname']) ? $_SESSION['userLname'] : 'Smith';
		// $Address1 = isset($_SESSION['userAddress1']) ? $_SESSION['userAddress1'] : '';
		// // $Address2 = isset($pm->CreditCardAddress2) ? $pm->CreditCardAddress2 : '';
		// $City = isset($_SESSION['userCity']) ? $_SESSION['userCity'] : '';
		// $Country = isset($_SESSION['userCountry']) ? $_SESSION['userCountry'] : 'USA'; // only USA if left blank
		// $PostalCode = isset($_SESSION['userPostalCode']) ? $_SESSION['userPostalCode'] : '';
		// $State = isset($_SESSION['userState']) ? $_SESSION['userState'] : 'CA'; // only CA if left blank
		// $Phone = isset($_SESSION['userPhone']) ? $_SESSION['userPhone'] : '';

		//Initialize Subscribe_Preview model
		$subscribePreview = new Subscribe_Preview();

		//If Cart is empty, return an empty cart message
		if(count($cart->cart_items)==0){
			$subscribePreview->invoiceAmount = 0;
			$subscribePreview->success = false;
			$subscribePreview->error = "EMPTY_CART";
			return $subscribePreview;
		}

		//Preview with SubscribeRequest
		$zapi = new zApi();

		date_default_timezone_set('America/Los_Angeles');
//		$date = date('Y-m-d',time()) . 'T00:00:00';
		$date = date('Y-m-d\T00:00:00',time());

		$today = getdate();
		$mday = $today['mday'];

		include("./config.php");

		//Set up account
		$account = array(
			"AutoPay" => 0,
			"Currency" => $defaultCurrency,
			"Name" => 'TestName',
			"PaymentTerm" => "Net 30",
			"Batch" => "Batch1",
			"BillCycleDay" => $mday,
			"Status" => "Active"
		);
		// FIX this
		//Set up contact
		$bcontact = array(
			"Address1" => $Address1,
			// "Address2" => $Address2,
			"City" => $City,
			"Country" => 'USA',
			"FirstName" => 'John',
			"LastName" => 'Smith',
			"PostalCode" => $PostalCode,
			"State" => 'CA',
			"WorkEmail" => $userEmail,
			"WorkPhone" => $Phone
		);

		$subscribeOptions = array(
			"GenerateInvoice"=>true,
			"ProcessPayments"=>false,
		);
		$previewOptions = array(
			"EnablePreviewMode"=>true,
			// "NumberOfPeriods"=>1
		);

		//Set up subscription
		$subscription = array(
			"ContractEffectiveDate" => $date,
			"ServiceActivationDate" => $date,
			"ContractAcceptanceDate" => $date,
			"TermStartDate" => $date,
			"TermType" => "TERMED",
			"InitialTerm" => 12,
			"RenewalTerm" => 12,
			"AutoRenew" => true,
			"Status" => "Active",
		);

		$ratePlanData = SubscriptionManager::getRatePlanDataFromCart($cart);

		$subscriptionData = array(
			"Subscription" => $subscription,
			"RatePlanData" => $ratePlanData
		);

		$subscribeRequest = array(
			"Account"=>$account,
			"BillToContact"=>$bcontact,
			"SubscribeOptions"=>$subscribeOptions,
			"PreviewOptions"=>$previewOptions,
			"SubscriptionData"=>$subscriptionData
		);

		$subResult = $zapi->zSubscribe($subscribeRequest);

		if($subResult->result->Success==true){
			if(isset($subResult->result->InvoiceData)){
				$subscribePreview->invoiceAmount = $subResult->result->InvoiceData->Invoice->Amount;
				$subscribePreview->success = true;
			} else {
				$subscribePreview->invoiceAmount = number_format((float)0, 2, '.', '');
				$subscribePreview->success = true;
			}
		} else {
			$subscribePreview->success = false;
			if(count($subResult->result->Errors)==1) $subResult->result->Errors = array($subResult->result->Errors);
			$errorResponse = $subResult->result->Errors[0]->Message;
			if(strpos($errorResponse, 'ProductRatePlanId is invalid.')){
				$subscribePreview->error = "RATE_PLAN_DOESNT_EXIST";
			} else if(strpos($errorResponse, 'RatePlan is out of date.')){
				$subscribePreview->error = "RATE_PLAN_EXPIRED";
			} else {
				$subscribePreview->error = $errorResponse;
			}
		}
		return $subscribePreview;
	}

	/**
	 * Assembles RatePlan information by pulling all rate plans in the user's current cart. This rate plan information is formatted in a way that is understood by the SubscribeRequest object.
	 * @param $cart An instance of a Cart object that contains all rate plans and quantities that will be used in this subscription.
	 * @return RatePlanData for subscribe
	 */

	static function getRatePlanDataFromCart($cart){

		$cartItems = $cart->cart_items;

		$ratePlanDatas = array();
		foreach($cartItems as $cartItem){
			$ratePlanData = array("RatePlan" => array("ProductRatePlanId" => $cartItem->ratePlanId));
			$ratePlanChargeData = array();
			// $catalogRp = Catalog::getRatePlan($cartItem->ratePlanId);
			// $charges = $catalogRp->charges;
			$charges = $cartItems[0]->charges;
			foreach($charges as $charge){
				if(($charge->ChargeType!='Usage') && ($charge->ChargeModel=='Per Unit Pricing' || $charge->ChargeModel=='Tiered Pricing' || $charge->ChargeModel=='Volume Pricing')){
					if ($charge->Qty){
						$chargeQty = $charge->Qty;
					} else {
						$chargeQty = 1;
					}
					array_push($ratePlanChargeData, array("RatePlanCharge"=>array("ProductRatePlanChargeId"=> $charge->Id, "Quantity"=>$chargeQty )));
				}
			}
			$ratePlanData["RatePlanChargeData"] = $ratePlanChargeData;
			array_push($ratePlanDatas, $ratePlanData);
		}
		return $ratePlanDatas;
	}

	/**
	 * Comparator to sort subscriptions - expired go last
	 */
	private static function cmpSubscriptions($b, $a)
	{
		if ($a->subExpired == $b->subExpired) {
			return 0;
		}
		return ($a->subExpired > $b->subExpired) ? -1 : 1;
	}
}

?>
