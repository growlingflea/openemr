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


if(isset($_POST['age_group_id'])) {
    $age_group_id = $_POST['age_group_id'];
    if(empty($age_group_id)) {
        exit('');
    }
    $query = sqlStatement("SELECT *
        FROM immunizations_schedules ims
        JOIN immunizations_schedules_options iso ON ims.id = iso.schedule_id
        JOIN immunizations_schedules_codes isc ON isc.id = iso.code_id
        WHERE ims.id = ?", array($age_group_id));


    require_once 'temp/schedule_list.php';
    exit;
}
if(isset($_POST['immunization_schedule'])) {
    $post = validationImmunizationCode($_POST['immunization_schedule']);

    if($post['id']) {
        $stmt = sqlQuery("UPDATE immunizations_schedules set description = ?,
            age = ?,
            age_max = ?,
            frequency = ?
            where id=?", array($post['description'],$post['age'],$post['age'],$post['age_max'],
                                $post['frequency'], $post['id']));



        if($stmt) {
            $successMessage = 'Schedule updated success!';
        } else {
            $errorMessage = 'SQL error';
        }

    } else {
        $id = sqlQuery("select MAX(id) from immunizations_schedules where id != 0 ");
        $id = $id['MAX(id)'] + 1;

        $stmt = sqlInsert("INSERT INTO immunizations_schedules (description, age, age_max, frequency) VALUES (?,?,?,?)",
                            array($post['description'], $post['age'], $post['age_max'], $post['frequency']));

        if($stmt) {
            $successMessage = 'Schedule added success!';
        } else {
            $errorMessage = 'SQL error';
        }


    }
}

if(isset($errorMessage)) {
    require_once 'temp/schedule_form.php';
    exit;
}
if(isset($_GET['action'])) {
    switch($_GET['action']) {
        case 'add':
            $action = "add";
            require_once 'temp/schedule_form.php';
            exit;
            break;
        case 'edit':
            if(isset($_GET['id'])) {
                $query = sqlStatement("SELECT * FROM immunizations_schedules WHERE id = ?", array('id' => $_GET['id']));

                $result = sqlFetcharray($query);
            }
            $action = "edit";
            require_once 'temp/schedule_form.php';
            exit;
            break;
        case 'del':
            if(isset($_GET['id'])) {
                $query = sqlQuery("DELETE FROM immunizations_schedules WHERE id = ?", $_GET['id']);

                if($query) {
                    $successMessage = 'Schedule deleted success!';
                } else {
                    $errorMessage = 'SQL error';
                }
            }
            break;
    }
}

$results = sqlStatement("SELECT id,description FROM immunizations_schedules ORDER BY age DESC");

require_once 'temp/view_table_schedule.php';

?>
