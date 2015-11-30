<?php

  function __autoload($class){
  @include('./model/' . $class . '.php');
  @include('./controller/' . $class . '.php');
  }
  //send the user back to the select_products page when completed
  header("location: ../account_view.html");

  session_start();

  global $messages;

  $zapi;
  try {
    $zapi = new zApi();
  } catch (Exception $e) {
    throw new Exception("INVALID_ZLOGIN");
  }

  $accId = $_SESSION['accountId'];

  //Get Default Payment Method Id for this accountl
  $defaultPmId;
  $accResult = $zapi->zQuery("SELECT DefaultPaymentMethodId FROM Account WHERE Id='".$accId."'");

  if($accResult->result->size==0){
    // throw new Exception('SUBSCRIPTION_DOES_NOT_EXIST');
    return 'PAYMENT_METHOD_DOES_NOT_EXIST';
  }
  foreach($accResult->result->records as $acc){
    $defaultPmId = $acc->DefaultPaymentMethodId;
  }

  date_default_timezone_set('America/Los_Angeles');
  $date = date('Y-m-d\TH:i:s');

  $payAmt = (float)$_POST["pay_amt"];
  $invId = $_POST["inv_id"];
  $invBal = (float)$_POST["inv_bal"];

  echo "<br>inv_num: ". $invId;
  echo "<br>pay_amt: ". $payAmt;
  echo "<br>inv_bal: ". $invBal;

  if ($payAmt > $invBal) {
    $credBal = $payAmt - $invBal;
    $appliedPayAmt = $invBal;
  } else {
    $credBal = (float)0.00;
    $appliedPayAmt = $payAmt;
  }

  $myInvoicePayment = array(
      "Amount" => $appliedPayAmt,
      "InvoiceId" => $invId,
    );

  $invoicePayments = array(
    "InvoicePayment" => $myInvoicePayment,
  );

  $newPayment = array(
    // 'Amount' => $payAmt,
    'AccountId' => $accId,
    'AppliedCreditBalanceAmount' => $credBal,
    // 'AppliedInvoiceAmount' => $appliedPayAmt,
    'EffectiveDate' => $date,
    // 'InvoiceId' => $invId,
    'PaymentMethodId' => $defaultPmId,
    'Type' => 'Electronic',
    'Status' => 'Processed',
    'InvoicePaymentData' => $invoicePayments,
  );

  $objs = array($newPayment);

  try {
    $createPaymentResult = $zapi->zCreateUpdate('create',$objs,'Payment');
  } catch (Exception $e){
    array_push($messages, "Create Exception.");
    array_push($messages, "Exception: " . $e->getMessage());
  }

  $responseArray = $createPaymentResult->result[0];

  return $createPaymentResult->result;

?>
