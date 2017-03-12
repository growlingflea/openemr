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
<label>Select Age:</label>
<select id="select_age_group">
    <option value="">Age group</option>
    <?php
    while($row = sqlFetchArray($results)) {
        ?>
        <option value="<?=$row['id']?>"><?=$row['description']?></option>
        <?php
    }
    ?>
</select>
<br><br>
<div id="schedules_table"></div>
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        $('#select_age_group').change(function() {
            var age_group_id = $(this).val();

            $.ajax({
                dataType: "html",
                type: "POST",
                url: "schedules.php",
                data: {age_group_id: age_group_id},
                success: function(response){
                    $('#schedules_table').html(response);
                }
            });

        });
    });
</script>
</body>
</html>