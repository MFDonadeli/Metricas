<head>
  <link rel="stylesheet" href="styles.css">
  <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
</head>

<select name="cmbprofiles" id="cmbprofiles">
    <option value="-1">Selecione</option>
    <?php
    foreach($profiles as $profile)
    {
        echo "<option value=" . $profile->profile_id . ">" . $profile->first_name . " " . $profile->last_name . "</option>";
    }
    ?>
</select>

<select name="cmbcontas" id="cmbcontas">
    
</select>

<div id='calendario'></div>

<script>
    $('#cmbprofiles').change(function(){
        var form_data = { profile: $(this).val() };

        $.ajax({
            url: '<?php echo base_url(); ?>app/get_accounts_info',
            type: 'POST',
            data: form_data,
            global: false,
            async:true,
            success: function(msg) { 
               $('#cmbcontas').empty();
               $('#cmbcontas').append(msg);

            }
        });
    });

    $('#cmbcontas').change(function(){
        var form_data = { account: $(this).val() };

        $.ajax({
            url: '<?php echo base_url(); ?>app/show_conta_activities',
            type: 'POST',
            data: form_data,
            global: false,
            async:true,
            success: function(msg) { 

               $('#calendario').html(msg);

            }
        });
    });
</script>
