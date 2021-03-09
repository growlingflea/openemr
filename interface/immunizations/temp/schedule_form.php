<html>
<head>
    <?php html_header_show();?>
    <link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
    <style type="text/css">
        .immunization_codes td, .immunization_codes th {
            padding: 10px;
            border-bottom: 1px solid black;
        }
        .immunization_codes th {
            text-align: left;
        }
        .immunization_code_form input {
            width: 400px;
        }
        .error {
            color: red;
        }
    </style>

</head>
<body class="body_top">
<span class="title"><?php echo $action == 'add' ? 'Add' : 'Edit'; echo ' schedule';?></span>
<br><br>
<div class="error">
    <?php
    if(isset($errorMessage)) {
        echo $errorMessage;
    }
    ?>
</div>

<form method="post" action="schedules.php">
    <table class="immunization_code_form">
        <tr>
            <td><label for="description">Description</label></td>
            <td><input type="text" name="immunization_schedule[description]" value="<?php if(isset($result['description'])) echo $result['description'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Age</label></td>
            <td><input type="text" name="immunization_schedule[age]" value="<?php if(isset($result['age'])) echo $result['age'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Fge max</label></td>
            <td><input type="text" name="immunization_schedule[age_max]" value="<?php if(isset($result['age_max'])) echo $result['age_max'] ?>"></td>
        </tr>
        <tr>
            <td><label for="cccc">Frequency</label></td>
            <td><input type="text" name="immunization_schedule[frequency]" value="<?php if(isset($result['frequency'])) echo $result['frequency'] ?>"></td>
        </tr>
    </table>
    <input type="hidden" name="immunization_schedule[id]" value="<?php if(isset($result['id'])) echo $result['id'] ?>">
    <button><?php echo $action == 'add' ? 'Add' : 'Save';?></button>
</form>
</body>
</html>