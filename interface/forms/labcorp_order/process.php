<?php
/** **************************************************************************
 *	LABCORP_ORDER/PROCESS.PHP
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
error_reporting(E_ALL ^ E_NOTICE);
ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors',1);

require_once("../../globals.php");
require_once("{$GLOBALS['srcdir']}/options.inc.php");
require_once("{$GLOBALS['srcdir']}/lists.inc");
require_once("{$GLOBALS['srcdir']}/wmt/wmt.class.php");
require_once("{$GLOBALS['srcdir']}/wmt/wmt.include.php");
require_once("{$GLOBALS['srcdir']}/labcorp/LabCorpOrderClient.php");
require_once("{$GLOBALS['srcdir']}/labcorp/LabCorpWebClient.php");
require_once("{$GLOBALS['srcdir']}/labcorp/LabCorpModelHL7v2.php");

$document_url = $GLOBALS['web_root']."/controller.php?document&retrieve&patient_id=".$pid."&amp;document_id=";

function getCreds($id) {
	if (!$id) return;
	
	$query = "SELECT * FROM users WHERE id = '".$id."' LIMIT 1";
	$user = sqlQuery($query);
	return $user['npi']."^".$user['lname']."^".$user['fname']."^".$user['mname']."^^^^^NPI";
}

if (!function_exists('getPrinters')) {
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
} // end if exists

// get a handle to processor
$client = new LabCorpOrderClient();

// get a new lab object
$request = new Message_HL7v2();

// add request information
$request->request_number = $order_data->order_number; // ORC.02, OBR.02
$request->request_siteid = $order_data->labcorp_siteid;

$request->pid = $order_data->pid; // PID.02
$request->pubpid = ($order_data->pubpid && $order_data->pid != $order_data->pubpid)? $order_data->pubpid: ''; // PID.04

$request->name = $order_data->pat_last . "^";
$request->name .= $order_data->pat_first . "^"; // PID.05
$request->name .= $order_data->pat_middle;

$request->dob = date('Ymd',strtotime($order_data->pat_DOB)); // PID.07
$request->age = $order_data->pat_age;
$request->sex = substr($order_data->pat_sex, 0, 1); // PID.08
$request->ethnicity = ListLook($order_data->pat_ethnicity,'LabCorp_Ethnicity'); // PID.22
if (!$request->ethnicity) $request->ethnicity = 'U';
$request->race = ListLook($order_data->pat_race,'LabCorp_Race'); // PID.10
if (!$request->race) $request->race = 'X';
if ($request->ethnicity == 'H') $request->race = 'H'; // old dumb stuff!!
$request->ss = $order_data->pat_ss; // PID.19

$request->address = $order_data->pat_street . "^^"; // PID.11
$request->address .= $order_data->pat_city . "^";
$request->address .= $order_data->pat_state . "^";
$request->address .= $order_data->pat_zip;

$request->phone = preg_replace("/[^0-9,.]/", "", $order_data->pat_phone); // PID.13

$request->account = $order_data->request_account; // PID.18.1
$request->bill_type = $order_data->request_billing; // PID.18.4
$request->abn_signed = ($order_data->order_abn_signed)? 'Y': ''; // PID.18.5

$request->datetime = date('YmdHis',strtotime($order_data->request_datetime)); // MSH.07

$request->pat_weight = "";
if ($order_data->pat_weight && is_numeric($order_data->pat_weight)) {
	$value = intval($order_data->pat_weight);
	$request->pat_weight = sprintf('%03s',$value);
}
$request->pat_height = "";
if ($order_data->pat_height && is_numeric($order_data->pat_height)) {
	$value = intval($order_data->pat_height);
	$request->pat_height = sprintf('%03s',$value);
}
$request->order_volume = "";
if ($order_data->order_volume && is_numeric($order_data->order_volume)) {
	$value = intval($order_data->order_volume);
	$request->order_volume = sprintf('%04s',$value);
}
$request->fasting = $order_data->order_fasting;
$sample_datetime = ""; // default no sample taken

$request->facility = ""; // PSC or blank
if ($order_data->order_psc) {
	$request->facility = "PSC"; // PSC or blank
}
else {
	$sample = $order_data->order_datetime;
	$sample_datetime = ($sample)? date('YmdHis',strtotime($sample)) : ''; // OBR.07
}

$request->copy_pat = $order_data->copy_pat;
$request->copy_acct = $order_data->copy_acct;
$request->copy_acctname = ($order_data->copy_acct)? $order_data->copy_acctname: '';
$request->copy_fax = $order_data->copy_fax;
$request->copy_faxname = ($order_data->copy_fax)? $order_data->copy_faxname: '';

$request->verified_id = getCreds($_SESSION['authId']); // ORC.11
$request->provider_id = getCreds($order_data->request_provider); // ORC.12

$request->order_notes = $order_data->order_notes; // PID - NTE

// add guarantor
$request->guarantor = $order_data->guarantor_last . "^";
$request->guarantor .= $order_data->guarantor_first . "^"; // PID.05
$request->guarantor .= $order_data->guarantor_middle;
$request->guarantor_phone = preg_replace("/[^0-9,.]/", "", $order_data->guarantor_phone);
$request->guarantor_relation = '3'; // assume other
if ($order_data->guarantor_relation == 'Self') $request->guarantor_relation = '1'; 
if ($order_data->guarantor_relation == 'Spouse') $request->guarantor_relation = '2';

$request->guarantor_address = $order_data->guarantor_street . "^^"; // PID.11
$request->guarantor_address .= $order_data->guarantor_city . "^";
$request->guarantor_address .= $order_data->guarantor_state . "^";
$request->guarantor_address .= $order_data->guarantor_zip;

if ($order_data->work_flag) { // workers comp claim
	$request->bill_type = 'T';
	$request->guarantor_relation = "3"; // workers comp always 1-self
	$request->guarantor_employer = $order_data->work_employer;

	// build workers comp insurance record
	$ins = new Insurance_HL7v2();
			
	$ins->set_id = 1; // IN1.01 - sequence
	$ins->plan = "Workers Comp"; // IN1.08
	$ins->group = ""; // IN1.08
	$ins->work_flag = "Y"; // IN1.31
	$ins->policy = $order_data->work_case; // IN1.36
					
	$ins->subscriber = $ins_data->pat_last . "^";
	$ins->subscriber .= $ins_data->pat_first . "^"; // IN1.16
	$ins->subscriber .= $ins_data->pat_middle;
				
	$ins->relation = 1; // IN1.17
	
	$ins->address = $ins_data->subscriber_street . "^^"; // IN1.19
	$ins->address .= $ins_data->subscriber_city . "^";
	$ins->address .= $ins_data->subscriber_state . "^";
	$ins->address .= $ins_data->subscriber_postal_code;
				
	$ins->company_id = $work_insurance;
	$ins_company = wmtInsurance::getCompany($ins->company_id);
	
	$ins->labcorp_id = ListLook($ins->company_id, 'LabCorp_Insurance');
	if (! $ins->labcorp_id) $ins->labcorp_id = $work_insurance;

	$ins->company_name = $ins_company['company_name']; // IN1.04
	$ins->company_address = $ins_company['line1'] . "^"; // IN1.05
	$ins->company_address .= $ins_company['line2'] . "^";
	$ins->company_address .= $ins_company['city'] . "^";
	$ins->company_address .= $ins_company['state'] . "^";
	$ins->company_address .= $ins_company['zip'];
			
	// create hl7 segment
	$client->addInsurance($ins);
}
else { // normal insurance
	// create insurance records
	$ins_primary_type = 0; // self insured
	if ( $request->bill_type != 'C' && ($order_data->ins_primary == 'Self Insured' || $order_data->ins_primary == 'No Insurance') ) 
		$request->bill_type = 'P'; // if not client bill and no insurance must be patient bill
	
	if ($request->bill_type == 'T' ) { // only add insurance for third-party bill
	//	$ins_list = array('ins_primary','ins_secondary','ins_tertiary');
		$ins_list = array('ins_primary','ins_secondary'); // supports 2 only!!
		
		$seq = 0;
		foreach ($ins_list AS $ins_key) {
			// process insurance
			if ($order_data->$ins_key) { // found insurance
				// retrieve insurance information
				$key = $ins_key."_id";
				$ins_data = new wmtInsurance($order_data->$key);
				if ($ins_key == "ins_primary") $ins_primary_type = $ins_data->plan_type; // save for ABN check
			
				// build insurance record
				$ins = new Insurance_HL7v2();
				
				$seq++;
				$ins->set_id = $seq; // IN1.01 - sequence
				$ins->plan = $ins_data->plan_name; // IN1.08
				$ins->group = $ins_data->group_number; // IN1.08
				$ins->policy = $ins_data->policy_number; // IN1.36
				$ins->work_flag = "N";
						
				$ins->subscriber = $ins_data->subscriber_lname . "^";
				$ins->subscriber .= $ins_data->subscriber_fname . "^"; // IN1.16
				$ins->subscriber .= $ins_data->subscriber_mname;
					
				$relation = 3; // other
				if ($ins_data->subscriber_relationship == 'self') $relation = 1;
				if ($ins_data->subscriber_relationship == 'spouse') $relation = 2;
				$ins->relation = $relation; // IN1.17
		
				$ins->address = $ins_data->subscriber_street . "^^"; // IN1.19
				$ins->address .= $ins_data->subscriber_city . "^";
				$ins->address .= $ins_data->subscriber_state . "^";
				$ins->address .= $ins_data->subscriber_postal_code;
					
				$ins->company_id = $ins_data->company_id;
				$ins->labcorp_id = ListLook($ins_data->company_id, 'LabCorp_Insurance');
				if (! $ins->labcorp_id) $ins->labcorp_id = $ins_data->company_id;
				$ins->company_name = $ins_data->company_name; // IN1.04
				$ins->company_address = $ins_data->line1 . "^"; // IN1.05
				$ins->company_address .= $ins_data->line2 . "^";
				$ins->company_address .= $ins_data->city . "^";
				$ins->company_address .= $ins_data->state . "^";
				$ins->company_address .= $ins_data->zip;
				
				// create hl7 segment
				$client->addInsurance($ins);
			}
		}
	}
}

// store diagnosis codes
$dx_count = 1;
$diag_codes = array();
for ($d = 0; $d < 10; $d++) {
	$key = "dx".$d."_code";
	$dx_code = $order_data->$key;
	if ($dx_code) {
		$key = "dx".$d."_text";
		$dx_text = $order_data->$key;
		$dx_data = new Diagnosis_HL7v2();
		$dx_data->set_id = $dx_count++;
		$dx_data->diagnosis_code = mysql_real_escape_string($dx_code);
		$dx_data->diagnosis_text = mysql_real_escape_string($dx_text);
		$request->diagnosis[] = $dx_data;
		$diag_codes[] = $dx_code;
	}
}

// validate aoe responses (loop)
$aoe_errors = "";
$seq = 1;
$zseg_list = array(); // list of zseg_aoe objects by zseg
foreach ($aoe_list as $aoe_data) {
	$zseg_data = array();
	$zseg_aoe = array(); // aoe responses for a single zseg
	$query = "SELECT field, question, answers, section FROM labcorp_aoe ";
	$query .= "WHERE active = 'Y' AND zseg = '".$aoe_data->zseg."' ";
	$result = sqlStatement($query);
	
	while ($data = sqlFetchArray($result)) {
		$zseg_data[$data['field']] = $data;

		if ($data['field'] == 'PID10' && !$request->race) {
			$aoe_errors .= "\nPatient race required (update demographics)";
		}
		if (!$order_data->order_psc) { // only require if NOT psc hold order
			if (($data['field'] == 'ZCI3.1' || $data['field'] == 'ZCI3.2') && !$request->order_volume) {
				$aoe_errors .= "\nSpecimen volume required for these tests";
			}
			if ($data['field'] == 'ZCI4' && !$request->fasting) {
				$aoe_errors .= "\nPatient fasting response required for these tests";
			}
		}
	}

	$aoe_count = 1;
	for ($a = 0; $a < 45; $a++) {
		$key = "aoe".$a."_code";
		$aoe_code = $aoe_data->$key;
		if ($aoe_code) {
			$key = "aoe".$a."_label";
			$aoe_label = $aoe_data->$key;
			$key = "aoe".$a."_text";
			$aoe_text = $aoe_data->$key;
			$key = "aoe".$a."_list";
			$aoe_look = $aoe_data->$key;
				
			$zseg_answers = $zseg_data[$aoe_code]['answers'];
			$zseg_section = $zseg_data[$aoe_code]['section'];
			
			if ($aoe_text) { // if there is something to validate
				if ($zseg_answers == 'YYYYMMDD') {
					if (strtotime($aoe_text) !== false) {
						$aoe_text = date('Ymd',strtotime($aoe_text));
					}
					else {
						$aoe_errors .= "\n$aoe_label requires a valid date or must be left empty";
					}
				}
				
				if ($zseg_answers == 'N^Y' && ($aoe_text != 'Y' && $aoe_text != 'N')) { // check response data for validity
					$aoe_errors .= "\n$aoe_label requires a Y/N response or must be left empty";
				}
				
				if ($zseg_answers == '1^2^9' && preg_match('/1|2|9/',$aoe_text) === FALSE) {
					$aoe_errors .= "\n$aoe_label must contain a number 1, 2 or 9 or must be left empty";
				}
			
				if ($zseg_answers == '0^1^2^3^4^5^6' && preg_match('/0|1|2|3|4|5|6/',$aoe_text) === FALSE) {
					$aoe_errors .= "\n$aoe_label must contain a number between 1 and 6 or must be left empty";
				}
			
				if ($zseg_answers == '1^2^3^4^5^6^7^8^9' && preg_match('/1|2|3|4|5|6|7|8|9/',$aoe_text) === FALSE) {
					$aoe_errors .= "\n$aoe_label must contain a number between 1 and 9 or must be left empty";
				}
			
				if ($zseg_answers == 'A^B^C^H^I^O^X' && preg_match('/A|B|C|H|I|O|X/',$aoe_text) === FALSE) {
					$aoe_errors .= "\n$aoe_label must contain a valid race code or must be left empty";
				}
			
				if ($zseg_answers == '##' || $zseg_answers == '###' || $zseg_answers == '####') {
					if (is_numeric($aoe_text) === FALSE) {
						$aoe_errors .= "\n$aoe_label requires a numeric value ($zseg_answers) or must be left empty";
					}
					else {
						$num = intval($aoe_text);

						if ($zseg_answers == '##')
							if ($num > 99) {
								$aoe_errors .= "\n$aoe_label requires a value between 1 and 99 or must be left empty";
							}
							else {
								$aoe_text = sprintf('%02d',$num); // make right justified zero filled
							}
						
						if ($zseg_answers == '###')
							if ($num > 999) {
								$aoe_errors .= "\n$aoe_label requires a value between 1 and 999 or must be left empty";
							}
							else {
								$aoe_text = sprintf('%03d',$num); // make right justified zero filled
							}
										
						if ($zseg_answers == '####')
							if ($num > 9999) {
								$aoe_errors .= "\n$aoe_label requires a value between 1 and 9999 or must be left empty";
							}
							else {
								$aoe_text = sprintf('%04d',$num); // make right justified zero filled
							}
					}
				}
			
				if ($zseg_answers == '##.#') {
					if (is_numeric($aoe_text) === FALSE) {
						$aoe_errors .= "\n$aoe_label requires a numeric value ($zseg_answers) or must be left empty";
					}
					else {
						$num = floatval($aoe_text);

						if ($num > 99.9) {
							$aoe_errors .= "\n$aoe_label requires a value between 1.0 and 99.9 or must be left empty";
						}
						else {
							$aoe_text = sprintf('%02.1f',$num); // make right justified zero filled
						}
					}
				}
			}
			
			// store non-standard values
			if ($aoe_code == 'ZCI1') $request_pat_height = $aoe_text;
			if ($aoe_code == 'ZCI2.1') $request->pat_weight = $aoe_text;
			
			$aoe = new Aoe_HL7v2();
			$aoe->set_id = $aoe_count++;
			$aoe->observation_code = $aoe_code;
			$aoe->observation_label = mysql_real_escape_string($aoe_label);
			$aoe->observation_text = mysql_real_escape_string($aoe_text);
			$aoe->section = mysql_real_escape_string($zseg_section);
			$aoe->display_label = $aoe_label;
			if ($aoe_look) { // needs translation
				$aoe->display_text = ListLook($aoe_text, $aoe_look);
			}
			elseif ($zseg_answers == 'YYYYMMDD') {
				if (strtotime($aoe_text) !== false) {
					$aoe->display_text = date('m/d/Y',strtotime($aoe_text));
				}
			}
			else {
				$aoe->display_text = $aoe_text;
			} 
			$zseg_aoe[] = $aoe; // responses for a single zseg
		}
	}
	
	$zseg_list[$aoe_data->zseg] = $zseg_aoe; // response sets by zseg
}

$reload_url = $rootdir.'/patient_file/encounter/view_form.php?formname=labcorp_order&id=';
?>
	
	<form method='post' action="" id="order_process" name="order_process" > 
		<table class="bgcolor2" style="width:100%;height:100%">
			<tr>
				<td colspan="4">
					<h2 style="padding-bottom:0;margin-bottom:0">Order Processing</h2>
				</td>
			</tr>
			<tr>
				<td colspan="4" style="padding-bottom:20px">
<?php 
if ($aoe_errors) { // oh well .. have to terminate process with errors
	echo "The following errors must be corrected before submitting:";
	echo "<pre>\n";
	echo $aoe_errors;
?>
				</td>
			</tr><tr>
				<td style="text-align:right">
					<input type="button" class="wmtButton" onclick="doReturn('<?php echo $form_id ?>')" value="close" />
				</td>
			</tr>
		</table>
	</form>
<?php
	exit; 
}

// create orders (loop)
$test_list = array();
$test_codes = array();
foreach ($item_list as $item_data) {
	$order = new Order_HL7v2();

	$order->set_id = $seq++; // OBR.01 - sequence
	$order->request_control = "NW"; // ORC.01 - CDC defined O119
	$order->request_number = $order_data->order_number; // ORC.02
	$order->request_provider = $order_data->request_provider;
	$order->test_code = $item_data->test_code;
	$order->test_text = preg_replace('/\s+\[.*\]/','',$item_data->test_text); // strip specimen text from title
	$order->zseg = $item_data->test_zseg;
	$order->specimen_datetime = $sample_datetime; // OBR.07

	$test_code = $item_data->test_code;
	$test_zseg = $item_data->test_zseg;
		
	$order->aoe = $zseg_list[$test_zseg];

	$client->addOrder($order);
	$test_list[] = $order;
	$test_codes[] = $test_code;
}

echo "<pre>\n";
$client->buildRequest($request);

$abn_needed = false;
if ($ins_primary_type == 2 && !$order_data->work_flag) { // medicare but not workers comp
	// get a handle to abn client
	$abn_client = new LabCorpWebClient();
	$abn_data = $abn_client->getAbnDocument($order_data,$diag_codes,$test_codes);
	if ($abn_data) {
		$order_data->order_abn_id = $abn_data->get_id();
		if (!$order_data->order_abn_signed) {
			echo "\n\nThis order requires a Medicare 'Advance Beneficiary Notice of Noncoverage'";
			echo "\nPlease print the ABN document and obtain the patient's signature.";
			echo "\nThen resubmit this order with the ABN SIGNED checkbox marked.\n\n\n";	
			$abn_needed = true;		
		}
	}
}
	
if (!$order_data->order_abn_id || $order_data->order_abn_signed) { // only submit if ABN not necessary or signed

	// generate requisition
	$doc_data = $client->getOrderDocument($order_data,$test_list,$zseg_list);

	if ($doc_data) { // got a document so suceess
		// DO THE SUBMIT !!
		$client->submitOrder($order_data);
	
		// never gets here if there is a processing error
		$order_data->status = 'p'; // processed
		$order_data->order_req_id = $doc_data->get_id();
		$order_data->request_processed = date('Y-m-d H:i:s');
		$order_data->update();	
	}
	else {
		die("FATAL ERROR: failed to generate requisition document!!");
	}
}
?>
					</pre>
				</td>
			</tr>
<?php 
//if ($doc_data) { // if no documents order failed
if (false) { // if no documents order failed
?>
			<tr>
				<td class="wmtLabel" colspan="2" style="padding-bottom:10px;padding-left:8px">
					Order Printer: 
					<select class="nolock" id="labeler" name="labeler" style="margin-right:10px">
						<?php getLabelers($_SERVER['REMOTE_ADDR'])?>
						<option value='file'>Print To File</option>
					</select>
					Quantity:
					<select class="nolock" name="count" style="margin-right:10px">
						<option value="1"> 1 </option>
						<option value="2"> 2 </option>
						<option value="3"> 3 </option>
						<option value="4"> 4 </option>
						<option value="5"> 5 </option>
					</select>

					<input class="nolock" type="button" tabindex="-1" onclick="printLabels(1)" value="Print Labels" />
				</td>
			</tr>
<?php 
} // end of failed test
?>				
			<tr>
<?php if ($order_data->order_abn_id) { ?>
				<td>
					<input type="button" class="wmtButton" onclick="location.href='<?php echo $document_url . $order_data->order_abn_id ?>';return false" value="ABN print" />
				</td>
<?php } ?>				
<?php if ($order_data->order_req_id) { ?>
				<td class="wmtLabel" style="text-align:left;width:400px;white-space:nowrap">
					<a class="css_button" tabindex="-1" href="javascript:printDocument(1)"><span>Print Order Document</span></a>
					<input id='order_req_id' type='hidden' value="<?php echo $order_data->order_req_id ?>" />
					<span style='vertical-align:bottom;line-height:25px'>
					On Printer:
					<select class="wmtSelect nolock" id="printer" name="printer" style="margin-right:10px">
						<?php getPrinters($_SERVER['REMOTE_ADDR'])?>
						<option value='file'>Print To File</option>
					</select>
					</span>
				</td>
<?php } ?>
				<td style="text-align:right;white-space:nowrap;width:150px">
<?php if (!$abn_needed) { ?>
					<input type="button" class="wmtButton" onclick="doClose()" value="close" />
<?php } ?>
					<input type="button" class="wmtButton" onclick="doReturn(<?php echo $form_id ?>)" value="return" />
				</td>
			</tr>
		</table>
	</form>