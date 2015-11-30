<?php
include_once './controller/SubscriptionManager.php';
include_once './controller/zApi.php';
include_once './controller/Cart.php';
include_once './model/Cart_Item.php';
include_once './controller/Catalog.php';

function get_post_value($name, $default = "")
{
    $v = isset($_POST[$name]) ? $_POST[$name] : $default;
    return trim($v);
}

ob_start();
session_start();

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	$acctName = get_post_value('Account');
	$firstName = get_post_value('FirstName');
	$lastName = get_post_value('LastName');
	$address = get_post_value('Address');
	$city = get_post_value('City');
	$state = get_post_value('State');
	$country = get_post_value('Country');
	$postalCode = get_post_value('postalCode');
	$paymentTerm = 'Due Upon Receipt';
	$invoiceOwner = get_post_value('invoiceOwner');
	$term = get_post_value('Term');
	$initTerm = get_post_value('Init');
	$renewTerm = get_post_value('RenewTerm');
	$autoRenew = get_post_value('AutoRenew');
	$termStart = get_post_value('TermStart');
	$contractEffdt = $termStart;
	$activationDt = $termStart;
	$acceptanceDt = $termStart;
	// set parent ID to the node in the hierarchy clicked
	$parentId = $_SESSION['accountId'];

	// echo 'payment in post: ' . $paymentTerm;

	if($invoiceOwner=='pbill') {
		$InvoiceOwnerId = $_SESSION['accountId'];
	} else {
		$InvoiceOwnerId = 'null';
	}
	//echo 'parent id: ' . $_SESSION['parentId'];
	$subInfo = array('accountName' => $acctName, 'term' => $term,
		'firstName' => $firstName, 'lastName' => $lastName,
		'accountName' => $acctName, 'parentId' => $parentId,
		'address' => $address, 'city' => $city, 'state' => $state,
		'country' => $country, 'postalCode' => $postalCode, 'paymentTerm' => $paymentTerm,
		'invoiceOwner' => $InvoiceOwnerId, 'initTerm' => $initTerm, 'renewTerm' => $renewTerm,
		'autoRenew' => $autoRenew, 'termStart' => $termStart,
		'contractEffdt' => $contractEffdt, 'activationDt' => $activationDt,
		'acceptanceDt' => $acceptanceDt);
	//TODO add code to check up against app table
	$subRes = SubscriptionManager::partnerSubscribe($subInfo, $_SESSION['cart']);

	//$sandbox_session_id = get_session_id($username, $password, $url);

	if(!$subRes->result->Success) {
		$_SESSION['subSuccess'] = 'false';
		echo 'false ';
		echo $subRes->result->Errors->Message . ' ';
		echo $subRes->result->Errors->Code . ' ';
	} else {
		$_SESSION['subSuccess'] = 'true';
		$_SESSION['subInfo'] = $subInfo;
		echo 'true';
	}

	$headerStr = "../confirmation.html";

	Header("Location:" . $headerStr);

}
ob_end_flush();
?>
