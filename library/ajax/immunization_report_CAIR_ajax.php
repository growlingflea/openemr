<?php

/**
 * library/ajax/sms_notification_log_report_ajax.php .
 *

 * Copyright (c) 2019 Growlingflea Software <daniel@growlingflea.com>
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/mpl-license.php>;.
 *
 */
$fake_register_globals = false;
$sanitize_all_escapes = true;
$testing = false;

require_once("../../interface/globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/patient.inc");
require_once("$srcdir/CAIRsoap.php");
require_once("$srcdir/datatables.inc.php");

$DateFormat = DateFormatRead();
//make sure to get the dates

//capture the date and correct it

if (!$_POST['immunization_form_from_date']) {

    $immunization_form_from_date = fixDate(date($DateFormat));

} else {
    $immunization_form_from_date = fixDate($_POST['immunization_form_from_date']);
}

if (!$_POST['immunization_form_to_date']) {

    $immunization_form_to_date = fixDate(date("$DateFormat"));

} else {

    $immunization_form_to_date = fixDate($_POST['immunization_form_to_date']);
}


if($_POST['func'] == 'server_side_immunization_report_duplicates'){

    $appt_response['data'] = array();
    $query = "Select CAIR, pd.pid , CONCAT(fname, ' ', mname, ' ', lname) as pt_name, " .
            " pd.DOB as DOB, CONCAT(street, ' ', city, ', ', state, ' ', postal_code ) as Address, " .
            "phone_home, mothersname, received_message, mothersname, '' as mothers_last, il.submit_date, il.immunization_id  from immunization_log il join immunizations i on  il.immunization_id = i.id " .
            " join patient_data pd on pd.pid = i.patient_id where received_message like '%The incoming patient matches more than one existing candidate%' " ;


    if($_POST['immunization_form_CAIR_id_only'] != 1) {
        $where = " AND " .
            " (DATE_FORMAT(i.administered_date,'%Y-%m-%d') >= '$immunization_form_from_date' " .
            " and " .
            " DATE_FORMAT(i.administered_date,'%Y-%m-%d') <= '$immunization_form_to_date') ";

        if (trim($_POST['immunization_form_CAIR_id']) != '') {

            if ($_POST['immunization_form_CAIR_id_only'] != 1) {

                $where .= "or (CAIR = {$_POST['immunization_form_CAIR_id']}) ";

            } else {

                $where = " where CAIR = {$_POST['immunization_form_CAIR_id']}) ";
            }

        }
    }else{

        $where = " AND CAIR = {$_POST['immunization_form_CAIR_id']} ";
    }

    $query .= " " . $where . " ";

    $res = sqlStatement($query);
    while($row = sqlFetchArray($res)){

        $err = CAIRsoap::ACK_to_array($row['received_message']);
        $message = '';
        foreach($err['ERR'] as $errmessage){

           $message .= " " . $errmessage['user_message'];
        }
        $row['error'] = $message;
        array_push($appt_response['data'], $row);
    }

    echo json_encode($appt_response);
}

if($_POST['func'] === "server_side_immunization_report"){

    //get an array of columns

    //get the paging parameters
    // Paging parameters.  -1 means not applicable.
    $appt_response['data'] = array();

    $displayStart  = isset($_POST['start'] )? 0 + $_POST['start'] : -1;
    $displayLength = isset($_POST['length']) ? 0 + $_POST['length'] : -1;
    $draw = $_POST['draw'];
    $columns = $_POST['columns'];

    if ($displayStart >= 0 && $displayLength >= 0) {
        $limit = "LIMIT " . escape_limit($displayStart) . ", " . escape_limit($displayLength);
    }

    $orderby = ' ';

    if($_POST['immunization_form_CAIR_id_only'] != 1) {
        $where = "where " .
            " (DATE_FORMAT(imm.administered_date,'%Y-%m-%d') >= '$immunization_form_from_date' " .
            " and " .
            " DATE_FORMAT(imm.administered_date,'%Y-%m-%d') <= '$immunization_form_to_date') ";

        if (trim($_POST['immunization_form_CAIR_id']) != '') {

            if ($_POST['immunization_form_CAIR_id_only'] != 1) {

                $where .= "or (CAIR = {$_POST['immunization_form_CAIR_id']}) ";

            } else {

                $where = " where CAIR = {$_POST['immunization_form_CAIR_id']}) ";
            }

        }
    }else{

        $where = " where CAIR = {$_POST['immunization_form_CAIR_id']} ";
    }




    /* This 'for each' statement renames columns in the search query to their original value.  Aliases used in the
    *  mySQL response that are sent to the DataTable sends the value of the alias, i.e. immid (as opposed to id).
     * The Datatable library search function uses the non-alias name 'id'.  Since this query uses multiple table joins
     * and 'id' is a common name in tables, we were getting ambiguity error from MySQL when using the search function.
     *
     * This renames the column so mySQL can understand it.  The aliases were used so Datatables could understand it.
     *
     */

    foreach($_POST['columns'] as $index => $column){

        if($column['data'] == "immid"){
            $_POST['columns'][$index]['data'] = "imm.id";

        }

        if($column['data'] == "pt_name"){
            $_POST['columns'][$index]['data'] = "CONCAT(pd.lname, ' , ', pd.fname)";

        }

        if($column['data'] == "vfc_level"){
            $_POST['columns'][$index]['data'] = "vfc";

        }

        if($column['data'] == "pid"){
            $_POST['columns'][$index]['data'] = "pd.pid";

        }
    }


    if(DATATABLES::returnWhereFromSearch($_POST['columns']))
        $where .= " AND " . DATATABLES::returnWhereFromSearch($_POST['columns']);

    //get the sorting parameters


    //todo: get the filtering of the report.

    $query = "SELECT imm.id as immid, CONCAT(pd.lname, ' , ', pd.fname) as pt_name, imm.cvx_code, imm.administered_date, " .
        " imm.manufacturer, imm.lot_number, imm.administered_by, imm.vfc as vfc_level, imm.refusal_reason, " .
        " imm.historical, pd.pid as pid, pd.pubpid, pd.dob, pd.CAIR, ct.*, c.* " .
        " from immunizations imm join patient_data pd on pd.pid = imm.patient_id " .
        " left join codes c on c.code = imm.cvx_code " .
        " left join code_types ct on ct.ct_id = c.code_type ";

    $query .= $where;
    $query .= $orderby;
    $query .= $limit;

    $total = 0;
    $res = sqlStatement($query);

    //Get the count

    $query2 = str_replace("SELECT ", "SELECT count(*) as count, ", $query );
    $count = sqlQuery($query2);
    $count = $count['count'];

    //this pulls the immunizations that have been recorded.
    while ($row = sqlFetchArray($res)) {

        //for each immunization that has been recorded, we check to see if it has been sent to CAIR
        //immunization submissions are stored in the immunization_log table.  This is known as the VXU table
        $response_query     = "Select * from immunization_log where immunization_id = $row[immid] ";
        $response_query_res = sqlStatement($response_query);
        $parsed_response = array(); //initialize the parsed response

        //get the number of times we sent the immunization
        $num = sqlNumRows($response_query_res);
        $row['cair_response_num_messages'] = $num;
        $errors = 0;
        $warnings = 0;
        if($num > 0) {

            $messages =  array();

            while ($CAIR_return_message = sqlFetchArray($response_query_res)) {


                $parsed_response                = CAIRsoap::ACK_to_array($CAIR_return_message['received_message']);
                $parsed_response['msg_id']      = $CAIR_return_message['id'];
                $parsed_response['submit_date'] = $CAIR_return_message['submit_date'];

                if($parsed_response['MSA']['ack_code'] !== 'AA') {

                    $parsed_response['error_message'] = $CAIR_return_message['error_msg'];

                }else{

                    $parsed_response['error_message'] = '';
                }


                if(strlen($parsed_response['error_message']) > 0)
                    $errors++;

                array_push($messages, $parsed_response);
            }

            $row['cair_response_messages'] = $messages;

        }else{

            $row['cair_response_messages'][0] = 0;
        }

        //todo: check these.  If error mark as NOT SENT
        $row['current_imm_cair_status'] = (int)CAIRsoap::getCurrentImmStatus($row['immid']);
        $row['immunization_attempts']   = (int)CAIRsoap::getNumAttempts($row['immid']);
        $row['immunization_failures']   = (int)CAIRsoap::getNumFailures($row['immid']);
        $row['cair_response_num_errors']= (int)$errors;
        $row['vfc'] = 'a';
        $row['ack_code'] = '';
        if(isset($parsed_response['MSA']['ack_code']))
            $row['ack_code'] = $parsed_response['MSA']['ack_code'];
        else $row['ack_code'] = "not sent";
        array_push($appt_response['data'], $row);




    }//end of while loop
    $appt_response['draw'] = $_POST['draw'];
    $appt_response['recordsTotal'] = $count;
    $test = json_encode($appt_response);
    echo json_encode($appt_response);






}


if ($_POST['func'] === "show_immunization_form_report") {

    $appt_response['data'] = array();


    $query = "SELECT imm.id as immid, CONCAT(pd.lname, ' , ', pd.fname) as pt_name, imm.cvx_code, imm.administered_date, " .
        " imm.manufacturer, imm.lot_number, imm.administered_by, imm.vfc as vfc_level, imm.refusal_reason, " .
        " imm.historical, pd.pid as pid, pd.pubpid, pd.dob, pd.CAIR, ct.*, c.* " .
        " from immunizations imm join patient_data pd on pd.pid = imm.patient_id " .
        " left join codes c on c.code = imm.cvx_code " .
        " left join code_types ct on ct.ct_id = c.code_type ";

    $query .= "where " .
        " DATE_FORMAT(imm.administered_date,'%Y-%m-%d') >= '$immunization_form_from_date' " .
        " and " .
        " DATE_FORMAT(imm.administered_date,'%Y-%m-%d') <= '$immunization_form_to_date' " .
        " order by immid desc ";

    $total = 0;
    $res = sqlStatement($query);

    //get the messages we sent and get the responses from CAIR
    while ($row = sqlFetchArray($res)) {

        $response_query     = "Select * from immunization_log where immunization_id = $row[immid] ";
        $response_query_res = sqlStatement($response_query);

        //get the number of times we sent the immunization
        $num = sqlNumRows($response_query_res);
        $row['cair_response_num_messages'] = $num;
        $errors = 0;
        $warnings = 0;
        if($num > 0) {

            $messages =  array();

            while ($CAIR_return_message = sqlFetchArray($response_query_res)) {

                $parsed_response = array();
                $parsed_response                = CAIRsoap::ACK_to_array($CAIR_return_message['received_message']);
                $parsed_response['msg_id']      = $CAIR_return_message['id'];
                $parsed_response['submit_date'] = $CAIR_return_message['submit_date'];

                if($parsed_response['MSA']['ack_code'] !== 'AA') {

                    $parsed_response['error_message'] = $CAIR_return_message['error_msg'];

                }else{

                    $parsed_response['error_message'] = '';
                }


                if(strlen($parsed_response['error_message']) > 0)
                    $errors++;

                array_push($messages, $parsed_response);
            }

            $row['cair_response_messages'] = $messages;

        }else{

            $row['cair_response_messages'][0] = 0;
        }

        //todo: check these.  If error mark as NOT SENT
        $row['current_imm_cair_status'] = CAIRsoap::getCurrentImmStatus($row['immid']);
        $row['immunization_attempts']   = CAIRsoap::getNumAttempts($row['immid']);
        $row['immunization_failures']   = CAIRsoap::getNumFailures($row['immid']);
        $row['cair_response_num_errors']= $errors;
        $row['vfc'] = 'a';

//        //***IBH TESTING
//        $HL7msg = CAIRsoap::gen_HL7_CAIR_QBP($row['pid']);

        array_push($appt_response['data'], $row);



    }//end of while loop
    $test = json_encode($appt_response);
    echo json_encode($appt_response);
} //end of      if($_POST['func']=="show_show_sms_sent") {