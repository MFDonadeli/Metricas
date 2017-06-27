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

if (!session_id()) {
    session_start();
}

if($_SESSION['facebook_access_token'] && $_GET['conta'])
{
    $tem_adsets = false;
// Initialize a new Session and instanciate an Api object
Api::init('241324603009226', '8ef0601083d6dec4bb7cc3e71f33556d', $_SESSION['facebook_access_token']);

// The Api object is now available trough singleton
$api = Api::instance();

$account = new AdAccount('act_' . $_GET['conta']);

if(!isset($_SESSION[$_GET['conta']]) || isset($_GET['refresh']))
{
    $response = $_SESSION['fb']->get($_GET['conta'] . "/campaigns?fields=id,name,amount_spent,status,insights{actions,inline_link_click_ctr,cost_per_inline_link_click}&limit=1200", $_SESSION['facebook_access_token']);

    $feedEdge = $response->getGraphEdge();

    foreach ($feedEdge as $status) {
        $campaigns[] = $status->asArray();
    }
}
else
{
    $campaigns = $_SESSION[$_GET['conta']];
}

echo "<a href='index.php'>Voltar para lista de contas</a><br>";
echo "<h3>Lista de Campanhas da Conta</h3>";


foreach ($campaigns as $campaign)
{
    if($campaign['status'] == 'ACTIVE')
    {
        $tem_adsets = true;
?>
        <div class='container' id='<?php echo $campaign['id'] ?>'>
        <a href='adset.php?campanha=<?php echo $campaign['id'] ?>'>
          <strong><?php echo $campaign['name']; ?></strong><br>
          ID: <?php echo $campaign['id']; ?><br>
        </a>
        <?php if(array_key_exists('insights', $campaign)) echo insights_simples($campaign['insights'][0]); ?>        
      </div>
<?php
    }
}

if(!$tem_adsets)
    echo 'Conta sem campanhas ativas';
}
?>
<p style='clear: both;'><button class='btn' id='addAd'>Adicionar Campanha do Facebook selecionada a Campanha</button></p>

<script>
    $('.container').click(function(e) {
        $(this).toggleClass('selected_container');
    });
</script>