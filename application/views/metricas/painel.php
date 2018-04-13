<?php require_once("inc/init.php"); ?>

<style>
.box{
	display: inline-block;
	vertical-align: top;
	width:25%; 
	margin:10px;
}
</style>

<div class="row">
	<div class="col-sm-12">
		
		<div class="well">
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

                foreach($vendas as $venda)
                {
                    if($venda->tipo_id)
                    {
						var_dump($venda);
						if(isset($venda->roi))
						{
							if($venda->roi == '0%')
							{
								echo "<div class='alert alert-info fade in box'>";    
							}
							else if($venda->roi[0] == '-')
							{
								echo "<div class='alert alert-danger fade in box'>";     
							}
							else
								echo "<div class='alert alert-success fade in box'>";   
								
							echo "<strong>Conta: </strong>" . $venda->conta . "<br>";
							echo "<strong>Anúncio: </strong>" . $venda->anuncio . "<br>";
							echo "<strong>Conjunto: </strong>" . $venda->conjunto . "<br>";
							echo "<strong>Campanha: </strong>" . $venda->campanha . "<br>";
							echo "<strong>ROI:</strong> <h4>$venda->roi</h4>";

							echo "</div>";
						}
                    }
                }
                    
            ?>


		</div> <!-- well -->
	</div> <!-- col-sm-12 -->
</div><!-- row -->

</section>

<script>
    
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>

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
	
	// end pagefunction
	
	// run pagefunction
	var pagefunction = function() {
        
    }

    var pagedestroy = function(){
		
		//destroy all charts
    	myLine.destroy();
		LineConfig=null;

    	if (debugState){
			root.console.log("✔ Chart.js charts destroyed");
		} 
	}
	
</script>