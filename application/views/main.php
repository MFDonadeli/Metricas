<html>
    <body>
        <h1>Bem vindo ao Métricas</h1>
        <?php if($this->session->userdata('logged_in')): ?>
        Logado!
        <?php else: ?>
        <a href='<?php echo $authUrl; ?>'><img width='20%' src='<?php echo base_url(); ?>assets/facebook.png'></a>   
        <?php endif; ?>     
    </body>
</html>
