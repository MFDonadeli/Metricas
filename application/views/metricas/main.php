<?php require_once("inc/init.php"); ?>

<div class="row">
	<div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
		<h1 class="page-title txt-color-blueDark">
			<i class="fa fa-home"></i> 
			Página Inicial
		</h1>
	</div>
</div>

<div class="row">
	<div class="col-sm-12">
		
		<div class="well">
			<?php 
            if(isset($error)):
                echo "Erro! Tente novamente!";
            else:
            ?>
                <div id='div_items'>
                    <div id='botao_contas'>
                        <button class='buscar_contas' id='btn_adicionar'>Adicionar Mais Contas</button>
                        <button id='btn_todas'>Adicionar Todas</button>
                        <button id='btn_sincronizar'>Sincronizar</button>
                    </div> <!-- botao_contas -->
                </div> <!-- div_items -->
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
                    </div> <!-- div_select_contas -->
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
                    Comissão Padrão:<input type="text" name="txtcomissao" id="txtcomissao">
                    <button id="btnvernumeros">Ver Números</button>
            
                <?php 
                endif;    
                ?>
            <?php 
            endif;    
            ?>
		</div> <!-- well -->
	</div> <!-- col-sm-12 -->
</div><!-- row -->

	
    <p id="msg"></p>
    <div id="progressbar"></div>

	<div style="clear:both"></div>



<section id="widget-grid" class="">
<div id='contas'>
    <!-- row -->
    <div class="row">

        <!-- a blank row to get started -->
        <div class="col-sm-12">
        
            <!-- your contents here -->
            <!-- Widget ID (each widget will need unique ID)-->
                <div class="jarviswidget" id="wid-id-2" data-widget-editbutton="false" data-widget-colorbutton="false">
                    <!-- widget options:
                        usage: <div class="jarviswidget" id="wid-id-0" data-widget-editbutton="false">
                        
                        data-widget-colorbutton="false"	
                        data-widget-editbutton="false"
                        data-widget-togglebutton="false"
                        data-widget-deletebutton="false"
                        data-widget-fullscreenbutton="false"
                        data-widget-custombutton="false"
                        data-widget-collapsed="true" 
                        data-widget-sortable="false"
                        
                    -->
                    <header>
                        <span class="widget-icon"> <i class="fa fa-arrows-v"></i> </span>
                        <h2 class="font-md"><strong>Contas</strong></h2>				
                    </header>

                    <!-- widget div-->
                    <div>
                        
                        <!-- widget edit box -->
                        <div class="jarviswidget-editbox">
                            <!-- This area used as dropdown edit box -->

                        </div>
                        <!-- end widget edit box -->
                        
                        <!-- widget content -->
                        <div class="widget-body" id='contas-content'>
                            
                            
                        </div>
                        <!-- end widget content -->
                        
                    </div>
                    <!-- end widget div -->
                    
                </div>
                <!-- end widget -->
        </div>
        <!-- end col-sm-12 -->
            
    </div>
    <!-- end row -->
</div>
<!-- end contas -->

<div id='numeros'>
    <!-- row -->
    <div class="row">

        <!-- a blank row to get started -->
        <div class="col-sm-12">
        
            <!-- your contents here -->
            <!-- Widget ID (each widget will need unique ID)-->
                <div class="jarviswidget" id="wid-id-2" data-widget-editbutton="false" data-widget-colorbutton="false">
                    <!-- widget options:
                        usage: <div class="jarviswidget" id="wid-id-0" data-widget-editbutton="false">
                        
                        data-widget-colorbutton="false"	
                        data-widget-editbutton="false"
                        data-widget-togglebutton="false"
                        data-widget-deletebutton="false"
                        data-widget-fullscreenbutton="false"
                        data-widget-custombutton="false"
                        data-widget-collapsed="true" 
                        data-widget-sortable="false"
                        
                    -->
                    <header>
                        <span class="widget-icon"> <i class="fa fa-arrows-v"></i> </span>
                        <h2 class="font-md"><strong>Métricas</strong></h2>				
                    </header>

                    <!-- widget div-->
                    <div>
                        
                        <!-- widget edit box -->
                        <div class="jarviswidget-editbox">
                            <!-- This area used as dropdown edit box -->

                        </div>
                        <!-- end widget edit box -->
                        
                        <!-- widget content -->
                        <div class="widget-body" id='numeros-content'>
                            
                            
                        </div>
                        <!-- end widget content -->
                        
                    </div>
                    <!-- end widget div -->
                    
                </div>
                <!-- end widget -->
        </div>
        <!-- end col-sm-12 -->
            
    </div>
    <!-- end row -->
</div>
<!-- end numeros -->
</section>

<script>
    $( document ).ready(function() {
        <?php if(!$contas) { ?> $('#botao_contas').hide(); <?php } ?>
        $('#numeros').hide();
        $('#btnvernumeros').hide();
        $('#contas').hide();

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
        $('#cmbconjunto').empty();
        $('#cmbanuncios').empty();
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
            $('#cmbanuncios').empty();
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
                          val: val,
                          comissao: $('#txtcomissao').val() };

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

        $('#numeros-content').html("<iframe width='100%' height='500 px' src='https://view.officeapps.live.com/op/embed.aspx?src=<?php echo base_url(); ?>template/" + resp.trim() + "'>");

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

		$('#contas').html('');
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

        $('#contas').show();
        $('#contas-content').html(resp);
        $('#sem_contas').hide();
        $('#botao_contas').show();
    });

    $(document).on('click', '.div_caixa', function(e)  {
        $(this).toggleClass('selected_container');
    });
</script>

<script type="text/javascript">
	
	/* DO NOT REMOVE : GLOBAL FUNCTIONS!
	 *
	 * pageSetUp(); WILL CALL THE FOLLOWING FUNCTIONS
	 *
	 * // activate tooltips
	 * $("[rel=tooltip]").tooltip();
	 *
	 * // activate popovers
	 * $("[rel=popover]").popover();
	 *
	 * // activate popovers with hover states
	 * $("[rel=popover-hover]").popover({ trigger: "hover" });
	 *
	 * // activate inline charts
	 * runAllCharts();
	 *
	 * // setup widgets
	 * setup_widgets_desktop();
	 *
	 * // run form elements
	 * runAllForms();
	 *
	 ********************************
	 *
	 * pageSetUp() is needed whenever you load a page.
	 * It initializes and checks for all basic elements of the page
	 * and makes rendering easier.
	 *
	 */

	pageSetUp();
	
	/*
	 * ALL PAGE RELATED SCRIPTS CAN GO BELOW HERE
	 * eg alert("my home function");
	 * 
	 * var pagefunction = function() {
	 *   ...
	 * }
	 * loadScript("js/plugin/_PLUGIN_NAME_.js", pagefunction);
	 * 
	 * TO LOAD A SCRIPT:
	 * var pagefunction = function (){ 
	 *  loadScript(".../plugin.js", run_after_loaded);	
	 * }
	 * 
	 * OR you can load chain scripts by doing
	 * 
	 * loadScript(".../plugin.js", function(){
	 * 	 loadScript("../plugin.js", function(){
	 * 	   ...
	 *   })
	 * });
	 */
	
	// pagefunction
	
	var pagefunction = function() {
		// clears the variable if left blank
	};
	
	// end pagefunction
	
	// run pagefunction
	pagefunction();
	
</script>