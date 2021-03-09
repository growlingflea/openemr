<?php

////////////////////////////////////////////////////////////////////
// Package:	CAIR Immunization Portal
// Purpose:	cron job grabs the immunization history of all patients who have appointments the following day
//
//
//
// Copyright (C) 2020 -Daniel Pflieger
// daniel@growlingflea.com daniel@mi-squared.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
////////////////////////////////////////////////////////////////////

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
$today = $testing ? date('Y-m-d', strtotime(date('Y-m-d') . '+2 days')) : date('Y-m-d');
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

//Get all the appointments of the day
$query ="SELECT pd.pid, pd.CAIR, pd.title,pd.fname,pd.lname,pd.mname,pd.phone_cell,pd.email,pd.hipaa_allowsms,pd.hipaa_allowemail, " .
    "pd.pid,ope.pc_eid,ope.pc_pid,ope.pc_title, " .
    "ope.pc_hometext,ope.pc_eventDate,ope.pc_endDate, " .
    "ope.pc_duration,ope.pc_alldayevent,ope.pc_startTime,ope.pc_endTime " .
    "FROM " .
    "openemr_postcalendar_events ope " .
     "join patient_data pd on ope.pc_pid=pd.pid " .
    " WHERE " .
    " pc_eventDate = '{$today}' " .
    " order by " .
    " ope.pc_eventDate,ope.pc_endDate,pd.pid ";

$res = sqlStatement($query);

while($row = sqlFetchArray($res)){

    echo "<br> Result: {$row['fname']} and {$row['lname']} have the CAIR ID of {$row['CAIR']}";
    $qbp_string = $cairSOAP::gen_HL7_CAIR_QBP($row['pid'], 'query');
    echo "<br> " . $qbp_string . "<br>";

    try {

        $cairResponse = $cairSOAP->submitSingleMessage($qbp_string);


    } catch (Exception $e) {

        echo $e->getMessage();


    }

    echo "<br> QBP RSP = " . $cairResponse->return . "<br>";
    $sql2 = "Insert into `immunization_QBP_log` values(?,?,?,?,?,?,?,?)";
    $res2 = sqlStatement($sql2, array('', $row['pid'], $row['CAIR'], $date , $IMM['wsdl_url'], $qbp_string, $cairResponse->return , ''  ));
    echo "<br> QBP for {$row['pid']} complete <br>";
}


echo "<br>complete<br>";












?>