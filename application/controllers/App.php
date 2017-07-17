<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class App extends CI_Controller {

    function __construct() {
        parent::__construct();

        // Load facebook library
        $this->load->library('facebook');
        // Load phpexcel library
        $this->load->library('phpexcel');

        //Load user model
        $this->load->model('metricas');

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
      $accounts = $this->facebook->request('get', 'me/adaccounts?fields=name,account_status,age&limit=1200');

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

    public function sync_contas()
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

      $conta = $this->input->post('conta');

      $conta = str_replace('div_','',$conta);

      $detalhes = $this->facebook->request('get',$conta.get_param_contas());
      log_message('debug',json_encode($detalhes));

      $this->grava_bd($detalhes); 

      $detalhes = $this->facebook->request('get',$conta.'/customconversions?fields=id,name,custom_event_type,account_id');
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

        $detalhes = $this->facebook->request('get',$detalhes['id'].'/customconversions?fields=id,name,custom_event_type,account_id','EAAGkQhc0J9UBAMyMWDsopZBuxpx6E8bgZBcB7kXW2O6QGxSCKXOuYradgpxrxeZAO7BN74w9G1YQcf5JjJIXE3JTeUZCVZCrb1DOMusZAwlbvYDPD9QmW4BAcj4QsbQrEVARcqVxf11dwZATsEmC28nXMAqV0UIA98ZD');
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
        $fb_id = $this->session->userdata('facebook_id');
      
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

    public function sync_metricas()
    {
      log_message('debug', 'sync_metricas');

      $id = $this->input->post('val');
      $tipo = $this->input->post('tipo');

      $dt_inicio = $this->metricas->getLastDateSync($id, $tipo);

      $url_params = get_param_contas_data($dt_inicio);

      $detalhes = $this->facebook->request('get', $id.'/insights'.$url_params);

      log_message('debug',json_encode($detalhes));

      foreach($detalhes['data'] as $insight_data)
      {
        $data['data'][] = $insight_data;
        $insights_data[] = processa_insights($data, $tipo);
      }

      $this->metricas->insertInsights($insights_data, $tipo);

      $result = $this->metricas->getTableData($ad);
      $data['metricas'] = $result;

      $html = $this->show_table($id);

      echo $html;
      
    }

    public function show_table($id)
    {
      $resultado = $this->metricas->getTableData($id);
      if($resultado)
      {
        $conversions = $this->metricas->getPossibleConversions($id);
        $translate = translate_conversions($conversions, $this->metricas);

        foreach($resultado as $dados)
        {
          $result_actions = $this->metricas->getTableDataActions($dados->ad_insights_id);

          foreach($conversions as $conv)
          {
            if(!isset($dados->conversao)) $dados->conversao = new stdClass();
            if(!isset($dados->conversao->name)) $dados->conversao->name = new stdClass();
            $dados->conversao->name->{$conv->action_type} = $translate[$conv->action_type];
            $dados->conversao->{$conv->action_type} = '';
            $dados->conversao->{'Valor por ' . $conv->action_type} = '';
          }

          foreach($result_actions as $action)
          {
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

    public function home(){
        if($this->facebook->is_authenticated()){
            log_message('debug','TOKEN FB: ' . $this->facebook->is_authenticated() );
            // Get user facebook profile details
            $userProfile = $this->facebook->request('get', '/me?fields=id,first_name,last_name,email,gender,locale');
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

            $userID = $userProfile['id'];

            // Insert or update user data
            $this->metricas->checkUser($userData);

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
      $result = $this->facebook->request('get', $id);
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
        $ret .= "<option value='-1'>Selecione</option>";
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
        $detalhes = $this->facebook->request('get', $next);

        return $detalhes;
        //$contas = array_merge($contas, $accounts['data']);
    }

}



