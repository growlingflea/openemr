<?php
/**
 * library/ajax/sms_notification_log_report_ajax.php .
 *
 * Copyright (C) 2012 Medical Information Integration <info@mi-squared.com>
 * Copyright (c) 2018 Growlingflea Software <daniel@growlingflea.com>
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
$fake_register_globals=false;
$sanitize_all_escapes=true;
$testing = false;

require_once("../../interface/globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/patient.inc");
$DateFormat = DateFormatRead();
//make sure to get the dates

//capture the date and correct it

if ( ! $_POST['from_date']) {

    $from_date = fixDate(date($DateFormat));

} else {
    $from_date = fixDate($_POST['from_date']);
}

if ( !$_POST['to_date']) {

    $to_date = fixDate(date("$DateFormat"));

} else{

    $to_date = fixDate($_POST['to_date']);
}


if($_POST['func']==="show_appointments") {

    $appt_response['data'] = array();

    $query = "SELECT pt.id as 'id', concat(lname, ', ', fname) as name, start_datetime as date, pt.apptdate, pt.appttime, pt.pid, " .
        "original_user, user, pte.seq, pte.status, lo.title " .
        "FROM `patient_tracker` pt join patient_tracker_element pte on pt.id = pte.pt_tracker_id " .
        "join patient_data pd on pd.pid = pt.pid " .
        "join list_options lo on lo.option_id = pte.status and lo.list_id = 'apptstat' " ;

    $query .= " Where pt.apptdate >= '$from_date' ";
    $query .= " AND pt.apptdate <= '$to_date' ";
    $query .= ' and (lo.title like "%rrived%" or lo.title like "%ancel%" or lo.title like "%hecked%")';


    $total = 0;
    //echo "<p> DEBUG query: $query </p>\n"; // debugging
    $res = sqlStatement($query);

    while ($row = sqlFetchArray($res)) {


        array_push($appt_response['data'], $row);

    }//end of while loop
    $test = json_encode($appt_response);
    echo json_encode($appt_response);
} //end of      if($_POST['func']=="show_show_sms_sent") {