<head>
  <link rel="stylesheet" href="<?php echo base_url(); ?>assets/styles.css">
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
</head>

<input type="hidden" id="hidPlataforma">
<?php
    foreach($plataformas as $key => $val)
    {
        echo "<button class='btn_plataforma' id='" . $val . "'>" . $key . "</button>";
    }
?>

<div id='resposta'>
</div>

<script>
    $('.btn_plataforma').click(function (){
        var id = $(this).attr('id');
        var plataforma = $(this).text();
        $('#hidPlataforma').val(plataforma);

        var form_data = { id: id,
                          plataforma: plataforma };

        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/get_postback_data_to_assoc',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                resp = msg; 
            }
        }).responseText;

        $('#resposta').html(resp);
    });

    function enviar_dado(dados, ad, tipo)
    {
        var form_data = { dados: dados,
                          ad_id: ad,
                          tipo: tipo,
                          plataforma: $('#hidPlataforma').val() };

        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/grava_ad_venda',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                resp = msg; 
            }
        }).responseText;
    }

    $(document).on('click', '#btnCartoes', function(e)  {
        var dados = [];
        $('.chkCartao').each(function(){
            if($(this).is(':checked'))
                dados.push($(this).attr('id'));
        });

        enviar_dado(dados, $('#cmbCartao').val(), 'cartoes');
    });

    $(document).on('click', '#btnBoletosPagos', function(e)  {
        var dados = [];
        $('.chkBoletoPago').each(function(){
            if($(this).is(':checked'))
                dados.push($(this).attr('id'));
        });

        enviar_dado(dados, $('#cmbBoletoPago').val(), 'boletos_pagos');
    });

    $(document).on('click', '#btnBoletosGerados', function(e)  {
        var dados = [];
        $('.chkBoletoGerado').each(function(){
            if($(this).is(':checked'))
                dados.push($(this).attr('id'));
        });

        enviar_dado(dados, $('#cmbBoletoGerado').val(), 'boletos_gerados');
    });
</script>