<head>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head>

<select name="cmbprofiles" id="cmbprofiles">
    <option value="-1">Selecione</option>
    <?php
    foreach($profiles as $profile)
    {
        echo "<option value=" . $profile->user_id . ">" . $profile->first_name . " " . $profile->last_name . "</option>";
    }
    ?>
</select>

<select name="cmbcontas" id="cmbcontas">
    
</select>

<button id='btnlista'>Lista</button>
<button id='btngrafico'>Gráfico</button>

<div id='calendario' style="overflow:scroll; height:800px;width:70%;float:left;"></div>
<div id='preview' style="overflow:scroll; height:800px;float:left;width:30%;"></div>

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

    $('#btnlista').click(function(){
        var form_data = { account: $('#cmbcontas').val() };

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

    $('#btngrafico').click(function(){
        var form_data = { account: $('#cmbcontas').val() };

        $.ajax({
            url: '<?php echo base_url(); ?>app/show_conta_activities_graph',
            type: 'POST',
            data: form_data,
            global: false,
            async:true,
            success: function(msg) { 

               $('#calendario').html(msg);

            }
        });
    });

    $(document).on('click', '.campanha', function(e)  {
        var classes = $(this).attr("class").split(' ');

        $('.campanha').css("background-color", "#FFFFFF");
        $('.conjunto').css("background-color", "#FFFFFF");
        $('.anuncio').css("background-color", "#FFFFFF");

        $.each(classes, function(key, value) {
            if(value.search('campanha_') == 0)
                $('.'+value).css("background-color", "red");
        });
    });

    $(document).on('click', '.conjunto', function(e)  {
        var classes = $(this).attr("class").split(' ');

        $('.campanha').css("background-color", "#FFFFFF");
        $('.conjunto').css("background-color", "#FFFFFF");
        $('.anuncio').css("background-color", "#FFFFFF");

        $.each(classes, function(key, value) {
            if(value.search('campanha_') == 0)
                $('.'+value).css("background-color", "red");
            if(value.search('conjunto_') == 0)
                $('.'+value).css("background-color", "yellow");
        });
    });

    $(document).on('click', '.anuncio', function(e)  {
        var classes = $(this).attr("class").split(' ');

        $('.campanha').css("background-color", "#FFFFFF");
        $('.conjunto').css("background-color", "#FFFFFF");
        $('.anuncio').css("background-color", "#FFFFFF");

        $.each(classes, function(key, value) {
            if(value.search('campanha_') == 0)
                $('.'+value).css("background-color", "red");
            
            if(value.search('anuncio_') == 0)
            {
                var id = value.replace("anuncio_","");

                var form_data = { anuncio: id };

                $.ajax({
                    url: '<?php echo base_url(); ?>app/preview_ad',
                    type: 'POST',
                    data: form_data,
                    global: false,
                    async:true,
                    success: function(msg) { 
                        $('#preview').html(msg);
                    }
                });
            }
        });
    });

    $( function() {
        $( document ).tooltip({
            items: "td",
            content: function() {
                var extra = JSON.stringify($(this).data('extra'));
                return extra;
            }
        });
    });
</script>
