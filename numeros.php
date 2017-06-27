<head>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js"></script>
  <style>
    /*#tabela_metricas {
      overflow-x:scroll;  
      margin-left:18em;
    }
    .fixa {
      position:absolute;
      width:18em;
      left:0;
    }*/
  </style>
</head>

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

session_start();

//23842607185100099/insights?breakdowns=age,gender
//23842607185100099/insights?breakdowns=region
//

if($_SESSION['facebook_access_token'] && $_GET['id'])
{
    $db = new MyDB();
    $anuncio_config = '';

    if(!isset($_SESSION['numeros'.$_GET['id']]) || isset($_GET['refresh']))
    {
        $response = $_SESSION['fb']->get($_GET['id'] . "/insights?fields=actions,ad_name,campaign_name,cost_per_inline_link_click,cost_per_action_type,cpm,frequency,impressions,inline_link_click_ctr,inline_link_clicks,reach,relevance_score,spend,website_clicks,website_ctr,ad_id,date_start", $_SESSION['facebook_access_token']);

        $feedEdge = $response->getGraphEdge();
    
        foreach ($feedEdge as $status) {
            $metricas = $status->asArray();
        }

        $date = date('Y-m-d', time());

        $response = $_SESSION['fb']->get($_GET['id'] . "/insights?fields=actions,ad_name,campaign_name,cost_per_inline_link_click,cost_per_action_type,cpm,frequency,impressions,inline_link_click_ctr,inline_link_clicks,reach,relevance_score,spend,website_clicks,website_ctr,ad_id,date_start,cpc&time_range={'since':'" . $metricas['date_start'] . "','until':'" . $date . "'}&time_increment=1&limit=1000", $_SESSION['facebook_access_token']);

        $feedEdge = $response->getGraphEdge();

        foreach ($feedEdge as $status) {
            $metricas_por_dia[] = $status->asArray();
        }
    }
    else
    {
        $metricas = $_SESSION['numeros'.$_GET['id']];
        $metricas_por_dia = $_SESSION['numerospd'.$_GET['id']];     
    }

    //Custom Conversions
    $ret = $db->execSQLQuery('SELECT id_conta FROM anuncios WHERE id = "'. $_GET['id'] . '";'); 

    $row = $ret->fetchArray();

    if(!empty($row[0]))
    {
      $response = $_SESSION['fb']->get($row[0] . "/customconversions?fields=custom_event_type,name", $_SESSION['facebook_access_token']);
      $feedEdge = $response->getGraphEdge();
    }

    foreach($feedEdge as $status)
    {
        $conversions[] = $status->asArray();
    }
    ///

    $arr_resposta = fill_array($metricas, $conversions);

    if($_GET['tipo'] == 'ad')
    {
      $anuncio_config = getAnuncioConfig($_GET['id']);
      $tem_anuncio = $db->checkAnuncio($_GET['id']);
      
      if(!$tem_anuncio)
      {
        $response = $_SESSION['fb']->get($_GET['id'] . "?fields=adset{id,name},campaign{id,name},name,account_id", $_SESSION['facebook_access_token']);

        $feedEdge = $response->getGraphNode();
    
        $ad = $feedEdge->asArray(); 

        $info['adset'] = $ad['adset']['name'];
        $info['id_adset'] = $ad['adset']['id'];
        $info['campanha'] = $ad['campaign']['name'];
        $info['id_campanha'] = $ad['adset']['id'];
        $info['id'] = $_GET['id'];
        $info['nome'] = $ad['name'];
        $info['id_conta'] = 'act_' . $ad['account_id'];

        $db->addAnuncio($info);
      }

      saveMetricaToBD($arr_resposta['Dia'], $_GET['id']);      
    }
    $array_output[] = $arr_resposta;
    
    foreach($metricas_por_dia as $metricas_dia)
    {
        $arr_resposta = fill_array($metricas_dia, $conversions);
        if($_GET['tipo'] == 'ad') saveMetricaToBD($arr_resposta['Dia'], $_GET['id']);
        $array_output[] = $arr_resposta;
    }

    $ret = $db->execSQLQuery('select * from produto;');
    echo "<select id='produtos'>";
    echo "<option>--SELECIONE UM PRODUTO--</option>";
    while ($row = $ret->fetchArray())
    {
      echo "<option value='" . $row['id'] . "'>" . $row['nome'] . "</option>";
    }
    echo "</select>";

    echo "<input type='checkbox' class='checkdia' value='Todos'>Todos";
    foreach($diasemana as $dia)
    {
      echo "<input type='checkbox' class='checkdia' value='" . $dia . "'>" . $dia;
    }

    echo "<button class='info'>Info</button>";

    $metricas_valores = $db->getMetricas($_GET['id']);

    
    //var_dump($array_output);
    echo "<div id='tabela_metricas'>";
    echo "<table border=1>";

    foreach($campos as $key => $val)
    {
        echo "<tr>";

        if(isset($campos_class[$key]))
          $class = $campos_class[$key];
        else
          $class = '';

        echo "<td class='fixa " . $class . "'> " . $key;

        if(isset($campos_botao[$key]))
        {
          echo "<button class='btn_" . $key . "'>" . $campos_botao[$key] . "</button>";
        }

        echo "</td>";
        for($i=0; $i<count($array_output); $i++) 
        {
          $valor = '';
          if(isset($campos_class[$key]))
            $class = $campos_class[$key];
          else
            $class = '';

          switch($key)
          {
            case 'Cartao':
            case 'Boleto':
            case 'Compensados':
            case 'Total':
            case 'Comissao':
            case 'ROI':
              if(array_key_exists($key, $array_output[$i]))
                $array_output[$i][$key] = $metricas_valores[$array_output[$i]['Dia']][$key];
          }

            if(isset($array_output[$i][$key]))
            {
              $valor = $array_output[$i][$key];                                   
            }
            else
                $valor = '-';

            echo "<td contenteditable='true' class='" . $array_output[$i]['Dia_da_Semana'] . " " . $class . " " . $key ."' data-date='" . $array_output[$i]['Dia'] . "'>" . $valor . "</td>"; 
        }
        echo "</tr>";    
    }
    echo "</table>";

}
?>

<div style='float: left;'><canvas id="cpcChart" width="50" height="50"></canvas></div>
<div style='float: left;'><canvas id="cpmChart" width="50" height="50"></canvas></div>
<div style='float: left;'><canvas id="ctrChart" width="50" height="50"></canvas></div>
<div style='float: left;'><canvas id="comprasChart" width="50" height="50"></canvas></div>


<script>
  var produto;                                
  var comissao;                
  var cpm_minimo;
  var cliques_minimo;
  var cpc_minimo;
  var ctr_minimo;
  var impressao_minimo;
  var cpm_medio;
  var cliques_medio;
  var cpc_medio;
  var ctr_medio;
  var impressao_medio;  
  var cpm_maximo;
  var cliques_maximo;
  var cpc_maximo;
  var ctr_maximo;
  var impressao_maximo;

  $('.btn_Visualizou_Conteudo').click(function(){
    if($('.vc_metrica').is(":visible"))
    {
      $('.vc_metrica').hide();
      $('.btn_Visualizou_Conteudo').text('+');
    }
    else
    {
      $('.vc_metrica').show();
      $('.btn_Visualizou_Conteudo').text('-');
    }
  });

  $('.btn_Iniciou_Compra').click(function(){
    if($('.ic_metrica').is(":visible"))
    {
      $('.ic_metrica').hide();
      $('.btn_Iniciou_Compra').text('+');
    }
    else
    {
      $('.ic_metrica').show();
      $('.btn_Iniciou_Compra').text('-');
    }
  });

  $('.btn_Compras').click(function(){
    if($('.pc_metrica').is(":visible"))
    {
      $('.pc_metrica').hide();
      $('.btn_Compras').text('+');
    }
    else
    {
      $('.pc_metrica').show();
      $('.btn_Compras').text('-');
    }
  });

  $('#produtos').change(function(){
    var form_data = {
            id_produto: $(this).val()
        };

        var resp = $.ajax({
            url: 'get_metricas_produto.php',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                var obj = $.parseJSON(msg);
                produto = obj.txtnome;                                
                comissao = obj.comissao;                
                cpm_minimo = obj.cpm_minimo;
                cliques_minimo = obj.cliques_minimo;
                cpc_minimo = obj.cpc_minimo;
                ctr_minimo = obj.ctr_minimo;
                impressao_minimo = obj.impressao_minimo;
                cpm_medio = obj.cpm_medio;
                cliques_medio = obj.cliques_medio;
                cpc_medio = obj.cpc_medio;
                ctr_medio = obj.ctr_medio;
                impressao_medio = obj.impressao_medio;  
                cpm_maximo = obj.cpm_maximo;
                cliques_maximo = obj.cliques_maximo;
                cpc_maximo = obj.cpc_maximo;
                ctr_maximo = obj.ctr_maximo;
                impressao_maximo = obj.impressao_maximo; 
            }
    });
  });
</script>

<script>
  $( function() {
    $( document ).tooltip({
        items: "td, button",
        content: function() {
            var element = $(this);
            var id = $(this).attr('id');

            if( element.hasClass( "info" ) )
            {
               return '<?php echo $anuncio_config; ?>';
            }
            else if( element.hasClass( "cpm" ) )
            {
                return 'CPM mínimo: ' + cpm_minimo + '<br>CPM médio: ' + cpm_medio + '<br>CPM máximo: ' + cpm_maximo;
            }
            else if( element.hasClass( "cpc" ) )
            {
                return 'CPC mínimo: ' + cpc_minimo + '<br>CPC médio: ' + cpc_medio + '<br>CPC máximo: ' + cpc_maximo;
            }
            else if( element.hasClass( "ctr" ) )
            {
                return 'CTR mínimo: ' + ctr_minimo + '<br>CTR médio: ' + ctr_medio + '<br>CTR máximo: ' + ctr_maximo;
            }
            else if( element.hasClass( "impressoes" ) )
            {
                return 'Impressoes mínimo: ' + impressao_minimo + '<br>Impressoes médio: ' + impressao_medio + '<br>Impressoes máximo: ' + impressao_maximo;
            }
            else if( element.hasClass( "cliques" ) )
            {
                return 'Cliques mínimo: ' + cliques_minimo + '<br>Cliques médio: ' + cliques_medio + '<br>Cliques máximo: ' + cliques_maximo;
            }
        }
    });
  });

  $('.checkdia').click(function() {
    var v = $(this).val();
    var check = $(this).prop('checked');

    if(v == 'Todos' && check){
      $('.checkdia').prop('checked', true);
      return; 
    } 
    
    if(check)
      $('.' + v).show();
    else
      $('.' + v).hide();

  });

  function update_campo(campo, valor, where)
  {
    var form_data = {
            campo: campo,
            valor: valor,
            where: where,
            id: <?php echo "'" . $_GET['id'] . "'"; ?> 
        };

        var resp = $.ajax({
            url: 'update_metrica.php',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                
            }
        });
  }

 function altera_geral(campo)
 {
   var total = 0;
    $(campo).each(function(){
      if($(this).attr('data-date') != 'Geral' && $(this).text() != '-')
      {
        total += parseInt($(this).text());
      }
    });

    $(campo+'.Geral').text(total);
 }

 function altera_Total_ROI_geral()
 {
   var total = 0;
    $('.Total').each(function(){
      if($(this).attr('data-date') != 'Geral' && $(this).text() != '-')
      {
        total += parseFloat($(this).text());
      }
    });

    var Investimento = $('.Investimento.Geral').text();
    $('.Total.Geral').text(total);
    ROI = ((total - parseFloat(Investimento)) / parseFloat(Investimento))*100;
    $('.ROI.Geral').text(ROI.toFixed(2));

    update_campo('ROI', $('.ROI.Geral').text(), 'Geral');    
    update_campo('total', $('.Total.Geral').text(), 'Geral');    
 }

 function calculaTotalRoi(data)
 {
  var Total = 0;
  var ROI = 0;
  var Cartao = $(".Cartao[data-date='" + data + "']").text();  
  var Compensado = $(".Compensados[data-date='" + data + "']").text();  
  var Comissao = $(".Comissao[data-date='" + data + "']").text();  
  var Investimento = $(".Investimento[data-date='" + data + "']").text();  
  
  if(!isNaN(Comissao) && Comissao != '')
  {
    if(isNaN(Cartao) || Cartao == '') Cartao = 0;
    if(isNaN(Compensado) || Compensado == '') Compensado = 0;
    Total = (parseInt(Cartao) + parseInt(Compensado)) * parseFloat(Comissao);
    ROI = ((Total - parseFloat(Investimento)) / parseFloat(Investimento))*100;
  }

  $(".Total[data-date='" + data + "']").text(Total);
  $(".ROI[data-date='" + data + "']").text(ROI.toFixed(2));

    update_campo('ROI', $(".ROI[data-date='" + data + "']").text(), data);    
    update_campo('total', $(".Total[data-date='" + data + "']").text(), data);    
  

  altera_Total_ROI_geral();

 }

  $('.Cartao').on('focusout', function() {
    altera_geral('.Cartao');
    calculaTotalRoi($(this).attr('data-date'));
    update_campo('cartao', $(this).text(), $(this).attr('data-date'));    
    update_campo('cartao', $('.Cartao.Geral').text(), 'Geral'); 
  });

  $('.Boleto').on('focusout', function() {
    altera_geral('.Boleto');
    update_campo('boleto', $(this).text(), $(this).attr('data-date'));    
    update_campo('boleto', $('.Boleto.Geral').text(), 'Geral'); 
  });

  $('.Compensados').on('focusout', function() {
    altera_geral('.Compensados');
    calculaTotalRoi($(this).attr('data-date'));
    update_campo('compensados', $(this).text(), $(this).attr('data-date'));    
    update_campo('compensados', $('.Compensados.Geral').text(), 'Geral'); 
  });

  $('.Comissao').on('focusout', function() {
    update_campo('comissao', $(this).text(), $(this).attr('data-date'));     
  });

  $( document ).ready(function() {
      $('.vc_metrica').hide();
      $('.ic_metrica').hide();
      $('.pc_metrica').hide();
      $('.checkdia').prop('checked',true);
      altera_Total_ROI_geral();

      var labels_dias_cpc = [];
      var data_cpc = [];
      var labels_dias_ctr = [];
      var data_ctr = [];
      var labels_dias_cpm = [];
      var data_cpm = [];
      var labels_dias_compras = [];
      var data_compras = [];

      $('.CPC').each(function(){
        if($(this).attr('data-date') != 'Geral')
        {
          labels_dias_cpc.push($(this).attr('data-date'));
          data_cpc.push($( this ).text())
        }
      });
      $('.CTR').each(function(){
        if($(this).attr('data-date') != 'Geral')
        {
          labels_dias_ctr.push($(this).attr('data-date'));
          data_ctr.push($( this ).text())
        }
      });
      $('.CPM').each(function(){
        if($(this).attr('data-date') != 'Geral')
        {
          labels_dias_cpm.push($(this).attr('data-date'));
          data_cpm.push($( this ).text())
        }
      });
      $('.Compras').each(function(){
        if($(this).attr('data-date') != 'Geral')
        {
          labels_dias_compras.push($(this).attr('data-date'));
          data_compras.push($( this ).text())
        }
      });

      //cpc chart
        var ctx = document.getElementById("cpcChart").getContext('2d');
        ctx.canvas.width = 500;
        ctx.canvas.height = 300;
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels_dias_cpc,
                datasets: [{
                    label: 'CPC: ',
                    data: data_cpc,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255,99,132,1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                }
            }
        });
      //
      //ctr chart
        var ctx = document.getElementById("ctrChart").getContext('2d');
        ctx.canvas.width = 500;
        ctx.canvas.height = 300;
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels_dias_ctr,
                datasets: [{
                    label: 'CTR %:',
                    data: data_ctr,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255,99,132,1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                }
            }
        });
      //
      //cpm chart
        var ctx = document.getElementById("cpmChart").getContext('2d');
        ctx.canvas.width = 500;
        ctx.canvas.height = 300;
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels_dias_cpm,
                datasets: [{
                    label: 'CPM: ',
                    data: data_cpm,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255,99,132,1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                }
            }
        });
      //
      //compras chart
        var ctx = document.getElementById("comprasChart").getContext('2d');
        ctx.canvas.width = 500;
        ctx.canvas.height = 300;
        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels_dias_compras,
                datasets: [{
                    label: 'Compras: ',
                    data: data_compras,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255,99,132,1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                }
            }
        });
      //
  });
</script>
<!--
$adsets = $account->getAdSets(array(AdSetFields::ID, AdSetFields::NAME, AdSetFields::STATUS, AdSetFields::CAMPAIGN_ID));
$paused_campaigns = array();
$paused_adsets = array();

foreach ($campaigns as $campaign) {
  if($campaign->{CampaignFields::STATUS} != 'ACTIVE')
    $paused_campaigns[] = $campaign->{CampaignFields::ID};
}

foreach ($adsets as $adset) {
  if($adset->{AdSetFields::STATUS} != 'ACTIVE')
    $paused_adsets[] = $adset->{AdSetFields::ID};
}


///
$response = $_SESSION['fb']->get("act_815527221921429/ads?fields=id,name,status,campaign_id,adset_id", $_SESSION['facebook_access_token']);

$feedEdge = $response->getGraphEdge();

foreach ($feedEdge as $status) {
  $ads[] = $status->asArray();
}

echo "<br>next<br>";
$nextFeed = $_SESSION['fb']->next($feedEdge);

foreach ($nextFeed as $status) {
  $ads[] = $status->asArray();
}

echo "<br>next<br>";
$nextFeed1 = $_SESSION['fb']->next($feedEdge);

if($nextFeed != $nextFeed1)
{
foreach ($nextFeed as $status) {
  var_dump($status->asArray());
}
}
///

var_dump($ads);

$ads_in_use = array();

foreach ($ads as $ad) {
  $key = array_search($ad[AdFields::ADSET_ID], $paused_adsets);
  if(!$key)
  {
    $key = array_search($ad[AdFields::CAMPAIGN_ID], $paused_campaigns);
      if(!$key)
        $ads_in_use[] = $ad[AdFields::ID];
  }
}

print_r($ads_in_use);

echo "<pre>";
echo "<table border=1>";
  echo "<tr>";
  echo '<td>ACTIONS</td>';
echo '<td>AD_NAME</td>';
echo '<td>CAMPAIGN_NAME</td>';
echo '<td>ADSET_NAME</td>';
echo '<td>COST_PER_INLINE_LINK_CLICK </td>';
echo '<td>CPM</td>';
echo '<td>FREQUENCY</td>';
echo '<td>IMPRESSIONS</td>';
echo '<td>INLINE_LINK_CLICK_CTR</td>';
echo '<td>INLINE_LINK_CLICKS</td>';
echo '<td>REACH</td>';
echo '<td>RELEVANCE_SCORE</td>';
echo '<td>SPEND</td>';
echo '<td>WEBSITE_CLICKS</td>';
echo '<td>WEBSITE_CTR</td>';
echo "</tr>";

foreach($ads_in_use as $ad_id)
{
  $ad = new Ad($ad_id);
  $insights = $ad->getInsights(array(AdsInsightsFields::ACTIONS,
    AdsInsightsFields::AD_NAME,
    AdsInsightsFields::CAMPAIGN_NAME,
    AdsInsightsFields::ADSET_NAME,
    AdsInsightsFields::AD_ID,
    AdsInsightsFields::CAMPAIGN_ID,
    AdsInsightsFields::ADSET_ID,
    AdsInsightsFields::CLICKS,
    AdsInsightsFields::COST_PER_INLINE_LINK_CLICK ,
    AdsInsightsFields::CPM,
    AdsInsightsFields::FREQUENCY,
    AdsInsightsFields::IMPRESSIONS,
    AdsInsightsFields::INLINE_LINK_CLICK_CTR,
    AdsInsightsFields::INLINE_LINK_CLICKS,
    AdsInsightsFields::REACH,
    AdsInsightsFields::RELEVANCE_SCORE,
    AdsInsightsFields::SPEND,
    AdsInsightsFields::WEBSITE_CLICKS,
    AdsInsightsFields::WEBSITE_CTR));
  
  
  foreach($insights as $insight)
  {
    echo "<tr>";
      echo '<td> '. print_r($insight->{AdsInsightsFields::ACTIONS},true) . '</td>';
echo '<td> '. $insight->{AdsInsightsFields::AD_NAME}.'</td>';
echo '<td> '. $insight->{AdsInsightsFields::CAMPAIGN_NAME}.'</td>';
echo '<td> '. $insight->{AdsInsightsFields::ADSET_NAME}.'</td>';
echo '<td> '. $insight->{AdsInsightsFields::COST_PER_INLINE_LINK_CLICK}.'</td>';
echo '<td> '. $insight->{AdsInsightsFields::CPM}.'</td>';
echo '<td> '. $insight->{AdsInsightsFields::FREQUENCY}.'</td>';
echo '<td> '. $insight->{AdsInsightsFields::IMPRESSIONS}.'</td>';
echo '<td> '. $insight->{AdsInsightsFields::INLINE_LINK_CLICK_CTR}.'</td>';
echo '<td> '. $insight->{AdsInsightsFields::INLINE_LINK_CLICKS}.'</td>';
echo '<td> '. $insight->{AdsInsightsFields::REACH}.'</td>';
echo '<td> '. print_r($insight->{AdsInsightsFields::RELEVANCE_SCORE},true).'</td>';
echo '<td> '. $insight->{AdsInsightsFields::SPEND}.'</td>';
echo '<td> '. $insight->{AdsInsightsFields::WEBSITE_CLICKS}.'</td>';
echo '<td> '. print_r($insight->{AdsInsightsFields::WEBSITE_CTR},true).'</td>';
    echo "</tr>";
  }
}
echo "</table>";
echo "</pre>";
-->