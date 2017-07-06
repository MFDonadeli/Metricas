<head>
  <link rel="stylesheet" href="<?php echo base_url(); ?>assets/styles.css">
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
</head>

<?php

if(!empty($authUrl)) {
    echo '<a href="'.$authUrl.'"><img src="'.base_url().'assets/facebook.png" alt=""/></a>';
}else{
?>
<div class="wrapper">
    <h1>Métricas</h1>
    <div class="welcome_txt">Bem-Vindo <b><?php echo $userData['first_name']; ?></b></div>

    <div class="fb_box">
        <p><b>Sair do <a href="<?php echo $logoutUrl; ?>">Facebook</a></b></p>
    </div>
</div>
<div id='div_items'>
    <div id='botao_contas'>
        <button id='btn_adicionar'>Adicionar Mais Contas</button>
        <button id='btn_todas'>Adicionar Todas</button>
        <button id='btn_sincronizar'>Sincronizar</button>
    </div>
    <p id='msg'></p>
    <div id='contas'>
    </div>

    <?php
    if(!$contas): ?>
        <div id='sem_contas'>
            <h2>Sem Contas Sincronizadas</h2>
            <button id='btn_buscar_contas'>Buscar Contas</button>
        </div>
    <?php
    else: ?>
    <?php 
        foreach($contas as $contas_item):?>
            <div class='container' id='div<?php echo $contas_item->id; ?>'>
                <strong><?php echo $contas_item->name; ?></strong><br>
                ID: <?php echo $contas_item->id; ?><br>
            </div>
        <?php 
        endforeach;
     endif;    
     ?>
</div>

<?php } ?>

<script>
    $( document ).ready(function() {
        <?php if(!$contas) { ?> $('#botao_contas').hide(); <?php } ?>
    });

    $('#btn_todas').click(function(){
        $('.div_caixa').toggleClass('selected_container');
    });

    $('#btn_sincronizar').click(function(){
        var id_conta;

        $('.selected_container').each(function(){
            id_conta = $(this).attr('id');

            $('#msg').html('Sincronizando: ' + id_conta);

            var form_data = { conta: id_conta };
            var resp = $.ajax({
                url: 'sync_contas',
                type: 'POST',
                data: form_data,
                global: false,
                async:false,
                success: function(msg) { 
                    resp = msg; 
                }
            }).responseText;
        });
    });

    $('#btn_buscar_contas').click(function(){
        var resp = $.ajax({
            url: 'get_contas',
            type: 'GET',
            data: '',
            global: false,
            async:false,
            success: function(msg) { 
                resp = msg; 
            }
        }).responseText;

        $('#contas').html(resp);
        $('#sem_contas').hide();
        $('#botao_contas').show();
    });

    $(document).on('click', '.div_caixa', function(e)  {
        $(this).toggleClass('selected_container');
    });
</script>
