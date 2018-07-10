	<!-- RIBBON -->
	<div id="ribbon">

		<span class="ribbon-button-alignment"> 
			<span id="refresh" class="btn btn-ribbon" data-action="resetWidgets" data-title="refresh"  rel="tooltip" data-placement="bottom" data-original-title="<i class='text-warning fa fa-warning'></i> Warning! This will reset all your widget settings." data-html="true" data-reset-msg="Would you like to RESET all your saved widgets and clear LocalStorage?"><i class="fa fa-refresh"></i></span> 
		</span>

		<!-- breadcrumb -->
		<ol class="breadcrumb">
			<!-- This is auto generated -->
		</ol>
		<!-- end breadcrumb -->

		<!-- You can also add more buttons to the
		ribbon for further usability

		Example below:

		<span class="ribbon-button-alignment pull-right">
		<span id="search" class="btn btn-ribbon hidden-xs" data-title="search"><i class="fa-grid"></i> Change Grid</span>
		<span id="add" class="btn btn-ribbon hidden-xs" data-title="add"><i class="fa-plus"></i> Add</span>
		<span id="search" class="btn btn-ribbon" data-title="search"><i class="fa-search"></i> <span class="hidden-mobile">Search</span></span>
		</span> -->

		<?php
			$style_30 = '';
			$style_7 = '';
			$style_hist = ''; 
			$style_mes = ''; 
			$style_active = "color:black; background-color:white; background-image:none";

			if(!isset($period))
				$style_hist = $style_active;
			else if($period == '30d')
				$style_30 = $style_active;
			else if($period == '7d')
				$style_7 = $style_active;
			else if($period == 'mes')
				$style_mes = $style_active;
				
		?>

		<span class="ribbon-button-alignment pull-right">
		<span style="color:white; float:left;">Período dos Dados: </span>
		<span id="sp_hist" class="btn btn-ribbon btn-periodos" style="<?php echo $style_hist; ?>" data-title="historico" data-action="periodos"> Histórico</span>
		<span id="sp_mes" class="btn btn-ribbon btn-periodos" style="<?php echo $style_mes; ?>" data-title="mes" data-action="periodos"> Este mês</span>
		<span id="sp_30d" class="btn btn-ribbon btn-periodos" style="<?php echo $style_30; ?>" data-title="30d" data-action="periodos"> 30 dias</span>
		<span id="sp_7d" class="btn btn-ribbon btn-periodos" style="<?php echo $style_7; ?>" data-title="7d" data-action="periodos"> 7 dias</span>
		<span>.........  </span>
		</span>

	</div>
	<!-- END RIBBON -->