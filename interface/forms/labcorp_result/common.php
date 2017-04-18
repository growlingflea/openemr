<?php
/** **************************************************************************
 *	LABCORP_RESULT/COMMON.PHP
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
 *  @subpackage result
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

/* INITIALIZE FORM DEFAULTS */
$id = '';
if ($viewmode) $id = $_GET['id'];
$pid = ($pid)? $pid : $_SESSION['pid'];
$encounter = ($encounter)? $encounter : $_SESSION['encounter'];

$result_title = 'LabCorp Results';
$result_name = 'labcorp_result';
$item_name = 'labcorp_result_item';
$lab_name = 'labcorp_result_lab';
$order_name = 'labcorp_order';

$save_url = $rootdir.'/forms/'.$result_name.'/save.php';
$print_url = $rootdir.'/forms/'.$result_name.'/print.php?id='.$id;
$abort_url = $rootdir.'/patient_file/summary/demographics.php';
$document_url = $webroot.'/controller.php?document&retrieve&patient_id='.$pid.'&document_id=';
$cancel_url = $rootdir.'/patient_file/encounter/encounter_top.php';
if ($mode == 'single') $cancel_url = "javascript:window.close()";

/* RETRIEVE FORM DATA */
try {
	$result_data = new wmtForm($result_name,$id);
	$order_data = new wmtForm($order_name,$result_data->request_id);
	$pat_data = wmtPatient::getPidPatient($pid);
}
catch (Exception $e) {
	print "FATAL ERROR ENCOUNTERED: ";
	print $e->getMessage();
	exit;
}

// result items for the order
$result_list = array();
if ($result_data->id) {
	$query = "SELECT id FROM form_labcorp_result_item WHERE parent_id = '$result_data->id' ORDER BY sequence";
	$result = sqlStatement($query);
	while ($row = sqlFetchArray($result)) {
		$result_list[] = new wmtForm($item_name,$row['id']); // retrieve the data
	}
}

// reporting labs for the order
$lab_list = array();
if ($result_data->id) {
	$query = "SELECT id FROM form_labcorp_result_lab WHERE parent_id = '$result_data->id' ORDER BY code";
	$result = sqlStatement($query);
	while ($row = sqlFetchArray($result)) {
		$lab_list[] = new wmtForm($lab_name,$row['id']); // retrieve the data
	}
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
?>
<!DOCTYPE HTML>
<html>
	<head>
		<?php html_header_show();?>
		<title><?php echo $result_title; ?></title>

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
		<!-- script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/wmt/wmtstandard.js"></script -->
		
		<!-- pop up calendar -->
		<style type="text/css">@import url(<?php echo $GLOBALS['webroot'] ?>/library/dynarch_calendar.css);</style>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dynarch_calendar.js"></script>
		<?php include_once("{$GLOBALS['srcdir']}/dynarch_calendar_en.inc.php"); ?>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dynarch_calendar_setup.js"></script>
	
<style>
@font-face {
    font-family: 'VeraSansMono';
    src: url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/VeraMono-webfont.eot');
    src: url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/VeraMono-webfont.eot?#iefix') format('embedded-opentype'),
         url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/VeraMono-webfont.woff') format('woff'),
         url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/VeraMono-webfont.ttf') format('truetype'),
         url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/VeraMono-webfont.svg#BitstreamVeraSansMonoRoman') format('svg');
    font-weight: normal;
    font-style: normal;
}

@font-face {
    font-family: 'DroidSansMono';
    src: url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/DroidSansMono-webfont.eot');
    src: url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/DroidSansMono-webfont.eot?#iefix') format('embedded-opentype'),
         url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/DroidSansMono-webfont.woff') format('woff'),
         url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/DroidSansMono-webfont.ttf') format('truetype'),
         url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/DroidSansMono-webfont.svg#BitstreamDroidSansMono') format('svg');
    font-weight: normal;
    font-style: normal;
}
.mono { font-family: DroidSansMono, VeraSansMono, courier }

@font-face {
    font-family: 'VeraSansMonoBold';
    src: url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/VeraMono-Bold-webfont.eot');
    src: url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/VeraMono-Bold-webfont.eot?#iefix') format('embedded-opentype'),
         url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/VeraMono-Bold-webfont.woff') format('woff'),
         url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/VeraMono-Bold-webfont.ttf') format('truetype'),
         url('<?php echo $GLOBALS['webroot'] ?>/library/labcorp/fonts/VeraMono-Bold-webfont.svg#BitstreamVeraSansMonoBold') format('svg');
    font-weight: normal;
    font-style: normal;
}
.monoBold { font-family: DroidSansMonoBold, Arial, sans-serif }

.Calendar tbody .day { border: 1px solid inherit; }

.wmtMainContainer table { font-size: 12px; }
.wmtMainContainer fieldset { margin-top: 0; }

.css_button_small { background: transparent url( '../../../images/bg_button_a_small.gif' ) no-repeat scroll top right; }
.css_button_small span { background: transparent url( '../../../images/bg_button_span_small.gif' ) no-repeat; }
.css_button { background: transparent url( '../../../images/bg_button_a.gif' ) no-repeat scroll top right; }
.css_button span { background: transparent url( '../../../images/bg_button_span.gif' ) no-repeat; }
</style>

		<script language="JavaScript">
			var mypcc = '<?php echo $GLOBALS['phone_country_code'] ?>';

			// validate data and submit form
			function saveClicked() {
				reviewed = $('#reviewed_id').val();
				reviewed_date = $('#reviewed_date').val();
				notified = $('#notified_id').val();
				notified_date = $('#notified_date').val();

				if ((reviewed != '_blank' && reviewed_date == '') || (reviewed == '_blank' && reviewed_date != '')) {
					alert("Both reviewed date and reviewed by required!!");
					return;
				}
				
				if (reviewed == '_blank' && notified != '_blank') {
					alert("Patient should not be notified until results reviewed!!\n\nPlease select the reviewing physician.");
					return;
				}

				if ((notified != '_blank' && notified_date == '') || (notified == '_blank' && notified_date != '')) {
					alert("Both notification date and notified by required!!");
					return;
				}
				
				person = $('#notified_person').val();
				if (notified != '_blank' && person == '') {
					alert("Please indicate who was notified of the results.");
					return;
				}
				
				<?php if (!$mode == 'single') { ?>top.restoreSession(); <?php } ?>
				var f = document.forms[0];
				f.submit();
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
			
 			// setup jquery processes
			$(document).ready(function(){
				$("#<?php echo $result_name; ?> :input").attr("disabled", true);
				$(".nolock").attr("disabled", false);
			});
				
			
		</script>
	</head>

	<body class="body_top">

		<!-- Required for the popup date selectors -->
		<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
		
		<form method='post' action="<?php echo $save_url ?>" id='<?php echo $result_name; ?>' name='<?php echo $result_name; ?>' > 
			<input class="nolock" type='hidden' name='process' id='process' value='' />
			<div class="wmtTitle">
<?php if ($viewmode) { ?>
				<input class="nolock" type=hidden name='mode' value='<?php echo $mode ?>' />
				<input class="nolock" type=hidden name='id' value='<?php echo $_GET["id"] ?>' />
				<span class=title><?php echo $result_title; ?></span>
<?php } else { ?>
				<input class="nolock" type='hidden' name='mode' value='new' />
				<span class='title'>New <?php echo $result_title; ?></span>
<?php } ?>
			</div>

<!-- BEGIN RESULT -->
			<!-- Client Information -->
			<div class="wmtMainContainer" style="width:99%">
				<div class="wmtCollapseBar" id="ReviewCollapseBar" onclick="togglePanel('ReviewBox','ReviewImageL','ReviewImageR','ReviewCollapseBar')">
					<table width="100%" border="0" cellspacing="0" cellpadding="0">	
						<tr>
							<td>
								<img id="ReviewImageL" align="left" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
							<td class="wmtChapter" style="text-align: center">
								Patient Information</span>
							</td>
							<td style="text-align: right">
								<img id="ReviewImageR" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
						</tr>
					</table>
				</div>
				
				<div class="wmtCollapseBox" id="ReviewBox">
					<table width="100%"	border="0" cellspacing="0" cellpadding="0">
						<tr>
							<!-- Left Side -->
							<td style="width:50%" class="wmtInnerLeft">
								<table width="99%">
							        <tr>
										<td style="width:20%" class="wmtLabel">
											Patient First
											<input name="pat_first" type="text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_first:$pat_data->fname; ?>">
										</td>
										<td style="width:10%" class="wmtLabel">
											Middle
											<input name="pat_middle" type"text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_middle:$pat_data->mname; ?>">
										</td>
										<td class="wmtLabel">
											Last Name
											<input name="pat_last" type"text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_last:$pat_data->lname; ?>">
										</td>
										<td style="width:20%" class="wmtLabel">
											Patient Id
											<input name="pat_pid" type="text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->request_pubpid:$pat_data->pubpid; ?>">
										</td>
										<td colspan="2" style="width:20%" class="wmtLabel">
											Social Security
											<input name="pat_ss" type"text" class="wmtFullInput" readonly value="<?php echo $pat_data->ss ?>">
										</td>
									</tr>

									<tr>
										<td colspan="3" class="wmtLabel">Email Address<input name="pat_email" type="text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_email:$pat_data->email; ?>"></td>
										<td style="width:20%" class="wmtLabel">
											Birth Date
											<input name="pat_DOB" type="text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_DOB:$pat_data->birth_date; ?>">
										</td>
										<td style="width:5%" class="wmtLabel">
											Age
											<input name="pat_age" type"text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_age:$pat_data->age; ?>">
										</td>
										<td style="width:15%" class="wmtLabel">
											Gender
											<input name="pat_sex" type"text" class="wmtFullInput" readonly value="<?php echo ListLook(($order_data->id)?$order_data->pat_sex:$pat_data->sex, 'sex') ?>">
										</td>
									</tr>
									
									<tr>
										<td colspan="3" class="wmtLabel">
											Primary Address
											<input name="pat_street" type="text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_street:$pat_data->street; ?>"></td>
										<td class="wmtLabel">Mobile Phone<input name="pat_mobile" id="ex_phone_mobile" type="text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_mobile:$pat_data->phone_cell; ?>"></td>
										<td colspan="2" class="wmtLabel">Home Phone<input name="pat_phone" type="text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_phone:$pat_data->phone_home; ?>"></td>
									</tr>

									<tr>
										<td colspan="3" class="wmtLabel" style="width:50%">
											City
											<input name="pat_city" type="text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_city:$pat_data->city; ?>">
										</td>
										<td class="wmtLabel">
											State
											<input type="text" class="wmtFullInput" readonly value="<?php echo ListLook(($order_data->id)?$order_data->pat_state:$pat_data->state, 'state'); ?>">
										</td>
										<td colspan="2" class="wmtLabel">
											Postal Code
											<input name="pat_zip" type="text" class="wmtFullInput" readonly value="<?php echo ($order_data->id)?$order_data->pat_zip:$pat_data->postal_code; ?>">
										</td>
									</tr>
								</table>
							</td>
							
							<!-- Right Side -->
							<td style="width:50%" class="wmtInnerRight">
								<table width="99%" border="0" cellspacing="0" cellpadding="1">
									<tr>
										<td style="width:20%" class="wmtLabel">
											Insured First
											<input name="ins_first" type="text" class="wmtFullInput" readonly value="<?php echo $order_data->ins_first; ?>">
										</td>
										<td style="width:10%" class="wmtLabel">
											Middle
											<input name="ins_middle" type"text" class="wmtFullInput" readonly value="<?php echo $order_data->ins_middle; ?>">
										</td>
										<td class="wmtLabel">
											Last Name
											<input name="ins_last" type"text" class="wmtFullInput" readonly value="<?php echo $order_data->ins_last; ?>">
										</td>
										<td style="width:20%" class="wmtLabel">
											Birth Date
											<input name="ins_DOB" type="text" class="wmtFullInput" readonly value="<?php echo $order_data->ins_DOB; ?>">
										</td>
										<td style="width:20%" class="wmtLabel">
											Relationship
											<input name="ins_relation" type="text" class="wmtFullInput" readonly value="<?php echo ListLook($order_data->ins_relation, 'sub_relation'); ?>">
										</td>
									</tr>
									<tr>
										<td colspan="3" class="wmtLabel">
											Primary Insurance
											<input name="ins_primary" type="text" class="wmtFullInput" readonly value="<?php echo $order_data->ins_primary ?>">
										</td>
										<td class="wmtLabel">Policy #<input name="ins_primary_policy" type="text" class="wmtFullInput" readonly value="<?php echo $order_data->ins_primary_policy; ?>"></td>
										<td class="wmtLabel">Group #<input name="ins_primary_group" type="text" class="wmtFullInput" readonly value="<?php echo $order_data->ins_primary_group; ?>"></td>
									</tr>
									<tr>
										<td colspan="3" class="wmtLabel">
											Secondary Insurance
											<input name="ins_secondary" type="text" class="wmtFullInput" readonly value="<?php echo $order_data->secondary; ?>">
										</td>
										<td class="wmtLabel">Policy #<input name="ins_secondary_policy" type="text" class="wmtFullInput" readonly value="<?php echo $order_data->secondary_policy; ?>"></td>
										<td class="wmtLabel">Group #<input name="ins_secondary_group" type="text" class="wmtFullInput" readonly value="<?php echo $order_data->secondary_group; ?>"></td>
									</tr>
									<tr>
										<td style="width:20%" class="wmtLabel">
											Guarantor First
											<input name="guarantor_first" type="text" class="wmtFullInput" readonly value="<?php echo $order_data->guarantor_first ?>">
										</td>
										<td style="width:10%" class="wmtLabel">
											Middle
											<input name="guarantor_middle" type"text" class="wmtFullInput" readonly value="<?php echo $order_data->guarantor_middle; ?>">
										</td>
										<td style="width:20%" class="wmtLabel">
											Last Name
											<input name="guarantor_last" type"text" class="wmtFullInput" readonly value="<?php echo $order_data->guarantor_last; ?>">
										</td>
										<td class="wmtLabel">SS#<input name="guarantor_ss" type="text" class="wmtFullInput" readonly value="<?php echo $order_data->guarantor_ss; ?>"></td>
										<td class="wmtLabel">
											Relationship
											<input name="guarantor_relation" type="text" class="wmtFullInput" readonly value="<?php echo ListLook($order_data->guarantor_relation, 'sub_relation'); ?>">
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
			<!-- End Information Review -->
								
			<!--  Start of Results -->
			<div class="wmtMainContainer" style="width:99%">
				<div class="wmtCollapseBar" id="ResultCollapseBar" onclick="togglePanel('ResultBox','ResultImageL','ResultImageR','ResultCollapseBar')">
					<table width="100%" border="0" cellspacing="0" cellpadding="0">	
						<tr>
							<td>
								<img id="ResultImageL" align="left" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
							<td class="wmtChapter" style="text-align: center">
								Order Results
							</td>
							<td style="text-align: right">
								<img id="ResultImageR" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
						</tr>
					</table>
				</div>
				
				<div class="wmtCollapseBox" id="ResultBox">
					<table width="100%"	border="0" cellspacing="2" cellpadding="0">
						<tr>
							<td>
								<fieldset>
									<legend>Diagnosis Codes</legend>
									<table id="codeTable" width="100%" border="0" cellspacing="0" cellpadding="2">
										<tr>
											<th class="wmtHeader" style="width:80px">ICD9 Code</th>
											<th class="wmtHeader">Description</th>
										</tr>

<?php 
// load the existing diagnosis codes
$newRow = '';
for ($d = 0; $d < 10; $d++) {
	$codekey = "dx".$d."_code";
	$textkey = "dx".$d."_text";
	if (!$order_data->$codekey) continue;
	$id = str_replace('.', '_', $order_data->$codekey);
	
	// add new row
	$newRow .= "<tr id='code_".$id."'>";
	$newRow .= "<td class='wmtLabel'><input name='dx_code[]' class='wmtFullInput code' style='font-weight:bold' readonly value='".$order_data->$codekey."'/>\n";
	$newRow .= "</td><td class='wmtLabel'><input name='dx_text[]' class='wmtFullInput name' readonly value='".$order_data->$textkey."'/>\n";
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
												<b>NO DIAGNOSIS CODES PROVIDED</b>
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
									<legend>Observation Results - <?php echo $result_data->request_order ?></legend>
									<table id="sample_table" border="0" cellspacing="0" cellpadding="2">
										<tr>
											<td style="padding-bottom:10px">
												<label class="wmtLabel" style="vertical-align:middle">Specimen:</label>
											</td><td style="padding-bottom:10px" colspan='6'>
												<input type="text" class="wmtInput" readonly style="width:220px" value="<?php echo $result_data->lab_number?>" />
											</td>
										</tr>
										<tr>
											<td>
												<label class="wmtLabel">Collected Date: </label>
											</td><td>
												<input class="wmtInput" type='text' size='10' readonly value='<?php echo ($result_data->specimen_datetime == 0)? '' : date('Y-m-d',strtotime($result_data->specimen_datetime)); ?>' />
											</td>
											<td style='text-align:right'>
												<label class="wmtLabel">Time: </label>
											</td><td colspan='4'>
												<input type="input" class="wmtInput" style="width:65px" readonly value='<?php echo ($result_data->specimen_datetime == 0)? '' : date('h:ia',strtotime($result_data->specimen_datetime)); ?>' />
											</td>
										</tr>
										<tr>
											<td>
												<label class="wmtLabel">Received Date: </label>
											</td><td>
												<input class="wmtInput" type='text' size='10' readonly value='<?php echo ($result_data->received_datetime == 0)? '' : date('Y-m-d',strtotime($result_data->received_datetime)); ?>' />
											</td>
											<td style='text-align:right'>
												<label class="wmtLabel">Time: </label>
											</td><td colspan='4'>
												<input type="input" class="wmtInput" style="width:65px" readonly value='<?php echo ($result_data->received_datetime == 0)? '' : date('h:ia',strtotime($result_data->received_datetime)); ?>' />
											</td>
										</tr>
										<tr>
											<td style='width:100px'>
												<label class="wmtLabel">Reported Date: </label>
											</td><td style='width:100px'>
												<input class="wmtInput" type='text' size='10' readonly value='<?php echo ($result_data->result_datetime == 0)? '' : date('Y-m-d',strtotime($result_data->result_datetime)); ?>' />
											</td>
											<td style='text-align:right;width:60px'>
												<label class="wmtLabel">Time: </label>
											</td><td>
												<input type="input" class="wmtInput" style="width:65px" readonly value='<?php echo ($result_data->result_datetime == 0)? '' : date('h:ia',strtotime($result_data->result_datetime)); ?>' />
											</td>
											<td style='text-align:right;width:80px'>
												<label class="wmtLabel">Status: </label>
											</td><td style='width:100px'>
												<input type="input" class="wmtInput" style="width:100px" readonly value='<?php echo ($result_data->lab_status == 'F')? 'FINAL' : 'INCOMPLETE' ?>' />
											</td>
											<td style='width:40%'></td>
										</tr>
<?php if ($result_data->lab_notes) { ?>
										<tr>
											<td style='width:100px;vertical-align:top'>
												<label class="wmtLabel">Lab Comments: </label>
											</td><td colspan=6>
												<textarea class="wmtFullInput" readonly rows=2><?php echo $result_data->lab_notes ?></textarea>
											</td>
										</tr>
<?php } ?>
									</table>

									<br/>
									<hr style="border-color:#eee"/>
									
									<table id="result_table" style="min-width:900px;width:90%;padding:10px">
<?php
// initialize indicators
$last_code = "FIRST";

// loop through all of the results
foreach ($result_list as $result) {
		if ($last_code != $result->test_code && $last_code != $result->parent_code && !$result->test_type) { // changed test code
?>
										<tr>
											<td colspan="8" class="wmtLabel" style="text-align:left;font-size:1.1em">
												<?php if ($last_code != "FIRST") echo "<br/><br/>" ?>
												<?php echo $result->test_code ?> - <?php echo $result->test_text ?>
											</td>
										</tr>
										<tr style="font-size:9px;font-weight:bold">
											<td style="min-width:30px">&nbsp;</td>
											<td style="min-width:30px">&nbsp;</td>
											<td style="min-width:30px">&nbsp;</td>
											<td style="text-align:center;width:20%">
												RESULT
											</td>
											<td style="text-align:center;width:15%">
												FLAG
											</td>
											<td style="text-align:center;width:15%">
												UNITS
											</td>
											<td style="text-align:center;width:15%">
												REFERENCE
											</td>
											<td style="text-align:center;width:10%">
												LAB
											</td>
											<td></td>
										</tr>
<?php 
			$last_code = $result->test_code;
		}
	
		$abnormal = $result->observation_abnormal; // in case they sneak in a new status
		if ($result->observation_abnormal == 'H') $abnormal = 'High';
		if ($result->observation_abnormal == 'L') $abnormal = 'Low';
		if ($result->observation_abnormal == 'HH') $abnormal = 'Alert High';
		if ($result->observation_abnormal == 'LL') $abnormal = 'Alert Low';
		if ($result->observation_abnormal == '>') $abnormal = 'Panic High';
		if ($result->observation_abnormal == '<') $abnormal = 'Panic Low';
		if ($result->observation_abnormal == 'A') $abnormal = 'Abnormal';
		if ($result->observation_abnormal == 'AA') $abnormal = 'Critical';
		if ($result->observation_abnormal == 'S') $abnormal = 'Susceptible';
		if ($result->observation_abnormal == 'R') $abnormal = 'Resistant';
		if ($result->observation_abnormal == 'I') $abnormal = 'Intermediate';
		if ($result->observation_abnormal == 'NEG') $abnormal = 'Negative';
		if ($result->observation_abnormal == 'POS') $abnormal = 'Positive';

		$facilities[$result->producer_id] = $result->producer_id; // store lab identifier (only once)
?>
										<tr <?php if ($abnormal) echo 'style="font-weight:bold;color:#bb0000"'?>>
											<td>&nbsp;</td>
											<td colspan="2" class="wmtLabel" style="text-align:left;width:20%">
												<?php if ($result->observation_label != ".") echo $result->observation_label ?>
											</td>
<?php 
		if ($result->observation_value) { // there is an observation
			if ($result->observation_type == 'TX') { // put TEXT on next line
?>
										</tr><tr <?php if ($abnormal) echo 'style="font-weight:bold;color:#bb0000"'?>>
											<td colspan="3"></td>
<?php 
			}
?>
											<td style="font-family:monospace;text-align:<?php echo ($result->observation_type == 'ST' || $result->observation_type == 'TX')?"left":"center" ?>">
												<?php if ($result->observation_value != ".") echo $result->observation_value ?>
											</td>
											<td style="font-family:monospace;text-align:center;width:15%">
												<?php echo $abnormal ?>
											</td>
											<td style="font-family:monospace;text-align:center;width:15%">
												<?php echo $result->observation_units ?>
											</td>
											<td style="font-family:monospace;text-align:center;width:20%">
												<?php echo $result->observation_range ?>
											</td>
											<td style="font-family:monospace;text-align:center;width:10%">
												<?php echo $result->producer_id ?>
											</td>
											<td></td>
										</tr>
<?php
			if ($result->observation_notes) { // put comments below test line
?>
										<tr <?php if ($abnormal) echo 'style="font-weight:bold;color:#bb0000"'?>>
											<td colspan="3">&nbsp;</td>
											<td colspan="5" style="text-align:left">
												<pre style="margin:0"><?php echo $result->observation_notes ?></pre>
											</td>
											<td></td>
										</tr>
<?php 
			} // end if notes
		} 
		else { // put comments on same line as test
?>
											<td colspan="4" style="text-align:left">
												<pre style="margin:0"><?php echo $result->observation_notes ?></pre>
											</td>
											<td style="font-family:monospace;text-align:center;width:10%">
												<?php echo $result->producer_id ?>
											</td>
											<td></td>
										</tr>
<?php
		} // end if values
	} // end result foreach
?>
									</table>

									<br/>
									<hr style="border-color:#eee"/>
									
									<table style="width:100%;padding:0 10px">
<?php 
	// loop through all of the labs
	$first = true;
	foreach ($lab_list AS $lab) {
?>
										<tr>
											<td style="width:40px;padding-left:30px">
												<b><?php echo $lab->code ?></b>
											</td>
											<td style="width:400px">
												<?php echo $lab->name ?>
											</td>
											<td style="width:255px">
												Director: <?php echo $lab->director ?>
											</td>
										</tr>
										<tr>
											<td>&nbsp;</td>
											<td>
<?php 
		echo $lab->street.", ";
		if ($lab->street2) echo $lab->street2.", ";
		echo $lab->city.", ";
		echo $lab->state." ";
		echo $lab->zip
?>
											</td>
											<td>&nbsp;</td>
										</tr>
										<tr>
											<td>&nbsp;</td>
											<td colspan="2">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
												<b>For inquiries, please contact the lab at: <?php echo $lab->phone ?></b> 
											</td>
										</tr>
<?php
	} // end foreach lab 
?>
									</table>
								</fieldset>
							</td>
						</tr>
					</table>
					
				</div>
			</div>
			<!-- End Review -->
			
			<!--  Start of Review Submission -->
			<div class="wmtMainContainer" style="width:99%">
				<div class="wmtCollapseBar" id="InfoCollapseBar" onclick="togglePanel('InfoBox','InfoImageL','InfoImageR','InfoCollapseBar')">
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td style="text-align:left">
								<img id="InfoImageL" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
							<td class="wmtChapter" style="text-align:center">
								Review Information
							</td>
							<td style="text-align:right">
								<img id="InfoImageR" src="<?php echo $webroot;?>/library/wmt/fill-090.png" border="0" alt="Show/Hide" title="Show/Hide" />
							</td>
						</tr>
					</table>
				</div>
				
				<div class="wmtCollapseBox" id="InfoBox">
					<table width="100%"	border="0" cellspacing="2" cellpadding="0">
						<tr>
							<td style="width:50%">
								<table style="width:100%">
									<tr>
										<td class="wmtLabel" nowrap style="text-align:right">Ordered Date: </td>
										<td nowrap>
											<input class="wmtInput" type='text' size='10' name='original_date' id='original_date' 
												value='<?php echo (strtotime($order_data->request_datetime))? date('Y-m-d',strtotime($order_data->request_datetime)) : ''; ?>'
												title='<?php xl('yyyy-mm-dd Original order date','e'); ?>' readonly />
										</td>

										<td class="wmtLabel" nowrap style="text-align:right">Ordered By: </td>
										<td>
<?php 
	$name = 'UNKNOWN';
	$provider= sqlQuery("SELECT * FROM users WHERE id = ?",array($order_data->provider));
	if ($provider) $name = $provider['lname'].", ".$provider['fname'];
?>
											<input class="wmtInput" name='original_by' id='original_by' style="min-width:200px" 
												value='<?php echo $name ?>' readonly />
										</td>
									</tr>

								<tr>
										<td class="wmtLabel" nowrap style="text-align:right">Reviewed Date: </td>
										<td nowrap>
											<input class="wmtInput nolock" type='text' size='10' name='reviewed_date' id='reviewed_date' 
												value='<?php echo ($result_data->reviewed_datetime > 0)? date('Y-m-d',strtotime($result_data->reviewed_datetime)) : ''; ?>'
												title='<?php xl('yyyy-mm-dd Date results reviewed','e'); ?>'
												onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' />
											<img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22' 
												id='img_reviewed_date' border='0' alt='[?]' style='cursor:pointer;cursor:hand'
												title='<?php xl('Click here to choose a date','e'); ?>'>
										</td>

										<td class="wmtLabel" nowrap style="text-align:right">Reviewed By: </td>
										<td>
											<select class="wmtInput nolock" name='reviewed_id' id='reviewed_id' style="min-width:200px" onchange="$('#reviewed_date').val('<?php echo date('Y-m-d') ?>')">
												<option value='_blank'>-- select --</option>
<?php 
	$rlist= sqlStatement("SELECT * FROM users WHERE authorized=1 AND active=1 AND npi != '' ORDER BY lname");
	while ($rrow= sqlFetchArray($rlist)) {
    	echo "<option value='" . $rrow['id'] . "'";
		if ($result_data->reviewed_id == $rrow['id']) echo " selected";
		echo ">" . $rrow['lname'].', '.$rrow['fname'].' '.$rrow['mname'];
    	echo "</option>";
  	}
?>
											</select>
										</td>
									</tr>

									<tr>
										<td class="wmtLabel" nowrap style="text-align:right">Notified Date: </td>
										<td nowrap>
											<input class="wmtInput nolock" type='text' size='10' name='notified_date' id='notified_date' 
												value='<?php echo ($result_data->notified_datetime > 0) ? date('Y-m-d',strtotime($result_data->notified_datetime)) : ''; ?>'
												title='<?php xl('yyyy-mm-dd Date patient notified','e'); ?>'
												onkeyup='datekeyup(this,mypcc)' onblur='dateblur(this,mypcc)' />
											<img src='../../pic/show_calendar.gif' align='absbottom' width='24' height='22'
												id='img_notified_date' border='0' alt='[?]' style='cursor:pointer;cursor:hand'
												title='<?php xl('Click here to choose a date','e'); ?>'>
										</td>

										<td class="wmtLabel" nowrap style="text-align:right">Notified By: </td>
										<td>
											<select class="wmtInput nolock" name='notified_id' id='notified_id' style="min-width:200px" onchange="$('#notified_date').val('<?php echo date('Y-m-d') ?>')">
												<option value='_blank'>-- select --</option>
<?php 
	$rlist= sqlStatement("SELECT * FROM users WHERE active=1 AND facility_id > 0 ORDER BY lname");
	while ($rrow= sqlFetchArray($rlist)) {
    	echo "<option value='" . $rrow['id'] . "'";
		if ($result_data->notified_id == $rrow['id']) echo " selected";
		echo ">" . $rrow['lname'].', '.$rrow['fname'].' '.$rrow['mname'];
    	echo "</option>";
  	}
?>
											</select>
										</td>
									</tr>
									
									<tr>
										<td class="wmtLabel" nowrap style="text-align:right">Person Notified: </td>
										<td colspan='3' nowrap>
											<input class="wmtFullInput nolock" id="notified_person" name="notified_person" style="width:70%" value="<?php echo $result_data->notified_person ?>" />
										</td>
									</tr>
									
									<tr>
										<td class="wmtLabel" nowrap style="text-align:right">Special Handling: </td>
										<td colspan='3' nowrap>
											<select class="wmtInput nolock" name='result_handling' id='result_handling' style="min-width:200px" >
												<?php echo ListSel($result_data->result_handling, 'LabCorp_Handling'); ?>
											</select>
										</td>
									</tr>
									
								</table>
							</td>
							
							<td>
								<table style="width:100%">
									<tr>
										<td class="wmtLabel" colspan="3">
											Clinical Notes: 
											<textarea name="result_notes" id="result_notes" class="wmtFullInput nolock" rows="4"><?php echo htmlspecialchars($result_data->result_notes) ?></textarea>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div><!-- End of Problem -->
			
<!-- END ENCOUNTER -->

			</br>

			<!-- Start of Buttons -->
			<table width="99%" border="0">
				<tr>
					<td class="wmtLabel" style="vertical-align:top;float:left">
						<a class="css_button" tabindex="-1" href="javascript:saveClicked()"><span>Save Work</span></a>
					</td>

<?php /*
					<td class="wmtLabel" width="100px">
						<a class="css_button" tabindex="-1" href="javascript:openPrint()"><span>Printable Form</span></a>
					</td>
*/ ?>
<?php if ($result_data->document_id) { ?>
					<td class="wmtLabel" width="100px">
						<a class="css_button" tabindex="-1" href="<?php echo $document_url . $result_data->document_id ?>"><span>Lab Document</span></a>
					</td>
<?php } ?>
					<td class="wmtLabel" style="vertical-align:top;float:right">
						<a class="css_button" tabindex="-1" href="javascript:doClose()"><span>Cancel</span></a>
					</td>
				</tr>
			</table>
			<!-- End of Buttons -->
			
			<input type="hidden" name="status" value="<?php echo ($result_data->status)?$result_data->status:'i' ?>" />
			<input type="hidden" name="priority" value="<?php echo ($result_data->priority)?$result_data->priority:'n' ?>" />
		</form>
	</body>

	<script language="javascript">
		/* required for popup calendar */
		Calendar.setup({inputField:"reviewed_date", ifFormat:"%Y-%m-%d", button:"img_reviewed_date"});
		Calendar.setup({inputField:"notified_date", ifFormat:"%Y-%m-%d", button:"img_notified_date"});
	</script>

</html>
