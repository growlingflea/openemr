<?php

// Copyright (C) 2019 Growlingflea Software.
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// This report lists  patient immunizations for a given date range.

//addition for CAIR plug-in made by Daniel Pflieger, daniel@mi-squared.com
//Changed made on 4-5-2017 to meet new requirements for TLS1.2:

//todo: improve the file header!!!
//todo: make sure everything is for Growlingflea Software





require_once("../globals.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/formatting.inc.php");
require_once($webserver_root.'/library/CAIRsoap.php');

//for 500
//include_once($GLOBALS['srcdir'].'/headers.inc.php'); //Use this for 500
//$library_array = array('datatables', 'datepicker');

//for 501
use OpenEMR\Core\Header;


//form variables:
$immunization_form_from_date = $_POST['immunization_form_from_date'] ?  $_POST['immunization_form_from_date'] : date('Y-m-d');
$immunization_form_to_date = $_POST['immunization_form_to_date'] ?  $_POST['immunization_form_to_date'] : date('Y-m-d');



?>

<html>
    <head>
        <style>
            /*for datatable*/
         .small{

             width:6em;
             th.align:left;
         }

        .med{

            width:14em;
            th.align:left;
        }

        tr th{

            text-align:left;
         }

        div.dataTables_wrapper {
                width: 1800px;
                margin: 0 auto;
            }


            
            
            
        </style>
        <?php html_header_show();?>
        <link rel="stylesheet" href="<?php echo $GLOBALS['css_header'];?>" type="text/css">
        <title><?php xl('CAIR Multiple Match Report: ','e'); ?></title>

        <?php // call_required_libraries($library_array); ?>
        <?php
            //For 501
            Header::setupHeader(['opener', 'report-helper', 'datatables', 'datatables-buttons',
                'datatables-buttons-html5', 'datetime-picker', 'dialog', 'jquery']);
        ?>

        <script>
            //This is for the child row that displays the history of the appointment
            $(document).ready(function() {

                var oTable;

                oTable=$('#show_immunization_report').DataTable({
                    dom: 'Bfrtip',
                    autoWidth: false,
                    processing: true,
                    serverSide: true,
                    scrollX: true,
                    fixedHeader: false,
                    buttons: [
                        'copyHtml5', 'csvHtml5'
                    ],

                    ajax:{ type: "POST",
                        url: "../../library/ajax/immunization_report_CAIR_ajax.php",
                        data: {
                            func:"server_side_immunization_report_duplicates",
                            immunization_form_to_date:   "<?php echo $immunization_form_to_date; ?>",
                            immunization_form_from_date:"<?php echo $immunization_form_from_date; ?>",
                            immunization_form_CAIR_id:"<?php echo $_POST['immunization_form_CAIR_id']; ?>",
                            immunization_form_CAIR_id_only:"<?php echo $_POST['immunization_form_CAIR_id_only']; ?>"

                        },

                    },
                    "lengthMenu": [ 10, 25, 50, 100 ],
                    columns:[
                        { 'data': 'CAIR'},
                        { 'data': 'submit_date'},
                        { 'data': 'immunization_id'},
                        { 'data': 'pid'},
                        { 'data': 'pt_name',
                            "render": function(data, type, row, meta){
                                if(type === 'display'){
                                    data = '<a>' + data + '</a>';
                                }

                                return data;
                            },
                        },
                        { 'data': 'DOB'},
                        { 'data': 'Address'},
                        { 'data': 'phone_home'},
                        { 'data': 'mothersname'},
                        { 'data': 'mothers_last'},
                        { 'data': 'error'},


                    ],
                    columnDefs: [
                        { className: 'dt-body-top',targets: [0,1,2,3,5,6,7,8,9,10] },
                        { className: "patientName", "targets": [ 4 ]  },
                    ],

                    "iDisplayLength": 25,
                    "select":true,
                    "searching":true,
                    "retrieve" : true,


                });




                $('#show_immunization_report tbody').on('click', 'td.patientName', function () {
                    var tr = $(this).closest('tr');
                    var row = oTable.row( tr );
                    var data = oTable.row( $(this).parents('tr') ).data();
                    var next = this.className;

                    var newpid =data['pid'];
                    console.log("This is the pid " +  newpid);
                    if (newpid.length===0)
                    {
                        return;
                    }
                    if (0) {
                        openNewTopWindow(newpid);
                    }
                    else {
                        top.restoreSession();
                        top.RTop.location = "<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/summary/demographics.php?set_pid=" + newpid;
                        top.left_nav.closeErx();
                    }


                } );

                function openNewTopWindow(pid) {
                    document.fnew.patientID.value = pid;
                    top.restoreSession();
                    document.fnew.submit();
                }

                $('#immunization_form_CAIR_id_only').on('change', function() {
                    {
                        if ($('#immunization_form_CAIR_id_only').is(':checked')) {
                            $("input.datefield").attr('disabled', true).css('background-color', 'grey');


                        } else {

                            $("input.datefield").attr('disabled', false).css('background-color', 'white');


                        }
                    }
                });

                $("#immunization_form_CAIR_id_only").attr('disabled', true).css('background-color', 'grey');

                $('#immunization_form_CAIR_id').on( 'change keyup keypress', function() {



                    if ($(this).val().length > 0) {

                        $("#immunization_form_CAIR_id_only").attr('disabled', false).css('background-color', 'white');


                    } else {

                        $("#immunization_form_CAIR_id_only").attr('disabled', true).css('background-color', 'grey');


                    }

                });


            });

        </script>



    </head>


<body class="body_top formtable">
<span class='title'><?php xl('Report','e'); ?> - <?php xl('Immunizations','e');  ?></span
    <div class="position-static">
        <form name='immunization_search_form' id='immunization_search_form' method='post' action='./immunization_report_CAIR_multimatched_errors.php'
              onsubmit='return top.restoreSession()'>
            <div id="report_parameters">

                <table>
                    <tr>
                        <td width='410px'>
                            <div style='float:left'>
                                <table>
                                    <tr class="title">
                                        <?php xl('Immunization Administered Date Range','e'); ?>:

                                    </tr>

                                    <tr>
                                        <td align ="right">
                                            <?php xl('From','e'); ?>:<input class="datefield" type='text' name='immunization_form_from_date' id='immunization_form_from_date' size='10'
                                                                            value='<?php echo $immunization_form_from_date ?>' >

                                        </td>

                                        <td align ="right">
                                            <?php xl('To','e'); ?>:<input class="datefield" type='text' name='immunization_form_to_date' id='immunization_form_to_date' size='10'
                                                                          value='<?php echo $immunization_form_to_date ?>'
                                            ></td>


                                    </tr>
                                    <br>
                                    <tr align ="right">
                                        <td>

                                            <?php xl('CAIR ID','e'); ?>:
                                            <input type='text' name='immunization_form_CAIR_id' id='immunization_form_CAIR_id' size='10'
                                                   value='<?php echo $_POST['immunization_form_CAIR_id'] ?>'>

                                        </td>

                                        <td align ="right">
                                            <input type='checkbox' name='immunization_form_CAIR_id_only' id='immunization_form_CAIR_id_only'
                                                   value='1'>

                                            <?php xl('Search Only by Cair ID','e'); ?>
                                        </td>

                                    </tr>

                                    <tr>
                                        <td>
                                            <label><input value="<?php echo htmlspecialchars(xl('Submit')) ?> " type="submit" id="submit_selector" name="appt_submit" ><?php ?></label>
                                            <input hidden id = 'submit_button' value = '<?php echo $_POST['appt_submit']  ?>'
                                        </td>
                                        <td>

                                        </td>
                                    </tr>

                                </table>
                            </div>
                        </td>
                    </tr>
                </table>
            </div> <!-- end of parameters -->

        </form>

    </div>


<table cellpadding="1px" cellspacing="0" border="0" class="display formtable session_table cell-border" id="show_immunization_report">

    <thead>



    <tr>
        <th> <?php xl('CAIR ID','e'); ?> </th>
        <th> <?php xl('Submit Date','e'); ?> </th>
        <th> <?php xl('Imm. ID','e'); ?> </th>
        <th> <?php xl('MRN(pid) ','e'); ?> </th>
        <th> <?php xl('Name','e'); ?> </th>
        <th> <?php xl('DOB','e'); ?> </th>
        <th> <?php xl('Address','e'); ?> </th>
        <th> <?php xl('Phone','e'); ?> </th>

        <th> <?php xl('Mothers FN','e'); ?> </th>
        <th> <?php xl('Mothers LN (maiden)','e'); ?> </th>
        <th> <?php xl('Error','e'); ?> </th>

    </tr>

    </thead>
    <tbody id="users_list" >
    </tbody>

</table>

<form name='fnew' method='post' target='_blank' action='../main/main_screen.php?auth=login&site=<?php echo attr($_SESSION['site_id']); ?>'>
    <input type='hidden' name='patientID'      value='0' />
</form>

</body>
<link rel="stylesheet" href="../../public/assets/jquery-datetimepicker-2-5-4/jquery.datetimepicker.css">
<script type="text/javascript" src="../../public/assets/jquery-datetimepicker-2-5-4/build/jquery.datetimepicker.full.min.js"></script>


<script>
    $(function() {
        $("#immunization_form_from_date").datetimepicker({
            timepicker: false,
            format: "Y-m-d"

        });
        $("#immunization_form_to_date").datetimepicker({
            timepicker: false,
            format: "Y-m-d"
        });




    });


</script>

</html>
