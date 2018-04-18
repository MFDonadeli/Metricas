<?php require_once("inc/init.php"); ?>

<div class="row">
	<div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
		<h1 class="page-title txt-color-blueDark">
			<i class="fa fa-home"></i> 
			Configurações gerais
		</h1>
	</div>
</div>


<div class="row">
	<div class="col-sm-12">
		<div class="well">
           <h4>Configuração de Metas de KPIs para Anúncios</h4>
		   <label for="txtmeta1">Meta 1:</label>
		   <input type="text" name="txtmeta1" id="txtmeta1">
		   <label for="txtmeta2">Meta 2:</label>
		   <input type="text" name="txtmeta2" id="txtmeta2"><br>
		   <label for="txtmeta1">Meta 3:</label>
		   <input type="text" name="txtmeta3" id="txtmeta3">
		   <label for="txtmeta2">Meta 4:</label>
		   <input type="text" name="txtmeta4" id="txtmeta4"><br>

		   <h4>Campos de exibidos na planilha</h4>

		   <label for="descricao">Descrição:</label>
		   <input type="text" name="txtdescricao" id="txtdescricao">
		   <label for="selecao">Selecione o campo a exibir:</label>
		   <select name="selecao" id="selecao">
		   	
		   </select>
		   
		</div> <!-- well -->
	</div> <!-- col-sm-12 -->
</div><!-- row -->

<script type="text/javascript">


$('#btnsalvar').click(function(){
	var postback = 0;
	if($('#start_interval').prop('checked'))
		postback = 1;

	var sync_time = $("#txtsinc").val();

	if(sync_time == '' || sync_time < 1)
		sync_time = 1;

    var form_data = { sync_time: sync_time,
                          postback_enabled: postback };

    var resp = $.ajax({
        url: '<?php echo base_url(); ?>app/save_config',
        type: 'POST',
        data: form_data,
        global: false,
        async:false,
        success: function(msg) { 
            resp = msg; 
        }
    }).responseText;
});
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
	 * OR
	 * 
	 * loadScript(".../plugin.js", run_after_loaded);
	 */
	

	// PAGE RELATED SCRIPTS

	// pagefunction
	
	var pagefunction = function() {
		/*
		 * Spinners
		 */
		$("#txtsinc").spinner();
	};
	
	// end pagefunction
	
	// run pagefunction on load

	pagefunction();

</script>