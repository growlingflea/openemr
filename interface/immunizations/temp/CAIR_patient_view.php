<?php

//Parse the response and print it to the end user
//Get the pat
$qbp_string = $cairSOAP::gen_HL7_CAIR_QBP($_GET['pid'], 'query');
$qbp_array = $cairSOAP::QBP_to_array($qbp_string);
$name_array    = explode( '^', $qbp_array['QPD']['patient_name']);
$pt_address    = explode( '^', $qbp_array['QPD']['patient_address']);
$pt_phone      = explode( '^', $qbp_array['QPD']['patient_home_phone']);

$msa_status      = explode( '^', $rsp_array['MSA']['status']);

?>


        <div id="patient_info">
            <div class="row">
                <div class = 'col-md-4 col-sm-4 title'>
                    Patient Information for <?php echo $name_array[1] . " " . $name_array[0]; ?>
                </div>
            </div>

            <br><br>
            <div class="row">
                <div class = 'col-md-2 col-sm-2 smtitle'>
                    Address:
                </div>
                <div class = 'col-md-4 col-sm-4 data' id="address">
                   <div><?php echo $pt_address[0] .", " . $pt_address[1] ?> <?php echo  $pt_address[2] .  ", " .$pt_address[3] . " " . $pt_address[4] ; ?></div>

                </div>
                <div class = 'col-md-2 col-sm-2 smtitle'>
                    <?php echo "Sex: " ; ?>
                </div>
                <div class = 'col-md-4 col-sm-4 data'>
                    <?php echo $qbp_array['QPD']['patient_sex'] ?>
                </div>
            </div>

            <div class="row">


                <div class = 'col-md-2 col-sm-2 smtitle'>
                    <?php echo "DOB: " ; ?>
                </div>
                <div class = 'col-md-4 col-sm-4 data'>
                    <?php echo date('Y-m-d', strtotime($qbp_array['QPD']['patient_dob'])) ;?>
                </div>

                <div class = 'col-md-2 col-sm-2 smtitle'>
                    <?php echo "Home Phone: " ; ?>
                </div>
                <div class = 'col-md-4 col-sm-4 data'>
                    <?php echo $pt_phone[5] . "-" . substr($pt_phone[6],0,3) . '-' . substr($pt_phone[6],4)  ?>
                </div>
            </div>

            <div class="row">
                <div class = 'col-md-2 col-sm-2 smtitle'>
                    <?php echo "CAIR ID: " ; ?>
                </div>
                <div class = 'col-md-4 col-sm-4 data'>
                    <?php echo $cairSOAP::getCairId($pid); ?>
                </div>

                <div class = 'col-md-2 col-sm-2 smtitle'>
                    <?php echo "MR# (PID): " ?>
                </div>
                <div class = 'col-md-4 col-sm-4 data'>
                    <?php echo $pid ?>
                </div>
            </div>

            <div class="row">
                <div class = 'col-md-2 col-sm-2 smtitle'>
                    <?php echo "Mother's Name: " ; ?>
                </div>
                <div class = 'col-md-4 col-sm-4 data'>
                    <?php echo $qbp_array['QPD']['patient_mother_name'] = $qbp_array['QPD']['patient_mother_name'] ? (strlen($qbp_array['QPD']['patient_mother_name']) > 1) : "<i>Not Recorded</i>"; ?>
                </div>
            </div>
        </div>
