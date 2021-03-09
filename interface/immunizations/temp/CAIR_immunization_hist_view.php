<?php

$localQBP = $cairSOAP::getLocalQBP($pid);

if($localQBP === false || $_POST['requery'] == true ){

    $rsp_string = $isInitalized->submitSingleMessage($qbp_string)->return;
    $qdate = " " . date('Y-m-d h:i:s');
    $wdsl = $IMM['wsdl_url'];
    $row = sqlQuery("select * from patient_data where pid = $pid");

    //we can save the immunization history so we can pull it if we need it.
    $sql2 = "Insert into `immunization_QBP_log` values(?,?,?,?,?,?,?,?)";
    $res2 = sqlStatement($sql2, array('', $pid, $row['CAIR'], $date , $IMM['wsdl_url'], $qbp_string, $rsp_string , ''));

}else{

    $qdate = "Last Query Submitted " . $localQBP['submit_date'] ;
    $rsp_string = $localQBP['received_message'];
    $wdsl = $localQBP['WDSL'];
}

$rsp_array  = $cairSOAP::RSP_to_array($rsp_string);
$qbp_array  = $cairSOAP::QBP_to_array($qbp_string);
$msgTypeArray  = explode( '^', $qbp_array['QPD']['message_query_name']);


?>

<script>

    $(document).ready(function() {

        var oTable;

        oTable = $('#show_immunization_report').DataTable({
            dom: 'Bfrtip',
            destroy: true,
            order: [[0, "asc"], [1, "asc"]],
            select: false,
            fixedHeader: true,
            buttons: [
                'copyHtml5', 'csvHtml5', 'pdf'
            ],

            data:<?php echo $cairSOAP::history_to_json_datatables($rsp_array); ?> ,



            columns: [

                {'data': 'vaccine_group'},
                {'data': 'date_of_admin'},
                {'data': 'series'},
                {'data': 'trade_name'},
                {'data': 'owned'},
                {'data': 'reaction'},
                {'data': 'hist'},

            ],

            "columnDefs": [],

            "rowCallback": function (row, data) {

                

            },

            "iDisplayLength": 100,
            "searching": false,
            "retrieve": true


        });
    });
</script>

<div class="container-fluid">

<table width="100%" cellpadding="0" cellspacing="0" border="0" class="display formtable session_table  compact" id="show_immunization_report_header">
    <tr><td><?php echo "<b>STATUS     :</b> " . $cairSOAP::CAIR_message_status($rsp_array['QAK']['query_response_status']); ?></td></tr>
    <tr><td><?php echo "<b>Last Update:</b> " . $qdate ?></td></tr>
    <?php
    //show the WDSL to admin
    if($_SESSION['authUser'] == "admin"){ ?>

    <tr><td><?php echo "<b> Last Update From </b> " .  $wdsl . "."; ?></td></tr>

    <?php } ?>

</table>
<br>
<br>
<table width="100%" cellpadding="0" cellspacing="0" border="1" class="display formtable session_table cell-border compact" id="show_immunization_report">


    <thead>
    <tr>
        <th> <?php xl('Vaccine Group','e'); ?> </th>
        <th> <?php xl('Admin Date','e'); ?> </th>
        <th> <?php xl('Series','e'); ?> </th>
        <th> <?php xl('Trade Name','e'); ?> </th>
        <th> <?php xl('Dose','e'); ?> </th>
        <th> <?php xl('Reaction','e'); ?> </th>
        <th> <?php xl('Hist?','e'); ?> </th>
    </tr>

    </thead>
    <tbody id="users_list" >
    </tbody>

</table>


</div>

<div class="container imm_hist_qbp">
    <div class="row">

    </div>
    <div class = "rsp" id="ACK_info" hidden>
        <div class="row">
            <div class = 'col-sm-6 smtitle underline'>
                <u>Request to CAIR</u>
            </div>
        </div>
        <div class="row" >
            <div class = 'col-sm-2 smtitle col-lg-2'>
                Request Type
            </div>
            <div class = 'col-sm-6 col-lg-6'>
                <?php echo $msgTypeArray[1] ?>
            </div>
        </div>

        <div class="row">
            <div class = 'col-sm-2 smtitle col-lg-2'>
                Response:
            </div>
            <div class = 'col-sm-6 data col-lg-6'>
                <?php echo $rsp_array['MSA']['ack_code'] . " - " . $msa_status[1] ;?>
            </div>
        </div>
        <br>
    </div>


    <br><br>


            <div class="row">
                <table>
                    <tr>
                        <th width="10%">ID</th>
                        <th width="10%">Date</th>
                        <th width="80%">Immunization</th>
                    </tr>

                    <?php

                    $localImm = $cairSOAP::getLocalImmHist($pid);
                    foreach($localImm as $imm){



                        ?>
                        <tr>
                            <td><?php echo $imm['IMMID'] ?></td>
                            <td><?php echo date('Y-m-d', strtotime($imm['administered_date'])) ?></td>
                            <td><?php echo $imm['code_text'] ?> </td>

                        </tr>
                    <?php }
                    ; ?>
                </table>

            </div>

    </div>
</div>

