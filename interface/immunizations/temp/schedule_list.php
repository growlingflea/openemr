<span class="title"><?php xl('Immunization Schedules ','e');?></span>
<a class="more" href="codes.php?action=add&schedule_id=<?=$age_group_id?>">Add new</a>
<br><br>
<table cellspacing="0" class="immunization_codes">
    <thead>
    <tr>
        <th>Description</th>
        <th>Manufacturer</th>
        <th>Cvx code</th>
        <th>Proc codes</th>
        <th>Justify codes</th>
        <th>Default site</th>
        <th>Comments</th>
        <th>Drug route</th>
        <td></td>
    </tr>
    </thead>
    <tbody>
    <?php
    while($row = sqlFetchArray($query)) {
        ?>
        <tr>
            <td><?=$row['description']?></td>
            <td><?=$row['manufacturer']?></td>
            <td><?=$row['cvx_code']?></td>
            <td><?=$row['proc_codes']?></td>
            <td><?=$row['justify_codes']?></td>
            <td><?=$row['default_site']?></td>
            <td><?=$row['comments']?></td>
            <td><?=$row['drug_route']?></td>
            <td>
                <a href="./codes.php?action=del&id=<?=$row['id']?>" onclick="return confirm('Are you sure you want to delete this code?') ? true : false">Del</a>
            </td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>