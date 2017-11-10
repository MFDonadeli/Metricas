
<?php require_once("inc/init.php"); ?>

<style>

/* Style the tab content */
.resposta {
    display: none;
    padding: 6px 12px;
    border: 1px solid #ccc;
    border-top: none;
}
</style>

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
        <?php
        if(!$plataformas):
            echo "<h1>Sem postbacks configurados. Configure os postbacks em Configurações -> Gerenciar Postbacks</h1>";
        else:
        ?>
        <h2 id='msg_inicial'>Escolha uma plataforma para continuar:</h2>
            <?php
                foreach($plataformas as $key => $val)
                {
            ?>
                <button class='btn_plataforma btn-primary' id="btn<?php echo $val; ?>"><?php echo $key; ?></button>
            <?php
                }
            ?>
           <div id="resposta">

           </div>
        <?php endif; ?>
    </div>
</div>
<script>

    var id;
    var plataforma;

    $('.btn_plataforma').click(function (){
        id = $(this).attr('id');
        id = id.replace("btn","");

        $("#resposta").show();
        $(".btn_plataforma").removeClass("active");
        $(this).addClass("active");

        $('#msg_inicial').hide();

        plataforma = $(this).text();

        reloadTable();
    });


    $(document).on('click', '.btnAssociar', function(e)  {
        var dados = [];
        var tipos = [];
        var tr_arr = [];
        var src = [];
        $('.chkCartao').each(function(){
            if($(this).is(':checked'))
            {
                dados.push($(this).attr('id'));
                tipos.push($(this).data('tipo'));
                tr_arr.push($(this).closest('tr'));
                src.push($(this).data('src'));
            }
        });

        if(dados.lenght == 0)
            return;


        var form_data = { dados: dados,
                          ad_id: $('#cmbCartao').val(),
                          tipo: tipos,
                          src: src,
                          plataforma: $('#hidPlataforma').val() };

        $.ajax({
            url: '<?php echo base_url(); ?>app/grava_ad_venda',
            type: 'POST',
            data: form_data,
            global: false,
            async:true,
            success: function(msg) { 

                $.smallBox({
                    title : "Vendas na Plataforma",
                    content : "Vendas Associadas Com Sucesso",
                    color : "#659265",
                    iconSmall : "fa fa-check fa-2x fadeInRight animated",
                    timeout : 3000
                });

                reloadTable();

            }
        });
    });

    function reloadTable()
    {
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

        $("table[id*=dt_cartao]").remove();

        $('#resposta').html(resp);

        setUpGrids();
    }

    function setUpGrids()
    {
        var responsiveHelper_dt_cartao;
        var table_cartao;
         
         var breakpointDefinition = {
             tablet : 1024,
             phone : 480
         };

         if ( $.fn.dataTable.isDataTable( '#dt_cartao' ) ) {
             console.log(table_cartao);
             table_cartao = $('#dt_cartao').DataTable();
             table_cartao.destroy();
             console.log(table_cartao);
         }
         
         table_cartao = $('#dt_cartao').DataTable({
         "sDom": "<'dt-toolbar'<'col-xs-12 col-sm-6 hidden-xs'f><'col-sm-6 col-xs-12 hidden-xs'<'toolbar'>>r>"+
         	"t"+
         	"<'dt-toolbar-footer'<'col-sm-6 col-xs-12 hidden-xs'i><'col-xs-12 col-sm-6'p>>",
         "autoWidth" : true,
         "preDrawCallback" : function() {
         	// Initialize the responsive datatables helper once.
         	if (!responsiveHelper_dt_cartao) {
         		responsiveHelper_dt_cartao = new ResponsiveDatatablesHelper($('#dt_cartao'), breakpointDefinition);
         	}
         },
         "rowCallback" : function(nRow) {
         	responsiveHelper_dt_cartao.createExpandIcon(nRow);
         },
         "drawCallback" : function(oSettings) {
         	responsiveHelper_dt_cartao.respond();
         }
         });
        
        console.log(table_cartao);
         
         // Apply the filter
         $("#dt_cartao thead th select").on( 'change', function () {
                     console.log(table_cartao);
         table_cartao
         	.column( $(this).parent().index()+':visible' )
         	.search( this.value )
         	.draw();
         	
         } );
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