<?php require_once("inc/init.php"); ?>

<div class="row">
	<div class="col-sm-12">
		
		<div class="well">
            <select class='form-control' name="produtos" id="cmbprodutos">
                <option value="-1">TODOS</option>
                <?php 
                    foreach($produtos as $prod_usr):
                ?>
                        <option data-plataforma="<?php echo $prod_usr->plataforma; ?>"><?php echo $prod_usr->nome; ?></option>
                <?php
                    endforeach;
                ?>
            </select>
		</div> <!-- well -->
	</div> <!-- col-sm-12 -->
</div><!-- row -->

<!-- POSTBACKS -->
    <div id="resultado" class="row">
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
					<h2>Desempenho do Produto</h2>
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
						<?php
							if(isset($token_msg))
							{
								echo "<div class='alert alert-warning fade in'>";
								echo $token_msg;
								echo "</div>";
							}

							if(isset($ads_msg))
							{
								echo "<div class='alert alert-info fade in'>";
								echo $ads_msg;
								echo "</div>";
							}
						?>
						<div id="widget-body-content">
							
						</div>	

						<canvas id="lineChart" height="120"></canvas> 	
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
    function grafico(x,y)
	{
		console.log('Configurando gráfico');
		if(x == '')
			return;

        var randomColorFactor = function() {
            return Math.round(Math.random() * 255);
        };
        var randomColor = function(opacity) {
            return 'rgba(' + randomColorFactor() + ',' + randomColorFactor() + ',' + randomColorFactor() + ',' + (opacity || '.3') + ')';
        };

        LineConfig = {
		            type: 'line',
		            data: {
		                labels: x,
		                datasets: [{
		                    label: 'Vendas',
		                    data: y,
		                    
		                }]
		            },
		            options: {
		                responsive: true,
		                tooltips: {
		                    mode: 'label'
		                },
		                hover: {
		                    mode: 'dataset'
		                },
		                scales: {
		                    xAxes: [{
		                        display: true,
		                        scaleLabel: {
		                            show: true,
		                            labelString: 'Month'
		                        }
		                    }],
		                    yAxes: [{
		                        display: true,
		                        scaleLabel: {
		                            show: true,
		                            labelString: 'Value'
		                        },
		                        ticks: {
		                            suggestedMin: Math.min.apply(null, y),
		                            suggestedMax: Math.max.apply(null, y),
		                        }
		                    }]
		                }
		            }
		        };
		        $.each(LineConfig.data.datasets, function(i, dataset) {
		            dataset.borderColor = 'rgba(0,0,0,0.15)';
		            dataset.backgroundColor = randomColor(0.5);
		            dataset.pointBorderColor = 'rgba(0,0,0,0.15)';
		            dataset.pointBackgroundColor = randomColor(0.5);
		            dataset.pointBorderWidth = 1;
		        });

        myLine = new Chart(document.getElementById("lineChart"), LineConfig);
	}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>

<script>

	$( document ).ready(function() {
		myFunction();
    });

	function myFunction() {
		setTimeout(function(){ troca_produto($('#cmbprodutos')); }, 1000);
	}

    $('#cmbprodutos').change(function(){
        troca_produto($(this));
	});
	
	function troca_produto(combo)
	{
		var form_data = { 
            produto: combo.find(':selected').text(),
            val: combo.val(),
            plataforma: combo.find(':selected').data('plataforma')
        };

		var html = "";
		var x = "";
		var y = "";

        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/get_desempenho_produto',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                resp = msg; 
				console.log(msg);
				var obj = $.parseJSON(msg);
				html = obj.html;
				x = obj.array_x;
				y = obj.array_y;
				grafico(x,y);
            }
        }).responseText;

        $('#widget-body-content').html(html); 
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
		// clears the variable if left blank
	};
	
	// end pagefunction
	
	// run pagefunction
	pagefunction();
	
</script>
