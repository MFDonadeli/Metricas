<style>
    .exampleB {
        margin: 10px;
    }

    .example3 {
        width: 150px;
        height: 70px;
        background-color: white;
        margin: 5px;
        float: left;
        box-shadow: 0px 0px 10px #666666;  
    }

    .destaque-numero{
        font-size: 19px;
        font-weight: bold;
    }

</style>
<div class="exampleB">
<div class="example3">
   <center>
      Vendas Hoje<br>
         <span class='destaque-numero'><?php echo $user_venda_hoje; ?></span>
   </center>
</div>
<div class="example3">
   <center>
      Faturamento Hoje<br>
         <span class='destaque-numero'>R$ <?php echo $user_comissao_hoje; ?></span>
   </center>
</div>
<div class="example3">
   <center>
      Vendas no Período<br>
         <span class='destaque-numero'><?php echo $user_vendas; ?></span><br>
         Média de <?php echo $media_vendas_user; ?> por dia
   </center>
</div>
<div class="example3">
   <center>
      Faturamento no Período<br>
         <span class='destaque-numero'>R$ <?php echo $user_comissao; ?></span>
   </center>
</div>
<div class="example3">
   <center>
      Investimento Hoje<br>
         <span class='destaque-numero'>R$ <?php echo $valor_gasto_hoje; ?></span>
   </center>
</div>
<div class="example3">
   <center>
      Investimento no Período<br>
         <span class='destaque-numero'>R$ <?php echo $valor_gasto; ?></span>
   </center>
</div>
<div class="example3">
   <center>
      Devoluções Hoje<br>
         <span class='destaque-numero'><?php echo $user_devolvida_hoje; ?></span>
   </center>
</div>
<div class="example3">
   <center>
      Devoluções no Período<br>
        <span class='destaque-numero'><?php echo $user_devolvida; ?></span>
   </center>
</div>
<div class="example3">
   <center>
      Cartões<br>
      <span class='destaque-numero'><?php echo $user_cartao; ?></span><br>
         Hoje: <?php echo $user_cartao_hoje; ?>
   </center>
</div>
<div class="example3">
   <center>
      Boletos Pagos<br>
      <span class='destaque-numero'><?php echo $user_bpago; ?></span><br>
         Hoje: <?php echo $user_bpago_hoje; ?>
   </center>
</div>
<div class="example3">
   <center>
      Boletos Gerados<br>
      <span class='destaque-numero'><?php echo $user_bimpresso; ?></span><br>
      Hoje: <?php echo $user_bimpresso_hoje; ?>
   </center>
</div>
<div class="example3">
   <center>
      Conversão de Boletos<br>
      <span class='destaque-numero'><?php echo $user_conversao; ?>%</span><br>
   </center>
</div>
<p style="clear: left;"> </p>
</div>

