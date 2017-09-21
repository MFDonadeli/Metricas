
<!-- widget grid -->
<section id="widget-grid" class="">

	<!-- row -->
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
					<h2>Vendas na Plataforma</h2>

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
						if($compras):
						?>	
						<table id="dt_cartao" class="table table-striped table-bordered table-hover" width="100%">
							<thead>
									<tr>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th></th>
										<th class="hasinput" style="width:17%">
											<select class="form-control">
												<option></option>
												<option>Cartão</option>
												<option>Boleto Impresso</option>
												<option>Boleto Pago</option>
											</select>
										</th>
										<th></th>
									</tr>
									<tr>
										<th>
                                            <input type="checkbox" name="checkbox-inline">
										</th>
										<th>Código Compra</th>
										<th>Data da Compra </th>
										<th>Data da Confirmação</th>
										<th>Produto </th>
										<th>Src </th>
										<th>Tipo </th>
										<th>Comissão </th>
									</tr>
								</thead>
                                <tbody>
								<?php
                                    foreach($compras as $compra)
                                    {
                                ?>
                                        <tr>
                                            <td><input type="checkbox" name='chkCartao' class="chkCartao" id="<?php echo $compra->id_plataforma ?>" data-tipo='<?php echo $compra->tipo; ?>' data-src='<?php echo $compra->src; ?>'></td>
                                            <td><?php echo $compra->transaction; ?></td>
											<td><?php echo $compra->data_compra; ?></td>
                                            <td><?php echo $compra->data_confirmacao; ?></td>
                                            <td><?php echo $compra->produto; ?></td>
                                            <td style="word-break: break-all;"><?php echo $compra->src; ?></td>
											<td><?php echo $compra->tipo; ?></td>
											<td><?php echo $compra->comissao; ?></td>
                                        </tr>
                                <?php
                                    }
                                ?>
                                </tbody>
						</table>

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
						
						<?php 
						else:
							echo "<h2>Sem Vendas na Plataforma</h2>";
						endif;
						?>

					</div>
					<!-- end widget content -->

				</div>
				<!-- end widget div -->

			</div>
			<!-- end widget -->
        </article>
		<!-- WIDGET END -->

	</div>

	<!-- end row -->

	<!-- end row -->

</section>
<!-- end widget grid -->

