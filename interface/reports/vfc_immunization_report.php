<?php
// Copyright (C) 2014 -Tony McCormick
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//SANITIZE ALL ESCAPES
$sanitize_all_escapes=true;
//

//STOP FAKE REGISTER GLOBALS
$fake_register_globals=false;

require_once("../globals.php");
require_once("../../library/forms.inc");
require_once("../../library/patient.inc");


$alertmsg = ''; // not used yet but maybe later

$form_from_date = fixDate($_POST['form_from_date'], '2017-01-01');
$form_to_date = fixDate($_POST['form_to_date'], date('Y-m-d'));
$is_download = $_POST['download'] != null;


if ( $is_download) {

    // Customer_ID = MemberPID

    $query = "select i.administered_date,  ";

    if($_POST['name_include']){ $query .= "  patient_id, lname, fname, ";}

    $query .= " isc.cvx_code, isc.manufacturer, isc.description, isc.drug_route, i.vfc from immunizations i
              join immunizations_schedules_codes isc on i.cvx_code = isc.cvx_code
              join patient_data pd on i.patient_id = pd.pid
              where i.vfc != 'V01' ";
    $query .= " AND i.administered_date >= ? AND i.administered_date <= ? ";


    $sqlresults = sqlStatement($query, array($form_from_date, $form_to_date));

    if ( $sqlresults ) {

        $fileName = 'vfc_immunization_report_' . $form_to_date . '.csv';

        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Description: File Transfer');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$fileName}");
        header("Expires: 0");
        header("Pragma: public");

        $fh = @fopen( 'php://output', 'w' );

        $printed_header = true;


        while ($row = sqlFetchArray($sqlresults)){

            $data = array();
            // header
            if ( $printed_header) {
                foreach($row as $key => $value) {
                    $data[] = $key;
                }


                // Put the data into the stream
                fputcsv($fh, $data, ',','"');
                $data = array();
                $printed_header = true;
            }


            // values
            foreach($row as $key => $value) {

                $data[] = $value;
            }

            // Put the data into the stream TAB DELIMITED
            fputcsv($fh, $data, ',','"');
            $printed_header = false;
        }

        //Get the summary of all the immunizations:
        $summary =  "Select description, count(*) as count from immunizations i ";
        $summary .= " join immunizations_schedules_codes isc on i.cvx_code = isc.cvx_code ";
        $summary .= " Where i.vfc != 'V01' AND i.administered_date >= ? AND i.administered_date <= ? ";
        $summary .= " group by isc.description ";
        $summaryresults = sqlStatement($summary, array($form_from_date, $form_to_date));


        $data = array("", "", "");
        fputcsv($fh, $data, ',','"');
        fputcsv($fh, $data, ',','"');
        fputcsv($fh, $data, ',','"');
        $data = array("Summary of Results for dates: [$form_from_date - $form_to_date]");
        fputcsv($fh, $data, ',','"');
        $data = array("Vaccine", "Count");
        fputcsv($fh, $data, ',','"');

        while ($row = sqlFetchArray($summaryresults)){
            $data = array();
            foreach($row as $key => $value) {

                $data[] = $value;
            }

            fputcsv($fh, $data, ',','"');
        }






        fclose($fh);
        #	dump_web($payments);



        exit;

    } else {

        $alertmsg = "No records found. Please try different parameters.";
    }














}

// Get the info.
//
?>
<html>
<head>
    <?php html_header_show();?>
    <title><?php xl('VFC Immunization Report','e'); ?></title>

    <style type="text/css">@import url(../../library/dynarch_calendar.css);</style>

    <link rel=stylesheet href="<?php echo $css_header;?>" type="text/css">
    <style type="text/css">

        /* specifically include & exclude from printing */
        @media print {
            #encreport_parameters {
                visibility: hidden;
                display: none;
            }
            #encreport_parameters_daterange {
                visibility: visible;
                display: inline;
            }
        }

        /* specifically exclude some from the screen */
        @media screen {
            #encreport_parameters_daterange {
                visibility: hidden;
                display: none;
            }
        }

        #encreport_parameters {
            width: 100%;
            background-color: #ddf;
        }
        #encreport_parameters table {
            border: none;
            border-collapse: collapse;
        }
        #encreport_parameters table td {
            padding: 3px;
        }

    </style>

    <script type="text/javascript" src="../../library/textformat.js"></script>
    <script type="text/javascript" src="../../library/dialog.js"></script>
    <script type="text/javascript" src="../../library/dynarch_calendar.js"></script>
    <script type="text/javascript" src="../../library/dynarch_calendar_en.js"></script>
    <script type="text/javascript" src="../../library/dynarch_calendar_setup.js"></script>

    <script language="JavaScript">

        var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';

        function get_csv() {
            document.forms[0].submit();
        }

    </script>

</head>

<body class="body_top">

<center>

    <h2><?php xl('VFC Immunization Export','e'); ?></h2>

    <div id="encreport_parameters_daterange">
        <?php echo date("d F Y", strtotime($form_from_date)) ." &nbsp; to &nbsp; ". date("d F Y", strtotime($form_to_date)); ?>
    </div>

    <div id="encreport_parameters">
        <form method='post' name='theform' action='vfc_immunization_report.php'>
            <input type="hidden" name="download" value="download"/>

            <table>

                <tr>
                    <td>

                        &nbsp;
                        <?php  xl('From','e'); ?>:
                        <input type='text' name='form_from_date' id='form_from_date' size='10' value='<?php echo $form_from_date ?>'
                               onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' title='Start Eff date yyyy-mm-dd'>
                        <img src='../pic/show_calendar.gif' align='absbottom' width='24' height='22'
                             id='img_from_date' border='0' alt='[?]' style='cursor:pointer'
                             title='<?php xl('Click here to choose a date','e'); ?>'>

                        &nbsp;<?php  xl('To','e'); ?>:
                        <input type='text' name='form_to_date' id='form_to_date' size='10' value='<?php echo $form_to_date ?>'
                               onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' title='End Eff date yyyy-mm-dd'>
                        <img src='../pic/show_calendar.gif' align='absbottom' width='24' height='22'
                             id='img_to_date' border='0' alt='[?]' style='cursor:pointer'
                             title='<?php xl('Click here to choose a date','e'); ?>'>

                        <input name = "name_include" type="checkbox" id="name" value="Include Patient Name" checked="checked">Include Patient Name? </>

                        &nbsp;
                        <input type='button' value='<?php xl('Download','e'); ?>' onclick='javascript:get_csv()' />
                        <input type='button' value='<?php xl('Done','e'); ?>' onclick='window.close();' />
                    </td>
                </tr>

                <tr>
                    <td height="1">
                    </td>
                </tr>

            </table>
            <?php
            if($_FILES['fileUplCSV'] && $_POST['submit']){
                echo "<div id='encreport_parameters'>";
                echo "Files in Directory <br>";
                foreach($directory as $contents){
                    echo "<b> $contents </b>";
                }

                echo "</div>";

            }

            ?>

    </div> <!-- end encreport_parameters -->

    </form>


</center>
</body>

<script language='JavaScript'>
    Calendar.setup({inputField:"form_from_date", ifFormat:"%Y-%m-%d", button:"img_from_date"});
    Calendar.setup({inputField:"form_to_date", ifFormat:"%Y-%m-%d", button:"img_to_date"});

    <?php if ($alertmsg) { echo " alert('$alertmsg');\n"; } ?>

</script>

</html>
