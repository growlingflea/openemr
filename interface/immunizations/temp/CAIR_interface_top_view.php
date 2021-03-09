<?php

// Copyright (C) 2019 Growlingflea Software.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// This report lists the immunizations that are in the CAIR database.

//addition for CAIR plug-in made by Daniel Pflieger, daniel@mi-squared.com
//Changed made on 4-5-2017 to meet new requirements for TLS1.2:

require_once("../../globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/patient.inc");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/CAIRsoap.php");

use OpenEMR\Core\Header;

$IMM = array('username' => $GLOBALS['IMM_sendingfacility'],
    'password' => $GLOBALS['IMM_password'],
    'facility' => $GLOBALS['IZ_portal_sending_facility_ID'],
    'wsdl_url' => $GLOBALS['wsdl_url']);

//Initalize variables for counting good records

$success = 0;
$failures = 0;



//brought in
?>
<html>
<head>
    <?php html_header_show();?>
    <?php if($_GET['action'] === 'query'){ ?>
    <title><?php xl('CAIR Portal: Query','e'); ?></title>

    <?php } else { ?>

    <title><?php xl('CAIR Portal: Submit', 'e'); ?></title>


   <?php  } ?>

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

        td.data {

            padding-left:25px;

        }

        .smtitle{
            font-weight: bold;
            text-align: right;
        }
        }

        .title2{
            font-weight: bold;
            text-align: left;
        }

        .data{

            text-align: left;
        }
    </style>


</head>

<body class="body_top">


<?php
//For 501

Header::setupHeader(['opener', 'report-helper', 'dialog', 'jquery', 'bootstrap', 'datatables', 'datatables-buttons',
    'datatables-buttons-html5',]);

//Parse the patient data to make sure the data is correct.
//we can have a direct link so the end user can edit the info if wrong
$cairSOAP = new CAIRsoap();
$isInitalized = $cairSOAP -> setFromGlobals($IMM)->initializeSoapClient();
$lastImm = $cairSOAP::getNewImmID($pid);
//Generate the QBP message and send it to CAIR

//get projections




?>
    <div class="container-fluid" id="whole_text">

        <div class="container-fluid">
           <?php include_once("CAIR_patient_view.php"); ?>
        </div>
        <br>

<?php if($_GET['action'] === 'query'){ ?>

    <div class="container-fluid">
        <?php include_once("CAIR_immunization_hist_view.php"); ?>
    </div>
    <br>

<!--    make raw data available to admin only-->

    <?php if ($_SESSION['authUser'] == 'admin') { ?>

    <div class="container-fluid">
        <?php include_once("CAIR_raw_data_view.php"); ?>
    </div>

<!--        Projections - can only be seen by admin until improved-->
    <div class="container">
        <?php include_once("CAIR_projections_view.php"); ?>
    </div>

    <?php } ?>



<?php }else{ ?>


    <div class="container-fluid">
        <?php include_once("CAIR_immunization_submit_view.php"); ?>
    </div>



<?php } ?>

    </div>


<script language="javascript" type="text/javascript">

    $('#patient_info').on('click', function(){

        $('.rsp').toggle();


    });







</script>

<!-- The button used to copy the text -->


</body>
</html>








