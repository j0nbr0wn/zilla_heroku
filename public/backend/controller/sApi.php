<?php

/**
* \brief The sApi class is used to interface with Salesforce API
*
* V1.05
*/
class sApi{
	/**
	 * Converts a lead into an account in Salesforce
	 * @param N/A
	 * @return a response result containing a 'success' result and with ids of the created account, contact, opportunity if successful.
	 */
	static function convertSfdcLead(){
		include("./config.php");

		$mySforceConnection = new SforceEnterpriseClient();
		$mySforceConnection->createConnection($SfdcWsdl);
		$mySforceConnection->login($SfdcUsername, $SfdcPassword.$SfdcSecurityToken);

		$records = array();

		$records[0] = new stdclass();
		$records[0]->leadId = $_SESSION["LeadId"];
		if ($makeSfdcOpportunity == false) {
			$records[0]->doNotCreateOpportunity = true;
		} else {
			$records[0]->doNotCreateOpportunity = false;
			$records[0]->opportunityName = 'New Sale - ' . $_SESSION['userEmail'];
		}
		$records[0]->convertedStatus = 'Qualified';
		$records[0]->overwriteLeadSource = false;
		$records[0]->sendNotificationEmail = false;

		$response = $mySforceConnection->convertLead($records[0]);

		return $response->result[0];
	}

	static function createSfdcLead(){
		include("./config.php");

		$mySforceConnection = new SforceEnterpriseClient();
		$mySforceConnection->createConnection($SfdcWsdl);
		$mySforceConnection->login($SfdcUsername, $SfdcPassword.$SfdcSecurityToken);

		$records = array();

		$records[0] = new stdclass();
		// $records[0]->Company = $_SESSION['userEmail'];
		$records[0]->Company = $_SESSION['userEmail'];
		$records[0]->Email = $_SESSION['userEmail'];
		$records[0]->FirstName = $_SESSION['userFname'];
		$records[0]->LastName = $_SESSION['userLname'];
		$records[0]->Phone = $_SESSION['userPhone'];
		$records[0]->Street = $_SESSION['userAddress1'];
		$records[0]->City = $_SESSION['userCity'];
		$records[0]->Country = $_SESSION['userCountry'];
		$records[0]->State = $_SESSION['userState'];
		$records[0]->PostalCode = $_SESSION['userPostalCode'];
		$records[0]->Description = 'This lead arrived from the website and abandoned the shopping cart flow prior to completing the transaction';
		$records[0]->Status = 'Open';
		$records[0]->LeadSource = 'Web';

		$response = $mySforceConnection->create($records, 'Lead');

		return $response[0];
	}
}

?>
