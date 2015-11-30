<?php

/*
 *    Copyright (c) 2012 Zuora, Inc.
 *
 *    Permission is hereby granted, free of charge, to any person obtaining a copy of
 *    this software and associated documentation files (the "Software"), to use copy,
 *    modify, merge, publish the Software and to distribute, and sublicense copies of
 *    the Software, provided no fee is charged for the Software.  In addition the
 *    rights specified above are conditioned upon the following:
 *
 *    The above copyright notice and this permission notice shall be included in all
 *    copies or substantial portions of the Software.
 *
 *    Zuora, Inc. or any other trademarks of Zuora, Inc.  may not be used to endorse
 *    or promote products derived from this Software without specific prior written
 *    permission from Zuora, Inc.
 *
 *    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *    FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL
 *    ZUORA, INC. BE LIABLE FOR ANY DIRECT, INDIRECT OR CONSEQUENTIAL DAMAGES
 *    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 *    ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/* * * * * * * * * *
* Zuora Credentials *
 *    (Required)     *
  * * * * * * * * * * */

$username = '<<zuora_login>>'; // *UPDATE*
$password = '<<zuora_password>>'; // *UPDATE*
$endpoint = 'https://www.zuora.com/apps/services/a/64.0';

/* * * * * * * * * *
* Additional Config *
 * * * * * * * * * * */

$wsdl = 'zuora.a.64.0.wsdl';

/* * * * * * * * * *
* Z-Payments Page   *
 *    (Required)     *
  * * * * * * * * * * */

// Use your HPM 2.0 page ID
$pageId = '<<zuora_CCpageId>>'; //hosted credit card page Id *UPDATE*
$ACHpageId = '<<zuora_ACHpageId>>'; // hosted ACH page Id *UPDATE*

$appUrl = 'https://www.zuora.com';
$RestBaseUrl = 'https://api.zuora.com/rest';

/* * * * * * * * * *
* SFDC Credentials  *
 * * * * * * * * * * */

$makeSfdcAccount = true; // set true to create a Salesforce account using the credentials below
$makeSfdcOpportunity = true; // set to false to prevent SFDC opportunity creation

$SfdcUsername = ""; // *UPDATE*
$SfdcPassword = ""; // *UPDATE*
$SfdcSecurityToken = ""; // *UPDATE*
$SfdcWsdl = "sfdc/enterprise.wsdl.xml"; // Use "sfdc/enterprise.wsdl.xml" for Production/Developmer (login.salesforce) or "sfdc/enterprise-sandbox.wsdl.xml" for Sandbox (test.salesforce)

/* * * * * * * * * * * * *
* Product Select Options  *
 * * * * * * * * * * * * * */

$showAllProducts = false; //Show All Products or only a subset
$groupingField = "ProductCategory__c"; // Zuora Custom Field to use as Grouping Field *UPDATE*

// Which values in the classification field (above) to show *UPDATE*
$groupingFieldValues = array("Base Product", "Add-On Product", "Miscellaneous", "Professional Services");

// API names of the "guided selling" Product *PICKLIST* custom fields to use *UPDATE*
$guidedSellingFields = array("SoftwareProductFamily__c", "HardwareProductFamily__c", "SaaSProductFamily__c", "PaaSProductFamily__c");

//Locally cache the product catalog at this location to reduce load times.
$cachePath = "catalogCache.txt";

//Field to use for the promo query *UPDATE*
$promoField="PromotionCode__c";

/* * * * * * * * * * * *
* New Account Defaults  *
 * * * * * * * * * * * * */

//New accounts are created in Zuora with the following default values
$defaultAutopay = true;
$defaultPaymentTerm = "Due Upon Receipt";
$defaultBatch = "Batch8";

/* * * * * * * * * * * * * * * *
* Currency & Date Format Options *
 * * * * * * * * * * * * * * * * * */

// Optionally *UPDATE* for anything outside the USA
$defaultCurrency = "USD"; // currency ISO code - filters catalog and defaults for new accounts
$dateFormat = "MM/DD/YYYY"; // use "DD/MM/YYYY" for most non-US (other options here: http://momentjs.com/docs/#/displaying/format/)
$decimalPlaces = 2; // # decimal places to show for currency values
$thousandSeparator = ","; // the separator between thousands in currency values e.g. 1,000
$decimalSeparator = "."; // the separate for decimal points in currency values e.g. 25.99
$currencySymbol = "$"; // the currency symbol to use in currency values

?>
