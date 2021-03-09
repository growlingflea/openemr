<?php

/**
 *  This file imports data from the cdc NDC crosswalk tables so end users can scan immunizations into the database without
 *  having to enter immunization data manually.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Daniel Pflieger daniel@growlingflea.com
 * @copyright Copyright (c) 2018 Daniel Pflieger daniel@growlingflea.com
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */



include_once("../globals.php");
include_once("$srcdir/registry.inc");
include_once("$srcdir/sql.inc");
include_once("$srcdir/options.inc.php");
use OpenEMR\Core\Header;

?>

<html>
<head>
<?php html_header_show();?>
<link rel="stylesheet" href="<?php echo $GLOBALS['css_header'];?>" type="text/css">
<title><?php xl('Immunizations: ','e'); ?></title>
<?php Header::setupHeader(['opener', 'report-helper', 'datatables', 'datetime-picker', 'dialog', 'jquery']); ?>

<script>

    $(document).ready(function() {

        var oTable;

        oTable=$('#show_immunizations_table').DataTable({
            dom: 'Bfrtip',
            autoWidth: false,
            fixedHeader: true,
            buttons: [
                'copy', 'excel', 'pdf', 'csv'
            ],
            ajax:{ type: "POST",
                url: "../../library/ajax/immunizations_ajax.php",
                data: {
                    func:"show_immunizations"
                },

            },

            columns:[
                {'data': 'id', visible:false, class:'admin'},
                {'data': 'GTIN', visible:false, class:'admin'},
                {'data': 'description'},
                {'data': 'manufacturer'},
                {'data': 'cvx_code'},
                {'data': 'NDC11'},
                {'data': 'proc_codes'},
                {'data': 'justify_codes'},
                {'data': 'default_site'},
                {'data': 'drug_route'},
                {'data': 'comments'},
                {'data': 'vis_date'},
                {
                    "data": null,
                    "defaultContent": "<button class='Edit' >Edit</button>",
                    "targets": -2
                },
                {
                    "data": null,
                    "defaultContent": "<button class='Delete'>Delete</button>",
                    "targets": -1
                }

            ],


            "iDisplayLength": 100,
            "select":true,
            "searching":true,
            "retrieve" : true


        });

        $('#show_immunizations_table tbody').on( 'click', 'button', function () {
            var data = oTable.row( $(this).parents('tr') ).data();
            var next = this.className;

            //handle the delete function

            if(next === "Delete"){

                console.log("we are going to attempt to delete something");
                $.ajax({
                    type: "POST",
                    url: "../../library/ajax/immunizations_ajax.php",
                    data: {
                        func:"delete_immunization",
                        info: data
                    }
                });

                oTable.ajax.reload();


            }else if (next === "Edit"){

                dlgopen('temp/view_edit_immunization.php?GTIN=' + encodeURIComponent(data['GTIN']) + '&id='+ encodeURIComponent(data['id']), '_blank', 800, 800);
                oTable.ajax.reload();
            }




        } );

        $('#add_new').click(function(){

            dlgopen('temp/view_edit_immunization.php?action=add', '_blank', 800, 800);


        });

        $('#default_file').change(function(){
            //on change event
            var formdata = new FormData();
            if($(this).prop('files').length > 0)
            {
                file =$(this).prop('files')[0];
                formdata.append("file", file);
            }

            console.log(formdata);


            $.ajax({
                url: "../../library/ajax/immunizations_ajax.php",
                type: "POST",
                data: formdata,
                processData: false,
                contentType: false,
                success: function (result) {

                    alert(result);
                    oTable.ajax.reload();
                }
            });


        });

        $(document).ajaxStart(function() {
            $('img').show(); // show the gif image when ajax starts
        }).ajaxStop(function() {
            $('img').hide(); // hide the gif image when ajax completes
        });


    });

    function refreshPage(){

        top.restoreSession();
    }


</script>
</head>
        <body class="body_top formtable">
            <span class='title'><?php xl('Vaccine Crosswalk Tables Upload - ','e'); ?> <a href="https://www2.cdc.gov/vaccines/iis/iisstandards/ndc_tableaccess.asp"  target="_blank">Upload the 'NDC Unit of Use', then the 'Vaccine NDC Linker' Found Here</a></span>


                <div id="report_parameters">

                    <table>
                        <tr>
                            <td width='410px'>
                                <div style='float:left'>
                                    <table>
<!--                                        <form id="submit_form" action="../../library/ajax/immunizations_ajax.php" method="post" enctype="multipart/form-data">-->

                                            <tr>
                                                <td>Upload </td>
                                                <td><input id="default_file" type="file" name="file" /></td>
                                                <td><input type="submit" name="upload_cdc_imm" id="upload_cdc_imm" value="Submit Files" /></td>
                                                <td><img src="../../images/loading.gif"</td>
                                            </tr>
                                            <tr>
                                                <td><button type="button" id="add_new">Add New</button></td>

                                            </tr>

<!--                                        </form>-->

                                    </table>

                                </div>
                            </td>
                       </tr>
                    </table>
                </div> <!-- end of parameters -->
                <div>Note: Excel files must be saved as a .csv for this script to work</div>

            <table cellpadding="0" cellspacing="0" border="0" class="display formtable session_table" id="show_immunizations_table">
                <thead align="left" class="dataTable-header">

                <tr>
                    <th class = "admin" hidden> <?php xl('ID','e'); ?> </th>
                    <th class = "admin" hidden> <?php xl('GTIN','e'); ?> </th>
                    <th> <?php xl('Description','e'); ?> </th>
                    <th> <?php xl('Manufacturer','e'); ?> </th>
                    <th > <?php xl('CVX','e'); ?> </th>
                    <th > <?php xl('NDC11','e'); ?> </th>
                    <th > <?php xl('Procedure Code','e'); ?> </th>
                    <th > <?php xl('Justify Code','e'); ?> </th>
                    <th > <?php xl('Default Code','e'); ?> </th>
                    <th > <?php xl('Drug Route','e'); ?> </th>
                    <th > <?php xl('Comments','e'); ?> </th>
                    <th > <?php xl('Vis Date','e'); ?> </th>
                    <th > </th><th > </th>



                </tr>

                </thead>
                <tbody id="users_list" >
                </tbody>

            </table>





        </body>

