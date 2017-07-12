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
        <button class='buscar_contas' id='btn_adicionar'>Adicionar Mais Contas</button>
        <button id='btn_todas'>Adicionar Todas</button>
        <button id='btn_sincronizar'>Sincronizar</button>
    </div>
    <p id="msg"></p>
    <div id="progressbar"></div>
    <div id='contas'>
    </div>

    <?php
    if(!$contas): ?>
        <div id='sem_contas'>
            <h2>Sem Contas Sincronizadas</h2>
            <button class='buscar_contas' id='btn_buscar_contas'>Buscar Contas</button>
        </div>
    <?php
    else: ?>
    <?php 
        foreach($contas as $contas_item):
            $arr = explode('_', $contas_item->effective_object_story_id);        
        ?>    
            <div class='container' id='div<?php echo $contas_item->id; ?>'>
                Anúncio: <?php echo $contas_item->ad_name; ?><br>
                ID: <?php echo $contas_item->id; ?><br>
                Conta: <?php echo $contas_item->account_name; ?><br>
                Campanha: <?php echo $contas_item->campaigns_name; ?><br>
                Conjunto: <?php echo $contas_item->ad_sets_name; ?><br>
                Tag: <?php echo $contas_item->url_tags; ?><br>
                <a href='https://www.facebook.com/<?php echo $arr[0] . '/posts/' . $arr[1];?>'>Link do Criativo</a><br>
                <button class='ver_metrica'>Métricas do Anúncio</button>
                <div class='numeros' id="numeros<?php echo $contas_item->id; ?>">
                    
                </div>
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
        $('.numeros').hide();
    });

    $('.ver_metrica').click(function(){
        //id = $(this).attr('id'); 

        divid = this.parentElement.id;
        id = divid.replace("div","");

        if($('#numeros' + id).is(':visible'))
        {
            $('#' + divid).toggleClass('bigContainer');
            $('#numeros' + id).hide();
            return;
        }
        
        var form_data = { id_ad: id };

        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/sync_ads',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                resp += msg; 
            }
        }).responseText;

        $('#numeros' + id).html(resp);
        $('#numeros' + id).show();
        $('#' + divid).toggleClass('bigContainer');



    });

    $('#btnfechar').click(function(){
        $('.numeros').hide();
    });

    $('#btn_todas').click(function(){
        $('.div_caixa').toggleClass('selected_container');
    });

    function sync_contas()
    {
        var id_conta;
        var count_divs = $('.selected_container').length;
        var i = 0;

        $('.selected_container').each(function(){
            i++;
            id_conta = $(this).attr('id');

            $('#msg').html('Sincronizando: ' + id_conta);
            $( "#progressbar" ).progressbar({
                value: i/count_divs
            });

            var form_data = { conta: id_conta };
            var resp = $.ajax({
                url: '<?php echo base_url(); ?>app/sync_contas',
                type: 'POST',
                data: form_data,
                global: false,
                async:false,
                success: function(msg) { 
                    resp = msg; 
                }
            }).responseText;
        });
    }

    $('#btn_sincronizar').click(function(){
        sync_contas();
    });

    $('.buscar_contas').click(function(){
        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/get_contas',
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
