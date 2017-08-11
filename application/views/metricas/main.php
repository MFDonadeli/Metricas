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
            <form class='form-horizontal'>
                <?php
                if(!$contas): ?>
                    <div id='sem_contas'>
                        <h2>Sem Contas Sincronizadas.</h2>
                        <h4>Clique em Gerenciar Contas no menu para iniciar.</h4>
                    </div>
                <?php
                else: ?>
                    <fieldset>
                    <div class="form-group" id="div_select_contas">
                        <label class='col-md-2 control-label' for="contas">Conta:</label>
                        <div class='col-sm-5'><select class='form-control' name="contas" id="cmbcontas">
                            <option value="-1">Selecione</option>
                            <?php 
                                foreach($contas as $conta):
                            ?>
                                    <option value="<?php echo $conta->account_id; ?>"><?php echo $conta->account_name; ?></option>
                            <?php
                                endforeach;
                            ?>
                        </select></div>
                    </div> <!-- div_select_contas -->
                    <div class='form-group' id="div_select_campanhas">
                        <label class='col-md-2 control-label' for="campanhas">Campanha:</label>
                        <div class='col-sm-5'>
                            <select class='form-control' name="campanhas" id="cmbcampanhas">
                            </select>
                        </div>
                    </div>
                    <div class='form-group' id="div_select_conjuntos">
                        <label class='col-md-2 control-label' for="conjunto">Conjunto:</label>
                        <div class='col-sm-5'>
                            <select class='form-control' name="conjunto" id="cmbconjunto">
                            </select>
                        </div>
                    </div>
                    <div class='form-group' id="div_select_anuncios">
                        <label class='col-md-2 control-label' for="anuncios">Anúncio:</label>
                        <div class='col-sm-5'>
                            <select class='form-control' name="anuncios" id="cmbanuncios">
                            </select>
                            <div id='link_anuncio'></div>
                        </div>
                    </div>
                    <div class='form-group'>
                        <label class='col-md-2 control-label' for='txtcomissao'>Comissão Padrão:</label>
                        <div class='col-sm-5'><input class='form-control' type="text" name="txtcomissao" id="txtcomissao"></div>
                    </div>
                    <button class='btn btn-default' id="btnvernumeros">Ver Números</button>
                    </fieldset>
            
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

    $('#cmbanuncios').change(function(){
        var story = $(this).find(':selected').data('story');

        if(story!='')
        {
            $('#link_anuncio').html("<a href='https://facebook.com/" + story + "' target='_blank'>Anúncio com comentários</a>");
        }
        
    });

    $('#btnvernumeros').click(function(e){
        e.preventDefault();

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

        $.ajax({
            url: '<?php echo base_url(); ?>app/sync_metricas',
            type: 'POST',
            data: form_data,
            global: false,
            async: true,
            beforeSend: function (){
                $('#numeros').show();
                $('#numeros-content').html('<h1 class="ajax-loading-animation"><i class="fa fa-cog fa-spin"></i> Loading...</h1>');
            },
            success: function(msg) { 
                $('#numeros-content').html("<iframe width='100%' height='500 px' src='https://view.officeapps.live.com/op/embed.aspx?src=<?php echo base_url(); ?>template/" + msg.trim() + "'>");
            }
        });

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