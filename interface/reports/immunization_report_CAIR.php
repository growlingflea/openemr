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

        <?php html_header_show();?>
<!--        <link rel="stylesheet" href="--><?php //echo $GLOBALS['css_header'];?><!--" type="text/css">-->
        <style>
            /*for datatable*/
            .small{

                width:6em;
                th.align:left;
            }

            .vsmall{

                width:3em;
                th.align:left;
            }

            .med{

                width:14em;
                th.align:left;
            }

            tr th{

                text-align:left;
            }





        </style>
        <title><?php xl('CAIR Immunization Report: ','e'); ?></title>

        <?php // call_required_libraries($library_array); ?>
        <?php
            //For 501
            Header::setupHeader(['opener', 'report-helper', 'datatables', 'datatables-buttons',
                'datatables-buttons-html5', 'datetime-picker', 'dialog', 'jquery']);
        ?>

        <script>
            //This is for the child row that displays the history of the appointment
            function format ( d ){

                var rowColor;
                var errorColor ="rgb(255,13,32)";
                var okColor ="rgba(7,247,51,0.83)";
                var warnColor="rgba(214,217,20,0.83)";
                var nextcolor ="rgba(246, 23, 216, 0.83)";
                var defaultcolor = "Black";

                var response = '' +

                    '<table id="main_table" class = "main" style=" width:90%; padding: 5px 5px 5px 5px;" border="4" align="center">'+'<tr><td width="95%">' +
                        '<div id="contact_list_div">'+
                        '<table class = " formtable " border=3 style="float: center; width:95%;  table-layout:fixed " id="summary" >' +
                            '<caption><h3>Immunization Summary</h3></caption>' +
                                '<tr>' +
                                    '<th>Administered Date</th>' +
                                    '<th>DOB</th>' +

                                    '<th>Current Status</th>' +
                                    '<th>Num Attempts</th>' +
                                    '<th>AE Failures</th>' +
                                    '<th>Other Errors</th>' +
                                '</tr>' ;

                    var accepted = (d.current_imm_cair_status > 0) ? "Accepted" : "Errors Exist" ;

                     response += '<tr>' +
                                    '<td>'+ d.administered_date +'</td>' +
                                    '<td>'+ d.dob +'</td>' +

                                    '<td>'+ accepted + '</td>' +
                                    '<td>'+ d.immunization_attempts +'</td>'+
                                    '<td>'+ d.immunization_failures + '</td>' +
                                    '<td>'+ d.cair_response_num_errors + '</td>' +
                                '</tr>' +
                        '</table>';





                response += '' +
                '<br><br>' +
                    '<table class = " formtable " border=3 style="float: center; width:85%;  table-layout:fixed " id="immunization_history" >' +
                    '<caption><h3>Immunization History</h3></caption>';


                response += '' +
                        '<tr>' +
                            '<th width="15%">Date Sent</th>' +
                            '<th width="5%">Status</th>' +
                            '<th width="80%">Error Response</th>' +
                        '</tr>' ;

                var cair_response_messages = d.cair_response_messages;

                cair_response_messages.forEach(function(message) {
                var error_message = ( message['error_message'] !== null) ?  '<i>' + message['error_message'] + '</i><br>' : '';
                    var msa_ack = ( typeof message['MSA']['ack_code'] !== 'undefined') ?  '<i>' + message['MSA']['ack_code'] + '</i><br>' : '';
                    console.log("passed this line twice");
                    response += '' +
                        '<tr>' +
                        '<td>' + message['submit_date'] + '</td>' +
                        '<td>' + msa_ack + '</td>' +
                        '<td>' ;

                    var errors = message.ERR;
                    response += '' +
                    '<table class = " formtable compact " border=1 style=" width:95%;  ">' + '<tr>' +
                            '<th  width="10%">Severity</th>' +
                            '<th   width="90%">Message</th></tr>' ;
                    errors.forEach(function(error){
                      response +='<tr>  <td>' + error['severity'] + '</td>';

                       response += '<td>'+ error['user_message'] + '</td></tr>';

                    });

                        response += '</table>' +
                        '</td>' +
                        '</tr>';
                });
                 response += '' +
                     '</table><br><br>' + "<div align='center'><i>Messages in Itallics may not be correct.  " +
                        "Currently working on data fix.</i><br> W = Warning, recorded by CAIR <br> E = Error. Must be resent </div>" +
                '</table>' ;

                return response;
            }








            $(document).ready(function() {

                var oTable;

                oTable=$('#show_immunization_report').DataTable({
                    dom: 'Bfrtip',
                    processing: true,
                    serverSide: true,
                    scrollx:true,
                    scrolly:true,

                    buttons: [
                        'copyHtml5', 'csvHtml5'
                    ],

                    ajax:{ type: "POST",
                        url: "../../library/ajax/immunization_report_CAIR_ajax.php",
                        data: {
                            func:"server_side_immunization_report",
                            immunization_form_to_date:   "<?php echo $immunization_form_to_date; ?>",
                            immunization_form_from_date:"<?php echo $immunization_form_from_date; ?> ",
                            immunization_form_CAIR_id:"<?php echo $_POST['immunization_form_CAIR_id']; ?>",
                            immunization_form_CAIR_id_only:"<?php echo $_POST['immunization_form_CAIR_id_only']; ?>"
                        },

                    },
                    "lengthMenu": [ 10, 25, 50, 100 ],
                    columns:[
                        { 'data': 'administered_date'},
                        { 'data': 'CAIR'},
                        { 'data': 'immid' },
                        { 'data': 'pid' },
                        { 'data': 'pt_name',
                            "render": function(data, type, row, meta){
                                if(type === 'display'){
                                    data = '<a>' + data + '</a>';
                                }

                                return data;
                            },
                       },
                        { 'data': 'cvx_code'},

                        { 'data': 'code_text_short'},
                        { 'data': 'manufacturer'},
                        { 'data': 'lot_number'},
                        { 'data': 'historical'},
                        { 'data': 'refusal_reason'},
                        { 'data': 'ack_code', 'searchable':false},
                        { 'data': 'cair_response_num_messages'},
                        { 'data': 'cair_response_num_errors'},

                        { 'data': 'current_imm_cair_status', 'visible':false},
                        { 'data': 'vfc', 'visible':false},
                        { 'data': 'CAIR', 'visible':false},
                        { 'data': 'ack_code', 'visible':false},

                    ],

                    "columnDefs":[

                        {className: "compact details-control", "targets": [ 0 ] },
                        { className: "details-control", "targets": [ 1,2,3,5,6,7,8,9,10,11,12,13 ] },
                        { className: "patientName", "targets": [ 4 ] },

                    ],

                    "rowCallback": function( row, data ) {

                        if(data['current_imm_cair_status'] === 0){

                            $(row).attr( "style" , "background-color: rgba(249,149,148,0.2) !important" );
                        }

                        if(data['ack_code'] === "AE"){

                            $(row).attr('style',  'background-color: rgba(214,217,20,0.37) !important');
                        }

                        if(data['cair_response_num_errors'] > 0){

                            $(row).attr( "style", 'background-color: rgba(249,149,148,0.44) !important' );
                        }

                        if(data['ack_code'] === "AA"){

                            $(row).attr( "style", 'background-color: rgba(47,163,22,0.49) !important' );

                        }


                    },

                    "iDisplayLength": 25,
                    "select":true,
                    "searching":true,
                    "retrieve" : true


                });

                    $('#column0_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 0 )
                        .search( this.value )
                        .draw();
                } );

                $('#column1_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 1 )
                        .search( this.value )
                        .draw();
                } );
                $('#column2_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 2 )
                        .search( this.value )
                        .draw();
                } );
                $('#column3_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 3 )
                        .search( this.value )
                        .draw();
                } );
                $('#column4_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 4 )
                        .search( this.value )
                        .draw();
                } );
                $('#column5_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 5 )
                        .search( this.value )
                        .draw();
                } );
                $('#column6_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 6 )
                        .search( this.value )
                        .draw();
                } );
                $('#column7_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 7 )
                        .search( this.value )
                        .draw();
                } );
                $('#column8_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 8 )
                        .search( this.value )
                        .draw();
                } );
                $('#column9_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 9 )
                        .search( this.value )
                        .draw();
                } );
                $('#column10_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 10 )
                        .search( this.value )
                        .draw();
                } );
                $('#column11_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 11 )
                        .search( this.value )
                        .draw();
                } );
                $('#column12_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 12 )
                        .search( this.value )
                        .draw();
                } );

                $('#column13_search_show_immunization_report').on( 'keyup', function () {
                    oTable
                        .columns( 13 )
                        .search( this.value )
                        .draw();
                } )


                $('#show_immunization_report tbody').on('click', 'td.details-control', function () {

                    var tr = $(this).closest('tr');
                    var row = oTable.row( tr );
                    var data = oTable.row( $(this).parents('tr') ).data();
                    var next = this.className;



                    if ( row.child.isShown() ) {
                        // This row is already open - close it
                        row.child.hide();
                        tr.removeClass('shown');
                    }
                    else {
                        // Open this row
                        row.child( format(row.data()) ).show();
                    }
                } );

                $('#show_immunization_report tbody').on('click', 'td.patientName', function () {
                    var tr = $(this).closest('tr');
                    var row = oTable.row( tr );
                    var data = oTable.row( $(this).parents('tr') ).data();
                    var next = this.className;

                    var newpid =data['pid'];

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
        <form name='immunization_search_form' id='immunization_search_form' method='post' action='./immunization_report_CAIR.php'
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
                            <div style="float:right; width:25%;">
                                <table>
                                    <tr style="align:left;">
                                     <td></td><td><u><b>Message Status Legend</b></u></td>
                                    </tr>
                                    <tr>
                                        <td>AA</td><td>Accepted, No Issue</td>
                                    </tr>
                                    <tr>
                                        <td>AE</td><td>Application Error, May Have Issues</td>
                                    </tr>
                                    <tr>
                                        <td>AR</td><td>Application Reject, Invalid Data Sent</td>
                                    </tr>

                                </table>
                            </div>
                        </td>
                    </tr>
                </table>
            </div> <!-- end of parameters -->

        </form>

    </div>


<table cellpadding="0" cellspacing="0" border="0" class="cell-border compact display" id="show_immunization_report" style="width:100%">

    <thead>

    <tr>

        <th><input  id = 'column0_search_show_immunization_report' class = "small" </th>
        <th><input  id = 'column1_search_show_immunization_report' class = "small" ></th>
        <th><input  id = 'column2_search_show_immunization_report' class = "small" ></th>
        <th><input  id = 'column3_search_show_immunization_report' class = "small" ></th>
        <th><input  id = 'column4_search_show_immunization_report' class = "small" ></th>
        <th><input  id = 'column5_search_show_immunization_report' class = "vsmall" ></th>

        <th><input  id = 'column6_search_show_immunization_report' class = "small" ></th>
        <th><input  id = 'column7_search_show_immunization_report' class = "small" ></th>
        <th><input  id = 'column8_search_show_immunization_report' class = "small" ></th>
        <th><input  id = 'column9_search_show_immunization_report' class = "vsmall" ></th>
        <th><input  id = 'column10_search_show_immunization_report' class = "vsmall" ></th>

        <th><input  id = 'column11_search_show_immunization_report' class = "small" ></th>
        <th><input  id = 'column12_search_show_immunization_report' class = "vsmall" ></th>
        <th><input  id = 'column13_search_show_immunization_report' class = "vsmall" ></th>

        <th></th>
        <th></th>
        <th></th>



    </tr>

    <tr>
        <th> <?php xl('Date Administered','e'); ?> </th>
        <th> <?php xl('CAIR','e'); ?> </th>
        <th> <?php xl('Imm ID','e'); ?> </th>
        <th> <?php xl('PID','e'); ?> </th>
        <th> <?php xl('PT Name','e'); ?> </th>
        <th> <?php xl('CVX','e'); ?> </th>
        <th> <?php xl('Imm Name','e'); ?> </th>

        <th> <?php xl('Mfr.','e'); ?> </th>
        <th> <?php xl('Lot Num','e'); ?> </th>
        <th> <?php xl('Hist?','e'); ?> </th>
        <th> <?php xl('Refusal <br> Reason','e'); ?> </th>
        <th> <?php xl('Status','e'); ?> </th>


        <th> <?php xl('CAIR <br> Resp','e'); ?> </th>
        <th> <?php xl('CAIR <br> Errors','e'); ?> </th>
        <th></th>
        <th></th>
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
