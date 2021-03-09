<?php

/**
 *  This file imports data from the cdc NDC crosswalk tables so end users can scan immunizations into the database without
 *  having to enter immunization data manually.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Daniel Pflieger daniel@growlingflea.com
 * @copyright Copyright (c) 2018 Daniel Pflieger daniel@growlingflea.com
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */


require_once("../../../interface/globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/acl.inc");
require_once("../../../custom/code_types.inc.php");
include_once("$srcdir/registry.inc");
include_once("$srcdir/options.inc.php");

use OpenEMR\Core\Header;

Header::setupHeader(['opener',  'dialog', 'jquery', 'common']);


?>

<html>
<head>
    <?php html_header_show();?>
    <link rel="stylesheet" href="<?php echo $GLOBALS['css_header'];?>" type="text/css">
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

</head>
<body class="body_top" onunload='imclosing()' >
<span class="title"><?php echo $_GET['action'] == 'add' ? 'Add' : 'Edit'; echo ' Code';?></span>
<br><br>
<div class="error">
    <?php
    if(isset($errorMessage)) {
        echo $errorMessage;
    }
    ?>
</div>

<?php



if(isset($_POST['immunizations_codes_edit']['GTIN'])){

    if($_POST['immunizations_codes_edit']['action'] === 'add'){

        $query = "INSERT into immunizations_schedules_codes ( description, manufacturer, cvx_code, justify_codes, proc_codes, default_site, comments, drug_route, vis_date , GTIN) " .

         " values (?,?,?,?,?,?,?,?,?,?)";

        $queryArray = array($_POST['immunizations_codes_edit']['description'], $_POST['immunizations_codes_edit']['manufacturer'],
            $_POST['immunizations_codes_edit']['cvx_code'], $_POST['immunizations_codes_edit']['justify_codes'], $_POST['immunizations_codes_edit']['proc_codes'],
            $_POST['default_site'], $_POST['immunizations_codes_edit']['comments'], $_POST['drug_route'], $_POST['immunizations_codes_edit']['vis_date'],
            $_POST['immunizations_codes_edit']['GTIN']);

    }else {

        $query = "Update immunizations_schedules_codes " .
            "set description = ? " .
            ", manufacturer = ? " .
            ", cvx_code = ? " .
            ", justify_codes = ? " .
            ", proc_codes = ? " .
            ", default_site = ? " .
            ", comments = ? " .
            ", drug_route = ? " .
            ", vis_date = ? ";

        $query .= " where GTIN = ? and id = ?";

        $queryArray = array($_POST['immunizations_codes_edit']['description'], $_POST['immunizations_codes_edit']['manufacturer'],
            $_POST['immunizations_codes_edit']['cvx_code'], $_POST['immunizations_codes_edit']['justify_codes'], $_POST['immunizations_codes_edit']['proc_codes'],
            $_POST['default_site'], $_POST['immunizations_codes_edit']['comments'], $_POST['drug_route'], $_POST['immunizations_codes_edit']['vis_date'],
            $_POST['immunizations_codes_edit']['GTIN'], $_POST['immunizations_codes_edit']['id']);

    }



    $success = sqlStatement($query, $queryArray);


}

if ($_POST['form_action'] != "") {
    echo "<html>\n<body>\n<script language='JavaScript'>\n";
    if ($info_msg) echo " alert('" . addslashes($info_msg) . "');\n";
    echo " if (opener && !opener.closed && opener.refreshme) {\n " .
        "  opener.refreshme();\n " . // This is for standard calendar page refresh
        " } else {\n " .
        " opener.refreshPage() ;\n " . // This is for patient flow board page refresh
        " };\n";
    echo "window.opener.location.reload(false);";
    echo " window.close();\n";
    echo "</script>\n</body>\n</html>\n";
    exit();
}



?>



<form method="post" action="view_edit_immunization.php" onSubmit="parent.refreshPage()">
    <table class="immunization_code_form">
        <tr>
            <td class="admin" hidden><label for="id">ID</label></td>
            <td class="admin" hidden><input id='id' type="text" name="immunizations_codes_edit[id]" value="<?php if(isset($result['id'])) echo $result['id'] ?>"></td>
        </tr>
        <tr>
            <td><label for="description">Description</label></td>
            <td><input id='desc' type="text" name="immunizations_codes_edit[description]" value="<?php if(isset($result['description'])) echo $result['description'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Manufacturer</label></td>
            <td><input id='manufacturer' type="text" name="immunizations_codes_edit[manufacturer]" value="<?php if(isset($result['manufacturer'])) echo $result['manufacturer'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Cvx Code</label></td>
            <td><input id='cvx_code' type="text" name="immunizations_codes_edit[cvx_code]" value="<?php if(isset($result['cvx_code'])) echo $result['cvx_code'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">GTIN</label></td>
            <td><input id='gtin' type="text" name="immunizations_codes_edit[GTIN]" value="<?php if(isset($result['GTIN'])) echo $result['GTIN'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Proc Codes</label></td>
            <td><input id='proc_codes' type="text" name="immunizations_codes_edit[proc_codes]" value="<?php if(isset($result['proc_codes'])) echo $result['proc_codes'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Justify Codes</label></td>
            <td><input id='justify_codes' type="text" name="immunizations_codes_edit[justify_codes]" value="<?php if(isset($result['justify_codes'])) echo $result['justify_codes'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Default Site</label></td>
            <td>
                <?php
                $svalue = '';
                if(isset($result['default_site'])) {
                    $svalue = $result['default_site'];
                }
                echo generate_select_list('default_site', 'Imm_Administrative_Site__CAIR', $svalue,'Select Default Site', ' ');
                ?>
            </td>
        </tr>
        <tr>
            <td><label for="cccc">Comments</label></td>
            <td><input id='comments' type="text" name="immunizations_codes_edit[comments]" value="<?php if(isset($result['comments'])) echo $result['comments'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Drug route</label></td>
            <td>
                <?php
                $value = '';
                if(isset($result['drug_route'])) {
                    $value = $result['drug_route'];
                }
                echo generate_select_list('drug_route', 'drug_route', $value,'Select Drug Route', ' ');
                ?>
            </td>
        </tr>
        <tr>
            <td><label for="cccc">Vis Date</label></td>
            <td><input id="vis_date" type="text" name="immunizations_codes_edit[vis_date]" value="<?php if(isset($result['vis_date'])) echo $result['vis_date'] ?>"></td>
        </tr>
    </table>
    <input type="hidden" name="immunizations_codes_edit[schedule_id]" value="<?php if(isset($_GET['schedule_id'])) echo $_GET['schedule_id'] ?>">
    <input type="hidden" name="immunizations_codes_edit[action]" value="<?php if(isset($_GET['action'])) echo $_GET['action'] ?>">

    <input type="hidden" name="form_action" id="form_action" value="save">
    <button id="save_button" name="saved_button"><?php echo 'Save';?></button>
</form>
</body>
</html>
<script language="javascript">

    $(document).ready(function() {

        var gtin = "<?php echo ($_GET['GTIN']); ?>";
        var id = "<?php echo ($_GET['id']); ?>"
        $.ajax({
            type: "POST",
            url: "../../../library/ajax/immunizations_ajax.php",
            data: {
                func:"check_GTIN",
                GTIN: gtin,
                id: id,
            },
            success:function(response){
                var formData = JSON.parse(response);

                console.log(response);
                $('#id').val(formData['id']);
                $('#desc').val(formData['description']);
                $('#manufacturer').val(formData['manufacturer']);
                $('#cvx_code').val(formData['cvx_code']);
                $('#gtin').val(formData['GTIN']);
                $('#proc_codes').val(formData['proc_codes']);
                $('#justify_codes').val(formData['justify_codes']);
                $('#comments').val(formData['comments']);
                $('#vis_date').val(formData['vis_date']);
                $('#default_site').val(formData['default_site']);
                $('#drug_route').val(formData['drug_route']);

            }
        });


    });


</script>
