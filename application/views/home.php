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
        <div id="div_select_contas">
            <label for="contas">Conta:</label>
            <select name="contas" id="cmbcontas">
                <option value="-1">Selecione</option>
                <?php 
                    foreach($contas as $conta):
                ?>
                        <option value="<?php echo $conta->account_id; ?>"><?php echo $conta->account_name; ?></option>
                <?php
                    endforeach;
                ?>
            </select><br>
        </div>
        <div id="div_select_campanhas">
            <label for="campanhas">Campanha:</label>
            <select name="campanhas" id="cmbcampanhas">
            </select><br>
        </div>
        <div id="div_select_conjuntos">
            <label for="conjunto">Conjunto:</label>
            <select name="conjunto" id="cmbconjunto">
            </select><br>
        </div>
        <div id="div_select_anuncios">
            <label for="anuncios">Anúncio:</label>
            <select name="anuncios" id="cmbanuncios">
            </select><br>
        </div>
        <button id="btnvernumeros">Ver Números</button>
        <div id="numeros"></div>
        <?php 
     endif;    
     ?>
</div>

<?php } ?>

<script>
    $( document ).ready(function() {
        <?php if(!$contas) { ?> $('#botao_contas').hide(); <?php } ?>
        $('#numeros').hide();
        $('#btnvernumeros').hide();

        $('#div_select_campanhas').hide();
        $('#div_select_conjuntos').hide();
        $('#div_select_anuncios').hide();
    });

    function ajax_fill_combo(id, tipo)
    {
        var form_data = { id: id,
                          tipo: tipo };


        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/fill_combo',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                resp = msg; 
            }
        }).responseText;

        return resp;        
    }

    $('#cmbcontas').change(function(){
        var retorno = ajax_fill_combo($('#cmbcontas').val(), 'campaigns');

        $('#div_select_campanhas').show();
        $('#div_select_conjuntos').hide();
        $('#div_select_anuncios').hide();
        $('#numeros').hide();

        $('#cmbcampanhas').empty();
        $('#cmbcampanhas').append(retorno);
        $('#btnvernumeros').hide();
    });

    $('#cmbcampanhas').change(function(){
        var val = $('#cmbcampanhas').val();
        if(val!=-1)
        {
            var retorno = ajax_fill_combo(val, 'adsets');

            $('#div_select_conjuntos').show();
            $('#div_select_anuncios').hide();
            $('#numeros').hide();

            $('#cmbconjunto').empty();
            $('#cmbconjunto').append(retorno);
            $('#btnvernumeros').show();
        }
        
    });

    $('#cmbconjunto').change(function(){
        var val = $('#cmbconjunto').val();
        var retorno = ajax_fill_combo(val, 'ads');

        if(val!=-1)
        {
            $('#div_select_anuncios').show();
            $('#numeros').hide();

            $('#cmbanuncios').empty();
            $('#cmbanuncios').append(retorno);
        }
        
    });

    $('#btnvernumeros').click(function(){
        //id = $(this).attr('id'); 

        var val;
        var tipo;

        divid = this.parentElement.id;
        id = divid.replace("div","");

        var val_conta = $('#cmbcontas').val();
        var val_campanha = $('#cmbcampanhas').val();
        var val_conjunto = $('#cmbconjunto').val();
        var val_anuncio = $('#cmbanuncios').val();

        if(val_anuncio != -1 && val_anuncio !== null)
        {
            val = $('#cmbanuncios').val();
            tipo = 'ad';
        }
        else if(val_conjunto != -1 && val_conjunto !== null)
        {
            val = $('#cmbconjunto').val();
            tipo = 'adset';
        }
        else if(val_campanha != -1 && val_campanha !== null)
        {
            val = $('#cmbcampanhas').val();
            tipo = 'campaign';
        }
        
        var form_data = { tipo: tipo,
                          val: val };

        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/sync_metricas',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                resp = msg; 
            }
        }).responseText;

        $('#numeros').show();

        $('#numeros').html("<iframe width='90%' height='500 px' src='https://view.officeapps.live.com/op/embed.aspx?src=<?php echo base_url(); ?>template/" + resp.trim() + "'>");

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
