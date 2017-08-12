<?php require_once("inc/init.php"); ?>

<!-- widget grid -->
<section id="widget-grid" class="">

	<!-- row -->
	<div class="row">

		<!-- NEW WIDGET START -->
		<article class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

			<!-- Widget ID (each widget will need unique ID)-->
			<div class="jarviswidget jarviswidget-color-darken" id="wid-id-0" data-widget-editbutton="false">
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
					<h2>Contas Sincronizadas</h2>

				</header>

				<!-- widget div-->
				<div>

					<!-- widget edit box -->
					<div class="jarviswidget-editbox">
						<!-- This area used as dropdown edit box -->

					</div>
					<!-- end widget edit box -->

					<!-- widget content -->
					<div class="widget-body no-padding">
                    <?php 
                        if(!$contas):
                            echo "<h2> Sem contas sincronizadas </h2";
                        else:
                    ?>
						<table id="dt_basic" class="table table-striped table-bordered table-hover" width="100%">
							<thead>			                
								<tr>
									<th data-hide="phone">ID</th>
									<th data-class="expand"> Nome</th>
									<th data-hide="phone"> Última atualização</th>
									<th>Anúncios Ativos</th>
									<th>Remover</th>
								</tr>
							</thead>
							<tbody>
                    <?php
                        foreach($contas as $conta)
                        {
                            echo "<tr>";
                                echo "<td>" . $conta->id . "</td>"; 
                                echo "<td>" . $conta->name . "</td>"; 
                                echo "<td>" . $conta->updated_time . "</td>"; 
                                echo "<td>" . $conta->anuncios_ativos . "</td>"; 
								echo "<td><a class='btn btn-danger btn_remover' id='btn" . $conta->id . "' data-name='" . $conta->name . "' href='#'><i class='fa fa-remove'></i> Remover do Sistema</a>";
                            echo "</tr>";
                        }
                    ?>
							</tbody>
						</table>
                    <?php endif; ?>

					</div>
					<!-- end widget content -->

				</div>
				<!-- end widget div -->

			</div>
			<!-- end widget -->
        </article>
		<!-- WIDGET END -->

        <div class='form-group'>
            <button class='btn btn-primary buscar_contas' id='btn_adicionar'>Adicionar Contas</button>
        </div>

        <div id='div_novas_contas'>
        <!-- NEW WIDGET START -->
		<article class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

			<!-- Widget ID (each widget will need unique ID)-->
			<div class="jarviswidget jarviswidget-color-darken" id="wid-id-1" data-widget-editbutton="false">
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
					<h2>Lista de Contas</h2>

				</header>

				<!-- widget div-->
				<div>

					<!-- widget edit box -->
					<div class="jarviswidget-editbox">
						<!-- This area used as dropdown edit box -->

					</div>
					<!-- end widget edit box -->

					<!-- widget content -->
					<div class="widget-body no-padding">
                    <?php 
                        if(!$contas):
                            echo "<h2> Sem contas sincronizadas </h2";
                        else:
                    ?>
						<table id="dt-novas-contas" class="table table-striped table-bordered table-hover" width="100%">
							<thead>			                
								<tr>
                                    <th><input type="checkbox" class="chkContaNova" id="todas"></th>
									<th>ID</th>
									<th>Nome</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
                    <?php endif; ?>

					</div>
					<!-- end widget content -->

				</div>
				<!-- end widget div -->
                <button class='btn btn-primary' id='btn_sincronizar'>Sincronizar Contas Selecionadas</button>
			</div>
			<!-- end widget -->
        </article>
		<!-- WIDGET END -->
        </div>

	</div>

	<!-- end row -->

	<!-- end row -->

</section>
<!-- end widget grid -->
<!-- Modal -->
<div class="modal fade" id="modal_contas" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"  data-keyboard="false" data-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="myModalLabel">Sincronizando Contas</h4>
      </div>
      <div class="modal-body">
		<div id="modal-text"></div>
		<div class="progress progress-sm progress-striped active">
			<div class="progress-bar bg-color-darken" id="progress_contas" role="progressbar" style="width: 0%"></div>
		</div>
      </div>
      <div class="modal-footer">
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

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
	 */
	
	// PAGE RELATED SCRIPTS
	
	// pagefunction	
	var pagefunction = function() {
		//console.log("cleared");
		
		/* // DOM Position key index //
		
			l - Length changing (dropdown)
			f - Filtering input (search)
			t - The Table! (datatable)
			i - Information (records)
			p - Pagination (paging)
			r - pRocessing 
			< and > - div elements
			<"#id" and > - div with an id
			<"class" and > - div with a class
			<"#id.class" and > - div with an id and class
			
			Also see: http://legacy.datatables.net/usage/features
		*/	

        $( document ).ready(function() {
            $('#div_novas_contas').hide();
		});
		
		$('.btn_remover').click(function(e)  {
			var name = $(this).data('name');
			var id_conta = $(this).attr('id');
			var close_tr = $(this).closest('tr');

			$.SmartMessageBox({
				title : "Alerta!",
				content : "Deseja realmente remover a conta " + name + " do sistema?",
				buttons : '[No][Yes]'
			}, function(ButtonPressed) {
				if (ButtonPressed === "Yes") {
					console.log(id_conta);
					id_conta = id_conta.replace("btn","");

					var form_data = { conta: id_conta };

					$.ajax({
						url: '<?php echo base_url(); ?>app/apaga_conta',
						type: 'POST',
						data: form_data,
						global: false,
						async: true,
						success: function(msg) { 
							close_tr.remove();
							$.smallBox({
								title : "Conta Excluída",
								content : "Conta " + name + " excluída do sistema",
								color : "#659265",
								iconSmall : "fa fa-check fa-2x fadeInRight animated",
								timeout : 3000
							});
						}
					});
				}
				if (ButtonPressed === "No") {
	
				}
	
			});
			e.preventDefault();
		});

        $('#btn_adicionar').click(function(){
            $.ajax({
                url: '<?php echo base_url(); ?>app/get_contas',
                type: 'GET',
                data: '',
                global: false,
				async: true,
				beforeSend: function (){
					$('#div_novas_contas').show();
					$('#dt-novas-contas tbody').html('<h1 class="ajax-loading-animation"><i class="fa fa-cog fa-spin"></i> Loading...</h1>');
				},
                success: function(msg) { 
					$('#dt-novas-contas tbody').html(msg); 
					setUpContasNovas();
                }
            });
        });

		/* BASIC ;*/
			var responsiveHelper_dt_basic = undefined;
			var responsiveHelper_datatable_fixed_column = undefined;
			var responsiveHelper_datatable_col_reorder = undefined;
			var responsiveHelper_datatable_tabletools = undefined;
			
			var breakpointDefinition = {
				tablet : 1024,
				phone : 480
			};

			$('#dt_basic').dataTable({
				"sDom": "<'dt-toolbar'<'col-xs-12 col-sm-6'f><'col-sm-6 col-xs-12 hidden-xs'l>r>"+
					"t"+
					"<'dt-toolbar-footer'<'col-sm-6 col-xs-12 hidden-xs'i><'col-xs-12 col-sm-6'p>>",
				"autoWidth" : true,
				"preDrawCallback" : function() {
					// Initialize the responsive datatables helper once.
					if (!responsiveHelper_dt_basic) {
						responsiveHelper_dt_basic = new ResponsiveDatatablesHelper($('#dt_basic'), breakpointDefinition);
					}
				},
				"rowCallback" : function(nRow) {
					responsiveHelper_dt_basic.createExpandIcon(nRow);
				},
				"drawCallback" : function(oSettings) {
					responsiveHelper_dt_basic.respond();
				}
			});

		/* END BASIC */

		var contas = [];
		var i = 0;

        function sync_conta(i)
		{
			console.log('sync_conta ' + i);
			id_conta = contas[i];

			var form_data = { conta: id_conta };
			$.ajax({
				url: '<?php echo base_url(); ?>app/sync_contas',
				type: 'POST',
				data: form_data,
				global: false,
				async: true,
				beforeSend: function (){
					$('#modal_contas').modal('show');
					$('#modal-text').html('<h1 class="ajax-loading-animation"><i class="fa fa-cog fa-spin"></i> Sincronizando ' + id_conta +  '</h1>');
				},
				success: function(msg) { 
					if(i == contas.length - 1)
					{
						$('#modal_contas').modal('hide');
						location.reload();
					}
					else
					{
						i++;
						$('#progress_contas').css("width", ((i / contas.length) * 100) + "%");
						sync_conta(i);
					}		 
				}
			});
		}
        $('#btn_sincronizar').click(function(){
			var count_divs = $('.selected_container').length;

            $('.chkContaNova').each(function(){
				if($(this).is(':checked'))
				{
					contas.push($(this).attr('id'));
				}
			});

			sync_conta(i);
        });

        var table_contas_novas;
        function setUpContasNovas(){
        /* CONTAS NOVAS ;*/
			var responsiveHelper_dt_novas_contas = undefined;
			var responsiveHelper_datatable_fixed_column = undefined;
			var responsiveHelper_datatable_col_reorder = undefined;
			var responsiveHelper_datatable_tabletools = undefined;
			
			var breakpointDefinition = {
				tablet : 1024,
				phone : 480
			};

            if ( $.fn.dataTable.isDataTable( '#dt-novas-contas' ) ) {
                table_contas_novas = $('#dt-novas-contas').DataTable();
                table_contas_novas.destroy();
            }

			table_contas_novas = $('#dt-novas-contas').dataTable({
				"sDom": "<'dt-toolbar'<'col-xs-12 col-sm-6'f><'col-sm-6 col-xs-12 hidden-xs'l>r>"+
					"t"+
					"<'dt-toolbar-footer'<'col-sm-6 col-xs-12 hidden-xs'i><'col-xs-12 col-sm-6'p>>",
				"autoWidth" : true,
				"preDrawCallback" : function() {
					// Initialize the responsive datatables helper once.
					if (!responsiveHelper_dt_novas_contas) {
						responsiveHelper_dt_novas_contas = new ResponsiveDatatablesHelper($('#dt-novas-contas'), breakpointDefinition);
					}
				},
				"rowCallback" : function(nRow) {
					responsiveHelper_dt_novas_contas.createExpandIcon(nRow);
				},
				"drawCallback" : function(oSettings) {
					responsiveHelper_dt_novas_contas.respond();
				}
			});

		/* END CONTAS NOVAS */
        }
	};

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