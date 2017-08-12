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

	/**
  * index
  *
  * Página principal do Sistema Métricas, chamado quando se inicia o sistema em 
  *  superadsmetrics.com/metricas:
  *   - Se estiver logado no Facebook redireciona para a função home desta classe
  *   - Se estiver deslogado no Facebook chama a View main.
  */
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

    /**
    * logout
    *
    * Desloga do Facebook
    */
    public function logout() {
        // Remove local Facebook session
        $this->facebook->destroy_session();

        // Remove user data from session
        $this->session->unset_userdata('userData');

        // Redirect to login page
        redirect('App');
    }

    /**
    * get_contas
    *
    * Função chamada pelo Ajax para trazer a lista de contas a serem sincronizadas
    */
    public function get_contas()
    {
      log_message('debug', 'get_contas');

      $accounts = $this->facebook->request('get', 'me/adaccounts?fields=name,account_status,age&limit=1200',$this->usrtkn);

      log_message('debug',json_encode($accounts));

      if(isset($accounts['error']))
        die('Erro. Tente novamete');
        
      $contas = $accounts['data'];
      $ret = '';

      $userID = $this->session->userdata('facebook_id');
      $contas_user = $this->metricas->getContas($userID);
      
      if($contas_user)
      {
        foreach($contas_user as $conta_user)
        {
          $conta_sinc[] = trim($conta_user->account_name);
        }
      }

      $data['contas'] = $contas;
      $data['conta_sinc'] = $conta_sinc;
      $ret = $this->load->view('caixa_div', $data, true);

      //Mostra o html na tela
      echo $ret;
    }

    /**
    * sync_contas
    *
    * Chamada pelo ajax, quando clica no botão Sincronizar Conta na View home,
    *   esta função faz a chamada do Facebook e inicia o tratamento dos dados a serem
    *   incluídos no banco de dados
    * @param  conta: Parâmetro opcional para caso de testes via barra de endereço
    */
    public function sync_contas($conta = null)
    {
      log_message('debug', $this->input->raw_input_stream);

      //Se o parâmetro conta vier do post (do ajax)
      if(isset($_POST['conta']))
      {
        $conta = $this->input->post('conta');
        //$conta = str_replace('div_','',$conta);
      }
      elseif($conta == null)
      {
        die('Erro. Sem acesso ao sistema');  
      }
      else
      {
        $conta = 'act_'.$conta;
      }
      
      //Busca os dados a serem sincronizados no Facebook
      $detalhes = $this->facebook->request('get',$conta.get_param_contas(),$this->usrtkn);
      log_message('debug',json_encode($detalhes));

      if(array_key_exists('error',$detalhes))
      {
        die('Erro');
      }

      $this->grava_bd($detalhes); 

      //Busca as conversões personalizadas
      $detalhes = $this->facebook->request('get',$conta.'/customconversions?fields=id,name,custom_event_type,account_id',$this->usrtkn);
      log_message('debug',json_encode($detalhes));

      if(!empty($detalhes['data']))
        $this->metricas->grava_custom_conversions($detalhes['data']);
    }

    /**
    * sync_contas_from_file
    *
    * Faz o mesmo processamento da função sync_contas, porém busca o json de
    * retorno do Facebook de um arquivo texto
    */
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

    /**
    * grava_bd
    *
    * Faz o processamento dos dados da conta vindo do Facebook. E grava no banco.
    * @param  detalhes: O array de resultados vindo do Facebook
    * @param  fb_id: Opcional. Id do Facebook do dono da conta
    */
    public function grava_bd($detalhes, $fb_id = '0')
    {
      if($fb_id == 0)
        $fb_id = $this->fb_id;

      if($fb_id == null)
      {
        $fb_id = $this->session->userdata('facebook_id');
      }
      
      //Apaga os dados antes de inserir novamente
      $this->metricas->deleteToNewSync(str_replace('act_','',$detalhes['id']));

      //Separa os arrays
      $campaigns = $detalhes['campaigns']['data'];
      $ads = $detalhes['ads']['data'];
      $adsets = $detalhes['adsets']['data'];

      //Se existir paginamento de campanhas, processa para incluir no array
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

      //Se existir paginamento de conjuntos de anúncios, processa para incluir no array
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

      //Se existir paginamento de anúncios, processa para incluir no array
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
      
      //Se existir insights de contas, separa
      if(array_key_exists('insights',$detalhes))
      {
        $accounts_insights = $detalhes['insights'];
        unset($detalhes['insights']);
      }

      //Apaga do array principal, os arrays individuais já separados
      unset($detalhes['campaigns']);
      unset($detalhes['ads']);
      unset($detalhes['adsets']);

      //Informações adicionais a serem incluídos para a conta
      $detalhes['facebook_id'] = $fb_id;
      $detalhes['updated_time'] = date("Y-m-d H:i:s");
      $detalhes['sync_interval_minutes'] = 12; //De x horas
      $detalhes['id'] = str_replace('act_','',$detalhes['id']);

      //Processa array de campanhas
      $campaigns = processa_campaigns($campaigns);
      //Processa array de conjunto de anúncios
      $adsets = processa_adsets($adsets);
      //Processa array de anúncios
      $ads = processa_ads($ads);
      
      //Insere no banco de dados após processamento dos dados
      $this->metricas->insertAccount($detalhes);
      $this->metricas->insertCampaign($campaigns);
      $this->metricas->insertAdSet($adsets);
      $this->metricas->insertAd($ads);

    }

    /**
    * sync_metricas
    *
    * Inicia o processamento detalhado da conta. Isto irá trazer os dados detalhados por dia.
    *   - Esta função é chamada tanto pela resincronização
    *   - Esta função é chamada também pelo clique do botão Ver Número da View home
    * @param  id. Opcional: Id do tipo a ser processado
    * @param  tipo. Opctional: Tipo a ser processado: ad, adset ou campaign
    * @param  sogeral. Opcional: Se true, só processa os dados gerais, sem nenhuma quebra por data
    * @param  gera_planilha. Opcional: Se true, faz a geração da planilha
    */
    public function sync_metricas($id = null, $tipo = null, $sogeral = false, $gera_planilha = true)
    {
      log_message('debug', 'sync_metricas');

      //Checa se todos os parâmetros veio por post
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

      //Se não for só geral, processa os dados por dia
      if($sogeral == false)
      {
        $dt_inicio = $this->metricas->getLastDateSync($id, $tipo);

        $url_params = get_param_contas_data($dt_inicio);

        //Faz a chamada no Facebook
        $detalhes = $this->facebook->request('get', $id.'/insights'.$url_params,$this->usrtkn);

        if(isset($datalhes['error']))
          die('Erro. Tente novamente');

        log_message('debug',json_encode($detalhes));

        //Chama a função para processamento do insight
        $this->processa_resposta_insight($detalhes, $tipo, true);
      }

      $dt_inicio = $this->metricas->getFirstDate($id, $tipo);

      $url_params = get_param_contas_data_simples($dt_inicio);

      //Faz a chamada no Facebook
      $detalhes = $this->facebook->request('get', $id.'/insights'.$url_params,$this->usrtkn);

      if(isset($detalhes['error']))
        die('Error');

      log_message('debug',json_encode($detalhes));

      //Chama a função para processamento do insight
      $this->processa_resposta_insight($detalhes, $tipo);
      
      //Se true, chama a função para iniciar a criação da planilha em Excel
      if($gera_planilha)
      {
        $html = $this->show_table($id, $tipo, $comissao);

        echo $html;
      }
      
    }

    /**
    * processa_resposta_insight
    *
    * Faz o processamento da resposta dos insights
    * @param  detalhes: Array com os dados da resposta do Faceboo
    * @param  tipo: Tipo a ser processado: ad, adset ou campaign
    * @param  bydate. Opcional: Se true, indica que vai processar insights quebrados por data
    */
    private function processa_resposta_insight($detalhes, $tipo, $bydate = false)
    {
      //Se existir insight a ser processado
      if(array_key_exists('data', $detalhes))
      {
        $insights = $detalhes['data'];

        //Se tiver dados de paginação
        if(array_key_exists('paging', $detalhes))
        {
          //Se existir mais dados de paginação a serem incorporados no array
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

        //Para cada insight, processa
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

        //Se tiver dados processados, insere no banco
        if(isset($insights_data))
          $this->metricas->insertInsights($insights_data, $tipo, $bydate);
      }
    }

    /**
    * show_table
    *
    * Inicia a busca de dados e chama a função para gerar planilha
    * @param  id: Id do tipo a ser mostrado na planilha
    * @param  tipo: Tipo a ser processado: ad, adset ou campaign
    * @param  comissao. Comissão do produto a ser mostrada na planilha
    * @return false em caso de erro
    *         (string): nome do arquivo gerado
    */
    public function show_table($id, $tipo, $comissao)
    {
      //Comissão tem que ser número
      if(!is_numeric($comissao)) $comissao = 0;

      //Caso não haja dados de postback sincronizado para o id
      $sem_dado_venda = true;
      //Traz os dados a serem mostrados na planilha
      $resultado = $this->metricas->getTableData($id, $tipo);
      //Traz os dados de postback sincronizado, por dia
      $dados_vendas = $this->metricas->dados_vendas($id, $tipo);
      //Traz os dados de postback sincronizado, geral
      $dados_vendas_geral = $this->metricas->dados_vendas_geral($id, $tipo);

      //Se houver resultados a serem mostrados
      if($resultado)
      {
        //Traz todas as conversões possíveis
        $conversions = $this->metricas->getPossibleConversions($id, $tipo);
        //Substitui o nome das conversões por nomes fáceis de ler
        $translate = translate_conversions($conversions, $this->metricas);

        foreach($resultado as $dados)
        {
          $result_actions = $this->metricas->getTableDataActions($dados->{$tipo.'_insights_id'}, $tipo);

          //Insere as conversões na posição do array
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

          //Insere os valores nas conversões inseridas
          foreach($result_actions as $action)
          {
            if($action->action_type == 'offsite_conversion.fb_pixel_custom')
              continue;

            $dados->conversao->{$action->action_type} = $action->value;  
            $dados->conversao->{'Custo por ' . $action->action_type} = $action->cost;
          }

          $date = substr($dados->date_start, 0, 10);

          //Se tiver dados de postback sincronizados
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

          //Se tiver dados de postback (geral) sincronizados
          if($dados->bydate != 1 && $dados_vendas_geral != null)
          {
            $dados->boletos_gerados = $dados_vendas_geral->boletos_gerados;
            $dados->boletos_pagos = $dados_vendas_geral->boletos_pagos;
            $dados->cartoes = $dados_vendas_geral->cartoes;
            $dados->faturamento_boleto = $dados_vendas_geral->faturamento_boleto;
            $dados->faturamento_cartao = $dados_vendas_geral->faturamento_cartao;
          }

          //Faz o cálculo de %PurchaseCheckout
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

          //Faz o cálculo de %CheckoutViewContent
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

          //Faz o cálculo de %PurchaseViewContent
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

        //Chama a função de gerar planilha
        $filename = generate_excel($retorno, $this->phpexcel, $sem_dado_venda, $comissao);
        return $filename;
      }

      return false;
      

    }

    /**
    * resync
    *
    * Faz a atualização dos dados já sincronizados (resincronização)
    * @param id: Id do Facebook para ser feita a resincronização
    */
    public function resync($id = 'all')
    {
      log_message('debug', 'resync.'); 

      if($id == 'all')
      {
        $profiles = $this->metricas->get_resync_to_do();
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

        //Get token de acesso ao Facebook
        $usr = $this->metricas->getProfileToken($profile->id);
        $this->usrtkn = $usr->token;
        $this->fb_id =  $usr->facebook_id;

        //Para cada conta deste Facebook
        $results = $this->metricas->getContas($profile->id);
        foreach($results as $result)
        {
          //Sincroniza as contas
          $this->sync_contas($result->account_id);
          foreach($tipos as $tipo)
          {
            $results_tipo = $this->metricas->getFromConta($result->account_id, $tipo);
            if(!$results_tipo)
              continue;

            //Faz a sincronização dos insights somente dos tipos ativos
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

        $this->metricas->salva_data_resync($profile->id);

      }

    }

    /**
    * associa_postback
    *
    * Mostra a tela de associação dos postbacks com os anúncios, mostrando os 
    * botões das plataformas que possuem os anúncios
    */
    public function associa_postback()
    {
      log_message('debug', 'associa_postback.'); 

      $id = $this->metricas->getuserid($this->session->userdata('facebook_id'));
      $results = $this->metricas->busca_plataformas_vendas($id);

      $data['plataformas'] = $results;

      $this->load->view('metricas/vendas',$data);

    }

    /**
    * get_postback_data_to_assoc
    *
    * Traz os dados dos boletos gerados, pagos e cartões da plataforma
    *   - Esta função é chamada após o clique no botão com o nome da plataforma
    */
    public function get_postback_data_to_assoc()
    {
      log_message('debug', 'get_postback_data_to_assoc.'); 

      if(isset($_POST['plataforma']))
      {
        $token = $this->input->post('id');
        $plataforma = $this->input->post('plataforma');

        $resultado = $this->metricas->{'busca_' . strtolower($plataforma) . '_token'}($this->session->userdata('facebook_id'));
        $ads = $this->metricas->get_ads_ativos_30_dias($this->session->userdata('facebook_id'));

        $data['compras'] = $resultado;
        $data['anuncios'] = $ads;

        $html = $this->load->view('dados_assoc',$data,true);

        echo $html;
      }
    }

    /**
    * grava_ad_venda
    *
    * grava no banco os dados de postback selecionado
    */
    public function grava_ad_venda()
    {
      log_message('debug', 'grava_ad_venda.');  

      $pb = $this->input->post('dados');
      $ad = $this->input->post('ad_id');
      $tipo = $this->input->post('tipo');
      $plataforma = $this->input->post('plataforma');

      $adset_id = $this->metricas->getAdSetFromAd($ad);
      $campaign_id = $this->metricas->getCampaignFromAd($ad);

      //Para cada item selecionado na lista
      //foreach($pb as $id_plataforma)
      for($i=0; $i<count($pb); $i++)
      {
        $ret = $this->metricas->getProdutoComissao($pb[$i], $plataforma);

        if($tipo[$i] == "Boleto Impresso")
          $tp = "boletos_gerados";
        else if($tipo[$i] == "Boleto Pagos")
          $tp = "boletos_pagos";
        else if($tipo[$i] == "Cartão")
          $tp = "cartoes";

        $item['ad_id'] = $ad;
        $item['plataforma'] = $plataforma;
        $item['id_plataforma'] = $pb[$i];
        $item[$tp] = 1;
        $item['adset_id'] = $adset_id;
        $item['campaign_id'] = $campaign_id;
        $item['produto'] = $ret->produto;
        $item['comissao'] = $ret->comissao;
        $item['data'] = $ret->data;
        
        $array_insert[] = $item;
      }

      $this->metricas->insert_ads_vendas($array_insert);
    }

    /**
    * home
    *
    * Tela principal do Dashboard.
    */
    public function main()
    {
      $userID = $this->session->userdata('facebook_id');
      $data['userData'] = $this->session->userdata('userData');
      //Lista Contas
      $contas = $this->metricas->getContas($userID);
      $data['contas'] = $contas;

      // Load login & profile view
      $this->load->view('metricas/main',$data);
    }

    /**
    *
    */
    public function login_as_other_user()
    {
      $profiles = $this->metricas->get_profiles(); 

      $retorno = ''; 
      foreach($profiles as $profile)
      {
        $retorno .= "<option value=" . $profile->profile_id . ">" . $profile->first_name . " " . $profile->last_name .
          " Token expira em: " . date('d/m/Y',$profile->token_expiration) . "</option>"; 
      }

      $data['retorno'] = $retorno;
      $this->load->view('laou',$data);
    }

    /**
    *
    */
    public function fkhome()
    {
      if(isset($_POST['usr_fk_home']))
      {
        $ret = $this->metricas->get_profiles($this->input->post('usr_fk_home'));
        $this->session->set_userdata('fb_access_token', $ret->token);
        $this->home();
      }
    }

    /**
    * home
    *
    * Tela principal do Sistema.
    *   - Em caso de já ter autenticado, mostra a dashboard do sistema
    *   - Caso não estar autenticado, mostra a tela de login no Facebook
    */
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
          redirect("app/");
        }
    }

    /**
    * exec_fb_conn
    * 
    * Função para testar as conexões e respostas do Facebook
    * @param  id: Id do Facebook
    */
    public function exec_fb_conn($id)
    {
      $result = $this->facebook->request('get', $id,$this->usrtkn);
      log_message('debug',json_encode($accounts));

      if(isset($result['error']))
        die('Erro. Tente novamente');

      echo json_encode($result);
      
    }

    /**
    * fill_combo
    *
    * Função para preencher os combos de anúncios, conjuntos e campanhas para mostrar planilha
    */
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
          $ret .= "<option value='" . $val->id . "'";
          
          if(isset($val->effective_object_story_id))
          {
            $arr = explode('_', $val->effective_object_story_id);
            $url = $arr[0] . '/posts/' . $arr[1]; 
            $ret .= " data-story='" . $url . "'";
          }
            

          $ret .= ">" . $val->name . "</option>";  
        }
      }
    
      echo $ret;
    }

    /**
    * process_pagination
    *
    * Chama a url de paginação
    * @param url: Url que contém mais dados de paginação
    * @return false: se ocorrer algum erro
    *         array: os dados da paginação
    */
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

    /**
    * dados_vendas_plataforma
    *
    * Tenta associar o postback com o anúncio automaticamente
    * @param ad_id: Id do anúncio
    */
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

    /**
    * ger_contas
    *
    * Página de gerenciamento de contas
    * @param ad_id: Id do anúncio
    */
    public function ger_contas()
    {
      $id = $this->session->userdata('facebook_id');
      $results = $this->metricas->getContasDetalhes($id);

      $data['contas'] = $results;

      $this->load->view('metricas/ger_contas',$data);

    }

    public function apaga_conta()
    {
      if(isset($_POST['conta']))
      {
        $conta = $this->input->post('conta');
        $this->metricas->apaga_conta($conta);
      }
    }

    /**
    * postback
    *
    * Chama tela de cadastro de tokens para postback
    */
    public function postback()
    {
      $results = $this->metricas->getPlataformas();

      $data['plataformas'] = $results;

      $this->load->view('metricas/postback',$data);  
    }

    /**
    * config
    *
    * Chama tela de configurações gerais do sistema
    */
    public function config()
    {
      $id = $this->session->userdata('facebook_id');
      $results = $this->metricas->getConfig($id);
      
      $data['config'] = $results;

      $this->load->view('metricas/config',$data);  
    }

    /**
    * config
    *
    * Salva configurações no banco. Chamado através de ajax do view config
    */
    public function save_config()
    {
      if(isset($_POST['sync_time']))
      {
        $sync_time = $this->input->post('sync_time');  
        $postback_enabled = $this->input->post('postback_enabled');

        $id = $this->session->userdata('facebook_id');

        $this->metricas->saveConfig($sync_time, $postback_enabled, $id);
      }
    }

    /**
    * cadastra_token
    *
    * Insere token no banco de dados, chamado por ajax a partir da view postback
    */
    public function cadastra_token()
    {
      if(isset($_POST['plataforma']))
      {
        $plataforma = $this->input->post('plataforma');  
        $token = $this->input->post('token');

        $id = $this->session->userdata('facebook_id');

        $this->metricas->insertToken($plataforma, $token, $id);
      }
    }

    /**
    * apaga_token
    *
    * Insere token no banco de dados, chamado por ajax a partir da view postback
    */
    public function apaga_token()
    {
      if(isset($_POST['id_token']))
      {
        $tokens = $this->input->post('id_token');  

        $this->metricas->deleteToken($tokens);
      }
    }

    /**
    * get_user_tokens
    *
    * Traz os tokens para o usuario logado
    */
    public function get_user_tokens()
    {
      $id = $this->session->userdata('facebook_id');

      $results = $this->metricas->getUserTokens($id);

      $ret = "";

      foreach($results as $row)
      {
        $ret .= "<tr>";
        $ret .= "<td><input type='checkbox' name='checkbox-inline' class='chkToken' id='" . $row->platform_user_id . "'></td>";
        $ret .= "<td>" . $row->plataforma . "</td>";
        $ret .= "<td>" . $row->token . "</td>";
        $ret .= "<td>" . $row->created_time . "</td>";
        $ret .= "</tr>";
      }

      echo $ret; 
    }

    public function get_activities()
    {
      
      $profiles = $this->metricas->get_resync_to_do();

      foreach($profiles as $profile)
      {
        $tipos = array("campaign", "adset", "ad");

        //Get token de acesso ao Facebook
        $usr = $this->metricas->getProfileToken($profile->id);
        $this->usrtkn = $usr->token;
        $this->fb_id =  $usr->facebook_id;

        $usr = $this->metricas->getProfileToken($profile->id);

        //Busca os dados a serem sincronizados no Facebook
        $detalhes = $this->facebook->request('get',
          'me/adaccounts?fields=account_id,account_status,age,amount_spent,balance,business_city,business_country_code,business_name,business_state,business_street,business_street2,business_zip,can_create_brand_lift_study,created_time,currency,disable_reason,funding_source,funding_source_details,has_migrated_permissions,id,is_attribution_spec_system_default,is_direct_deals_enabled,is_notifications_enabled,is_personal,is_prepay_account,is_tax_id_required,min_campaign_group_spend_cap,min_daily_budget,name,offsite_pixels_tos_accepted,owner,spend_cap,tax_id,tax_id_status,tax_id_type,timezone_id,timezone_name,timezone_offset_hours_utc,user_role',
          $this->usrtkn);

        if(isset($accounts['error']))
         die('Erro. Tente novamete');

        $contas = $detalhes['data'];

        //Se existir paginamento de campanhas, processa para incluir no array
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

            $contas = array_merge($contas, $retorno['data']);
          }
        }

        foreach($contas as $conta)
        {
          $conta_add = null;

          if($conta['age'] > '0')
          {
            if(array_key_exists('funding_source_details'))
            {
              $conta['funding_source_details_id'] = $conta['funding_source_details']['id'];
              $conta['funding_source_details_display_string'] = $conta['funding_source_details']['display_string'];
              $conta['funding_source_details_type'] = $conta['funding_source_details']['type'];
              unset($conta['funding_source_details']);
            }

            $age = intval($conta['age']);
            $date = new DateTime();
            $date->modify('-' . $age . ' day');
            $begin = $this->metricas->get_last_activity($contas['id']);

            if(!$begin)
              $begin = $date->format('Y-m-d');

            $now = date("Y-m-d");
            //Busca os dados a serem sincronizados no Facebook
            $detalhes = $this->facebook->request('get',
              $conta['id'].'/activities?fields=actor_id,actor_name,application_id,application_name,date_time_in_timezone,event_time,event_type,extra_data,object_id,object_name,translated_event_type&since=' . $begin . '&until=' . $now,
              $this->usrtkn);

            $this->metricas->insert_activity($detalhes['data']);

            $conta_add[] = $conta;
          }
          $this->metricas->insert_contas_info($conta_add);
        }
      }

      
    ///act_815527071921444/activities?fields=actor_id,actor_name,application_id,application_name,date_time_in_timezone,event_time,event_type,extra_data,object_id,object_name,translated_event_type&since=2015-01-01&until=2017-08-09
    }

}



