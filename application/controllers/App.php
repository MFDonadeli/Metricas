<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH  . 'libraries/vendor/autoload.php';

use Facebook\Facebook;
use Facebook\FacebookApp;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\FacebookRequest;

class App extends CI_Controller {

    function __construct() {
        parent::__construct();

        // Load facebook library
        $this->load->library('facebook');

        //Load user model
        $this->load->model('metricas');
    }

	//Página Principal do Sistema de Métricas
  public function index()
    {
        // Check if user is logged in
        if($this->facebook->is_authenticated()){
            // Get user facebook profile details
            $userProfile = $this->facebook->request('get', '/me?fields=id,first_name,last_name,email,gender,locale');

            // Preparing data for database insertion
            $userData['oauth_provider'] = 'facebook';
            $userData['oauth_uid'] = $userProfile['id'];
            $userData['facebook_id'] = $userProfile['id'];
            $userData['first_name'] = $userProfile['first_name'];
            $userData['last_name'] = $userProfile['last_name'];
            $userData['email'] = $userProfile['email'];
            $userData['gender'] = $userProfile['gender'];
            $userData['locale'] = $userProfile['locale'];
            $userData['logged_in'] = true;

            $userID = $userProfile['id'];

            // Insert or update user data
            $this->metricas->checkUser($userData);

            // Check user data insert or update status
            if($userID){
                $data['userData'] = $userData;
                $this->session->set_userdata('userData',$userData);
            }else{
               $data['userData'] = array();
            }

            // Get logout URL
            $data['logoutUrl'] = $this->facebook->logout_url();

            //Lista Contas
            $contas = $this->metricas->getContas($userID);
            $data['contas'] = $contas;

            // Load login & profile view
            $this->load->view('home',$data);

        }else{
            $fbuser = '';

            // Get login URL
            $data['authUrl'] =  $this->facebook->login_url();

            redirect('app/index');
        }

            
    }

    public function logout() {
        // Remove local Facebook session
        $this->facebook->destroy_session();

        // Remove user data from session
        $this->session->unset_userdata('userData');

        // Redirect to login page
        redirect('App');
    }

    public function sync_contas()
    {
      $accounts = $this->facebook->request('get', 'me/adaccounts?fields=name,account_status,amount_spent,created_time,age,insights{actions,inline_link_click_ctr,cost_per_inline_link_click,account_id,account_name,action_values,app_store_clicks,call_to_action_clicks,canvas_avg_view_percent,canvas_avg_view_time,clicks,cost_per_10_sec_video_view,cost_per_estimated_ad_recallers,cost_per_inline_post_engagement,cost_per_unique_inline_link_click,cost_per_unique_click,cost_per_action_type,cost_per_outbound_click,cost_per_total_action,cpc,cpm,cpp,ctr,date_start,date_stop,deeplink_clicks,estimated_ad_recall_rate,estimated_ad_recallers,frequency,impressions,inline_link_clicks,inline_post_engagement,objective,reach,social_reach,social_spend,spend,total_action_value,unique_clicks,unique_ctr,unique_inline_link_click_ctr,unique_inline_link_clicks,website_clicks,age_targeting,buying_type,canvas_component_avg_pct_view,cost_per_unique_action_type,cost_per_unique_outbound_click,created_time,gender_targeting,labels,location,mobile_app_purchase_roas,outbound_clicks,outbound_clicks_ctr,place_page_name,relevance_score,social_clicks,social_impressions,total_actions,total_unique_actions,unique_actions,unique_link_clicks_ctr,unique_outbound_clicks,unique_outbound_clicks_ctr,unique_social_clicks,updated_time,video_10_sec_watched_actions,video_15_sec_watched_actions,video_30_sec_watched_actions,video_avg_percent_watched_actions,video_avg_time_watched_actions,video_p100_watched_actions,video_p25_watched_actions,video_p50_watched_actions,video_p75_watched_actions,video_p95_watched_actions,website_ctr,website_purchase_roas}&limit=50');
            $contas = $accounts['data'];

            while($accounts['paging']['next'] != '')
            {
              $str = "https://graph.facebook.com/v2.9/";
              $next = str_replace($str, '', $accounts['paging']['next']);
              $accounts = $this->facebook->request('get', $next);
              $contas = array_merge($contas, $accounts['data']);
            }
            var_dump($userData);
            var_dump($contas);
            die();
    }

    public function sync_adsets()
    {

    }

    public function sync_ads()
    {

    }

    public function sync_campanhas()
    {

    }

    public function home(){
        $this->load->view('home',null);
    }

}



/*
    <head>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
</head>
<?php
require_once __DIR__ . '/vendor/autoload.php';
include 'mysqldb.php';


use Facebook\Facebook;
use Facebook\FacebookApp;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\FacebookRequest;

// Init PHP Sessions
session_start();

//tokn: EAAGkQhc0J9UBAHYMZBe2nz2UcMHEwTyjomtG3bnF2JmhbvGtZCQR2k44IK7ZCBmp3ZB9yRqCNNX1J92QUMooziBAcj6kGG7m6PbJcICFcmVtFSlIH5njn8O9gZCEK2i7UCQ0RMIxKQEumn1mJT0zZCz8ZBGFHrYciyZB578jmokoG6mEtvZBFnDum
$_SESSION['fb'] = new Facebook([
  'app_id' => '462078740801493',
  'app_secret' => '4d46daf81761d012f64afcf5b1537b29'
]);

$helper = $_SESSION['fb']->getRedirectLoginHelper();
if(isset($_GET['state'])) {
      if(!isset($_SESSION['FBRLH_' . 'state']))
      {
          $_SESSION['FBRLH_' . 'state'] = $_GET['state'];
      }
}

if (!isset($_SESSION['facebook_access_token'])) {
  $_SESSION['facebook_access_token'] = null;
}

if (!$_SESSION['facebook_access_token']) {
  $helper = $_SESSION['fb']->getRedirectLoginHelper();
  try {
    $accessToken = (string) $helper->getAccessToken();
    $_SESSION['facebook_access_token'] = $accessToken;
  } catch(FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
  } catch(FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
  }
}

if ($_SESSION['facebook_access_token']) {
  echo "You are logged in!.<br>";
} else {
  $permissions = ['ads_management'];
  $loginUrl = $helper->getLoginUrl('http://localhost/~mfdonadeli/metricas/', $permissions);
  echo '<a href="' . $loginUrl . '">Log in with Facebook</a><br>';
} 
?>

<ul>
  <li>Produtos:</li>
  <li><button class='btn_comum' id='btn_produto_cadastrar'>Cadastrar</button></li>
  <li><button class='btn_comum' id='btn_produto_editar'>Editar</button></li>
</ul>

<div id='produtos' style='display: none;'>
    Nome: 
    <input name='nome' id='txtnome' type="text" style='display: none;'>
    <select name="nome_select" id="cmbnome" style='display: none;'>
      <option>-- SELECIONE -- </option>
    </select><br>
    Comissão: <input id="comissao" type="text"><br>
    <h4>Métricas Produtor</h4>
    <table>
      <tr>
        <td></td>
        <td>Mínimo</td>
        <td>Médio</td>
        <td>Máximo</td>
      </tr>
      <tr>
        <td>CPM</td>
        <td><input id="cpm_minimo" type="text"></td>
        <td><input id="cpm_medio" type="text"></td>
        <td><input id="cpm_maximo" type="text"></td>        
      </tr>
      <tr>
        <td>Cliques/Venda</td>
        <td><input id="cliques_minimo" type="text"></td>
        <td><input id="cliques_medio" type="text"></td>
        <td><input id="cliques_maximo" type="text"></td>        
      </tr>
      <tr>
        <td>CPC</td>
        <td><input id="cpc_minimo" type="text"></td>
        <td><input id="cpc_medio" type="text"></td>
        <td><input id="cpc_maximo" type="text"></td>        
      </tr>
      <tr>
        <td>CTR</td>
        <td><input id="ctr_minimo" type="text"></td>
        <td><input id="ctr_medio" type="text"></td>
        <td><input id="ctr_maximo" type="text"></td>        
      </tr>
      <tr>
        <td>Impressões</td>
        <td><input id="impressao_minimo" type="text"></td>
        <td><input id="impressao_medio" type="text"></td>
        <td><input id="impressao_maximo" type="text"></td>        
      </tr>
    </table>
    <button class='btn_comum' id='btn_produto_ok'>OK</button>
    <button class='btn_comum' id='btn_produto_cancelar'>Cancelar</button>
</div>

<?php
if($_SESSION['facebook_access_token'])
{
  $db = new MyDB();
  $db->createTables();
  $contas = array();

  if(!isset($_SESSION['accounts']) || isset($_GET['refresh']))
  {
    $response = $_SESSION['fb']->get("me/adaccounts?fields=name,account_status,amount_spent,campaigns{status,id,name},adsets{id,status,campaign_id,name},ads{id,status,adset_id,campaign_id,name},insights{actions,inline_link_click_ctr,cost_per_inline_link_click}&limit=1200", $_SESSION['facebook_access_token']);

    $feedEdge = $response->getGraphEdge();

    foreach ($feedEdge as $status) {
      $accounts[] = $status->asArray();
    }
    $_SESSION['accounts'] = $accounts;
  }
  else
    $accounts = $_SESSION['accounts'];

  echo '<h3>Campanhas</h3>';
  $ret = $db->execSQLQuery('select * from campanha;');
  while ($row = $ret->fetchArray()):
?>
    <div class='container'>
      <a href='numeros.php?tipo=analise&id=<?php echo $row['id']; ?>'><?php echo $row['nome'] ?></a>
    </div>
<?php
  endwhile;

  echo '<p style="clear:both;"></p>'; 
  echo '<h3>Contas do Gerenciador de Anúncios</h3>';  
  foreach ($accounts as $account)
  {
    if($account['amount_spent'] != '0' && $account['account_status'] == 1 && array_key_exists('insights', $account))
    {
      $contas[$account['id']] = $account['name'];
  ?>
      <div class='container'>
        <a href='campanhas.php?conta=<?php echo $account['id'] ?>'>
          <strong><?php echo $account['name']; ?></strong><br>
          ID: <?php echo $account['id']; ?><br>
        </a>
      </div>
<?php   
    }
  }

  $campanha_nome = '';
  echo '<p style="clear:both;"></p>';
  echo '<h3>Resumo de Anúncios</h3>';
  $ret = $db->execSQLQuery('select * from anuncios order by id_campanha, id_adset;');
  echo "<table border=1>";

  while ($row = $ret->fetchArray()):
    if($row['campanha'] != $campanha_nome)
    {
      $campanha_nome = $row['campanha'];
      echo "<tr><td colspan=7>" . $campanha_nome . "</td></tr>";
      echo "<tr>";
      echo "<th></th>";
      echo "<th>Anúncio</th>";
      echo "<th>Resultado</th>";
      echo "<th>ROI</th>";
      echo "<th>Números</th>";
      echo "<th>Link</th>";
      echo "<th></th>";
      echo "</tr>";
    }

    $ret1 = $db->execSQLQuery('select * from t' . $row['id'] . ' where Dia = "Geral";');
    $row1 = $ret1->fetchArray();
?>
    <tr>
      <td><input type="checkbox"></td>
      <td class='info' data-data='<?php echo $contas[$row["id_conta"]] . "<br>" . $row["campanha"] . "<br>". $row["adset"];?>'><?php echo $row['nome']; ?></td>
      <td><?php echo $row1['Total']; ?></td>
      <td><?php echo $row1['ROI']; ?></td>
      <td><a href='numeros.php?tipo=ad&id=<?php echo $row['id']; ?>'>Números</a></td>
      <td><a href='<?php echo $row['link']; ?>'>Link</a></td>
      <td><button>Excluir</button></td>
    </tr>
<?php
  endwhile;
  echo "</table>";

}

?>

<script>
  $('#btn_produto_cadastrar').click(function(){
   $('#produtos').show(); 
   $('#txtnome').show();
   $('#btn_produto_cadastrar').hide();
   $('#btn_produto_editar').hide();
  });

  $('#cmbnome').change(function(){
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
                $('#produto').val(obj.txtnome);                                
                $('#comissao').val(obj.comissao);                
                $('#cpm_minimo').val(obj.cpm_minimo);
                $('#cliques_minimo').val(obj.cliques_minimo);
                $('#cpc_minimo').val(obj.cpc_minimo);
                $('#ctr_minimo').val(obj.ctr_minimo);
                $('#impressao_minimo').val(obj.impressao_minimo);
                $('#cpm_medio').val(obj.cpm_medio);
                $('#cliques_medio').val(obj.cliques_medio);
                $('#cpc_medio').val(obj.cpc_medio);
                $('#ctr_medio').val(obj.ctr_medio);
                $('#impressao_medio').val(obj.impressao_medio);  
                $('#cpm_maximo').val(obj.cpm_maximo);
                $('#cliques_maximo').val(obj.cliques_maximo);
                $('#cpc_maximo').val(obj.cpc_maximo);
                $('#ctr_maximo').val(obj.ctr_maximo);
                $('#impressao_maximo').val(obj.impressao_maximo); 
            }
    });
  });

function limpa_produtos()
  {
    $('#produtos').hide(); 
   $('#cmbnome').hide();
   $('#txtnome').hide();
   $('#btn_produto_cadastrar').show();
   $('#btn_produto_editar').show();
   $('#comissao').val('');
   $('#txtnome').val('');
   $('#cpm_minimo').val('');
   $('#cliques_minimo').val('');
   $('#cpc_minimo').val('');
   $('#ctr_minimo').val('');
   $('#impressao_minimo').val('');
   $('#cpm_medio').val('');
   $('#cliques_medio').val('');
   $('#cpc_medio').val('');
   $('#ctr_medio').val('');
   $('#impressao_medio').val('');
   $('#cpm_maximo').val('');
   $('#cliques_maximo').val('');
   $('#cpc_maximo').val('');
   $('#ctr_maximo').val('');
   $('#impressao_maximo').val('');   
  }

  $('#btn_produto_editar').click(function(){
   limpa_produtos();    
   $('#produtos').show(); 
   $('#cmbnome').show();
   $('#btn_produto_cadastrar').hide();
   $('#btn_produto_editar').hide();
   $('#cmbnome').empty();
   $('#cmbnome')
      .append($('<option></option>')
      .text("--SELECIONE--"));   
   <?php
    $ret = $db->execSQLQuery('select * from produto;');
    while ($row = $ret->fetchArray()):
   ?>
   $('#cmbnome')
    .append($('<option></option>')
    .attr('value', "<?php echo $row['id']; ?>")
    .text("<?php echo $row['nome']; ?>"));
   <?php
    endwhile;
   ?>
  });


  $('#btn_produto_cancelar').click(function(){
   limpa_produtos();
  });

  $('#btn_produto_ok').click(function(){
    var form_data = {
            id_produto: $('#cmbnome').val(),
            produto: $('#txtnome').val(),
            comissao: $('#comissao').val(),
            cpm_minimo: $('#cpm_minimo').val(),
            cliques_minimo: $('#cliques_minimo').val(),
            cpc_minimo: $('#cpc_minimo').val(),
            ctr_minimo: $('#ctr_minimo').val(),
            impressao_minimo: $('#impressao_minimo').val(),
            cpm_medio: $('#cpm_medio').val(),
            cliques_medio: $('#cliques_medio').val(),
            cpc_medio: $('#cpc_medio').val(),
            ctr_medio: $('#ctr_medio').val(),
            impressao_medio: $('#impressao_medio').val(),
            cpm_maximo: $('#cpm_maximo').val(),
            cliques_maximo: $('#cliques_maximo').val(),
            cpc_maximo: $('#cpc_maximo').val(),
            ctr_maximo: $('#ctr_maximo').val(),
            impressao_maximo: $('#impressao_maximo').val()    
        };

        var resp = $.ajax({
            url: 'set_metricas_produto.php',
            type: 'POST',
            data: form_data,
            global: false,
            async:false,
            success: function(msg) { 
                var obj = $.parseJSON(msg);
            }
    });

    limpa_produtos();
  });

  $('#btn_campanha_editar').click(function(){
   $('#campanhas').show(); 
  });

  $('#btn_apagar_campanha').click(function(){
    
  });

  $('#btn_remover_campanha').click(function(){
   
  });

  $('#btn_cancelar_campanha').click(function(){
   $('#campanhas').hide(); 
  });

  
  
</script>

<script>
  $( function() {
    $( document ).tooltip({
        items: "td",
        content: function() {
            var element = $(this);
            var id = $(this).attr('id');
            var data = $(this).attr('data-data');

            if( element.hasClass( "info" ) )
            {
               return data;
            }
        }
    });
  });
</script>
*/