<?php
// Copyright (C) 2011 Ensoftek Inc.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// This report lists  patient immunizations for a given date range.

//addition for CAIR plug-in made by Daniel Pflieger, daniel@mi-squared.com
//Changed made on 4-5-2017 to meet new requirements for TLS1.2:

require_once("../globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/patient.inc");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/CAIRsoap.php");


//brought in
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

//brought in
function duplicateImmunizationCheck($id){

    $query = sqlQuery("select * from immunization_log where immunization_id = ?", array($id));

    return $query;


}


//brought in
function tr($a) {
    return (str_replace(' ','^',$a));
}

//brought in
function format_cvx_code($cvx_code) {

    if ( $cvx_code < 10 ) {
        return "0$cvx_code";
    }

    return $cvx_code;
}

//brought in
function format_phone($phone) {

    $phone = preg_replace("/[^0-9]/", "", $phone);
    switch (strlen($phone))
    {
        case 7:
            return tr(preg_replace("/([0-9]{3})([0-9]{4})/", "000 $1$2", $phone));
        case 10:
            return tr(preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "$1 $2$3", $phone));
        default:
            return tr("000 0000000");
    }
}

//brought in
function format_ethnicity($ethnicity) {

    switch ($ethnicity)
    {
        case "hisp_or_latin":
            return ("H^Hispanic or Latino^HL70189");
        case "not_hisp_or_latin":
            return ("N^not Hispanic or Latino^HL70189");
        default: // Unknown
            return ("U^Unknown^HL70189");
    }
}
 //brought in
function getErrorsArray($responseArray) {

    $errorArray = array();
    foreach($responseArray as $resp) {

        if(strpos($resp, 'Informational Error') === false){
            continue;
        }
        $resp = explode('ERR',$resp);
        $resp = explode('Informational Error',$resp[0]);
        $errorArray[] = substr(trim($resp[1]), 2);
    }

    return $errorArray;
}

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


if(isset($_POST['form_from_date'])) {
    $from_date = $_POST['form_from_date'] !== "" ?
        fixDate($_POST['form_from_date'], date('Y-m-d')) :
        0;
}else{

    fixDate($_POST['form_from_date'], date('Y-m-d'));
}

if(isset($_POST['form_to_date'])) {
    $to_date =$_POST['form_to_date'] !== "" ?
        fixDate($_POST['form_to_date'], date('Y-m-d')) :
        date('Y-m-d');
}else{

    fixDate($_POST['form_to_date'], date('Y-m-d'));
}
//
$form_code = isset($_POST['form_code']) ? $_POST['form_code'] : Array();
//
if (empty ($form_code) ) {
    $query_codes = '';
} else {
    $query_codes = 'c.id in (';
    foreach( $form_code as $code ){ $query_codes .= $code . ","; }
    $query_codes = substr($query_codes ,0,-1);
    $query_codes .= ') and ';
}




$query =
    "select " .
    "i.patient_id as patientid, " .
    "p.language, ".
    "i.cvx_code , " ;
if ($_POST['submit_to_CAIR']==='true') {
    $query .=
        "DATE_FORMAT(p.DOB,'%Y%m%d') as DOB, ".
        "concat(p.street, '^^', p.city, '^', p.state, '^', p.postal_code) as address, ".
        "p.country_code, ".
        "p.phone_home, ".
        "p.phone_biz, ".
        "p.status, ".
        "p.sex, ".
        "p.ethnoracial, ".
        "p.race, ".
        "p.ethnicity, ".
        "c.code_text, ".
        "c.code, ".
        "c.code_type, ".
        "DATE_FORMAT(i.vis_date,'%Y%m%d') as immunizationdate, ".
        "DATE_FORMAT(i.administered_date,'%Y%m%d') as administered_date, ".
        "i.lot_number as lot_number, ".
        "i.manufacturer as manufacturer, i.vfc, i.historical, ".
        "concat(p.lname, '^', p.fname) as patientname, ";
} else {
    $query .= "concat(p.lname, ' ',p.mname,' ', p.fname) as patientname, ".
        "i.vis_date as immunizationdate, DATE_FORMAT(i.administered_date,'%Y-%m-%d') as administered_date, "  ;
}
$query .=
    "i.id as immunizationid, c.code_text_short as immunizationtitle, ".
    "i.route as route, i.administration_site as site, i.update_date, i.submitted ".
    "from immunizations i, patient_data p, codes c ".
    "left join code_types ct on c.code_type = ct.ct_id ".
    "where ".
    "ct.ct_key='CVX' and ";

$query .= "i.patient_id=p.pid and ".
    $query_codes .
    "i.cvx_code = c.code and ";

if($_POST['immunization_id'] != ""){


        $query .= " i.id = ".$_POST['immunization_id']. " and ";


}else {



        $query .= " DATE_FORMAT(i.administered_date,'%Y-%m-%d') >= '$from_date' ";

        $query .= " and ";

        $query .= "DATE_FORMAT(i.administered_date,'%Y-%m-%d') <= '$to_date' and ";

}

//do not show immunization added erroneously
$query .=  "i.added_erroneously = 0";



//echo "<p> DEBUG query: $query </p>\n"; // debugging


$D="\r";
$delimiter = "------------------------------------------------------------------------------------------------------------------";
$nowdate = date('Ymdhms');
$now = date('YmdGi');
$now1 = date('Y-m-d G:i');
$filename = "imm_reg_". $now . ".hl7";

// GENERATE HL7 FILE
// Initalize variables.

//These can be removed once globals is set up.



if ($_POST['submit_to_CAIR']==='true') {
    $content = '';

    $res = sqlStatement($query);

    /*
     * This is the beginning of the HL7 message. The following fields that are
     * completed and verified will be noted with OK.
     * Others will be noted as UNVERIFIED and listed here.
     *
     * MSH = OK
     *
     * To do: Verify the following fields:
     *
     */

    $first = true;
    while ($r = sqlFetchArray($res)) {
        if($r['submitted'] === '1') continue;
        $content = '';
        $content .=
            "MSH|".     //1. Field Seperator R OK
            "^~\&|".    //2. Encoding Characters R OK
            "OPENEMR|". //3. Sending App optional OK
            $GLOBALS['IMM_sendingfacility']."|". //4. Sending facility OK
            "|".        //5. receiving application Ignored  OK
            $GLOBALS['IMM_receivingfacility']."|". //6. Receiving Facility OK
            $nowdate."|". //7. date/time message OK
            "|".       //8. Security - ignored OK
            $GLOBALS['IMM_messageType']."|". // 9. message type - Required OK
            date('Ymdhms').$r['patientid'].preg_replace("/[^A-Za-z0-9 ]/", '', $r['immunizationtitle'])."|".  //  OK 10. Message control ID (must be unique for a given day) Required
            "P|". //11. Processing ID (Only value accepted is �P� for production. All other values will cause themessage to be rejected.)
            $GLOBALS['IMM_hl7versionID'] . "|". //12 Version ID (2.5.1 as of current) OK
            "|".     //13. Sequence number
            "|".     //14. Continuation pointer
            "NE|".     //15. Accept acknowledgment type
            "AL|".     //16. Application acknowledgment type
            "|".     //17. Country code
            "|".     //18. Character set
            "|".     //19. Principal language of message
            "|".     //20. Alternate character set handling scheme
            "Z23^CDCPHINVS|".     //21. Sites may use this field to assert adherence to, or reference, a message profile
            $GLOBALS['IMM_CAIR_ID'] . $D;     //22. Responsible Sending Org

        if ($r['sex']==='Male') $r['sex'] = 'M';
        if ($r['sex']==='Female') $r['sex'] = 'F';
        if ($r['sex'] != 'M' && $r['sex'] != 'F') $r['sex'] = 'U';
        if ($r['status']==='married') $r['status'] = 'M';
        if ($r['status']==='single') $r['status'] = 'S';
        if ($r['status']==='divorced') $r['status'] = 'D';
        if ($r['status']==='widowed') $r['status'] = 'W';
        if ($r['status']==='separated') $r['status'] = 'A';
        if ($r['status']==='domestic partner') $r['status'] = 'P';

        $content .= "PID|" . // [[ 3.72 ]]
            $r['immunizationid']."|" . // 1. Set id (For logging purposes, this is going to be the ID of the immuniation in the immunizations table
            "|" . // 2. (B)Patient id
            $r['patientid']. "^^^SantiagoPeds^PI" . "|". // 3. (R) Patient indentifier list. OK
            "|" . // 4. (B) Alternate PID
            $r['patientname']."|" . // 5.R. Name OK
            "|" . // 6. Mather Maiden Name OK
            $r['DOB']."|" . // 7. Date, time of birth OK
            $r['sex']."|" . // 8. Sex OK
            "|" . // 9.B Patient Alias OK
            "2106-3^" . $r['race']. "^HL70005" . "|" . // 10. Race // Ram change
            $r['address'] . "^^M" . "|" . // 11. Address. Default to address type  Mailing Address(M)
            "|" . // 12. county code
        "^PRN^^^^" . format_phone($r['phone_home']) . "|" . // 13. Phone Home. Default to Primary Home Number(PRN)
        "^WPN^^^^" . format_phone($r['phone_biz']) . "|" . // 14. Phone Work.
        "|" . // 15. Primary language
        $r['status']."|" . // 16. Marital status
        "|" . // 17. Religion
        "|" . // 18. patient Account Number
        "|" . // 19.B SSN Number
        "|" . // 20.B Driver license number
        "|" . // 21. Mothers Identifier
        "^^|" . // 22. Ethnic Group
        "|" . // 23. Birth Plase
        "|" . // 24. Multiple birth indicator
        "|" . // 25. Birth order
        "|" . // 26. Citizenship
        "|" . // 27. Veteran military status
        "|" . // 28.B Nationality
        "|" . // 29. Patient Death Date and Time
        "|" . // 30. Patient Death Indicator
        "|" . // 31. Identity Unknown Indicator
        "|" . // 32. Identity Reliability Code
        "|" . // 33. Last Update Date/Time
        "|" . // 34. Last Update Facility
        "|" . // 35. Species Code
        "|" . // 36. Breed Code
        $D;

        //PD1
        if (!isset($r['data_sharing']))
            $r['data_sharing'] = "N";

        if (!isset($r['data_sharing_date']))
            $r['data_sharing_date'] = '';

        $content .= "PD1|".
            "|". // 1. living dependency
            "|". // 2. living arrangment
            "^^^^^^^^^".$GLOBALS['IMM_CAIR_ID']."|". // 3. Patient primary facility (R)
            "|". // 4. Primary care provider (can be empty)
            "|". // 5. Student Indicator
            "|". // 6. Handicap
            "|". // 7. Living Will
            "|". // 8. Organ Donor
            "|". // 9. Seperate bill
            "|". // 10. Duplicate Patient
            "^^^|". // 11. Publicity Code (may be empty)
            $r['data_sharing']."|". // 12. Protection Indicator (R)
            $r['data_sharing_date']. // 13. Protection Indicator effective date
            "|". // 14. Place of worship
            "|". // 15. Advance directive code
            "|". // 16. Immunization registry status
            "|". // 17. Immunization registry status effective date
            "|". // 18. Publicity code effective date
            $D ;


        $content .= "ORC" . // ORC mandatory for RXA
            "|" .
            "RE|" . //1. Order Control
            "|" . //2. Placer Order Number
            "|" . //3. Filler Order Number
            "|" . //4. Placer Group Number
            "|" . //5. Order Status
            "|" . //6. Response Flag
            "|" . //7. Quantity/Timing
            "|" . //8. Parent
            "|" . //9. Date/Time of Transaction
            "|" . //10. Entered By
            "|" . //11. Verified By
            "|" . //12. Ordering Provider
            "|" . //13. Enterer's Location
            "|" . //14. Call Back Phone Number
            "|" . //15. Order Effective Date/Time
            "|" . //16. Order Control Code Reason
            "|" . //17. Entering Organization
            "|" . //18. Entering Device
            "|" . //19. Action By
            "|" . //20. Advanced Beneficiary Notice Code
            "|" . //21. Ordering Facility Name
            "|" . //22. Ordering Facility Address
            "|" . //23. Ordering Facility Phone Number
            "|" . //24. Ordering Provider Address
            "|" . //25. Order Status Modifier
            "|" . //26. Advanced Beneficiary Notice Override Reason
            "|" . //27. Filler's Expected Availability Date/Time
            "|" . //28. Confidentiality Code
            "|" . //29. Order Type
            "|" . //30. Enterer Authorization Mode
            "|" . //31. Parent Universal Service Identifier
            $D;

        //RXA details:
        if($r['historical'] == '0')
            $r['historical'] = '00';
        if($r['historical'] == '1')
            $r['historical'] = '01';

        $content .= "RXA|" .
            "0|" . // 1. Give Sub-ID Counter
            "1|" . // 2. Administrattion Sub-ID Counter
            $r['administered_date']."|" . // 3. Date/Time Start of Administration
            $r['administered_date']."|" . // 4. Date/Time End of Administration
            format_cvx_code($r['code']). "^" . $r['immunizationtitle'] . "^" . "CVX" ."|" . // 5. Administration Code(CVX)
            "999|" . // 6. Administered Amount. TODO: Immunization amt currently not captured in database, default to 999(not recorded)**********************
            "mL|" . // 7. Administered Units
            "|" . // 8. Administered Dosage Form
            $r['historical']."^^NIP001|" . // 9. Administration Notes (determines if from an historical record or new immunization)
            "|" . // 10. Administering Provider
            "^^^".$GLOBALS['IMM_CAIR_ID']."|" . // 11. Administered-at Location
            "|" . // 12. Administered Per (Time Unit)
            "|" . // 13. Administered Strength
            "|" . // 14. Administered Strength Units
            $r['lot_number']."|" . // 15. Substance Lot Number
            "|" . // 16. Substance Expiration Date
            "MSD" . "^" . $r['manufacturer']. "^" . "HL70227" . "|" . // 17. Substance Manufacturer Name
            "|" . // 18. Substance/Treatment Refusal Reason
            "|" . // 19.Indication
            "|" . // 20.Completion Status
            "A" . // 21.Action Code - RXA
            "$D" ;


        $content .= "RXR|" .
            $r['route']."^^HL70162^^^" . "|" .     //1. Route, required but may be empty
            $r['site']."^^HL70163^^^" . "|" .                 //2. Site.  required, but may be empty
            "|" .                 //3. administration device - ignored
            "|" .                 //4. administration method - ignored
            "|" .                 //5. routing instruction - ignored
            "$D";


        $content .= "OBX|" .
            "1|".              // 1. Set ID - OBX (required)
            "CE|".            // 2. Value Type (required)
            "64994-7^^LN^^^|".              //3. Observation Identifier Required if RXA-9 value is '00'(required)
            "|".              //4. Observation Sub-Id (required)
            $r['vfc']."^^|".              //5. Observation Value (required)
            "|".              //6. Units (ignored)
            "|".              //7 Reference Ranges (ignored)
            "|".              //8 Abnormal flags (ignored)
            "|".              //9 Probability (ignored)
            "|".              //10 Nature of Abnormal test (ignored)
            "F|".              //11 Observsation Result Status (Required)
            "|".              //12 eff date of ref range values (ignored)
            "|".              //13 User defined access Checks (ignored)
            "|".              //14 Date/Time of the Observation (required, but may be empty)
            "||||||||||".        //15-25 ignored.
            "$D";

        $content_str .= $first ? $content : "\n\n" . $delimiter . "\n\n" . $content;
        $first = false;


    }


    $res = sqlStatement($query);
    while ($r = sqlFetchArray($res)) {
        $res_array[] =  $r;
    }
    $immunization_array = explode($delimiter, $content_str);

    $errors = '';
    $imm_ct = 0;
    if($immunization_array[0] !== "") {
        foreach ($immunization_array as $key => $immunization) {

            $imm_ct++;
            $immunization = trim($immunization);
            $wdsl = $IMM['wsdl_url'];


            try {
                $cairResponse = $cairSOAP->submitSingleMessage($immunization);

            } catch (Exception $e) {
                echo $e->getMessage();
                echo $imm_ct;
                exit;
            }
            //get info about rejected patient


            $response = explode("|", $cairResponse->return);
            $errorsArray = getErrorsArray($response);

            //expand the PID string to get imm id
            $parsed = get_string_between($immunization, "PID|", "\n");
            $exploded = explode("|", $parsed);

            $id = $exploded[0];

            if (empty($errorsArray)) {
                $success++;
                $submitted = 1;


            } else {


                //get the pid

                $PID = explode("^", $exploded[2]);
                $error_pid = $PID[0];

                //get first and last names
                $PID = explode("^", $exploded[4]);
                $lastname = $PID[0];
                $firstname = $PID[1];

                //get the administered date
                $parsed = get_string_between($immunization, "RXA|", "\n");
                $parsed = explode('|', $parsed);
                $administered_date = $parsed[3];


                $errors .= $delimiter . "<br>";
                $errors .= "PID: $error_pid <br>";
                $errors .= "LastName: $lastname <br>";
                $errors .= "FirstName: $firstname <br>";
                $errors .= "Immunization Date: $administered_date <br>";

                $errors .= 'Message "' . $res_array[$key]['immunizationtitle'] . '" contains errors:<br>';
                $errors .= '<ul>';
                $logerror = '';
                foreach ($errorsArray as $error) {
                    $logerror .= $error . " ";
                    $errors .= '<li>' . $error . '</li>';
                }
                $errors .= '</ul>';

                $failures++;
                $submitted = 0;
            }

            //We mark the immunization as sent
            $today = date("Y-m-d H:i:s");
            $sql = "Update immunizations set submitted = ? where id = ?";
            $res = sqlStatement($sql, array($submitted, $id));

            //We insert data into the immunization log.
            $sql2 = "Insert into immunization_log(`immunization_id`, `submit_date`, `WDSL`, `sent_message`, " .
                " `received_message`, `error_msg`) values(?,?,?,?,?,?) ";
            $res2 = sqlStatement($sql2, array($id, $today, $wdsl, $immunization, $cairResponse->return, $logerror));

        }

    }
}


?>

<html>
<head>
    <?php html_header_show();?>
    <title><?php xl('CAIR Portal: Submission','e'); ?></title>
    <style type="text/css">@import url(../../library/dynarch_calendar.css);</style>
    <script type="text/javascript" src="../../library/dialog.js"></script>
    <script type="text/javascript" src="../../library/textformat.js"></script>
    <script type="text/javascript" src="../../library/dynarch_calendar.js"></script>
    <?php include_once("{$GLOBALS['srcdir']}/dynarch_calendar_en.inc.php"); ?>
    <script type="text/javascript" src="../../library/dynarch_calendar_setup.js"></script>
    <script type="text/javascript" src="../../library/js/jquery-2.0.3.min.js"></script>
    <script language="JavaScript">
        <?php require($GLOBALS['srcdir'] . "/restoreSession.php"); ?>
    </script>

    <link rel='stylesheet' href='<?php echo $GLOBALS['css_header'] ?>' type='text/css'>
    <style type="text/css">
        /* specifically include & exclude from printing */
        @media print {
            #report_parameters {
                visibility: hidden;
                display: none;
            }
            #report_parameters_daterange {
                visibility: visible;
                display: inline;
                margin-bottom: 10px;
            }
            #report_results table {
                margin-top: 0px;
            }

            #immunization_results table {
                margin-top: 0px;
            }
        }
        /* specifically exclude some from the screen */
        @media screen {
            #report_parameters_daterange {
                visibility: hidden;
                display: none;
            }
            #report_results {
                width: 100%;
            }

            #immunization_results {
                width: 100%;
            }
        }



    </style>
</head>

<body class="body_top">

<span class='title'><?php xl('Report','e'); ?> - <?php xl('Immunization Registry','e');  ?></span>

<div id="report_parameters_daterange">
    <?php echo date("d F Y", strtotime($form_from_date)) ." &nbsp; to &nbsp; ". date("d F Y", strtotime($form_to_date)); ?>
</div>
<div name="display_wdsl" id="display_wdsl"><?php echo $cairSOAP->displayTargetWdsl() ?></div>
<form name='theform' id='theform' method='post' action='immunization_submit_view.php'
      onsubmit='return top.restoreSession()'>
    <div id="report_parameters">
        <input type='hidden' name='form_refresh' id='form_refresh' value=''/>
        <input type='hidden' name='submit_to_CAIR' id='submit_to_CAIR' value=''/>
        <table>
            <tr>
                <td width='410px'>
                    <div style='float:left'>
                        <table class='text'>
                            <tr>
                                <td class='label'>
                                    <?php xl('Filter By','e'); ?>:
                                </td>
                                <td>
                                    <div id="checkbox-container">
                                        <input class = "sent" type="checkbox" id="failed" name="failed"    > Rejected by CAIR<br>
                                        <input class = "sent" type="checkbox" id="success" name="success"  > Accepted by CAIR<br>
                                        <input type="checkbox" id="immunizations" name="immunizations"> Immunizations<br>
                                    </div>
                                </td>

                                <td class='label'>
                                    <?php xl('From','e'); ?>:
                                </td>
                                <td>
                                    <?php if(!isset($form_from_date)){
                                        $form_from_date = date("Y-m-d");
                                    }?>
                                    <input type='text' name='form_from_date' id="form_from_date"
                                           size='10' value='<?php echo $form_from_date ?>'
                                           onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)'
                                           title='yyyy-mm-dd'>
                                    <img src='../pic/show_calendar.gif' align='absbottom'
                                         width='24' height='22' id='img_from_date' border='0'
                                         alt='[?]' style='cursor:pointer'
                                         title='<?php xl('Click here to choose a date','e'); ?>'>
                                </td>
                                <td class='label'>
                                    <?php xl('To','e'); ?>:
                                </td>
                                <td>
                                    <input type='text' name='form_to_date' id="form_to_date"
                                           size='10' value='<?php echo $form_to_date ?>'
                                           onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)'
                                           title='yyyy-mm-dd'>
                                    <img src='../pic/show_calendar.gif' align='absbottom'
                                         width='24' height='22' id='img_to_date' border='0'
                                         alt='[?]' style='cursor:pointer'
                                         title='<?php xl('Click here to choose a date','e'); ?>'>
                                </td>
                            </tr>
                            <tr>

                                <td class='label'>
                                  <?php xl('Immunization&nbsp;ID','e'); ?>
                                </td>
                                <td class="text-box">
                                    <input type='text' name='immunization_id' id="immunization_id" >
                                </td>
                            </tr>
<!--                            <tr>-->
<!--                                <td class='label'>-->
<!--                                    --><?php //xl('Last Name','e'); ?><!--:-->
<!--                                </td>-->
<!--                                <td class="text-box">-->
<!--                                    <input type='text' name='lname' id="lname" >-->
<!--                                </td>-->
<!--                            </tr>-->
<!--                            <tr>-->
<!--                                <td class='label'>-->
<!--                                    --><?php //xl('First Name','e'); ?><!--:-->
<!--                                </td>-->
<!--                                <td class="text-box">-->
<!--                                    <input type='text' name='fname' id="fname" >-->
<!--                                </td>-->
<!--                            </tr>-->

                        </table>
                    </div>
                </td>
                <td align='left' valign='middle' height="100%">
                    <table style='border-left:1px solid; width:100%; height:100%' >
                        <tr>
                            <td>
                                <div style='margin-left:15px'>
                                    <a href='#' class='css_button'
                                       onclick='
            $("#form_refresh").attr("value","true"); 
            $("#submit_to_CAIR").attr("value","false"); 
            $("#theform").submit();
            '>
            <span>
              <?php xl('Search','e'); ?>
            </span>
                                    </a>
                                    <?php if ($_POST['form_refresh']) { ?>
                                        <a href='#' class='css_button' onclick='window.print()'>
                <span>
                  <?php xl('Print','e'); ?>
                </span>
                                        </a>
                                        <a href='#' class='css_button' onclick=
                                        "if(confirm('<?php xl('This will submit the listed immunizations to CAIR.  Proceed?','e'); ?>')) {
                                            $('#submit_to_CAIR').attr('value','true');
                                            $('#theform').submit();
                                            }">
                <span>
                  <?php xl('Send to CAIR','e'); ?>
                </span>
                                        </a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td></td>
                            <td>
                                <p>Alert:  Improvements comming soon! </p>
                                <p>Improved Reporting and Immediate submission of immunizations when saved!!</p>
                                <p>The ability to query CAIR and get patients CAIR history using OpenEMR coming soon too!!</p>

                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div> <!-- end of parameters -->
</form>


<?php
if ($_POST['form_refresh'] && $_POST['immunizations']) {
    ?>
    <div id="report_results">
        <table>
            <thead align="left">
            <th> <?php xl('Immunization ID','e'); ?> </th>
            <th> <?php xl('Patient ID','e'); ?> </th>
            <th> <?php xl('Patient Name','e'); ?> </th>
            <th> <?php xl('Immunization Code','e'); ?> </th>
            <th> <?php xl('Immunization Title','e'); ?> </th>
            <th> <?php xl('Immunization Date','e'); ?> </th>
            <th> <?php xl('Administration Site','e'); ?> </th>
            <th> <?php xl('Submitted: <br> 0:rejected <br> 1:passed','e'); ?> </th>
            </thead>
            <tbody>
            <?php
            $total = 0;
            //echo "<p> DEBUG query: $query </p>\n"; // debugging
            $res = sqlStatement($query);


            while ($row = sqlFetchArray($res)) {

            $duplicate = duplicateImmunizationCheck($row['immunizationid']);
            if($duplicate){$color = 'red';} else $color = 'black'
                ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($row['immunizationid']) ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['patientid']) ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['patientname']) ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['cvx_code']) ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['immunizationtitle']) ?>
                    </td>
                    <td>
                        <?php echo $row['administered_date'] ?>
                    </td>

                    <td>
                        <?php
                        $route = htmlspecialchars($row['route']);

                        if (strlen($route) > 2 || strlen($route) < 2 )
                            echo '<div style="background-color:red">'. $route. '</div>';
                        else echo $route;
                        ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['submitted'])  ?>
                    </td>
                </tr>
                <?php
                ++$total;


                $HL7msg = CAIRsoap::gen_HL7_CAIR_QBP($row['patientid']);
                try {
                    $cairResponse = $cairSOAP->submitSingleMessage($HL7msg);

                } catch (Exception $e) {
                    echo $e->getMessage();

                    exit;
                }
                //get info about rejected patient
                $response = explode("|", $cairResponse->return);
                $errorsArray = getErrorsArray($response);
                //***IBH TESTING COMPLETE
            }
            ?>
            <tr class="report_totals">
                <td colspan='9'>
                    <?php xl('Total Number of Immunizations','e'); ?>
                    :
                    <?php echo $total ?>
                </td>
            </tr>

            </tbody>
        </table>
    </div> <!-- end of results -->

<?php }else if($_POST['failed'] || $_POST['success']){

    ?>






    <div id="report_results">
        <script language='JavaScript'> $("#submit_to_CAIR").attr("value",false);  </script>
        <table>
            <thead align="left">
            <th> <?php xl('Immunization&nbsp;ID&nbsp;','e'); ?> </th>
            <th> <?php xl('Patient&nbsp;ID','e'); ?> </th>
            <th> <?php xl('Last&nbsp;Name&nbsp;','e'); ?> </th>
            <th> <?php xl('Submit&nbsp;Date&nbsp;','e'); ?> </th>
            <th> <?php xl('WDSL','e'); ?> </th>
            <th> <?php xl('Error&nbsp;Message','e'); ?> </th>
            </thead>
            <tbody>

                 <?php
                $immunization_log_report = "Select immunization_log.*, immunizations.patient_id, lname from immunization_log ";
                $immunization_log_report .="JOIN immunizations on immunizations.id = immunization_log.immunization_id ";
                 $immunization_log_report .="JOIN patient_data on patient_data.pid = immunizations.patient_id ";
                $immunization_log_report .= " Where ";

                 if($_POST['immunization_id'] != ""){
                     $immunization_log_report .= " immunization_log.immunization_id = ".$_POST['immunization_id']. " and ";
                 }

                 if($_POST['failed']){
                    $immunization_log_report .= " error_msg is not null and immunizations.submitted = 0";
                }else if($_POST['success']){
                    $immunization_log_report .= " error_msg is null";
                }

                $res = sqlStatement($immunization_log_report);
                while ($row = sqlFetchArray($res)) {
                    ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($row['immunization_id']) ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['patient_id']) ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($row['lname']) ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['submit_date']) ?>
                        </td>
                        <td>
                            <?php if(strpos($row['WDSL'], 'TRN'))echo htmlspecialchars('Training');
                            else  if(strpos($row['WDSL'], 'PRD'))echo htmlspecialchars('Production');
                            else echo htmlspecialchars('Broken Link!'); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['error_msg']) ?>
                        </td>
                    </tr>
                 <?php } ?>
                </tbody>

        </table>

        </div><!--end of results-->
        <?php

} else if ($_POST['submit_to_CAIR'] && $immunization_array !== ""){



            echo " You have successfuly entered in $success immunizations.  "; ?> <br> <?php
            echo " There were $failures submissions that have failed. ";
            if($errors) {
                echo '<br><span style="color: #ffc857">' .$errors . '</span>';
            }
            if ($failures > 0) echo "Please check your email account that CAIR communicates with you to get the reason. ";

    }else{ ?>
        <div class='text'>
            <?php echo xl('Click Refresh to view all results, or please input search criteria above to view specific results.', 'e' ); ?>
        </div>
    <?php } ?>

    <?php


//display report if any box is checked
//display sent immunizations


?>











<script language='JavaScript'>




    Calendar.setup({inputField:"form_from_date", ifFormat:"%Y-%m-%d", button:"img_from_date"});
    Calendar.setup({inputField:"form_to_date", ifFormat:"%Y-%m-%d", button:"img_to_date"});




    //Keep the state of checkboxes remembered.
    var checkboxValues = JSON.parse(localStorage.getItem('checkboxValues')) || {};
    var $checkboxes = $("#checkbox-container :checkbox");

    $checkboxes.on("change", function(){




        $checkboxes.each(function(){
            checkboxValues[this.id] = this.checked;
        });
        localStorage.setItem("checkboxValues", JSON.stringify(checkboxValues));

        //if immunizations change, clear all other boxes



    });

    $('#immunizations').click(function(){
        if (this.checked) {

            $('#failed').prop('checked', false);
            $('#success').prop('checked', false);


        }
    });

    $('.sent').click(function(){
        if (this.checked) {
            $('#immunizations').prop('checked', false);


        }
    })

    $.each(checkboxValues, function(key, value) {
        $("#" + key).prop('checked', value);
    });




</script>




</body>
</html>
