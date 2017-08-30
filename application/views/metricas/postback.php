<?php require_once("inc/init.php"); ?>

<div class="row">
	<div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
		<h1 class="page-title txt-color-blueDark">
			<i class="fa fa-home"></i> 
			Gerenciador de Postbacks
		</h1>
	</div>
</div>

<div class="row">
	<div class="col-sm-12">
		
		<div class="well">
            <form class='form-horizontal'>
                    <fieldset>
                    <div class="form-group" id="div_select_contas">
                        <label class='col-md-2 control-label' for="plataformas">Plataforma:</label>
                        <div class='col-sm-5'><select class='form-control' name="plataformas" id="cmbplataformas">
                            <option value="-1">Selecione</option>
                            <?php 
                                foreach($plataformas as $plataforma):
                            ?>
                                    <option data-url='<?php echo $plataforma->postback_url; ?>' value="<?php echo $plataforma->platform_id; ?>"><?php echo $plataforma->name; ?></option>
                            <?php
                                endforeach;
                            ?>
                        </select></div>
                    </div> <!-- div_select_contas -->
                    <div class="form-group">
                        Configure a URL na Plataforma: <span id="postback_url"></span>
                    </div>
                    <div class='form-group'>
                        <label class='col-md-2 control-label' for='txttoken'>Token:</label>
                        <div class='col-sm-5'><input class='form-control' type="text" name="txttoken" id="txttoken"></div>
                    </div>
					<div class='form-group'>
                        <label class='col-md-2 control-label'></label>
                        <div class='col-sm-5'><button class='btn btn-default' id="btninserir">Inserir</button></div>
                    </div>
                </fieldset>
            </form>
		</div> <!-- well -->
	</div> <!-- col-sm-12 -->
</div><!-- row -->

<!-- POSTBACKS -->
    <div class="row">
    <!-- NEW WIDGET START -->
		<article class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

			<!-- Widget ID (each widget will need unique ID)-->
			<div class="jarviswidget" id="wid-id-0" data-widget-editbutton="false">
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
					<span class="widget-icon"> <i class="fa fa-table"></i> </span>
					<h2>Postbacks Cadastrados</h2>
				</header>

				<!-- widget div-->
				<div>

					<!-- widget edit box -->
					<div class="jarviswidget-editbox">
						<!-- This area used as dropdown edit box -->

					</div>
					<!-- end widget edit box -->

					<!-- widget content -->
					<div class="widget-body">
		
						<div class="table-responsive">
						
							<table id="table_postbacks" class="table table-bordered table-striped table-condensed table-fixed table-hover smart-form">
                                <thead>
									<tr>
										<th>
                                            <input type="checkbox" name="checkbox-inline">
										</th>
										<th>Plataforma </th>
										<th>Token</th>
										<th>Data da Criação </th>
									</tr>
								</thead>
                                <tbody>
                                    
                                </tbody>
							</table>
							
						</div>

                        <div class='form-group'>
                            <button id='btnApagar' class='btn btn-primary'>Apagar Selecionados</button>
                        </div>
                        
					</div>
					<!-- end widget content -->

				</div>
				<!-- end widget div -->

			</div>
			<!-- end widget -->

		</article>
		<!-- WIDGET END -->
</section>

<script>
    $( document ).ready(function() {
        fill_table();
    });

    function fill_table()
    {
        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/get_user_tokens',
            type: 'POST',
            data: '',
            global: false,
            async:false,
            success: function(msg) { 
                resp = msg; 
            }
        }).responseText;

        $('#table_postbacks tbody').html(resp);

        $('#cmbplataformas').val(-1).change();
        $('#txttoken').val("");     
    }

    $('#cmbplataformas').change(function(){
        var url = '';
        if($(this).val() != -1)
            url = $(this).find(':selected').data('url');
        $('#postback_url').html(url);
    });

    $('#btninserir').click(function(e){
        e.preventDefault();
        //id = $(this).attr('id'); 

		$plataforma = $('#cmbplataformas').val();
		$token = $('#txttoken').val();

		if($plataforma == -1)
		{
			alert('Selecione uma plataforma');
			return;
		}

		if($token == '')
		{
			alert('Token não pode estar em branco!');
			return;	
		}
        
        var form_data = { plataforma: $plataforma,
                          token: $token };

        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/cadastra_token',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                resp = msg; 
            }
        }).responseText;

        fill_table();

    });

    $('#btnApagar').click(function(e){
        e.preventDefault(); 
        
        var dados = [];
        $('.chkToken').each(function(){
            if($(this).is(':checked'))
                dados.push($(this).attr('id'));
        });

		if(dados.length == 0)
		{
			return;
		}

        var form_data = { id_token: dados };

        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/apaga_token',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                resp = msg; 
            }
        }).responseText;

        fill_table();

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