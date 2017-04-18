<?php
/** **************************************************************************
 *	LABCORP_ORDER/PRINT.PHP
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
 *  @uses labcorp_order/report.php
 * 
 *************************************************************************** */
require_once("../../globals.php");
include_once("{$GLOBALS['incdir']}/forms/labcorp_result/report.php");
include_once("$srcdir/wmt/wmt.class.php");
require_once("{$GLOBALS['srcdir']}/wmt/wmt.include.php");

// grab inportant stuff
$pid = $_SESSION['pid'];
$encounter = $_SESSION['encounter'];
$id = $_REQUEST['id'];

// Retrieve content
$enc_data = wmtEncounter::getEncounter($encounter);
$result_data = new wmtForm('labcorp_result',$id);
$order_data = new wmtForm('labcorp_order',$result_data->request_id);

?>
<!DOCTYPE HTML>
<html>
	<head>
		<?php html_header_show();?>
		<title>LabCorp Results for <?php echo $result_data->request_pat_last; ?>", " <?php echo $result_data->request_pat_first; ?><?php echo ($result_data->request_pat_middle)?" ".$result_data->request_pat_middle:''; ?> on <?php echo $result_data->result_datetime; ?></title>
		<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
		<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/interface/forms/labcorp_order/style_wmt.css" media="screen" />
	</head>

	<body class="wmtPrint" style="width:650pt">
	    <h1><?php echo $enc_data->facility ?></h1>
	    <h2>LabCorp Results</h2>

		<table class="wmtHeader">
			<tr>
 				<td class="wmtHeaderLabel" style="width: 10%; text-align: left">Date:<input type="text" class="wmtHeaderOutput" readonly value="<?php echo date('Y-m-d'); ?>"></td>
				<td class="wmtHeaderLabel" style="width: auto; text-align: center">Patient Name:<input type="text" class="wmtHeaderOutput" style="width: 70%" readonly value="<?php echo $result_data->request_pat_first.' '.$result_data->request_pat_middle.' '.$result_data->request_pat_last; ?>"></td>
				<td class="wmtHeaderLabel" style="width: 10%; text-align: right">Patient ID:<input type="text" class="wmtHeaderOutput" readonly value="<?php echo $result_data->request_pid; ?>"></td>
			</tr>
		</table>

		<?php labcorp_result_report($pid, $encounter, '*', $id); ?>
	
	</body>
</html>