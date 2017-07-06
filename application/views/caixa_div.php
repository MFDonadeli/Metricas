<div class="div_caixa" id="div_<?php echo $id; ?>">
Nome: <?php echo $name; ?><br>
ID: <?php echo $id; ?><br>
Status: <?php 

if($status == 1)
    echo "ATIVA";
else
    echo "BLOQUEADA";

 ?><br>
</div>