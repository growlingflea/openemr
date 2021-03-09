<html>
<head>
    <?php html_header_show();?>
    <link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
    <style type="text/css">
        .immunization_codes {
            border-left: 1px #000000 solid;
            border-right: 1px #000000 solid;
            border-top: 1px #000000 solid;
            width: 90%;
        }
        .immunization_codes thead tr {
            height: 24px;
            background: lightgrey;
        }
        .immunization_codes tbody tr {
            height: 24px;
            background:white;
        }
        .immunization_codes td, .immunization_codes th {
            border-bottom: 1px #000000 solid;
            border-right: 1px #000000 solid;
            padding: 10px;
        }
        .immunization_codes th {
            text-align: left;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
    </style>

</head>
<body class="body_top">
<div class="success">
    <?php
    if(isset($successMessage)) {
        echo $successMessage;
    }
    ?>
</div>
<div class="error">
    <?php
    if(isset($errorMessage)) {
        echo $errorMessage;
    }
    ?>
</div>
<span class="title"><?php xl('Immunization codes ','e');?></span>
<a class="more" href="./codes.php?action=add">Add new</a>
<br><br>

<table width="600">
    <form action="../../library/cdc_immunization_import.php" method="post" enctype="multipart/form-data">

        <tr>
            <td width="20%">Select file</td>
            <td width="80%"><input type="file" name="file" id="file" /></td>
        </tr>

        <tr>
            <td>Submit</td>
            <td><input type="submit" name="submit" /></td>
        </tr>

    </form>
</table>

<table cellspacing="0" class="immunization_codes">
    <thead>
    <tr>
        <th><a href="codes.php?action=sort&value=isc.description">Description</a></th>
        <th><a href="codes.php?action=sort&value=manufacturer">Manufacturer</a></th>
        <th><a href="codes.php?action=sort&value=cvx_code">CVX <br> Code</a></th>
        <th>GTIN</th>
        <th>Proc <br> codes</th>
        <th>Justify <br> codes</th>
        <th>Default <br> site</th>
        <th><a href="codes.php?action=sort&value=age">Age</a></th>
        <th>Drug <br> route</th>
        <th>VIS <br> Date</th>
        <td></td>
    </tr>
    </thead>
    <tbody>
    <?php
    while( $row = sqlFetchArray($results)) {
        ?>
        <tr>
            <td><?=$row['description']?></td>
            <td><?=$row['manufacturer']?></td>
            <td><?=$row['cvx_code']?></td>
            <td><?=$row['GTIN'] ?></td>
            <td><?=$row['proc_codes']?></td>
            <td><?=$row['justify_codes']?></td>
            <td><?=$row['default_site']?></td>
            <td><?=$row['age_desc']?></td>
            <td><?=$row['drug_route']?></td>
            <td><?=$row['vis_date']?></td>
            <td><a href="codes.php?action=edit&id=<?=$row['id']?>"">Edit</a>
                <a href="codes.php?action=del&id=<?=$row['id']?>" onclick="return confirm('Are you sure you want to delete this code?') ? true : false">Del</a>
            </td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>
</body>
</html>