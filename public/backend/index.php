<?php

/**
 * \brief index.php is used as a REST layer to interface between the front end HTML files and backend controller methods.
 * Events can be triggered from this page, using "<Base URL>/backend/?type=<ActionName>"
 *
 * V1.05
 */


function __autoload($class){
  @include('./model/' . $class . '.php');
  @include('./controller/' . $class . '.php');
  @include('./sfdc/' . $class . '.php');
}
session_start();

$debug = 0; //debug mode

$errors = array();
$messages = null;

//debug($client->__getFunctions());

isset($_REQUEST['type']) ? dispatcher($_REQUEST['type']) : '';

function addErrors($field,$msg){
	global $errors;
	$error['field']=$field;
	$error['msg']=$msg;
	$errors[] = $error;
}

function dispatcher($type){
	switch($type) {
		case 'LoginAttempt' : loginAttempt();
		break;
		case 'GetInitialCart' : getInitialCart();
		break;
		case 'AddItemToCart' : addItemToCart();
		break;
		case 'RemoveItemFromCart' : removeItemFromCart();
		break;
		case 'EmptyCart' : emptyCart();
		break;
		case 'RefreshCatalog' : refreshCatalog();
		break;
		case 'GetGuidedSellingValues' : getGuidedSellingValues();
		break;
		case 'ReadCatalog' : readCatalog();
		break;
		case 'GetSubscriptions' : getSubscriptions();
		break;
		case 'SetAmendSubId' : setAmendSubId();
		break;
		case 'GetAmendSubscription' : getAmendSubscription();
		break;
		case 'PreviewAddRatePlan' : previewAddRatePlan();
		break;
		case 'AddRatePlan' : addRatePlan();
		break;
		case 'PreviewRemoveRatePlan' : previewRemoveRatePlan();
		break;
		case 'RemoveRatePlan' : removeRatePlan();
		break;
		case 'PreviewUpdateRatePlan' : previewUpdateRatePlan();
		break;
		case 'UpdateRatePlan' : updateRatePlan();
		break;
		case 'GetUpgradeDowngradePlans' : getUpgradeDowngradePlans();
		break;
		case 'PreviewPlanUpgradeDowngrade' : previewPlanUpgradeDowngrade();
		break;
		case 'PlanUpgradeDowngrade' : planUpgradeDowngrade();
		break;
		case 'PreviewRenewSubscription' : previewRenewSubscription();
		break;
		case 'RenewSubscription' : renewSubscription();
		break;
		case 'CancelSubscription' : cancelSubscription();
		break;
		case 'GetAccountSummary' : getAccountSummary();
		break;
		case 'GetContactSummary' : getContactSummary();
		break;
		case 'GetPaymentMethodSummary' : getPaymentMethodSummary();
		break;
		case 'GetInvoiceSummary' : getInvoiceSummary();
		break;
		case 'GetBillingPreview' : getBillingPreview();
		break;
		case 'GetUsageSummary' : getUsageSummary();
		break;
		case 'GetCompleteSummary' : getCompleteSummary();
		break;
		case 'UpdateContact' : updateContact();
		break;
		case 'CheckEmailAvailability' : checkEmailAvailability();
		break;
		case 'UpdatePaymentMethod' : updatePaymentMethod();
		break;
		case 'RemovePaymentMethod' : removePaymentMethod();
		break;
		case 'GetNewIframeSrc' : getNewIframeSrc();
		break;
		case 'GetExistingIframeSrc' : getExistingIframeSrc();
		break;
		case 'SubscribeWithCurrentCart' : subscribeWithCurrentCart();
		break;
		case 'PreviewCurrentCart' : previewCurrentCart();
		break;
		case 'IsUserLoggedIn' : isUserLoggedIn();
		break;
		// begin new code for Partner - Ming
		case 'GetHierarchy' : getHierarchy();
		break;
		case 'GoToAccountView' : goToAccountView();
		break;
		case 'GetAccountInfo' : getAccountInfo();
		break;
		case 'GetSubConfirmInfo' : getSubConfirmInfo();
		break;
		case 'IsPartnerLoggedIn' : isPartnerLoggedIn();
		break;
		// end new code for Partner - Ming
		// HPM 2.0 begin
		case 'SubscribeHPM2' : subscribeHPM2();
		break;
		// HPM 2.0 end
		// Start Promo
		case 'PromoValidate' : promoValidate();
		break;
		// End Promo
		// Start Set Account Data
		case 'SetAccountInfo' : setAccountInfo();
		break;
		// End Set Account Data
		// Start Get Address Data
		case 'GetAddressInfo' : getAddressInfo();
		break;
		// End Set Account Data
		// Start Get Formatting Data
		case 'GetFormatting' : getFormatting();
		break;
		// End Set Formatting Data
		default : addErrors(null,'no action specified');
	}
}

function loginAttempt() {
	global $messages;

	$username = $_REQUEST['username'];
	$partnerLogin = $_REQUEST['partnerLogin'];

	$loginResult = LoginManager::loginAttempt($username, $partnerLogin);

	$sessionData = array('validLogin' => $loginResult, 'username' => $_SESSION['email']);

	$partnerData = array('isPartner' => $_SESSION['partnerLogin'], 'partnerEmail' => $_SESSION['partnerEmail'], 'partnerId' => $_SESSION["partnerId"]);

	$acctInfo = array('sessionData' => $sessionData, 'partnerData' => $partnerData);

	$messages = $acctInfo;
}

function setAccountInfo() {
	global $messages;

	$_SESSION['userEmail'] = $_REQUEST['userEmail'];
	$_SESSION['userFname'] = $_REQUEST['userFname'];
	$_SESSION['userLname'] = $_REQUEST['userLname'];
	$_SESSION['userPhone'] = $_REQUEST['userPhone'];
	$_SESSION['userAddress1'] = $_REQUEST['userAddress1'];
	$_SESSION['userCity'] = $_REQUEST['userCity'];
	$_SESSION['userCountry'] = $_REQUEST['userCountry'];
	$_SESSION['countryIso'] = $_REQUEST['countryIso'];
	$_SESSION['userState'] = $_REQUEST['userState'];
	$_SESSION['stateFullName'] = $_REQUEST['stateFullName'];
	$_SESSION['userPostalCode'] = $_REQUEST['userPostalCode'];

	try{
		// create SFDC lead
		$sfdcResponse = sApi::createSfdcLead();
		if($sfdcResponse->success){
			$_SESSION["LeadId"] = $sfdcResponse->id;
		}
	} catch (Exception $e){
		error_log('Lead: '.$userEmail.' could not be created in Salesforce: ' . $e->getMessage());
	}

	$acctInfo = Array($_SESSION['userEmail'], $_SESSION['userFname'], $_SESSION['userLname'], $_SESSION['userPhone'], $_SESSION['userAddress1'], $_SESSION['userCity'], $_SESSION['userCountry'], $_SESSION['userState'], $_SESSION['userPostalCode'], $_SESSION["LeadId"]);

	$messages = $acctInfo;
}

function getAddressInfo() {
	global $messages;

	$acctInfo = Array($_SESSION['userEmail'], $_SESSION['userFname'], $_SESSION['userLname'], $_SESSION['userAddress1'], $_SESSION['userCity'], $_SESSION['userCountry'], $_SESSION['countryIso'], $_SESSION['userState'], $_SESSION['stateFullName'], $_SESSION['userPostalCode']);

	$messages = $acctInfo;
}

function getFormatting() {
	global $messages;
	include('./config.php');

	$formatInfo = Array('defaultCurrency' => $defaultCurrency, 'dateFormat' => $dateFormat, 'decimalPlaces' =>$decimalPlaces, 'thousandSeparator' => $thousandSeparator, 'decimalSeparator' => $decimalSeparator, 'currencySymbol' => $currencySymbol, 'isPartner' => $_SESSION['partnerLogin'], 'isLoggedIn' => $_SESSION['email'], 'parentId' => $_SESSION['partnerId'], 'acctId' => $_SESSION['accountId']);

	$messages = $formatInfo;
}

// Promo Validate Begin
function promoValidate() {
	global $messages;

	if(!isset($_SESSION['cart'])){
		emptyCart();
	}

	// handle the case of a blank promo code
	if(empty($_REQUEST["promoCode"])) {
		$promoValue = 'PROMO_CODES_CANNOT_BE_BLANK';
	} else {
	    $promoValue = $_REQUEST["promoCode"];
	}

	//Get Rate Plan by Promo Code
	$promoPlanData = Catalog::getPromoPlans($promoValue);

	//if the query has results, add them to an array, then add that array to cart
	$quantity = 1;

	if ($promoPlanData){
		foreach($promoPlanData as $promoPlan){
	    	$chargeAndQty = array();
	    	foreach($promoPlan->charges as $charge){
	    		$chrgDetails = Array('name' => $charge->Id, 'value' => 1);
	    		array_push($chargeAndQty, $chrgDetails);
	    	}
	    	$_SESSION['cart']->addCartItem($promoPlan->Id, $chargeAndQty);
		}
	    $promoResult = "successfully applied";
	} else {
	    // echo "No promo rate plans returned";
	    $promoResult = 'is invalid';
	}
	$messages = $_SESSION['cart'];
	$messages->promoResult = $promoResult;
	$messages->codeTried = $promoValue;
	$messages->result = $promoPlanData;
}
// Promo Validate End

// HPM 2.0 function begin
function subscribeHPM2() {
	global $messages;

	// get the configuration
	include("./config.php");

	// $accountId = getAccountID();

	// connect to Zuora through the REST API
	$curl = curl_init();

	// set the page ID to HPM for credit or ACH
	if ($_REQUEST['paymentType'] == 'Credit'){
		$HPMpageId = $pageId;
	} else {
		$HPMpageId = $ACHpageId;
	}

	//set POST variables
	$url = 'https://zuora.com/apps/v1/rsa-signatures';
	$fields = array(
					'uri' => 'https://zuora.com/apps/PublicHostedPageLite.do',
					'method' => 'POST',
					'pageId' => $HPMpageId,
					);
	$fields_json = json_encode($fields);

	// echo $fields_json;

	$headers = array(
		"apiAccessKeyId:" . $username,
		"apiSecretAccessKey:" . $password,
		"Accept:application/json",
		"Content-Type: application/json"

	);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_json);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

	$data = curl_exec($curl);

	if (curl_errno($curl)) {
		addErrors(null, "Error: " . curl_error($curl));
	} else {
		$messages = json_decode($data);
		$messages->id = $HPMpageId;
		// $messages->accountId = $accountId;
	}
    curl_close($curl);
}
// end features code
// HPM 2.0 function end

// new function just to get accountID
function getAccountID() {
	$zapi;
	try{
		$zapi = new zApi();
	} catch(Exception $e){
		return null;
	}

	if(isset($_SESSION['email'])){
		$accName = $_SESSION['email'];;
	} else {
		return '';
	}

	//Get Contact with this email
	$accResult = $zapi->zQuery("SELECT Id FROM Account WHERE Name='".$accName."'");

	foreach($accResult->result->records as $acc){
		return $acc->Id;
	}
}

// begin new code for Partner - Ming
function getAccountInfo(){
	global $messages;

	$acctInfo = Array($_SESSION['AccountId'], $_SESSION['email'], $_SESSION['CustName']);

	$messages = $acctInfo;
}

function getSubConfirmInfo(){

	global $messages;

	$subInfo = $_SESSION['subInfo'];

	$confirmInfo = Array($_SESSION['subSuccess'], $subInfo['accountName'], $subInfo['firstName'] . " " . $subInfo['lastName'],
		$subInfo['term'], $subInfo['initTerm'],
		$subInfo['renewTerm'], $subInfo['autoRenew'],
		$subInfo['termStart'], $subInfo['contractEffdt'],
		$subInfo['activationDt'], $subInfo['acceptanceDt']);

	$messages = $confirmInfo;

}

function isPartnerLoggedIn(){
	global $messages;

	if(isset($_SESSION['email'])){
		$messages = true;
	} else {
		addErrors(null,"SESSION_NOT_SET");
		return;
	}
}

function goToAccountView(){
	$accountEmail = $_REQUEST['email'];
	$accountId = $_REQUEST['accId'];
	$accountParentId = $_REQUEST['parentId'];


	AccountManager::displayAccountView($accountEmail, $accountId, $accountParentId);
	// header('Location: ../account_view.html');
}

function getHierarchy(){
	global $messages;
	$result = AccountManager::buildHierarchy($_SESSION['partnerId'], 'null', 'null', $_SESSION['partnerEmail'], $_SESSION['partnerId'], 'null');
	$messages = $result;
}

// end new code for Partner - Ming

function emptyCart(){
	global $messages;

	$_SESSION['cart'] = new Cart();

	$messages = $_SESSION['cart'];
}

function getInitialCart(){
	global $messages;

	if(!isset($_SESSION['cart'])){
		emptyCart();
	}

	$messages = $_SESSION['cart'];
}

function addItemToCart(){
	global $messages;

	if(!isset($_SESSION['cart'])){
		emptyCart();
	}

	$ratePlanId = $_REQUEST['ratePlanId'];

	if(isset($_REQUEST['chargeAndQty'])) {
		$chargeAndQty = $_REQUEST['chargeAndQty'];
	} else {
		$chargeAndQty = null;
	}


	if(isset($_SESSION['cart'])){
		$_SESSION['cart']->addCartItem($ratePlanId, $chargeAndQty);
	} else {
		addErrors(null,'Cart has not been set up.');
		return;
	}

	$messages = $_SESSION['cart'];
}

function removeItemFromCart(){
	global $messages;

	$itemId;
	if(isset($_REQUEST['itemId'])){
		$itemId = $_REQUEST['itemId'];
	} else {
		addErrors(null,'Item Id not specified.');
		return;
	}

	if(isset($_SESSION['cart'])){
		$removed = $_SESSION['cart']->removeCartItem($itemId);
		if(!$removed){
			addErrors(null,'Item no longer exists.');
		}
	} else {
		addErrors(null,'Cart has not been set up.');
		return;
	}

	$messages = $_SESSION['cart'];
}

function refreshCatalog(){
	global $messages;
	$refreshResult = Catalog::refreshCache();

	$messages = $refreshResult;
}

function getGuidedSellingValues(){
	global $messages;

	$productFilters = Catalog::getGuidedSellingValues();

	$messages = $productFilters;
}

function readCatalog(){
	global $messages;
	$readResult = Catalog::readCache();

	$messages = $readResult;
}

function getSubscriptions(){
 	global $messages;

	$userSub = SubscriptionManager::getCurrentSubscriptions($_SESSION['accountId']);

	$messages = $userSub;
}

function setAmendSubId(){
 	global $messages;

 	$_SESSION['amendSubId'] = $_REQUEST['amendSubId'];

	$messages = $_SESSION['amendSubId'];
}

function getAmendSubscription(){
 	global $messages;

 	$currentSubId = $_SESSION['amendSubId'];

	$amendSub = SubscriptionManager::getAmendSubscription($currentSubId);

	$messages = $amendSub;
}

/* Retrieve the subtotal of the amendment being added
 *
 */
function previewAddRatePlan(){
	global $messages;

	$subId = $_REQUEST['subId'];
	$addRatePlanId = $_REQUEST['rpId'];
	$chargeAndQty = $_REQUEST['chargeAndQty'];
	$preview = true;

	$amRes = Amender::addRatePlan($subId, $addRatePlanId, $chargeAndQty, $preview);

	$messages = $amRes;
}

function addRatePlan(){
	global $messages;

	$subId = $_REQUEST['subId'];
	$addRatePlanId = $_REQUEST['rpId'];
	$chargeAndQty = $_REQUEST['chargeAndQty'];
	$preview = false;

	$amRes = Amender::addRatePlan($subId, $addRatePlanId, $chargeAndQty, $preview);

	$_SESSION['amendSubId'] = $amRes->results->SubscriptionId;

	$messages = $_SESSION['amendSubId'];
}

//Remove Product amendments generate no invoices, so this method will instead return the date on which the removal should take effect (End of term)
function previewRemoveRatePlan(){
	global $messages;

	$rpId = $_REQUEST['rpId'];
	$subId = $_REQUEST['subId'];
	$invoice = true;
	$preview = true;

	$amRes = Amender::removeRatePlan($subId, $rpId, $invoice, $preview);

	$messages = $amRes;
}

function removeRatePlanNoInvoice(){
	global $messages;

	$rpId = $_REQUEST['rpId'];
	$subId = $_REQUEST['subId'];
	$invoice = false;
	$preview = false;

	$amRes = Amender::removeRatePlan($subId, $rpId, $invoice, $preview);

	if(!$amRes->results->Success){
		addErrors(null,"Unable to remove Rate Plan");
		return;
	}

	$messages = $amRes;
}

function removeRatePlan(){
	global $messages;

	$rpId = $_REQUEST['rpId'];
	$subId = $_REQUEST['subId'];
	$invoice = true;
	$preview = false;

	$amRes = Amender::removeRatePlan($subId, $rpId, $invoice, $preview);

	if(!$amRes->results->Success){
		addErrors(null,"Unable to remove Rate Plan");
		return;
	}

	$messages = $amRes;
}

function previewUpdateRatePlan(){
	global $messages;

	$subId = $_REQUEST['subId'];
	$rpId = $_REQUEST['rpId'];
	$rpcId = $_REQUEST['rpcId'];
	$rpcQty = $_REQUEST['rpcQty'];
	$previewOpt = true;

	$amRes = Amender::updateRatePlan($subId, $rpId, $rpcId, $rpcQty, $previewOpt);

	$messages = $amRes;
}

function updateRatePlan(){
	global $messages;

	$subId = $_REQUEST['subId'];
	$rpId = $_REQUEST['rpId'];
	$rpcId = $_REQUEST['rpcId'];
	$rpcQty = $_REQUEST['rpcQty'];
	$previewOpt = false;

	$amRes = Amender::updateRatePlan($subId, $rpId, $rpcId, $rpcQty, $previewOpt);

	$messages = $amRes;
}

function getUpgradeDowngradePlans(){
	global $messages;

	$updownSku = $_REQUEST['updownSku'];

	$amRes = Amender::getUpgradeDowngradePlans($updownSku);


	$messages = $amRes;
}

function previewPlanUpgradeDowngrade(){
	global $messages;

	$subId = $_REQUEST['subId'];
	$addRatePlanId = $_REQUEST['addRatePlanId'];
	$chargeAndQty = $_REQUEST['chargeAndQty'];
	$removeRatePlanId = $_REQUEST['removeRatePlanId'];
	$invoice = true;
	$previewOpt = true;

	$amRes = Amender::planUpgradeDowngrade($subId, $addRatePlanId, $chargeAndQty, $removeRatePlanId, $invoice, $previewOpt);

	$messages = $amRes;
}

function planUpgradeDowngrade(){
	global $messages;

	$subId = $_REQUEST['subId'];
	$addRatePlanId = $_REQUEST['addRatePlanId'];
	$chargeAndQty = $_REQUEST['chargeAndQty'];
	$removeRatePlanId = $_REQUEST['removeRatePlanId'];
	$invoice = false;
	$previewOpt = false;

	$amRes = Amender::planUpgradeDowngrade($subId, $addRatePlanId, $chargeAndQty, $removeRatePlanId, $invoice, $previewOpt);

	$messages = $amRes;
}

function previewRenewSubscription(){
	global $messages;

	$subId = $_REQUEST['subId'];
	$previewOpt = true;

	$amRes = Amender::renewSubscription($subId, $previewOpt);

	$messages = $amRes;
}

function renewSubscription(){
	global $messages;

	$subId = $_REQUEST['subId'];
	$previewOpt = false;

	$amRes = Amender::renewSubscription($subId, $previewOpt);

	$messages = $amRes;
}

function previewCancelSubscription(){
	global $messages;

	$subId = $_REQUEST['subId'];
	$cancelDate = $_REQUEST['cancelDate'];
	$previewOpt = true;

	$amRes = Amender::cancelSubscription($subId, $cancelDate, $previewOpt);

	$messages = $amRes;
}

function cancelSubscription(){
	global $messages;

	$subId = $_REQUEST['subId'];
	$cancelDate = $_REQUEST['cancelDate'];
	$previewOpt = false;

	$amRes = Amender::cancelSubscription($subId, $cancelDate, $previewOpt);

	$messages = $amRes;
}

function getAccountSummary(){
	global $messages;

	$accSum = AccountManager::getAccountDetail($_SESSION['accountId']);

	if($accSum=='INVALID_ZLOGIN'){
		addErrors(null,"INVALID_ZLOGIN");
		return;
	} else if($accSum=='USER_DOESNT_EXIST'){
		addErrors(null,"USER_DOESNT_EXIST");
		return;
	}

	$messages = $accSum;
}

function getContactSummary(){
	global $messages;

	$conSum = AccountManager::getContactDetail($_SESSION['accountId']);

	$messages = $conSum;
}

function updateContact(){
	global $messages;

	$contactId = $_REQUEST['contactId'];
	$firstName = $_REQUEST['firstName'];
	$lastName = $_REQUEST['lastName'];
	$address = $_REQUEST['address'];
	$city = $_REQUEST['city'];
	$state = $_REQUEST['state'];
	$postalCode = $_REQUEST['postalCode'];
	$country = $_REQUEST['country'];
	$updRes = AccountManager::updateContact($contactId, $firstName, $lastName, $address, $city, $state, $postalCode, $country);

	$messages = $updRes;
}

function getPaymentMethodSummary(){
	global $messages;

	$accSum = AccountManager::getPaymentMethodDetail($_SESSION['accountId']);

	$pmSum = Array($accSum->paymentMethodSummaries, $accSum->ACHpaymentMethodSummaries);

	$messages = $accSum;
}

function getInvoiceSummary(){
	global $messages;

	$invoiceSum = AccountManager::getInvoiceDetail($_SESSION['accountId']);

	$messages = $invoiceSum;
}

function getBillingPreview(){
	global $messages;

	$billPreview = AccountManager::getBillingPreview($_SESSION['accountId']);

	$messages = $billPreview;
}

function getUsageSummary(){
	global $messages;

	$usageSum = AccountManager::getUsageDetail($_SESSION['accountId']);

	$messages = $usageSum;
}

function getCompleteSummary(){
	global $messages;

	$accSum = AccountManager::getCompleteDetail($_SESSION['email']);

	$messages = $accSum;
}

function updatePaymentMethod(){
	global $messages;

	$pmId = $_REQUEST['pmId'];

	$updRes = PaymentManager::changePaymentMethod($_SESSION['accountId'], $pmId);

	$messages = $updRes;
}

function removePaymentMethod(){
	global $messages;

	$pmId = $_REQUEST['pmId'];

	$delRes = PaymentManager::removePaymentMethod($pmId);

	$messages = $delRes;
}

function checkEmailAvailability(){
	global $messages;

	$uEmail = $_REQUEST['uEmail'];

	$check = AccountManager::checkEmailAvailability($uEmail);

	$messages = $check;
}

function getNewIframeSrc(){
	global $messages;

	$iframeSrc = PaymentManager::getNewAccountUrl();

	$messages = $iframeSrc;
}

function getExistingIframeSrc(){
	global $messages;

	$iframeSrc = PaymentManager::getExistingIframeSrc($_SESSION['email']);

	$messages = $iframeSrc;
}

function subscribeWithCurrentCart(){
	global $messages;

	$userEmail = $_SESSION['userEmail'];
	$pmId = $_REQUEST['pmId'];

	$subRes = SubscriptionManager::subscribeWithCart($userEmail, $pmId, $_SESSION['cart']);

	if($subRes=='DUPLICATE_EMAIL'){
		addErrors(null,"This email address is already in use. Please choose another and re-submit.");
		return;
	}
	if($subRes=='INVALID_PMID'){
		addErrors(null,"There was an error processing this transaction. Please try again.");
		return;
	}

	$partnerLogin = false;
	$loginRes = LoginManager::loginAttempt($userEmail, $partnerLogin);

	$messages = $subRes;
}


function previewCurrentCart(){
	global $messages;

	$subscribePreview = new Subscribe_Preview();
	$subscribePreview = SubscriptionManager::previewCart($_SESSION['cart']);

	$messages = $subscribePreview;
	return;
}

function isUserLoggedIn(){
	global $messages;

	$isLoggedIn = $_SESSION['email'];
	$partnerLogin = $_SESSION['partnerLogin'];

	$loginData = array('isLoggedIn' => $isLoggedIn, 'isPartner' => $partnerLogin);

	$messages = $loginData;

	return $messages;
}

function debug($a) {
	global $debug ;
	if($debug) {
		echo "/*";
		var_dump($a);
		echo "*/";
	}
}

function output(){
	global $errors,$messages;
	$msg = array();

	if(count($errors)>0) {
		debug($errors);
		$msg['success'] = false;
		$msg['msg'] = $errors;
	}
	else {
		$msg['success'] = true;
		if(!is_array($messages)) $messages = array($messages);
		$msg['msg'] = $messages;
	}

	debug($msg);

	echo json_encode($msg);

}

output();
?>
