<?php

$_SERVER['REQUEST_URI']=$_SERVER['PHP_SELF'];
$_SERVER['SERVER_NAME']='localhost';
$_SERVER['HTTP_HOST']='default';
$ignoreAuth = true;
$backpic = "";
date_default_timezone_set('America/Los_Angeles');
// email notification
$ignoreAuth=1;

//necessary for cronjob
chdir(__DIR__);
$testing = false;

require_once("../../interface/globals.php");
require_once("$srcdir/CAIRsoap.php");
$today = $testing ? date('Y-m-d', strtotime(date('Y-m-d') . '-1 days')) : date('Y-m-d');
$date = date('Y-m-d h:i:s');

$IMM = array('username' => $GLOBALS['IMM_sendingfacility'],
    'password' => $GLOBALS['IMM_password'],
    'facility' => $GLOBALS['IZ_portal_sending_facility_ID'],
    'wsdl_url' => $GLOBALS['wsdl_url']);

//Initalize variables for counting good records

$success = 0;
$failures = 0;

$cairSOAP = new CAIRsoap();
try {
    $cairSOAP->setFromGlobals($IMM)
        ->initializeSoapClient();
}catch (Exception $e) {
    echo $e->getMessage();

    exit;
}


//we are going to submit the day's immunizations that do not have a response.
$sql = "select *, im.id as immid from immunizations im left join immunization_log ilog on im.id = ilog.immunization_id " .
    " where im.administered_date > ? and im.submitted = 0 and ilog.sent_message is null ";


$res = sqlStatement($sql, array($today));

while($row = sqlFetchArray($res) ){

    echo "<br> Result: {$row['patient_id']} ";

    $vxu_string = $cairSOAP::gen_HL7_VXU($row['patient_id'], $row['immid']);

    try{

        $cairResponseVXU = $cairSOAP->submitSingleMessage($vxu_string);

    }catch(Exception $e){


        echo $e->getMessage();

    }

    echo "<br> VXU RSP = " . $cairResponseVXU->return . "<br>";
    $sql2 = "Insert into `immunization_log` values(?,?,?,?,?,?,?)";
    $res2 = sqlStatement($sql2, array('', $row['immid'], $date , $IMM['wsdl_url'], $vxu_string, $cairResponseVXU->return , ''  ));
    echo "<br> QBP for {$row['patient_id']} complete <br>";

}