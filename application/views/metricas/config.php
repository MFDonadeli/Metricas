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
            <div class='form-group'>
                <span class="onoffswitch-title">Usar postback da plataforma para vendas</span> 
                <span class="onoffswitch">
                    <input type="checkbox" name="start_interval" class="onoffswitch-checkbox" id="start_interval" <?php if($config->postback_enabled == 1) echo 'checked="checked"'; ?>>
                    <label class="onoffswitch-label" for="start_interval"> 
                    <span class="onoffswitch-inner" data-swchon-text="SIM" data-swchoff-text="NÃO"></span> 
                    <span class="onoffswitch-switch"></span> </label> 
                </span> 
            </div>
            <div class='form-group'>
                <label class='col-md-2 control-label' for='txtsinc'>Sincronização a cada quantas horas:</label>
                <div class='col-sm-5'><input class="form-control"  id="txtsinc" name="txtsinc" type="text" value="<?php echo $config->sync_time; ?>"></div>
            </div>
            <div class='form-group'>
                <button class='btn btn-default' id="btnsalvar">Salvar</button>
            </div>
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