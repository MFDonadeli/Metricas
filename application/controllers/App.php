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

    public function sync_metricas($id = null, $tipo = null, $gera_planilha = true)
    {
      log_message('debug', 'sync_metricas');

      if(isset($_POST['val']) && isset($_POST['tipo']))
      {
        $id = $this->input->post('val');
        $tipo = $this->input->post('tipo');
      }
      elseif($id == null && $tipo == null)
      {
        die('Erro. Sem acesso ao sistema');
      }

      $dt_inicio = $this->metricas->getLastDateSync($id, $tipo);

      $url_params = get_param_contas_data($dt_inicio);

      $detalhes = $this->facebook->request('get', $id.'/insights'.$url_params,$this->usrtkn);

      log_message('debug',json_encode($detalhes));

      if(array_key_exists('data', $detalhes))
      {
        $insights = $detalhes['data'];

        if(array_key_exists('next', $detalhes['paging']))
        {
          $next = $detalhes['paging']['next'];
          while($next != '')
          {
            $retorno = $this->process_pagination($next);
            
            if(array_key_exists('next', $retorno['paging']))
              $next = $retorno['paging']['next'];
            else
              $next = '';

            $insights = array_merge($insights, $retorno['data']);
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
          $this->metricas->insertInsights($insights_data, $tipo);
      }

      if($gera_planilha)
      {
        $html = $this->show_table($id, $tipo);

        echo $html;
      }
      
    }

    public function show_table($id, $tipo)
    {
      $resultado = $this->metricas->getTableData($id, $tipo);
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
              $dados->conversao->{'Valor por ' . $conv->action_type} = '';
            }
          }

          foreach($result_actions as $action)
          {
            if($action->action_type == 'offsite_conversion.fb_pixel_custom')
              continue;

            $dados->conversao->{$action->action_type} = $action->value;  
            $dados->conversao->{'Valor por ' . $action->action_type} = $action->cost;
          }

          $retorno[] = $dados;
        }

        $filename = generate_excel($retorno, $this->phpexcel);
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
            foreach($results_tipo as $res_tipo)
            {
              $this->sync_metricas($res_tipo->id, $tipo, false);
            }
          }
        }
      }


    }

    public function home(){
        if($this->facebook->is_authenticated()){
            log_message('debug','TOKEN FB: ' . $this->facebook->is_authenticated() );
            // Get user facebook profile details
            $userProfile = $this->facebook->request('get', '/me?fields=id,first_name,last_name,email,gender,locale',$this->usrtkn);
            log_message('debug',json_encode($userProfile));

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

            // Load login & profile view
            $this->load->view('home',$data);

        }
    }

    public function exec_fb_conn($id)
    {
      $result = $this->facebook->request('get', $id,$this->usrtkn);
      log_message('debug',json_encode($accounts));
      
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

        return $detalhes;
        //$contas = array_merge($contas, $accounts['data']);
    }

}



