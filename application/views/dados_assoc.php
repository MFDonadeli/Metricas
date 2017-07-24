<h3>Cartões:</h3>
<div style="height: 200px;overflow-x: scroll;">
    <table border=1>
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
    </table>
</div>
Associar a: 
<select id="cmbCartao">
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
<button id='btnCartoes' class='btnAssociar'>Salvar</button>

<h3>Boletos Pagos:</h3>
<div style="height: 200px;overflow-x: scroll;">
    <table border=1>
    <?php
        foreach($compras['boleto_pago'] as $compra)
        {
    ?>
            <tr>
                <td><input type="checkbox" class="chkBoletoPago" id='<?php echo $compra->id_plataforma ?>'></td>
                <td><?php echo $compra->data_compra; ?></td>
                <td><?php echo $compra->data_confirmacao; ?></td>
                <td><?php echo $compra->produto; ?></td>
                <td><?php echo $compra->src; ?></td>
            </tr>
    <?php
        }
    ?>
    </table>
</div>
Associar a: 
<select id="cmbBoletoPago">
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
<button id='btnBoletosPagos' class='btnAssociar'>Salvar</button>

<h3>Boletos Gerados:</h3>
<div style="height: 200px;overflow-x: scroll;">
    <table border=1>
    <?php
        foreach($compras['boleto_impresso'] as $compra)
        {
    ?>
            <tr>
                <td><input type="checkbox" class="chkBoletoGerado" id='<?php echo $compra->id_plataforma ?>'></td>
                <td><?php echo $compra->data_compra; ?></td>
                <td><?php echo $compra->data_confirmacao; ?></td>
                <td><?php echo $compra->produto; ?></td>
                <td><?php echo $compra->src; ?></td>
            </tr>
    <?php
        }
    ?>
    </table>
</div>
Associar a: 
<select id="cmbBoletoGerado">
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
<button id='btnBoletosGerados' class='btnAssociar'>Salvar</button>
