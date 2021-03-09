<?php
$qbp_string_projection = $cairSOAP::gen_HL7_CAIR_QBP($_GET['pid'], 'projection');
$rsp_string_projection = $isInitalized->submitSingleMessage($qbp_string_projection)->return;

$qbp_array_projection = $cairSOAP::QBP_to_array($qbp_string_projection);

$rsp_array_projection = $cairSOAP::RSP_to_array($rsp_string_projection);
$projection = $cairSOAP::print_message($rsp_string_projection);
?>
<br><b> Recommended Schedule</b><br>
<div class="container">
    <div class="row rsp" id = "prj"  hidden>
        <div class = 'col-sm-12 data'>

            <?php echo $projection ?>

        </div>
    </div>
</div>

