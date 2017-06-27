<?php

include 'mysqldb.php';

$campos = array( 'Dia_da_Semana' => '',
                'Dia' => '',
                'Impressoes' => 'impressions',
                'Frequencia' => 'frequency',
                'Relevancia' => '',
                'Cliques' => 'inline_link_clicks',
                'Investimento' => 'spend',
                'CTR' => 'inline_link_click_ctr',
                'CPC' => 'cost_per_inline_link_click',
                'CPM' => 'cpm',
                'Visualizou_Conteudo' => '',
                'Custo_por_Visualizacao_de_Conteudo' => '',
                'Iniciou_Compra' => '',
                'Custo_por_Iniciou_Compra' => '',
                '%_Visualizou_-_Iniciou_Compra' => '',
                'Impressoes-IniciouCompra' => '',
                'Cliques-IniciouCompra' => '',
                'Compras' => '',
                'Custo_por_Compra' => '',
                '%_Visualizou_-_Comprou' => '',
                '%_Iniciou_Compra_-_Comprou' => '',
                'Impressoes-Compra' => '',
                'Cliques-Compra' => '',
                'EventoPersonalizado' => '',
                'Cartao' => '',
                'Boleto' => '',
                'Compensados' => '',
                'Total' => '',
                'Comissao' => '',
                'ROI' => '');

$campos_table = array( 'Dia_da_Semana' => 'diadasemana',
                'Dia' => 'dia',
                'Impressoes' => 'impressoes',
                'Frequencia' => 'frequencia',
                'Relevancia' => 'relevancia',
                'Cliques' => 'cliques',
                'Investimento' => 'investimento',
                'CTR' => 'ctr',
                'CPC' => 'cpc',
                'CPM' => 'cpm',
                'Visualizou_Conteudo' => 'visualizou_conteudo',
                'Custo_por_Visualizacao_de_Conteudo' => 'custo_visualizacao',
                'Iniciou_Compra' => 'iniciou_compra',
                'Custo_por_Iniciou_Compra' => 'custo_iniciou',
                '%_Visualizou_-_Iniciou_Compra' => 'visualizou_iniciou',
                'Impressoes-IniciouCompra' => 'impressoes_iniciou',
                'Cliques-IniciouCompra' => 'cliques_iniciou',
                'Compras' => 'compras',
                'Custo_por_Compra' => 'custo_comprou',
                '%_Visualizou_-_Comprou' => 'visualizou_comprou',
                '%_Iniciou_Compra_-_Comprou' => 'iniciou_comprou',
                'Cliques-Compra' => 'cliques_compra',
                'Impressoes-Compra' => 'impressoes_compra',
                'Cartao' => 'compra_cartao',
                'Boleto' => 'compra_boleto',
                'Compensados' => 'boleto_compensado',
                'Total' => 'total',
                'Comisao' => 'comissao',
                'ROI' => 'roi');

$campos_class = array( 
                'Custo_por_Visualizacao_de_Conteudo' => 'vc_metrica',
                'Custo_por_Iniciou_Compra' => 'ic_metrica',
                '%_Visualizou_-_Iniciou_Compra' => 'ic_metrica',
                'Impressoes-IniciouCompra' => 'ic_metrica',
                'Cliques-IniciouCompra' => 'ic_metrica',
                'Custo_por_Compra' => 'pc_metrica',
                '%_Visualizou_-_Comprou' => 'pc_metrica',
                '%_Iniciou_Compra_-_Comprou' => 'pc_metrica',
                'Impressoes-Compra' => 'pc_metrica',
                'Cliques-Compra' => 'pc_metrica');

$campos_botao = array(
                'Visualizou_Conteudo' => '+',
                'Iniciou_Compra' => '+',
                'Compras' => '+');

$diasemana = array('Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sabado');

function fill_array($array, $conversions = null)
{
    if(isset($array['cpc']))
    {
        $diasemana = array('Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sabado');

        $date = DateTime::createFromFormat('Y-m-d', $array['date_start']);
        $array_output["Dia_da_Semana"] = $diasemana[$date->format('w')];
        $array_output["Dia"] = $date->format('d/m');
    }
    else
    {
        $array_output["Dia_da_Semana"]= 'Geral';
        $array_output["Dia"] = "Geral";
    }

    $array_output["Impressoes"] = $array['impressions'];
    $array_output["Frequencia"] = $array['frequency'];

    if(array_key_exists('relevance_score', $array))
    {
        foreach($array['relevance_score'] as $key => $val)
        {
            if($val == 'NOT_ENOUGH_IMPRESSIONS')
                $val = '-';
            $array_output["Relevancia"] = $val;
            break;
        }
    }

    $array_output["Cliques"] = $array['inline_link_clicks'];
    $array_output["Investimento"] = $array['spend'];
    $array_output["CTR"] = round(floatval($array['inline_link_click_ctr']),2);
    $array_output["CPC"] = '' . round(floatval($array['cost_per_inline_link_click']),2);
    $array_output["CPM"] = '' . round(floatval($array['cpm']),2);

    foreach($array['actions'] as $action)
    {
        if($action['action_type'] == 'offsite_conversion.fb_pixel_view_content')
        { 
            $array_output["Visualizou_Conteudo"] = intval($action['value']);
        }
        elseif($action['action_type'] == 'offsite_conversion.fb_pixel_initiate_checkout')
        {
            $array_output["Iniciou_Compra"] = intval($action['value']);           
        }
        elseif($action['action_type'] == 'offsite_conversion.fb_pixel_purchase')
        {
            $array_output["Compras"] = intval($action['value']);                                                     
        }
        elseif($action['action_type'] == 'offsite_conversion.fb_pixel_custom')
        {
            $array_output["Evento Customizado"] = intval($action['value']); 
        }
        elseif(strpos($action['action_type'],'offsite_conversion.custom.') !== false)
        {
            $custom_id = str_replace('offsite_conversion.custom', '', $action['action_type']);
            $key = array_search($custom_id, array_column($conversions, 'id'));
            $custom_nome = $conversions[$key]['name'];
            //$array_output["EventoPersonalizado"][$custom_nome]['valor'] = round(floatval($action['value']),2);
            //$array_output["EventoPersonalizado"][$custom_nome]['ACadaImpressao'] = round(($array_output["Impressoes"] / floatval($action['value'])), 0);
            //$array_output["EventoPersonalizado"][$custom_nome]['ACadaClique'] = round(($array_output["Cliques"] / floatval($action['value'])), 0); 
        }
    }

    if(array_key_exists("Impressoes", $array_output) && array_key_exists("Iniciou_Compra",$array_output)) 
        $array_output["Impressoes-IniciouCompra"] = round(($array_output["Impressoes"] / $array_output["Iniciou_Compra"]), 0);  
    if(array_key_exists("Impressoes", $array_output) && array_key_exists("Compras",$array_output)) 
        $array_output["Impressoes-Compra"] = round(($array_output["Impressoes"] / $array_output["Compras"]), 0);  
    if(array_key_exists("Cliques", $array_output) && array_key_exists("Iniciou_Compra",$array_output)) 
        $array_output["Cliques-IniciouCompra"] = round(($array_output["Cliques"] / $array_output["Iniciou_Compra"]), 0);  
    if(array_key_exists("Cliques", $array_output) && array_key_exists("Compras",$array_output))     
        $array_output["Cliques-Compra"] = round(($array_output["Cliques"] / $array_output["Compras"]), 0);                            

    if(isset($array_output["Visualizou_Conteudo"]) && isset($array_output["Iniciou_Compra"])) 
        $array_output["%_Visualizou_-_Iniciou_Compra"] = round(($array_output["Iniciou_Compra"] / $array_output["Visualizou_Conteudo"]) * 100, 2) . ' %';        
    if(isset($array_output["Visualizou_Conteudo"]) && isset($array_output["Compras"]))     
        $array_output["%_Visualizou_-_Comprou"] = round(($array_output["Compras"] / $array_output["Visualizou_Conteudo"]) * 100, 2) . ' %';
    if(isset($array_output["Iniciou_Compra"]) && isset($array_output["Compras"]))
        $array_output["%_Iniciou_Compra_-_Comprou"] = round(($array_output["Compras"] / $array_output["Iniciou_Compra"]) * 100, 2) . ' %'; 

    foreach($array['cost_per_action_type'] as $action)
    {
        if($action['action_type'] == 'offsite_conversion.fb_pixel_view_content')
        { 
            $array_output["Custo_por_Visualizacao_de_Conteudo"] = round(floatval($action['value']),2);
        }
        elseif($action['action_type'] == 'offsite_conversion.fb_pixel_initiate_checkout')
        {
            $array_output["Custo_por_Iniciou_Compra"] = round(floatval($action['value']),2);
        }
        elseif($action['action_type'] == 'offsite_conversion.fb_pixel_purchase')
        {
            $array_output["Custo_por_Compra"] = round(floatval($action['value']),2);    
        }
        elseif(strpos($action['action_type'],'offsite_conversion.custom.') !== false)
        {
            $custom_id = str_replace('offsite_conversion.custom', '', $action['action_type']);
            $key = array_search($custom_id, array_column($conversions, 'id'));
            $custom_nome = $conversions[$key]['name'];
            //$array_output["EventoPersonalizado"][$custom_nome]['Custo'] = round(floatval($action['value']),2);
        }

    }

    //Eventos Personalizados
    if(isset($evento_personalizado))
    {

    }
    //if(array['custom_event_type'] == 'CONTENT_VIEW')
            //else if(array['custom_event_type'] == 'PURCHASE')
            //else if(array['custom_event_type'] == 'INITIATED_CHECKOUT')

    return $array_output;
}

function saveMetricaToBD($dia,$id)
{
    $db = new MyDB();
    echo $db->lastErrorMsg();
    //if(!$db)
    {
        $ret = $db->createTableMetrics($id);
        $ret = $db->execSQLQuery("SELECT COUNT(*) as c FROM t" . $id . " WHERE Dia = '" . $dia . "';");
        if($ret)
        {
            $row = $ret->fetchArray();
            if($row[0] == 0)
            {
                $ret = $db->execSQL("INSERT INTO t" . $id . " (Dia) VALUES ('" . $dia . "');");
            }
        }       
    }
}

function insert_metricas($id, $array)
{
    foreach($array as $key => $value)
    {
        $str_campos[] = $campos_table['$key'];
        $str_valores[] = $value;
    }

    $sql = "INSERT INTO " . $id . " " . implode(',',$str_campos) . " VALUES (" . implode(',',$str_valores) .");";

    $db = new MyDB();
    if(!$db){
        echo $db->lastErrorMsg();
    } 
    else {
        $db->createTableMetrics($id);
        $db->execSQL($sql);
    }
    $db->close();
}

function get_metricas($id)
{
    $db = new MyDB();
    if(!$db){
        echo $db->lastErrorMsg();
    } 
    else {
        $ret = $db->execSQL('SELECT * FROM ' . $id);
    }
}

function insights_simples($array)
{
    foreach($array['actions'] as $action)
    {
        if($action['action_type'] == 'offsite_conversion.fb_pixel_view_content')
        { 
            $array_output["Visualizou_Conteudo"] = intval($action['value']);
        }
        elseif($action['action_type'] == 'offsite_conversion.fb_pixel_initiate_checkout')
        {
            $array_output["Iniciou_Compra"] = intval($action['value']);
        }
        elseif($action['action_type'] == 'offsite_conversion.fb_pixel_purchase')
        {
            $array_output["Compras"] = intval($action['value']);  
        }
    }  

    $array_output["CTR"] = round(floatval($array['inline_link_click_ctr']),2);
    $array_output["CPC"] = 'R$ ' . round(floatval($array['cost_per_inline_link_click']),2);

    $str='';
    foreach($array_output as $key => $value)
    {
        $str .= $key . ': ' . $value . '<br>';
    }
    return $str;  
}

function getAnuncioConfig($id)
{
    $db = new MyDB();
    $ret = $db->execSQLQuery('SELECT configuracoes, tag, link, adset, campanha FROM anuncios WHERE id = "'. $id . '";'); 

    $row = $ret->fetchArray();
    return $row[3] . '<br>' . $row[4] . '<br>' . $row[0] . '<br>' . $row[1] . '<br>' . $row[2]; 
}
?>