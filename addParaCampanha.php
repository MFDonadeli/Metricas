<?php
require_once __DIR__ . '/vendor/autoload.php';
include 'functions.php';

use Facebook\Facebook;
use Facebook\FacebookApp;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\FacebookRequest;

use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Fields\AdSetFields;

use FacebookAds\Object\Fields\AdFields;
use FacebookAds\Object\Fields\AdsInsightsFields;

use FacebookAds\Api;
use FacebookAds\ApiRequest;
use FacebookAds\Http\RequestInterface;

use FacebookAds\Object\Ad;

$db = new MyDB();
?>

<head>
  <link rel="stylesheet" href="styles.css">
  <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
</head>

<?php
    if(isset($_GET['tipo']) && isset($_GET['id']))
    {
        $tipo = $_GET['tipo'];
        $id = explode(',',$_GET['id']);        
        if($tipo == 'campanha')
        {
            $consulta = '?fields=name,status,account_id,ads{name,id,status,adset{name,id,status}}';
        }
        else if($tipo == 'adset')
        {
            $consulta = '?fields=ads{name,status,id,campaign{name,id,status}},id,name,status, account_id';
        }
        else if($tipo == 'anuncio')
        {
            $consulta = '?fields=name,id,status,campaign{name,id,status},adset{name,id,status},account_id';
        }
        else
        {
            die('Tipo Inválido');
        }
        print_r($id);
    }
    else
        die('Nenhum tipo selecionado');

    if (!session_id()) {
        session_start();
    }

    if($_SESSION['facebook_access_token'])
    {
        foreach($id as $id_id)
        {
            $response = $_SESSION['fb']->get($id_id . $consulta, $_SESSION['facebook_access_token']);  
            $feedEdge = $response->getGraphNode(); 
            $informacao = $feedEdge->asArray();
            foreach ($feedEdge as $status) 
            {
                if(!is_string($status))
                    $adsid = $status->asArray();
            }                 
        }

        if($tipo == 'campanha')
        {
        //Id anuncio, nome anuncio, Id Adset, nome Adset, Id campanha, nome Campanha, id Conta
        //Anuncio x ID:... Status: x no Grupo de Anuncios x ID:... Status: x na Campanha x ID:... Status: x na Conta: x           
            foreach($adsid as $ids)
            { 
                $options[$ids['id']] = "Anúncio " . $ids['name'] . " ID: " . $ids['id'] . " Status: " . $ids['status'] . 
                 " no Grupo de Anúncios: " . $ids['adset']['name'] . " ID: " . $ids['adset']['id'] . " Status: " . $ids['adset']['status'] .
                 " na Campanha: " . $informacao['name'] . " ID: " . $informacao['id'] . " Status: " . $informacao['status'] . 
                 " na Conta: " . $informacao['account_id'];  
            }
        }
        else if($tipo == 'adset')
        {
            foreach($adsid as $ids)
            {
                $options[$ids['id']] = "Anúncio " . $ids['name'] . " ID: " . $ids['id'] . " Status: " . $ids['status'] . 
                 " no Grupo de Anúncios: " . $informacao['name'] . " ID: " . $informacao['id'] . " Status: " . $informacao['status'] .
                 " na Campanha: " . $ids['campaign']['name'] . " ID: " . $ids['campaign']['id'] . " Status: " . $ids['campaign']['status'] . 
                 " na Conta: " . $informacao['account_id'];
            }
        }
        else if($tipo == 'anuncio')
        {
            foreach($adsid as $ids)
            {
                $options[$informacao['id']] = "Anúncio " . $informacao['name'] . " ID: " . $informacao['id'] . " Status: " . $informacao['status'] . 
                 " no Grupo de Anúncios: " . $informacao['adset']['name'] . " ID: " . $informacao['adset']['id'] . " Status: " . $informacao['adset']['status'] .
                 " na Campanha: " . $informacao['campaign']['name'] . " ID: " . $informacao['campaign']['id'] . " Status: " . $informacao['campaign']['status'] . 
                 " na Conta: " . $informacao['account_id']; 
            }
        }
    }
?>

<h3>Incluir para campanha</h3>
<button id='novo'>Nova Campanha</button>
<button id='existente'>Adicionar para Campanha Existente</button>
<div id='div_novo' style="display: none;">
    Digite o nome da Campanha: <input id='txtnomecampanha' type="text"><br>
    Selecione o produto: 
    <select name="" id="produto">
        <?php
            $ret = $db->execSQLQuery('select * from produto;');
            while ($row = $ret->fetchArray())
                echo "<option value=" . $row['id'] . ">" . $row['nome'] . "</option>";
        ?>
    </select><br>
    Anúncios a serem adicionados: 
    <select name="" id="listaAnunciosAdd" multiple>
        <?php
            foreach($options as $key => $val)
            {
                echo "<option id=" . $key . ">" . $val . "</option>";
            }
        ?>
    </select>
    <button id='btn_novo_remover'>Remover Item</button><br>
    <button id='btn_novo_ok'>OK</button>
    <button id='btn_novo_cancelar'>Cancelar</button>
</div>
<div id='div_existente' style="display: none;">
    Escolha a campanha para adicionar: 
    <select name="" id="campanhas">
        <option value="">--SELECIONE--</option>
        <?php
            $ret = $db->execSQLQuery('select * from campanha;');
            while ($row = $ret->fetchArray())
                echo "<option value=" . $row['id'] . ">" . $row['nome'] . "</option>";
        ?>
    </select><br>
    Anúncios a serem adicionados: 
    <select name="" id="listaAnunciosAddUpd" multiple>
        <?php
            foreach($options as $key => $val)
            {
                echo "<option value=" . $key . ">" . $val . "</option>";
            }
        ?>
    </select>
    <button id='btn_novo_remover'>Remover Item</button><br>    
    Anúncios já existentes: 
    <select name="" id="listaAnuncios" multiple>
    </select><br>    
    <button id='btn_existente_ok'>OK</button>
    <button id='btn_existente_cancelar'>Cancelar</button>
</div>

<script>
    $('#novo').click(function(){
        $('#div_novo').show();
    });

    $('#existente').click(function(){
        $('#div_existente').show();
    });

    $('#btn_novo_ok').click(function(){
        var options = $('#listaAnunciosAdd option');
        var values = $.map(options ,function(option) {
            return option.value;
        });
        var ids = $.map(options ,function(option) {
            return option.id;
        });

        var form_data = {
            nome_campanha: $('#txtnomecampanha').val(),
            produto: $('#produto').val(),
            anuncios: ids,
            conteudo: values 
        };

        var resp = $.ajax({
            url: 'set_campanha.php',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                var obj = $.parseJSON(msg);
            }
    });
        $('#div_novo').hide();
    });

    $('#btn_novo_cancelar').click(function(){
        $('#div_novo').hide();
    });

    $('#btn_existente_ok').click(function(){
        var options = $('#listaAnunciosAddUpd option');
        var ids = $.map(options ,function(option) {
            return option.value;
        });
        var values = $.map(options ,function(option) {
            return option.text;
        });

        var options = $('#listaAnuncios option');
        var ids_existente = $.map(options ,function(option) {
            return option.value;
        });
        var val_existente = $.map(options ,function(option) {
            return option.text;
        });

        var form_data = {
            id_campanha: $('#campanhas').val(),
            anuncios: ids,
            conteudo: values,
            existentes: ids_existente,
            descexiste: val_existente 
        };

        var resp = $.ajax({
            url: 'set_campanha.php',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                var obj = $.parseJSON(msg);
            }
    });
        $('#div_existente').hide();
    });

    $('#btn_existente_cancelar').click(function(){
        $('#div_existente').hide();
    });

    $('#campanhas').change(function(){
        var form_data = {
            id_campanha: $(this).val()
        };

        var resp = $.ajax({
            url: 'get_ads_campanha.php',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                $('#listaAnuncios').empty();
                var obj = $.parseJSON(msg);
                for (i = 0; i < obj.length; i++) {
                    $('#listaAnuncios')
                        .append($('<option></option>')
                        .attr('value', obj[i].key)
                        .text(obj[i].val));
                } 
            }
        });
    });
    
</script>