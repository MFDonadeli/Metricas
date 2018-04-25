<?php require_once("inc/init.php"); ?>

<style>
.box{
	display: inline-block;
	vertical-align: top;
	width:25%;
  	min-width: 200px;
	margin:10px;
  	border: 1px solid;
}

#index-container{
	height:100%;
	overflow:hidden;
}

#index-main-content{
	float:left;     
}

#right-side-bar{
	width: 75%;
	float:right; 
}

.w20{
  width: 25%;
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

				if($vendas)
				{
					echo "<div id='index-container'>";
					echo "<div id='index-main-content'>";

					foreach($vendas as $venda)
					{
						if($venda->tipo_id)
						{
							if($venda->roi != "")
							{
								if($venda->roi == '0%')
								{
									echo "<div id='div_" . $venda->ad_id . "' class='alert alert-info fade in box'>";    
								}
								else if($venda->roi[0] == '-')
								{
									echo "<div id='div_" . $venda->ad_id . "' class='alert alert-danger fade in box'>";     
								}
								else
									echo "<div id='div_" . $venda->ad_id . "' class='alert alert-success fade in box'>";   
									
								echo "<strong>Conta: </strong>" . $venda->conta . "<br>";
								echo "<strong>Anúncio: </strong>" . $venda->anuncio . "<br>";
								echo "<strong>Conjunto: </strong>" . $venda->conjunto . "<br>";
								echo "<strong>Campanha: </strong>" . $venda->campanha . "<br>";
								echo "<strong>ROI:</strong> <h4>$venda->roi</h4>";

								echo "</div>";
							}
						}
					}

					echo "</div>"; //index-main-content
					echo "<div id='right-side-bar'></div>";
					echo "</div>"; 	//index-container
				}
                    
            ?>


		</div> <!-- well -->
	</div> <!-- col-sm-12 -->
</div><!-- row -->

<input type="hidden" id="ad_id">

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
		$('#right-side-bar').hide();
    }

    var pagedestroy = function(){
		
		//destroy all charts
    	myLine.destroy();
		LineConfig=null;

    	if (debugState){
			root.console.log("✔ Chart.js charts destroyed");
		} 
	}

	$('.box').click(function(){
		var val = $(this).attr('id');
		var id = val.replace("div_", "");

		var val_cmp = $("#ad_id").val();

		if(val == val_cmp)
		{
			$('#right-side-bar').hide();
			$('#index-main-content').removeClass('w20');
			$("#ad_id").val("");

			return;
		}
		else
		{
			$('#right-side-bar').show();
			$('#index-main-content').addClass('w20');
			$("#ad_id").val(val);
		}

		var form_data = { id: id };


        var resp = $.ajax({
            url: '<?php echo base_url(); ?>app/resumo_funil',
            type: 'POST',
            data: form_data,
            global: false,
            async: true,
            success: function(msg) { 
                $('#right-side-bar').html(msg);
            }
        }).responseText;

	});
	
</script>