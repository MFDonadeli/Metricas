<style>
    #exampleB {
        margin: 10px;
    }

    .example3 {
        width: 150px;
        height: 100px;
        background-color: #85b9e9;
        margin-right: 20px;
        float: left;
        box-shadow: 0px 0px 10px #666666;  
    }
</style>
<div id="exampleB">
<div class="example3">
   <center>
      Vendas
      <h3>
         <strong><?php echo $user_vendas; ?></strong>
      </h3>
      <p>
         Média de <?php echo $media_vendas_user; ?> por dia
      </p>
   </center>
</div>
<div class="example3">
   <center>
      Valor Recebido
      <h3>
         <strong>R$ <?php echo $user_comissao; ?></strong>
      </h3>
   </center>
</div>
<p style="clear: left;"> </p>
</div>
<div id="exampleB">
<div class="example3">
   <center>
      Cartões
      <h3>
         <strong><?php echo $user_cartao; ?></strong>
      </h3>
   </center>
</div>
<div class="example3">
   <center>
      Boletos Pagos
      <h3>
         <strong><?php echo $user_bpago; ?></strong>
      </h3>
   </center>
</div>
<div class="example3">
   <center>
      Boletos Gerados
      <h3>
         <strong><?php echo $user_bimpresso; ?></strong>
      </h3>
   </center>
</div>
<div class="example3">
   <center>
      Conversão de Boletos
      <h3>
         <strong><?php echo $user_conversao; ?>%</strong>
      </h3>
   </center>
</div>
<div class="example3">
   <center>
      Devoluções
      <h3>
         <strong><?php echo $user_devolvida; ?></strong>
      </h3>
   </center>
</div>
<p style="clear: left;"></p>
</div>
<div id="exampleB">
<div class="example3">
   <center>
      Vendas Última Semana
      <h3>
         <strong><?php echo $user_venda_7dias; ?></strong>
      </h3>
      <p>
         Média de <?php echo $media_vendas_7dias_user; ?> por dia
      </p>
   </center>
</div>
<div class="example3">
   <center>
      Vendas Últimos 3 dias
      <h3>
         <strong><?php echo $user_venda_3dias; ?></strong>
      </h3>
      <p>
         Média de <?php echo $media_vendas_3dias_user; ?> por dia
      </p>
   </center>
</div>
<p style="clear: left;"></p>
</div>