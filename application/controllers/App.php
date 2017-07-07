<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class App extends CI_Controller {

    function __construct() {
        parent::__construct();

        // Load facebook library
        $this->load->library('facebook');

        //Load user model
        $this->load->model('metricas');

        $this->load->helper('constants_helper');
        $this->load->helper('data_process_helper');

        
    }

	//Página Principal do Sistema de Métricas
  public function index()
    {
        // Check if user is logged in
        if($this->facebook->is_authenticated()){
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

/*
      while($accounts['paging']['next'] != '')
      {
        $str = "https://graph.facebook.com/v2.9/";
        $next = str_replace($str, '', $accounts['paging']['next']);
        $accounts = $this->facebook->request('get', $next);
        $contas = array_merge($contas, $accounts['data']);
      } */
      
      
    }

    public function sync_contas()
    {
      log_message('debug', $this->input->raw_input_stream);

      $conta = $this->input->post('conta');

      $conta = str_replace('div_','',$conta);

      $detalhes = $this->facebook->request('get',$conta.get_param_contas());
      log_message('debug',json_encode($detalhes));

      $this->grava_bd($detalhes);      
    }

    public function grava_bd($detalhes)
    {
      $fb_id = $this->session->userdata('facebook_id');
      $campaigns = $detalhes['campaigns']['data'];
      $ads = $detalhes['ads']['data'];
      $adsets = $detalhes['adsets']['data'];
      
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



