<?php
/** **************************************************************************
 *	LABCORP_ORDER/REPORT.PHP
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
include_once("{$GLOBALS['srcdir']}/sql.inc");
include_once("{$GLOBALS['srcdir']}/api.inc");
include_once("{$GLOBALS['srcdir']}/wmt/wmt.class.php");
include_once("{$GLOBALS['srcdir']}/wmt/wmt.report.php");
include_once("{$GLOBALS['srcdir']}/wmt/wmt.forms.php");
require_once("{$GLOBALS['srcdir']}/wmt/wmt.include.php");

if (!function_exists("labcorp_order_report")) { // prevent redeclarations

function labcorp_order_report($pid, $encounter, $cols, $id) {
	$form_name = 'labcorp_order';
	$form_table = 'form_labcorp_order';
	$item_table = 'form_labcorp_order_item';
	$form_title = 'Laboratory Order';

	// Retrieve form content
	$order_data = new wmtForm('labcorp_order',$id);

	$item_list = array();
	$query = "SELECT  * FROM form_labcorp_order_item WHERE parent_id = '$id' ORDER BY id";
	$result = sqlStatement($query);
	while ($row = sqlFetchArray($result)) {
		$item_list[] = $row;	
	}

	$zseg_list = array();
	$query = "SELECT  * FROM form_labcorp_order_aoe WHERE parent_id = '$id' ORDER BY id";
	$result = sqlStatement($query);
	while ($row = sqlFetchArray($result)) {
		$zseg_list[$row['zseg']] = $row;
	}
	
	// Report outter frame
	print "<link rel='stylesheet' type='text/css' href='../../forms/labcorp_order/style_wmt.css' />";
	print "<div class='wmtReport'>\n";
	print "<table class='wmtFrame'>\n";

	// Status header
	$content = "";
	$status = 'Complete';
	if ($order_data->status == 'i') $status = "Incomplete";
	$content .= "<tr><td colspan='4'>\n";
	$content .= "<table class='wmtStatus' style='margin-bottom:10px'><tr>";
	$content .= "<td class='wmtLabel' style='width:50px;min-width:50px'>Status:</td>";
	$content .= "<td class='wmtOutput'>" . $status . "</td>";
	$content .= "<td class='wmtLabel' style='width:50px;min-width:50px'>Priority:</td>";
	$content .= "<td class='wmtOutput'>Normal</td>\n";
	$content .= "</tr></table></td></tr>\n";
	if ($content) print $content;
	
	// Order summary
	$content = '';
	$processed = ($order_data->request_processed > 0)? date('Y-m-d h:i A',strtotime($order_data->request_processed)): 'PENDING';
	$content .= do_columns(date('Y-m-d h:i A',strtotime($order_data->order_datetime)),'Order Date',$processed,'Processed Date');
	$content .= do_columns($order_data->order_number,'Requisition',ListLook($order_data->request_billing,'LabCorp_Billing'),'Billing Method');
	$content .= do_columns(UserIdLook($order_data->request_provider),'Ordering Provider',$order_data->request_account,'Billing Account');
	$entby = UserLook($order_data->user);
	if ($order_data->request_provider == $order_data->user) $entby = "";
	$content .= do_columns(UserLook($order_data->user),'Entering Clinician',ListLook($order_data->request_handling,'LabCorp_Haandling'));
	$notes = ($order_data->request_notes)? "<div style='white-space:pre-wrap'>".$order_data->request_notes."</div>" : "";
	$content .= do_line($notes,'Clinic Notes');
	do_section($content, 'Order Summary');
	
	// Loop through diagnosis
	$content = "<td style='width:120px'></td><td style='width:40px'></td><td style='width:120px'></td><td></td>\n";
	$dx_count = 1;
	for ($d = 0; $d < 10; $d++) {
		$key = "dx".$d."_code";
		$dx_code = $order_data->$key;
		if ($dx_code) {
			$key = "dx".$d."_text";
			$dx_text = $order_data->$key;

			// Diagnosis section
			$content .= do_columns($dx_code, 'ICD Code',$dx_text, 'Description');
		}
	}	
	do_section($content, 'Order Diagnosis');
	
	// Order specimen
	$content = "<td style='width:120px'></td><td style='width:40px'></td><td style='width:120px'></td><td></td>\n";
	$collected = ($order_data->order_datetime)?date('Y-m-d h:i A',strtotime($order_data->order_datetime)):null;
	$pending = ($order_data->order_pending)?date('Y-m-d h:i A',strtotime($order_data->order_pending)):null;
	
	if ($order_data->order_psc) {
		$content .= do_line('Yes','PSC Hold Order');
	}
	else {
		$content .= do_columns('Yes','Sample Collected',$collected,'Collection Date');
		$content .= do_columns(ListLook($order_data->order_fasting,'LabCorp_Yes_No'),'Patient Fasting',$order_data->order_volume,'Specimen Volume');
	}
	
	$content .= do_break();
	
	// loop through requisitions
	foreach ($item_list as $item_data) {
		$need_blank = false;
		
		// Test section
		$type = ($item_data['test_profile'] == 1)? "Profile Code" : "Test Code";
		$content .= do_columns($item_data['test_code'],$type,$item_data['test_text'], 'Description');

		if ($item_data['test_profile'] == '1') {
			$query = "SELECT distinct(result_cd) AS component, result_text AS description FROM labcorp_codes ";
			$query .= "WHERE active = 'Y' AND test_cd = '".$item_data['test_code']."' AND result_loinc NOT LIKE '%INC' AND result_units != '' ";
			$query .= "ORDER BY result_cd ";
			$result = sqlStatement($query);

			while ($profile = sqlFetchArray($result)) {
				if ($profile['description']) {
					$content .= do_columns("","",$profile['component']." - ".$profile['description'],"Component",true);
					$need_blank = true;
				}
			}
			
		}
	
		/* (NOT FOR LABCORP) add AOE questions if necessary
		for ($a = 0; $a < 20; $a++) {
			if ($item_data[aoe.$a._code]) {
				$aoe_text = $item_data[aoe.$a._text];
				if ($item_data[aoe.$a._list]) $aoe_text = ListLook($aoe_text,$item_data[aoe.$a._list]);
				if ($aoe_text) {
					$aoe = "<td class='wmtLabel' style='width:120px'>&nbsp;</td><td class='wmtLabel' style='width:300px;white-space:nowrap'>".$item_data[aoe.$a._label].": </td>\n";
					$aoe .= "<td class='wmtOutput' style='white-space:nowrap'>".$aoe_text."</td>\n";
					$content .= "<tr>".$aoe."</tr>";
					$need_blank = true;
				}
			}
		}
		*/
		
		if ($need_blank) $content .= do_blank(); // skip first time
	}
	// lab notes
	if ($order_data->order_notes) {
		$content .= do_break();
		$content .= do_line($order_data->order_notes,'Lab Notes');
	}
	
	do_section($content, 'Order Requisition - '.$order_data->order_number);
		

	// AOE questions
	$content = "<td style='width:30%'></td><td style='width:20%'></td><td style='width:30%'></td><td style='width:20%'></td>";
	$aoe_count = 0;
	$zseg = "";
	foreach ($zseg_list AS $aoe_data) {
		if ($zseg != $aoe_data['zseg']) {
			switch ($aoe_data['zseg']) {
				case 'SOURCE': $title = 'SPECIMEN SOURCE'; break;
				case 'PAP': $title = 'GYNECOLOGIC CYTOLOGY'; break;
				case 'AFAFP': $title = 'AMNIOTIC FLUID AFP'; break;
				case 'MSONLY': $title = 'MATERNAL SCREEN ONLY'; break;
				case 'MSSNT': $title = 'MATERNAL SCREEN WITH NT'; break;
				case 'SERIN': $title = 'SERUM INTEGRATED AFP'; break;
				default: $title = 'UNKNOWN SECTION';
			}
			$zseg = $aoe_data['zseg'];
		}
		
		$out_list = array();
		for ($a = 0; $a < 45; $a++) {
			$key = "aoe".$a."_code";
			$aoe_code = $aoe_data[$key];
			if ($aoe_code) {
				$key = "aoe".$a."_label";
				$aoe_label = $aoe_data[$key];
				$key = "aoe".$a."_text";
				$aoe_text = $aoe_data[$key];
				$key = "aoe".$a."_list";
				$aoe_look = $aoe_data[$key];
		
				if ($aoe_text) { // collect all of the output data
					$data = ($aoe_look)? ListLook($aoe_text,$aoe_look): $aoe_text;
					$out_list[] = array($data,$aoe_label);
				}
			}
			
		} // end foreach AOE

		// output the zseg aoe responses
		if (count($out_list) > 0) { // something to output
			$half = round(count($out_list) / 2);
			$y = $half;
			for ($x = 0; $x < $half; $x++) {
				$content .= do_columns($out_list[$x][0], $out_list[$x][1], $out_list[$y][0], $out_list[$y][1]);
				$y++;
			}
			do_section($content, $title);
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	print "</td></tr></table>";
	
} // end declaration 

} // end if function

?>
