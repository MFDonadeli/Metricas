<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class App extends CI_Controller {

    private $usrtkn = null;
    private $fb_id = null;

    function __construct() {
        parent::__construct();

        // Load facebook library
        $this->load->library('facebook');
        // Load phpexcel library
        $this->load->library('phpexcel');

        $this->load->helper('constants_helper');
        $this->load->helper('data_process_helper');
        $this->load->helper('excel_helper');

        
    }

	//Página Principal do Sistema de Métricas
  public function index()
    {
        // Check if user is logged in
        if($this->facebook->is_authenticated()){

            // Load login & profile view
            redirect('app/home');

        }else{
            $fbuser = '';

            // Get login URL
            $data['authUrl'] =  $this->facebook->login_url();

            $this->load->view('main',$data);
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

    public function get_contas()
    {
      $accounts = $this->facebook->request('get', 'me/adaccounts?fields=name,account_status,age&limit=1200',$this->usrtkn);

      log_message('debug',json_encode($accounts));

      if(isset($accounts['error']))
        die('Erro. Tente novamete');
        
      $contas = $accounts['data'];
      $ret = '';

      log_message('debug', 'get_contas');

      foreach($contas as $conta)
      {
        if(intval($conta['age']) > 0)
        {
          $data['id'] = $conta['id'];
          $data['name'] = $conta['name'];
          $data['status'] = $conta['account_status'];
          $msg = $this->load->view('caixa_div', $data, true);
          $ret .= $msg;
        }
      }

      echo $ret;
    }

    public function sync_contas($conta = null)
    {
      log_message('debug', $this->input->raw_input_stream);

      /*$handle = fopen(APPPATH."jsons2.txt", "r");
      if ($handle) {
          while (($line = fgets($handle)) !== false) {
              $aaa[] = $line;
          }

          fclose($handle);
      } else {
          // error opening the file.
      } */

      if(isset($_POST['conta']))
      {
        $conta = $this->input->post('conta');
        $conta = str_replace('div_','',$conta);
      }
      elseif($conta == null)
      {
        die('Erro. Sem acesso ao sistema');  
      }
      else
      {
        $conta = 'act_'.$conta;
      }
      

      $detalhes = $this->facebook->request('get',$conta.get_param_contas(),$this->usrtkn);
      log_message('debug',json_encode($detalhes));

      if(array_key_exists('error',$detalhes))
      {
        die('Erro');
      }

      $this->grava_bd($detalhes); 

      $detalhes = $this->facebook->request('get',$conta.'/customconversions?fields=id,name,custom_event_type,account_id',$this->usrtkn);
      log_message('debug',json_encode($detalhes));

      if(!empty($detalhes['data']))
        $this->metricas->grava_custom_conversions($detalhes['data']);
    }

    public function sync_contas_from_file()
    {
      log_message('debug', 'sync_contas_from_file');

      $handle = fopen(APPPATH."jsons2.txt", "r");
      if ($handle) {
          while (($line = fgets($handle)) !== false) {
              $aaa[] = $line;
          }

          fclose($handle);
      } else {
          // error opening the file.
      } 

      foreach($aaa as $aa)
      {
        $detalhes = json_decode($aa, true);

        $this->grava_bd($detalhes, '1621655807847312'); 

        $detalhes = $this->facebook->request('get',$detalhes['id'].'/customconversions?fields=id,name,custom_event_type,account_id',$this->usrtkn);
        log_message('debug',json_encode($detalhes));

        if(array_key_exists('error', $detalhes))
        {
          //Joga erro na tela
        }
        else
        {
          if(!empty($detalhes['data']))
            $this->metricas->grava_custom_conversions($detalhes['data']);
        }
      }
      
    }

    public function grava_bd($detalhes, $fb_id = '0')
    {
      if($fb_id == 0)
        $fb_id = $this->fb_id;

      if($fb_id == null)
      {
        $fb_id = $this->session->userdata('facebook_id');
      }
      
      $this->metricas->deleteToNewSync(str_replace('act_','',$detalhes['id']));

      $campaigns = $detalhes['campaigns']['data'];
      $ads = $detalhes['ads']['data'];
      $adsets = $detalhes['adsets']['data'];

      if(array_key_exists('next', $detalhes['campaigns']['paging']))
      {
        $next = $detalhes['campaigns']['paging']['next'];
        while($next != '')
        {
          $retorno = $this->process_pagination($next);

          if(array_key_exists('next', $retorno['paging']))
            $next = $retorno['paging']['next'];
          else
            $next = '';

          $campaigns = array_merge($campaigns, $retorno['data']);
        }
      }

      if(array_key_exists('next', $detalhes['adsets']['paging']))
      {
        $next = $detalhes['adsets']['paging']['next'];
        while($next != '')
        {
          $retorno = $this->process_pagination($next);
          
          if(array_key_exists('next', $retorno['paging']))
            $next = $retorno['paging']['next'];
          else
            $next = '';

          $adsets = array_merge($adsets, $retorno['data']);
        }
      }

      if(array_key_exists('next', $detalhes['ads']['paging']))
      {
        $next = $detalhes['ads']['paging']['next'];
        while($next != '')
        {
          $retorno = $this->process_pagination($next);
          
          if(array_key_exists('paging', $retorno))
          {
            if(array_key_exists('next', $retorno['paging']))
              $next = $retorno['paging']['next'];
            else
              $next = '';

            $ads = array_merge($ads, $retorno['data']);
          }
        }
      }
      
      if(array_key_exists('insights',$detalhes))
      {
        $accounts_insights = $detalhes['insights'];
        unset($detalhes['insights']);
      }

      unset($detalhes['campaigns']);
      unset($detalhes['ads']);
      unset($detalhes['adsets']);

      $detalhes['facebook_id'] = $fb_id;
      $detalhes['updated_time'] = date("Y-m-d H:i:s");
      $detalhes['sync_interval_minutes'] = 12; //De x horas
      $detalhes['id'] = str_replace('act_','',$detalhes['id']);

      $campaigns = processa_campaigns($campaigns);
      $adsets = processa_adsets($adsets);
      $ads = processa_ads($ads);
      
      $this->metricas->insertAccount($detalhes);
      $this->metricas->insertCampaign($campaigns);
      $this->metricas->insertAdSet($adsets);
      $this->metricas->insertAd($ads);

    }

    public function sync_metricas($id = null, $tipo = null, $sogeral = false, $gera_planilha = true)
    {
      log_message('debug', 'sync_metricas');

      if(isset($_POST['val']) && isset($_POST['tipo']))
      {
        $id = $this->input->post('val');
        $tipo = $this->input->post('tipo');
        $comissao = $this->input->post('comissao');
      }
      elseif($id == null && $tipo == null)
      {
        die('Erro. Sem acesso ao sistema');
      }

      if($sogeral == false)
      {
        $dt_inicio = $this->metricas->getLastDateSync($id, $tipo);

        $url_params = get_param_contas_data($dt_inicio);

        $detalhes = $this->facebook->request('get', $id.'/insights'.$url_params,$this->usrtkn);

        if(isset($datalhes['error']))
          die('Erro. Tente novamente');

        log_message('debug',json_encode($detalhes));

        $this->processa_resposta_insight($detalhes, $tipo, true);
      }

      $dt_inicio = $this->metricas->getFirstDate($id, $tipo);

      $url_params = get_param_contas_data_simples($dt_inicio);

      $detalhes = $this->facebook->request('get', $id.'/insights'.$url_params,$this->usrtkn);

      if(isset($detalhes['error']))
        die('Error');

      log_message('debug',json_encode($detalhes));

      $this->processa_resposta_insight($detalhes, $tipo);
      

      if($gera_planilha)
      {
        $html = $this->show_table($id, $tipo, $comissao);

        echo $html;
      }
      
    }

    private function processa_resposta_insight($detalhes, $tipo, $bydate = false)
    {
      if(array_key_exists('data', $detalhes))
      {
        $insights = $detalhes['data'];

        if(array_key_exists('paging', $detalhes))
        {
          if(array_key_exists('next', $detalhes['paging']))
          {
            $next = $detalhes['paging']['next'];
            while($next != '')
            {
              $retorno = $this->process_pagination($next);
              
              if(array_key_exists('paging', $retorno))
              {
                if(array_key_exists('next', $retorno['paging']))
                  $next = $retorno['paging']['next'];
                else
                  $next = '';

                $insights = array_merge($insights, $retorno['data']);
              }
            }
          }
        }

        foreach($insights as $insight_data)
        {
          $data['data'] = null;
          $data['data'][] = $insight_data;
          $ret_insights = processa_insights($data, $tipo);

          $dt_start = $data['data'][0]['date_start'];
          $dt_end = $data['data'][0]['date_stop'];

          if(isset($insights_data[$dt_start.$dt_end]))
          {
            if(count($insights_data[$dt_start.$dt_end]) < count($ret_insights))
              $insights_data[$dt_start.$dt_end] = $ret_insights;
          }
          else
            $insights_data[$dt_start.$dt_end] = $ret_insights;
        }

        if(isset($insights_data))
          $this->metricas->insertInsights($insights_data, $tipo, $bydate);
      }
    }

    public function show_table($id, $tipo, $comissao)
    {
      if(!is_numeric($comissao)) $comissao = 0;

      $sem_dado_venda = true;
      $resultado = $this->metricas->getTableData($id, $tipo);
      $dados_vendas = $this->metricas->dados_vendas($id, $tipo);
      $dados_vendas_geral = $this->metricas->dados_vendas_geral($id, $tipo);

      if($resultado)
      {
        $conversions = $this->metricas->getPossibleConversions($id, $tipo);
        $translate = translate_conversions($conversions, $this->metricas);

        foreach($resultado as $dados)
        {
          $result_actions = $this->metricas->getTableDataActions($dados->{$tipo.'_insights_id'}, $tipo);

          foreach($conversions as $conv)
          {
            if(isset($translate[$conv->action_type]))
            {
              if(!isset($dados->conversao)) $dados->conversao = new stdClass();
              if(!isset($dados->conversao->name)) $dados->conversao->name = new stdClass();
              $dados->conversao->name->{$conv->action_type} = $translate[$conv->action_type];
              $dados->conversao->{$conv->action_type} = '';
              $dados->conversao->{'Custo por ' . $conv->action_type} = '';
            }
          }

          foreach($result_actions as $action)
          {
            if($action->action_type == 'offsite_conversion.fb_pixel_custom')
              continue;

            $dados->conversao->{$action->action_type} = $action->value;  
            $dados->conversao->{'Custo por ' . $action->action_type} = $action->cost;
          }

          $date = substr($dados->date_start, 0, 10);

          foreach($dados_vendas as $venda)
          {
            if($venda->dt == $date)
            {
              $sem_dado_venda = false;
              $dados->boletos_gerados = $venda->boletos_gerados;
              $dados->boletos_pagos = $venda->boletos_pagos;
              $dados->cartoes = $venda->cartoes;
              $dados->faturamento_boleto = $venda->faturamento_boleto;
              $dados->faturamento_cartao = $venda->faturamento_cartao;
            }
          }

          if($dados->bydate != 1 && $dados_vendas_geral != null)
          {
            $dados->boletos_gerados = $dados_vendas_geral->boletos_gerados;
            $dados->boletos_pagos = $dados_vendas_geral->boletos_pagos;
            $dados->cartoes = $dados_vendas_geral->cartoes;
            $dados->faturamento_boleto = $dados_vendas_geral->faturamento_boleto;
            $dados->faturamento_cartao = $dados_vendas_geral->faturamento_cartao;
          }

          if(isset($dados->conversao->{"offsite_conversion.fb_pixel_initiate_checkout"})
              && isset($dados->conversao->{"offsite_conversion.fb_pixel_purchase"}))
          {
            if($dados->conversao->{"offsite_conversion.fb_pixel_initiate_checkout"} != "")
            {
              $dados->purchase_checkout = 
                ($dados->conversao->{"offsite_conversion.fb_pixel_purchase"} /
                $dados->conversao->{"offsite_conversion.fb_pixel_initiate_checkout"}) * 100;
            }
          }

          if(isset($dados->conversao->{"offsite_conversion.fb_pixel_initiate_checkout"})
              && isset($dados->conversao->{"offsite_conversion.fb_pixel_view_content"}))
          {
            if($dados->conversao->{"offsite_conversion.fb_pixel_view_content"} != "")
            {
              $dados->checkout_view = 
                ($dados->conversao->{"offsite_conversion.fb_pixel_initiate_checkout"} /
                $dados->conversao->{"offsite_conversion.fb_pixel_view_content"}) * 100;
            }
          }

          if(isset($dados->conversao->{"offsite_conversion.fb_pixel_purchase"})
              && isset($dados->conversao->{"offsite_conversion.fb_pixel_view_content"}))
          {
            if($dados->conversao->{"offsite_conversion.fb_pixel_view_content"} != "")
            {
              $dados->purchase_view = 
                ($dados->conversao->{"offsite_conversion.fb_pixel_purchase"} /
                $dados->conversao->{"offsite_conversion.fb_pixel_view_content"}) * 100;
            }
          }

          $retorno[] = $dados;
        }

        $filename = generate_excel($retorno, $this->phpexcel, $sem_dado_venda, $comissao);
        return $filename;
      }

      return false;
      

    }

    public function resync($id = 'all')
    {
      if($id == 'all')
      {
        //pega todos
      }  
      else
      {
        $profiles = new stdClass();
        $profiles->A = new stdClass();
        $profiles->A->id = $id;
      }

      foreach($profiles as $profile)
      {
        $tipos = array("campaign", "adset", "ad");

        $usr = $this->metricas->getProfileToken($profile->id);
        $this->usrtkn = $usr->token;
        $this->fb_id =  $usr->facebook_id;

        $results = $this->metricas->getContas($profile->id);
        foreach($results as $result)
        {
          $this->sync_contas($result->account_id);
          foreach($tipos as $tipo)
          {
            $results_tipo = $this->metricas->getFromConta($result->account_id, $tipo);
            if(!$results_tipo)
              continue;

            foreach($results_tipo as $res_tipo)
            {
              $sogeral = false;
              if($res_tipo->effective_status != 'ACTIVE')
                $sogeral = true; 

              if($res_tipo->effective_status == 'ACTIVE') // SÓ FAZ OS ATIVOS
                $this->sync_metricas($res_tipo->id, $tipo, $sogeral, false);
            }
          }
        }
      }


    }

    public function associa_postback()
    {
      log_message('debug', 'associa_postback.'); 

      $id = $this->metricas->getuserid($this->session->userdata('facebook_id'));
      $results = $this->metricas->busca_plataformas_vendas($id);

      $data['plataformas'] = $results;

      $this->load->view('associa_postback',$data);

    }

    public function get_postback_data_to_assoc()
    {
      log_message('debug', 'get_postback_data_to_assoc.'); 

      if(isset($_POST['plataforma']))
      {
        $token = $this->input->post('id');
        $plataforma = $this->input->post('plataforma');

        $resultado = $this->metricas->{'busca_' . strtolower($plataforma) . '_token'}($token);
        $ads = $this->metricas->get_ads_ativos_30_dias($this->session->set_userdata('facebook_id'));

        $data['compras'] = $resultado;
        $data['anuncios'] = $ads;

        $html = $this->load->view('dados_assoc',$data,true);

        echo $html;
      }
    }

    public function grava_ad_venda()
    {
      log_message('debug', 'grava_ad_venda.');  

      $pb = $this->input->post('dados');
      $ad = $this->input->post('ad_id');
      $tipo = $this->input->post('tipo');
      $plataforma = $this->input->post('plataforma');

      $adset_id = $this->metricas->getAdSetFromAd($ad);
      $campaign_id = $this->metricas->getCampaignFromAd($ad);

      foreach($pb as $id_plataforma)
      {
        $ret = $this->metricas->getProdutoComissao($id_plataforma, $plataforma);

        $item['ad_id'] = $ad;
        $item['plataforma'] = $plataforma;
        $item['id_plataforma'] = $id_plataforma;
        $item[$tipo] = 1;
        $item['adset_id'] = $adset_id;
        $item['campaign_id'] = $campaign_id;
        $item['produto'] = $ret->produto;
        $item['comissao'] = $ret->comissao;
        $item['data'] = $ret->data;
        
        $array_insert[] = $item;
      }

      $this->metricas->insert_ads_vendas($array_insert);
    }

    public function home(){
        if($this->facebook->is_authenticated()){
            log_message('debug','TOKEN FB: ' . $this->facebook->is_authenticated() );
            // Get user facebook profile details
            $userProfile = $this->facebook->request('get', '/me?fields=id,first_name,last_name,email,gender,locale,picture{url}',$this->usrtkn);
            log_message('debug',json_encode($userProfile));

            if(!isset($userProfile['error']))
            {
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
              $userData['picture'] = $userProfile['picture']['data']['url'];
              $userData['token'] = $this->session->userdata('fb_access_token');
              $userData['token_expiration'] = $this->session->userdata('fb_expire');

              $userID = $userProfile['id'];
              $this->fb_id = $userID;

              // Insert or update user data
              $this->metricas->checkUser($userData);

              unset($userData['token']);
              unset($userData['token_expiration']);

              // Check user data insert or update status
              if($userID){
                  $data['userData'] = $userData;
                  $this->session->set_userdata('userData',$userData);
                  $this->session->set_userdata('facebook_id',$userID);
              }else{
                $data['userData'] = array();
              }

              // Get logout URL
              $data['logoutUrl'] = $this->facebook->logout_url();

              //Lista Contas
              $contas = $this->metricas->getContas($userID);
              $data['contas'] = $contas;
            }
            else
            {
              $data['error'] = $userProfile['error'];
            }

            // Load login & profile view
            $this->load->view('metricas/index',$data);

        }
        else
        {
          redirect("index");
        }
    }

    public function exec_fb_conn($id)
    {
      $result = $this->facebook->request('get', $id,$this->usrtkn);
      log_message('debug',json_encode($accounts));

      if(isset($result['error']))
        die('Erro. Tente novamente');

      echo json_encode($result);
      
    }

    public function fill_combo()
    {
      log_message('debug', 'fill_combo');

      $id = $this->input->post('id'); 
      $tipo = $this->input->post('tipo');   

      $retorno = $this->metricas->get_from_tipo($id, $tipo);

      $ret = "";

      if($retorno == "Nenhum ativo")
      {
        $ret .= "<option value='-1'>Nenhum ativo</option>";
      }
      else
      {
        $ret .= "<option value='-1'>Todos</option>";
        foreach($retorno as $val)
        {
          $ret .= "<option value='" . $val->id . "'>" . $val->name . "</option>";  
        }
      }
    
      echo $ret;
    }

    public function process_pagination($url)
    {
      
        $str = "https://graph.facebook.com/v2.9/";
        $next = str_replace($str, '', $url);
        $detalhes = $this->facebook->request('get', $next, $this->usrtkn);

        if(isset($detalhes['error']))
          return false;

        return $detalhes;
        //$contas = array_merge($contas, $accounts['data']);
    }

    public function dados_vendas_plataforma($ad_id)
    {
      $tags = $this->metricas->get_tags_from_ad($ad_id);

      $tag_vars = explode("&", $tags->url_tags);

      if($tags->url_tags == null)
        die('Tag Vars vazio no banco');
      
      foreach($tag_vars as $var)
      {
        $var_explode = explode("=", $var);
        $var_array[$var_explode[0]] = $var_explode[1];
      }

      $retorno = $this->metricas->busca_vendas_tag($ad_id,$var_array);

      if($retorno)
      {
        $adset_id = $this->metricas->getAdSetFromAd($ad_id);
        $campaign_id = $this->metricas->getCampaignFromAd($ad_id);

        if(isset($retorno['boleto_impresso']))
        {
          foreach($retorno['boleto_impresso'] as $valor)
          {
            $valor->ad_id = $ad_id;
            $valor->boletos_gerados = 1;
            $valor->adset_id = $adset_id;
            $valor->campaign_id = $campaign_id;
            $array_insert[] = $valor;
          }
        }
        if(isset($retorno['boleto_pago']))
        {
          foreach($retorno['boleto_pago'] as $valor)
          {
            $valor->ad_id = $ad_id;
            $valor->boletos_pagos = 1;
            $valor->adset_id = $adset_id;
            $valor->campaign_id = $campaign_id;
            $array_insert[] = $valor;
          }
        }
        if(isset($retorno['cartao']))
        {
          foreach($retorno['cartao'] as $valor)
          {
            $valor->ad_id = $ad_id;
            $valor->cartoes = 1;
            $valor->adset_id = $adset_id;
            $valor->campaign_id = $campaign_id;
            $array_insert[] = $valor;
          }
        }

        $this->metricas->insert_ads_vendas($array_insert);
      }

        die('Sem vendas a inserir');
      
      return;
    }

    public function processa_postback($plataforma, $id)
    {

    }

}



