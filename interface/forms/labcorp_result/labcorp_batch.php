<?php
/** **************************************************************************
 *	LABCORP_RESULTS/BATCH.PHP
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
 *  @subpackage results
 *  @version 1.0
 *  @copyright Williams Medical Technologies, Inc.
 *  @author Ron Criswell <info@keyfocusmedia.com>
 * 
 *************************************************************************** */
error_reporting(E_ALL ^ E_NOTICE);
ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors',1);

$ignoreAuth=true; // signon not required!!

// ENVIRONMENT SETUP
if (defined('STDIN')) {
	parse_str(implode('&', array_slice($argv, 1)), $_GET);
}
 
$BROWSER = ($_POST['browser']) ? $_POST['browser'] : FALSE; // never allow browser from command line
$DEBUG = ($_POST['form_debug']) ? $_POST['form_debug'] : $_GET['debug'];
$FROM = ($_POST['form_from_date']) ? $_POST['form_from_date'] : $_GET['from'];
$THRU = ($_POST['form_to_date']) ? $_POST['form_to_date'] : $_GET['thru'];
$SITE = ($_SESSION['site_id']) ? $_SESSION['site_id'] : $_GET['site'];

$here = dirname(dirname(dirname(__FILE__)));

require_once($here."/globals.php");
require_once("{$GLOBALS['srcdir']}/options.inc.php");
require_once("{$GLOBALS['srcdir']}/lists.inc");
require_once("{$GLOBALS['srcdir']}/forms.inc");
include_once("{$GLOBALS['srcdir']}/pnotes.inc");
require_once("{$GLOBALS['srcdir']}/wmt/wmt.class.php");
require_once("{$GLOBALS['srcdir']}/wmt/wmt.include.php");
require_once("{$GLOBALS['srcdir']}/labcorp/LabCorpResultClient.php");
require_once("{$GLOBALS['srcdir']}/labcorp/LabCorpObservation.php");
require_once("{$GLOBALS['srcdir']}/labcorp/LabCorpModelHL7v2.php");
require_once("{$GLOBALS['srcdir']}/labcorp/LabCorpParserHL7v2.php");
require_once("{$GLOBALS['srcdir']}/classes/Document.class.php");

$LABCORP_PID = FALSE;
$LABCORP_ID = FALSE;

// GET DEFAULT SITE ID
$query = "SELECT title FROM list_options ";
$query .= "WHERE list_id = 'Quest_Site_Identifiers' AND is_default = 1 LIMIT 1";
if ($dummy = sqlQuery($query)) $GLOBALS['lab_labcorp_siteid'] = $dummy['title'];	
	
// GET LABCORP DUMMY PATIENT
$query = "SELECT pid FROM patient_data WHERE lname = '#LABCORP#' LIMIT 1";
if ($dummy = sqlQuery($query)) $LABCORP_PID = $dummy['pid'];

// GET LABCORP DUMMY PROVIDER
$query = "SELECT id FROM users WHERE username = 'labcorp' LIMIT 1";
if ($dummy = sqlQuery($query)) $LABCORP_ID = $dummy['id'];

// VALIDATE INSTALL
$invalid = "";
if (!$LABCORP_PID) $invalid .= "Missing LabCorp Patient Record\n";
if (!$LABCORP_ID) $invalid .= "Missing LABCORP User/Provider Record\n";
if (!$GLOBALS["lab_corp_enable"]) $invalid .= "LabCorp Interface Not Enabled\n";
if (!$GLOBALS["lab_corp_catid"] > 0) $invalid .= "No LabCorp Document Category\n";
if (!$GLOBALS["lab_corp_facilityid"]) $invalid .= "No Receiving Facility Identifier\n";
//if (!$GLOBALS["lab_corp_siteid"]) $invalid .= "No Sending Clinic Site Identifier\n";
if (!$GLOBALS["lab_corp_username"]) $invalid .= "No LabCorp Username\n";
if (!$GLOBALS["lab_corp_password"]) $invalid .= "No LabCorp Password\n";
if (!file_exists("{$GLOBALS["OE_SITE_DIR"]}/lab")) $invalid .= "No Lab Work Directory\n";
if (!file_exists("{$GLOBALS["OE_SITE_DIR"]}/lab/backup")) $invalid .= "No Lab Backup Directory\n";
if (!file_exists("{$GLOBALS["srcdir"]}/wmt")) $invalid .= "Missing WMT Library\n";
if (!file_exists("{$GLOBALS["srcdir"]}/labcorp")) $invalid .= "Missing LabCorp Library\n";
if (!file_exists("{$GLOBALS["srcdir"]}/tcpdf")) $invalid .= "Missing TCPDF Library\n";
if (!file_exists("{$GLOBALS["srcdir"]}/phpseclib")) $invalid .= "Missing PHPSECLIB Library\n";
//if (!extension_loaded("curl")) $invalid .= "CURL Module Not Enabled\n";
if (!extension_loaded("xml")) $invalid .= "XML Module Not Enabled\n";
//if (!extension_loaded("sockets")) $invalid .= "SOCKETS Module Not Enabled\n";
//if (!extension_loaded("soap")) $invalid .= "SOAP Module Not Enabled\n";
if (!extension_loaded("openssl")) $invalid .= "OPENSSL Module Not Enabled\n";

if ($invalid) { ?>
<html><head></head><body>
<h1>LabCorp Interface Not Available</h1>
The interface is not enabled, not properly configured, or required components are missing!!
<br/><br/>
For assistance with implementing this service contact:
<br/><br/>
<a href="http://www.williamsmedtech.com/page4/page4.html" target="_blank"><b>Williams Medical Technologies Support</b></a>
<br/><br/>
<table style="border:2px solid red;padding:20px"><tr><td style="white-space:pre;color:red"><h3>DEBUG OUTPUT</h3><?php echo $invalid ?></td></tr></table>
</body></html>
<?php
exit; 
}

// special pnote insert function
function labPnote($pid, $newtext, $assigned_to = '', $datetime = '') {
	$message_sender = 'labcorp';
	$message_group = 'Default';
	$authorized = '0';
	$activity = '1';
	$title = 'LabCorp';
	$message_status = 'New';
	if (empty($datetime)) $datetime = date('Y-m-d H:i:s');

	// notify doctor or doctor's nurse?
	$notify = ListLook($assigned_to, 'Lab_Notification');
	if (!$notify) $notify = $assigned_to;
	
	$body = date('Y-m-d H:i') . ' (LabCorp to '. $notify;
	$body .= ') ' . $newtext;

	return sqlInsert("INSERT INTO pnotes (date, body, pid, user, groupname, " .
			"authorized, activity, title, assigned_to, message_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($datetime, $body, $pid, $message_sender, $message_group, $authorized, $activity, $title, $notify, $message_status) );
}

// set process defaults
$order_table = "form_labcorp_order";
$order_name = "labcorp_order";
$result_title = "LabCorp Results - ";
$result_table = "form_labcorp_result";
$result_name = "labcorp_result";
$item_table = "form_labcorp_result_item";
$item_name = "labcorp_result_item";
$labs_table = "form_labcorp_result_lab";
$labs_name = "labcorp_result_lab";

// get the table column names
$fields = sqlListFields($result_table);
$fields = array_slice($fields,7);

// get a handles to processors
$client = new LabCorpResultClient();

// initialize
$last_pid = null;
$last_order = null;

$results = array(); // to collect result records
$acks = array(); // to collect ack records

if ($BROWSER) { // debug output to html page
?>
<html>
	<head>
		<?php html_header_show();?>
		<title><?php echo $form_title; ?></title>

		<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
		<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox-1.3.4/jquery.fancybox-1.3.4.css" media="screen" />
		<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/interface/forms/labcorp_order/style_wmt.css" media="screen" />
		
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-1.7.2.min.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-ui-1.10.0.custom.min.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/common.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox-1.3.4/jquery.fancybox-1.3.4.pack.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/overlib_mini.js"></script>
		<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/textformat.js"></script>
	
	</head>
	
	<body>
		<table style="width:100%">
			<tr>
				<td colspan="2">
					<h2>LabCorp Result Processing</h2>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<pre>
<?php 
} // end of debug output header

echo "START OF BATCH PROCESSING: ".date('Y-m-d H:i:s')."\n";

$reprocess = FALSE;

if (!$FROM || !$THRU) { // must have both
	$FROM = FALSE;
	$THRU = FALSE;
}

if ($FROM) {
	if ($from_date = strtotime($FROM)) {
		$FROM = date('Ymd',$from_date);
	}
	else {
		echo "  -- Invalid from date: ($FROM) IGNORING DATES \n";
		$FROM = FALSE;
		$THRU = FALSE;
	}
}

if ($THRU) {
	if ($thru_date = strtotime($THRU)) {
		$THRU = date('Ymd',$thru_date);
	}
	else {
		echo "  -- Invalid to date: ($THRU) IGNORING DATES \n";
		$FROM = FALSE;
		$THRU = FALSE;
	}
}

$response_id = '';

if ($FROM && $THRU) {
	$reprocess = TRUE;
	echo "  -- Reprocessing from: $FROM to: $THRU \n";
	$messages = $client->repeatResults(25, $FROM, $THRU, $DEBUG);
}
else {
	$messages = $client->getResults(25, $DEBUG);
}

echo "\n\n";

foreach ($messages as $message) {
	ob_start(); // buffer the output
	
	// med-manager check
	$message->pid = str_replace('.', '0', $message->pid);
	
	$response_id = $message->response_id; // the same value in all messages
	if ($DEBUG) {	
		echo "<hr/>";
		echo "Processing Results for Patient: ".$message->name[0].", ".$message->name[1]." ".$message->name[2]."\n";
		echo "Patient PID: ".$message->pid."\n";
		echo "Patient DOB: ".date('Y-m-d',strtotime($message->dob))."\n";
		echo "Order Number: $message->order_number \n";
		echo "Provider: ".$message->provider[0]." - ".$message->provider[1]." ".$message->provider[2]."\n";
		echo "Lab number : $message->lab_number \n";
		echo "Lab Received: ".date('Y-m-d', strtotime($message->lab_received))." \n";
		echo "Facility: $message->facility \n\n";
	}

	$pid = 0;
	$pubpid = '';
	$provider_id = 0;
	$request_id = 0;
	$result_id = 0;
	$site_id = 0;
	$encounter = 0;
	$request_handling = 0;
	$matched = FALSE;
	$order_data = FALSE;
	$images = array(); // addl doc images
	
	// match order
	$query = "SELECT ot.id AS request_id, ot.pat_first, ot.pat_middle, ot.pat_last, ot.request_provider, ot.request_handling, forms.encounter FROM $order_table ot ";
	$query .= "LEFT JOIN forms ON ot.id = forms.form_id AND forms.formdir = 'labcorp_order' ";
	$query .= "WHERE ot.pid = '$message->pid' AND ot.pat_DOB = '".date('Y-m-d',strtotime($message->dob))."' AND ot.order_number = '$message->order_number' AND ot.labcorp_siteid = '$message->facility' ";
	$query .= "LIMIT 1";
	if ($parent = sqlQuery($query)) {
		if ($parent['request_id'] && $parent['encounter']) {
			$request_id = $parent['request_id'];
			$order_data = new wmtForm($order_name, $request_id);
			$request_handling = $parent['request_handling'];
			$provider_id = $parent['request_provider'];
			$encounter = $parent['encounter'];
			$matched = TRUE;
		}
	}
	else {
		echo "WARNING: NO MATCHING ORDER FOUND FOR THESE RESULTS \n";
	}
	
	// find previous result (if there is one)
	$query = "SELECT id FROM $result_table ";
	$query .= "WHERE request_order = '$message->order_number' AND lab_number = '$message->lab_number' ";
	$query .= "LIMIT 1";
	if ($result = sqlQuery($query)) {
		$result_id = $result['id'];
	}
	
	// create or retrieve result record (used for result output)
	$result_data = new wmtForm($result_name, $result_id, TRUE); // will create if no id present
	if ($result_data->id) { // found previous record so store details
		echo "NOTICE: FOUND PREVIOUS RECORDS FOR THESE RESULTS \n";
		$matched = TRUE;
		$pid = $result_data->pid;
		$pubpid = $result_data->pubpid;
		$provider_id = $result_data->request_provider;
		$site_id = $result_data->request_facility;
		if ($pid == $LABCORP_PID) $matched = FALSE; // this is an orphan result
	}
		
	// validate pid
	if (!$pid) {
		$query = "SELECT pid, providerID, lname, fname, mname FROM patient_data WHERE pid = '".$message->pid."' AND DOB = '".date('Y-m-d',strtotime($message->dob))."' ";
		if ($patient = sqlQuery($query)) {
			$pid = $patient['pid'];
		}
	}
	if (!$pid && $message->pubpid) { // maybe they used pubpid
		$query = "SELECT pid, providerID, lname, fname, mname FROM patient_data WHERE pubpid = '".$message->pubpid."' AND DOB = '".date('Y-m-d',strtotime($message->dob))."' ";
		if ($patient = sqlQuery($query)) {
			$pid = $patient['pid'];
		}
	}
	if (!$pid) { // no match
		$matched = FALSE;
		$pid = $LABCORP_PID; // if no valid patient use Quest dummy
		echo "WARNING: NO MATCHING PATIENT FOUND FOR THIS PID \n";
	}
		
	// validate result provider
	if ($provider_id) { 
		$query = "SELECT username FROM users WHERE id = '".$provider_id."' ";
		if ($provider = sqlQuery($query)) {
			$provider_username = $provider['username'];
		}
	}
	if (!$provider_id && $message->provider[0]) { // 2013-05-07 CRISWELL - CATCH BLANK NPI NUMBER
		$query = "SELECT id, facility_id, username FROM users WHERE npi = '".$message->provider[0]."' ";
		if ($provider = sqlQuery($query)) {
			$provider_id = $provider['id']; // use result provider if found
			$provider_facility = $provider['facility_id'];
			$provider_username = $provider['username'];
		}
	}
	if (!$provider_id) { // use patient default provider
		$query = "SELECT id, facility_id, username FROM users WHERE id = '".$patient['providerID']."' ";
		if ($provider = sqlQuery($query)) {
			$provider_id = $provider['id']; // patient default provider
			$provider_facility = $provider['facility_id'];
			$provider_username = $provider['username'];
		}
	}
	if (!$provider_id) { // use labcorp dummy provider
		$provider_id = $LABCORP_ID;
		$provider_username = 'labcorp';
	}

	// validate facility
	if ($site_id) {
		$query = "SELECT name FROM facility WHERE id = '".$site_id."' ";
		if ($site = sqlQuery($query)) {
			$site_name = $site['name'];
		}
	}
	if (!$site_id) {
		$query = "SELECT o.option_id, o.title, f.name, f.id  FROM list_options o, facility f ";
		$query .= "WHERE o.list_id = 'LabCorp_Site_Identifiers' AND o.title = '$message->facility' ";
		$query .= "AND o.option_id = f.id ";
		if ($site = sqlQuery($query)) {
			$site_id = $site['id'];	
			$site_name = $site['name'];
		}
	}
	if (!$site_id) {
		$site_name = 'UNKNOWN';
		$query = "SELECT f.name, f.id, o.title FROM facility f ";
		$query .= "LEFT JOIN list_options o ON o.option_id = f.id ";
		$query .= "WHERE f.id = '$provider_facility' ";
		if ($site = sqlQuery($query)) {
			$site_id = $site['id'];
			$site_name = $site['name'];
		}
	}
	
	// no order or previous result but found patient
	if ($pid != $LABCORP_PID && !$request_id && !$result_id) {
		// build dummy encounter for this patient/result
		$provider_id = ($provider_id) ? $provider_id : $LABCORP_ID;
		$conn = $GLOBALS['adodb']['db'];
		$encounter = $conn->GenID("sequences");
		addForm($encounter, "LABCORP RESULT ENCOUNTER",
			sqlInsert("INSERT INTO form_encounter SET " .
				"date = '".date('Y-m-d H:i:s',strtotime($message->datetime))."', " .
				"onset_date = '".date('Y-m-d H:i:s',strtotime($message->datetime))."', " .
				"reason = 'GENERATED ENCOUNTER FOR LABCORP RESULT', " .
				"facility = '" . add_escape_custom($site_name) . "', " .
				"pc_catid = '', " .
				"facility_id = '$site_id', " .
				"billing_facility = '', " .
				"sensitivity = 'normal', " .
				"referral_source = '', " .
				"pid = '$pid', " .
				"encounter = '$encounter', " .
				"provider_id = '$provider_id'"),
			"newpatient", $pid, 0, date('Y-m-d'), 'labcorp');
		$matched = TRUE;
	}
	
	// validate the respository directory
	$repository = $GLOBALS['oer_config']['documents']['repository'];		
	$file_path = $repository . preg_replace("/[^A-Za-z0-9]/","_",$pid) . "/";
	if (!file_exists($file_path)) {
		if (!mkdir($file_path,0700)) {
			throw new Exception("The system was unable to create the directory for this result, '" . $file_path . "'.\n");
		}
	}

	// generate observation results document
	$document = makeResultDocument($message);
	
	// create file name
	$unique = date('y').str_pad(date('z'),3,0,STR_PAD_LEFT); // 13031 (year + day of year)
	$doc_name = $message->order_number . "_RESULT";

	$docnum = 1;
	$doc_file = $doc_name."_".$unique.".pdf";
	while (file_exists($file_path.$doc_file)) { // don't overlay duplicate file names
		$doc_name = $message->order_number . "_RESULT_".$docnum++;
		$doc_file = $doc_name."_".$unique.".pdf";
	}

	// write the new file to the repository
	if (($fp = fopen($file_path.$doc_file, "w")) == false) {
		throw new Exception('Could not create local file ('.$file_path.$doc_file.')');
	}
	fwrite($fp, $document);
	fclose($fp);
				
	if ($DEBUG) echo "\nDocument Name: " . $doc_file;
				
	// register the new document
	$d = new Document();
	$d->name = $doc_name;
	$d->storagemethod = 0; // only hard disk sorage supported
	$d->url = "file://" .$file_path.$doc_file;
	$d->mimetype = "application/pdf";
	$d->size = filesize($file_path.$doc_file);
	$d->owner = $LABCORP_ID;
	$d->hash = sha1_file( $file_path.$doc_file );
	$d->type = $d->type_array['file_url'];
	$d->set_foreign_id($pid);
	$d->persist();
	$d->populate();
				
	// save document id
	$result_data->document_id = $d->get_id(); 
		
	// update cross reference
	$query = "REPLACE INTO categories_to_documents set category_id = '".$GLOBALS['lab_corp_catid']."', document_id = '" . $d->get_id() . "'";
	sqlStatement($query);

	// save the document identifier
	$doc_id = $result_data->document_id;
	if ($DEBUG && $doc_id) 
		echo "\nDocument Completion: SUCCESS\n";
	
	
	// result form data
	$result_data->date = date('Y-m-d H:i:s');
	$result_data->user = 'labcorp';
	$result_data->pid = $pid; // store under LABCORP if no valid patient
	$result_data->groupname = 'Default';
	$result_data->authorized = 0;
	$result_data->priority = 'n';
	
	$result_data->request_id = $request_id;
	$result_data->request_pid = $pid;
	$result_data->request_pubpid = $message->pubpid;
	$result_data->request_DOB = $message->dob;
	$result_data->request_pat_last = $message->name[0];
	$result_data->request_pat_first = $message->name[1];
	$result_data->request_pat_middle = $message->name[2];
	$result_data->request_provider = $provider_id;
	$result_data->request_npi = $message->provider[0];
	$result_data->request_control = $message->order_control;
	$result_data->request_order = $message->order_number;
	$result_data->request_facility = $site_id;
		
	$result_data->lab_received = date('Y-m-d', strtotime($message->lab_received));
	$result_data->lab_number = $message->lab_number;
	$result_data->lab_status = $message->lab_status;
	
	// store general notes
	$result_data->lab_notes = ''; // combine notes
	if ($message->notes) {
		$note_text = '';
		foreach ($message->notes AS $note) {
			$note_text .= trim($note->comment)." ";
		}
		$note_text = str_replace("\'", "\'\'", $note_text);
		$result_data->lab_notes = mysql_real_escape_string($note_text);
	}
	 
	$order_status = ''; // don't know yet
	$items = array(); // for new tests
	
	// poor naming... ORDERS == TESTS ORDERED
	if (count($message->orders) > 0) { // do we have anything to process?

		foreach ($message->orders as $order) {
			if ($DEBUG) {
				echo "<hr/>";
				echo "Test Ordered: ".$order->service_id[0]." - ".$order->service_id[1]."\n";
				echo "Specimen Date: ".date('Y-m-d H:i:s', strtotime($order->specimen_datetime))." \n";
				echo "Received Date: ".date('Y-m-d H:i:s', strtotime($order->received_datetime))." \n"; 
				echo "Result Date: ".date('Y-m-d H:i:s', strtotime($order->result_datetime))." \n";
				echo "Test Action: $order->action_type \n";
				echo "Result Status: $order->result_status \n";
			}
		
			if ($order->specimen_datetime && !$result_data->speciment_datetime)
				$result_data->specimen_datetime = date('Y-m-d H:i:s', strtotime($order->specimen_datetime));
			if ($order->received_datetime && !$result_data->received_datetime)
				$result_data->received_datetime = date('Y-m-d H:i:s', strtotime($order->received_datetime));
			if ($order->result_datetime && !$result_data->result_datetime)
				$result_data->result_datetime = date('Y-m-d H:i:s', strtotime($order->result_datetime));

			if (!$order_status && $order->result_status == 'F') $order_status = 'z'; // found a final so assume final
			if ($order->result_status == 'P') $order_status = 'x'; // found at least one partial so entire order is partial
			
			$items_abnormal = 0;
			if (count($order->results) > 0) { // do we have results for this order?
				foreach ($order->results as $result) {
			
					// merge notes into a single field
					$notes = '';
					if ($result->notes) {
						foreach ($result->notes as $note) {
							if ($notes) $notes .= "\n";
							$notes .= $note->comment;
						}
					}
			
					if ($DEBUG) {
						echo "\nValue Type: $result->value_type \n";
						echo "Observation: ".$result->observation_id[1]." \n";
						echo "Observed Value: $result->observation_value \n";
						echo "Observed Units: $result->observation_units \n";
						echo "Observed Range: $result->observation_range \n";
						echo "Observed Status: $result->observation_status \n";
						echo "Observed Abnormal: $result->abnormal_flags \n";
						echo "Observed Date: " .date('Y-m-d H:i:s', strtotime($result->observation_datetime)). "\n";
						echo "Observed Lab: $result->producer_id \n";
						echo "NOTES:\n $notes\n";
					}
				
					// generate the object
					$item_data = new wmtForm($item_name); // empty object

					// default form data
					$item_data->date = date('Y-m-d H:i:s');
					$item_data->pid = $pid; // store under LABCORP if no valid patient
					$item_data->sequence = count($items);
	
					$item_data->test_code = mysql_real_escape_string($order->service_id[0]);
					$item_data->test_text = mysql_real_escape_string($order->service_id[1]);
					$item_data->test_type = mysql_real_escape_string($order->action_type);
					$item_data->parent_code = mysql_real_escape_string($order->parent_id);
			
					$item_data->observation_type = mysql_real_escape_string($result->value_type);
					$item_data->observation_loinc = mysql_real_escape_string(ltrim($result->observation_id[3]));
					$item_data->observation_label = mysql_real_escape_string(ltrim($result->observation_id[1])); // use labcorp text
					if (!$item_data->observation_label) 
						$item_data->observation_label = mysql_real_escape_string(ltrim($result->observation_id[4])); // use loinc text
					
					$obvalue = $result->observation_value;
					if (is_array($obvalue)) {
						// do we have image files?
						if ($obvalue[1] == 'Image') {
							$obvalue = "SEE LAB DOCUMENT";
							if (is_array($item_data->images)) {
								$images = array_merge($images,$item_data->images);
							}
						}
						else {
							$obvalue = $obvalue[0]; // save text portion
						}
					}
					$item_data->observation_value = mysql_real_escape_string($obvalue);

					$item_data->observation_units = mysql_real_escape_string($result->observation_units);
					$item_data->observation_range = mysql_real_escape_string($result->observation_range);
					$item_data->observation_status = mysql_real_escape_string($result->observation_status);
					$item_data->observation_abnormal = mysql_real_escape_string($result->observation_abnormal);
					$item_data->producer_id = mysql_real_escape_string($result->producer_id); // performing lab id
					$item_data->observation_notes = mysql_real_escape_string($notes);
			
					
					$items[] = $item_data;
			
					if ($item_data->observation_abnormal != 'N') $items_abnormal++;
				} // end result loop
			} // end results check
		} // end order loop
	} // end order check
	
	// store lab facility data
	$labs = array(); // for new labs
	
	if ($message->labs) {
		foreach ($message->labs AS $lab_data) {
			if ($lab_data->phone) {
				$phone = preg_replace('~.*(\d{3})[^\d]*(\d{3})[^\d]*(\d{4}).*~', '($1) $2-$3', $lab_data->phone);
			}

			if ($lab_data->director) {
				$director = "";
				$director .= $lab_data->director[2]." "; // first
				if ($lab_data->director[3]) $director .= $lab_data->director[3]." "; // middle
				$director .= $lab_data->director[1]." "; // last
				if ($lab_data->director[0]) $director .= $lab_data->director[0]." "; // title
			}

			if ($DEBUG) {
				echo "Lab Id: $lab_data->code \n";
				echo "Lab Name: $lab_data->name \n";
				echo "Phone: $phone \n";
				echo "Director: $director \n";
				echo "<hr/>";
			}
		
			// generate the object
			$lab = new wmtForm($labs_name); // empty object
			
			// default form data
			$lab->date = date('Y-m-d H:i:s');
			$lab->user = 'labcorp';
			$lab->pid = $pid; // store under LABCORP if no valid patient
			$lab->sequence = count($labs) +1;
			
			$lab->code = mysql_real_escape_string($lab_data->code);
			$lab->name = mysql_real_escape_string($lab_data->name);
			$lab->street = mysql_real_escape_string($lab_data->address[0]);
			$lab->street2 = mysql_real_escape_string($lab_data->address[1]);
			$lab->city = mysql_real_escape_string($lab_data->address[2]);
			$lab->state = mysql_real_escape_string($lab_data->address[3]);
				
			if ($lab_data->address[4]) {
				if (strlen($lab_data->address[4] > 5)) $zip = preg_replace('~.*(\d{5})(\d{4}).*~', '$1-$2', $lab_data->address[4]);
				else $zip = $lab_data->address[4];				
				$lab->zip = mysql_real_escape_string($zip);
			}
			
			$lab->phone = mysql_real_escape_string($phone);
			$lab->director = mysql_real_escape_string($director);
			$lab->clia = $lab_data->clia;
			
			$labs[] = $lab;
		}
	}
	 
	// now save all of the results
	if (count($items) > 0) { // got results
		
		$result_data->status = $order_status; // determined from individual tests
		if (!$matched) { // got no pid or order
			$result_data->request_id = 0;
			$result_data->status = 'u'; // orphan
		}

		$result_form = '';
		$result_form = $result_title.$result_data->request_order." (".$result_data->lab_number.")";
		
		$result_data->result_abnormal = $items_abnormal;
		$result_data->result_handling = $request_handling;
		
		if ($result_id) { // have an existing record
			if ($result_data->result_notes) $result_data->result_notes .= "\n"; 
			$result_data->result_notes .= "RESULTS REVISED: ".date('Y-m-d H:i:s')." - Previous review data cleared!!";
				
			$result_data->reviewed_id = '';
			$result_data->reviewed_datetime = 'NULL';
			$result_data->notified_id = '';
			$result_data->notified_datetime = 'NULL';
			$result_data->notified_person = '';
				
			$result_data->update();
		}
		else { // need a new record
			$result_id = wmtForm::insert($result_data);
			if ($encounter) // only add form if matched to order or dummy encounter created
				addForm($encounter, $result_form, $result_id, $result_name, $pid, 0, 'NOW()', $provider_username);
		}
		
		// remove existing detail records
		$query = "DELETE FROM $item_table WHERE parent_id = $result_id ";
		sqlStatement($query);
		
		// insert new detail records
		foreach ($items as $record) {
			$record->parent_id = $result_id;
			wmtForm::insert($record);
		}
		
		// remove existing lab records
		$query = "DELETE FROM $labs_table WHERE parent_id = $result_id ";
		sqlStatement($query);
		
		// insert new lab records
		foreach ($labs as $record) {
			$record->parent_id = $result_id;
			wmtForm::insert($record);
		}
		
		// update status of order
		if ($order_data) {
			$order_data->status = 'g'; // results received for order
			$order_data->update();
		}
	}
	
	// if found, send them a message
	if ($provider_username && $provider_username != 'labcorp') {
		if ($patient) {
   			$link_ref = "../../forms/labcorp_result/update.php?id=$result_id&pid=".$pid."&enc=".$encounter;
   			
   			$note = "\n\nLabCorp results received for patient '".$patient['fname']." ".$patient['lname']."' (pid: ".$pid.") order number '".$message->order_number."'. ";
			$note .= "To review these results click on the following link: ";
  			$note .= "<a href='". $link_ref ."' target='_blank' class='link_submit' onclick='top.restoreSession()'>". $result_form ."</a>\n\n";
			labPnote($pid, $note, $provider_username);
		}
		else {
			$note = "LabCorp results received for an unknown patient. ";
			$note .= "\n\nThe information provided indicates the results are for patient '".$message->name[1]." ".$message->name[0]."' (pid: ".$message->pid.") ";
			$note .= "and order number '".$message->order_number."'. ";
			$note .= "Please use the Orphan Lab Results report to assign these results to a valid patient.\n\n";
			labPnote($pid, $note, $provider_username);
		}
	}
	

	// display results
	if ($DEBUG && $doc_file) { // do we have any?
		echo "Document Title: ".$doc_name." \n";
		echo "Document link: /controller.php?document&retrieve&patient_id=".$pid."&document_id=".$doc_id." \n\n";
	}

	// LAST... prepare acknowledgement
	$acks[] = $message->file_name;
	
	if ($DEBUG) {
		// display final results
		echo "<hr/>";
		echo "STORED RECORDS: ".count($items); 
		echo "\nACKNOWLEDGMENT: Result processed (ORDER: ".$message->order_number." LAB: ".$message->lab_number.")"; 
		echo "<hr/><hr/>";
	}
	else {
		echo "DATE: ".date('Y-m-d H:i:s')." -- ORDER: ".$message->order_number." -- LAB: ".$message->lab_number." -- PID: ".$message->pid." -- DOCUMENTS: ".$doccnt." -- RESULTS: ".count($items)."\n";
	}
	
	$output = ob_get_flush();
	
	$query = "INSERT INTO form_labcorp_batch SET ";
	$query .= "date = '$result_data->date', ";
	$query .= "pid = '$message->pid', ";
	$query .= "user = '".$_SESSION['authUser']."', ";
	$query .= "groupname = 'Default', ";
	$query .= "authorized = 0, ";
	$query .= "activity = 1, ";
	$query .= "facility = '$result_data->request_facility', ";
	$query .= "order_number = '$result_data->request_order', ";
	$query .= "order_datetime = '$result_data->specimen_datetime', ";
	$query .= "provider_id = '$result_data->request_provider', ";
	$query .= "provider_npi = '$result_data->request_npi', ";
	$query .= "pat_dob = '$result_data->request_DOB', ";
	$query .= "pat_first = '".mysql_real_escape_string($result_data->request_pat_first)."', ";
	$query .= "pat_middle = '".mysql_real_escape_string($result_data->request_pat_middle)."', ";
	$query .= "pat_last = '".mysql_real_escape_string($result_data->request_pat_last)."', ";
	$query .= "lab_number = '$result_data->lab_number', ";
	$query .= "lab_status = '$result_data->lab_status', ";
	$query .= "result_output = ? ";
	sqlStatementNoLog($query, array(mysql_real_escape_string($output)));
}

// send the acknowledgements 
if (count($acks) > 0 && !$reprocess) {
	foreach ($acks AS $key => $file) {
		$client->setResultAck($file, $DEBUG);
		if ($DEBUG) echo "\nACK MESSAGE: ".$file." - STATUS: Okay";
	}
}

echo "\n\nEND OF BATCH PROCESSING: ".date('Y-m-d H:i:s')."\n\n\n";

if ($BROWSER) { // end of debug html output
?>
					</pre>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php 
} // end of bedug output footer
?>
