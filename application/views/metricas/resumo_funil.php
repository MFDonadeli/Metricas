<table>
    <tr>
        <td><strong>Impressões:</strong></td>
        <td><?php echo $resumo->Impressoes; ?></td>
        <td><strong>Investimento:</strong></td>
        <td><?php echo $resumo->Investimento; ?></td>
    </tr>
</table>

<div style='float: left; margin: 10px;'>
<strong> Visão Geral </strong>
    <table class="table">
        <tr>
            <td>CPC:</td>
            <td><?php echo $resumo->CPC; ?></td>
        </tr>
        <tr>
            <td>CTR:</td>
            <td><?php echo $resumo->CTR; ?></td>
        </tr>
        <tr>
            <td>CPM:</td>
            <td><?php echo $resumo->CPM; ?></td>
        </tr>
        <tr>
            <td>ViewContents:</td>
            <td><?php echo $resumo->ViewContents; ?></td>
        </tr>
        <tr>
            <td>InitiateCheckout:</td>
            <td><?php echo $resumo->InitiateCheckout; ?></td>
        </tr>
        <tr>
            <td>Lead:</td>
            <td><?php echo $resumo->Lead; ?></td>
        </tr>
        <tr>
            <td>Vendas:</td>
            <td><?php echo $resumo->Vendas; ?></td>
        </tr>
        <tr>
            <td>Cartoes:</td>
            <td><?php echo $resumo->Cartoes; ?></td>
        </tr>
        <tr>
            <td>Boletos Gerados:</td>
            <td><?php echo $resumo->BoletosGerados; ?></td>
        </tr>
        <tr>
            <td>Boletos Pagos:</td>
            <td><?php echo $resumo->BoletosPagos; ?></td>
        </tr>
    </table>
</div>

<?php 
if($resumo->{'7dias'} == 1):
?>

<div style='float: left; margin: 10px;'>
<strong> Visão últimos 7 dias </strong>
    <table class="table">
        <tr>
            <td>CPC:</td>
            <td><?php echo $resumo->CPC_7dias; ?></td>
        </tr>
        <tr>
            <td>CTR:</td>
            <td><?php echo $resumo->CTR_7dias; ?></td>
        </tr>
        <tr>
            <td>CPM:</td>
            <td><?php echo $resumo->CPM_7dias; ?></td>
        </tr>
        <tr>
            <td>ViewContents:</td>
            <td><?php echo $resumo->ViewContent_7dias; ?></td>
        </tr>
        <tr>
            <td>InitiateCheckout:</td>
            <td><?php echo $resumo->InitiateCheckout_7dias; ?></td>
        </tr>
        <tr>
            <td>Lead:</td>
            <td><?php echo $resumo->Lead_7dias; ?></td>
        </tr>
        <tr>
            <td>Vendas:</td>
            <td><?php echo $resumo->Venda_7dias; ?></td>
        </tr>
        <tr>
            <td>Cartoes:</td>
            <td><?php echo $resumo->Cartao_7dias; ?></td>
        </tr>
        <tr>
            <td>Boletos Gerados:</td>
            <td><?php echo $resumo->BoletosGerado_7dias; ?></td>
        </tr>
        <tr>
            <td>Boletos Pagos:</td>
            <td><?php echo $resumo->BoletosPago_7dias; ?></td>
        </tr>
    </table>
</div>
<?php
endif;
?>

<?php 
if($resumo->{'3dias'} == 1):
?>

<div style='float: left; margin: 10px;'>
<strong> Visão últimos 3 dias </strong>
    <table class="table">
        <tr>
            <td>CPC:</td>
            <td><?php echo $resumo->CPC_3dias; ?></td>
        </tr>
        <tr>
            <td>CTR:</td>
            <td><?php echo $resumo->CTR_3dias; ?></td>
        </tr>
        <tr>
            <td>CPM:</td>
            <td><?php echo $resumo->CPM_3dias; ?></td>
        </tr>
        <tr>
            <td>ViewContents:</td>
            <td><?php echo $resumo->ViewContent_3dias; ?></td>
        </tr>
        <tr>
            <td>InitiateCheckout:</td>
            <td><?php echo $resumo->InitiateCheckout_3dias; ?></td>
        </tr>
        <tr>
            <td>Lead:</td>
            <td><?php echo $resumo->Lead_3dias; ?></td>
        </tr>
        <tr>
            <td>Vendas:</td>
            <td><?php echo $resumo->Venda_3dias; ?></td>
        </tr>
        <tr>
            <td>Cartoes:</td>
            <td><?php echo $resumo->Cartao_3dias; ?></td>
        </tr>
        <tr>
            <td>Boletos Gerados:</td>
            <td><?php echo $resumo->BoletosGerado_3dias; ?></td>
        </tr>
        <tr>
            <td>Boletos Pagos:</td>
            <td><?php echo $resumo->BoletosPago_3dias; ?></td>
        </tr>
    </table>
</div>
<?php 
endif;
?>

