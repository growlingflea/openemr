<html>
<head>
    <?php html_header_show();?>
    <link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
    <style type="text/css">
        .immunization_codes td, .immunization_codes th {
            padding: 10px;
            border-bottom: 1px solid black;
        }
        .immunization_codes th {
            text-align: left;
        }
        .immunization_code_form input, .immunization_code_form select {
            width: 400px;
        }
        .error {
            color: red;
        }
    </style>
    <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.9.1.min.js"></script>
    <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/textformat.js"></script>
    <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js?v=<?php echo $v_js_includes; ?>"></script>

</head>
<body class="body_top">
<span class="title"><?php echo $action == 'add' ? 'Add' : 'Edit'; echo ' code';?></span>
<br><br>
<div class="error">
    <?php
    if(isset($errorMessage)) {
        echo $errorMessage;
    }
    ?>
</div>

<form method="post" action="codes.php">
    <table class="immunization_code_form">
        <tr>
            <td><label for="scanner">Scan</label></td>
            <td><input type="text" name="immunization_code[scan]" id="scanner_code" </td>
        </tr>
        <tr>
            <td><label for="description">Description</label></td>
            <td><input type="text" name="immunization_code[description]" value="<?php if(isset($result['description'])) echo $result['description'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Manufacturer</label></td>
            <td><input type="text" name="immunization_code[manufacturer]" value="<?php if(isset($result['manufacturer'])) echo $result['manufacturer'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Cvx code</label></td>
            <td><input type="text" name="immunization_code[cvx_code]" value="<?php if(isset($result['cvx_code'])) echo $result['cvx_code'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">GTIN</label></td>
            <td><input id='gtin' type="text" name="immunization_code[GTIN]" value="<?php if(isset($result['GTIN'])) echo $result['GTIN'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Proc codes</label></td>
            <td><input type="text" name="immunization_code[proc_codes]" value="<?php if(isset($result['proc_codes'])) echo $result['proc_codes'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Justify codes</label></td>
            <td><input type="text" name="immunization_code[justify_codes]" value="<?php if(isset($result['justify_codes'])) echo $result['justify_codes'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Default site</label></td>
            <td>
                <?php
                $value = '';
                if(isset($result['default_site'])) {
                    $value = $result['default_site'];
                }
                echo generate_select_list('immunization_code[default_site]', 'Imm_Administrative_Site__CAIR', $value,'Select Default Site', ' ');
                ?>
            </td>
        </tr>
        <tr>
            <td><label for="cccc">Comments</label></td>
            <td><input type="text" name="immunization_code[comments]" value="<?php if(isset($result['comments'])) echo $result['comments'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Drug route</label></td>
            <td>
                <?php
                $value = '';
                if(isset($result['drug_route'])) {
                    $value = $result['drug_route'];
                }
                echo generate_select_list('immunization_code[drug_route]', 'Drug_Route', $value,'Select Drug Route', ' ');
                ?>
            </td>
        </tr>
        <tr>
            <td><label for="cccc">Vis Date</label></td>
            <td><input type="text" name="immunization_code[vis_date]" value="<?php if(isset($result['vis_date'])) echo $result['vis_date'] ?>"></td>
        </tr>
    </table>
    <input type="hidden" name="immunization_code[id]" value="<?php if(isset($result['id'])) echo $result['id'] ?>">
    <input type="hidden" name="immunization_code[schedule_id]" value="<?php if(isset($_GET['schedule_id'])) echo $_GET['schedule_id'] ?>">
    <button><?php echo $action == 'add' ? 'Add' : 'Save';?></button>
</form>
</body>
</html>
<script language="javascript">
    $(document).ready(function() {
        $('#scanner_code').focus();
        var typingTimer;
        var waitTime = 400;
        var input = $('#scanner_code');
        input.on('keyup', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(doneTyping, waitTime);
        });

        input.on('keydown', function () {
            clearTimeout(typingTimer);
        });

        function doneTyping() {

            //Pull information from the 2-D code.  Alert user if they are not using the UoS
            var scanned = $('#scanner_code').val();

            console.log(scanned);
            var gs11 = scanned.substr(0, 2);      //GS1 Application Identifier for GTIN '01'
            var gtin = scanned.substr(2, 14);     //GTIN with embedded NDC
            var gs12 = scanned.substr(16, 2);     //GS1 Application Identifier for Expiration Date '17'
            var expDate = scanned.substr(18, 6);     //Expiration date in format YYMMDD
            var gs13 = scanned.substr(23, 2);     //gs1 Application Identifier for Lot NUM '10'
            var lotNum = scanned.substr(26, 100);   //Lot Number

            //get the NDC from the GTIN
            var gs1ID = gtin.substr(0, 1); //GD1 indiciator digit
            var gs1USP = gtin.substr(1, 2); // GS1 US Placeholder
            var ndc = gtin.substr(3, 10); //NDC
            var chk = gtin.substr(13, 1); //check digit

            //if the gtin is valid and the check digit is correct, we can fill the form and
            //check the database to see if the the GTIN exists in the immunizations_schedules_codes table.
            //If the NDC code is not in there, or if it is different the user can update the
            //immunizations_schedules_codes table.
            if (check_GTIN_digit(gtin)) {

                console.log("gtin passed");
                $('#gtin').val(gtin).css('background-color', 'green');
                $('#scanner_code').val('').focus().css('background-color', 'white');
            } else {
                //GTIN didn't work
                alert("GTIN Scanning Error, Please try again");
                $('#gtin').val('');
                $('#scanner_code').val('').focus().css('background-color', 'red');



            }

            function check_GTIN_digit(gtin) {

                if (gtin.length == 14) {
                    var chkDigt = gtin.slice(-1);
                    var chkSum = 0;

                    console.log("GTIN " + gtin);
                    console.log("Check Digit " + chkDigt);

                    for (var i = 0; i < gtin.length - 1; i++) {
                        if (i % 2 == 0) {
                            chkSum = chkSum + 3 * parseInt(gtin[i]);
                        } else {

                            chkSum = chkSum + parseInt(gtin[i]);
                        }
                    }

                    console.log(chkSum);
                    var chkDigitCalc = Math.ceil(chkSum / 10) * 10 - chkSum;
                    console.log(chkDigitCalc);
                    if (chkDigt == chkDigitCalc) {
                        return true;

                    } else {

                        return false;
                    }

                } else {

                    console.log("Length of the GTIN " + gtin.length);

                    return false;
                }


            }


        }
    });

</script>
