<?php
// library/ajax/appointment_status_change_report_ajax.php
// Copyright (C) 2017 -Daniel Pflieger
// daniel@growlingflea.com daniel@mi-squared.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// This is a reporting tool that shows all sent notifications and their status.

require_once("../globals.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/formatting.inc.php");
require_once("$webserver_root/library/globals.inc.php");
require_once("{$GLOBALS['srcdir']}/sql.inc");
//include_once($GLOBALS['srcdir'].'/headers.inc.php'); //Use this for 500
use OpenEMR\Core\Header;

//$library_array = array('datatables');

$form_from_date = $_POST['form_from_date'] ?  $_POST['form_from_date'] : date('Y-m-d');
$form_to_date = $_POST['form_to_date'] ?  $_POST['form_to_date'] : date('Y-m-d');

?>
<html>
<head>
    <?php html_header_show();?>
    <link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
    <title><?php xl('Appointment Status Report: ','e'); ?></title>
    <?php //call_required_libraries($library_array); ?>
    <?php Header::setupHeader(['opener', 'report-helper', 'datatables', 'datatables-buttons',
        'datatables-buttons-html5', 'datetime-picker', 'dialog', 'jquery']); ?>


    <script>
        //This is for the child row that displays the history of the appointment
        function format ( d ){

        }

        $(document).ready(function() {

            var oTable;

            oTable=$('#show_appointment_table').DataTable({
                dom: 'Bfrtip',
                autoWidth: false,
                scrollY: false,
                fixedHeader: true,
                buttons: [
                    'copy', 'excel', 'pdf', 'csv'
                ],
                ajax:{ type: "POST",
                    url: "../../library/ajax/appointment_status_change_report_ajax.php",
                    data: {
                        func:"show_appointments",
                        to_date:   "<?php echo $form_to_date; ?>",
                        from_date:" <?php echo $form_from_date; ?> "
                    },

                },

                columns:[
                    { 'data': 'id'},
                    { 'data': 'pid'},
                    { 'data': 'name'},
                    { 'data': 'date'},
                    { 'data': 'apptdate'},
                    { 'data': 'appttime'},
                    { 'data': 'user'},
                    { 'data': 'title'}

                ],

                "iDisplayLength": 100,
                "select":true,
                "searching":true,
                "retrieve" : true


            });

            $('#column0_search_show_appointment_table').on( 'keyup', function () {
                oTable
                    .columns( 0 )
                    .search( this.value )
                    .draw();
            } );
        });





</script>
</head>
<body class="body_top formtable">
<span class='title'><?php xl('Report','e'); ?> - <?php xl('Appointments','e');  ?></span>
<div class="position-static">

    <form name='theform' id='theform' method='post' action='./appointment_status_change_report.php'
          onsubmit='return top.restoreSession()'>
        <div id="report_parameters">

            <table>
                <tr>
                    <td width='410px'>
                        <div style='float:left'>
                            <table>
                                <tr class='label'>
                                    <?php xl('Appointment Date Range','e'); ?>:
                                </tr>
                                <br>
                                <tr>
                                    <input type='text' name='form_from_date' id='form_from_date' size='10'
                                           value='<?php echo $form_from_date ?>' >

                                    <input type='text' name='form_to_date' id='form_to_date' size='10'
                                           value='<?php echo $form_to_date ?>' >
                                </tr>
                                <br>
                                <tr>

                                    <label><input value="<?php echo htmlspecialchars(xl('Submit')) ?> " type="submit" id="submit_selector" name="appt_submit" ><?php ?></label>
                                    <input hidden id = 'submit_button' value = '<?php echo $_POST['appt_submit']  ?>'
                                </tr>

                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div> <!-- end of parameters -->

    </form>

</div>

<table cellpadding="0" cellspacing="0" border="0" class="display formtable session_table" id="show_appointment_table">

    <thead>



    <tr>
        <th> <?php xl('Appt ID','e'); ?> </th>
        <th> <?php xl('PID','e'); ?> </th>
        <th> <?php xl('Name','e'); ?> </th>
        <th> <?php xl('Date Changed','e'); ?> </th>
        <th> <?php xl('Appt. Date','e'); ?> </th>
        <th> <?php xl('Appt. Time','e'); ?> </th>
        <th> <?php xl('User','e'); ?> </th>
        <th> <?php xl('Status','e'); ?> </th>
    </tr>

    </thead>
    <tbody id="users_list" >
    </tbody>

</table>


</body>


<script>
    $(function() {
        $("#form_from_date").datetimepicker({
            timepicker: false,
            format: "Y-m-d"

        });
        $("#form_to_date").datetimepicker({
            timepicker: false,
            format: "Y-m-d"
        });

    });


</script>


</html>