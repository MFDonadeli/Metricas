<!-- widget grid -->
<section id="widget-grid" class="">    
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
					<h2>Cartões</h2>
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
						
							<table id="dt_cartao" class="table table-bordered table-striped table-condensed table-fixed table-hover smart-form">
                                <thead>
									<tr>
										<th>
                                            <input type="checkbox" name="checkbox-inline">
										</th>
										<th>Data da Compra </th>
										<th>Data da Confirmação</th>
										<th>Produto </th>
										<th>Src </th>
									</tr>
								</thead>
                                <tbody>
                                    <?php
                                    foreach($compras['cartao'] as $compra)
                                    {
                                ?>
                                        <tr>
                                            <td><input type="checkbox" name='chkCartao' class="chkCartao" id='<?php echo $compra->id_plataforma ?>'></td>
                                            <td><?php echo $compra->data_compra; ?></td>
                                            <td><?php echo $compra->data_confirmacao; ?></td>
                                            <td><?php echo $compra->produto; ?></td>
                                            <td><?php echo $compra->src; ?></td>
                                        </tr>
                                <?php
                                    }
                                ?>
                                </tbody>
							</table>
							
						</div>

                        <div class='form-group'>
                            <label class='col-md-2 control-label' for="cmbCartao">Associar a:</label>
                            <select class='form-control' id="cmbCartao">
                                <option value="-1">Selecione</option>
                                <?php
                                    foreach($anuncios as $anuncio)
                                    {
                                ?>
                                        <option value='<?php echo $anuncio->id ?>'>
                                            Anuncio: <?php echo $anuncio->name; ?> - Tag: <?php echo $anuncio->url_tags; ?> -
                                            Status: <?php echo $anuncio->effective_status; ?> - Conjunto: <?php echo $anuncio->conjunto; ?> - 
                                            Campanha: <?php echo $anuncio->campanha; ?> - Conta: <?php echo $anuncio->conta; ?>
                                        </option>
                                <?php        
                                    }
                                ?>
                            </select>
                        </div>
                        <button id='btnCartoes' class='btnAssociar btn btn-primary'>Salvar</button>
						
					</div>
					<!-- end widget content -->

				</div>
				<!-- end widget div -->

			</div>
			<!-- end widget -->

		</article>
		<!-- WIDGET END -->
    </div> <!-- row -->

    <!-- BOLETO PAGO -->
    <div class="row">
    <!-- NEW WIDGET START -->
		<article class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

			<!-- Widget ID (each widget will need unique ID)-->
			<div class="jarviswidget" id="wid-id-1" data-widget-editbutton="false">
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
					<h2>Boletos Pagos</h2>
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
						
							<table id="dt_boleto_pago" class="table table-bordered table-striped table-condensed table-fixed table-hover smart-form">
                                <thead>
									<tr>
										<th>
                                            <input type="checkbox" name="checkbox-inline">
										</th>
										<th>Data da Compra </th>
										<th>Data da Confirmação</th>
										<th>Produto </th>
										<th>Src </th>
									</tr>
								</thead>
                                <tbody>
                                    <?php
                                    foreach($compras['boleto_pago'] as $compra)
                                    {
                                ?>
                                        <tr>
                                            <td><input type="checkbox" name='chkBoletoPago' class="chkBoletoPago" id='<?php echo $compra->id_plataforma ?>'></td>
                                            <td><?php echo $compra->data_compra; ?></td>
                                            <td><?php echo $compra->data_confirmacao; ?></td>
                                            <td><?php echo $compra->produto; ?></td>
                                            <td><?php echo $compra->src; ?></td>
                                        </tr>
                                <?php
                                    }
                                ?>
                                </tbody>
							</table>
							
						</div>

                        <div class='form-group'>
                            <label class='col-md-2 control-label' for="cmbBoletoPago">Associar a:</label>
                            <select class='form-control' id="cmbBoletoPago">
                                <option value="-1">Selecione</option>
                                <?php
                                    foreach($anuncios as $anuncio)
                                    {
                                ?>
                                        <option value='<?php echo $anuncio->id ?>'>
                                            Anuncio: <?php echo $anuncio->name; ?> - Tag: <?php echo $anuncio->url_tags; ?> -
                                            Status: <?php echo $anuncio->effective_status; ?> - Conjunto: <?php echo $anuncio->conjunto; ?> - 
                                            Campanha: <?php echo $anuncio->campanha; ?> - Conta: <?php echo $anuncio->conta; ?>
                                        </option>
                                <?php        
                                    }
                                ?>
                            </select>
                        </div>
                        <button id='btnBoletosPagos' class='btnAssociar btn btn-primary'>Salvar</button>
						
					</div>
					<!-- end widget content -->

				</div>
				<!-- end widget div -->

			</div>
			<!-- end widget -->

		</article>
		<!-- WIDGET END -->
    </div> <!-- row -->

    <!-- BOLETO GERADO -->
    <div class="row">
    <!-- NEW WIDGET START -->
		<article class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

			<!-- Widget ID (each widget will need unique ID)-->
			<div class="jarviswidget" id="wid-id-2" data-widget-editbutton="false">
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
					<h2>Boletos Gerados</h2>
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
						
							<table id="dt_boleto_gerado" class="table table-bordered table-striped table-condensed table-fixed table-hover smart-form">
                                <thead>
									<tr>
										<th>
                                            <input type="checkbox" name="checkbox-inline">
										</th>
										<th>Data da Compra </th>
										<th>Data da Confirmação</th>
										<th>Produto </th>
										<th>Src </th>
									</tr>
								</thead>
                                <tbody>
                                    <?php
                                    foreach($compras['boleto_impresso'] as $compra)
                                    {
                                ?>
                                        <tr>
                                            <td><input type="checkbox" name='chkBoletoGerado' class="chkBoletoGerado" id='<?php echo $compra->id_plataforma ?>'></td>
                                            <td><?php echo $compra->data_compra; ?></td>
                                            <td><?php echo $compra->data_confirmacao; ?></td>
                                            <td><?php echo $compra->produto; ?></td>
                                            <td><?php echo $compra->src; ?></td>
                                        </tr>
                                <?php
                                    }
                                ?>
                                </tbody>
							</table>
							
						</div>

                        <div class='form-group'>
                            <label class='col-md-2 control-label' for="cmbBoletoGerado">Associar a:</label>
                            <select class='form-control' id="cmbBoletoGerado">
                                <option value="-1">Selecione</option>
                                <?php
                                    foreach($anuncios as $anuncio)
                                    {
                                ?>
                                        <option value='<?php echo $anuncio->id ?>'>
                                            Anuncio: <?php echo $anuncio->name; ?> - Tag: <?php echo $anuncio->url_tags; ?> -
                                            Status: <?php echo $anuncio->effective_status; ?> - Conjunto: <?php echo $anuncio->conjunto; ?> - 
                                            Campanha: <?php echo $anuncio->campanha; ?> - Conta: <?php echo $anuncio->conta; ?>
                                        </option>
                                <?php        
                                    }
                                ?>
                            </select>
                        </div>
                        <button id='btnBoletosGerados' class='btnAssociar btn btn-primary'>Salvar</button>
						
					</div>
					<!-- end widget content -->

				</div>
				<!-- end widget div -->

			</div>
			<!-- end widget -->

		</article>
		<!-- WIDGET END -->
    </div> <!-- row -->

</section>
<!-- end widget grid -->

