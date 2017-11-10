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
                    <h2>Vendas no Anúncio</h2>

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
                        <button class="btn-success" id='btn_venda_manual'>Inserir Venda Manualmente</button>

                        <div id='div_novaVenda'>
                            <form class='form-horizontal'>
                                <fieldset>
                                    <div class='form-group'>
                                        <label class='col-md-2 control-label' for='dt_venda'>Data da Venda:</label>
                                        <div class='col-sm-5'><input class="form-control"  id="dt_venda" name="dt_venda" type="text"></div>
                                    </div>
                                    <div class='form-group'>
                                        <label class='col-md-2 control-label' for='cmbtipo'>Tipo da Venda:</label>
                                        <div class='col-sm-5'>
                                            <select class="form-control" id='cmbtipo' name='cmbtipo'>
                                                <option>Selecione</option>
                                                <option>Cartão</option>
                                                <option>Boleto Impresso</option>
                                                <option>Boleto Pago</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class='form-group'>
                                        <label class='col-md-2 control-label' for='cmbplataforma'>Plataforma:</label>
                                        <div class='col-sm-5'>
                                            <select class="form-control" id='cmbplataforma' name='cmbplataforma'>
                                            <option value="-1">Selecione</option>
                                                <?php 
                                                    foreach($plataforms as $plataforma): 
                                                ?>
                                            <option value="<?php echo $plataforma->platform_id; ?>"ß><?php echo $plataforma->name; ?></option>
                                                <?php
                                                    endforeach;
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class='form-group'>
                                        <label class='col-md-2 control-label' for='cmbproduto'>Produto:</label>
                                        <div class='col-sm-5'>
                                            <select class="form-control" id='cmbproduto' name='cmbproduto'>
                                            
                                            </select>
                                        </div>
                                    </div>
                                    <div class='form-group'>
                                        <label class='col-md-2 control-label' for='txt_comissao'>Comissão:</label>
                                        <div class='col-sm-5'><input class="form-control"  id="txt_comissao" name="txt_comissao" type="text"></div>
                                    </div>
                                    <div class='form-group'>
                                        <label class='col-md-2 control-label' for='btnsalvar'></label>
                                        <button class='btn btn-default col-sm-5' id="btnsalvar">Salvar</button>
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                        <!-- div_novaVenda -->
                        <?php
                        if($compras):
                        ?>	
                            <p>Produto: <span id='nome_produto'><?php echo $compras[0]->produto; ?>  </span> </p>
                            <p>Plataforma: <?php echo $compras[0]->plataforma; ?></p>

                        <table id="dt_vendas_associadas" class="table table-striped table-bordered table-hover" width="100%">
                            <thead>
                                    <tr>
                                        <th class="center" width="20px"></th>
                                        <th>Data</th>
                                        <th>Cartões </th>
                                        <th>Boletos Pagos </th>
                                        <th>Boletos Impressos </th>
                                        <th>Faturamento Total </th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                    foreach($compras as $compra)
                                    {
                                ?>
                                        <tr>
                                            <td class="center" width="20px">
                                                <a href="#" > <i class="fa fa-chevron-right fa-lg" data-toggle="row-detail" title="Vendas do dia"></i> </a>
                                            </td>
                                            <td><?php echo $compra->dt; ?></td>
                                            <td><?php echo $compra->cartoes; ?></td>
                                            <td><?php echo $compra->boletos_pagos; ?></td>
                                            <td><?php echo $compra->boletos_gerados; ?></td>
                                            <td><?php echo round(floatval($compra->faturamento_cartao)+floatval($compra->faturamento_boleto),2); ?></td>
                                        </tr>
                                <?php
                                    }
                                ?>
                                </tbody>
                        </table>
                        
                        <?php 
                        else:
                            echo "<h2>Sem Vendas Associadas</h2>";
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

<script>
$(document).ready(function(){

    <?php
        if($compras):
    ?>
        $("#cmbplataforma option:contains(<?php echo $compras[0]->plataforma; ?>)").attr('selected', true);
        $('#cmbplataforma').trigger('change');
    <?php
        endif;
    ?>
});

$( "#dt_venda" ).datepicker({
    	dateFormat : 'yy-mm-dd',
        prevText : '<i class="fa fa-chevron-left"></i>',
		nextText : '<i class="fa fa-chevron-right"></i>',
		onSelect : function(selectedDate) {
			$('#finishdate').datepicker('option', 'minDate', selectedDate);
		}
    });
</script>