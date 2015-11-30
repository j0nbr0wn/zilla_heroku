<?php


/**
 * \brief The Amender class manages Amendments for the logged in user's subscription.
 *
 * V1.05
 */
class Amender {

	/**
	 * \brief Adds a new ratePlan to the current user's subscription.
	 *
	 * New products added to the user's subscription are effective immediately.
	 * A quantity can also be supplied, that will apply to all recurring and one-time charges on the rate plan that do not use flat fee pricing.
	 * @param $accountName Name of the target account
	 * @param $prpId Product Rate Plan of the amendment to be added.
	 * @param $qty Amount of UOM for the RatePlan being added. A null value can be passed for product rate plans that use flat fee pricing
	 * @param $preview Flag to determine whether this function will be used to create an amendment, or preview an invoice
	 * @return Amend Result
	 */
	public static function addRatePlan($subId, $addRatePlanId, $chargeAndQty, $preview) {
		$zapi;
		try {
			$zapi = new zApi();
		} catch (Exception $e) {
			throw new Exception("INVALID_ZLOGIN");
		}

		$ratePlanData = array("RatePlan" => array("ProductRatePlanId" => $addRatePlanId));

		date_default_timezone_set('America/Los_Angeles');
		$date = date('Y-m-d\TH:i:s');

		$ratePlanChargeData = array();

		foreach($chargeAndQty as $charge) {
		    $chargeId = $charge['name'];
		    if ($charge['value']){
				$chargeQty = $charge['value'];
			} else {
				$chargeQty = 1;
			}
		    array_push($ratePlanChargeData, array(
		    	"RatePlanCharge"=>array(
		    		"ProductRatePlanChargeId"=> $chargeId,
		    		"Quantity"=>$chargeQty)
		    	)
		    );
		}
		$ratePlanData["RatePlanChargeData"] = $ratePlanChargeData;

		$amendment = array (
			'EffectiveDate' => $date,
			'Name' => 'Add Rate Plan' . time(),
			'Description' => 'New Rate Plan',
			'Status' => 'Completed',
			'SubscriptionId' => $subId,
			'Type' => 'NewProduct',
			'ContractEffectiveDate' => $date,
			'ServiceActivationDate' => $date,
			'CustomerAcceptanceDate' => $date,
			'EffectiveDate' => $date,
			'RatePlanData' => $ratePlanData,
		);

		$amendOptions = array (
			"GenerateInvoice" => true,
			"ProcessPayments" => true,

		);
		$previewOptions = array (
			"EnablePreviewMode" => $preview
			// "NumberOfPeriods" => 1 // this seems to be a bug, default is 1 anyways
		);

		$amendResult = $zapi->zAmend($amendment, $amendOptions, $previewOptions);
		return $amendResult;
	}

	/**
	 * \brief Remove an existing Rate Plan from the give user's subscription.
	 *
	 * Rate Plans removed will take effect at the end of the user's current billing cycle to avoid prorations and credit back.
	 * @param $accountName Name of the target account
	 * @param $rpId Rate Plan ID of the rate plan to be removed
	 * @param $preview Flag to determine whether this function will be used to create an amendment, or preview an invoice
	 * @return Amend Result
	 */
	public static function removeRatePlan($subId, $rpId, $invoice, $preview) {
		$zapi;
		try {
			$zapi = new zApi();
		} catch (Exception $e) {
			throw new Exception("INVALID_ZLOGIN");
		}

		// $sub = SubscriptionManager :: getCurrentSubscriptions($accountName);

		date_default_timezone_set('America/Los_Angeles');
		$date = date('Y-m-d\TH:i:s');
		$amendment = array (
			'Name' => 'Remove Rate Plan' . time(),
			'Description' => 'Remove Rate Plan',
			'Status' => 'Completed',
			'SubscriptionId' => $subId,
			'Type' => 'RemoveProduct',

			 // set all dates to today
			'ContractEffectiveDate' => $date,
			'ServiceActivationDate' => $date,
			'CustomerAcceptanceDate' => $date,
			'EffectiveDate' => $date,

			// 'ContractEffectiveDate' => $sub->endOfTermDate,
			// 'ServiceActivationDate' => $sub->endOfTermDate,
			// 'CustomerAcceptanceDate' => $sub->endOfTermDate,
			// 'EffectiveDate' => $sub->endOfTermDate,

			'RatePlanData' => array (
				'RatePlan' => array (
					'AmendmentSubscriptionRatePlanId' => $rpId
				),
			),
		);
		$amendOptions = array (
			"GenerateInvoice" => $invoice,
			"ProcessPayments" => $invoice,

		);
		$previewOptions = array (
			"EnablePreviewMode" => $preview,
			// "NumberOfPeriods" => 1 // this seems to be a bug, default is 1 anyways
		);

		$amendResult = $zapi->zAmend($amendment, $amendOptions, $previewOptions);
		return $amendResult;
	}

	public static function planUpgradeDowngrade($subId, $addRatePlanId, $chargeAndQty, $removeRatePlanId, $invoice, $previewOpt) {

		$removedRatePlan = Amender::removeRatePlan($subId, $removeRatePlanId, $invoice, $previewOpt);
		$addedRatePlan = Amender::addRatePlan($subId, $addRatePlanId, $chargeAndQty, $previewOpt);

		$amendResult = Array("addedPlanResult" => $addedRatePlan, "removedPlanResult" => $removedRatePlan);

		return $amendResult;
	}

	public static function updateRatePlan($subId, $rpId, $rpcId, $rpcQty, $preview) {
		$zapi;
		try {
			$zapi = new zApi();
		} catch (Exception $e) {
			throw new Exception("INVALID_ZLOGIN");
		}

		date_default_timezone_set('America/Los_Angeles');
		$date = date('Y-m-d\TH:i:s');
		$amendment = array (
			'Name' => 'Update Rate Plan Quantity' . time(),
			'Description' => 'Update Rate Plan Quantity',
			'Status' => 'Completed',
			'SubscriptionId' => $subId,
			'Type' => 'UpdateProduct',

			 // set all dates to today
			'ContractEffectiveDate' => $date,
			'ServiceActivationDate' => $date,
			'CustomerAcceptanceDate' => $date,
			'EffectiveDate' => $date,

			'RatePlanData' => array (
				'RatePlan' => array (
					'AmendmentSubscriptionRatePlanId' => $rpId
				),
				'RatePlanChargeData' => array (
					'RatePlanCharge' => array (
						'ProductRatePlanChargeId' => $rpcId,
						'Quantity' => $rpcQty
						)
				),
			),
		);
		$amendOptions = array (
			"GenerateInvoice" => true,
			"ProcessPayments" => true,

		);
		$previewOptions = array (
			"EnablePreviewMode" => $preview,
			// "NumberOfPeriods" => 1 // this seems to be a bug, default is 1 anyways
		);

		$amendResult = $zapi->zAmend($amendment, $amendOptions, $previewOptions);
		return $amendResult;
	}

	public static function renewSubscription($subId, $preview) {
		$zapi;
		try {
			$zapi = new zApi();
		} catch (Exception $e) {
			throw new Exception("INVALID_ZLOGIN");
		}

		date_default_timezone_set('America/Los_Angeles');
		$date = date('Y-m-d\TH:i:s');
		$amendment = array (
			'Name' => 'Renewal on' . time(),
			'Description' => 'Renewed subscription via self-service site',
			'Status' => 'Completed',
			'SubscriptionId' => $subId,
			'Type' => 'Renewal',

			/** set all dates to today*/
			'ContractEffectiveDate' => $date,
			'ServiceActivationDate' => $date,
			'CustomerAcceptanceDate' => $date,
			'EffectiveDate' => $date,
			);

		$amendOptions = array (
			"GenerateInvoice" => false,
			"ProcessPayments" => false,

			);
		$previewOptions = array (
			"EnablePreviewMode" => $preview,
			);

		$amendResult = $zapi->zAmend($amendment, $amendOptions, $previewOptions);

		return $amendResult;
	}

	public static function cancelSubscription($subId, $cancelDate, $preview) {
		$zapi;
		try {
			$zapi = new zApi();
		} catch (Exception $e) {
			throw new Exception("INVALID_ZLOGIN");
		}

		date_default_timezone_set('America/Los_Angeles');
		$cancelDateFormatted = DateTime::createFromFormat('m/d/Y', $cancelDate)->format('Y-m-d\TH:i:s');

		$date = date('Y-m-d\TH:i:s');
		$amendment = array (
			'Name' => 'Cancellation' . time(),
			'Description' => 'Cancelled subscription via the self-service site',
			'Status' => 'Completed',
			'SubscriptionId' => $subId,
			'Type' => 'Cancellation',

			/* set all dates for the cancellation */
			'ContractEffectiveDate' => $date,
			'ServiceActivationDate' => $date,
			'CustomerAcceptanceDate' => $date,
			'EffectiveDate' => $cancelDateFormatted,
			);

		$amendOptions = array (
			"GenerateInvoice" => false,
			"ProcessPayments" => false,

			);
		$previewOptions = array (
			"EnablePreviewMode" => $preview,
			);

		$amendResult = $zapi->zAmend($amendment, $amendOptions, $previewOptions);

		return $amendResult;
	}

	public static function getUpgradeDowngradePlans($updownSku) {
		//Initialize Zuora API Instance
		include('./config.php');
		$zapi = new zApi();

		$_SESSION['myCurrency'] = $defaultCurrency;

		//For each classification

		date_default_timezone_set('America/Los_Angeles');
		$curDate = date('Y-m-d\TH:i:s',time());

		//Get the Upgrade/Downgrade Product by SKU
		$productZoql = "select Id,Name,SKU,Description from Product where SKU='".$updownSku."' or Id='".$updownSku."' and EffectiveStartDate<'".$curDate."' and EffectiveEndDate>'".$curDate."'";
		$result = $zapi->zQuery($productZoql);

		if($result->result!=null) {
			$qProduct = $result->result->records[0];
		} else {
			addErrors(null,'No Products found.');
			return;
		}

		//Set up the Catalog_Product object
		$catalog_product = new Catalog_Product();
		$catalog_product->Id = $qProduct->Id;
		$catalog_product->Name = $qProduct->Name;
		$catalog_product->Description = isset($qProduct->Description) ? $qProduct->Description : "";
		$catalog_product->SKU = $qProduct->SKU;

		//Get RatePlans for this Product
		$result = $zapi->zQuery("select Id,Name,Description from ProductRatePlan where ProductId='".$catalog_product->Id."' and EffectiveStartDate<'".$curDate."' and EffectiveEndDate>'".$curDate."' ");
		$qRatePlans = array();
		$catalog_product->ratePlans = array();
		$qRatePlans = $result->result->records;
		if($qRatePlans!=null){
			foreach($qRatePlans as $rp){
				$catalog_rateplan = new Catalog_RatePlan();
				$catalog_rateplan->Id = $rp->Id;
				$catalog_rateplan->Name = $rp->Name;
				$catalog_rateplan->productName = $qProduct->Name;
				$catalog_rateplan->Description = isset($rp->Description) ? $rp->Description : "";

				//Get Charges for the Rate Plan
				$result = $zapi->zQuery("select Id,Name,DefaultQuantity,Description,UOM,ChargeModel,ChargeType,BillingPeriod from ProductRatePlanCharge where ProductRatePlanId='".$catalog_rateplan->Id."'");
				$qCharges = array();
				$catalog_rateplan->charges = array();
				$qCharges = $result->result->records;
					if($qCharges!=null){
						// start of for loop for rate plan charges
						foreach($qCharges as $rpc){
							$catalog_charge = new Catalog_Charge();
							$catalog_charge->Id = $rpc->Id;
							$catalog_charge->Name = $rpc->Name;
							$catalog_charge->Description = isset($rpc->Description) ? $rpc->Description : "";
							$catalog_charge->ChargeModel = $rpc->ChargeModel;
							$catalog_charge->ChargeType = $rpc->ChargeType;
							$catalog_charge->BillingPeriod = $rpc->BillingPeriod;
							if($catalog_charge->ChargeModel=='Tiered with Overage Pricing' || $catalog_charge->ChargeModel=='Tiered Pricing' || $catalog_charge->ChargeModel=='Volume Pricing'){
								$catalog_charge->Uom = $rpc->UOM;
								$catalog_charge->isTiered = true;
							}
							if(($catalog_charge->ChargeType!='Usage') && ($catalog_charge->ChargeModel=='Per Unit Pricing' || $catalog_charge->ChargeModel=='Tiered Pricing' || $catalog_charge->ChargeModel=='Volume Pricing')){
								$catalog_charge->Uom = $rpc->UOM;
								$catalog_charge->quantifiable = true;
								$catalog_charge->DefaultQuantity = isset($rpc->DefaultQuantity) ? $rpc->DefaultQuantity : "1";
							}
							// probably need special case for ChargeModel == Overage Pricing to show overage price
							//MD New Lines to get detail for the individual charges
							$result = $zapi->zQuery("select Id,Price,Currency,Tier,StartingUnit,EndingUnit,PriceFormat from ProductRatePlanChargeTier where ProductRatePlanChargeId='".$catalog_charge->Id."'");
							$qChargeTiers = array();
							$catalog_charge->chargeTiers = array();
							if($result->result!=null) {
								usort($result->result->records, "Amender::cmpTiers");
								$qChargeTiers = $result->result->records;
								if($qChargeTiers!=null){
									foreach($qChargeTiers as $rpct){
										$catalog_chargeTier = new Catalog_ChargeTier();
										$catalog_chargeTier->Id = $rpct->Id;
										$catalog_chargeTier->Price = $rpct->Price;
										$catalog_chargeTier->Currency = $rpct->Currency;
										$catalog_chargeTier->myCurrency = $rpct->Currency == $_SESSION['myCurrency'];
										$catalog_chargeTier->Tier = $rpct->Tier;
										$catalog_chargeTier->StartingUnit = $rpct->StartingUnit;
										$catalog_chargeTier->EndingUnit = $rpct->EndingUnit;
										$catalog_chargeTier->PriceFormat = $rpct->PriceFormat;
										array_push($catalog_charge->chargeTiers, $catalog_chargeTier);
									}
								}
							}
							//MD end new lines to get charge detail
							array_push($catalog_rateplan->charges, $catalog_charge);
						}
					}
				array_push($catalog_product->ratePlans, $catalog_rateplan);
			}
		}
	return $catalog_product;
	}

	private static function cmpTiers($b, $a)
	{
		if ($a->Tier == $b->Tier) {
			return 0;
		}
		return ($a->Tier > $b->Tier) ? -1 : 1;
	}


	// public static function RESTupdateRatePlan($subId, $rpId, $rpcId, $rpcQty, $preview) {
	// 	global $messages;

	// 	// get the configuration
	// 	include("./config.php");

	// 	// connect to Zuora through the REST API
	// 	$curl = curl_init();

	// 	// the update to the qty of the charge
	// 	$chargeUpdateDetailsArray = array(
	// 		array('ratePlanChargeId' => $rpcId,
	// 		'quantity' => $rpcQty)
	// 		);

	// 	// the update to the rate plan with effective date
	// 	$updateArray = array(array(
	// 		'ratePlanId' => $rpId,
	// 		'contractEffectiveDate' => '2014-12-31',
	// 		'chargeUpdateDetails' => $chargeUpdateDetailsArray)
	// 		);

	// 	//set POST variables
	// 	$url = $RestBaseUrl . 'v1/subscriptions/' . $subId;
	// 	$fields = array(
	// 					'update' => $updateArray,
	// 					'preview' => $preview,
	// 					// 'uri' => $url,
	// 					// 'method' => 'PUT'
	// 					);
	// 	$fields_json = json_encode($fields);

	// 	// echo $fields_json;

	// 	$headers = array(
	// 		"apiAccessKeyId:" . $username,
	// 		"apiSecretAccessKey:" . $password,
	// 		"Accept:application/json",
	// 		"Content-Type: application/json",
	// 		"Content-Length: " . strlen($fields_json)
	// 	);
	// 	curl_setopt($curl, CURLOPT_URL, $url);
	// 	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	// 	// curl_setopt($curl, CURLOPT_POST, 1);
	// 	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
	// 	curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_json);
	// 	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
	// 	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	// 	// curl_setopt($curl, CURLOPT_VERBOSE, 1);

	// 	$data = curl_exec($curl);

	// 	echo $fields_json;

	// 	if (curl_errno($curl)) {
	// 		addErrors(null, "Error: " . curl_error($curl));
	// 	} else {
	// 		$messages = json_decode($data);
	// 	}
	//     curl_close($curl);
	// }

}
?>
