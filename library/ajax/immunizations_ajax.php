<?php

/**
 * library/ajax/immunizations_ajax.php Ajax file to process immunization bar code scans
 * and use the embedded information to populate the immunization form.
 *
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
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;
$testing = false;

require_once("../../interface/globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/acl.inc");
require_once("$srcdir/CAIRsoap.php");
require_once("../../custom/code_types.inc.php");

if ($_POST['GTIN'] && $_POST['func']=="check_GTIN") {
    //check if there is an ID
    if (isset($_POST['id'])) {
        $query = "SELECT * from immunizations_schedules_codes where GTIN = ? and id = ? limit 0,1 ";
        $result = sqlStatement($query, array($_POST['GTIN'], $_POST['id']));
    } else {
        $query = "SELECT * from immunizations_schedules_codes where GTIN = ? order by id desc limit 0,1 ";
        $result = sqlStatement($query, array($_POST['GTIN']));

    }
    $row = sqlFetchArray($result);
    echo json_encode($row);



}

if ($_POST['GTIN'] && $_POST['func']=="create_GTIN" && $_POST['cvx']) {

//What happens if CVX doesn't exists

    $query = "Update immunizations_schedules_codes " .
        "set GTIN = ? " .
        "where cvx = ? ";


    $result = sqlStatement($query, array($_POST['GTIN'], $_POST['cvx']));
    return $result;

}

if ($_POST['GTIN'] && $_POST['func']=="update_GTIN") {
    $query = "Select cvx_code from immunizations_schedules_codes where GTIN = ? limit 0,1 ";
    $result = sqlStatement($query, array($_POST['GTIN']));
    $row = sqlFetchArray($result);
    echo json_encode($row);
}

if ($_POST['func'] == "lookup_code_descriptions")  {

    echo lookup_code_descriptions($_POST['codes']);

}

if ($_POST['cvx'] && $_POST['func'] == 'return_lookup_codes'){

    $query = "Select proc_codes, justify_codes, description from immunizations_schedules_codes where cvx_code  == ? ";
    $result = sqlStatement($query, array($_POST['cvx']));
    $row = sqlFetchArray($result);
    echo json_encode($row);

}

if ($_POST['func'] === 'show_immunizations'){

    $dataTable['data'] = array();

    $query = "Select id, description, manufacturer, cvx_code, proc_codes, justify_codes, default_site, " .
             "comments, drug_route, vis_date, GTIN, NDC11 from immunizations_schedules_codes";

    $result = sqlStatement($query);
    while($row = sqlFetchArray($result)){

        array_push($dataTable['data'], $row);
    }
    $josn = json_encode($dataTable);
    echo json_encode($dataTable);
}

//Handle the POSTED File
if ( isset($_FILES['file']) ) {

    if (isset($_FILES["file"])) {

        //if there was an error uploading the file
        if ($_FILES["file"]["error"] > 0) {
            echo "Return Code: " . $_FILES["file"]["error"] . "<br />";



        } else {

            $source = $_FILES["file"]["tmp_name"];


            $fh_source = fopen($source, 'r') or die("Failed to open file");
            $row = fgets($fh_source);
            $row = preg_replace("/[\n\r]/","",$row);
            $row_header = explode(',',$row);
            while( $row = fgets($fh_source)){
                $row = preg_replace("/[\n\r]/","",$row);
                $row_values = str_getcsv($row, ",", "\"");
                $sqlArray = array_combine($row_header, $row_values);

                //Don't save immunizations that don't have a GTIN
                if($sqlArray['GTIN'] === '') continue;

                //Check to see if GTIN exists and / or if there are double.
                //If there are double it means there is an issue
                if($sqlArray['GTIN'] != null) {

                    $res = sqlQuery("select count(*) as count from immunizations_schedules_codes where GTIN = ?", array($sqlArray['GTIN']));
                }

                if($res['count'] == '0'){

                    $sql =  "INSERT INTO immunizations_schedules_codes (`description`,  `cvx_code`, `comments`, `GTIN`, `NDC11`, `ndc_inner_id`)";
                    $sql .= "VALUES(?,?,?,?,?,?)";
                    $queryArray = array($sqlArray['UseUnitPropName'], $sqlArray['CVX Code'], $sqlArray['CVX Short Description'], $sqlArray['GTIN'], $sqlArray['NDC11'], $sqlArray['NDCInnerID'] );
                    sqlStatement($sql, $queryArray);

                }else{

                    if(isset($sqlArray['GTIN']) && $sqlArray['GTIN'] != null  ) {
                        $sql = "Update immunizations_schedules_codes set `description` = ?,  `cvx_code` = ?, `comments` = ?,  `NDC11` = ?, ndc_inner_id = ? where GTIN like ? ";
                        $queryArray = array($sqlArray['UseUnitPropName'], $sqlArray['CVX Code'], $sqlArray['CVX Short Description'], $sqlArray['NDC11'],  $sqlArray['NDCInnerID'], "%" . $sqlArray['GTIN'] . "%");
                        sqlStatement($sql, $queryArray);

                    }else if(isset($sqlArray['MVX'])){

                        $sql2 = "Update immunizations_schedules_codes set manufacturer = ? where ndc_inner_id = ? ";
                        $queryArray = array($sqlArray['MVX'], $sqlArray['NDCInnerID'] );
                        sqlStatement($sql2, $queryArray);
                       echo  '';
                    }
                }
            }

        }
    }

    echo "Upload Successful!! New Immunizations successfully uploaded";
}


if ($_POST['func'] === 'delete_immunization'){



    $query = "Delete from immunizations_schedules_codes " .
        "where description = ? and manufacturer = ? and cvx_code = ? and proc_codes = ?" .
        " and justify_codes = ? and default_site = ? and comments = ? and drug_route = ? ".
        " and (vis_date = ? OR vis_date is null ) and GTIN = ?";

    $queryArray = array();

    foreach($_POST['info'] as $field){

        array_push($queryArray, $field);

    }
    $success = sqlStatement($query, $queryArray);
    echo $success;
}

if(isset($_POST['immunizations_codes_edit']['GTIN'])){

    $query = "Update immunizations_schedules_codes " .
        "set description = ? " .
        ", manufacturer = ? " .
        ", cvx_code = ? " .
        ", justify_codes = ? " .
        ", proc_codes = ? " .
        ", default_site = ? " .
        ", comments = ? "  .
        ", drug_route = ? " .
        ", vis_date = ? ";

    $query .= " where GTIN = ?" ;
    $queryArray = array($_POST['immunizations_codes_edit']['description'], $_POST['immunizations_codes_edit']['manufacturer'],
    $_POST['immunizations_codes_edit']['cvx_code'], $_POST['immunizations_codes_edit']['justify_codes'], $_POST['immunizations_codes_edit']['proc_codes'],
    $_POST['default_site'], $_POST['immunizations_codes_edit']['comments'], $_POST['drug_route'], $_POST['immunizations_codes_edit']['vis_date'],
    $_POST['immunizations_codes_edit']['GTIN']);


    $success = sqlStatement($query, $queryArray);
    echo $success;

}
