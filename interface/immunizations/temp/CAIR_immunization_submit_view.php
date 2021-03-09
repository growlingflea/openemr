<?php

$maxImm =  $cairSOAP::getNewImmID($pid);
$hl7_vxu = $cairSOAP::gen_HL7_VXU($pid, $maxImm);
$ack_string = $isInitalized->submitSingleMessage($hl7_vxu)->return;
$ack_array = $cairSOAP::ACK_to_array($ack_string);
//$test_array = $cairSOAP::ACK_to_array($test2);
//$ack_array = $test_array;
$today = date("Y-m-d H:i:s");
$err_msg = '';


$today = date("Y-m-d H:i:s");
$sql = "Update immunizations set submitted = ? where id = ?";
?>
<br>


<?php

//if AA is received, print green "Success!"
if ($ack_array['MSA']['ack_code'] == 'AA'){

    echo "<b>Immunization Submission Success!</b>";
    $submitted = 1;
    $res = sqlStatement($sql, array($submitted, $maxImm));

}else if ($ack_array['MSA']['ack_code'] == 'AE'){

    echo "<b>Message was processed and errors are being reported.</b><br>";
    foreach($ack_array['ERR'] as $err){

        echo $err['user_message'] . "<br>";
        $err_msg .= $err['user_message'] . ' ';

    }
    $submitted = 0;
    $res = sqlStatement($sql, array($submitted, $maxImm));

}else if ($ack_array['MSA']['ack_code'] == 'AR'){

    echo "<b>Message was rejected because one of the following occurred:</b>";
    foreach($ack_array['ERR'] as $err){

        echo $err['user_message'] . "<br>";
        $err_msg .= $err['user_message'] . ' ';
    }
    $submitted = 0;
    $res = sqlStatement($sql, array($submitted, $maxImm));
}


$sql2 = "Insert into immunization_log(`immunization_id`, `submit_date`, `WDSL`, `sent_message`, " .
    " `received_message`, `error_msg`) values(?,?,?,?,?,?) ";

$res2 = sqlStatement($sql2, array($maxImm, $today, $GLOBALS['IMM']['wsdl_url'], $hl7_vxu, $ack_string, $err_msg));

?>






<?php //Display raw data to admin, only.   ?>
 <?php if ($_SESSION['authUser'] == 'admin') { ?>
<br><br>

<div class="row rsp" id = "rsp" hidden>
    <B>Raw Data</B>
    <div>
        <?php echo $hl7_vxu ?>
    </div>
    <br>
    <div>
        <h5>HL7 VXU</h5><br>
        <?php $cairSOAP::print_message($hl7_vxu); ?>
    </div>

    <div>
        <h5>HL7 RSP</h5><br>
        <?php echo $cairSOAP::print_message($ack_string); ?>
    </div>
</div>

<?php } ?>