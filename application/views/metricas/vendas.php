<?php require_once("inc/init.php"); ?>

<div class="row">
	<div class="col-xs-12 col-sm-7 col-md-7 col-lg-4">
		<h1 class="page-title txt-color-blueDark">
			<i class="fa fa-list-alt"></i> 
			Vendas na Plataforma
		</h1>
	</div>
</div>

<div class="well">
    <input type="hidden" id="hidPlataforma">

    <div id="tabs">
        <ul>
            
            <?php
                foreach($plataformas as $key => $val)
                {
            ?>
            <li>
                <a class='btn_plataforma' href="#tab<?php echo $val; ?>"><?php echo $key; ?></a>
            </li>
            <?php
                }
            ?>
        </ul>

        <?php
            foreach($plataformas as $key => $val)
            {
        ?>
            <div id="tab<?php echo $val; ?>">
                <div id="resposta<?php echo $key; ?>">

                </div>
            </div>
        <?php
            }
        ?>
    </div>
</div>

<script>
    $('.btn_plataforma').click(function (){
        var id = $(this).attr('href');
        id = id.replace("#tab","");

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

        $('#resposta'+plataforma).html(resp);

        setUpGrids();
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
        var tipos = [];
        $('.chkCartao').each(function(){
            if($(this).is(':checked'))
            {
                dados.push($(this).attr('id'));
                tipos.push($(this).data('tipo'));
            }
        });

        enviar_dado(dados, $('#cmbCartao').val(), tipos);
    });

    function setUpGrids()
    {
        /* CARTOES ;*/
			var responsiveHelper_dt_cartao = undefined;
			var table_cartao = undefined;
			
			var breakpointDefinition = {
				tablet : 1024,
				phone : 480
			};

            if ( $.fn.dataTable.isDataTable( '#dt_cartao' ) ) {
                table_cartao = $('#dt_cartao').DataTable();
                table_cartao.destroy();
            }

			table_cartao = $('#dt_cartao').dataTable();
			console.log(table_cartao);

			// Apply the filter
			$("#dt_cartao thead th select").on( 'change', function () {

				table_cartao
					.column( $(this).parent().index()+':visible' )
					.search( this.value )
					.draw();
					
			} );

		/* END CARTOES ;*/
    }
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
        $( "#tabs" ).tabs();
	};
	
	// end pagefunction
	
	// run pagefunction
	pagefunction();

    // load related plugins

    var path = "<?php echo base_url(); ?>assets/";
	
	loadScript(path+"js/plugin/datatables/jquery.dataTables.min.js", function(){
		loadScript(path+"js/plugin/datatables/dataTables.colVis.min.js", function(){
			loadScript(path+"js/plugin/datatables/dataTables.tableTools.min.js", function(){
				loadScript(path+"js/plugin/datatables/dataTables.bootstrap.min.js", function(){
					loadScript(path+"js/plugin/datatable-responsive/datatables.responsive.min.js", pagefunction)
				});
			});
		});
	});
	
</script>