<?php
function validationImmunizationCode ($post) {
    foreach ($post as &$item) {
        $item = strip_tags($item);
    }

    return $post;
}
include_once("../globals.php");
include_once("$srcdir/registry.inc");
include_once("$srcdir/sql.inc");
include_once("$srcdir/options.inc.php");



if(isset($_POST['immunization_code'])) {
    $post = validationImmunizationCode($_POST['immunization_code']);

    if($post['id']) {

        $query = "Update immunizations_schedules_codes set description = ?, ".
            "manufacturer = ? ,".
            "cvx_code = ? ,".
            "proc_codes = ? ,".
            "justify_codes = ? ,".
            "default_site = ? , ".
            "comments = ? , ".
            "drug_route = ?  ".
            "where id = ?";

        $res = sqlStatement($query, array($post['description'],$post['manufacturer'],$post['cvx_code'],
            $post['proc_codes'], $post['justify_codes'], $post['default_site'],
            $post['comments'], $post['drug_route'], $post['id']));

        if($res) {
            $successMessage = 'Code updated success!';
        } else {
            $errorMessage = 'SQL error';
        }

    } else {
        try {

            //need to get last id to insert next record.
            $id = sqlQuery("select MAX(id) from immunizations_schedules_codes where id != 0 ");
            $id = $id['MAX(id)'] + 1;
            $insert_ID = $id;

            $insert =  sqlInsert("INSERT INTO immunizations_schedules_codes ".
                "(id, description, manufacturer, cvx_code, proc_codes, justify_codes, ".
                "default_site, comments, drug_route) VALUES ($id,?,?,?,?,?,?,?,?)",
                array($post['description'], $post['manufacturer'], $post['cvx_code'], $post['proc_codes'], $post['justify_codes'],
                    $post['default_site'], $post['comments'], $post['drug_route']));


            //need to get last id to insert next record.
            $id = sqlQuery("select MAX(id) from immunizations_schedules_options where id != 0 ");
            $id = $id['MAX(id)'] + 1;

            if ($post['schedule_id']) {

                $stmt = sqlInsert("INSERT INTO immunizations_schedules_options (id, schedule_id, code_id) VALUES (?,?,?)", array($id, $post['schedule_id'], $insert_ID));

            }
            $successMessage = 'Code added success!';

        } catch (Exception $e) {

            $errorMessage = 'SQL error: ' . $e->getMessage();
        }
    }
}

if(isset($errorMessage)) {
    require_once 'temp/code_form.php';
    exit;
}
if(isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'add':
            $action = "add";
            require_once 'temp/code_form.php';
            exit;
            break;
        case 'edit':
            if(isset($_GET['id'])) {
                $query = sqlStatement("SELECT * FROM immunizations_schedules_codes WHERE id = ?", array($_GET['id']));
                $result = sqlFetchArray($query);
            }
            $action = "edit";
            require_once 'temp/code_form.php';
            exit;
            break;
        case 'del':
            if(isset($_GET['id'])) {
                $query = sqlStatement("DELETE FROM immunizations_schedules_codes WHERE id = ?", array($_GET['id']));

                if($query) {
                    $successMessage = 'Code deleted success!';
                } else {
                    $errorMessage = 'SQL error';
                }
            }
            break;
    }
}

$results = sqlStatement("SELECT * FROM immunizations_schedules_codes");

require_once 'temp/view_table.php';

?>
