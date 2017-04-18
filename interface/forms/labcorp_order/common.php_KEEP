<?php
/** **************************************************************************
 *	LABCORP_ORDER/COMMON.PHP
 *
 *	Copyright (c)2013 - Williams Medical Technology, Inc.
 *
 *	This program is licensed software: licensee is granted a limited nonexclusive
 *  license to install this Software on more than one computer system, as long as all
 *  systems are used to support a single licensee. Licensor is and remains the owner
 *  of all titles, rights, and interests in program.
 *  
 *  Licensee will not make copies of this Software or allow copies of this Software 
 *  to be made by others, unless authorized by the licensor. Licensee may make copies 
 *  of the Software for backup purposes only.
 *
 *	This program is distributed in the hope that it will be useful, but WITHOUT 
 *	ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS 
 *  FOR A PARTICULAR PURPOSE. LICENSOR IS NOT LIABLE TO LICENSEE FOR ANY DAMAGES, 
 *  INCLUDING COMPENSATORY, SPECIAL, INCIDENTAL, EXEMPLARY, PUNITIVE, OR CONSEQUENTIAL 
 *  DAMAGES, CONNECTED WITH OR RESULTING FROM THIS LICENSE AGREEMENT OR LICENSEE'S 
 *  USE OF THIS SOFTWARE.
 *
 *  @package labcorp
 *  @subpackage order
 *  @version 1.0
 *  @copyright Williams Medical Technologies, Inc.
 *  @author Ron Criswell <info@keyfocusmedia.com>
 * 
 *************************************************************************** */
require_once("../../globals.php");
require_once("{$GLOBALS['srcdir']}/options.inc.php");
require_once("{$GLOBALS['srcdir']}/lists.inc");
require_once("{$GLOBALS['srcdir']}/wmt/wmt.class.php");
require_once("{$GLOBALS['srcdir']}/wmt/wmt.include.php");

// grab inportant stuff
$id = '';
if ($viewmode) $id = $_GET['id'];
$pid = ($pid)? $pid : $_SESSION['pid'];
$encounter = ($encounter)? $encounter : $_SESSION['encounter'];

$form_name = 'labcorp_order';
$form_title = 'Laboratory Order';
$form_table = 'form_labcorp_order';
$item_name = 'labcorp_order_item';
$item_table = 'form_labcorp_order_item';
$aoe_name = 'labcorp_order_aoe';
$aoe_table = 'form_labcorp_order_aoe';

$save_url = $rootdir.'/forms/'.$form_name.'/save.php';
$validate_url = $rootdir.'/forms/'.$form_name.'/validate.php';
$submit_url = $rootdir.'/forms/'.$form_name.'/submit.php';
$print_url = $rootdir.'/forms/'.$form_name.'/print.php?id='.$id;
$abort_url = $rootdir.'/patient_file/summary/demographics.php';
$reload_url = $rootdir.'/patient_file/encounter/view_form.php?formname='.$form_name.'&id=';
$cancel_url = $rootdir.'/patient_file/encounter/encounter_top.php';
$document_url = $GLOBALS['web_root'].'/controller.php?document&retrieve&patient_id='.$pid.'&document_id=';

/* RETRIEVE FORM DATA */
try {
	$form_data = new wmtForm('labcorp_order',$id);
	$pat_data = wmtPatient::getPidPatient($pid);
	$ins_list = wmtInsurance::getPidInsurance($pid);
	$enc_data = wmtEncounter::getEncounter($encounter);
	$vtl_data = wmtForm::fetchRecent('vitals',$pid);
}
catch (Exception $e) {
	die ("FATAL ERROR ENCOUNTERED: " . $e->getMessage());
	exit;
}

// get labcorp site id
$GLOBALS['lab_corp_siteid'] = ListLook($enc_data->facility_id, 'LabCorp_Site_Identifiers');

// set form status
$completed = FALSE;
if ($form_data->id && $form_data->status != 'i') $completed = TRUE;

// VALIDATE INSTALL
$invalid = "";
if (!$GLOBALS["lab_corp_enable"]) $invalid .= "LabCorp Interface Not Enabled\n";
if (!$GLOBALS["lab_corp_catid"] > 0) $invalid .= "No LabCorp Document Category\n";
if (!$GLOBALS["lab_corp_facilityid"]) $invalid .= "No Receiving Facility Identifier\n";
if (!$GLOBALS["lab_corp_siteid"]) $invalid .= "No Sending Clinic Site Identifier\n";
if (!$GLOBALS["lab_corp_username"]) $invalid .= "No LabCorp Username\n";
if (!$GLOBALS["lab_corp_password"]) $invalid .= "No LabCorp Password\n";
if (!file_exists("{$GLOBALS["OE_SITE_DIR"]}/lab")) $invalid .= "No Lab Work Directory\n";
if (!file_exists("{$GLOBALS["srcdir"]}/wmt")) $invalid .= "Missing WMT Library\n";
if (!file_exists("{$GLOBALS["srcdir"]}/labcorp")) $invalid .= "Missing LabCorp Library\n";
if (!file_exists("{$GLOBALS["srcdir"]}/tcpdf")) $invalid .= "Missing TCPDF Library\n";
if (!file_exists("{$GLOBALS["srcdir"]}/phpseclib")) $invalid .= "Missing SSH/SFTP Library\n";
//if (!extension_loaded("curl")) $invalid .= "CURL Module Not Enabled\n";
if (!extension_loaded("xml")) $invalid .= "XML Module Not Enabled\n";
//if (!extension_loaded("sockets")) $invalid .= "SOCKETS Module Not Enabled\n";
//if (!extension_loaded("soap")) $invalid .= "SOAP Module Not Enabled\n";
if (!extension_loaded("openssl")) $invalid .= "OPENSSL Module Not Enabled\n";

if ($invalid) { ?>
<h1>LabCorp Laboratory Interface Not Available</h1>
The interface is not enabled, not properly configured, or required components are missing!!
<br/><br/>
For assistance with implementing this service contact:
<br/><br/>
<a href="http://www.williamsmedtech.com/page4/page4.html" target="_blank"><b>Williams Medical Technologies Support</b></a>
<br/><br/>
<table style="border:2px solid red;padding:20px"><tr><td style="white-space:pre;color:red"><h3>DEBUG OUTPUT</h3><?php echo $invalid ?></td></tr></table>
<?php
exit; 
}

// round vitals values
$vtl_height = (is_numeric($vtl_data->height))? intval($vtl_data->height): '';
$vtl_weight = (is_numeric($vtl_data->weight))? intval($vtl_data->weight): '';

// labcorp insurance codes
$ins_primary_id = $ins_list[0]->company_id; 
$ins_primary_labcorp = '';
if ( $ins_primary_id ) {
	$ins_primary_labcorp = ListLook($ins_primary_id,'LabCorp_Insurance');
	if (!$ins_primary_labcorp) $ins_primary_labcorp = 'Missing';
} 
$ins_secondary_id = $ins_list[1]->company_id; 
$ins_secondary_labcorp = '';
if ( $ins_secondary_id ) {
	$ins_secondary_labcorp = ListLook($ins_secondary_id,'LabCorp_Insurance');
	if (!$ins_secondary_labcorp) $ins_secondary_labcorp = 'Missing';
}

// test items for the order
$item_list = array();
if ($form_data->id) {
	$query = "SELECT  * FROM $item_table WHERE parent_id = '$form_data->id' ORDER BY id";
	$result = sqlStatement($query);
	while ($row = sqlFetchArray($result)) {
		$item_list[] = $row;
	}
}

// aoe responses for the order
$aoe_list = array();
if ($form_data->id) {
	$query = "SELECT  * FROM $aoe_table WHERE parent_id = '$form_data->id' ORDER BY id";
	$result = sqlStatement($query);
	while ($row = sqlFetchArray($result)) {
		$aoe_list[$row['zseg']] = $row;
	}
}

// retrieve diagnosis quick list
$query = "SELECT title, notes, formatted_dx_code AS code, short_desc, long_desc FROM list_options l ";
$query .= "JOIN icd9_dx_code c ON c.formatted_dx_code = l.option_id ";
$query .= "WHERE l.list_id LIKE 'LabCorp\_Diagnosis%' ";
$query .= "ORDER BY l.title, l.seq";
$result = sqlStatement($query);

$dlist = array();
while ($data = sqlFetchArray($result)) {
	// create array ('tab title','icd9 code','short title','long title')
	$dlist[] = $data;
}

// retrieve order quick list
$query = "SELECT DISTINCT title, notes, test_cd AS code, test_text AS description FROM list_options l ";
$query .= "JOIN labcorp_codes c ON c.test_cd = l.option_id ";
$query .= "WHERE l.list_id LIKE 'LabCorp\_Laboratory%' ";
$query .= "ORDER BY l.title, l.seq";
$result = sqlStatement($query);

$olist = array();
while ($data = sqlFetchArray($result)) {
	// create array ('tab title','icd9 code','short title','long title')
	$olist[] = $data;
}

// retrieve zseg list entries
$query = "SELECT list_id, option_id, title, is_default, notes FROM list_options ";
$query .= "WHERE list_id LIKE 'LabCorp\_%' ";
$query .= "ORDER BY list_id, seq";
$result = sqlStatement($query);

$aoe_id = '';
while ($data = sqlFetchArray($result)) {
	if ($data['list_id'] != $aoe_id) {
		$aoe_id = $data['list_id'];
		${$aoe_id.'_list'} = array();
	}
	${$aoe_id.'_list'}[] = $data;
}

if (!function_exists('UserIdLook')) {
	function UserIdLook($thisField) {
	  if(!$thisField) return '';
	  $ret = '';
	  $rlist= sqlStatement("SELECT * FROM users WHERE id='" .
	           $thisField."'");
	  $rrow= sqlFetchArray($rlist);
	  if($rrow) {
	    $ret = $rrow['lname'].', '.$rrow['fname'].' '.$rrow['mname'];
	  }
	  return $ret;
	}
}

function getLabelers($thisField) {
	$rlist= sqlStatement("SELECT * FROM list_options WHERE list_id = 'LabCorp_Label_Printers' ORDER BY seq, title");
	
	$active = '';
	$default = '';
	$labelers = array();
	while ($rrow= sqlFetchArray($rlist)) {
		if ($thisField == $rrow['option_id']) $active = $rrow['option_id'];
		if ($rrow['is_default']) $default = $rrow['option_id'];
		$labelers[] = $rrow; 
	}

	if (!$active) $active = $default;
	
	echo "<option value=''";
	if (!$active) echo " selected='selected'";
	echo ">&nbsp;</option>\n";
	foreach ($labelers AS $rrow) {
		echo "<option value='" . $rrow['option_id'] . "'";
		if ($active == $rrow['option_id']) echo " selected='selected'";
		echo ">" . $rrow['title'];
		echo "</option>\n";
	}
}

function getPrinters($thisField) {
	$rlist= sqlStatement("SELECT * FROM list_options WHERE list_id = 'LabCorp_Printers' ORDER BY seq, title");
	
	$active = '';
	$default = '';
	$labelers = array();
	while ($rrow= sqlFetchArray($rlist)) {
		if ($thisField == $rrow['option_id']) $active = $rrow['option_id'];
		if ($rrow['is_default']) $default = $rrow['option_id'];
		$printers[] = $rrow; 
	}

	if (!$active) $active = $default;
	
	echo "<option value=''";
	if (!$active) echo " selected='selected'";
	echo ">&nbsp;</option>\n";
	foreach ($printers AS $rrow) {
		echo "<option value='" . $rrow['option_id'] . "'";
		if ($active == $rrow['option_id']) echo " selected='selected'";
		echo ">" . $rrow['title'];
		echo "</option>\n";
	}
}

function doAOE($zseg,$aoe_list,$section='') {

	// organize answers (zseg field = text value)
	$aoe = array();
	for ($i = 0; $i < 45; $i++) 
		$aoe[$aoe_list["aoe{$i}_code"]] = $aoe_list["aoe{$i}_text"];
	 
	// retrieve zseg questions
	$query = "SELECT aoe.field, aoe.question, aoe.answers, aoe.size, aoe.comments, list.list_id, list.option_id, list.title, list.notes, list.is_default FROM labcorp_aoe aoe ";
	$query .= "LEFT JOIN list_options list ON aoe.list = list.list_id ";
	$query .= "WHERE aoe.active = 'Y' AND aoe.zseg = '".$zseg."' AND aoe.section = '".$section."' ORDER BY aoe.seq ";
	$result = sqlStatement($query);

	$aoe_id = '';
	$list_id = '';
	while ($data = sqlFetchArray($result)) {
//		if (strpos($data['field'], 'ZCI') !== false || $data['field'] == 'PID10') continue;
		$size = ($data['size'] > 0)? $data['size']: "30"; // default sizes
	
		if ($data['field'] != $aoe_id) {
			$newRow .= "<tr><td class='wmtLabel' style='text-align:right;min-width:200px'>".$data['question'].": </td>\n";
			$aoe_id = $data['field'];
			$list_id = '';
		}

		if ($data['list_id']) { // list answers
			if ($data['list_id'] != $list_id) {
				$list_id = $data['list_id'];
				$newRow .= "<input type='hidden' name='aoe_".$zseg."_label[]' value='".$data['question']."' />\n";
				$newRow .= "<input type='hidden' name='aoe_".$zseg."_list[]' value='".$data['list_id']."' />\n";
				$newRow .= "<input type='hidden' name='aoe_".$zseg."_code[]' value='".$data['field']."' />\n";
				$newRow .= "<input type='hidden' name='aoe_".$zseg."_section[]' value='".$data['section']."' />\n";
				$newRow .= "<td style='min-width:200px'><select class='wmtInput aoe' name='aoe_".$zseg."_text[]'>\n";
				$newRow .= "<option value='_blank' ";
				if (!$aoe[$data['field']]) $newRow .= " selected "; // if field value empty
				$newRow .= "> </option>\n";
			}

			$newRow .= "<option value='".$data['option_id']."' ";
			if ($aoe[$data['field']] == $data['option_id']) $newRow .= " selected "; // field value = option value
			$newRow .= ">".$data['title']."</option>\n";
		}
		else { // simple answers
			$newRow .= "<input type='hidden' name='aoe_".$zseg."_label[]' value='".$data['question']."' />\n";
			$newRow .= "<input type='hidden' name='aoe_".$zseg."_list[]' value='' />\n";
			$newRow .= "<input type='hidden' name='aoe_".$zseg."_code[]' value='".$data['field']."' />\n";
			$newRow .= "<input type='hidden' name='aoe_".$zseg."_section[]' value='".$data['section']."' />\n";
			$newRow .= "<td style='min-width:200px'><input name='aoe_".$zseg."_text[]' title='".$data['field'].": ".$data['comments'];
			$newRow .= "' class='wmtInput aoe' value='".$aoe[$data['field']]."' size='".$size."' style='padding-left:6px' /></td></tr>\n";
		}
	} // end while

	if ($list_id) $newRow .= "</select></td></tr>\n";

	return $newRow;
}

?>

<!DOCTYPE HTML>
<html>
	<head>
		<?php html_header_show();?>
		<title><?php echo $form_title; ?></title>

		<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
		<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox-1.3.4/jquery.fancybox-1.3.4.css" media="screen" />
		<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/interface/forms/labcorp_order/style_wmt.css" media="screen" />
		<!-- link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css" media="screen" / -->
		
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.7.2.min.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-ui-1.10.0.custom.min.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/common.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox-1.3.4/jquery.fancybox-1.3.4.pack.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/overlib_mini.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/textformat.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/wmt/wmtstandard.js"></script>
		
		<!-- pop up calendar -->
		<style type="text/css">@import url(<?php echo $GLOBALS['webroot'] ?>/library/dynarch_calendar.css);</style>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dynarch_calendar.js"></script>
		<?php include_once("{$GLOBALS['srcdir']}/dynarch_calendar_en.inc.php"); ?>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dynarch_calendar_setup.js"></script>
	
<style>
.Calendar tbody .day { border: 1px solid inherit; }
.wmtMainContainer { min-width: 880px }
.wmtMainContainer table { font-size: 12px; }
.wmtMainContainer fieldset { margin-top: 0; }

.css_button_small { background: transparent url( '../../../images/bg_button_a_small.gif' ) no-repeat scroll top right; }
.css_button_small span { background: transparent url( '../../../images/bg_button_span_small.gif' ) no-repeat; }
.css_button { background: transparent url( '../../../images/bg_button_a.gif' ) no-repeat scroll top right; }
.css_button span { background: transparent url( '../../../images/bg_button_span.gif' ) no-repeat; }
</style>

		<script>
			var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';

			// validate data and submit form
			function saveClicked() {
				var f = document.forms[0];
				$resp = confirm("Your order will be saved but will NOT be submitted.\n\nClick 'OK' to save and exit.");
				if ($resp) {
					if (top.frames.length > 0) top.restoreSession();
					f.submit();
				}
 			}

			function submitClicked() {
				// minimum validation
				notice = '';
				if ($('.code').length < 1) notice += "\n- At least one diagnosis code required.";
				if ($('.test').length < 1) notice += "\n- At least one profile / test code required.";
				if ($('#request_provider').val() == '_blank') notice += "\n- An ordering physician is required.";
				if ($('#request_account').val() == '') notice += "\n- A billing account must be specified.";
				
				if (notice) {
					notice = "PLEASE CORRECT THE FOLLOWING:\n" + notice;
					alert(notice);
					return;
				}

				$.fancybox.showActivity();
				
				$('#process').val('1'); // flag doing submit
				
				$.ajax ({
					type: "POST",
					url: "<?php echo $save_url ?>",
					data: $("#<?php echo $form_name; ?>").serialize(),
					success: function(data) {
			            $.fancybox({
			                'content' 				: data,
							'overlayOpacity' 		: 0.6,
							'showCloseButton' 		: false,
							'width'					: 'auto',
							'height' 				: 'auto',
							'centerOnScroll' 		: false,
							'autoScale'				: false,
							'autoDimensions'		: false,
							'hideOnOverlayClick' 	: false
						});
					}
				});
			}

 			function openPrint() {
				<?php if ($mode=='single') { ?>
				location.href="<?php echo $print_url ?>";
				<?php } else { ?>
				top.restoreSession();
				window.open('<?php echo $print_url ?>','_blank');
				return;
				<?php } ?>
 			}

			function doClose() {
				<?php if ($mode=='single') { ?>
				window.close();
				<?php } else { ?>
				top.restoreSession();
				window.location='<?php echo $cancel_url ?>';
				<?php } ?>
			}
			
			function doReturn(id) {
				<?php if ($mode=='single') { ?>
				window.close();
				<?php } else { ?>
				top.restoreSession();
				window.location= '<?php echo $reload_url?>'+id;
				<?php } ?>
			}
			
 			 // define ajax error handler
			$(function() {
			    $.ajaxSetup({
			        error: function(jqXHR, exception) {
			            if (jqXHR.status === 0) {
			                alert('Not connect to network.');
			            } else if (jqXHR.status == 404) {
			                alert('Requested page not found. [404]');
			            } else if (jqXHR.status == 500) {
			                alert('Internal Server Error [500].');
			            } else if (exception === 'parsererror') {
			                alert('Requested JSON parse failed.');
			            } else if (exception === 'timeout') {
			                alert('Time out error.');
			            } else if (exception === 'abort') {
			                alert('Ajax request aborted.');
			            } else {
			                alert('Uncaught Error.\n' + jqXHR.responseText);
			            }
			        }
			    });

			    return false;
			});

			// search for the provided icd9 code
			function searchDiagnosis() {
				var output = '';
				var f = document.forms[0];
				var code = f.searchIcd.value;
				if ( code == '' ) { 
					alert('You must enter a diagnosis search code.');
					return;
				}
				
				// retrieve the diagnosis array
				$.ajax ({
					type: "POST",
					url: "<?php echo $GLOBALS['webroot'] ?>/library/labcorp/LabCorpAjax.php",
					dataType: "json",
					data: {
						type: 'icd9',
						code: code
					},
					success: function(data) {
				    	$.each(data, function(key, val) {
					    	id = val.code.replace('.','_');
				    		output += "<tr><td style='white-space:nowrap'><nowrap><input class='wmtCheck' type='checkbox' name='check_"+id+"' code='"+val.code+"' desc='"+val.long_desc+"'/> <b>"+val.code+"</b> - </nowrap></td><td style='width:auto;text-align:left'>"+val.short_desc+"<br/></td>\n";
						});
					},
					async:   false
				});

				if (output == '') {
					output = '<table><tr><td><h4>NO MATCHES</h4></td></tr></table>';
				}
				else{
					output = '<table>' + output + '</table>';
				}
				
				$('#dc_Search').html(output);
				$("#dc_tabs").tabs( "option", "active", 0 );	
				f.searchIcd.value = '';
			}

			function addCodes() {
				var count = 0;
				$('#dc_tabs').tabs('option','active');
				$("#dc_tabs div[aria-hidden='false'] input:checked").each(function() {
					success = addCodeRow($(this).attr('code'), $(this).attr('desc'));
					$(this).attr('checked',false);
					if (success) count++;
				});
// PER RICK		if (count) alert("Requested items added to order.");
			}
			
			function addCodeRow(code,text) {
				$('#codeEmptyRow').remove();

				id = code.replace('.','_');
				if ($('#code_'+id).length) {
					alert("Code "+code+" has already been added.");
					return false;
				}

				if ($('#codeTable tr').length > 10) {
					alert("Maximum number of diagnosis codes exceeded.");
					return false;
				}
				
				var newRow = "<tr id='code_" +id + "'>";
				newRow += "<td><input type='button' class='wmtButton' value='remove' style='width:60px' onclick=\"removeCodeRow('code_"+id+"')\" /></td>\n";
				newRow += "<td class='wmtLabel'><input name='dx_code[]' class='wmtFullInput code' style='font-weight:bold' readonly value='";
				newRow += code;
				newRow += "'/></td><td class='wmtLabel'><input name='dx_text[]' class='wmtFullInput name' readonly value='";
				newRow += text;
				newRow += "'/></td></tr>\n";
				
				$('#codeTable').append(newRow);

				return true;
			}

			function removeCodeRow(id) {
				$('#'+id).remove();
				// there is always the header and the "empty" row
				if ($('#codeTable tr').length == 1) $('#codeTable').append('<tr id="CodeEmptyRow"><td colspan="3"><b>NO PROFILES / TESTS SELECTED</b></td></tr>');
			}

			// search for the provided test code
			function searchTest() {
				var output = '';
				var f = document.forms[0];
				var code = f.searchCode.value;
				if ( code == '' ) { 
					alert('You must enter a profile or lab test search code.');
					return;
				}
				
				// retrieve the test array
				$.ajax ({
					type: "POST",
					url: "<?php echo $GLOBALS['webroot'] ?>/library/labcorp/LabCorpAjax.php",
					dataType: "json",
					data: {
						type: 'lab',
						code: code
					},
					success: function(data) {
						// data = array('code','type','description','specimen','storage');
						$.each(data, function(key, val) {
					    	id = val.code.replace('.','_');
					    	text = val.description;
//					    	if (val.type != '') text += " [ " + val.specimen + " ]";
				    		output += "<tr><td style='white-space:nowrap'><nowrap><input class='wmtCheck' type='checkbox' name='check_"+id+"' code='"+val.code+"' desc='"+text+"' prof='"+val.type+"' /> ";
				    		if (val.type == 'P') {
					    		output += "<span style='font-weight:bold;color:#c00;vertical-align:middle'>"+val.code+"</span>";
				    		}
				    		else { 	
					    		output += "<span style='font-weight:bold;vertical-align:middle'>"+val.code+"</span>";
				    		}
				    		output += " - </nowrap></td><td style='width:auto;text-align:left'>"+val.description+"<br/></td>\n";
				    	});
					},
					async:   false
				});

				if (output == '') {
					output = '<table><tr><td><h4>NO MATCHES</h4></td></tr></table>';
				}
				else{
					output = '<table>' + output + '</table>';
				}
				
				$('#oc_Search').html(output);
				$("#oc_tabs").tabs( "option", "active", 0 );	
				f.searchCode.value = '';
			}

			// search for the provided test code
			function fetchDetails(code) {
				var output = '';
				
				// retrieve the test details
				$.ajax ({
					type: "POST",
					url: "<?php echo $GLOBALS['webroot'] ?>/library/labcorp/LabCorpAjax.php",
					dataType: "json",
					data: {
						type: 'details',
						code: code
					},
					success: function(data) {
						output = data; // process later
					},
					async:   false
				});

				return output;
			}

			function addTests() {
				var count = 0;
				var errors = 0;
				$('#oc_tabs').tabs('option','active');
				$("#oc_tabs div[aria-hidden='false'] input:checked").each(function() {
					success = addTestRow($(this).attr('code'),$(this).attr('desc'),$(this).attr('prof'));
					$(this).attr('checked',false);
					if (success) {
						count++;
					}
					else {
						errors++;
					}
				});
				if (count) {
					if (errors) {
						alert("Some items were not added to order.");
					}
					else {
// PER RICK				alert("Requested items added to order.");
					}
				}
			}
			
			function addTestRow(code,text,flag) {
				$('#orderEmptyRow').remove();

				id = code.replace('.','_');
				if ($('#test_'+id).length) {
					alert("Test "+code+" has already been added.");
					return false;
				}

				if ($('#order_table tr').length > 35) {
					alert("Maximum number of profile/test requests exceeded.");
					return false;
				}

				var data = fetchDetails(code);
				var profile = data.profile; // json data fron ajax
				var zseg = data.zseg; // aoe section
				var type = data.type; // test proc class
				var ctype = $('#order_type').val();
				var czseg = $('#order_zseg').val();
				
				if (ctype != '' && ctype != type && (ctype == 'CY' || type == 'CY') ) {
					alert("SPECIMEN PATHOLOGY MISMATCH: \nThis test requires different processing and must be\nentered on a separate request.");
					return false;
				}
				
				if (ctype != '' && ctype != type && (ctype == 'HI' || type == 'HI') ) {
					alert("SPECIMEN HISTOLOGY MISMATCH: \nThis test requires different processing and must be\nentered on a separate request.");
					return false;
				}
				
				if (czseg != '' && czseg == 'SOURCE' && zseg == 'SOURCE') {
					alert("SPECIMEN DUPLICATION: \nAnother test requiring source data has already been selected.\nPlease enter this test on a separate request.");
					return false;
				}
				
				if (zseg != '') {
					if (czseg == '') {
						$('#order_zseg').val(zseg);
					}
<?php if ($GLOBALS['lab_corp_psc']) { ?>
					else if (czseg != zseg) {
						alert("SPECIMEN DATA MISMATCH: \nTest ["+code+"] requires different information and must be\nentered on a separate request.");
						return false;
					}
<?php } ?>
				}

				var success = true;
				$('.component').each(function() {
					if ($(this).attr('unit') == code && success) {
						alert("Test "+code+" has already been added as profile component.");
						success = false;
					} 					
				});

				if (!success) return false;

				var newRow = "<tr id='test_" +id + "'>";
				newRow += "<td style='vertical-align:top'><input type='button' class='wmtButton' value='remove' style='width:60px' onclick=\"removeTestRow('test_"+id+"')\" /> ";
				newRow += "<input type='button' class='wmtButton' value='details' style='width:60px' onclick=\"testOverview('"+id+"')\" /></td>\n";
				newRow += "<td class='wmtLabel' style='vertical-align:top;padding-top:5px'><input name='test_code[]' class='wmtFullInput test' readonly value='"+code+"' ";
				if (flag == 'P' || flag == 'S') { // profile test
					newRow += "style='font-weight:bold;color:#c00' /><input type='hidden' name='test_profile[]' value='1' />";
				}
				else {
					newRow += "style='font-weight:bold' /><input type='hidden' name='test_profile[]' value='0' />";
				} 
 				newRow += "</td><td colspan='2' class='wmtLabel' style='text-align:right;vertical-align:top;padding-top:5px'><input name='test_text[]' class='wmtFullInput component' readonly unit='"+code+"' value='"+text+"'/><input type='hidden' name='test_type[]' value='"+type+"' /><input type='hidden' name='test_zseg[]' value='"+zseg+"' />\n";
  				
				// add profile tests if necessary
				success = true;
				for (var key in profile) {
					var obj = profile[key];

					$('.component').each(function() {
						if ($(this).attr('unit') == obj.component && success) {
							alert("Component of test "+code+" has already been added.");
							success = false;
						} 					
					});
						
					if (obj.description) newRow += "<input class='wmtFullInput component' style='margin-top:5px' readonly unit='"+obj.component+"' value='"+obj.component+" - "+obj.description+"'/>\n";

				}
				if (!success) return false;
				
				newRow += "</td></tr>\n"; // finish up order row
				
				$('#order_table').append(newRow);

				aoeDisplay(); // show/hide aoe sections
				return true;
			}

			function removeTestRow(id) {
				$('#'+id).remove();
				aoeDisplay(); // fix aoe sections
				// there is always the header and the "empty" row
				if ($('#order_table tr').length == 1) {
					 $('#order_table').append('<tr id="orderEmptyRow"><td colspan="3"><b>NO PROFILES / TESTS SELECTED</b></td></tr>');
				}
			}

			// determine which AOE sections are needed
			function aoeDisplay() {
				$('#order_type').val(''); // clear current type flag
				$('#order_zseg').val(''); // clear current zseg flag

				var i = 0;
				var type_needed = new Array();
				$("input[name='test_type[]']").each(function() {
					var type = $(this).val();
					if (type == 'HI' || type == 'CY') {
						$('#order_type').val(type);
					}
				});
					
				var i = 0;
				var zseg_needed = new Array();
				$("input[name='test_zseg[]']").each(function() {
					var zseg = $(this).val();
					if ($.inArray(zseg,zseg_needed) == -1) {
						zseg_needed[i++] = zseg; 
					}
				});
				
				$("table[zseg]").each(function() {
					zseg = $(this).attr("zseg");
					if ($.inArray(zseg,zseg_needed) == -1) { // not in array, not needed
						if ($("table[zseg='"+zseg+"']").is(":visible")) $("table[zseg='"+zseg+"']").hide(); // hide aoe section
					}
					else {
						if ($("table[zseg='"+zseg+"']").is(":hidden")) $("table[zseg='"+zseg+"']").show(); // display aoe section
						$('#order_zseg').val(zseg); // there should only be one
					}
				});
			}

			// display test overview pop up
			function testOverview(code) {
				$.fancybox.showActivity();
				
				// retrieve the overview details
				$.ajax ({
					type: "POST",
					url: "<?php echo $GLOBALS['webroot'] ?>/library/labcorp/LabCorpAjax.php",
					dataType: "html",
					data: {
						type: 'overview',
						code: code
					},
					success: function(data) {
			            $.fancybox({
			                'content' 				: data,
							'overlayOpacity' 		: 0.6,
							'showCloseButton' 		: true,
							'width'					: '500',
							'height' 				: '400',
							'centerOnScroll' 		: false,
							'autoScale'				: false,
							'autoDimensions'		: false,
							'hideOnOverlayClick' 	: true,
							'scrolling'				: 'auto'
						});
										},
					async:   false
				});

				return;
			}

			
			// print labels
			function printLabels(item) {
				var f = document.forms[0];
				var fl = document.forms[item];
				var printer = fl.labeler.value;
				if ( printer == '' ) { 
					alert('Unable to determine default label printer.\nPlease select a label printer.');
					return;
				}

				var count = fl.count.value;
				var order = f.order_number.value;
				var patient = "<?php echo $pat_data->lname; ?>, <?php echo $pat_data->fname; ?> <?php echo $pat_data->mname; ?>";
				var pid = "<?php echo $pat_data->pid  ?>";
				
				// retrieve the label
				$.ajax ({
					type: "POST",
					url: "<?php echo $GLOBALS['webroot'] ?>/library/labcorp/LabCorpAjax.php",
					dataType: "text",
					data: {
						type: 'label',
						printer: printer,
						count: count,
						order: order,
						patient: patient,
						pid: pid,
						siteid: '<?php echo $GLOBALS['lab_corp_siteid'] ?>'
					},
					success: function(data) {
						if (printer == 'file') {
							window.open(data,"_blank");
						}
						else {
							alert(data);
						}
					},
					async:   false
				});

			}

			// print document
			function printDocument(item) {
				var f = document.forms[0];
				var fl = document.forms[item];
				var printer = fl.printer.value;
				if ( printer == '' ) { 
					alert('Unable to determine default printer.\nPlease select a printer.');
					return;
				}

				var reqid = fl.order_req_id.value;
				var order = f.order_number.value;
				var patient = "<?php echo $pat_data->lname; ?>, <?php echo $pat_data->fname; ?> <?php echo $pat_data->mname; ?>";
				var pid = "<?php echo $pat_data->pid  ?>";

				if (printer == 'file') {
					window.open("<?php echo $document_url ?>"+reqid,"_blank");
					exit;
				}
				
				// retrieve the document
				$.ajax ({
					type: "POST",
					url: "<?php echo $GLOBALS['webroot'] ?>/library/labcorp/LabCorpAjax.php",
					dataType: "text",
					data: {
						type: 'print',
						printer: printer,
						order: order,
						patient: patient,
						pid: pid,
						reqid: reqid,
						siteid: '<?php echo $GLOBALS['lab_corp_siteid'] ?>'
					},
					success: function(data) {
						if (printer == 'file') {
							window.open(data,"_blank");
						}
						else {
							alert(data);
						}
					},
					async:   false
				});
			}

			// process insurance id update
			function doInsurance(code1,code2) {
				var ins_primary_company = $('#fancybox-content #ins_primary_company').val(); 
				var ins_primary_labcorp = $('#fancybox-content #ins_primary_labcorp').val();

				var ins_secondary_company = $('#fancybox-content #ins_secondary_company').val();
				var ins_secondary_labcorp = $('#fancybox-content #ins_secondary_labcorp').val();
				
				if ( (ins_primary_company && !ins_primary_labcorp) || (ins_secondary_company && !ins_secondary_labcorp) ) { 
					alert('LabCorp insurance identifiers are required to submit order.\nPlease obtain proper identifiers.');
					return;
				}

				$.fancybox.showActivity();
				
				// store insurance identifiers
				$.ajax ({
					type: "POST",
					url: "<?php echo $GLOBALS['webroot'] ?>/library/labcorp/LabCorpAjax.php",
					dataType: "html",
					data: {
						type: 	'insurance',
						ins1: 	ins_primary_company,
						code1: 	ins_primary_labcorp,
						ins2: 	ins_secondary_company,
						code2: 	ins_secondary_labcorp
					},
					success: function(data) {
			            $.fancybox.close()
					},
					async:   false
				});

				return;
			}

			// setup jquery processes
			$(document).ready(function(){
				$('#dc_tabs').tabs().addClass('ui-tabs-vertical ui-helper-clearfix');
				$('#oc_tabs').tabs().addClass('ui-tabs-vertical ui-helper-clearfix');

				$("#searchIcd").keyup(function(event){
				    if(event.keyCode == 13){
				        searchDiagnosis();
				    }
				});

				$("#searchCode").keyup(function(event){
				    if(event.keyCode == 13){
				        searchTest();
				    }
				});
				
				$("#order_psc").change(function(){
					$("#sample_data").show();
					
				    if ($(this).attr("checked")) {
						$("#sample_data").hide();
				    }
				});
				
				$("#work_flag").change(function(){
					$("#work_data").hide();
					
				    if ($(this).attr("checked")) {
						$("#work_data").show();
				    }
				});
				
<?php if ($completed) { // disable everything ?>
				$("#<?php echo $form_name; ?> :input").attr("disabled", true);
				$(".nolock").attr("disabled", false);
<?php } ?>

<?php /* REMOVED PER LABCORP 
			if ($ins_primary_labcorp == 'Missing' || $ins_secondary_labcorp == 'Missing') { ?>
				$.fancybox({
					'hideOnOverlayClick': false,
					'showCloseButton': false,
					'enableEscapeButton': false,
					'scrolling': 'no',
					'content': $("#labcorp_insurance").html()
				});

<?php } */ ?>
			});
				
			
		</script>
	</head>

	<body class="body_top">

		<!-- Required for the popup date selectors -->
		<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
		
		<form method='post' action="<?php echo $save_url ?>" id='<?php echo $form_name; ?>' name='<?php echo $form_name; ?>' > 
			<input type='hidden' name='process' id='process' value='' />
			<input type='hidden' name='labcorp_siteid' id='labcorp_siteid' value='<?php echo $GLOBALS['lab_corp_siteid'] ?>' />
			<input type='hidden' name='facility_id' id='facility_id' value='<?php echo $enc_data->facility_id ?>' />
			<div class="wmtTitle">
<?php if ($viewmode) { ?>
				<input type=hidden name='mode' value='update' />
				<input type=hidden name='id' value='<?php echo $_GET["id"] ?>' />
				<span class=title><?php echo $form_title; ?> <?php echo ($form_data->status == 'p')? 'View Only': 'Update' ?></span>
<?php } else { ?>
				<input type='hidden' name='mode' value='new' />
				<span class='title'>New <?php echo $form_title; ?></span>
<?php } ?>
			</div>

<!-- BEGIN ORDER -->
			<!-- Client Information -->
			<div class="wmtMainContainer" style="width:99%">
				<div class="wmtCollapseBar" id="ReviewCollapseBar" onclick="togglePanel('ReviewBox','ReviewImageL','ReviewImageR','ReviewCollapseBar')">
					<table style="width:100%">	
						<tr>
							<td>
								<img id="ReviewImageL" align="left" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
							<td class="wmtChapter" style="text-align: center">
								Patient Information
							</td>
							<td style="text-align: right">
								<img id="ReviewImageR" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
						</tr>
					</table>
				</div>
				
				<div class="wmtCollapseBox" id="ReviewBox">
					<table style="width:100%">	
						<tr>
							<!-- Left Side -->
							<td style="width:50%" class="wmtInnerLeft">
								<table style="width:99%">
							        <tr>
										<td style="width:20%" class="wmtLabel">
											Patient First
											<input name="pat_first" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->pat_first:$pat_data->fname; ?>">
											<input name="pid" type="hidden" value="<?php echo $pat_data->pid; ?>">
											<input name="pubpid" type="hidden" value="<?php echo $pat_data->pubpid; ?>">
											<input name="encounter" type="hidden" value="<?php echo $encounter; ?>">
										</td>
										<td style="width:10%" class="wmtLabel">
											Middle
											<input name="pat_middle" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->pat_middle:$pat_data->mname; ?>">
										</td>
										<td class="wmtLabel">
											Last Name
											<input name="pat_last" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->pat_last:$pat_data->lname; ?>">
										</td>
										<td style="width:20%" class="wmtLabel">
											Patient Id
											<input name="pat_pid" type="text" class="wmtFullInput" readonly value="<?php echo $pat_data->pubpid; ?>">
										</td>
										<td colspan="2" style="width:20%" class="wmtLabel">
											Social Security
											<input name="pat_ss" type"text" class="wmtFullInput" readonly value="<?php echo $pat_data->ss ?>">
										</td>
									</tr>

									<tr>
										<td colspan="3" class="wmtLabel">Email Address<input name="pat_email" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->pat_email:$pat_data->email; ?>"></td>
										<td style="width:20%" class="wmtLabel">
											Birth Date
											<input name="pat_DOB" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->pat_DOB:$pat_data->birth_date; ?>">
										</td>
										<td style="width:5%" class="wmtLabel">
											Age
											<input name="pat_age" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->pat_age:$pat_data->age; ?>">
										</td>
										<td style="width:15%" class="wmtLabel">
											Gender
											<input name="pat_sex" type="hidden" value="<?php echo ($completed)?$form_data->pat_sex:$pat_data->sex ?>" />
											<input type="text" class="wmtFullInput" readonly value="<?php echo ListLook(($completed)?$form_data->pat_sex:$pat_data->sex, 'sex') ?>">
										</td>
										<!-- td style="width:15%" class="wmtLabel">
											Race
											<input name="pat_race" type="hidden" value="<?php echo ($completed)?$form_data->pat_race:$pat_data->race ?>" />
											<input type="text" class="wmtFullInput" readonly value="<?php echo ListLook(($completed)?$form_data->pat_race:$pat_data->race, 'Race') ?>">
										</td>
										<td style="width:15%" class="wmtLabel">
											Ethnicity
											<input name="pat_ethnicity" type="hidden" value="<?php echo ($completed)?$form_data->pat_ethnicity:$pat_data->ethnicity ?>" />
											<input type="text" class="wmtFullInput" readonly value="<?php echo ListLook(($completed)?$form_data->pat_ethnicity:$pat_data->ethnicity, 'Ethnicity') ?>">
										</td -->
									</tr>
																		
									<tr>
										<td colspan="3" class="wmtLabel">
											Primary Address
											<input name="pat_street" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->pat_street:$pat_data->street; ?>">
										</td>
										<td class="wmtLabel">Mobile Phone<input name="pat_mobile" id="ex_phone_mobile" type="text" class="wmtFullInput" readonly value="<?php echo $pat_data->phone_cell; ?>"></td>
										<td colspan="2" class="wmtLabel">Home Phone<input name="pat_phone" type="text" class="wmtFullInput" readonly value="<?php echo $pat_data->phone_home; ?>"></td>
									</tr>

									<tr>
										<td colspan="3" class="wmtLabel" style="width:50%">
											City
											<input name="pat_city" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->pat_city:$pat_data->city; ?>">
										</td>
										<td class="wmtLabel">
											State
											<input type="text" class="wmtFullInput" readonly value="<?php echo ListLook(($completed)?$form_data->pat_state:$pat_data->state, 'state'); ?>">
											<input type="hidden" name="pat_state" value="<?php echo ($completed)?$form_data->pat_state:$pat_data->state ?>" />
										</td>
										<td colspan="2" class="wmtLabel">
											Postal Code
											<input name="pat_zip" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->pat_zip:$pat_data->postal_code; ?>">
										</td>
									</tr>
								</table>
							</td>
							
							<!-- Right Side -->
							<td style="width:50%" class="wmtInnerRight">
								<table style="width:99%">
									<tr>
										<td style="width:20%" class="wmtLabel">
											Insured First
											<input name="ins_first" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->ins_first:$ins_list[0]->subscriber_fname; ?>">
										</td>
										<td style="width:10%" class="wmtLabel">
											Middle
											<input name="ins_middle" type"text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->ins_middle:$ins_list[0]->subscriber_mname; ?>">
										</td>
										<td class="wmtLabel">
											Last Name
											<input name="ins_last" type"text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->ins_last:$ins_list[0]->subscriber_lname; ?>">
										</td>
										<td style="width:20%" class="wmtLabel">
											Birth Date
											<input name="ins_DOB" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?($form_data->ins_DOB != '0000-00-00')?$form_data->ins_DOB:'':$ins_list[0]->subscriber_birth_date; ?>">
										</td>
										<td style="width:20%" class="wmtLabel">
											Relationship
											<input name="ins_relation" type="text" class="wmtFullInput" readonly value="<?php echo ListLook(($completed)?$form_data->ins_relation:$ins_list[0]->subscriber_relationship, 'sub_relation'); ?>">
											<input name="ins_ss" type="hidden" value="<?php echo ($completed)?$form_data->ins_ss:$ins_list[0]->subscriber_ss ?>" />
											<input name="ins_sex" type="hidden" value="<?php echo ($completed)?$form_data->ins_sex:$ins_list[0]->subscriber_sex ?>" />
										</td>
									</tr>
									<tr>
										<td colspan="3" class="wmtLabel">
											Primary Insurance
											<input name="ins_primary" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->ins_primary:($ins_list[0]->company_name)?$ins_list[0]->company_name:'No Insurance'; ?>">
											<input id="ins_primary_id" name="ins_primary_id" type="hidden" value="<?php echo $ins_list[0]->id ?>"/>
											<input name="ins_primary_plan" type="hidden" value="<?php echo $ins_list[0]->plan_name ?>"/>
											<input name="ins_primary_type" type="hidden" value="<?php echo $ins_list[0]->plan_type ?>"/>
										</td>
										<td class="wmtLabel">Policy #<input name="ins_primary_policy" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->ins_primary_policy:$ins_list[0]->policy_number; ?>"></td>
										<td class="wmtLabel">Group #<input name="ins_primary_group" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->ins_primary_group:$ins_list[0]->group_number; ?>"></td>
									</tr>
									<tr>
										<td colspan="3" class="wmtLabel">
											Secondary Insurance
											<input name="ins_secondary" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->ins_secondary:$ins_list[1]->company_name; ?>">
											<input id="ins_secondard_id" name="ins_secondary_id" type="hidden" value="<?php echo $ins_list[1]->id ?>"/>
											<input name="ins_secondary_plan" type="hidden" value="<?php echo $ins_list[1]->plan_name ?>"/>
										</td>
										<td class="wmtLabel">Policy #<input name="ins_secondary_policy" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->ins_secondary_policy:$ins_list[1]->policy_number; ?>"></td>
										<td class="wmtLabel">Group #<input name="ins_secondary_group" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->ins_secondary_group:$ins_list[1]->group_number; ?>"></td>
									</tr>
									<tr>
										<td style="width:20%" class="wmtLabel">
											Guarantor First
											<input name="guarantor_first" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->guarantor_first:($ins_list[0]->subscriber_lname)?$ins_list[0]->subscriber_fname:$pat_data->fname; ?>">
											<input name="guarantor_phone" type="hidden" value="<?php echo ($ins_list[0]->subscriber_phone)?$ins_list[0]->subscriber_phone:$pat_data->phone_home ?>" />
											<input name="guarantor_street" type="hidden" value="<?php echo ($ins_list[0]->subscriber_street)?$ins_list[0]->subscriber_street:$pat_data->street ?>" />
											<input name="guarantor_city" type="hidden" value="<?php echo ($ins_list[0]->subscriber_street)?$ins_list[0]->subscriber_city:$pat_data->city ?>" />
											<input name="guarantor_state" type="hidden" value="<?php echo ($ins_list[0]->subscriber_street)?$ins_list[0]->subscriber_state:$pat_data->state ?>" />
											<input name="guarantor_zip" type="hidden" value="<?php echo ($ins_list[0]->subscriber_street)?$ins_list[0]->subscriber_postal_code:$pat_data->postal_code ?>" />
										</td>
										<td style="width:10%" class="wmtLabel">
											Middle
											<input name="guarantor_middle" type"text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->guarantor_middle:($ins_list[0]->subscriber_lname)?$ins_list[0]->subscriber_mname:$pat_data->mname; ?>">
										</td>
										<td style="width:20%" class="wmtLabel">
											Last Name
											<input name="guarantor_last" type"text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->guarantor_last:($ins_list[0]->subscriber_lname)?$ins_list[0]->subscriber_lname:$pat_data->lname; ?>">
										</td>
										<td class="wmtLabel">SS#<input name="guarantor_ss" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?$form_data->guarantor_ss:($ins_list[0]->subscriber_ss)?$ins_list[0]->subscriber_ss:$pat_data->ss; ?>"></td>
										<td class="wmtLabel">
											Relationship
											<input name="guarantor_relation" type="text" class="wmtFullInput" readonly value="<?php echo ($completed)?ListLook($form_data->guarantor_relation, 'sub_relation'):($ins_list[0]->subscriber_relationship)?ListLook($ins_list[0]->subscriber_relationship, 'sub_relation'):'Self'; ?>">
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<!-- End Information Review -->
			
			<!--  Start of Order Entry -->
			<div class="wmtMainContainer" style="width:99%">
				<div class="wmtCollapseBar" id="EntryCollapseBar" onclick="togglePanel('EntryBox','EntryImageL','EntryImageR','EntryCollapseBar')">
					<table style="width:100%">	
						<tr>
							<td>
								<img id="EntryImageL" align="left" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
							<td class="wmtChapter" style="text-align: center">
								Order Entry
							</td>
							<td style="text-align: right">
								<img id="EntryImageR" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
						</tr>
					</table>
				</div>
				
				<div class="wmtCollapseBox" id="EntryBox">
					<table style="width:100%">
						<tr>
							<!-- Left Side -->
							<td style="width:50%" class="wmtInnerLeft">
								<table class="wmtLabBox"  style="margin-left:3px">
							        <tr>
										<td class="wmtLabHeader">
											<div style="width:100%;text-align:left;line-height:14px;margin-left:3px">
												CLINICAL DIAGNOSIS CODES&nbsp;
											</div>
											<div style="float:left;vertical-align:bottom;">
												<input type="button" onclick="addCodes()" value="add selected"/>
											</div>
											<div style="float:right">
												<input class="wmtInput" type="text" name="searchIcd" id="searchIcd" />
												<input class="wmtButton" type="button" value="search" onclick="searchDiagnosis()" />
											</div>
										</td>
									</tr>
									<tr>
										<td class="wmtLabBody">
											<div id="dc_tabs">
												<div class="wmtLabMenu">
													<ul style="margin:0;padding:0">
<?php 
$title = 'Search';
echo "<li><a href='#dc_Search'>Search</a></li>\n";
foreach ($dlist as $data) {
	if ($data['title'] != $title) {
		$title = $data['title']; // new tab
		$link = strtolower(str_replace(' ', '_', $title));
		echo "<li><a href='#dc_".$link."'>".$title."</a></li>\n";
	}
}
?>
													</ul>
												</div>
												
<?php 
$title = 'Search';
echo "<div class='wmtQuick' id='dc_Search' style='display:none'><table width='100%'><tr><td style='text-align:center;padding-top:30px'><h3>Select profile at left or<br/>search using search box at top.</h3></tr></td>\n";
foreach ($dlist as $data) {
	if ($data['title'] != $title) {
		if ($title) echo "</table></div>\n"; // end previous section
		$title = $data['title']; // new section
		$link = strtolower(str_replace(' ', '_', $title));
		echo "<div class='wmtQuick' id='dc_".$link."' style='display:none'><table>\n";
	}
	$text = ($data['notes']) ? $data['notes'] : $data['short_desc'];
	$id = str_replace('.', '_', $data['code']);
	echo "<tr><td style='white-space:nowrap'><nowrap><input class='wmtCheck' type='checkbox' id='check_".$id."' code='".$data['code']."' desc='".htmlspecialchars($text)."' > <b>".$data['code']."</b></input> - </nowrap></td><td style='padding-top:0'>".$text."</td></tr>\n";
}
if ($title) echo "</table></div>\n"; // end if at least one section
?>
											</div>
										</td>
									</tr>
								</table>
							</td>
							
							<!-- Right Side -->
							<td style="width:50%" class="wmtInnerRight">
								<table class="wmtLabBox">
							        <tr>
										<td class="wmtLabHeader">
											<div style="width:100%;text-align:left;line-height:14px;margin-left:3px">
												LABORATORY TEST &amp; PANEL CODES&nbsp;
											</div>
											<div style="float:left;vertical-align:bottom;">
												<input type="button" onclick="addTests()" value="add selected"/>
											</div>
											<div style="float:right">
												<input class="wmtInput" type="text" name="searchCode" id="searchCode" />
												<input class="wmtButton" type="button" value="search" onclick="searchTest()" />
											</div>
										</td>
									</tr>
									<tr>
										<td class="wmtLabBody">
											<div id="oc_tabs">
												<div class="wmtLabMenu">
													<ul style="margin:0;padding:0">
<?php 
$title = 'Search';
echo "<li><a href='#oc_Search'>Search</a></li>\n";
foreach ($olist as $data) {
	if ($data['title'] != $title) {
		$title = $data['title']; // new tab
		$link = strtolower(str_replace(' ', '_', $title));
		echo "<li><a href='#oc_".$link."'>".$title."</a></li>\n";
	}
}
?>
													</ul>
												</div>
												
<?php 
$title = 'Search';
echo "<div class='wmtQuick' id='oc_Search' style='display:none'><table width='100%'><tr><td style='text-align:center;padding-top:30px'><h3>Select profile at left or<br/>search using search box at top.</h3>\n";
foreach ($olist as $data) {
	if ($data['title'] != $title) {
		if ($title) echo "</table></div>\n"; // end previous section
		$title = $data['title']; // new section
		$link = strtolower(str_replace(' ', '_', $title));
		echo "<div class='wmtQuick' id='oc_".$link."' style='display:none'><table>\n";
	}
	$text = ($data['notes']) ? $data['notes'] : $data['description'];
	$id = str_replace('.', '_', $data['code']);
	echo "<tr><td style='white-space:nowrap'><nowrap><input class='wmtCheck' type='checkbox' id='mark_".$id."' code='".$data['code']."' desc='".htmlspecialchars($text)."' > <b>".$data['code']."</b></input> - </nowrap></td><td style='padding-top:0'>".$text."</td></tr>\n";
}
if ($title) echo "</table></div>\n"; // end if at least one section
?>
											</div>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<!-- End Order Entry -->
								
			<!--  Start of Review Review -->
			<div class="wmtMainContainer" style="width:99%">
				<div class="wmtCollapseBar" id="OrderCollapseBar" onclick="togglePanel('OrderBox','OrderImageL','OrderImageR','OrderCollapseBar')">
					<table style="width:100%">	
						<tr>
							<td>
								<img id="OrderImageL" align="left" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
							<td class="wmtChapter" style="text-align: center">
								Order Review
							</td>
							<td style="text-align: right">
								<img id="OrderImageR" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
						</tr>
					</table>
				</div>
				
				<div class="wmtCollapseBox" id="OrderBox">
					<table style="width:100%">
						<tr>
							<td>
								<fieldset>
									<legend>Diagnosis Codes</legend>

									<table id="codeTable" style="width:100%">
										<tr>
											<th class="wmtHeader" style="width:60px">Action</th>
											<th class="wmtHeader" style="width:80px">ICD9 Code</th>
											<th class="wmtHeader">Description</th>
										</tr>

<?php 
// load the existing diagnosis codes
$newRow = '';
for ($d = 0; $d < 10; $d++) {
	$codekey = "dx".$d."_code";
	$textkey = "dx".$d."_text";
	if (!$form_data->$codekey) continue;
	$id = str_replace('.', '_', $form_data->$codekey);
	
	// add new row
	$newRow .= "<tr id='code_".$id."'>";
	$newRow .= "<td><input type='button' class='wmtButton' value='remove' style='width:60px' onclick=\"removeCodeRow('code_".$id."')\" /></td>\n";
	$newRow .= "<td class='wmtLabel'><input name='dx_code[]' class='wmtFullInput code' style='font-weight:bold' readonly value='".$form_data->$codekey."'/>\n";
	$newRow .= "</td><td class='wmtLabel'><input name='dx_text[]' class='wmtFullInput name' readonly value='".$form_data->$textkey."'/>\n";
	$newRow .= "</td></tr>\n";
}

// anything found
if ($newRow) {
	echo $newRow;
}
else { // create empty row
?>
										<tr id="codeEmptyRow">
											<td colspan="3">
												<b>NO DIAGNOSIS CODES SELECTED</b>
											</td>
										</tr>
<?php } ?>
									</table>
								</fieldset>
							
							</td>
						</tr>

<?php 
// create unique identifier for order number
if ($viewmode) {
	$ordnum = $form_data->order_number;
}
else {
	$ordnum = generate_id();
}
?>
						<tr>
							<td>
								<fieldset>
									<legend>Order Requisition - <?php echo $ordnum ?></legend>
									<input type="hidden" name="order_number" value="<?php echo $ordnum ?>" />
									<input type="hidden" id="order_type" name="order_type" value="<?php echo $form_data->order_type ?>" />
									<input type="hidden" id="order_zseg" name="order_zseg" value="<?php echo $form_data->order_zseg ?>" />
									
									<table style="margin-bottom:5px">
										<tr>
											<td colspan="8">
												<input type="checkbox" class="wmtCheck" id="work_flag" name="work_flag" value="Y"	<?php if ($form_data->work_flag) echo "checked" ?> />
												<label class="wmtLabel" style="vertical-align:middle">Workers Compensation Claim</label>
											</td>
										</tr>
										<tr id="work_data" style="<?php if (!$form_data->work_flag) echo "display:none" ?>">
											<td style='width:90px'>
												<label class="wmtLabel">Accident Date: </label>
											</td><td style="white-space:nowrap">
												<input class="wmtInput" type='text' size='10' name='work_date' id='work_date' 
													value='<?php echo $viewmode ? ($form_data->work_date == 0)? '' : date('Y-m-d',strtotime($form_data->work_date)) : date('Y-m-d'); ?>'
													title='<?php xl('yyyy-mm-dd Date of accident','e'); ?>'
													onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' />
												<img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22'
													id='img_work_date' border='0' alt='[?]' style='cursor:pointer;cursor:hand;
													title='<?php xl('Click here to choose a date','e'); ?>'>
											</td>
											<td style="padding-left:15px">
												<label class="wmtLabel">Employer: </label>
											</td><td style="white-space:nowrap">
												<input type="text" class="wmtInput" style="width:150px" name='work_employer' id='work_employer' 
												value='<?php echo $form_data->work_employer; ?>' />
											</td>
											<td style="padding-left:15px;white-space:nowrap">
												<label class="wmtLabel" style="vertical-align:middle">Insurance:&nbsp;&nbsp;</label>
												<select name='work_insurance' class='wmtSelect'>
<?php 
	$query = "SELECT * FROM insurance_companies WHERE freeb_type = 25";
	$result = sqlStatement($query);
	while ($record = sqlFetchArray($result)) {
		echo "<option value='".$record['id']."' ";
		if ($form_data->work_insurance == $record['id']) echo "selected ";
		echo ">".$record['name']."</option>\n";
	}
?>
												</select>
											</td>
											<td style="padding-left:15px">
												<label class="wmtLabel">Case #: </label>
											</td><td style="white-space:nowrap">
												<input type="text" class="wmtInput" style="width:80px" name='work_case' id='work_case' 
												value='<?php echo $form_data->work_case; ?>' />
											</td>
										</tr>
<?php if ($ins_list[0]->plan_type == 2) { // medicare ?>
										<tr>
											<td colspan="8">
												<input type="checkbox" class="wmtCheck" id="order_abn_signed" name="order_abn_signed" value="Y"	<?php if ($form_data->order_abn_signed) echo "checked" ?> />
												<label class="wmtLabel" style="vertical-align:middle">ABN (Advanced Beneficiary Notice) Signed</label>
											</td>
										</tr>
<?php } ?>
									</table>
									
<?php 
	if ($GLOBALS['lab_corp_psc']) {
?>
									<table style="margin-bottom:5px">
										<tr>
											<td colspan="10">
												<input type="checkbox" class="wmtCheck" id="order_psc" name="order_psc" value="1"	<?php if ($form_data->order_psc || !$GLOBALS['lab_corp_psc']) echo "checked" ?> />
												<label class="wmtLabel" style="vertical-align:middle">Specimen Not Collected [ PSC Hold Order ]</label>
											</td>
										</tr>
										<tr id="sample_data" style="<?php if ($form_data->order_psc) echo "display:none" ?>">
											<td style='width:90px'>
												<label class="wmtLabel">Collection Date: </label>
											</td><td style="white-space:nowrap">
												<input class="wmtInput" type='text' size='10' name='order_date' id='order_date' <?php if ($form_data->order_psc) echo "disabled " ?>
													value='<?php echo $viewmode ? ($form_data->order_datetime == 0)? '' : date('Y-m-d',strtotime($form_data->order_datetime)) : date('Y-m-d'); ?>'
													title='<?php xl('yyyy-mm-dd Date sample taken','e'); ?>'
													onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' />
												<img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22'
													id='img_order_date' border='0' alt='[?]' style='cursor:pointer;cursor:hand;<?php if ($form_data->order_psc) echo "display:none" ?>'
													title='<?php xl('Click here to choose a date','e'); ?>'>
											</td>
											<td style='text-align:right;width:40px'>
												<label class="wmtLabel">Time: </label>
											</td><td>
												<input type="text" class="wmtInput" style="width:65px" name='order_time' id='order_time' <?php if ($form_data->order_psc) echo "disabled " ?>
												value='<?php echo $viewmode ? ($form_data->order_datetime == 0)? '' : date('h:ia',strtotime($form_data->order_datetime)) : date('h:ia'); ?>' />
											</td>
											<td style="padding-left:25px">
												<label class="wmtLabel">Specimen Volume: </label>
											</td><td style="white-space:nowrap">
												<input type="text" class="wmtInput" style="width:65px" name='order_volume' id='order_volume' <?php if ($form_data->order_psc) echo "disabled " ?>
												value='<?php echo $form_data->order_volume; ?>' /> <small>( ml )</small>
											</td>
											<td style="padding-left:25px;white-space:nowrap">
												<label class="wmtLabel" style="vertical-align:middle">Patient Fasting:&nbsp;&nbsp;</label>
												<select name='order_fasting' class='wmtSelect'>
													<?php ListSel($form_data->order_fasting,'LabCorp_Yes_No') ?>
												</select>
											</td>
										</tr>
									</table>
<?php } else { ?>
									<input type='hidden' name='order_psc' value='1' />
									<input type='hidden' name='order_volume' value='' />
									<input type='hidden' id='order_date' name='order_date' value='' />
									<input type='hidden' name='order_time' value='' />
									<input type='hidden' name='order_fasting' value='' />
<?php } // end PSC info?>
									
									<table>
										<tr>
											<td>
												<label class="wmtLabel">Copy Account: </label>
											</td><td style="white-space:nowrap">
												<input type="text" class="wmtInput" style="width:75px" name='copy_acct' id='copy_acct' 
												value="<?php echo $form_data->copy_acct ?>" />
												Attn:
												<input type="text" class="wmtInput" style="width:150px" name='copy_acctname' id='copy_acctname' 
												value="<?php echo $form_data->copy_acctname ?>" />
											</td>
											<td style="padding-left:20px">
												<label class="wmtLabel">Send Fax: </label>
											</td><td style="white-space:nowrap">
												<input type="text" class="wmtInput" style="width:95px" name='copy_fax' id='copy_fax' 
												value="<?php echo $form_data->copy_fax ?>" />
												Attn:
												<input type="text" class="wmtInput" style="width:150px" name='copy_faxname' id='copy_faxname' 
												value="<?php echo $form_data->copy_faxname ?>" />
											</td>
											<td style="padding-left:20px">
												<label class="wmtLabel">Copy Patient: </label>
											</td><td style="white-space:nowrap">
												<input class='wmtCheck' type='checkbox' id="copy_pat" name="copy_pat" value="1" <?php if ($form_data->copy_pat) echo "checked" ?> />
											</td>
										</tr>
									</table>
									
									<br/><hr style="border-color:#eee"/>
									
									<table id="order_table" style="width:100%">
										<tr>
											<th class="wmtHeader" style="width:125px">Actions</th>
											<th class="wmtHeader" style="width:100px">Profile / Test</th>
											<th class="wmtHeader">General Description</th>
											<!-- th class="wmtHeader" style="width:300px">Order Entry Questions</th -->
										</tr>
<?php 
// load the existing requisition codes
$newRow = '';
foreach ($item_list as $item) {
	if (!$item['test_code']) continue;
	$newRow .= "<tr id='test_".$item['test_code']."'>";
	$newRow .= "<td style='vertical-align:top'><input type='button' class='wmtButton' value='remove' style='width:60px' onclick=\"removeTestRow('test_".$item['test_code']."')\" /> ";
	$newRow .= "<input type='button' class='wmtButton' value='details' style='width:60px' onclick=\"testOverview('".$item['test_code']."');return false;\" /></td>\n";
	$newRow .= "<td class='wmtLabel' style='vertical-align:top;padding-top:5px'><input name='test_zseg[]' type='hidden' value='".$item['test_zseg']."'/><input name='test_code[]' class='wmtFullInput test' readonly value='".$item['test_code']."' ";
	if ($item['test_profile'] == '1') { // profile test
		$newRow .= "style='font-weight:bold;color:#c00' /><input type='hidden' name='test_profile[]' value='1' />";
	}
	else {
		$newRow .= "style='font-weight:bold' /><input type='hidden' name='test_profile[]' value='0' />";
	}
	$newRow .= "</td><td colspan='2' class='wmtLabel' style='text-align:right;vertical-align:top;padding-top:5px'><input name='test_text[]' class='wmtFullInput' readonly value='".$item['test_text']."'/>\n";

	// add profile tests if necessary
	if ($item['test_profile'] == '1') {
//		$query = "SELECT lct.test_cd AS component, lct.test_text AS description FROM labcorp_codes lcr ";
//		$query .= "JOIN labcorp_codes lct ON lcr.result_cd = lct.test_cd ";
//		$query .= "WHERE lct.active = 'Y' AND lct.test_type = 'T' AND lcr.active = 'Y' AND lcr.test_cd = '".$item['test_code']."' ";
//		$query .= "ORDER BY lct.test_cd";
//		$result = sqlStatement($query);

		$query = "SELECT result_cd AS component, result_text AS description FROM labcorp_codes ";
		$query .= "WHERE active = 'Y' AND test_cd = ? AND result_loinc NOT LIKE '%INC' AND result_units != '' ";
		$query .= "ORDER BY result_cd ";
		$result = sqlStatement($query,array($item['test_code']));

		while ($record = sqlFetchArray($result)) {
			$profile[$record['component']] = $record['component']." - ".$record['description'];
		}
		
		foreach ($profile AS $record) {
			if ($record) $newRow .= "<input class='wmtFullInput' style='margin-top:5px' readonly value='".$record."'/>\n";
		}
	}
		
	$newRow .= "</td></tr>\n"; // finish up order row
}

// anything found
if ($newRow) {
	echo $newRow;
}
else { // create empty row
?>
										
										<tr id="orderEmptyRow">
											<td colspan="3">
												<b>NO PROFILES / TESTS SELECTED</b>
											</td>
										</tr>
<?php } ?>										
									</table>
									
								</fieldset>
							</td>
						</tr>

						
						<tr>
							<td>
								<fieldset>
									<legend>Order Entry Questions / Notes</legend>
									<table id="aoe_htwttv" zseg="HTWTTV" style="width:100%;<?php if (!$aoe_list['HTWTTV']) echo "display:none" ?>">
										<tr>
											<td style="width:30%;vertical-align:top">
												<table>
													<tr>
														<td class="wmtLabel" style="width:200px;min-width:200px;text-align:right">
															Patient Height: 
														</td>
														<td style="white-space:nowrap">
															<input type="text" class="wmtInput" style="width:65px" name='pat_height' id='pat_height' title="##"
															value="<?php echo $form_data->pat_height ?>" /> <small>( in )</small>
														</td>
													</tr>
												</table>
											</td>
											<td style="width:70%;vertical-align:top">
												<table>
													<tr>
														<td class="wmtLabel" style="width:200px;text-align:right">
															Patient Weight:
														</td>
														<td style="white-space:nowrap">
															<input type="text" class="wmtInput" style="width:65px" name='pat_weight' id='pat_weight' title="###"
															value="<?php echo $form_data->pat_weight ?>"  /> <small>( lb )</small>
														</td>
													</tr>
												</table>
											</td>
										</tr>
										<tr><td colspan=2><hr style="border-color:#eee"/></td></tr>
									</table>
									
									<table id="aoe_source" zseg="SOURCE" style="width:100%;<?php if (!$aoe_list['SOURCE']) echo "display:none" ?>">
										<tr>
											<td style="width:50%;vertical-align:top">
												<table>
													<?php echo doAOE('SOURCE',$aoe_list['SOURCE'])?>
												</table>
											</td>
										</tr>
										<tr><td colspan=2><hr style="border-color:#eee"/></td></tr>
									</table>
									
									<table id="aoe_lcmbld" zseg="LCMBLD" style="width:100%;<?php if (!$aoe_list['LCMBLD']) echo "display:none" ?>">
										<tr>
											<td style="width:50%;vertical-align:top">
												<table>
													<?php echo doAOE('LCMBLD',$aoe_list['LCMBLD'])?>
												</table>
											</td>
										</tr>
										<tr><td colspan=2><hr style="border-color:#eee"/></td></tr>
									</table>
									
									<table id="aoe_afafp" zseg="AFAFP" style="width:100%;<?php if (!$aoe_list['AFAFP']) echo "display:none" ?>">
										<tr>
											<td style="width:50%;vertical-align:top">
												<table>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">GESTATIONAL AGE</span>
														</td>
													</tr>
													<?php echo doAOE('AFAFP',$aoe_list['AFAFP'],'age'); ?>
												</table>
											</td>
											<td style="width:50%;vertical-align:top">
												<table>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">CALCULATION INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('AFAFP',$aoe_list['AFAFP'],'calc'); ?>
												</table>
											</td>
										</tr>
										<tr><td colspan=2><hr style="border-color:#eee"/></td></tr>
									</table>
									
									<table id="aoe_msonly" zseg="MSONLY" style="width:100%;<?php if (!$aoe_list['MSONLY']) echo "display:none" ?>">
										<tr>
											<td style="width:50%;vertical-align:top">
												<table>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">GESTATIONAL AGE</span>
														</td>
													</tr>
													<?php echo doAOE('MSONLY',$aoe_list['MSONLY'],'age'); ?>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">PATIENT INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('MSONLY',$aoe_list['MSONLY'],'patient'); ?>
												</table>
											</td>
											<td style="width:50%;vertical-align:top">
												<table>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">OTHER INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('MSONLY',$aoe_list['MSONLY'],'other'); ?>
												<tr>
														<td colspan="2">
															<span style="padding-left:150px">PRIOR INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('MSONLY',$aoe_list['MSONLY'],'prior'); ?>
												</table>
											</td>
										</tr>
										<tr><td colspan=2><hr style="border-color:#eee"/></td></tr>
									</table>
									
									<table id="aoe_mssnt" zseg="MSSNT" style="width:100%;<?php if (!$aoe_list['MSSNT']) echo "display:none" ?>">
										<tr>
											<td style="width:50%;vertical-align:top">
												<table>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">FETAL INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('MSSNT',$aoe_list['MSSNT'],'fetal'); ?>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">PATIENT INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('MSSNT',$aoe_list['MSSNT'],'patient'); ?>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">OTHER INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('MSSNT',$aoe_list['MSSNT'],'other'); ?>
												</table>
											</td>
											<td style="width:50%;vertical-align:top">
												<table>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">CREDENTIAL INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('MSSNT',$aoe_list['MSSNT'],'creds'); ?>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">PRIOR INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('MSSNT',$aoe_list['MSSNT'],'prior'); ?>
												</table>
											</td>
										</tr>
										<tr><td colspan=2><hr style="border-color:#eee"/></td></tr>
									</table>
									
									<table id="aoe_serin" zseg="SERIN" style="width:100%;<?php if (!$aoe_list['SERIN']) echo "display:none" ?>">
										<tr>
											<td style="width:50%;vertical-align:top">
												<table>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">GESTATIONAL AGE</span>
														</td>
													</tr>
													<?php echo doAOE('SERIN',$aoe_list['SERIN'],'age'); ?>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">PATIENT INFORMAtION</span>
														</td>
													</tr>
													<?php echo doAOE('SERIN',$aoe_list['SERIN'],'patient'); ?>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">OTHER INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('SERIN',$aoe_list['SERIN'],'other'); ?>
												</table>
											</td>
											<td style="width:50%;vertical-align:top">
												<table>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">CALCULATION INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('SERIN',$aoe_list['SERIN'],'calc'); ?>
													<tr>
														<td colspan="2">
															<span style="padding-left:150px">PRIOR INFORMATION</span>
														</td>
													</tr>
													<?php echo doAOE('SERIN',$aoe_list['SERIN'],'prior'); ?>
												</table>
											</td>
										</tr>
										<tr><td colspan=2><hr style="border-color:#eee"/></td></tr>
									</table>
									
									<table id="aoe_pap" zseg="PAP" style="width:100%;<?php if (!$aoe_list['PAP']) echo "display:none" ?>">
										<tr>
											<td style="width:50%;vertical-align:top">
												<span style="padding-left:150px">GYNOLOGICAL BODY SITE</span>
												<table>
													<tr>
														<td>
															<?php echo doAOE('PAP',$aoe_list['PAP'],'bodysite'); ?>
														</td>
													</tr>
												</table>
												<span style="padding-left:150px">COLLECTION INFORMATION</span>
												<table>
													<tr>
														<td>
															<?php echo doAOE('PAP',$aoe_list['PAP'],'collection'); ?>
														</td>
													</tr>
												</table>
												<span style="padding-left:150px">CYTOLOGY INFORMATION</span>
												<table>
													<tr>
														<td>
															<?php echo doAOE('PAP',$aoe_list['PAP'],'cytology'); ?>
														</td>
													</tr>
												</table>
											</td>
											<td style="width:50%;vertical-align:top">
												<span style="padding-left:150px">LMP INFORMATION</span>
												<table>
													<tr>
														<td>
															<?php echo doAOE('PAP',$aoe_list['PAP'],'lmp'); ?>
														</td>
													</tr>
												</table>
												<span style="padding-left:150px">PREVIOUS TREATMENTS</span>
												<table>
													<tr>
														<td>
															<?php echo doAOE('PAP',$aoe_list['PAP'],'previous'); ?>
														</td>
													</tr>
												</table>
												<span style="padding-left:150px">OTHER PATIENT DATA</span>
												<table>
													<tr>
														<td>
															<?php echo doAOE('PAP',$aoe_list['PAP'],'other'); ?>
														</td>
													</tr>
												</table>
											</td>
										</tr>
										<tr><td colspan=2><hr style="border-color:#eee"/></td></tr>
									</table>
									
									
									<table style="width:100%">
										<tr>
											<td>
												<label class="wmtLabel">Lab Notes / Comments:  <small style='font-weight:normal;padding-left:20px'>[ Sent to lab and printed on requisition ]</small></label>
												<textarea id="order_notes" name="order_notes" rows="2" class="wmtFullInput"><?php echo htmlspecialchars($form_data->order_notes) ?></textarea>	
											</td>
										</tr>
									</table>
								</fieldset>
							</td>
						</tr>
						
					</table>
				</div>
			</div>
			<!-- End Review Review -->
			
			<!--  Start of Order Submission -->
			<div class="wmtMainContainer" style="width:99%">
				<div class="wmtCollapseBar" id="InfoCollapseBar" onclick="togglePanel('InfoBox','InfoImageL','InfoImageR','InfoCollapseBar')">
					<table style="width:100%">
						<tr>
							<td style="text-align:left">
								<img id="InfoImageL" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
							<td class="wmtChapter" style="text-align:center">
								Additional Information
							</td>
							<td style="text-align:right">
								<img id="InfoImageR" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
						</tr>
					</table>
				</div>
				
				<div class="wmtCollapseBox" id="InfoBox">
					<table style="width:100%">
						<tr>
							<td style="width:50%">
								<table style="width:100%">
									<tr>
										<td class="wmtLabel" nowrap style="text-align:right">Order Date: </td>
										<td nowrap>
											<input class="wmtInput" type='text' size='10' name='request_date' id='request_date' 
												value='<?php echo $viewmode ? date('Y-m-d',strtotime($form_data->request_datetime)) : date('Y-m-d'); ?>'
												title='<?php xl('yyyy-mm-dd Date of order','e'); ?>'
												onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' />
											<img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22'
												id='img_request_date' border='0' alt='[?]' style='cursor:pointer;cursor:hand'
												title='<?php xl('Click here to choose a date','e'); ?>'>
										</td>
										<td class="wmtLabel" nowrap style="text-align:right">Process Date: </td>
										<td nowrap>
											<input class="wmtInput" readonly style="width:100px" value="<?php echo ($form_data->request_processed > 0)?date('Y-m-d H:i:s',strtotime($form_data->request_processed)):''?>" />
										</td>
									</tr>
									
									<tr>
										<td class="wmtLabel" nowrap style="text-align:right">Physician: </td>
										<td>
											<select class="wmtSelect" name='request_provider' id='request_provider' style="min-width:150px">
												<option value='_blank'>-- select --</option>
<?php 
	$rlist= sqlStatement("SELECT * FROM users WHERE authorized=1 AND active=1 AND npi != '' ORDER BY lname");
	while ($rrow= sqlFetchArray($rlist)) {
    	echo "<option value='" . $rrow['id'] . "'";
		if ($form_data->request_provider == $rrow['id']) echo " selected";
		if (!$form_data->request_provider && $_SESSION['authUser'] == $rrow['username']) echo " selected";
		echo ">" . $rrow['lname'].', '.$rrow['fname'].' '.$rrow['mname'];
    	echo "</option>";
  	}
?>
											</select>
										</td>
										<td class="wmtLabel" nowrap style="text-align:right">Special Handling: </td>
										<td nowrap>
											<select class="wmtSelect" name="request_handling" id="request_handling">
											<?php ListSel($form_data->request_handling, 'LabCorp_Handling') ?>
											</select>
										</td>
									</tr>
									<tr>
										<td class="wmtLabel" nowrap style="text-align:right">Billing Method: </td>
										<td nowrap>
											<select class="wmtSelect" name="request_billing" id="request_billing" style="width:105px">
<?php 
	$bill_option = "";
	if (($form_data->ins_primary && $form_data->ins_primary != 'No Insurance') || 
			($form_data->ins_secondary && $form_data->ins_secondary != 'No Insurance') || 
					$ins_list[0]->company_name || $ins_list[1]->company_name) { // insurance available
		$bill_option .= "<option value='T'";
		if ($form_data->request_billing == 'T') $bill_option .= " selected";
		$bill_option .= ">Third Party</option>\n";
	}
	$bill_option .= "<option value='P'";
	if ($form_data->request_billing == 'P') $bill_option .= " selected";
	$bill_option .= ">Patient Bill</option>\n";
	$bill_option .= "<option value='C'";
	if ($form_data->request_billing == 'C') $bill_option .= " selected";
	$bill_option .= ">Client Bill</option>\n";
	
	echo $bill_option;	
?>
											</select>
										</td>
										<td class="wmtLabel" nowrap style="text-align:right">Billing Account: </td>
										<td nowrap>
											<select class="wmtSelect" name="request_account" id="request_account">
<?php 
	$rlist= sqlStatement("SELECT * FROM list_options WHERE list_id = 'LabCorp_Accounts' ORDER BY seq");
	while ($rrow= sqlFetchArray($rlist)) {
    	echo "<option value='" . $rrow['option_id'] . "'";
		if ($form_data->request_account == $rrow['option_id']) echo " selected";
		if (!$form_data->request_account && $rrow['is_default']) echo " selected";
		echo ">" . $rrow['title'];
    	echo "</option>";
  	}
?>
											</select>
										</td>
									</tr>
								</table>
							</td>
							
							<td>
								<table style="width:100%">
									<tr>
										<td class="wmtLabel" colspan="3">
											Clinic Notes:  <small style='font-weight:normal;padding-left:20px'>[ Not sent to lab or printed on requisition ]</small>
											<textarea name="request_notes" id="request_notes" class="wmtFullInput" rows="4"><?php echo htmlspecialchars($form_data->request_notes) ?></textarea>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div><!-- End of Problem -->
			
<!-- END ENCOUNTER -->

			<br/>

			<!-- Start of Buttons -->
			<table style="width:99%">
<?php // if ($viewmode && $form_data->status != 'i') { ?>
<?php  if (false) { ?>
				<tr>
					<td class="wmtLabel" colspan="4" style="padding-bottom:10px;padding-left:8px">
						Label Printer: 
						<select class="wmtSelect nolock" id="labeler" name="labeler" style="margin-right:10px">
							<?php getLabelers($_SERVER['REMOTE_ADDR'])?>
							<option value='file'>Print To File</option>
						</select>
						Quantity:
						<select class="smtSelect nolock" name="count" style="margin-right:10px">
							<option value="1"> 1 </option>
							<option value="2"> 2 </option>
							<option value="3"> 3 </option>
							<option value="4"> 4 </option>
							<option value="5"> 5 </option>
						</select>

						<input class="nolock" type="button" tabindex="-1" onclick="printLabels(0)" value="Print Labels" />

					</td>
				</tr>
<?php } ?>
				<tr>
<?php if(!$viewmode || $form_data->status == 'i') { ?>
					<td class="wmtLabel" style="vertical-align:top;float:left">
						<a class="css_button" tabindex="-1" href="javascript:saveClicked()"><span>Save Work</span></a>
					</td>
				
					<td class="wmtLabel" style="vertical-align:top;float:left">
						<a class="css_button" tabindex="-1" href="javascript:submitClicked()"><span>Submit Order</span></a>
					</td>
				
<?php } if($viewmode && false) { ?>
					<td class="wmtLabel">
						<a class="css_button" tabindex="-1" href="javascript:openPrint()"><span>Printable Form</span></a>
					</td>
<?php } ?>
<?php if ($form_data->order_abn_id) { ?>
					<td class="wmtLabel">
						<a class="css_button" tabindex="-1" href="<?php echo $document_url . $form_data->order_abn_id ?>"><span>ABN Documents</span></a>
					</td>
<?php } ?>
<?php if ($form_data->order_req_id) { ?>
					<td class="wmtLabel" style="padding-left:150px">
						<a class="css_button" tabindex="-1" href="javascript:printDocument(0)"><span>Print Order Document</span></a>
						<input id='order_req_id' type='hidden' value="<?php echo $form_data->order_req_id ?>" />
						<span style='vertical-align:bottom;line-height:25px'>
						On Printer:
						<select class="wmtSelect nolock" id="printer" name="printer" style="margin-right:10px">
							<?php getPrinters($_SERVER['REMOTE_ADDR'])?>
							<option value='file'>Print To File</option>
						</select>
						</span>
					</td>
<?php } ?>
					<td class="wmtLabel" style="vertical-align:top;float:right">
<?php if(!$viewmode) { ?>
						<a class="css_button" tabindex="-1" href="javascript:doClose()"><span>Don't Save</span></a>
<?php } else { ?>
						<a class="css_button" tabindex="-1" href="javascript:doClose()"><span>Cancel</span></a>
<?php } ?>
					</td>
				</tr>
			</table>
			<!-- End of Buttons -->
			
			<input type="hidden" name="status" value="<?php echo ($form_data->status)?$form_data->status:'i' ?>" />
			<input type="hidden" name="priority" value="<?php echo ($form_data->priority)?$form_data->priority:'n' ?>" />
			
			
			<div id="labcorp_insurance" style="display:none">
				<div class="bgcolor2" style="padding:10px">
					<h3>LabCorp Insurance Identifier Required</h3>
					<p style="white-space:nowrap">Contact LabCorp to obtain the correct insurance identifier(s): &nbsp;</p>
					<table><tr><td style="width:50%">
						<input type="hidden" id="ins_primary_company" value="<?php echo $ins_primary_id ?>" />
<?php 
	if ($ins_primary_id) { // have insurance
		echo $ins_list[0]->company_name."<br/>";
		echo $ins_list[0]->line1."<br/>";
		echo $ins_list[0]->city.",&nbsp;";
		echo $ins_list[0]->state."&nbsp;&nbsp;";
		echo $ins_list[0]->zip;
		if ($ins_primary_labcorp == 'Missing') { 
			echo '<input type="text" class="wmtInput" id="ins_primary_labcorp" />';
		}
		else {
			echo '<input type="hidden" id="ins_primary_labcorp" value="'.$ins_primary_labcorp.'" />';
			echo '<br/>LabCorp: '.$ins_primary_labcorp;
		}
	}
?>
					</td><td>
						<input type="hidden" id="ins_secondary_company" value="<?php echo $ins_secondary_id ?>" />
<?php 
	if ($ins_secondary_id) { // have insurance
		echo $ins_list[1]->company_name."<br/>";
		echo $ins_list[1]->line1."<br/>";
		echo $ins_list[1]->city.",&nbsp;";
		echo $ins_list[1]->state."&nbsp;&nbsp;";
		echo $ins_list[1]->zip;
		if ($ins_secondary_labcorp == 'Missing') { 
			echo '<input type="text" class="wmtInput" id="ins_secondary_labcorp" />';
		}
		else {
			echo '<input type="hidden" id="ins_secondary_labcorp" value="'.$ins_secondary_labcorp.'" />';
			echo '<br/>LabCorp: '.$ins_secondary_labcorp;
		}
	}
?>	
					</td></tr></table>
					<br/>
					<center>
						<input type="button" class="wmtButton" onclick="doInsurance()" value="Save Code" />
						<input type="button" class="wmtButton" onclick="$.fancybox.close()" value="Ignore" />
					</center>
				</div>
			</div>
			
		</form>
		
	</body>

	<script>
		/* required for popup calendar */
		Calendar.setup({inputField:"request_date", ifFormat:"%Y-%m-%d", button:"img_request_date"});
		Calendar.setup({inputField:"order_date", ifFormat:"%Y-%m-%d", button:"img_order_date"});
		Calendar.setup({inputField:"work_date", ifFormat:"%Y-%m-%d", button:"img_work_date"});
	</script>

</html>
