<?php

/**
 * \brief This file contains tests for Controller methods. It can be run from the index.html page.
 *
 * V1.05
 */


function __autoload($class){
  @include('./model/' . $class . '.php');
  @include('./controller/' . $class . '.php');
  @include('./sfdc/' . $class . '.php');
}

$NEW_PAYMENT_METHOD = "77777777777777777777777777777777"; // Needed for a successful response from SubscribeWithHpm. Populate with a PaymentMethodId generated by Hpm, but not yet attached to an account.
$EXISTING_CUSTOMER_ACCOUNT_NAME = "eric.neto@zuora.com"; // Needed for a successful response from calls that refer to an existing user (Get Account Summary, Update Contact Info, etc.)'.

$phpInfo=					false;

$testzApi=					false;
$testCatalog=				false;
$testCart=					false;
$testSubscriptionManager=	false;
$testAmender=				false;
$testAccountManager=		false;
$testPaymentManager=		false;
$testInvoiceManager=		false;
$testsApi=					false;

$testzApi = 				isset($_GET['zApi']) ? 					$_GET['zApi'] : false;
$testCatalog = 				isset($_GET['Catalog']) ? 				$_GET['Catalog'] : false;
$testCart = 				isset($_GET['Cart']) ? 					$_GET['Cart'] : false;
$testSubscriptionManager = 	isset($_GET['SubscriptionManager']) ? 	$_GET['SubscriptionManager'] : false;
$testAccountManager = 		isset($_GET['AccountManager']) ? 		$_GET['AccountManager'] : false;
$testPaymentManager = 		isset($_GET['PaymentManager']) ? 		$_GET['PaymentManager'] : false;
$testInvoiceManager = 		isset($_GET['InvoiceManager']) ? 		$_GET['InvoiceManager'] : false;
$testAmender = 				isset($_GET['Amender']) ? 				$_GET['Amender'] : false;
$testsApi = 				isset($_GET['sApi']) ? 					$_GET['sApi'] : false;

if(isset($_GET['existingAccount']))
	$EXISTING_CUSTOMER_ACCOUNT_NAME = $_GET['existingAccount'];


$testNum = 1;
$zapi;

$startTime;

function printResultStart($func){
	global $testNum, $startTime;

	echo "<font color='blue'>=============<br>";
	echo "<b>TEST " . $testNum . "</b><br>";
	echo $func . "<br>";
	echo "-------------<br></font>";
	$testNum++;
	$startTime = microtime(true);

}
function printResultEnd($messages){
	global $startTime;

	$elapsedTime = microtime(true) - $startTime;

	if(count($messages)>0){
		echo "Messages: <br><font color='red'>";
		foreach($messages as $message)
			echo $message . "<br>";
		echo "</font>-------------<br>";
	} else {
		echo "<font color='green'>No Errors</font><br>";
	}

	echo "<font color='blue'>Elapsed Time: ".($elapsedTime)."<br>";
	echo "=============<br>";
	echo "<br></font>";
}


//Tests zApi Login
function test_zApi_Login(){
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	try{
		$zapi = new zApi();
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}
	//Report
	printResultEnd($messages);
}

//Tests zApi Query
function test_zApi_Query(){
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	$queryResult ;
	try{
		$zapi = new zApi();
		$queryResult = $zapi->zQuery("SELECT Name FROM Account");
	} catch (Exception $e){
		array_push($messages, "Query Exception.");
		array_push($messages, "Exception: " . $e->getMessage());
	}
	print_r_html($queryResult);

	//Report
	printResultEnd($messages);
}

//Tests zApi Create
function test_zApi_Create(){
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	try{
		$zapi = new zApi();
		$newProduct1 = array(
			"Name"=>"TestProduct1",
			"EffectiveStartDate"=>"2000-01-01T00:00:00.000-08:00",
			"EffectiveEndDate"=>"3000-01-01T00:00:00.000-08:00"
		);
		$newProduct2 = array(
			"Name"=>"TestProduct2",
			"EffectiveStartDate"=>"2000-01-01T00:00:00.000-08:00",
			"EffectiveEndDate"=>"3000-01-01T00:00:00.000-08:00"
		);
		$objs = array($newProduct1, $newProduct2);

		$createResult = $zapi->zCreateUpdate('create',$objs,'Product');

		print_r_html($createResult);

	} catch (Exception $e){
		array_push($messages, "Create Exception.");
		array_push($messages, "Exception: " . $e->getMessage());
	}

	//Report
	printResultEnd($messages);
}

//Tests zApi Delete
function test_zApi_Delete(){
	printResultStart(__FUNCTION__);
	$messages = array();

	try{
		$zapi = new zApi();

		//Get Product Id
		$pIds = array();
		$queryResult = $zapi->zQuery("Select Id From Product Where Name='TestProduct1' or Name='TestProduct2'");
		foreach($queryResult->result->records as $record){
			array_push($pIds, $record->Id);
		}
		array_push($pIds, '555555');

		//Delete
		$deleteResult = $zapi->zDelete($pIds,'Product');

		print_r_html($deleteResult);
	} catch (Exception $e){
		array_push($messages, "Delete Exception.");
		array_push($messages, "Exception: " . $e->getMessage());
	}

	//Report
	printResultEnd($messages);
}

//Tests zApi Subscribe
function test_zApi_Subscribe(){
	printResultStart(__FUNCTION__);
	$messages = array();

	try{
		$zapi = new zApi();

		$date = date('Y-m-d\TH:i:s',time());
		$today = getdate();
		$mday = $today['mday'];

		//Set up account
		$account = array(
			"AutoPay" => 0,
			"Currency" => "USD",
			"Name" => "TestSubscriber",
			"PaymentTerm" => "Net 30",
			"Batch" => "Batch1",
			"BillCycleDay" => $mday,
			"Status" => "Active"
		);

		//Set up contact
		$bcontact = array(
			"Address1" => "123 Main",
			"Address2" => "Apt 1",
			"City" => "Santa Clara",
			"Country" => "United States",
			"FirstName" => "John",
			"LastName" => "Smith",
			"PostalCode" => "99999",
			"State" => "California",
			"WorkEmail" => "test@zuora.com",
			"WorkPhone" => "555-555-5555"
		);
		$scontact = array(
			"Address1" => "123 Main",
			"Address2" => "Apt 1",
			"City" => "Santa Clara",
			"Country" => "United States",
			"FirstName" => "John",
			"LastName" => "Smith",
			"PostalCode" => "99999",
			"State" => "California",
			"WorkEmail" => "test@zuora.com",
			"WorkPhone" => "555-555-5555"
		);

		$subscribeOptions = array(
			"GenerateInvoice"=>false,
			"ProcessPayments"=>false,
		);
		$previewOptions = array(
			"EnablePreviewMode"=>false
		);

		//Set up subscription
		$subscription = array(
			"AutoRenew" => 0,
			"InitialTerm" => 12,
			"RenewalTerm" => 12,
			"ContractEffectiveDate" => $date,
			"ServiceActivationDate" => $date,
			"ContractAcceptanceDate" => $date,
			"TermStartDate" => $date,
			"TermType" => "TERMED",
			"Status" => "Active",
		);

		$ratePlanData = array(
			array("RatePlan" => array("ProductRatePlanId" => "4028e69737d27a3f0137f11e73b307c7")),
		);

		$subscriptionData = array(
			"Subscription" => $subscription,
			"RatePlanData" => $ratePlanData
		);

		$subscribeRequest = array(
			"Account"=>$account,
			"BillToContact"=>$bcontact,
			"SoldToContact"=>$scontact,
			"SubscribeOptions"=>$subscribeOptions,
			"SubscriptionData"=>$subscriptionData
		);

		print_r_html($subscribeRequest);

		//Subscribe
		$subResult = $zapi->zSubscribe($subscribeRequest);

		print_r_html($subResult);


		echo "Deleting subscription.<br>";
		$deleteResult = $zapi->zDelete(array($subResult->result->AccountId),'Account');
		print_r_html($deleteResult);
	} catch (Exception $e){
		array_push($messages, "Test Exception: " . $e->getMessage());
	}

	//Report
	printResultEnd($messages);
}


//Tests zApi Amend
function test_zApi_Amend(){
	printResultStart(__FUNCTION__);
	$messages = array();

	$zapi = new zApi();
	$date = date('Y-m-d\TH:i:s');
	$amendment = array(
		'EffectiveDate' => $date,
		'Name' => 'addproduct' . time(),
		'Description' => 'AmendmentTest',
		'Status' => 'Completed',
		'SubscriptionId' => '4028e69636e2c6590136eae027bf39f7',
		'Type' => 'NewProduct',
		'ContractEffectiveDate' => $date,
		'EffectiveDate' => $date,
		'RatePlanData' =>array(
			'RatePlan' =>array(
				'ProductRatePlanId'=>'4028e69736acda1e0136e100fda71c72'
			)
		)
	);
	$amendOptions = array(
		"GenerateInvoice"=>false,
		"ProcessPayments"=>false,
	);
	$previewOptions = array(
		"EnablePreviewMode"=>false
	);

	//Amend
	try{
		$amendResult = $zapi->zAmend($amendment,$amendOptions, $previewOptions);

		print_r_html($amendResult);
	} catch (Exception $e){
		array_push($messages, "Test Exception: " . $e->getMessage());
	}

	//Report
	printResultEnd($messages);
}


//Tests Catalog refresh
function test_Catalog_Refresh(){
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	$refreshResult;
	try{
		$refreshResult = Catalog::refreshCache();
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}
	echo "Refreshed Catalog from Zuora:<br>";
	echo htmlentities(json_encode($refreshResult)) . "<br>";


	//Report
	printResultEnd($messages);
}
//Tests Catalog read
function test_Catalog_Read(){
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	$readResult;
	try{
		$readResult = Catalog::readCache();
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}
	echo "Read Catalog from Cache:<br>";
	echo htmlentities(json_encode($readResult)) . "<br>";

	//Report
	printResultEnd($messages);
}


//Tests Cart Methods
function test_Cart(){
	printResultStart(__FUNCTION__);
	$messages = array();

	//Retrieve a productRatePlanId from the catalog
	$prpid;
	try{
		$catalog = Catalog::readCache();
		$prp = $catalog[0]->products[0]->ratePlans[0];
		echo "Using Product Rate Plan '" . $prp->Name . "' on Product '".$prp->productName."' <br>";
		$prpid = $prp->Id;
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
		printResultEnd($messages);
		return;
	}

	//Create a new cart
	$newCart = new Cart();
	echo "Empty cart: <br>";
	print_r_html($newCart);

	//Adding items to new cart
	echo "Adding to cart: <br>";
	$newCart->addCartItem($prpid, null);
	$newCart->addCartItem($prpid, null);
	print_r_html($newCart);

	//Removing first item from new cart
	echo "Removing from cart: <br>";
	$removed = $newCart->removeCartItem('1');
	print_r_html($newCart);


	//Emptying cart
	echo "Emptying cart: <br>";
	$newCart->clearCart();
	print_r_html($newCart);

	//Report
	printResultEnd($messages);
}

//Tests the SubscriptionManager - Get Entire Sub.
function test_SubscriptionManager_getCurrentSubscriptions(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	$test_subs;
	try{
		//Get Active Rate Plans
		$sub = SubscriptionManager::getCurrentSubscriptions($EXISTING_CUSTOMER_ACCOUNT_NAME);

		//Report
		print_r_html($sub);
	} catch (Exception $e){
		array_push($messages, "Exception: " . $e->getMessage());
	}

	//Report
	printResultEnd($messages);
}

//Subscribe With CurrentCart.
function test_SubscriptionManager_subscribeWithCurrentCart(){
	global $NEW_PAYMENT_METHOD;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Retrieve a productRatePlanId from the catalog
	$prpid;
	try{
		$catalog = Catalog::readCache();
		$prp = $catalog[0]->products[0]->ratePlans[0];
		echo "Using Product Rate Plan '" . $prp->Name . "' on Product '".$prp->productName."' <br>";
		$prpid = $prp->Id;
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
		printResultEnd($messages);
		return;
	}

	//Create a cart with one item
	$newCart = new Cart();
	$newCart->addCartItem($prpid, null);

	//Test
	try{
		//Get Active Rate Plans
		//Pass in a unique username and a Payment method that has been created but NOT attached to an account.
		$subRes = SubscriptionManager::subscribeWithCart(time().'@zillatest.com', $NEW_PAYMENT_METHOD, $newCart);

		//Report
		print_r_html($subRes);
		echo "Note: A successful response from this test requires an unattached Payment Method ID generated through HPM.<br>";
	} catch (Exception $e){
		array_push($messages, "Exception: " . $e->getMessage());
	}

	//Report
	printResultEnd($messages);
}

//Preview Current Cart.
function test_SubscriptionManager_previewCurrentCart(){
	printResultStart(__FUNCTION__);
	$messages = array();

	//Retrieve a productRatePlanId from the catalog
	$prpid;
	try{
		$catalog = Catalog::readCache();
		$prp = $catalog[0]->products[0]->ratePlans[0];
		echo "Using Product Rate Plan '" . $prp->Name . "' on Product '".$prp->productName."' <br>";
		$prpid = $prp->Id;
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
		printResultEnd($messages);
		return;
	}

	//Create a cart with one item
	$newCart = new Cart();
	$newCart->addCartItem($prpid, null);

	//Test
	try{
		//Get Active Rate Plans
		$subRes = SubscriptionManager::previewCart($newCart);

		//Report
		print_r_html($subRes);
	} catch (Exception $e){
		array_push($messages, "Exception: " . $e->getMessage());
	}

	//Report
	printResultEnd($messages);
}

function test_Amender_addRatePlan(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Retrieve a productRatePlanId from the catalog
	$prpId;
	try{
		$catalog = Catalog::readCache();
		$prp = $catalog[0]->products[0]->ratePlans[0];
		echo "Previewing AddProduct amendment for customer '".$EXISTING_CUSTOMER_ACCOUNT_NAME."' with Product Rate Plan '" . $prp->Name . "' on Product '".$prp->productName."' <br>";
		$prpId = $prp->Id;
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
		printResultEnd($messages);
		return;
	}


	//Test
	try{
		$amRes = Amender::addRatePlan($EXISTING_CUSTOMER_ACCOUNT_NAME, $prpId,null,true);
		print_r_html($amRes);
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}

	printResultEnd($messages);
}

function test_Amender_removeRatePlan(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	try{
		//Get the Rateplan ID of one of the active plans on this user's subscription
		$sub = SubscriptionManager::getCurrentSubscriptions($EXISTING_CUSTOMER_ACCOUNT_NAME);
		$rpId = $sub->active_plans[0]->Id;
		echo "Removing '".$sub->active_plans[0]->ProductName." : ".$sub->active_plans[0]->Name."' from subscription on account '".$EXISTING_CUSTOMER_ACCOUNT_NAME."'<br>";

		//Test
		$amRes = Amender::removeRatePlan($EXISTING_CUSTOMER_ACCOUNT_NAME, $rpId,false);

		print_r_html($amRes);

		if($amRes->results->Success){
			echo "<br>RemoveProduct amendment successfully created.<br>";

			$zapi = new zApi();
			echo "Deleting created amendment.<br>";
			$deleteResult = $zapi->zDelete(array($amRes->results->AmendmentIds),'Amendment');
			print_r_html($deleteResult);

		}
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}

	printResultEnd($messages);
}

function test_Amender_updateRatePlan(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	try{
		//Get the Rateplan ID of an updatable active plan on this user's subscription
		$sub = SubscriptionManager::getCurrentSubscriptions($EXISTING_CUSTOMER_ACCOUNT_NAME);
		$rpId = null;
		foreach($sub->active_plans as $rp){
			if($rp->uom!=null){
				$rpId = $rp->Id;
			}
		}
		if($rpId==null){
			echo "This must have an updatable rate plan to run this test.";
			printResultEnd($messages);
			return;
		}
		echo "Updating quantity of Rate Plan '".$rpId."' on account '".$EXISTING_CUSTOMER_ACCOUNT_NAME."'<br>";

		//Test
		$amRes = Amender::updateRatePlan($EXISTING_CUSTOMER_ACCOUNT_NAME, $rpId, 4, true);

		print_r_html($amRes);

	/*
		if($amRes->results->Success){
			echo "<br>RemoveProduct amendment successfully created.<br>";

			$zapi = new zApi();
			echo "Deleting created amendment.<br>";
			$deleteResult = $zapi->zDelete(array($amRes->results->AmendmentIds),'Amendment');
			print_r_html($deleteResult);

		}*/
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}

	printResultEnd($messages);
}


function test_GetAccountSummary(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	echo "Getting account summary for user " . $EXISTING_CUSTOMER_ACCOUNT_NAME . "<br>";
	try{
		$sumRes = AccountManager::getAccountDetail($EXISTING_CUSTOMER_ACCOUNT_NAME);
	} catch (Exception $e){
		array_push($messages, "Exception: " . $e->getMessage());
	}

	print_r_html($sumRes);

	printResultEnd($messages);
}

function test_GetContactSummary(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	echo "Getting contact summary for user " . $EXISTING_CUSTOMER_ACCOUNT_NAME . "<br>";
	try{
		$sumRes = AccountManager::getContactDetail($EXISTING_CUSTOMER_ACCOUNT_NAME);
	} catch (Exception $e){
		array_push($messages, "Exception: " . $e->getMessage());
	}

	print_r_html($sumRes);

	printResultEnd($messages);
}

function test_GetPaymentMethodSummary(){

	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	echo "Getting paymentMethod summary for user " . $EXISTING_CUSTOMER_ACCOUNT_NAME . "<br>";
	try{
		$sumRes = AccountManager::getPaymentMethodDetail($EXISTING_CUSTOMER_ACCOUNT_NAME);
	} catch (Exception $e){
		array_push($messages, "Exception: " . $e->getMessage());
	}

	print_r_html($sumRes);

	printResultEnd($messages);
}

function test_GetCompleteSummary(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	echo "Getting complete summary for user " . $EXISTING_CUSTOMER_ACCOUNT_NAME . "<br>";
	try{
		$sumRes = AccountManager::getCompleteDetail($EXISTING_CUSTOMER_ACCOUNT_NAME);
	} catch (Exception $e){
		array_push($messages, "Exception: " . $e->getMessage());
	}

	print_r_html($sumRes);

	printResultEnd($messages);
}
function test_UpdateContact(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	try{
		echo "Testing valid contact update (Updating postal code to current time - ".time()."): <br>";
		$updRes = AccountManager::updateContact($EXISTING_CUSTOMER_ACCOUNT_NAME, null, null, null, null, null, time(), null);
		print_r_html($updRes);

		echo "Testing invalid contact update (Non-existant US State): <br>";
		$updRes = AccountManager::updateContact($EXISTING_CUSTOMER_ACCOUNT_NAME, null, null, null, null, '"Tokyo"', null, 'United States');
		print_r_html($updRes);
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}

	printResultEnd($messages);
}

function test_CheckEmailAvailability(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	try{
		echo "Checking for an existing Account (".$EXISTING_CUSTOMER_ACCOUNT_NAME."): <br>";
		$check1 = AccountManager::checkEmailAvailability($EXISTING_CUSTOMER_ACCOUNT_NAME);
		echo $check1 ? "Name is available." : "Name is unavailable.";
		echo "<br>";

		echo "Checking for a non-existing Account (".time()."@zuora.com): <br>";
		$check2 = AccountManager::checkEmailAvailability(time()."@zuora.com");
		echo $check2 ? "Name is available." : "Name is unavailable.";
		echo "<br>";
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}

	printResultEnd($messages);
}

function test_GetLastPdfBody(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	try{
		echo "Getting Base64 encoded pdf for the last invoice of customer ". $EXISTING_CUSTOMER_ACCOUNT_NAME . "<br>";
		$body = InvoiceManager::getLastInvoicePdf($EXISTING_CUSTOMER_ACCOUNT_NAME);
		echo substr($body, 0, 100) . " . . . <br>";
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}

	printResultEnd($messages);
}

function test_ChangeDefaultPaymentMethod(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	$pmId;
	$checkPmId;
	try{
		$zapi = new zApi();
		$pres = $zapi->zQuery("SELECT DefaultPaymentMethodId FROM Account WHERE Name='".$EXISTING_CUSTOMER_ACCOUNT_NAME."'");
		if(count($pres->result->records)==0){
			throw new Exception("ACCOUNT_DOESNT_EXIST");
		}
		$pmId = $pres->result->records[0]->DefaultPaymentMethodId;

		$cpmres = $zapi->zQuery("SELECT Id from PaymentMethod where Name='Check'");

		//Test
		echo "Updating Default Payment Method on Account " . $EXISTING_CUSTOMER_ACCOUNT_NAME . " to 'Check': <br>";
		$changeRes = PaymentManager::changePaymentMethod($EXISTING_CUSTOMER_ACCOUNT_NAME, $pmId);
		print_r_html($changeRes);

		echo "Updating Default Payment Method on Account " . $EXISTING_CUSTOMER_ACCOUNT_NAME . " back to original payment method: <br>";
		$changeRes = PaymentManager::changePaymentMethod($EXISTING_CUSTOMER_ACCOUNT_NAME, $pmId);
		print_r_html($changeRes);
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}


	printResultEnd($messages);
}
function test_generateIframeUrl(){
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	$iframeSrc;
	try{
		$iframeSrc = PaymentManager::getNewAccountUrl();
	} catch (Exception $e){
		array_push($messages, "Iframe URL threw an exception " . $e->getMessage());
	}
	echo "The iframe below should display a credit card and contact information entry form.<br>";
	echo "<iframe src='".$iframeSrc."' width='400' height='180'></iframe><br>";

	printResultEnd($messages);
}

function test_getExistingAccountUrl(){
	global $EXISTING_CUSTOMER_ACCOUNT_NAME;
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	try{
		$iframeSrc = PaymentManager::getExistingIframeSrc($EXISTING_CUSTOMER_ACCOUNT_NAME);
		echo "The iframe below should display a credit card and contact information entry form, ".
			"prepopulated with existing user, '".$EXISTING_CUSTOMER_ACCOUNT_NAME."'s contact information..<br>";
		echo "<iframe src='".$iframeSrc."' width='400' height='180'></iframe><br>";
	} catch (Exception $e){
		array_push($messages, "Iframe URL threw an exception: " . $e->getMessage());
	}

	printResultEnd($messages);
}
function test_sApi_createAccount(){
	printResultStart(__FUNCTION__);
	$messages = array();

	//Test
	try{
		$sfdcRes = sApi::createSfdcAccount('NEW-ECOMMERCE-TEST-ACCOUNT');
		print_r_html($sfdcRes);
	} catch (Exception $e){
		array_push($messages, $e->getMessage());
	}

	printResultEnd($messages);
}

if($phpInfo){
	echo phpinfo();
}

//Test zApi.php
if($testzApi){
	try{
		test_zApi_Login();
		////Query Accounts
		test_zApi_Query();
		////Create Product
		test_zApi_Create();
		////Delete Product
		test_zApi_Delete();
		////Subscribe()
	//	test_zApi_Subscribe();
		////Amend()
		//test_zApi_Amend();
	} catch (Exception $e){
	}
}

//Test Catalog.php
if($testCatalog){
	//Refresh Catalog
	test_Catalog_Refresh();
	//Read Cached Catalog
	test_Catalog_Read();
}

//Test Cart.php
if($testCart){
	//Clear Cart
	test_Cart();
}

//Test SubscriptionManager.php
if($testSubscriptionManager){
	////Get Active Plan of the given user
	test_SubscriptionManager_getCurrentSubscriptions();
	////Subscribe with Current Cart and given email and pre-created payment method
	test_SubscriptionManager_subscribeWithCurrentCart();
	////Preview Current Cart
	test_SubscriptionManager_previewCurrentCart();
}

//Test Amender.php
if($testAmender){
	////Preview add A Rate Plan to the current Subscription, passing in a product rate plan id
	test_Amender_addRatePlan();
	////Remove a Rate Plan, No preview
	test_Amender_removeRatePlan();
	////Preview An Update to a rate plan quantity
	test_Amender_updateRatePlan();
	////Upgrade Change Product

	////Downgrade Change Product

}

//Test AccountManager.php
if($testAccountManager){
	//Get Account Detail
//	test_GetAccountSummary();
	//Get Contact Detail
//	test_GetContactSummary();
	//Get PaymentMethod Detail
//	test_GetPaymentMethodSummary();
	//Get Account, Contact and PaymentMethod detail
	test_GetCompleteSummary();
	//Update Contact
	test_UpdateContact();
	//Check Email Availability
	test_CheckEmailAvailability();
}

if($testPaymentManager){
	//Change Default Payment Method
	test_ChangeDefaultPaymentMethod();
	//Change Default Payment Method
	test_generateIframeUrl();
	//Change Default Payment Method
	test_getExistingAccountUrl();
}

if($testInvoiceManager){
	//Get Last Invoice Pdf
	test_GetLastPdfBody();
}

if($testsApi){
	//Create SFDC API
	test_sApi_createAccount();
}



function print_r_html ($arr) {
	?><pre><?
    print_r($arr);
    ?></pre><?
}

?>