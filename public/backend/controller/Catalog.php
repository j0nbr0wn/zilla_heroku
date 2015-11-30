<?php
/**
 * \brief The Catalog class manages and caches all Product data retrieved from the configured Zuora tenant
 *
 * V1.05
 */
class Catalog{
	private static $lastSync;

	/**
	 * Reads the Product Catalog Data from Zuora and saves it to a JSON cache stored on the server to reduce load times. This method must be called each time the Product Catalog is changed in Zuora to ensure the catalog is not out of date for the user.
	 * @return A model containing all necessary information needed to display the products and rate plans in the product catalog
	 */
	public static function refreshCache(){
		//Initialize Zuora API Instance
		include('./config.php');
		$zapi = new zApi();

		// if guided selling filter fields exist, loop through and create string for the query
		$guidedSellingFieldsString = '';
		if (count($guidedSellingFields) > 0) {
			$guidedSellingFieldsString = ',' . implode(",", $guidedSellingFields);
		}

		//For each classification
		$fieldGroups;
		$numGroups;
		if($showAllProducts){
			$numGroups = 1;
			$fieldGroups = array('');
		} else {
			$numGroups = count($groupingFieldValues);
			$fieldGroups = $groupingFieldValues;
		}

		$catalog_groups = array();
		foreach($fieldGroups as $fieldGroup){
			$catalog_group = new Catalog_Group();
			if ($fieldGroup == 'Base Product') {
				$catalog_group->isBase = true;
			} elseif ($fieldGroup == 'Add-On Product') {
				$catalog_group->isAddon = true;
			// } elseif ($fieldGroup == 'Partner') {
			// 	$catalog_group->isPartner = true;
			} else {
				$catalog_group->isHidden = true;
			}
			$catalog_group->Name = $fieldGroup;
			$catalog_group->products = array();

			date_default_timezone_set('America/Los_Angeles');
			$curDate = date('Y-m-d\TH:i:s',time());

			//Get All Products
			$productZoql = "select Id,Name,SKU,Description".$guidedSellingFieldsString." from Product where EffectiveStartDate<'".$curDate."' and EffectiveEndDate>'".$curDate."'";
			if(!$showAllProducts){
				$productZoql .= " and ".$groupingField."='".$fieldGroup."'";
			}
			$result = $zapi->zQuery($productZoql);
			$qProducts = array();
			if($result->result!=null) {
				$qProducts = $result->result->records;
			} else {
				addErrors(null,'No Products found.');
				return;
			}

			//Set up Catalog_Product objects
			foreach($qProducts as $p){
				$catalog_product = new Catalog_Product();
				$catalog_product->Id = $p->Id;
				$catalog_product->Name = $p->Name;
				$catalog_product->Description = isset($p->Description) ? $p->Description : "";
				$catalog_product->SKU = $p->SKU;
				// loop through the guided selling fields from config file
				if (count($guidedSellingFields) > 0) {
					$guidedFieldsArray = array();
					foreach($guidedSellingFields as $filterField) {
						// set the product to have the value of the guided selling field
						$catalog_product->$filterField = isset($p->$filterField) ? $p->$filterField : "";
						$guidedSellingValues = array();
						if ($catalog_product->$filterField != ""){
							$guidedFieldsArray[$filterField] = $catalog_product->$filterField;
						}
					}
					$catalog_product->filterValues = array();
					array_push($catalog_product->filterValues, $guidedFieldsArray);
				}
				//Get RatePlans for this Product
				$result = $zapi->zQuery("select Id,Name,Description,".$promoField." from ProductRatePlan where ProductId='".$catalog_product->Id."' and EffectiveStartDate<'".$curDate."' and EffectiveEndDate>'".$curDate."' ");
				$qRatePlans = array();
				$catalog_product->ratePlans = array();
				if($result->result!=null) {
					$qRatePlans = $result->result->records;
					if($qRatePlans!=null){
						foreach($qRatePlans as $rp){
							$catalog_rateplan = new Catalog_RatePlan();
							$catalog_rateplan->Id = $rp->Id;
							$catalog_rateplan->Name = $rp->Name;
							$catalog_rateplan->productName = $p->Name;
							$catalog_rateplan->Description = isset($rp->Description) ? $rp->Description : "";
							$catalog_rateplan->$promoField = isset($rp->$promoField) ? $rp->$promoField : "";

							//Get Charges for the Rate Plan
							$result = $zapi->zQuery("select Id,Name,DefaultQuantity,Description,UOM,ChargeModel,ChargeType,BillingPeriod from ProductRatePlanCharge where ProductRatePlanId='".$catalog_rateplan->Id."'");
							$qCharges = array();
							$catalog_rateplan->charges = array();
							if($result->result!=null) {
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
											usort($result->result->records, "Catalog::cmpTiers");
											$qChargeTiers = $result->result->records;
											if($qChargeTiers!=null){
												foreach($qChargeTiers as $rpct){
													$catalog_chargeTier = new Catalog_ChargeTier();
													$catalog_chargeTier->Id = $rpct->Id;
													$catalog_chargeTier->Price = $rpct->Price;
													$catalog_chargeTier->Currency = $rpct->Currency;
													$catalog_chargeTier->myCurrency = $rpct->Currency == $defaultCurrency;
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
							}
							array_push($catalog_product->ratePlans, $catalog_rateplan);
						}
						array_push($catalog_group->products, $catalog_product);
					}
				}
			}
			array_push($catalog_groups, $catalog_group);
		}
		$catalogJson = json_encode($catalog_groups);

		$lastSync = date(NULL);

		//Cache product list
		$myFile = $cachePath;
		$fh = fopen($myFile, 'w') or die("can't open file");
		fwrite($fh, $catalogJson);
		fclose($fh);

		return $catalog_groups;
	}

	private static function cmpTiers($b, $a)
	{
		if ($a->Tier == $b->Tier) {
			return 0;
		}
		return ($a->Tier > $b->Tier) ? -1 : 1;
	}

	/**
	 * Reads the Product Catalog Data from the locally saved JSON cache. If no cache exists, this will refresh the catalog from Zuora first.
	 * @return A model containing all necessary information needed to display the products and rate plans in the product catalog
	 */
	public static function readCache(){
		require('./config.php');
		if(!file_exists($cachePath)){
			return self::refreshCache();
		}
		$myFile = $cachePath;
		$fh = fopen($myFile, 'r');
		$catalogJson = fread($fh, filesize($myFile));
		fclose($fh);
		$catalog_groups = json_decode($catalogJson);
		return $catalog_groups;
	}

	/**
	 * Given a RatePlan ID, retrieves all rateplan information by searching through the cached catalog file
	 * @return RatePlan model
	 */
	public static function getRatePlan($rpId){
		$catalog_groups = self::readCache();
		foreach($catalog_groups as $group){
			foreach($group->products as $product){
				foreach($product->ratePlans as $ratePlan){
					if($ratePlan->Id == $rpId){
						return $ratePlan;
					}
				}
			}
		}
		return NULL;
	}

	public static function getPromoPlans($promoCode){

		include('./config.php');

		$plansArray = array();

		$catalog_groups = self::readCache();
		foreach($catalog_groups as $group){
			foreach($group->products as $product){
				foreach($product->ratePlans as $ratePlan){
					if(strtolower($ratePlan->$promoField) == strtolower($promoCode)){
						array_push($plansArray, $ratePlan);
					}
				}
			}
		}
		return $plansArray;
	}
	// function to get all the possible fields and values to be used in guided selling
	public static function getGuidedSellingValues(){

		include('./config.php');

		// $filterFieldsArray = array();
		$filterFieldsArray = array();

		$catalog_groups = self::readCache();
		if (count($guidedSellingFields) > 0) {
			foreach($guidedSellingFields as $filterField) {
				$fieldArray = array();
				foreach($catalog_groups as $group){
					foreach($group->products as $product){
						// strip out white spaces or underscores & replace with "-"
						$val = preg_replace("/[\s_]/", "-", $product->$filterField);
						if (!in_array($val, $fieldArray) && $val != ''){
							array_push($fieldArray, $val);
						}
					}
				}
				// sort field values alphabetically and name array keys to the custom field value
				usort($fieldArray, "Catalog::cmpGSFieldValues");
				$filterFieldsArray[$filterField] = $fieldArray;
			}
		}
		return $filterFieldsArray;
	}

	private static function cmpGSFieldValues($b, $a)
	{
		if ($a == $b) {
			return 0;
		}
		return ($a > $b) ? -1 : 1;
	}
}

?>
