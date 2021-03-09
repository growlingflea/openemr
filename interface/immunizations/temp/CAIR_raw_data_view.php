<?php

//This displays the raw query data

$s = $cairSOAP::print_message($qbp_string);
$m = $cairSOAP::print_message($rsp_string);
$js = $cairSOAP::history_to_json_datatables($rsp_array);

?>
<br><b>Raw Data</b><br>
<div class="row rsp" id = "rsp" hidden>

    <h5>QBP String</h5>
    <div class = 'col-sm-12 data'>
        <?php echo $s ?>
    </div>
   <?php echo "\n\n"; ?>
    <h5>RSP String</h5>
    <div class = 'col-sm-12 data'>
        <?php echo $m ?>
    </div>
    <?php echo "\n\n"; ?>
    <h5>RSP ARRAY</h5>
    <div class = 'col-sm-12 data'>
        <?php echo $js ?>
    </div>
</div>


