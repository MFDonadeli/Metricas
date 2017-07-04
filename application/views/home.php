<?php

if(!empty($authUrl)) {
    echo '<a href="'.$authUrl.'"><img src="'.base_url().'assets/facebook.png" alt=""/></a>';
}else{
?>
<div class="wrapper">
    <h1>Métricas</h1>
    <div class="welcome_txt">Bem-Vindo <b><?php echo $userData['first_name']; ?></b></div>

    <div class="fb_box">
        <p><b>Sair do <a href="<?php echo $logoutUrl; ?>">Facebook</a></b></p>
    </div>
</div>
    <?php
    if(!$contas): ?>
        <h2>Sem Contas Sincronizadas</h2>
        <button>Sincronizar Contas</button>
    <?php
    else: ?>
        <button>Adicionar Contas</button>
    <?php 
        foreach($contas as $contas_item):?>
            <div class='container'>
                <strong><?php echo $contas_item->name; ?></strong><br>
                ID: <?php echo $contas_item->id; ?><br>
            </div>
        <?php 
        endforeach;
     endif;    
     ?>


<?php } ?>