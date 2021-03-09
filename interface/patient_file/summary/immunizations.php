<?php
/**
 * Immunizations
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2018 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 *
 * Modified by Daniel Pflieger for interaction with CAIR
 * * @author    Daniel Pflieger <daniel@growlingflea.com>
 * @copyright Copyright (c) 2018 Daniel Pflieger <daniel@growlingflea.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../../globals.php");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/immunization_helper.php");

//*** SANTI ADD: include files
include_once("$srcdir/sql.inc");
require_once("../../../custom/code_types.inc.php");
require_once("$srcdir/CAIRsoap.php");
use OpenEMR\Core\Header; //***Santipeds add
$cairserver = (strpos($GLOBALS['gl_value'], 'CATRN')) ? "Training" : "Production";
$cairmsg = $cairserver == "Training" ? "Vaccine will not be updated on the live system" : "Vaccine will be submitted to CAIR";
//*** SANTI ADD END: include files
if (isset($_GET['mode'])) {
    /*
	 * THIS IS A BUG. IF NEW IMMUN IS ADDED AND USER PRINTS PDF,
	 * WHEN BACK IS CLICKED, ANOTHER ITEM GETS ADDED
	 */

    //***SANTI ADD historical and vfc  for CAIR2
    if ($_GET['mode'] == "add") {
        $sql = "REPLACE INTO immunizations set
                      id = ?,
                      administered_date = if(?,?,NULL),
                      immunization_id = ?,
                      cvx_code = ?,
                      manufacturer = ?,
                      lot_number = ?,
                      administered_by_id = if(?,?,NULL),
                      administered_by = if(?,?,NULL),
                      education_date = if(?,?,NULL),
                      vis_date = if(?,?,NULL),
                      note   = ?,
                      patient_id   = ?,
                      created_by = ?,
                      updated_by = ?,
   				      create_date = now(),
					  amount_administered = ?,
					  amount_administered_unit = ?,
					  expiration_date = if(?,?,NULL),
					  route = ?,
					  administration_site = ? ,
                      completion_status = ?,
                      information_source = ?,
                      refusal_reason = ?,
                      ordering_provider = ?,
                      historical = ?,
                      vfc = ?,
                      ndc = if(?,?,NULL),
                      gtin = if(?,?,NULL) ";
        $sqlBindArray = array(
            trim($_GET['id']),
            trim($_GET['administered_date']), trim($_GET['administered_date']),
            trim($_GET['form_immunization_id']),
            trim($_GET['cvx_code']),
            trim($_GET['manufacturer']),
            trim($_GET['lot_number']),
            trim($_GET['administered_by_id']), trim($_GET['administered_by_id']),
            trim($_GET['administered_by']), trim($_GET['administered_by']),
            trim($_GET['education_date']), trim($_GET['education_date']),
            trim($_GET['vis_date']), trim($_GET['vis_date']),
            trim($_GET['note']),
            $pid,
            $_SESSION['authId'],
            $_SESSION['authId'],
            trim($_GET['immuniz_amt_adminstrd']),
            trim($_GET['form_drug_units']),
            trim($_GET['immuniz_exp_date']), trim($_GET['immuniz_exp_date']),
            trim($_GET['immuniz_route']),
            trim($_GET['immuniz_admin_ste']),
            trim($_GET['immuniz_completion_status']),
            trim($_GET['immunization_informationsource']),
            trim($_GET['immunization_refusal_reason']),
            trim($_GET['ordered_by_id']),
            trim($_GET['historical']), //***SANTI ADD added for CAIR2
            trim($_GET['vfc']),          //***SANTI ADD added for CAIR2
            trim($_GET['ndc']), trim($_GET['ndc']), //***SANTI added for CAIR2
            trim($_GET['scanner_code']), trim($_GET['scanner_code']) //***SANTI added for CAIR2
        );
        $newid = sqlInsert($sql,$sqlBindArray);
        $administered_date=date('Y-m-d H:i');
        $education_date=date('Y-m-d');
        $immunization_id=$cvx_code=$manufacturer=$lot_number=$administered_by_id=$note=$id=$ordered_by_id="";
        $administered_by=$vis_date="";
        $newid = $_GET['id'] ? $_GET['id'] : $newid;
        if($GLOBALS['observation_results_immunization']) {
            saveImmunizationObservationResults($newid,$_GET);
        }
    } else if ($_GET['mode'] == "delete" ) {
        // log the event
        newEvent("delete", $_SESSION['authUser'], $_SESSION['authProvider'], 1, "Immunization id ".$_GET['id']." deleted from pid ".$pid);
        // delete the immunization
        $sql="DELETE FROM immunizations WHERE id =? LIMIT 1";
        sqlStatement($sql, array($_GET['id']));
    } else if ($_GET['mode'] == "added_error" ) {
        $sql = "UPDATE immunizations " .
            "SET added_erroneously=? "  .
            "WHERE id=?";
        $sql_arg_array = array(
            ($_GET['isError'] === 'true'),
            $_GET['id']
        );
        sqlStatement($sql, $sql_arg_array);
    } else if ($_GET['mode'] == "edit" ) {
        $sql = "select * from immunizations where id = ?";
        $result = sqlQuery($sql, array($_GET['id']));

        $administered_date = new DateTime($result['administered_date']);
        $administered_date = $administered_date->format('Y-m-d H:i');

        $immuniz_amt_adminstrd = $result['amount_administered'];
        $drugunitselecteditem = $result['amount_administered_unit'];
        $immunization_id = $result['immunization_id'];
        $immuniz_exp_date = $result['expiration_date'];

        $cvx_code = $result['cvx_code'];
        $code_text = '';
        if ( !(empty($cvx_code)) ) {
            $query = "SELECT codes.code_text as `code_text`, codes.code as `code` " .
                "FROM codes " .
                "LEFT JOIN code_types on codes.code_type = code_types.ct_id " .
                "WHERE code_types.ct_key = 'CVX' AND codes.code = ?";
            $result_code_text = sqlQuery($query, array($cvx_code));
            $code_text = $result_code_text['code_text'];
        }
        $manufacturer = $result['manufacturer'];
        $lot_number = $result['lot_number'];
        $administered_by_id = ($result['administered_by_id'] ? $result['administered_by_id'] : 0);
        $ordered_by_id      = ($result['ordering_provider'] ? $result['ordering_provider'] : 0);
        $entered_by_id      = ($result['created_by'] ? $result['created_by'] : 0);

        //***SANTI ADD historical and vfc added for CAIR2
        $historical = $result['historical'];
        $vfc = $result['vfc'];


        $administered_by = "";
        if (!$result['administered_by'] && !$row['administered_by_id']) {
            $stmt = "select CONCAT(IFNULL(lname,''), ' ,',IFNULL(fname,'')) as full_name ".
                "from users where ".
                "id=?";
            $user_result = sqlQuery($stmt, array($result['administered_by_id']));
            $administered_by = $user_result['full_name'];
        }

        $education_date = $result['education_date'];
        $vis_date = $result['vis_date'];
        $immuniz_route = $result['route'];
        $immuniz_admin_ste = $result['administration_site'];
        $note = $result['note'];
        $isAddedError = $result['added_erroneously'];

        $immuniz_completion_status = $result['completion_status'];
        $immuniz_information_source = $result['information_source'];
        $immuniz_refusal_reason     = $result['refusal_reason'];
        //set id for page
        $id = $_GET['id'];

        $imm_obs_data = getImmunizationObservationResults();
    }
}

$observation_criteria = getImmunizationObservationLists('1');
$observation_criteria_value = getImmunizationObservationLists('2');
// Decide whether using the CVX list or the custom list in list_options
if ($GLOBALS['use_custom_immun_list']) {
    // user forces the use of the custom list
    $useCVX = false;
} else {
    if ($_GET['mode'] == "edit") {
        //depends on if a cvx code is enterer already
        if (empty($cvx_code)) {
            $useCVX = false;
        } else {
            $useCVX = true;
        }
    } else { // $_GET['mode'] == "add"
        $useCVX = true;
    }
}

// set the default sort method for the list of past immunizations
$sortby = $_GET['sortby'];
if (!$sortby) {
    $sortby = 'vacc';
}

// set the default value of 'administered_by'
if (!$administered_by && !$administered_by_id) {
    $stmt = "select CONCAT(IFNULL(lname,''), ' ,',IFNULL(fname,'')) as full_name ".
        " from users where ".
        " id=?";
    $row = sqlQuery($stmt, array($_SESSION['authId']));
    $administered_by = $row['full_name'];
}

// get the entered username
if ($entered_by_id) {
    $stmt = "select CONCAT(IFNULL(lname,''), ' ,',IFNULL(fname,'')) as full_name ".
        " from users where ".
        " id=?";
    $row = sqlQuery($stmt, array($entered_by_id));
    $entered_by = $row['full_name'];
}

if ($_POST['type'] == 'duplicate_row') {
    $observation_criteria = getImmunizationObservationLists('1');
    echo json_encode($observation_criteria);
    exit;
}
if ($_POST['type'] == 'duplicate_row_2') {
    $observation_criteria_value = getImmunizationObservationLists('2');
    echo json_encode($observation_criteria_value);
    exit;
}

function getImmunizationObservationLists($k)
{
    if ($k == 1) {
        $observation_criteria_res = sqlStatement("SELECT * FROM list_options WHERE list_id = ? AND activity=1 ORDER BY seq, title", array('immunization_observation'));
        for ($iter = 0; $row = sqlFetchArray($observation_criteria_res); $iter++) {
            $observation_criteria[0]['option_id'] = '';
            $observation_criteria[0]['title']     = 'Unassigned';
            $observation_criteria[++$iter] = $row;
        }
        return $observation_criteria;
    } else {
        $observation_criteria_value_res = sqlStatement("SELECT * FROM list_options WHERE list_id = ? AND activity=1 ORDER BY seq, title", array('imm_vac_eligibility_results'));
        for ($iter = 0; $row = sqlFetchArray($observation_criteria_value_res); $iter++) {
            $observation_criteria_value[0]['option_id'] = '';
            $observation_criteria_value[0]['title']     = 'Unassigned';
            $observation_criteria_value[++$iter] = $row;
        }
        return $observation_criteria_value;
    }
}

function getImmunizationObservationResults()
{
    $obs_res_q = "SELECT
                  *
                FROM
                  immunization_observation
                WHERE imo_pid = ?
                  AND imo_im_id = ?";
    $res = sqlStatement($obs_res_q, array($_SESSION["pid"],$_GET['id']));
    for ($iter = 0; $row = sqlFetchArray($res); $iter++)
        $imm_obs_data[$iter] = $row;
    return $imm_obs_data;
}

function saveImmunizationObservationResults($id,$immunizationdata)
{
    $imm_obs_data = getImmunizationObservationResults();
    if(count($imm_obs_data) > 0) {
        foreach($imm_obs_data as $key=>$val) {
            if($val['imo_id'] && $val['imo_id'] != 0 ) {
                $sql2                   = " DELETE
                                            FROM
                                              immunization_observation
                                            WHERE imo_im_id = ?
                                              AND imo_pid = ?";
                $result2                = sqlQuery($sql2,array($val['imo_im_id'],$val['imo_pid']));
            }
        }
    }
    for($i = 0; $i < $immunizationdata['tr_count']; $i++) {
        if($immunizationdata['observation_criteria'][$i] == 'vaccine_type') {
            $code                     = $immunizationdata['cvx_vac_type_code'][$i];
            $code_text                = $immunizationdata['code_text_hidden'][$i];
            $code_type                = $immunizationdata['code_type_hidden'][$i];
            $vis_published_dateval    = $immunizationdata['vis_published_date'][$i] ? $immunizationdata['vis_published_date'][$i] : '';
            $vis_presented_dateval    = $immunizationdata['vis_presented_date'][$i] ? $immunizationdata['vis_presented_date'][$i] : '';
            $imo_criteria_value       = '';
        } else if($immunizationdata['observation_criteria'][$i] == 'disease_with_presumed_immunity') {
            $code                     = $immunizationdata['sct_code'][$i];
            $code_text                = $immunizationdata['codetext'][$i];
            $code_type                = $immunizationdata['codetypehidden'][$i];
            $imo_criteria_value       = '';
            $vis_published_dateval    = '';
            $vis_presented_dateval    = '';
        }else if($immunizationdata['observation_criteria'][$i] == 'funding_program_eligibility') {
            $imo_criteria_value       = $immunizationdata['observation_criteria_value'][$i];
            $code                     = '';
            $code_text                = '';
            $code_type                = '';
            $vis_published_dateval    = '';
            $vis_presented_dateval    = '';
        }

        if($immunizationdata['observation_criteria'][$i] != '') {
            $sql                      = " INSERT INTO immunization_observation (
                                          imo_im_id,
                                          imo_pid,
                                          imo_criteria,
                                          imo_criteria_value,
                                          imo_user,
                                          imo_code,
                                          imo_codetext,
                                          imo_codetype,
                                          imo_vis_date_published,
                                          imo_vis_date_presented
                                        )
                                        VALUES
                                          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $res                      = sqlQuery($sql, array($id,$_SESSION["pid"],$immunizationdata['observation_criteria'][$i],$imo_criteria_value,$_SESSION['authId'],$code, $code_text, $code_type,$vis_published_dateval,$vis_presented_dateval));
        }
    }
    return;
}

//Takes in $cvx_code and returns the date specified by the immunization schedule
function getDefaultVisDate($cvx_code){
    $sql = "select vis_date from immunizations_schedules_codes where cvx_code = ?";
    $res =  sqlQuery($sql, array($cvx_code));
    return $res['vis_date'];
}




?>
<html>
<head>
    <?php html_header_show();?>

    <!-- supporting javascript code -->
    <?php //***SANTI ADD ?>
    <?php Header::setupHeader(['opener', 'common', 'datetime-picker', 'dialog', 'jquery', 'knockout']); //***Santi peds add/modify ?>


    <!-- page styles -->
    <?php //***SANTI ADD ?>
    <link rel="stylesheet" href="<?php echo $GLOBALS['css_header'];?>" type="text/css">

    <style>
        .highlight {
            color: green;
        }
        tr.selected {
            background-color: white;
        }
    </style>

    <!-- pop up calendar -->
    <style type="text/css">@import url(<?php echo $GLOBALS['webroot'] ?>/library/dynarch_calendar.css);</style>
    <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dynarch_calendar.js"></script>
    <?php include_once("{$GLOBALS['srcdir']}/dynarch_calendar_en.inc.php"); ?>
    <script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dynarch_calendar_setup.js"></script>

    <script language="JavaScript">
        // required to validate date text boxes
        var mypcc = '<?php echo htmlspecialchars( $GLOBALS['phone_country_code'], ENT_QUOTES); ?>';
    </script>

</head>

<body class="body_top">

<?php //***SANTI ADD ?>
<div>
     <span class="title"><?php echo htmlspecialchars( xl('Immunizations: '), ENT_NOQUOTES); ?></span>
     <span>Connected to :<?php echo "$cairserver. $cairmsg" ?></span>
</div>

<?php //***SANTI ADD ?>
<div style="float:left;">
<div><span><button id="cair_qpd">Query CAIR for Recorded Immunizations</button></span></div>
    <br>
<form action="immunizations.php" name="add_immunization" id="add_immunization">
    <input type="hidden" name="mode" id="mode" value="add">
    <input type="hidden" name="id" id="id" value="<?php echo htmlspecialchars( $id, ENT_QUOTES); ?>">
    <input type="hidden" name="pid" id="pid" value="<?php echo htmlspecialchars( $pid, ENT_QUOTES); ?>">
    <br>
    <table border=0 cellpadding=1 cellspacing=1>
        <?php
        if ($isAddedError) {
            echo "<tr><font color='red'><b>" . xlt("Entered in Error") . "</b></font></tr>";
        }
        ?>

        <?php if (!($useCVX)) { ?>
            <tr>
                <td align="right">
                <span class='text'>
              <?php echo htmlspecialchars( xl('Immunization'), ENT_NOQUOTES); ?>            </span>          </td>
                <td>
                    <?php
                    // Modified 7/2009 by BM to incorporate the immunization items into the list_options listings
                    generate_form_field(array('data_type'=>1,'field_id'=>'immunization_id','list_id'=>'immunizations','empty_title'=>'SKIP'), $immunization_id);
                    ?>
                </td>
            </tr>
        <?php } else { ?>
            <tr>
                <td align="right" valign="top" style="padding-top:4px;">
                <?php //***SANTI ADD ?>
                <span class='text'>
              <?php echo htmlspecialchars( xl('2D Scanner Code'), ENT_NOQUOTES); ?> </span>          </td>
                <td>
                    <input type='text' size='40' name='scanner_code' id='scanner_code'
                           value=''
                           title='<?php echo htmlspecialchars( xl('Use Scanner and Insert Code Here'), ENT_QUOTES); ?>'
                    />
                </td>
            </tr>

            <tr>
                <td align="right">
                <span class='text'>

                <?php echo htmlspecialchars( xl('NDC'), ENT_NOQUOTES); ?>            </span>          </td>
                <td>
                    <input id = 'ndc' class='text' type='text' name="ndc" size="25" value="">          </td>
            </tr>

            <tr>
                <td align="right">

                        <span class='text'>
                        <?php echo htmlspecialchars( xl('Description'), ENT_NOQUOTES); ?>
                    </span>

                </td>

                <td>
                    <textarea id='cvx_description' cols="80" rows="2">
                        <?php echo htmlspecialchars( xl( $code_text ), ENT_QUOTES); ?>
                    </textarea>
                </td>


            </tr>




            <tr>
                <td align="right" valign="top" style="padding-top:4px;">
                <span class='text'>
                <?php //***SANTI ADD END ?>
              <?php echo htmlspecialchars( xl('Immunization'), ENT_NOQUOTES); ?> (<?php echo htmlspecialchars( xl('CVX Code'), ENT_NOQUOTES); ?>)            </span>          </td>
                <td>
                    <input type='text' size='10' name='cvx_code' id='cvx_code'
                           value='<?php echo htmlspecialchars($cvx_code,ENT_QUOTES); ?>' onclick='sel_cvxcode(this)'
                           title='<?php echo htmlspecialchars( xl('Click to select or change CVX code'), ENT_QUOTES); ?>'
                </td>
            </tr>
        <?php } ?>

        <tr>
            <td align="right">
            <span class=text>
              <?php echo htmlspecialchars( xl('Date & Time Administered'), ENT_NOQUOTES); ?>            </span>          </td>
            <td><table border="0">
                    <tr>
                        <td><input type='text' size='14' name="administered_date" id="administered_date" class="datepicker"
                                   value='<?php echo $administered_date ? htmlspecialchars( $administered_date, ENT_QUOTES) : date('Y-m-d H:i:s'); ?>'
                                   title='<?php echo htmlspecialchars( xl('yyyy-mm-dd Hours(24):minutes:seconds'), ENT_QUOTES); ?>'
                                   onKeyUp='datekeyup(this,mypcc)' onBlur='dateblur(this,mypcc);'
                            />
                            <img src='<?php echo $rootdir; ?>/pic/show_calendar.gif' align='absbottom' width='24' height='22'
                                 id='img_administered_date' border='0' alt='[?]' style='cursor:pointer;cursor:hand'
                                 title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>'>
                        </td>
                    </tr>
                </table></td>
        </tr>
        <tr>
            <td align="right"><span class="text"><?php echo htmlspecialchars( xl('Amount Administered'), ENT_NOQUOTES); ?></span></td>
            <td class='text'>
                <input class='text' type='text' id="immuniz_amt_adminstrd" name="immuniz_amt_adminstrd" size="25" value="<?php echo htmlspecialchars( $immuniz_amt_adminstrd, ENT_QUOTES); ?>">
                <?php echo generate_select_list("form_drug_units", "drug_units", $drugunitselecteditem,'Select Drug Unit',''); ?>
            </td>
        </tr>
        <tr>
            <td align="right"><span class="text"><?php echo htmlspecialchars( xl('Immunization Expiration Date'), ENT_NOQUOTES); ?></span></td>
            <td class='text'><input type='text' size='10' name="immuniz_exp_date" id="immuniz_exp_date"
                                    value='<?php echo $immuniz_exp_date ? htmlspecialchars( $immuniz_exp_date, ENT_QUOTES) : ''; ?>'
                                    title='<?php echo htmlspecialchars( xl('yyyy-mm-dd'), ENT_QUOTES); ?>'
                                    onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc);'
                />
                <img src='<?php echo $rootdir; ?>/pic/show_calendar.gif' align='absbottom' width='24' height='22'
                     id='img_immuniz_exp_date' border='0' alt='[?]' style='cursor:pointer;cursor:hand'
                     title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>'></td>
        </tr>
        <tr>
            <td align="right">
                <span class='text'>
                <!-- changed to use custom manufacturer chooser -->
                <?php echo htmlspecialchars( xl('Immunization Manufacturer'), ENT_NOQUOTES); ?>            </span>          </td>
            <td>
                <input id = 'manufacturer' class='text' type='text' name="manufacturer" size="25" value="<?php echo htmlspecialchars( $manufacturer, ENT_QUOTES); ?>">          </td>
        </tr>
        <tr>
            <td align="right">
                <span class='text'>
              <?php echo htmlspecialchars( xl('Immunization Lot Number'), ENT_NOQUOTES); ?>            </span>          </td>
            <td>
                <input class='text' type='text' id="lot_number" name="lot_number" size="25" value="<?php echo htmlspecialchars( $lot_number, ENT_QUOTES); ?>">          </td>
        </tr>
        <tr>
            <td align="right">
            <span class='text'>
                <?php //***SANTI Modify ?>
                  <?php echo htmlspecialchars( xl('Administrator Name'), ENT_NOQUOTES); ?>            </span>          </td>
            <td class='text'>
                <input type="text" name="administered_by" id="administered_by" size="25" value="<?php echo htmlspecialchars( $administered_by, ENT_QUOTES); ?>">
                <?php echo htmlspecialchars( xl('or choose'), ENT_NOQUOTES); ?>
                <!-- NEEDS WORK -->
                <select name="administered_by_id" id='administered_by_id'>
                    <option value=""></option>
                    <?php
                    $sql = "select id, CONCAT_WS(' ',lname,fname) as full_name " .
                        "from users where username != '' and password != 'NoLogin' " .
                        "order by full_name";

                    $result = sqlStatement($sql);
                    while($row = sqlFetchArray($result)){
                        echo '<OPTION VALUE=' . htmlspecialchars( $row{'id'}, ENT_QUOTES);
                        echo (isset($administered_by_id) && $administered_by_id != "" ? $administered_by_id : $_SESSION['authId']) == $row{'id'} ? ' selected>' : '>';
                        echo htmlspecialchars( $row{'full_name'}, ENT_NOQUOTES) . '</OPTION>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td align="right" class="text">
                <?php //***SANTI Modify ?>
                    <?php echo htmlspecialchars( xl('Date IIS Given'), ENT_NOQUOTES); ?>          </td>
            <td>
                <input type='text' size='10' name="education_date" id="education_date"
                       value='<?php echo $education_date? htmlspecialchars( $education_date, ENT_QUOTES) : date('Y-m-d'); ?>'
                       title='<?php echo htmlspecialchars( xl('yyyy-mm-dd'), ENT_QUOTES); ?>'
                       onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc);'
                />
                <img src='<?php echo $rootdir; ?>/pic/show_calendar.gif' align='absbottom' width='24' height='22'
                     id='img_education_date' border='0' alt='[?]' style='cursor:pointer;'
                     title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>'
                />          </td>
        </tr>
        <tr>
            <td align="right" class="text">
                <?php echo htmlspecialchars( xl('Date of VIS Statement'), ENT_NOQUOTES); ?>
                (<a href="https://www.cdc.gov/vaccines/hcp/vis/index.html" title="<?php echo htmlspecialchars( xl('Help'), ENT_QUOTES); ?>" target="_blank">?</a>)          </td>
            <td>
                <?php


                ?>

                <input type='text' size='10' name="vis_date" id="vis_date"
                       value='<?php echo $vis_date ? htmlspecialchars( $vis_date, ENT_QUOTES) : date('Y-m-d'); ?>'
                       title='<?php echo htmlspecialchars( xl('yyyy-mm-dd'), ENT_QUOTES); ?>'
                       onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc);'
                />
                <img src='<?php echo $rootdir; ?>/pic/show_calendar.gif' align='absbottom' width='24' height='22'
                     id='img_vis_date' border='0' alt='[?]' style='cursor:pointer;'
                     title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>'
                />          </td>
        </tr>
        <tr>
            <td align="right" class='text'><?php echo htmlspecialchars( xl('Route'), ENT_NOQUOTES); ?></td>
            <td>
                <?php //***SANTI Modify ?>
                <?php echo generate_select_list('immuniz_route', 'drug_routes', $immuniz_route, 'Select Route', '');?>
            </td>
        </tr>
        <tr>
            <td align="right" class='text'><?php echo htmlspecialchars( xl('Administration Site'), ENT_NOQUOTES); ?></td>
            <td>
                <?php //***SANTI Modify ?>
                <?php echo generate_select_list('immuniz_admin_ste', 'Imm_Administrative_Site__CAIR', $immuniz_admin_ste, 'Select Administration Site', ' ');?>
            </td>
        </tr>
        <tr>
        <?php //***SANTI ADD ?>
        <tr>
            <td align="right" class='text'><?php echo htmlspecialchars( xl('VFC Eligibility'), ENT_NOQUOTES); ?></td>
            <td>
                <select name = 'vfc' id="vfc">
                    <option value=""></option>
                    <option value="V01">V01: not VFC eligible (Private Pay/Insurance) </option>
                    <option value="V02" selected="selected">V02: VFC eligible – Medi-Cal/Medi-Cal Managed Care</option>
                    <option value="V03">V03: VFC eligible - Uninsured</option>
                    <option value="V04">V04: VFC eligible - American Indian/Alaskan Native </option>
                    <option value="V05">V05: VFC eligible - Underinsured</option>
                    <option value="V07">V06: Public vaccine - State- specific eligibility [317 Special Funds] </option>
                    <option value="CAA01">CAA01: State General Fund Vaccines</option>

                </select
            </td>
        </tr>
        <tr>
            <td align = "right">
                    <span class='text'><?php echo htmlspecialchars( xl('Historical?'), ENT_NOQUOTES); ?></span>
            </td>
            <td>
                <input id="historical" type="checkbox" name="historical" value="">
            </td>

        </tr>

        <?php $_GET['historical'] === '01' ? $_GET['historical'] = '01' : $_GET['historical'] = '00' ?>


        <tr>
            <td align="right" class='text'><?php echo htmlspecialchars( xl('VFC Eligibility'), ENT_NOQUOTES); ?></td>
            <?php //***SANTI ADD END ?>
            <td>
                <textarea class='text' name="note" id="note" rows=5 cols=25><?php echo htmlspecialchars( $note, ENT_NOQUOTES); ?></textarea>          </td>
        </tr>
        <tr>
            <td align="right" class='text'>
                <?php echo htmlspecialchars( xl('Information Source'), ENT_NOQUOTES); ?>
            </td>
            <td>
                <?php echo generate_select_list('immunization_informationsource', 'immunization_informationsource', $immuniz_information_source, 'Select Information Source', ' ');?>
            </td>
        </tr>
        <tr>
            <td align="right" class='text'>
                <?php echo htmlspecialchars( xl('Completion Status'), ENT_NOQUOTES); ?>          </td>
            <td>
                <?php echo generate_select_list('immuniz_completion_status', 'Immunization_Completion_Status', $immuniz_completion_status, 'Select Completion Status', ' ');?>          </td>
        </tr>
        <tr>
            <td align="right" class='text'>
                <?php echo htmlspecialchars( xl('Substance Refusal Reason'), ENT_NOQUOTES); ?>
            </td>
            <td>
                <?php echo generate_select_list('immunization_refusal_reason', 'immunization_refusal_reason', $immuniz_refusal_reason, 'Select Refusal Reason', ' ');?>
            </td>
        </tr>
        <tr>
            <td align="right" class='text'>
                <?php echo htmlspecialchars( xl('Immunization Ordering Provider'), ENT_NOQUOTES); ?>
            </td>
            <td>
                <?php //***SANTI Modify ?>
                <select name="ordered_by_id" id='ordered_by_id' title='<?php echo htmlspecialchars( xl('To select provider, the provider must be active.  Activate old providers in Admin -> Users'), ENT_QUOTES); ?>'>
                    <option value=""></option>
                    <?php
                    $sql = "select id, CONCAT(IFNULL(lname,''), ' ,',IFNULL(fname,'')) as full_name " .
                        "from users where username != '' and password != 'NoLogin' " .
                        " and authorized = 1 and calendar = 1 and active = 1 " . // //***SANTI modify
                        " order by full_name";

                    $result = sqlStatement($sql);
                    if($encounter >  0 ) {
                        $provider = sqlQuery("select * from form_encounter where encounter = $encounter");
                    }
                    while($row = sqlFetchArray($result)){
                        echo '<OPTION VALUE=' . htmlspecialchars( $row{'id'}, ENT_QUOTES);
                        echo (isset($ordered_by_id) && $ordered_by_id != "" ? $ordered_by_id : $provider['provider_id']) == $row{'id'} ? ' selected>' : '>';
//                        if (isset($ordered_by_id) && $ordered_by_id != ""){
//                            echo "$ordered_by_id selected>";
//
//                        }else if($encounter > 0){
//                            $provider = sqlStatement("select * from form_encounter where encounter = $encounter");
//                            echo "{$provider['provider_id']} selected>";
//                        }else{
//                            echo ">";
//                        }
                        echo htmlspecialchars( $row{'full_name'}, ENT_NOQUOTES) . '</OPTION>';
                    }
                    ?>
                </select>
            </td>
        </tr>
        <?php
        if($entered_by){
            ?>
            <tr>
                <td align="right" class='text'>
                    <?php echo htmlspecialchars( xl('Entered By'), ENT_NOQUOTES); ?>
                </td>
                <td>
                    <?php echo htmlspecialchars( $entered_by, ENT_NOQUOTES); ?>
                </td>
            </tr>
            <?php
        }
        if($GLOBALS['observation_results_immunization']) {
            ?>
            <tr>
            <td colspan="3" align="center">
                <img src='../../pic/add.png' onclick="showObservationResultSection();" align='absbottom' width='27' height='24' border='0' style='cursor:pointer;cursor:hand' title='<?php echo xla('Click here to see observation results'); ?>'>
            </td>
            </tr><?php } ?>
        <tr>
            <td align="center" colspan="3">
                <div class="observation_results" style="display:none;">
                    <fieldset class="obs_res_head">
                        <legend><?php echo htmlspecialchars( xl('Observation Results'), ENT_QUOTES); ?></legend>
                        <table class="obs_res_table">
                            <?php if(count($imm_obs_data) > 0) {
                                foreach($imm_obs_data as $key=>$value) {
                                    $key_snomed = 0; $key_cvx = 0; $style= '';?>
                                    <tr id="or_tr_<?php echo $key + 1 ;?>">
                                        <?php
                                        if($id == 0 ) {
                                            if($key == 0)
                                                $style = 'display: table-cell;width:765px !important';
                                            else
                                                $style = 'display: none;width:765px !important';
                                        }
                                        else
                                            $style = 'display : table-cell;width:765px !important';
                                        ?>
                                        <td id="observation_criteria_td_<?php echo $key + 1 ;?>" style="<?php echo $style;?>">
                                            <label><?php echo htmlspecialchars( xl('Observation Criteria'), ENT_QUOTES);?></label>
                                            <select id="observation_criteria_<?php echo $key + 1 ;?>" name="observation_criteria[]" onchange="selectCriteria(this.id,this.value);" style="width: 220px;">
                                                <?php foreach ($observation_criteria as $keyo=> $valo) { ?>
                                                    <option value="<?php echo attr($valo['option_id']);?>" <?php if($valo['option_id'] == $value['imo_criteria'] && $id !=0) echo 'selected = "selected"' ;?> ><?php echo text($valo['title']);?></option>
                                                <?php }
                                                ?>
                                            </select>
                                        </td>
                                        <td <?php if($value['imo_criteria'] != 'funding_program_eligibility' || $id == 0) { ?> style="display: none;" <?php } ?> class="observation_criteria_value_td" id="observation_criteria_value_td_<?php echo $key + 1 ;?>">
                                            <label><?php echo htmlspecialchars( xl('Observation Criteria Value'), ENT_QUOTES); ?></label>
                                            <select name="observation_criteria_value[]" id="observation_criteria_value_<?php echo $key + 1 ;?>" style="width: 220px;">
                                                <?php foreach ($observation_criteria_value as $keyoc=> $valoc) { ?>
                                                    <option value="<?php echo attr($valoc['option_id']);?>" <?php if($valoc['option_id'] == $value['imo_criteria_value']  && $id != 0) echo 'selected = "selected"' ;?>><?php echo text($valoc['title']);?></option>
                                                <?php }
                                                ?>
                                            </select>
                                        </td>
                                        <td <?php if($value['imo_criteria'] != 'disease_with_presumed_immunity' || $id == 0) { ?> style="display: none;" <?php } ?> class="code_serach_td" id="code_search_td_<?php echo $key + 1 ;?>">
                                            <?php $key_snomed = ($key > 0) ? (($key*2) + 2) : ($key + 2);?>
                                            <label><?php echo htmlspecialchars( xl('SNOMED-CT Code'), ENT_QUOTES);?></label>
                                            <input type="text" id="sct_code_<?php echo $key_snomed; ?>" style="width:140px" name="sct_code[]" class="code" value="<?php if($id != 0 && $value['imo_criteria'] == 'disease_with_presumed_immunity') echo attr($value['imo_code']);?>"  onclick='sel_code(this.id);'><br>
                                            <span id="displaytext_<?php echo $key_snomed; ?>" style="width:210px !important;display: block;font-size:13px;color: blue;" class="displaytext"><?php  echo text($value['imo_codetext']);?></span>
                                            <input type="hidden" id="codetext_<?php echo $key_snomed; ?>" name="codetext[]" class="codetext" value="<?php echo attr($value['imo_codetext']); ?>">
                                            <input type="hidden"  value="SNOMED-CT" name="codetypehidden[]" id="codetypehidden<?php echo $key_snomed; ?>" />
                                        </td>
                                        <td <?php if($value['imo_criteria'] != 'vaccine_type' || $id == 0) { ?> style="display: none;" <?php } ?> class="code_serach_vaccine_type_td" id="code_serach_vaccine_type_td_<?php echo $key + 1 ;?>">
                                            <label><?php echo htmlspecialchars( xl('CVX Code'), ENT_QUOTES);?></label>
                                            <?php $key_cvx = ($key > 0) ? (($key*2) + 3) : ($key + 3);?>
                                            <input type="text" id="cvx_code<?php echo $key_cvx ;?>" name="cvx_vac_type_code[]" onclick="sel_cvxcode(this);"
                                                   value="<?php if($id != 0 && $value['imo_criteria'] == 'vaccine_type') echo attr($value['imo_code']);?>" style="width:140px;" />
                                            <div class="imm-imm-add-12" id="imm-imm-add-12<?php echo $key_cvx ;?>"><?php if($id != 0 && $value['imo_criteria'] == 'vaccine_type') echo text($value['imo_codetext']);?></div>
                                            <input type="hidden"  value="CVX" name="code_type_hidden[]" id="code_type_hidden<?php echo $key_cvx ;?>" />
                                            <input type="hidden" class="code_text_hidden" name="code_text_hidden[]" id="code_text_hidden<?php echo $key_cvx ;?>" value="<?php if($id != 0 && $value['imo_criteria'] == 'vaccine_type') echo attr($value['imo_codetext']);?>"/>
                                        </td>
                                        <td <?php if($value['imo_criteria'] != 'vaccine_type' || $id == 0) { ?> style="display: none;" <?php } ?> class="vis_published_date_td" id="vis_published_date_td_<?php echo $key + 1 ;?>">
                                            <label><?php echo htmlspecialchars( xl('Date VIS Published'), ENT_QUOTES); ?></label>
                                            <?php
                                            $vis_published_dateval = $value['imo_vis_date_published'] ? htmlspecialchars( $value['imo_vis_date_published'], ENT_QUOTES) : '';
                                            ?>
                                            <input type="text" name="vis_published_date[]" value="<?php if($id != 0 && $vis_published_dateval != 0) echo attr($vis_published_dateval);?>" id="vis_published_date_<?php echo $key + 1 ;?>" autocomplete="off" onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc);' style="width:140px">
                                            <img src='<?php echo $rootdir; ?>/pic/show_calendar.gif' align='absbottom' width='24' height='22' id='img_immuniz_vis_date_published_<?php echo $key + 1 ;?>' border='0' alt='[?]' style='cursor:pointer;cursor:hand' title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>'>
                                        </td>
                                        <td <?php if($value['imo_criteria'] != 'vaccine_type' || $id == 0) { ?> style="display: none;" <?php } ?> class="vis_presented_date_td" id="vis_presented_date_td_<?php echo $key + 1 ;?>">
                                            <label><?php echo htmlspecialchars( xl('Date VIS Presented'), ENT_QUOTES); ?></label>
                                            <?php
                                            $vis_presented_dateval = $value['imo_vis_date_presented'] ?htmlspecialchars( $value['imo_vis_date_presented'], ENT_QUOTES) : '';
                                            ?>
                                            <input type="text" name="vis_presented_date[]" value="<?php if($id != 0 && $vis_presented_dateval !=0) echo attr($vis_presented_dateval);?>" id="vis_presented_date_<?php echo $key + 1 ;?>" autocomplete="off" onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc);' style="width:140px">
                                            <img src='<?php echo $rootdir; ?>/pic/show_calendar.gif' align='absbottom' width='24' height='22' id='img_immuniz_vis_date_presented_<?php echo $key + 1 ;?>' border='0' alt='[?]' style='cursor:pointer;cursor:hand' title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>'>
                                        </td>
                                        <?php if($key != 0 && $id != 0) {?>
                                            <td>
                                                <img src='../../pic/remove.png' id ="<?php echo $key +1;?>" onclick="RemoveRow(this.id);" align='absbottom' width='24' height='22' border='0' style='cursor:pointer;cursor:hand' title='<?php echo xla('Click here to delete the row'); ?>'>
                                            </td>
                                        <?php } ?>
                                    </tr>
                                <?php }
                            }else{?>
                                <tr id="or_tr_1">
                                    <td id="observation_criteria_td_1">
                                        <label><?php echo htmlspecialchars( xl('Observation Criteria'), ENT_QUOTES); ?></label>
                                        <select id="observation_criteria_1" name="observation_criteria[]" onchange="selectCriteria(this.id,this.value);" style="width: 220px;">
                                            <?php foreach ($observation_criteria as $keyo=> $valo) { ?>
                                                <option value="<?php echo attr($valo['option_id']);?>" <?php if($valo['option_id'] == $value['imo_criteria'] && $id !=0) echo 'selected = "selected"' ;?> ><?php echo text($valo['title']);?></option>
                                            <?php }
                                            ?>
                                        </select>
                                    </td>
                                    <td <?php if($value['imo_criteria'] != 'funding_program_eligibility') { ?> style="display: none;" <?php } ?> class="observation_criteria_value_td" id="observation_criteria_value_td_1">
                                        <label><?php echo htmlspecialchars( xl('Observation Criteria Value'), ENT_QUOTES); ?></label>
                                        <select id="observation_criteria_value_1" name="observation_criteria_value[]" style="width: 220px;">
                                            <?php foreach ($observation_criteria_value as $keyoc=> $valoc) { ?>
                                                <option value="<?php echo attr($valoc['option_id']);?>" <?php if($valoc['option_id'] == $value['imo_criteria_value'] && $id != 0) echo 'selected = "selected"' ;?>><?php echo text($valoc['title']);?></option>
                                            <?php }
                                            ?>
                                        </select>
                                    </td>
                                    <td <?php if($value['imo_criteria'] != 'disease_with_presumed_immunity' || $id == 0) { ?> style="display: none;" <?php } ?> class="code_serach_td" id="code_search_td_1">
                                        <label><?php echo htmlspecialchars( xl('SNOMED-CT Code'), ENT_QUOTES);?></label>
                                        <input type="text" id="sct_code_2" style="width:140px" name="sct_code[]" class="code" value="<?php if($id != 0 && $value['imo_criteria'] == 'disease_with_presumed_immunity') echo attr($value['imo_code']);?>"  onclick='sel_code(this.id);'><br>
                                        <span id="displaytext_2" style="width:210px !important;display: block;font-size:13px;color: blue;" class="displaytext"><?php  echo text($value['imo_codetext']);?></span>
                                        <input type="hidden" id="codetext_2" name="codetext[]" class="codetext" value="<?php echo attr($value['imo_codetext']); ?>">
                                        <input type="hidden"  value="SNOMED-CT" name="codetypehidden[]" id="codetypehidden2" />
                                    </td>
                                    <td <?php if($value['imo_criteria'] != 'vaccine_type' || $id == 0) { ?> style="display: none;" <?php } ?> class="code_serach_vaccine_type_td" id="code_serach_vaccine_type_td_1">
                                        <label><?php echo htmlspecialchars( xl('CVX Code'), ENT_QUOTES);?></label>
                                        <input type="text" id="cvx_code3" name="cvx_vac_type_code[]" onclick="sel_cvxcode(this);"
                                               value="<?php if($id != 0 && $value['imo_criteria'] == 'vaccine_type') echo attr($value['imo_code']);?>" style="width:140px;" />
                                        <div class="imm-imm-add-12" id="imm-imm-add-123"><?php if($id != 0 && $value['imo_criteria'] == 'vaccine_type') echo text($value['imo_codetext']);?></div>
                                        <input type="hidden"  value="CVX" name="code_type_hidden[]" id="code_type_hidden3"/>
                                        <input type="hidden" class="code_text_hidden" name="code_text_hidden[]" id="code_text_hidden3" value="<?php if($id != 0 && $value['imo_criteria'] == 'vaccine_type') echo attr($value['imo_codetext']);?>"/>
                                    </td>
                                    <td <?php if($value['imo_criteria'] != 'vaccine_type' || $id == 0) { ?> style="display: none;" <?php } ?> class="vis_published_date_td" id="vis_published_date_td_1">
                                        <label><?php echo htmlspecialchars( xl('Date VIS Published'), ENT_QUOTES); ?></label>
                                        <?php
                                        $vis_published_dateval = $value['imo_vis_date_published'] ? htmlspecialchars( $value['imo_vis_date_published'], ENT_QUOTES) : '';
                                        ?>
                                        <input type="text" name="vis_published_date[]" value="<?php if($id != 0 && $vis_published_dateval != 0) echo attr($vis_published_dateval);?>" id="vis_published_date_1"  autocomplete="off" onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc);' style="width:140px">
                                        <img src='<?php echo $rootdir; ?>/pic/show_calendar.gif' align='absbottom' width='24' height='22' id='img_immuniz_vis_date_published_1' border='0' alt='[?]' style='cursor:pointer;cursor:hand' title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>'>
                                    </td>
                                    <td <?php if($value['imo_criteria'] != 'vaccine_type' || $id == 0) { ?> style="display: none;" <?php } ?> class="vis_presented_date_td" id="vis_presented_date_td_1">
                                        <label><?php echo htmlspecialchars( xl('Date VIS Presented'), ENT_QUOTES); ?></label>
                                        <?php
                                        $vis_presented_dateval = $value['imo_vis_date_presented'] ?htmlspecialchars( $value['imo_vis_date_presented'], ENT_QUOTES) : '';
                                        ?>
                                        <input type="text" name="vis_presented_date[]" value="<?php if($id != 0 && $vis_presented_dateval !=0) echo attr($vis_presented_dateval);?>" id="vis_presented_date_1" autocomplete="off" onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc);' style="width:140px">
                                        <img src='<?php echo $rootdir; ?>/pic/show_calendar.gif' align='absbottom' width='24' height='22' id='img_immuniz_vis_date_presented_1' border='0' alt='[?]' style='cursor:pointer;cursor:hand' title='<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>'>
                                    </td>
                                </tr>
                            <?php }?>
                        </table>
                        <div>
                            <center style="cursor: pointer;">
                                <img src='../../pic/add.png' onclick="addNewRow();" align='absbottom' width='27' height='24' border='0' style='cursor:pointer;cursor:hand' title='<?php echo xla('Click here to add new row'); ?>'>
                            </center>
                        </div>
                        <input type ="hidden" name="tr_count" id="tr_count" value="<?php echo (count($imm_obs_data)>0) ? count($imm_obs_data) : 1 ;?>">
                        <input type="hidden" id="clickId" value="">
                    </fieldset>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="3" align="center">
                <?php //***SANTI Modify ?>
                <input type="button" name="save" id="save" value="<?php echo htmlspecialchars( xl('Save'), ENT_QUOTES); ?>">

                <?php //***SANTI ADD ?>
                <input type="button" name="save_and_send" id="save_and_send" value="<?php echo htmlspecialchars( xl('Save and Send'), ENT_QUOTES); ?>">

                <input type="button" name="print" id="print" value="<?php echo htmlspecialchars( xl('Print Record') . xl('PDF','',' (',')'), ENT_QUOTES); ?>">

                <input type="button" name="printHtml" id="printHtml" value="<?php echo htmlspecialchars( xl('Print Record') . xl('HTML','',' (',')'), ENT_QUOTES); ?>">

                <input type="reset" name="clear" id="clear" value="<?php echo htmlspecialchars( xl('Clear'), ENT_QUOTES); ?>">          </td>
        </tr>
    </table>
</form>
</div>
<div id="immunization_list">

    <table border=0 cellpadding=3 cellspacing=0 width="95%">

        <!-- some columns are sortable -->
        <tr class='text bold'>
            <th>
                <a href="javascript:top.restoreSession();location.href='immunizations.php?sortby=vacc';" title='<?php echo htmlspecialchars( xl('Sort by vaccine'), ENT_QUOTES); ?>'>
                    <?php echo htmlspecialchars( xl('Vaccine'), ENT_NOQUOTES); ?></a>
                <span class='small' style='font-family:arial'><?php if ($sortby == 'vacc') { echo 'v'; } ?></span>
            </th>
            <th>
                <a href="javascript:top.restoreSession();location.href='immunizations.php?sortby=date';" title='<?php echo htmlspecialchars( xl('Sort by date'), ENT_QUOTES); ?>'>
                    <?php echo htmlspecialchars( xl('Date'), ENT_NOQUOTES); ?></a>
                <span class='small' style='font-family:arial'><?php if ($sortby == 'date') { echo 'v'; } ?></span>
            </th>
            <th><?php echo htmlspecialchars( xl('Amount'), ENT_NOQUOTES); ?></th>
            <th><?php echo xlt('Expiration'); ?></th>
            <th><?php echo htmlspecialchars( xl('Manufacturer'), ENT_NOQUOTES); ?></th>
            <th><?php echo htmlspecialchars( xl('Lot Number'), ENT_NOQUOTES); ?></th>
            <th><?php echo htmlspecialchars( xl('Administered By'), ENT_NOQUOTES); ?></th>
            <th><?php echo htmlspecialchars( xl('Education Date'), ENT_NOQUOTES); ?></th>
            <th><?php echo htmlspecialchars( xl('Route'), ENT_NOQUOTES); ?></th>
            <th><?php echo htmlspecialchars( xl('Administered Site'), ENT_NOQUOTES); ?></th>
            <th><?php echo htmlspecialchars( xl('Notes'), ENT_NOQUOTES); ?></th>
            <th><?php echo htmlspecialchars( xl('VFC Eligibility'), ENT_NOQUOTES); ?></th><?php //***SANTI ADD ?>
            <th><?php echo htmlspecialchars( xl('Completion Status'), ENT_NOQUOTES); ?></th>
            <th><?php echo htmlspecialchars( xl('Error'), ENT_NOQUOTES); ?></th>
            <th>&nbsp;</th>
        </tr>

        <?php
        $result = getImmunizationList($pid, $_GET['sortby'], true);

        while($row = sqlFetchArray($result)) {
            $isError = $row['added_erroneously'];

            if ($isError) {
                $tr_title = 'title="' . xla("Entered in Error") . '"';
            } else {
                $tr_title = "";
            }

            if ($row["id"] == $id) {
                echo "<tr " . $tr_title . " class='immrow text selected' id='".htmlspecialchars( $row["id"], ENT_QUOTES)."'>";
            }
            else {
                echo "<tr " . $tr_title . " class='immrow text' id='".htmlspecialchars( $row["id"], ENT_QUOTES)."'>";
            }

            // Figure out which name to use (ie. from cvx list or from the custom list)
            if ($GLOBALS['use_custom_immun_list']) {
                $vaccine_display = generate_display_field(array('data_type'=>'1','list_id'=>'immunizations'), $row['immunization_id']);
            }
            else {
                if (!empty($row['code_text_short'])) {
                    $vaccine_display = htmlspecialchars( xl($row['code_text_short']), ENT_NOQUOTES);
                }
                else {
                    $vaccine_display = generate_display_field(array('data_type'=>'1','list_id'=>'immunizations'), $row['immunization_id']);
                }
            }

            if ($isError) {
                $del_tag_open = "<del>";
                $del_tag_close = "</del>";
            } else {
                $del_tag_open = "";
                $del_tag_close = "";
            }

            echo "<td>" . $del_tag_open . $vaccine_display . $del_tag_close . "</td>";

            if ($row["administered_date"]) {
                $administered_date_summary = new DateTime($row['administered_date']);
                $administered_date_summary = $administered_date_summary->format('Y-m-d H:i');
            } else {
                $administered_date_summary = "";
            }
            echo "<td>" . $del_tag_open . htmlspecialchars( $administered_date_summary, ENT_NOQUOTES) . $del_tag_close . "</td>";
            if ($row["amount_administered"] > 0) {
                echo "<td>" . $del_tag_open . htmlspecialchars( $row["amount_administered"] . " " . generate_display_field(array('data_type'=>'1','list_id'=>'drug_units'), $row['amount_administered_unit']) , ENT_NOQUOTES) . $del_tag_close . "</td>";
            }
            else {
                echo "<td>&nbsp</td>";
            }
            echo "<td>" . $del_tag_open . text($row["expiration_date"]) . $del_tag_close . "</td>";
            echo "<td>" . $del_tag_open . htmlspecialchars( $row["manufacturer"], ENT_NOQUOTES) . $del_tag_close . "</td>";
            echo "<td>" . $del_tag_open . htmlspecialchars( $row["lot_number"], ENT_NOQUOTES) . $del_tag_close . "</td>";
            echo "<td>" . $del_tag_open . htmlspecialchars( $row["administered_by"], ENT_NOQUOTES) . $del_tag_close . "</td>";
            echo "<td>" . $del_tag_open . htmlspecialchars( $row["education_date"], ENT_NOQUOTES) . $del_tag_close . "</td>";
            //***SANTI Modify
            echo "<td>" . $del_tag_open . generate_display_field(array('data_type'=>'1','list_id'=>'drug_routes'), $row['route']) . $del_tag_close . "</td>";
            //***SANTI Modify
            echo "<td>" . $del_tag_open . generate_display_field(array('data_type'=>'1','list_id'=>'Imm_Administrative_Site__CAIR'), $row['administration_site']) . $del_tag_close . "</td>";

            echo "<td>" . $del_tag_open . htmlspecialchars( $row["note"], ENT_NOQUOTES) . $del_tag_close . "</td>";
            echo "<td>" . $del_tag_open . htmlspecialchars( $row["vfc"], ENT_NOQUOTES) . $del_tag_close . "</td>";  //***SANTI ADD
            echo "<td>" . $del_tag_open . generate_display_field(array('data_type'=>'1','list_id'=>'Immunization_Completion_Status'), $row['completion_status']) . $del_tag_close . "</td>";




            if ($isError) {
                $checkbox = "checked";
            } else {
                $checkbox = "";
            }

            echo "<td><input type='checkbox' class='error' id='".htmlspecialchars( $row["id"], ENT_QUOTES)."' value='" . htmlspecialchars( xl('Error'), ENT_QUOTES) . "' " . $checkbox . "></td>";

            echo "<td><input type='button' class='delete' id='".htmlspecialchars( $row["id"], ENT_QUOTES)."' value='" . htmlspecialchars( xl('Delete'), ENT_QUOTES) . "'></td>";
            echo "</tr>";
        }

        ?>

    </table>
</div> <!-- end immunizations -->

</body>

<script language="javascript">
    var tr_count = $('#tr_count').val();
    /* required for popup calendar */
    <?php //***SANTI ADD ?>
    Calendar.setup({inputField:"administered_date", ifFormat:"%Y-%m-%d %H:%M", button:"img_administered_date", showsTime:true});
    Calendar.setup({inputField:"immuniz_exp_date", ifFormat:"%Y-%m-%d", button:"img_immuniz_exp_date"});
    Calendar.setup({inputField:"education_date", ifFormat:"%Y-%m-%d", button:"img_education_date"});
    Calendar.setup({inputField:"vis_date", ifFormat:"%Y-%m-%d", button:"img_vis_date"});
    Calendar.setup({inputField:"vis_published_date_"+tr_count, ifFormat:"%Y-%m-%d", button:"img_immuniz_vis_date_published_"+tr_count});
    Calendar.setup({inputField:"vis_presented_date_"+tr_count, ifFormat:"%Y-%m-%d", button:"img_immuniz_vis_date_presented_"+tr_count});
    <?php //***SANTI ADD END ?>
    // jQuery stuff to make the page a little easier to use

    $(document).ready(function(){
        <?php //***SANTI ADD ?>
        $("#historical").click(function() {
            if($("#historical").prop('checked') == true) {
                $("#historical").val("01");
                $("#immuniz_exp_date").val("");
                $("#administered_by").val("");
                $("#education_date").val("");
                $("#vis_date").val("");
                $("#ordered_by_id").val("");
                $("#manufacturer").val("");
                $("#vfc").val("");
                $("#administered_by_id").val("");
            } else {
                $("#historical").val("00");
            }

        });

        $('textarea').css('border', '1px solid black');
        $('#inputId').prop('readonly', true);
        <?php
        if (!($useCVX)) {
            ?>
        $("#save").click(function() {

            SaveForm(); });
        <?php } else {
            ?>
        <?php //***SANTI ADD ?>
        $("#save").click(function() {
            if (validate_cvx()) {

                SaveForm();

            }

            else {

                return;
            }
        });
        <?php //***SANTI ADD ?>
        $("#save_and_send").click(function() {
            if (validate_cvx()) {

                SaveForm();
                var pid = <?php echo $pid; ?>;

                var encounter = <?php echo $encounter; ?>;
                //todo: check if patient has authorized the submitting to CAIR, if not alert the provider
                if(0) {

                    alert("Don't Send me immunizations for " + pid);

                }else{
                    //we only want to do this if historica is selected or there is an encounter associated with this
                    //entry

                    if(encounter > 0 || $('#historical').is(":checked") ) {
                        var paturl = 'immunizations/temp/CAIR_interface_top_view.php?pid=' + pid + '&action=submit';

                        parent.left_nav.loadFrame('dem3', 'imm3', paturl);
                    }

                }
            }else {

                return;
            }
        });
        <?php //***SANTI ADD END ?>

        <?php } ?>
        $("#print").click(function() { PrintForm("pdf"); });
        $("#printHtml").click(function() { PrintForm("html"); });
        $(".immrow").click(function() { EditImm(this); });
        $(".error").click(function(event) { ErrorImm(this); event.stopPropagation(); });
        $(".delete").click(function(event) { DeleteImm(this); event.stopPropagation(); });

        $(".immrow").mouseover(function() { $(this).toggleClass("highlight"); });
        $(".immrow").mouseout(function() { $(this).toggleClass("highlight"); });

        $("#administered_by_id").change(function() { $("#administered_by").val($("#administered_by_id :selected").text()); });

        $("#form_immunization_id").change( function() {
            if ( $(this).val() != "" ) {
                $("#cvx_code").val( "" );
                $("#cvx_description").text( "" );

            }
        });




        <?php //***SANTI ADD ?>
        //Scan the vial and
        //pause the processor until the field is complete.  This prevents multiple
        //ajax requests from the same query
        var typingTimer;
        var waitTime = 400;
        var input = $('#scanner_code');
        input.focus();
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
                input.css('background-color', 'rgba(198,220,206,255)');

                //once ndc is complete, fill it in form
                if (gs11.length == 2 && gs12.length == 2) {
                    //Now we get the NDC11 using the specs as provided by https://www.cdc.gov/vaccines/programs/iis/2d-vaccine-barcodes/downloads/barcode-functional-capabilities.pdf
                    var vaccineNDC542 = ndc.substr(0, 5) + '-' + ndc.substr(5, 4) + '-' + ndc.substr(9, 1);
                    var NDC11_542 = ndc.substr(0,9) + '0' + ndc.substr(9,1);
                    console.log("NDC 5-4-1 = " + vaccineNDC542);
                    console.log("NDC11 5-4-1 = " + NDC11_542);

                    var vaccineNDC532 = ndc.substr(0, 5) + '-' + ndc.substr(5, 3) + '-' + ndc.substr(8, 2);
                    var NDC11_532 = ndc.substr(0,5) + '0' + ndc.substr(5,5);
                    console.log("NDC 5-3-2 = " + vaccineNDC532);
                    console.log("NDC11 5-3-2 = " + NDC11_532);

                    var vaccineNDC442 = ndc.substr(0, 4) + '-' + ndc.substr(4, 4) + '-' + ndc.substr(8, 2);
                    var NDC11_442 = "0" + ndc;
                    console.log("NDC 4-4-2 = " + vaccineNDC442);
                    console.log("NDC11 4-4-2 = " + NDC11_442);

                    console.log("GTIN: " + gtin);


                    //We check the immunizations_schedules_codes table to
                    //see if the GTIN has been recorded.
                    $.ajax({
                        type: "POST",
                        url: "../../../library/ajax/immunizations_ajax.php",
                        data: {
                            func:"check_GTIN",
                            GTIN:gtin
                        },
                        success:function(data)
                        {
                            //This is where we verify that the GTIN exists in the DB.
                            //If it returns false we prompt the user to make a selection.
                            if(data == 'false' || data == false){

                                console.log("User prompted to make a selection");
                                alert("GTIN does not exist in the database.  Please add add GTIN by scanning vial " +
                                    "in Admin-> Codes under the appropiate CVX code.");

                                //Update database
                                //we do not clear the box
                                input.css('background-color', 'white');

                            }else {

                                console.log("Matching GTIN.  DATA: " + data);
                                var cvx_response = JSON.parse(data);
                                $('#cvx_code').val(cvx_response['cvx_code']);
                                $('#ndc').val(ndc);
                                var desc = get_code_description("CVX:" + cvx_response['cvx_code']);
                                $('#cvx_description').val(desc);
                                $('#manufacturer').val(cvx_response['manufacturer']);
                                if(checkExpData(expDate, lotNum, scanned)){

                                 choose_imm_entry(cvx_response);


                                }else{

                                    console.log('Bad stuff, expired');

                                }


                            }

                        }

                    });


                }




            }else{
                //GTIN didn't work
                if(input.val () != "") {

                    alert("GTIN Scanning Error, Please try again");
                }
                input.css('background-color', 'white').focus();
                clearImm();


            }



        }




    });

    function checkExpData(expDate, lotNum, scanned){

        //once exp date is complete, fill it in form
        if (expDate.length == 6) {
            console.log("EXP " + expDate);
            var vaccineExpires = "20" + expDate.substr(0, 2) + '-' + expDate.substr(2, 2) + '-' + expDate.substr(4, 2);
            $('#immuniz_exp_date').val(vaccineExpires);
            var selectedDate = new Date(vaccineExpires);
            var now = new Date();
            now.setHours(0, 0, 0, 0);

            if (Date.parse(vaccineExpires)) {
                if (selectedDate < now) {

                    $('#immuniz_exp_date').css({'background-color': 'red'});
                    alert('Expired Vaccine');
                    $('#scanner_code').css('background-color', 'white').focus().val('');
                    return false;

                } else {
                    $('#immuniz_exp_date').css({'background-color': 'white'});

                    if (lotNum.length == (scanned.length - 26)) {
                        console.log("lotNum " + lotNum);
                        $('#lot_number').val(lotNum);
                        console.log("NDC " + ndc);
                        console.log("Scanned " + scanned);

                    }

                    $('#immuniz_amt_adminstrd').focus();
                    return true;

                }
            }
        }



    }
    <?php //***SANTI ADD END ?>

    var PrintForm = function(typ) {
        top.restoreSession();
        newURL='shot_record.php?output='+typ+'&sortby=<?php echo $sortby; ?>';
        window.open(newURL, '_blank', "menubar=1,toolbar=1,scrollbars=1,resizable=1,width=600,height=450");
    }

    var SaveForm = function() {
        top.restoreSession();
        $("#add_immunization").submit();
    }

    var EditImm = function(imm) {
        top.restoreSession();
        location.href='immunizations.php?mode=edit&id='+imm.id;
    }

    var DeleteImm = function(imm) {
        if (confirm("<?php echo htmlspecialchars( xl('This action cannot be undone.'), ENT_QUOTES); ?>" + "\n" +"<?php echo htmlspecialchars( xl('Do you wish to PERMANENTLY delete this immunization record?'), ENT_QUOTES); ?>")) {
            top.restoreSession();
            location.href='immunizations.php?mode=delete&id='+imm.id;
        }
    }

    var ErrorImm = function(imm) {
        top.restoreSession();
        location.href='immunizations.php?mode=added_error&id='+imm.id+'&isError='+imm.checked;
    }

    //This is for callback by the find-code popup.
    //Appends to or erases the current list of diagnoses.
    function set_related(codetype, code, selector, codedesc) {
        if(codetype == 'CVX') {
            var f = document.forms[0][current_sel_name];
            if(!f.length) {
                var s = f.value;

                if (code) {
                    s = code;
                }
                else {
                    s = '';
                }

                f.value = s;
                if(f.name != 'cvx_vac_type_code[]'){
                    $("#cvx_description").text( codedesc );
                    $("#form_immunization_id").attr( "value", "" );
                    $("#form_immunization_id").change();
                }else{
                    id_arr = f.id.split('cvx_code');
                    counter = id_arr[1];
                    $('#imm-imm-add-12'+counter).html(codedesc);
                    $('#code_text_hidden'+counter).val(codedesc);
                }
            }else {
                var index = document.forms[0][current_sel_name].length -1;
                var elem = document.forms[0][current_sel_name][index];
                var ss = elem.value;
                if (code) {
                    ss = code;
                }
                else {
                    ss = '';
                }

                elem.value = ss;
                arr = elem.id.split('cvx_code');
                count = arr[1];
                $('#imm-imm-add-12'+count).html(codedesc);
                $('#code_text_hidden'+count).val(codedesc);
            }
        }else {
            var checkId = $('#clickId').val();
            $("#sct_code_" + checkId).val(code);
            $("#codetext_" + checkId).val(codedesc);
            $("#displaytext_" + checkId).html(codedesc);
        }
    }

    // This invokes the find-code popup.
    function sel_cvxcode(e) {
        current_sel_name = e.name;
        dlgopen('../encounter/find_code_popup.php?codetype=CVX', '_blank', 500, 400);
    }

    // This ensures the cvx centric entry is filled.
    // to improve workflow, we remove the read only status of the 2d Scanner code
    function validate_cvx() {
        if (document.add_immunization.cvx_code.value>0) {

            return true;
        }
        else {
            document.add_immunization.cvx_code.style.backgroundColor="red";
            document.add_immunization.cvx_code.focus();
            return false;
        }
    }

    function showObservationResultSection()
    {
        $('.observation_results').slideToggle();
    }

    function selectCriteria(id,value)
    {
        var arr = id.split('observation_criteria_');
        var key = arr[1];
        if(value == 'funding_program_eligibility') {
            $('.obs_res_table').css('width','50%');
            if(key > 1) {
                var target = $("#observation_criteria_value_"+key);
                $.ajax({
                    type: "POST",
                    url:  "immunizations.php",
                    dataType: "json",
                    data: {
                        type : 'duplicate_row_2'
                    },
                    success: function(thedata){
                        $.each(thedata,function(i,item) {
                            target.append($('<option />').val(item.option_id).text(item.title));
                        });
                        $('#observation_criteria_value_'+key+' option[value=""]').attr('selected','selected');
                    },
                    error:function(){
                        alert("ajax error");
                    }
                });
            }
            $("#code_search_td_"+key).hide();
            $("#vis_published_date_td_"+key).hide();
            $("#vis_presented_date_td_"+key).hide();
            $("#code_serach_vaccine_type_td_"+key).hide();
            $("#observation_criteria_value_td_"+key).show();
        }
        if(value == 'vaccine_type')
        {
            $("#observation_criteria_value_td_"+key).hide();
            $("#code_search_td_"+key).hide();
            $("#code_serach_vaccine_type_td_"+key).show();
            $("#vis_published_date_td_"+key).show();
            $("#vis_presented_date_td_"+key).show();
            if(key == 1) {
                key = parseInt(key) + 2;
            }
            else {
                key = (parseInt(key) * 2) + 1;
            }
            $("#cvx_code"+key).css("background-color", "red");
            $("#cvx_code"+key).focus();
            return false;
        }
        if(value == 'disease_with_presumed_immunity')
        {
            $('.obs_res_table').css('width','50%');
            $("#observation_criteria_value_td_"+key).hide();
            $("#vis_published_date_td_"+key).hide();
            $("#vis_presented_date_td_"+key).hide();
            $("#code_serach_vaccine_type_td_"+key).hide();
            $("#code_search_td_"+key).show();
            if(key == 1) {
                key = parseInt(key) + 1;
            }
            else {
                key = (parseInt(key) * 2);
            }
            $("#sct_code_"+key).css("background-color", "red");
            $("#sct_code_"+key).focus();
            return false;
        }
        if(value == '')
        {
            $("#observation_criteria_value_td_"+key).hide();
            $("#vis_published_date_td_"+key).hide();
            $("#vis_presented_date_td_"+key).hide();
            $("#code_serach_vaccine_type_td_"+key).hide();
            $("#code_search_td_"+key).hide();
        }
    }

    function RemoveRow(id)
    {
        tr_count = parseInt($("#tr_count").val());
        new_tr_count = tr_count-1;
        $("#tr_count").val(new_tr_count);
        $("#or_tr_"+id).remove();
    }

    function addNewRow()
    {
        tr_count = parseInt($("#tr_count").val());
        new_tr_count = tr_count+1;
        new_tr_count_2 = (new_tr_count * 2);
        new_tr_count_3 = (new_tr_count *2) + 1;
        $("#tr_count").val(new_tr_count);
        label1 = "<?php echo htmlspecialchars( xl('Observation Criteria'), ENT_QUOTES); ?>";
        label2 = "<?php echo htmlspecialchars( xl('Observation Criteria Value'), ENT_QUOTES); ?>";
        label3 = "<?php echo htmlspecialchars( xl('SNOMED-CT Code'), ENT_QUOTES); ?>";
        label4 = "<?php echo htmlspecialchars( xl('CVX Code'), ENT_QUOTES); ?>";
        label5 = "<?php echo htmlspecialchars( xl('Date VIS Published'), ENT_QUOTES); ?>";
        label6 = "<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>";
        label7 = "<?php echo htmlspecialchars( xl('Date VIS Presented'), ENT_QUOTES); ?>";
        label8 = "<?php echo htmlspecialchars( xl('Click here to choose a date'), ENT_QUOTES); ?>";
        label9 = "<?php echo htmlspecialchars( xl('Click here to delete the row'), ENT_QUOTES); ?>";
        str = '<tr id ="or_tr_'+new_tr_count+'">'+
            '<td id ="observation_criteria_td_'+new_tr_count+'"><label>'+label1+'</label><select id="observation_criteria_'+new_tr_count+'" name="observation_criteria[]" onchange="selectCriteria(this.id,this.value);" style="width: 220px;"></select>'+
            '</td>'+
            '<td id="observation_criteria_value_td_'+new_tr_count+'" class="observation_criteria_value_td" style="display: none;"><label>'+label2+'</label><select name="observation_criteria_value[]" id="observation_criteria_value_'+new_tr_count+'" style="width: 220px;"></select>'+
            '</td>'+
            '<td class="code_serach_td" id="code_search_td_'+new_tr_count+'" style="display: none;"><label>'+label3+'</label>'+
            '<input type="text" id="sct_code_'+new_tr_count_2+'" style="width:140px" name="sct_code[]" class="code" onclick=sel_code(this.id) /><br>'+
            '<span id="displaytext_'+new_tr_count_2+'" style="width:210px !important;display: block;font-size:13px;color: blue;" class="displaytext"></span>'+
            '<input type="hidden" id="codetext_'+new_tr_count_2+'" name="codetext[]" class="codetext">'+
            '<input type="hidden"  value="SNOMED-CT" name="codetypehidden[]" id="codetypehidden'+new_tr_count_2+'" /> '+
            '</td>'+
            '<td class="code_serach_vaccine_type_td" id="code_serach_vaccine_type_td_'+new_tr_count+'" style="display: none;"><label>'+label4+'</label>'+
            '<input type="text" id="cvx_code'+new_tr_count_3+'" name="cvx_vac_type_code[]" onclick=sel_cvxcode(this); style="width:140px;" />'+
            '<div class="imm-imm-add-12" id="imm-imm-add-12'+new_tr_count_3+'"></div> '+
            '<input type="hidden"  value="CVX" name="code_type_hidden[]" id="code_type_hidden'+new_tr_count_3+'" /> '+
            '<input type="hidden" class="code_text_hidden" name="code_text_hidden[]" id="code_text_hidden'+new_tr_count_3+'" value="" />'+
            '</td>'+
            '<td id="vis_published_date_td_'+new_tr_count+'" class="vis_published_date_td" style="display: none;"><label>'+label5+'</label><input type="text" name= "vis_published_date[]" id ="vis_published_date_'+new_tr_count+'" autocomplete=off onkeyup="datekeyup(this,mypcc)" onblur="dateblur(this,mypcc);" style="width:140px">'+
            '<img src="../../pic/show_calendar.gif" align="absbottom" width="24" height="22" id="img_immuniz_vis_date_published_'+new_tr_count+'" border="0" alt="[?]" style="cursor:pointer;cursor:hand" title="'+label6+'"></td>'+
            '<td id="vis_presented_date_td_'+new_tr_count+'" class="vis_presented_date_td" style="display: none;"><label>'+label7+'</label><input type="text" name= "vis_presented_date[]" id ="vis_presented_date_'+new_tr_count+'" autocomplete=off onkeyup="datekeyup(this,mypcc)" onblur="dateblur(this,mypcc);" style="width:140px">'+
            '<img src="../../pic/show_calendar.gif" align="absbottom" width="24" height="22" id="img_immuniz_vis_date_presented_'+new_tr_count+'" border="0" alt="[?]" style="cursor:pointer;cursor:hand" title="'+label8+'"></td>'+
            '<td><img src="../../pic/remove.png" id ="'+new_tr_count+'" onclick="RemoveRow(this.id);" align="absbottom" width="24" height="22" border="0" style="cursor:pointer;cursor:hand" title="'+label9+'"></td></tr>';

        $(".obs_res_table").append(str);

        var ajax_url = 'immunizations.php';
        var target = $("#observation_criteria_"+new_tr_count);
        $.ajax({
            type: "POST",
            url: ajax_url,
            dataType: "json",
            data: {
                type : 'duplicate_row'
            },
            success: function(thedata){
                $.each(thedata,function(i,item) {
                    target.append($('<option></option>').val(item.option_id).text(item.title));
                });
                $('#observation_criteria_'+new_tr_count+' option[value=""]').attr('selected','selected');
            },
            error:function(){
                alert("ajax error");
            }
        });

        Calendar.setup({inputField:"vis_published_date_"+new_tr_count, ifFormat:"%Y-%m-%d", button:"img_immuniz_vis_date_published_"+new_tr_count});
        Calendar.setup({inputField:"vis_presented_date_"+new_tr_count, ifFormat:"%Y-%m-%d", button:"img_immuniz_vis_date_presented_"+new_tr_count});
    }

    function sel_code(id)
    {
        id = id.split('sct_code_');
        id = id.split('sct_code_');
        var checkId = id[1];
        $('#clickId').val(checkId);
        dlgopen('<?php echo $GLOBALS['webroot'] . "/interface/patient_file/encounter/" ?>find_code_popup.php', '_blank', 700, 400);
    }
    <?php //***SANTI ADD ?>
    function clearImm(){

        $('#cvx_code').val("");
        $('#immuniz_amt_adminstrd').val("");
        $('#lot_number').val("");
        $('#immuniz_exp_date').val("");

        $('#scanner_code').focus().val("");
    }
    <?php //***SANTI ADD ?>
    function check_GTIN_digit(gtin){
        if(gtin.length == 14){
            var chkDigt = gtin.slice(-1);
            var chkSum = 0;

            console.log("GTIN " + gtin);
            console.log("Check Digit " + chkDigt);

            for (var i = 0; i < gtin.length - 1; i++) {
                if( i % 2 == 0){
                    chkSum = chkSum + 3*parseInt(gtin[i]);
                }else{

                    chkSum = chkSum + parseInt(gtin[i]);
                }
            }

            console.log(chkSum);
            var chkDigitCalc = Math.ceil(chkSum / 10) * 10 - chkSum;
            console.log(chkDigitCalc);
            if (chkDigt == chkDigitCalc){
                return true;

            }else{

                return false;
            }

        }else{

            console.log("Length of the GTIN " + gtin.length);

            return false;
        }



    }

    //This updates the cvx code with the new GTIN
    <?php //***SANTI ADD ?>
    function createGTIN(cvx, GTIN){

        $.ajax({
            type: "POST",
            url: "../../../library/ajax/immunizations_ajax.php",
            data: {
                func:"create_GTIN",
                GTIN:GTIN,
                cvx:cvx
            },
            success:function(data)
            {
                //This is where we verify that the GTIN exists in the DB.
                //If it returns false we prompt the user to make a selection.
                if(data == false){

                    console.log("Error updating cvx");

                }else {

                    console.log("CVX updated successfully");


                }

            }

        });



    }
    <?php //***SANTI ADD ?>
    function get_code_description(codetype) {
        var response;
        $.ajax({
            type: "POST",
            url: "../../../library/ajax/immunizations_ajax.php",
            async: false,
            data: {
                func: "lookup_code_descriptions",
                codes: codetype
            },
            success: function (data) {
                response = data;

            }
        });
        return response;
    }
    <?php //***SANTI ADD ?>
    $('.datepicker').datetimepicker({

        <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
        <?php // can add any additional javascript settings to datetimepicker here; need to prepend first setting with a comma ?>
    });

    <?php //***SANTI ADD ?>
    $("#cair_qpd").on('click', function (){
        var pid = <?php echo $pid; ?>;
        //check if patient has authorized the submitting to CAIR, if not alert the provider
        if(0) {
            alert("Don't Send me immunizations for " + pid);
        }else{

            var paturl = 'immunizations/temp/CAIR_interface_top_view.php?pid=' + pid + '&action=query';
            parent.left_nav.loadFrame('dem3', 'imm2', paturl);
        }
    });

</script>
<?php //***SANTI ADD ?>
<?php include_once("immunizations_schedules/initialize_immunizations_form.php"); ?>
</html>
