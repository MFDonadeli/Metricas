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

if($_SESSION['facebook_access_token'] && $_GET['campanha'])
{
    $tem_ads = false;

    if(!isset($_SESSION[$_GET['campanha']]) || isset($_GET['refresh']))
    {
        $response = $_SESSION['fb']->get($_GET['campanha'] . "/adsets?fields=id,name,amount_spent,status,campaign_id,campaign{name},account_id,insights{actions,inline_link_click_ctr,cost_per_inline_link_click}&limit=1200", $_SESSION['facebook_access_token']);
        
        $feedEdge = $response->getGraphEdge();

        foreach ($feedEdge as $status) {
            $adsets[] = $status->asArray();
        }
    }
    else
        $adsets = $_SESSION[$_GET['campanha']];
    

    echo "<a href='campanhas.php?conta=" . $adsets[0]['account_id'] . "'>Campanha: " . $adsets[0]['campaign']['name'] . "</a>";        
    echo "<h3>Lista de Grupo de Anúncios da Campanha</h3>";
    echo "<p><a href='numeros.php?tipo=campanha&id=" .  $_GET['campanha'] . "'>Analisar números da Campanha</a></p>";

    foreach ($adsets as $adset)
    {
        if($adset['status'] == 'ACTIVE')
        {
            $tem_ads = true;
?>
        <div class='container' id='<?php echo $adset['id'] ?>'>
        <a href='ads.php?adset=<?php echo $adset['id'] ?>'>
          <strong><?php echo $adset['name']; ?></strong><br>
          ID: <?php echo $adset['id']; ?><br>
        </a>
        <?php echo insights_simples($adset['insights'][0]); ?>        
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

<p style='clear: both;'><button class='btn' id='addAd'>Adicionar Grupo de Anúncio selecionado à Campanha</button></p>

<script>
    $('.container').click(function(e) {
        $(this).toggleClass('selected_container');
    });
</script>