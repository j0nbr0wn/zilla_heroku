<?php

	function __autoload($class){
	  @include('controller/' . $class . '.php');
	}
	session_start();

    //Start of getting this invoice's body to view as PDF
    $zapi;
    try{
        $zapi = new zApi();
    } catch(Exception $e){
        return null;
    }

    $invPdfId = $_POST["pdf_inv_id"];

    //Use this invoice and return the body
    $invResult = $zapi->zQuery("SELECT Body FROM Invoice WHERE InvoiceNumber='".$invPdfId."'");

	$body = $invResult->result->records[0]->Body;
	header("Content-type: application/pdf");
	echo (base64_decode($body));

?>