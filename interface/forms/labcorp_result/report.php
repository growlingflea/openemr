<?php
/** **************************************************************************
 *	LABCORP_RESULT/REPORT.PHP
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
include_once("{$GLOBALS['srcdir']}/sql.inc");
include_once("{$GLOBALS['srcdir']}/api.inc");
include_once("{$GLOBALS['srcdir']}/wmt/wmt.include.php");
include_once("{$GLOBALS['srcdir']}/wmt/wmt.class.php");
include_once("{$GLOBALS['srcdir']}/wmt/wmt.report.php");
include_once("{$GLOBALS['srcdir']}/wmt/wmt.forms.php");

if (!function_exists("labcorp_result_report")) { // prevent redeclarations

function labcorp_result_report($pid, $encounter, $cols, $id) {
	$form_name = 'labcorp_result';
	$form_table = 'form_labcorp_result';
	$order_name = 'labcorp_order';
	$order_table = 'form_labcorp_order';
	$item_name = 'labcorp_result_item';
	$item_table = 'form_labcorp_result_item';
	$lab_name = 'labcorp_result_lab';
	$lab_table = 'form_labcorp_result_lab';
	$form_title = 'LabCorp Results';

	// Retrieve form content
	try {
		$result_data = new wmtForm($form_name,$id);
		$order_data = new wmtForm($order_name,$result_data->request_id);
	}
	catch (Exception $e) {
		print "THERE WAS AN ERROR RETRIEVING THESE RECORDS";
		exit;
	}
	
	$result_list = array();
	$query = "SELECT  * FROM form_labcorp_result_item WHERE parent_id = '$id' AND observation_value != 'DNR' ORDER BY id";
	$result = sqlStatement($query);
	while ($row = sqlFetchArray($result)) {
		$result_list[] = new wmtForm($item_name,$row['id']); // retrieve the data
	}

	$lab_list = array();
	$query = "SELECT  * FROM form_labcorp_result_lab WHERE parent_id = '$id' ORDER BY code";
	$result = sqlStatement($query);
	while ($row = sqlFetchArray($result)) {
		$lab_list[] = new wmtForm($lab_name,$row['id']); // retrieve the data
	}

	// Report outter frame
	print "<link rel='stylesheet' type='text/css' href='../../forms/labcorp_order/style_wmt.css' />";
	print "<div class='wmtReport'>\n";
	print <<<EOT
<style>
@font-face {
    font-family: 'VeraSansMono';
    src: url('{$GLOBALS['webroot']}/library/labcorp/fonts/VeraMono-webfont.eot');
    src: url('{$GLOBALS['webroot']}/library/labcorp/fonts/VeraMono-webfont.eot?#iefix') format('embedded-opentype'),
         url('{$GLOBALS['webroot']}/library/labcorp/fonts/VeraMono-webfont.woff') format('woff'),
         url('{$GLOBALS['webroot']}/library/labcorp/fonts/VeraMono-webfont.ttf') format('truetype'),
         url('{$GLOBALS['webroot']}/library/labcorp/fonts/VeraMono-webfont.svg#BitstreamVeraSansMonoRoman') format('svg');
    font-weight: normal;
    font-style: normal;
}
.mono { font-family: VeraSansMono, Arial, sans-serif }

@font-face {
    font-family: 'VeraSansMonoBold';
    src: url('{$GLOBALS['webroot']}/library/labcorp/fonts/VeraMono-Bold-webfont.eot');
    src: url('{$GLOBALS['webroot']}/library/labcorp/fonts/VeraMono-Bold-webfont.eot?#iefix') format('embedded-opentype'),
         url('{$GLOBALS['webroot']}/library/labcorp/fonts/VeraMono-Bold-webfont.woff') format('woff'),
         url('{$GLOBALS['webroot']}/library/labcorp/fonts/VeraMono-Bold-webfont.ttf') format('truetype'),
         url('{$GLOBALS['webroot']}/library/labcorp/fonts/VeraMono-Bold-webfont.svg#BitstreamVeraSansMonoBold') format('svg');
    font-weight: normal;
    font-style: normal;
}
.monoBold { font-family: VeraSansMonoBold, Arial, sans-serif }
</style>
EOT;
	print "<table class='wmtFrame' cellspacing='0' cellpadding='3'>\n";

	// Status header
	$content = "";
	$status = ($result_data->lab_status == 'F')?'Final':'Partial';

	$content .= "<tr><td colspan='4'>\n";
	$content .= "<table class='wmtStatus' style='margin-bottom:10px'><tr>";
	$content .= "<td class='wmtLabel' style='width:50px;min-width:50px'>Status:</td>";
	$content .= "<td class='wmtOutput'>" . $status . "</td>";
	$content .= "<td class='wmtLabel' style='width:50px;min-width:50px'>Priority:</td>";
	$content .= "<td class='wmtOutput'>Normal</td>\n";
	$content .= "</tr></table></td></tr>\n";

	if ($content) print $content;
	
	// Result summary
	$content = '';
	$content .= do_columns($result_data->lab_number,'Specimen Number', date('Y-m-d h:i A',strtotime($result_data->specimen_datetime)),"Collection Date");
	$content .= do_columns($result_data->request_order,'Requisition Number',date('Y-m-d h:i A',strtotime($result_data->received_datetime)),'Received Date');
	$content .= do_columns(UserIdLook($order_data->request_provider),'Ordering Provider',date('Y-m-d h:i A',strtotime($result_data->result_datetime)),'Result Date');
	$entby = UserLook($order_data->user);
	if ($order_data->request_provider == $order_data->user) $entby = "";
	$content .= do_columns(UserLook($order_data->user),'Entering Clinician',$status.' Report','Lab Status');
	$notes = ($order_data->request_notes)? "<div style='white-space:pre-wrap'>".$order_data->request_notes."</div>" : "";
	$content .= do_line($notes,'Clinic Notes');
	do_section($content, 'Result Summary');
	
	// Review summary
	$content = '';
	if ($result_data->reviewed_id) {
		$content = do_columns(UserIdLook($result_data->reviewed_id),'Reviewing Provider',date('Y-m-d',strtotime($result_data->reviewed_datetime)),'Reviewed Date');
	}
	if ($result_data->notified_id) {
		$content .= do_columns(UserIdLook($result_data->notified_id),'Notification By',date('Y-m-d',strtotime($result_data->notified_datetime)),'Notified Date');
		$content .= do_columns($result_data->notified_person, 'Person Notified',ListLook($result_data->result_handling, 'Lab_Special_Handling'), 'Special Handling');
	}
	elseif ($result_data->result_handling) {
		$notified .= do_columns('BLANK','',ListLook($result_data->result_handling, 'Lab_Special_Handling'), 'Special Handling');
	}
	if ($result_data->notes) {
		$content .= do_line("<div style='white-space:pre-wrap'>$result_data->result_notes</div>",'Result Notes');
	}
	do_section($content, 'Review Information');
	
?>	
	<tr>
		<td>
			<div class='wmtSection'>
				<div class='wmtSectionTitle'>
					Lab Results - <?php echo $result_data->request_order ?>
				</div>
				<div class='wmtSectionBody'>
		
					<table class="wmtOutput" style="width:100%">

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
							<td style="text-align:center;width:15%">
								RESULT
							</td>
							<td style="text-align:center;width:15%">
								FLAG
							</td>
							<td style="text-align:center;width:15%">
								UNITS
							</td>
							<td style="text-align:center;width:20%">
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
					<hr/>
					<table style="width:100%;padding:0 10px;font-size:0.8em">
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
				</div>
			</div>
		</td>
	</tr>
</table>
</div>
<?php 	
} // end declaration 

} // end if function
?>
