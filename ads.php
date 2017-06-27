<head>
  <link rel="stylesheet" href="styles.css">
  <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
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

if($_SESSION['facebook_access_token'] && $_GET['adset'])
{
    $tem_ads = false;
    $json_retorno = '';

    if(!isset($_SESSION[$_GET['adset']]) || isset($_GET['refresh']))
    {

        $response = $_SESSION['fb']->get($_GET['adset'] . "?fields=targeting,optimization_goal,promoted_object,attribution_spec,ads{id,name,status,campaign_id,adset_id,adset{name},campaign{name},account_id,creative{effective_object_story_id,url_tags},insights{actions,inline_link_click_ctr,cost_per_inline_link_click}}&limit=1000", $_SESSION['facebook_access_token']);

        $feedEdge = $response->getGraphNode();

        $informacao = $feedEdge->asArray(); 

        $ads = $informacao['ads'];
        unset($informacao['ads']);
        $json_retorno = json_encode($informacao);
    }
    else
        $ads = $_SESSION[$_GET['adset']];


    echo "<a href='campanhas.php?conta=act_" . $ads[0]['account_id'] . "'>Campanha: " . $ads[0]['campaign']['name'] . "</a>";
    echo " > ";
    echo "<a href='adset.php?campanha=" . $ads[0]['campaign']['id'] . "'>Grupo de Anúncio: " . $ads[0]['adset']['name'] . "</a>";
    echo "<h3>Lista de Anúncio do Grupo de Anúncio</h3>";
    echo "<p><a href='numeros.php?tipo=campanha&id=" .  $_GET['adset'] . "' target='numeros'>Analisar números do Grupo de Anúncios</a></p>";

    foreach ($ads as $ad)
    {
        $db = new MyDB();
        $tag = '';
        $url = 'https://www.facebook.com/'; 
        $arr = explode('_', $ad['creative']['effective_object_story_id']); 
        $url .= $arr[0] . '/posts/' . $arr[1]; 
        if(array_key_exists('url_tags', $ad['creative']))
            $tag = $ad['creative']['url_tags'];

        $db->saveExtrasToDb($ad['id'], $_GET['adset'], $json_retorno, $url, $tag);

        if($ad['status'] == 'ACTIVE')
        {
            $tem_ads = true;
?>
        <div class='container' id='<?php echo $ad['id'] ?>'>
        <a href='numeros.php?tipo=ad&id=<?php echo $ad['id'] ?>' target='numeros'>
          <strong><?php echo $ad['name']; ?></strong><br>
          ID: <?php echo $ad['id']; ?><br>
        </a>
        <?php echo insights_simples($ad['insights'][0]); ?>  <br>
        URL Tags: <?php echo $ad['creative']['url_tags']; ?> <br>
        <a href='<?php echo $url; ?>' target='_blank'>Ver Anuncio Completo</a>     
      </div>
<?php        
        }
    }

    if(!$tem_ads)
    {
        echo 'AdSet sem anúncios ativos';
    }
}

?>

<iframe width="100%" height="100%" frameBorder="0" name='numeros' src=""></iframe>

<script>
    $('.container').click(function(e) {
        $(this).toggleClass('selected_container');
    });
</script>