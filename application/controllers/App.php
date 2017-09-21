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

        $this->load->library('excel_build');

        $this->load->helper('constants_helper');
        $this->load->helper('data_process_helper');    
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
      $conta_sinc = false;

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
    * @param  completa: Se vai fazer a sincronização apagando os dados de actions
    */
    public function sync_contas($conta = null, $completa = false)
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

      $this->grava_bd($detalhes, $completa); 

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

        $this->grava_bd($detalhes, '1621655807847312', false); 

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
    * sync_contas_info
    *
    * Chamada pelo ajax, quando clica no botão Sincronizar Conta na View home,
    *   esta função faz a chamada do Facebook e inicia o tratamento dos dados a serem
    *   incluídos no banco de dados
    * @param  conta: Parâmetro opcional para caso de testes via barra de endereço
    * @param  completa: Se vai fazer a sincronização apagando os dados de actions
    */
    public function sync_contas_info($conta = null, $completa = false)
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

      $this->grava_bd($detalhes, $completa); 

      //Busca as conversões personalizadas
      $detalhes = $this->facebook->request('get',$conta.'/customconversions?fields=id,name,custom_event_type,account_id',$this->usrtkn);
      log_message('debug',json_encode($detalhes));

      if(!empty($detalhes['data']))
        $this->metricas->grava_custom_conversions($detalhes['data']);
    }

    /**
    * grava_bd
    *
    * Faz o processamento dos dados da conta vindo do Facebook. E grava no banco.
    * @param  detalhes: O array de resultados vindo do Facebook
    * @param  fb_id: Opcional. Id do Facebook do dono da conta
    */
    public function grava_bd($detalhes, $completa, $fb_id = '0')
    {
      if($fb_id == 0)
        $fb_id = $this->fb_id;

      if($fb_id == null)
      {
        $fb_id = $this->session->userdata('facebook_id');
      }
      
      //Apaga os dados antes de inserir novamente
      $this->metricas->deleteToNewSync(str_replace('act_','',$detalhes['id']), $completa);

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

        log_message('debug', 'Resposta insight por data ' . json_encode($detalhes));

        //Chama a função para processamento do insight
        $this->processa_resposta_insight($detalhes, $tipo, true);
      }

      $dt_inicio = $this->metricas->getFirstDate($id, $tipo);

      if(!$dt_inicio)
        $dt_inicio = $this->metricas->getLastDateSync($id, $tipo);

      $url_params = get_param_contas_data_simples($dt_inicio);

      //Faz a chamada no Facebook
      $detalhes = $this->facebook->request('get', $id.'/insights'.$url_params,$this->usrtkn);

      if(isset($detalhes['error']))
        die('Error');

      log_message('debug','Resposta insight ' . json_encode($detalhes));

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

      $conversions = "";

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
            $comissao = $dados_vendas_geral->comissao;
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

          $dados->dia_da_semana = '';
          $retorno[] = $dados;
        }

        //Chama a função de gerar planilha
        $filename = $this->excel_build->generate_excel($retorno, $this->phpexcel, $sem_dado_venda, $comissao, $tipo);

        $resumo = false;

        if($tipo!='ads')
          $resumo = $this->get_resumo($id, $tipo, $comissao, $translate);

        if(!$resumo)
          $resumo = 'Nenhum';

        $ret = array("filename" => $filename,
                      "dados" => $retorno,
                      "resumo" => $resumo,
                      "conversoes" => $conversions,
                      "nomes_conversoes" => $translate);

        $js = json_encode($ret);

        return $js;
      }

      return false;
      

    }

    /**
    * get_resumo
    *
    * Pega resumo do tipo para consulta rápida de todos o tipo abaixo
    * @param id: Id do tipo
    * @param tipo: Tipo
    * @param comissao: Comissao para cálculo do ROI
    * @param translate: Para traduzir nome das conversões
    */
    private function get_resumo($id, $tipo, $comissao, $translate)
    {
      log_message('debug', 'get_resumo');   

      $retorno = $this->metricas->get_resumo($id, $tipo, $comissao);

      if(!$retorno)
        return false;

      $header = "<tr>";
      $header .="<th>Nome</th>";
      $header .="<th>CPC</th>";
      $header .="<th>CTR</th>";
      $header .="<th>CPM</th>";
      foreach($translate as $key => $val)
      {
        $header .="<th>".$val."</th>";
      }
      $header .= "<tr>";

      $body = "";
      foreach($retorno as $ret)
      {
        $body .= "<tr>";
        $body .= "<td>" . $ret['nome'] . "</td>";
        $body .= "<td>" . $ret['cpc'] . "</td>";
        $body .= "<td>" . $ret['ctr'] . "</td>";
        $body .= "<td>" . $ret['cpm'] . "</td>";
        foreach($translate as $key => $val)
        {
          if(isset($ret[$key]))
            $body .="<td>".$ret[$key]."</td>";
          else
            $body .="<td>-</td>";
        }
        $body .= "</tr>";
      }   

      $val_ret['header'] = $header;
      $val_ret['body'] = $body;

      return $val_ret;                                
    }

    /**
    * resync
    *
    * Faz a atualização dos dados já sincronizados (resincronização)
    * @param id: Id do Facebook para ser feita a resincronização
    */
    public function resync($id = 'all', $completa = false)
    {
      if($completa !== false)
        $completa = true;
        
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
          $this->sync_contas($result->account_id, $completa);
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

              // if($res_tipo->effective_status == 'ACTIVE') // SÓ FAZ OS ATIVOS
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
    * get_vendas_dia
    *
    * Lista as vendas do dia
    */
    public function get_vendas_dia()
    {
      log_message('debug', 'get_vendas_dia.'); 

      if(isset($_POST['data']))
      {
        $data = $this->input->post('data');
        $id = $this->input->post('ad_id');

        $results = $this->metricas->dados_vendas_dia($data, $id, 'ad');

        $html = "<table class='table table-striped table-bordered table-hover'>";
        $html .= "<tr>";
        $html .= "<td>Data</td>";
        $html .= "<td>Produto</td>";
        $html .= "<td>Comissao</td>";
        $html .= "<td>Src</td>";
        $html .= "<td>Tipo</td>";
        $html .= "<td>Cancelar</td>";
        $html .= "</tr>";

        foreach($results as $result)
        {
          $html .= "<tr>";

          $html .= "<td>" . $result->dt . "</td>";
          $html .= "<td>" . $result->produto . "</td>";
          $html .= "<td>" . $result->comissao . "</td>";
          $html .= "<td>" . $result->src . "</td>";
          
          if($result->boletos_gerados == 1)
            $tipo = 'Boleto Gerado'; 
          else if($result->boletos_pagos == 1)
            $tipo = 'Boleto Pago'; 
          else if($result->cartoes == 1)
            $tipo = 'Cartão'; 

          $html .= "<td>" . $tipo . "</td>";

          $html .= "<td><a class='btn btn-danger btn_cancelavenda' id='" . $result->ads_vendas_id .  " href='#'><i class='fa fa-remove'></i> Cancelar Associação</a></td>";
          
          $html .= "</tr>";
        }

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
      $src = $this->input->post('src');
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
        $item['src'] = $src[$i];
        
        $array_insert[] = $item;
      }

      $this->metricas->insert_ads_vendas($array_insert);
    }

    /**
    * grava_ad_venda_manual
    *
    * grava no banco os dados de venda digitado
    */
    public function grava_ad_venda_manual()
    {
      log_message('debug', 'grava_ad_venda_manual');  

      $data = $this->input->post('data');
      $ad = $this->input->post('ad_id');
      $tipo = $this->input->post('tipo');
      $plataforma = $this->input->post('plataforma');
      $produto = $this->input->post('produto');
      $comissao = $this->input->post('comissao');

      $adset_id = $this->metricas->getAdSetFromAd($ad);
      $campaign_id = $this->metricas->getCampaignFromAd($ad);

      if($tipo == "Boleto Impresso")
        $tp = "boletos_gerados";
      else if($tipo == "Boleto Pagos")
        $tp = "boletos_pagos";
      else if($tipo == "Cartão")
        $tp = "cartoes";

      $item['ad_id'] = $ad;
      $item['plataforma'] = $plataforma;
      $item[$tp] = 1;
      $item['adset_id'] = $adset_id;
      $item['campaign_id'] = $campaign_id;
      $item['produto'] = $produto;
      $item['comissao'] = $comissao;
      $item['data'] = $data;
        
      $array_insert[] = $item;

      $this->metricas->insert_ads_vendas($array_insert);
    }

    /**
    * cancela_associacao_postback
    *
    * Desfaz a associação de um postback ou lançamento manual
    */
    public function cancela_associacao_postback()
    {
      if(isset($_POST['id_ads_vendas']))
      {
        log_message('debug', 'cancela_associacao_postback');  

        $this->metricas->undo_ads_vendas($this->input->post('id_ads_vendas'));
      }
    }

    /**
    * get_produtos_plataforma
    *
    * Traz uma lista de produtos de uma plataforma selecionada
    */
    public function get_produtos_plataforma()
    {
      $plataforma = $this->input->post('plataforma');
      $resultados = $this->metricas->getProdutos($plataforma);

      if(!$resultados)
      {
        $html = "<option value=-1>Nenhum produto cadastrado</option>";
      }
      else
      {
        $html = "<option value=-1>Selecione o produto</option>";

        foreach($resultados as $resultado)
        {
          $html .= "<option value=" . $resultado->id_produtos . " data-comissao='" . $resultado->comissao . "'>" .
             $resultado->nome . " - Comissão: " . $resultado->comissao . "</option>";
        }
      }

      echo $html;

    }

    /**
    * gerencia_postback
    *
    * Mostra a tela de gerenciamento dos postbacks com os anúncios já associados de acordo com o 
    * anúncio. Pode também associar manualmente vendas que não geraram postbacks
    */
    public function gerencia_postback()
    {
      log_message('debug', 'gerencia_postback.'); 

      $id = $this->metricas->getuserid($this->session->userdata('facebook_id'));
      
      $ads = $this->metricas->get_ads_ativos_30_dias($this->session->userdata('facebook_id'));
      
      $data['anuncios'] = $ads;

      $html = $this->load->view('metricas/ger_assoc',$data);

    }

    /**
    * show_vendas_assoc
    * 
    * Mostra as vendas associadas para o anúncio selecionado
    */
    public function show_vendas_assoc()
    {
      log_message('debug', 'show_vendas_assoc');

      $id = $this->input->post('ad_id');
      
      $resultado = $this->metricas->dados_vendas($id, 'ad');
      $plataformas = $this->metricas->getPlataformas();

      $data['compras'] = $resultado;
      $data['plataforms'] = $plataformas;

      $html = $this->load->view('metricas/ger_assoc_vendas',$data, true);

      echo $html;
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
    *
    */
    public function fkhome_dbonly()
    {
      if(isset($_POST['usr_fk_home']))
      {
        $ret = $this->metricas->get_profiles($this->input->post('usr_fk_home'));
        $this->session->set_userdata('fb_access_token', $ret->token);
        
        $a = json_encode($ret, true);
        $b = json_decode($a);
        $userProfile = json_decode(json_encode($ret), true);

        if(!isset($userProfile['error']))
        {
          // Preparing data for database insertion
          $userData['oauth_provider'] = 'facebook';
          
          $userData['oauth_uid'] = $userProfile['facebook_id'];
          $userData['facebook_id'] = $userProfile['facebook_id'];
            
          if(array_key_exists('first_name', $userProfile))  
            $userData['first_name'] = $userProfile['first_name'];

          if(array_key_exists('last_name', $userProfile))  
            $userData['last_name'] = $userProfile['last_name'];

          if(array_key_exists('email', $userProfile))  
            $userData['email'] = $userProfile['email'];

          if(array_key_exists('gender', $userProfile))  
            $userData['gender'] = $userProfile['gender'];

          if(array_key_exists('locale', $userProfile))  
            $userData['locale'] = $userProfile['locale'];

          $userData['logged_in'] = true;

          if(array_key_exists('picture', $userProfile))
            $userData['picture'] = $userProfile['picture']['data']['url'];
          
          $userData['token'] = $this->session->userdata('fb_access_token');
          $userData['token_expiration'] = $this->session->userdata('fb_expire');

          $userID = $userProfile['facebook_id'];
          $this->fb_id = $userID;

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
              if(array_key_exists('id', $userProfile))
              {
                $userData['oauth_uid'] = $userProfile['id'];
                $userData['facebook_id'] = $userProfile['id'];
              }
                
              if(array_key_exists('first_name', $userProfile))  
                $userData['first_name'] = $userProfile['first_name'];

              if(array_key_exists('last_name', $userProfile))  
                $userData['last_name'] = $userProfile['last_name'];

              if(array_key_exists('email', $userProfile))  
                $userData['email'] = $userProfile['email'];

              if(array_key_exists('gender', $userProfile))  
                $userData['gender'] = $userProfile['gender'];

              if(array_key_exists('locale', $userProfile))  
                $userData['locale'] = $userProfile['locale'];

              $userData['logged_in'] = true;

              if(array_key_exists('picture', $userProfile))
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
        if($tipo == 'campaigns')
          $ret .= "<option value='-1'>Selecione</option>";
        else
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

      if(!$results)
      {
        $ret = "<h2>Sem PostBacks Cadastrados</h2>";
      }
      else
      {
        foreach($results as $row)
        {
          $ret .= "<tr>";
          $ret .= "<td><input type='checkbox' name='checkbox-inline' class='chkToken' id='" . $row->platform_user_id . "'></td>";
          $ret .= "<td>" . $row->plataforma . "</td>";
          $ret .= "<td>" . $row->token . "</td>";
          $ret .= "<td>" . $row->created_time . "</td>";
          $ret .= "</tr>";
        }
      }

      echo $ret; 
    }

    public function get_activities()
    {
      ob_start();
      
      $profiles = $this->metricas->get_profiles();

      foreach($profiles as $profile)
      {

        //Get token de acesso ao Facebook
        $usr = $this->metricas->getProfileToken($profile->facebook_id);
        $this->usrtkn = $usr->token;
        $this->fb_id =  $usr->facebook_id;

        echo $profile->first_name . "<br>";
        ob_flush();

        //$contas_bd = $this->metricas->getContas($this->fb_id);

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

      $contas_bd = $contas;

      //print_r($contas_bd);

      if($contas_bd)
      {
        foreach($contas_bd as $conta_user)
        {
          echo $conta_user['account_id'] . "<br>";
          ob_flush();

          $conta_add = null;

          $detalhes = $this->facebook->request('get', 'act_' . $conta_user['account_id']  . 
          '?fields=account_id,account_status,age,amount_spent,balance,business_city,business_country_code,business_name,business_state,business_street,business_street2,business_zip,can_create_brand_lift_study,created_time,currency,disable_reason,funding_source,funding_source_details,has_migrated_permissions,id,is_attribution_spec_system_default,is_direct_deals_enabled,is_notifications_enabled,is_personal,is_prepay_account,is_tax_id_required,min_campaign_group_spend_cap,min_daily_budget,name,offsite_pixels_tos_accepted,owner,spend_cap,tax_id,tax_id_status,tax_id_type,timezone_id,timezone_name,timezone_offset_hours_utc,user_role',
          $this->usrtkn);

          if(isset($detalhes['error']))
            die('Erro. Tente novamete');

          $conta = $detalhes;
          $conta['facebook_id'] = $this->fb_id;

          if($conta['age'] > '0')
          {
            if(array_key_exists('funding_source_details', $conta))
            {
              $conta['funding_source_details_id'] = $conta['funding_source_details']['id'];
              $conta['funding_source_details_display_string'] = $conta['funding_source_details']['display_string'];
              $conta['funding_source_details_type'] = $conta['funding_source_details']['type'];
              unset($conta['funding_source_details']);
            }

            $date = explode("T", $conta['created_time']);
            $begin = $this->metricas->get_last_activity($conta['account_id']);

            if(!$begin)
              $begin = $date[0];


            $now = date('Y-m-d',strtotime($begin . "+60 days"));
            $today = date('Y-m-d');

            if($now > $today)
              $now = $today;

            if($begin > $today)
              $begin = $today;

            //$now = date("Y-m-d");
            //Busca os dados a serem sincronizados no Facebook
            $detalhes = $this->facebook->request('get',
              $conta['id'].'/activities?fields=actor_id,actor_name,application_id,application_name,date_time_in_timezone,event_time,event_type,extra_data,object_id,object_name,translated_event_type&since=' . $begin . '&until=' . $now . '&limit=1000',
              $this->usrtkn);

            $activities = false;


            if(!isset($detalhes['error']))
            {
              $k = 30;
              while(array_key_exists('paging', $detalhes))
              {
                $now = date('Y-m-d',strtotime($begin . "+" . (int)$k . " days")); 
                $detalhes = $this->facebook->request('get',
                  $conta['id'].'/activities?fields=actor_id,actor_name,application_id,application_name,date_time_in_timezone,event_time,event_type,extra_data,object_id,object_name,translated_event_type&since=' . $begin . '&until=' . $now . '&limit=1000',
                  $this->usrtkn); 
                $k /= 2;
              }  
            }

            if(!isset($detalhes['error']))
            {

              $activities = $detalhes['data'];

              if(array_key_exists('paging', $detalhes))
              {
                if(array_key_exists('next', $detalhes['paging']))
                {
                  $next = $detalhes['paging']['next'];
                  while($next != '')
                  {
                    log_message('debug', "Quantos: " . count($activities)); 

                    $retorno = $this->process_pagination($next);

                    if($retorno)
                    {
                      if(array_key_exists('paging', $retorno))
                      {
                        if(array_key_exists('next', $retorno['paging']))
                          $next = $retorno['paging']['next'];
                        else
                          $next = '';
                      }
                    }

                    $activities = array_merge($activities, $retorno['data']);
                  }
                }
              }
            }
            else
            {
                log_message('debug', 'Erro: ' . $detalhes['error']);
            }

            if($activities)
            {
              $activities[] = array("event_type" => "controle", "event_time" => $now."T00:00:00");
              $this->metricas->insert_activity($activities, $conta['account_id'], $usr->facebook_id);
              $this->metricas->insert_contas_info($conta);
            }
            else
            {
              $activities[] = array("event_type" => "controle", "event_time" => $now."T00:00:00");
              $this->metricas->insert_activity($activities, $conta['account_id'], $usr->facebook_id);
            }
            
          }
          
        }
      }
      }

      
    ///act_815527071921444/activities?fields=actor_id,actor_name,application_id,application_name,date_time_in_timezone,event_time,event_type,extra_data,object_id,object_name,translated_event_type&since=2015-01-01&until=2017-08-09
      ob_end_flush();
    }

    public function show_grafico()
    {
      $this->load->view('show_grafico',null);  
    }

    public function show_activities()
    {
      $profiles = $this->metricas->get_profiles();

      $data['profiles'] = $profiles;

      $this->load->view('show_activities',$data);  
    }

    public function get_accounts_info()
    {
      if(isset($_POST['profile']))
      {
        $profile = $this->input->post('profile');

        $accounts = $this->metricas->get_accounts_info($profile);

        $retorno = "<option value='-1'>Selecione</option>";
        foreach($accounts as $conta)
        {
          $retorno .= "<option value='" . $conta->id . "'>" . $conta->name . "</option>";
        }

        echo $retorno;
      }
    }

    public function show_conta_activities()
    {
      if(isset($_POST['account']))
      {
        $account = $this->input->post('account');
        $account = str_replace("act_","",$account);
        $activities = $this->metricas->show_conta_activities($account); 

        if(count($activities) == 0)
          die("Sem atividades registradas");

        $data = explode(" at ", $activities[0]->date_time_in_timezone);

        if(!isset($intervalos))
        {
          $date = DateTime::createFromFormat('m/d/Y', $data[0]);
          $hoje = new DateTime( );

          $interval = DateInterval::createFromDateString('1 day');
          $intervalos = new DatePeriod($date, $interval, $hoje);  

          foreach($intervalos as $dt)
          {
            $periodo[] = $dt->format('m/d/Y');
          }

        }

        $i = 0;
        $retorno = "<table class='table'>";
        foreach($periodo as $data)
        {
          if($i < count($activities))
          {
            $data_activity = explode(" at ", $activities[$i]->date_time_in_timezone);
            $retorno .= "<tr><td colspan=3><h3>" . $data . "</h3></td></tr>";

            while($data == $data_activity[0])
            {
              if($activities[$i]->tipo == 'campanha')
              {
                $msg = "<strong>" . $activities[$i]->name . "</strong>";
                $msg .= " - " . $activities[$i]->id . "<br>" . $activities[$i]->event_type;
                $retorno .= "<tr><td class='campanha campanha_" . $activities[$i]->id . "' data-extra='" . $activities[$i]->extra_data .  "'>" . $msg . "</td><td></td><td></td></tr>";
                //$array_activity[$data][$activities[$i]->id][$activities[$i]->event_type] = $activities[$i]->extra_data;
              }
              else if($activities[$i]->tipo == 'conjunto')
              {
                $msg = "<strong>" . $activities[$i]->name . "</strong>";
                $msg .= " - " . $activities[$i]->id . "<br>" . $activities[$i]->event_type;
                $retorno .= "<tr><td></td><td class='conjunto 
                  campanha_" . $activities[$i]->campanha_id . "
                  conjunto_" . $activities[$i]->id . "' data-extra='" . $activities[$i]->extra_data .  "'>" . $msg . "</td><td></td></tr>";
                //$array_activity[$data][$activities[$i]->campanha_id][$activities[$i]->id][$activities[$i]->event_type] = $activities[$i]->extra_data;
              }
              else if($activities[$i]->tipo == 'anuncio')
              {
                $msg = "<strong>" . $activities[$i]->name . "</strong>";
                $msg .= " - " . $activities[$i]->id . "<br>" . $activities[$i]->event_type;
                $retorno .= "<tr><td></td><td></td><td class='anuncio 
                  campanha_" . $activities[$i]->campanha_id . "
                  conjunto_" . $activities[$i]->conjunto_id . "
                  anuncio_" . $activities[$i]->id . "' data-extra='" . $activities[$i]->extra_data .  "'>" . $msg . "</td></tr>";
                //$array_activity[$data][$activities[$i]->campanha_id][$activities[$i]->conjunto_id][$activities[$i]->id][$activities[$i]->event_type] = $activities[$i]->extra_data;
              }

              log_message('debug', 'Processando: ' . $i . " de " . count($activities) . " Data: " . $data ); 
              
              $i++;
              if($i < count($activities))
                $data_activity = explode(" at ", $activities[$i]->date_time_in_timezone);
              else
                break;
            }
          }
        }

        $retorno .= "</table>";

        echo $retorno;
      }
    }

    public function show_conta_activities_graph()
    {
      if(isset($_POST['account']))
      {
        $account = $this->input->post('account');
        $account = str_replace("act_","",$account);
        $activities = $this->metricas->show_conta_activities($account); 

        $data = explode(" at ", $activities[0]->date_time_in_timezone);

        if(!isset($intervalos))
        {
          $date = DateTime::createFromFormat('m/d/Y', $data[0]);
          $hoje = new DateTime( );

          $interval = DateInterval::createFromDateString('1 day');
          $intervalos = new DatePeriod($date, $interval, $hoje);  

          foreach($intervalos as $dt)
          {
            $periodo[] = $dt->format('m/d/Y');
          }

        }

        foreach($activities as $activity)
        {
          $data_activity = explode(" at ", $activity->date_time_in_timezone);

          if($activity->event_type == 'create_ad')
          {
            $anuncio[$activity->id]['criou'] = $data_activity[0];
          }
          else if($activity->event_type == 'ad_review_approved')
          {
            $anuncio[$activity->id]['aprovou'] = $data_activity[0];
          }
          else if($activity->event_type == 'update_ad_run_status' && 
            strpos($activity->extra_data,'"new_value":"Inactive"') !== false)
          {
            $anuncio[$activity->id]['parou'] = $data_activity[0];
          }
          else if($activity->event_type == 'update_ad_set_budget')
          {
            $anuncio[$activity->id]['orcamento'] = $data_activity[0];
            $anuncio[$activity->id]['orcamento_valor'] = $activity->extra_data;
          }
        }

        $ativos = 0;
        $i = 0;
        $retorno = "";
        foreach($periodo as $data)
        {
          $orcamento = false;
        
          foreach($anuncio as $a)
          {
            if(isset($a['aprovou']))
            {
              if($a['aprovou'] == $data)
              {
                $ativos++;
              }
            }
            else if(isset($a['parou']))
            {
              if($a['parou'] == $data)
              {
                $ativos--;
              }
            }
            else if(isset($a['orcamento']))
            {
              if($a['orcamento'] == $data)
              {
                $orcamento = true;
              }  
            }
          }

          $retorno .= "<h2>" . $data . "</h2>";
          $retorno .= "Anuncios Ativos: " . $ativos . "<br>";
          if($orcamento)
            $retorno .= "Mexeu no orçamento <br>";
        }

        echo $retorno;
      }
    }

    public function preview_ad()
    {
      if(isset($_POST))
      {
        $ad_id = $this->input->post('anuncio');
        $retorno = $this->metricas->get_ad_data_preview($ad_id);

        $msg = $retorno->body . "<br>";
        $msg .= "<img src='" . $retorno->image_url . "' width=500px/><br>";
        $msg .= "<h4>" . $retorno->title . "</h4><br>";
        $msg .= $retorno->object_story_spec_link_data_description . "<br>";
        $msg .= $retorno->call_to_action_type . "<br>";
        $msg .= $retorno->object_story_spec_link_data_link . "<br>";

        $msg .= "<table border=1>";
        $msg .= "<tr><td>Primeira Data</td><td>" . $retorno->primeira . "</td></tr>";
        $msg .= "<tr><td>Ultima Data</td><td>" . $retorno->ultima . "</td></tr>";
        $msg .= "<tr><td>Janela</td><td>" . $retorno->attribution_spec_window_days . "</td></tr>";
        $msg .= "<tr><td>Promovendo</td><td>" . $retorno->promoted_object_custom_event_type . "</td></tr>";
        $msg .= "<tr><td>Idade</td><td>" . $retorno->age_min . "-" . $retorno->age_max . "</td></tr>";
        $msg .= "<tr><td>Devices</td><td>" . $retorno->device_platforms . "</td></tr>";
        $msg .= "<tr><td>Publisher</td><td>" . $retorno->publisher_platforms . "</td></tr>";
        $msg .= "<tr><td>Posições</td><td>" . $retorno->facebook_positions . "</td></tr>";
        $msg .= "<tr><td>Sexo</td><td>" . $retorno->genders . "</td></tr>";
        $msg .= "<tr><td>CPC</td><td>" . $retorno->cpc . "</td></tr>";
        $msg .= "<tr><td>CTR</td><td>" . $retorno->ctr . "</td></tr>";
        $msg .= "<tr><td>CPM</td><td>" . $retorno->cpm . "</td></tr>";
        $msg .= "<tr><td>Relevancia</td><td>" . $retorno->relevance_score_score . "</td></tr>";
        $msg .= "<tr><td>Investimento</td><td>" . $retorno->spend . "</td></tr>";
        $msg .= "<tr><td>Impressoes</td><td>" . $retorno->impressions . "</td></tr>";
        $msg .= "<tr><td>Cliques</td><td>" . $retorno->clicks . "</td></tr>";
        $msg .= "</table>";

        $msg .= "<table border=1>";
        $msg .= "<tr><td>Audiencia Personalizada</td><td>" . $retorno->custom_audiences . "</td></tr>";
        $msg .= "<tr><td>Excluindo</td><td>" . $retorno->excluded_custom_audiences . "</td></tr>";
        $msg .= "<tr><td>Local</td><td>" . $retorno->geo_locations . "</td></tr>";
        $msg .= "<tr><td>Excluindo</td><td>" . $retorno->excluded_geo_locations . "</td></tr>";
        $msg .= "<tr><td>Interesses</td><td>" . $retorno->flexible_spec_interests . "</td></tr>";
        $msg .= "<tr><td>Comportamento</td><td>" . $retorno->flexible_spec_behaviors . "</td></tr>";
        $msg .= "<tr><td>Excluindo</td><td>" . $retorno->exclusions . "</td></tr>";
        $msg .= "</table>";

        echo $msg;
      }
    }
    

}



