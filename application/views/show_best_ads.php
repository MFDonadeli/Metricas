<head>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
</head>

<select name="cmbads" id="cmbads">
    <option value="-1">Selecione</option>
    <?php
    foreach($ads as $ad)
    {
        echo "<option value=" . $ad->ad_id . "> Vendas: " . $ad->qtde . "</option>";
    }
    ?>
</select>

<button id='btnpreview'>Preview</button>  

<div id='preview' style="overflow:scroll; height:800px;width:50%;float:left;"></div>
<div id='info' style="overflow:scroll; height:800px;float:left;width:50%;"></div>

<script>
    $('#btnpreview').click(function(){
        var form_data = { ad_id: $('#cmbads').val() };

        $.ajax({
            url: '<?php echo base_url(); ?>app/get_info_best_ad',
            type: 'POST',
            data: form_data,
            global: false,
            async:true,
            success: function(msg) { 
                var obj = $.parseJSON(msg);

                console.log(obj);

                $('#preview').html(obj.preview);
                $('#info').html(obj.info);
            }
        });
    });
</script>
