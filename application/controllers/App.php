<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class App extends CI_Controller {

    private $usrtkn = null;
    private $fb_id = null;

    function __construct() {
        parent::__construct();

        // Load facebook library
        $this->load->library('facebook');
        

        //$this->load->library('excel_build');
        $this->load->library('table_build');

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

      if(array_key_exists('error',$accounts))
      {
        log_message('error', print_r($detalhes, true));
        die('Erro. Tente novamente!');
      }
        
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
      log_message('debug', 'Sync_contas' . $this->input->raw_input_stream);

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
      log_message('debug','In ' . __LINE__ . " : " . json_encode($detalhes));

      if(array_key_exists('error',$detalhes))
      {
        log_message('error', print_r($detalhes, true));
        return false;
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

      $results = $this->metricas->getContasInfo();

      if($results)
      {
        $fb_id = "";
        foreach($results as $res)
        {
          if($fb_id != $res->facebook_id)
          {
            $fb_id = $res->facebook_id;
            $token = $this->metricas->getProfileToken($fb_id)->token;
          }
          $url_params = get_param_contas_info(); 

          log_message('debug',$res->id.$url_params);
          
          $detalhes = $this->facebook->request('get',$res->id.$url_params,$token);
          log_message('debug',json_encode($detalhes));

          if(array_key_exists('error',$detalhes))
          {
            log_message('error', print_r($detalhes, true));
            continue;
          }

          ////////PROCESSAMENTO DE RESPOSTAS

          $ads = $detalhes['ads']['data'];
          $adsets = $detalhes['adsets']['data'];
    
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
          
          unset($detalhes['ads']);
          unset($detalhes['adsets']);
    
          //Processa array de conjunto de anúncios
          $adsets = processa_adsets($adsets);
          //Processa array de anúncios
          $ads = processa_ads($ads);
          

          $this->metricas->insertAdSetInfo($adsets);
          $this->metricas->insertAdInfo($ads);




          ///////
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
    public function grava_bd($detalhes, $completa, $fb_id = '0')
    {
      if($fb_id == 0)
        $fb_id = $this->fb_id;

      if($fb_id == null)
      {
        $fb_id = $this->session->userdata('facebook_id');
      }
      
      $campaigns = null;
      $ads = null;
      $adsets = null;
      //Apaga os dados antes de inserir novamente
      $this->metricas->deleteToNewSync(str_replace('act_','',$detalhes['id']), $completa);

      //Separa os arrays
      if(array_key_exists('campaigns', $detalhes))
        $campaigns = $detalhes['campaigns']['data'];

      if(array_key_exists('ads', $detalhes))  
        $ads = $detalhes['ads']['data'];

      if(array_key_exists('adsets', $detalhes))  
        $adsets = $detalhes['adsets']['data'];

      //Se existir paginamento de campanhas, processa para incluir no array
      if(array_key_exists('campaigns', $detalhes))
      {
        if(array_key_exists('next', $detalhes['campaigns']['paging']))
        {
          $next = $detalhes['campaigns']['paging']['next'];
          while($next != '')
          {
            $retorno = $this->process_pagination($next);

            if($retorno)
            {
              if(array_key_exists('next', $retorno['paging']))
                $next = $retorno['paging']['next'];
              else
                $next = '';

              $campaigns = array_merge($campaigns, $retorno['data']);
            }
          }
        }
      }

      //Se existir paginamento de conjuntos de anúncios, processa para incluir no array
      if(array_key_exists('adsets', $detalhes))
      {
        if(array_key_exists('next', $detalhes['adsets']['paging']))
        {
          $next = $detalhes['adsets']['paging']['next'];
          while($next != '')
          {
            $retorno = $this->process_pagination($next);
            
            if($retorno)
            {
              if(array_key_exists('next', $retorno['paging']))
                $next = $retorno['paging']['next'];
              else
                $next = '';

              $adsets = array_merge($adsets, $retorno['data']);
            }
          }
        }
      }

      //Se existir paginamento de anúncios, processa para incluir no array
      if(array_key_exists('ads', $detalhes))
      {
        if(array_key_exists('next', $detalhes['ads']['paging']))
        {
          $next = $detalhes['ads']['paging']['next'];
          while($next != '')
          {
            $retorno = $this->process_pagination($next);
            
            if($retorno)
            {
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
        }
      }

      //log_message('debug', 'COMPLETO: ' . print_r($detalhes, true));
      
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

      //Processa array de contas
      $contas = processa_contas($detalhes);
      //Processa array de campanhas
      $campaigns = processa_campaigns($campaigns);
      //Processa array de conjunto de anúncios
      $adsets = processa_adsets($adsets);
      //Processa array de anúncios
      $ads = processa_ads($ads);
      
      //Insere no banco de dados após processamento dos dados
      $this->metricas->insertAccount($contas);
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
        log_message('error', 'Sem acesso ao sistema');
        die('Erro. Sem acesso ao sistema');
      }

      if($tipo == 'campanha') $tipo = 'campaign';
      else if($tipo == 'conjunto') $tipo = 'adset';
      else if($tipo == 'anuncio') $tipo = 'ad';

      //Se não for só geral, processa os dados por dia
      if($sogeral == false)
      {
        $dt_inicio = $this->metricas->getLastDateSync($id, $tipo);

        $url_params = get_param_contas_data($dt_inicio);

        //Faz a chamada no Facebook
        $detalhes = $this->facebook->request('get', $id.'/insights'.$url_params,$this->usrtkn);

        if(array_key_exists('error',$detalhes))
        {
            log_message('error', print_r($detalhes, true));
        }
        else
        {
          log_message('debug', 'Resposta insight por data ' . json_encode($detalhes));
          
          //Chama a função para processamento do insight
          $this->processa_resposta_insight($detalhes, $tipo, true);
        }
      }

      $dt_inicio = $this->metricas->getFirstDate($id, $tipo);

      if(!$dt_inicio)
        $dt_inicio = $this->metricas->getLastDateSync($id, $tipo);

      $url_params = get_param_contas_data_simples($dt_inicio);  

      //Faz a chamada no Facebook
      $detalhes = $this->facebook->request('get', $id.'/insights'.$url_params,$this->usrtkn);

      if(array_key_exists('error',$detalhes))
      {
        log_message('error', print_r($detalhes, true));
      }
      else
      {
        log_message('debug','Resposta insight ' . json_encode($detalhes));
        
        //Chama a função para processamento do insight
        $this->processa_resposta_insight($detalhes, $tipo);
      }
      
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

      $fb_id = $this->session->userdata('facebook_id');

      //Caso não haja dados de postback sincronizado para o id
      $sem_dado_venda = true;
      //Traz os dados a serem mostrados na planilha
      $resultado = $this->metricas->getTableData($id, $tipo, $fb_id);
      //Traz os dados de postback sincronizado, por dia
      $dados_vendas = $this->metricas->dados_vendas($id, $tipo, $fb_id);
      //Traz os dados de postback sincronizado, geral
      $dados_vendas_geral = $this->metricas->dados_vendas_geral($id, $tipo, $fb_id);

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
            $dados->produto = $venda->produto;
            $sem_dado_venda = false;
          }

          //Faz o cálculo de %PurchaseCheckout
          if(isset($dados->conversao->{"offsite_conversion.fb_pixel_initiate_checkout"})
              && isset($dados->conversao->{"offsite_conversion.fb_pixel_purchase"}))
          {
            if($dados->conversao->{"offsite_conversion.fb_pixel_initiate_checkout"} != "")
            {
              $dados->purchase_checkout = 
                ((int)$dados->conversao->{"offsite_conversion.fb_pixel_purchase"} /
                (int)$dados->conversao->{"offsite_conversion.fb_pixel_initiate_checkout"}) * 100;
            }
          }

          //Faz o cálculo de %CheckoutViewContent
          if(isset($dados->conversao->{"offsite_conversion.fb_pixel_initiate_checkout"})
              && isset($dados->conversao->{"offsite_conversion.fb_pixel_view_content"}))
          {
            if($dados->conversao->{"offsite_conversion.fb_pixel_view_content"} != "")
            {
              $dados->checkout_view = 
                ((int)$dados->conversao->{"offsite_conversion.fb_pixel_initiate_checkout"} /
                (int)$dados->conversao->{"offsite_conversion.fb_pixel_view_content"}) * 100;
            }
          }

          //Faz o cálculo de %PurchaseViewContent
          if(isset($dados->conversao->{"offsite_conversion.fb_pixel_purchase"})
              && isset($dados->conversao->{"offsite_conversion.fb_pixel_view_content"}))
          {
            if($dados->conversao->{"offsite_conversion.fb_pixel_view_content"} != "")
            {
              $dados->purchase_view = 
                ((int)$dados->conversao->{"offsite_conversion.fb_pixel_purchase"} /
                (int)$dados->conversao->{"offsite_conversion.fb_pixel_view_content"}) * 100;
            }
          }

          $dados->dia_da_semana = '';
          $retorno[] = $dados;
        }

        //Chama a função de gerar planilha
        //$filename = $this->excel_build->generate_excel($retorno, $this->metricas, $sem_dado_venda, $comissao, 1, $id, $tipo);
        $filename = $this->table_build->generate_table($retorno, $this->metricas, $sem_dado_venda, $comissao, 1, $id, $tipo);        

        $resumo = false;

        $html_return = array(
          'size' => strlen($filename),
          'content' => $filename
        );

        $ret = array("filename" => $html_return,
                      "dados" => $retorno,
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

      $retorno = $this->metricas->get_resumo($id, $tipo, $comissao,$this->session->userdata('facebook_id'));

      if(!$retorno)
        return false;

      $header = "<tr>";
      $body = "";

      if($tipo == 'account')
      {
        $subtipo = 'campanha';
        $menor = 'conjuntos';
        $count = 6;

        $header .="<th></th>";
        $header .= "<th>Status</th>";
        $header .= "<th>Nome</th>";
        $header .= "<th>Objetivo</th>";
        $header .= "<th>Conversões</th>";
        $header .="<th></th>";
        $header .= "</tr>";

        foreach($retorno as $ret)
        {
          $conv = "-";

          $body .= '<tr>';
          $body .= '<td width="10px"><button class="btn_conjuntos" id="' . $ret['id'] . '">+</button></td>';
          $body .= '<td>' . $ret['status'] . '</td>';
          $body .= '<td>' . $ret['name'] . '</td>';
          $body .= '<td>' . $ret['objective'] . '</td>';
          if($translate)
          {
            foreach($translate as $key => $val)
            {
              if(isset($ret[$key]))
                $conv .= "<strong>" . $val . ":</strong> " . $ret[$key] ."<br>";
            }
          }
          $body .= '<td style="font-size: x-small;">' . $conv . '</td>';
          $body .= '<td width="10px"><button class="btnvernumeros" id="campanha_' . $ret['id'] . '">Ver números</button></td>';

          $body .= "</tr>";
          $body .= "<tr class='campanhas' id='tr_" . $ret['id'] . "' style='display: none;background-color:green;'>";
          $body .= "<td id=td_" . $ret['id'] . " colspan=" . $count . "></td>";
        }

      }
      else if($tipo == 'campaign')
      {
        $header .="<th></th>";
        $subtipo = 'conjunto';
        $menor = 'anuncios';
        $count = 9;

        $header .= "<th>Status</th>";
        $header .= "<th>Nome</th>";
        $header .= "<th>Objetivo</th>";
        $header .= "<th>Métricas</th>";
        $header .= "<th>Orçamento</th>";
        $header .= "<th>Conversões</th>";
        $header .="<th></th>";
        $header .= "</tr>";

        foreach($retorno as $ret)
        {
          $conv = "-";

          $body .= '<tr>';
          $body .= '<td width="10px"><button class="btn_anuncios" id="' . $ret['id'] . '">+</button></td>';
          $body .= '<td>' . $ret['status'] . '</td>';
          $body .= '<td>' . $ret['name'] . '</td>';
          $body .= '<td>' . $ret['objetivo'] . '</td>';

          $metricas = "CPC: " . $ret['cpc'] . "<br>";
          $metricas .= "CTR: " . $ret['ctr'] . "<br>";
          $metricas .= "CPM: " . $ret['cpm'] . "<br>";

          $body .= '<td>' . $metricas . '</td>';

          $metricas = "Por Dia: " . $ret['daily_budget'] . "<br>";
          $metricas .= "Resta: " . $ret['budget_remaining'] . "<br>";

          $body .= '<td>' . $metricas . '</td>';

          if($translate)
          {
            foreach($translate as $key => $val)
            {
              if(isset($ret[$key]))
                $conv .= "<strong>" . $val . ":</strong> " . $ret[$key] ."<br>";
            }
          }
          $body .= '<td style="font-size: x-small;">' . $conv . '</td>';
          $body .= '<td width="10px"><button class="btnvernumeros" id="conjunto_' . $ret['id'] . '" >Ver números</button></td>';

          $body .= "</tr>";
          $body .= "<tr class='conjuntos' id='tr_" . $ret['id'] . "' style='display: none;background-color:yellow;'>";
          $body .= "<td id=td_" . $ret['id'] . " colspan=" . $count . "></td>";
        }
      }
      else 
      {
        $count = 4;
        $subtipo = 'anuncio';

        $header .= "<th>Status</th>";
        $header .= "<th>Nome</th>";
        $header .= "<th>Métricas</th>";
        $header .= "<th>Gasto</th>";
        $header .= "<th>Relevância</th>";
        $header .= "<th>Conversões</th>";
        $header .= "<th></th>";
        $header .="<th></th>";
        $header .= "</tr>";

        foreach($retorno as $ret)
        {
          $conv = "-";

          $view_ad = explode("_", $ret['effective_object_story_id']);

          $body .= '<tr>';
          $body .= '<td>' . $ret['status'] . '</td>';
          $body .= '<td>' . $ret['name'] . '</td>';

          $metricas = "CPC: " . $ret['cpc'] . "<br>";
          $metricas .= "CTR: " . $ret['ctr'] . "<br>";
          $metricas .= "CPM: " . $ret['cpm'] . "<br>";

          $body .= '<td>' . $metricas . '</td>';
          $body .= '<td>' . $ret['spend'] . '</td>';
          $body .= '<td>' . $ret['relevancia'] . '</td>';
          if($translate)
          {
            foreach($translate as $key => $val)
            {
              if(isset($ret[$key]))
                $conv .= "<strong>" . $val . ":</strong> " . $ret[$key] ."<br>";
            }
          }
          $body .= '<td style="font-size: x-small;">' . $conv . '</td>';
          $body .= '<td><a href="https://facebook.com/' . $view_ad[0] . '/posts/' . $view_ad[1] . '" target="_blank">Ver Anúncio</a></td>';
          $body .= '<td width="10px"><button class="btnvernumeros" id="anuncio_' . $ret['id'] . '">Ver números</button></td>';

          $body .= "</tr>";
        }
      } 
      
      
        

      //$val_ret = "<h2>" . $title . "</h2>";
      $val_ret = "<table class='table table-bordered table-striped table-condensed table-hover'>";
      $val_ret .= $header;
      $val_ret .= $body;
      $val_ret .= "</table>";

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

        if(!$results)
          continue;

        foreach($results as $result)
        {
          //Sincroniza as contas
          if(!$this->sync_contas($result->account_id, $completa))
            continue;
          
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
        else if($tipo[$i] == "Boleto Pago")
          $tp = "boletos_pagos";
        else if($tipo[$i] == "Cartão")
          $tp = "cartoes";

        $item = [];

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

      $this->load->view('metricas/ger_assoc',$data);

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
      $contas = $this->metricas->getContasDetalhes($userID);
      $data['contas'] = $contas;

      // Load login & profile view
      $this->load->view('metricas/main',$data);
    }

    /**
    * desempenho_produto
    *
    * Carrega página que mostra um resumo consolidado dos dados de postback por produto
    *  e seu próprio desempenho comparando com outros anunciantes.
    */
    public function desempenho_produto()
    {
      $id = $this->session->userdata('facebook_id');

      $tokens = $this->metricas->getUserTokens($id);

      if(!$tokens)
        $data['token_msg'] = "Você não tem nenhum postback configurado!";

      $vendas = $this->metricas->get_best_ads($id);

      if(!$vendas)
        $data['ads_msg'] = "Não existem anúncios com vendas na sua conta";
      else
      {
        foreach($vendas as $venda)
        {
          if(empty($venda->tipo_id))
          {
            $data['ads_msg'] = "Existem anúncios que não foram acessadas as métricas, por favor gere as métricas para todos os anúncios das contas cadastradas para ter o seu resultado completo!";
          }
        } 
      }

      $data['vendas'] = $vendas;

      $this->load->view('metricas/desempenho_produto',$data);   

    }

    /**
    * painel
    *
    * Traz os tokens para o usuario logado
    */
    public function painel()
    {
      log_message('debug', 'desempenho_produto'); 

      $id = $this->metricas->getuserid($this->session->userdata('facebook_id'));
      
      $result = $this->metricas->getProdutosByUser($id);

      $data['produtos'] = $result;

      //Pega desempenho de todos os produto
      //$data['desempenho'] = $this->get_desempenho_produto(true);

      $tokens = $this->metricas->getUserTokens($id);

      if(!$tokens)
        $data['token_msg'] = "Você não tem nenhum postback configurado!";

      $vendas = $this->metricas->get_best_ads($id);

      if(!$vendas)
        $data['ads_msg'] = "Não existem anúncios com vendas na sua conta";

      $this->load->view('metricas/painel',$data);
      
    }

    /**
    * get_desempenho_produto
    *
    * Carrega informações do resumo consolidado dos dados de postback por produto
    *  e seu próprio desempenho comparando com outros anunciantes.
    */
    public function get_desempenho_produto($todos = false)
    {
      log_message('debug', 'get_desempenho_produto  '); 

      if(isset($_POST['produto']) || $todos)
      {
        $produto = $this->input->post('produto');
        $val = $this->input->post('val');
        $plataforma = $this->input->post('plataforma');

        if($todos || $val == -1)
        {
          $produto = false;
          $val = null;
          $plataforma = false;
        }
        
        $venda_array = array();
        $vendas_user_cartao = $this->metricas->get_vendas_plataforma($this->session->userdata('facebook_id'), $plataforma, false, $produto, 'Cartão');
        $vendas_user_bimpresso = $this->metricas->get_vendas_plataforma($this->session->userdata('facebook_id'), $plataforma, false, $produto, 'Boleto Impresso');
        $vendas_user_bpago = $this->metricas->get_vendas_plataforma($this->session->userdata('facebook_id'), $plataforma, false, $produto, 'Boleto Pago');
        $vendas_user_devolvida = $this->metricas->get_vendas_plataforma($this->session->userdata('facebook_id'), $plataforma, false, $produto, 'Devolvida');

        $vendas_outros_cartao = $this->metricas->get_vendas_plataforma($this->session->userdata('facebook_id'), $plataforma, false, $produto, 'Cartão', true);
        $vendas_outros_bimpresso = $this->metricas->get_vendas_plataforma($this->session->userdata('facebook_id'), $plataforma, false, $produto, 'Boleto Impresso', true);
        $vendas_outros_bpago = $this->metricas->get_vendas_plataforma($this->session->userdata('facebook_id'), $plataforma, false, $produto, 'Boleto Pago', true);
        $vendas_outros_devolvida = $this->metricas->get_vendas_plataforma($this->session->userdata('facebook_id'), $plataforma, false, $produto, 'Devolvida', true);

        $user_cartao = count($vendas_user_cartao);
        $user_bimpresso = count($vendas_user_bimpresso);
        $user_bpago = count($vendas_user_bpago);
        $user_devolvida = count($vendas_user_devolvida);

        $totais = $this->metricas->get_vendas_totais($this->session->userdata('facebook_id'));
        if($totais !== false)
          $user_vendas = $totais;
        else
          $user_vendas = $user_cartao + $user_bpago - $user_devolvida;

        $user_conversao = $user_bpago == 0 ? 0 : ($user_bpago / ($user_bimpresso + $user_bpago)) * 100;

        $today = date('Y-m-d');
        //$today = '2017-07-14';
        
        $intervalo = 0;
        if($user_vendas > 0 && $totais === false)
        {
          $primeira_data_user[] = $user_cartao == 0 ? null : $vendas_user_cartao[0]->data_compra;
          $primeira_data_user[] = $user_bpago == 0 ? null : $vendas_user_bpago[0]->data_compra;

          $primeira_venda = min(array_filter($primeira_data_user));
          
          $dt1 = new DateTime($primeira_venda);
          $dt2 = new DateTime('today');
          $intervalo = $dt2->diff($dt1)->format('%a');  
        }
        else
        {
          $primeira_venda = date('Y-m-d', strtotime("-30 days"));
        }

        $periodo = $this->metricas->filtro_periodo($this->session->userdata('facebook_id'), true);
        if(!$periodo) 
          $periodo = date('Y-m-d', strtotime($primeira_venda));

        $venda_array = createDateRange($periodo);

        $user_comissao_cartao = 0;
        $user_comissao_boleto = 0;
        $user_comissao_devolvida = 0;
        $user_venda_hoje = 0;
        $user_venda_cartao_hoje = 0;
        $user_venda_bpago_hoje = 0;
        $user_venda_bimpresso_hoje = 0;
        $user_comissao_cartao_hoje = 0;
        $user_comissao_boleto_hoje = 0;
        $user_comissao_devolvida_hoje = 0;

        if(is_array($vendas_user_cartao))
        {
          foreach($vendas_user_cartao as $venda)
          {
            $date = date('Y-m-d', strtotime($venda->data_compra));
            
            $venda_array[$date]++;

            if($date == $today)
            {
              $user_venda_hoje++;
              $user_venda_cartao_hoje++;
              $user_comissao_cartao_hoje += $venda->comissao; 
            }

            $user_comissao_cartao += $venda->comissao;          
          }
        }

        $comissao_boleto = 0;
        if(is_array($vendas_user_bpago))
        {
          foreach($vendas_user_bpago as $venda)
          {
            $date = date('Y-m-d', strtotime($venda->data_compra));
            
            $venda_array[$date]++;

            if($date == $today)
            {
              $user_venda_hoje++;
              $user_venda_bpago_hoje++;
              $user_comissao_boleto_hoje += $venda->comissao;  
            }

            $user_comissao_boleto += $venda->comissao;          
          }
        }

        if(is_array($vendas_user_bimpresso))
        {
          foreach($vendas_user_bimpresso as $venda)
          {
            if(date('Y-m-d', strtotime($venda->data_compra)) == $today)
            {
              $user_venda_bimpresso_hoje++;
            }

            $comissao_boleto += $venda->comissao;          
          }
        }

        $comissao_devolvida = 0;
        if(is_array($vendas_user_devolvida))
        {
          foreach($vendas_user_devolvida as $venda)
          {
            $comissao_devolvida += $venda->comissao; 
            
            if($venda->data_compra == date('Y-m-d', strtotime($today)))
            {
              $user_venda_hoje--;
              $user_comissao_devolvida_hoje += $venda->comissao; 
            }

            $user_comissao_devolvida += $venda->comissao; 
          }
        }
       
        $media_vendas_user = $intervalo == 0 ? 0 : $user_vendas / $intervalo;
        $user_comissao = $user_comissao_cartao + $user_comissao_boleto - $user_comissao_devolvida;
        $user_comissao_hoje = $user_comissao_cartao_hoje + $user_comissao_boleto_hoje - $user_comissao_devolvida_hoje;

        $data['user_vendas'] = $user_vendas;
        $data['media_vendas_user'] = sprintf("%.2f", $media_vendas_user);
        $data['user_comissao'] = sprintf("%.2f", $user_comissao);
        $data['user_cartao'] = $user_cartao;
        $data['user_bpago'] = $user_bpago;
        $data['user_bimpresso'] = $user_bimpresso;
        $data['user_conversao'] = sprintf("%.2f", $user_conversao);
        $data['user_devolvida'] = $user_devolvida;

        $data['user_venda_hoje'] = $user_venda_hoje;
        $data['user_comissao_hoje'] = sprintf("%.2f", $user_comissao_hoje);
        $data['user_cartao_hoje'] = $user_venda_cartao_hoje;
        $data['user_bpago_hoje'] = $user_venda_bpago_hoje;
        $data['user_bimpresso_hoje'] = $user_venda_bimpresso_hoje;
        $data['user_devolvida_hoje'] = $user_comissao_devolvida_hoje;

        $valor_gasto = $this->metricas->get_sum_gasto($this->session->userdata('facebook_id'), false, $produto);
        $valor_gasto_hoje = $this->metricas->get_sum_gasto($this->session->userdata('facebook_id'), $today, $produto);

        $data['valor_gasto'] = ($valor_gasto ? round($valor_gasto,2) : "0.00");
        $data['valor_gasto_hoje'] = ($valor_gasto_hoje ? round($valor_gasto_hoje,2) : "0.00");

        $html = $this->load->view('metricas/apresenta_desempenho',$data, true);

        $array_retorno['html'] = $html;
        $array_retorno['array_x'] = array_keys($venda_array);
        $array_retorno['array_y'] = array_values($venda_array);

        echo json_encode($array_retorno);
      }

    }

    /**
    *
    */
    public function login_as_other_user()
    {
      log_message('debug', 'login_as_other_user'); 
      $executionStartTime = microtime(true);

      $profiles = $this->metricas->get_profiles(); 

      $executionEndTime = microtime(true);
      $seconds = $executionEndTime - $executionStartTime;
      log_message('debug', '"This script is taking $seconds to execute."');

      $retorno = ''; 
      foreach($profiles as $profile)
      {
        $retorno .= "<option value=" . $profile->profile_id . ">" . $profile->first_name . " " . $profile->last_name .
          " Token expira em: " . date('d/m/Y',$profile->token_expiration) . "</option>"; 
      }

      $executionEndTime = microtime(true);
      $seconds = $executionEndTime - $executionStartTime;
      log_message('debug', '"This script is taking $seconds to execute."');

      $data['retorno'] = $retorno;
      $this->load->view('laou',$data);

      $executionEndTime = microtime(true);
      $seconds = $executionEndTime - $executionStartTime;
      log_message('debug', '"This script took $seconds to execute."');
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

        if(!array_key_exists('error',$userProfile))
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
          log_message('error', print_r($userProfile, true));
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

            if(!array_key_exists('error',$userProfile))
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
              $period = $this->metricas->checkUser($userData);

              $data['period'] = $period;

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
              log_message('error', print_r($userProfile, true));
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
    * set_period
    * 
    * Função para setar o período que os dados vão ser mostrados
    */
    public function set_period()
    {
      if(isset($_POST['periodo']))
      {
        $period = $this->input->post('periodo');
        $id = $this->session->userdata('facebook_id');
        $this->metricas->set_period_data($id, $period);
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

      if(array_key_exists('error',$detalhes))
      {
        log_message('error', print_r($detalhes, true));
        die('Erro. Tente novamente');
      }

      echo json_encode($result);
      
    }

    /**
    * fill_combo
    *
    * Função para preencher os resumos de conjunto e anúncio
    */
    public function fill_combo()
    {
      log_message('debug', 'fill_combo');

      $id = $this->input->post('id'); 
      $tipo = $this->input->post('tipo');  
      $comissao = 0;

      if($tipo == 'campanha')
        $tipo = 'account';
      else if($tipo == 'conjunto')
        $tipo = 'campaign';
      else
        $tipo = 'adset';

      $conversions = $this->metricas->getPossibleConversions($id, $tipo, true);
      $translate = translate_conversions($conversions, $this->metricas);

      $retorno = $this->get_resumo(trim($id), $tipo, $comissao, $translate,$this->session->userdata('facebook_id'));
    
      echo $retorno;
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

        $version = $this->config->item("facebook_graph_version");
        $str = "https://graph.facebook.com/" . $version . "/";
        $next = str_replace($str, '', $url);
        $detalhes = $this->facebook->request('get', $next, $this->usrtkn);

        if(array_key_exists('error',$detalhes))
        {
          log_message('error', print_r($detalhes, true));
          return false;
        }

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

      $config_planilha = $this->metricas->getConfigPlanilha(1);
      
      $data['config'] = $results;
      $data['config_planilha'] = $config_planilha;

      $this->load->view('metricas/config',$data);  
    }

    /**
    * save_config
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
    * save_config_planilha
    *
    * Salva configurações da planilha no banco. Chamado através de ajax do view config
    */
    public function save_config_planilha()
    {
      if(isset($_POST['meta1']))
      {
        $meta[] = $this->input->post('meta1');  
        $meta[] = $this->input->post('meta2');  
        $meta[] = $this->input->post('meta3');  
        $meta[] = $this->input->post('meta4');  

        $id = $this->session->userdata('facebook_id');

        $this->metricas->saveConfigPlanilha($meta, $id);
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

    public function resumo_funil()
    {
      log_message('debug', 'resumo_funil'); 
      $id = $this->input->post('id');
      $result = $this->metricas->get_info_funil($id);

      $data['resumo'] = $result;

      $ret = $this->load->view('metricas/resumo_funil',$data, true); 

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

        if(array_key_exists('error',$accounts))
        {
          log_message('error', print_r($detalhes, true));
          continue;
        }

        if(!array_key_exists('data', $detalhes))
          continue;

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

          if(array_key_exists('error',$detalhes))
          {
            log_message('error', print_r($detalhes, true));
            continue;
          }

          $conta = $detalhes;
          $conta['facebook_id'] = $this->fb_id;

          if($conta['age'] > '0')
          {
            if(array_key_exists('funding_source_details', $conta))
            {
              $conta['funding_source_details_id'] = $conta['funding_source_details']['id'];
              if(array_key_exists('display_string', $conta['funding_source_details'])) $conta['funding_source_details_display_string'] = $conta['funding_source_details']['display_string'];
              if(array_key_exists('funding_source_details', $conta['funding_source_details'])) $conta['funding_source_details_type'] = $conta['funding_source_details']['type'];
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


            if(!array_key_exists('error',$detalhes))
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
            else
            {
              log_message('error', print_r($detalhes, true));
            }

            if(!array_key_exists('error',$detalhes))
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

    public function show_best_ads()
    {
      $ads = $this->metricas->get_best_ads();

      $data['ads'] = $ads;

      $this->load->view('show_best_ads',$data);  
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

    public function get_info_best_ad()
    {
      if(isset($_POST['ad_id']))
      {
        $ad_id = $this->input->post('ad_id');
        $retorno = $this->metricas->get_info_best_ads($ad_id);

        $detalhes = $this->facebook->request('get', $retorno['ad_creatives']->id . '/previews?ad_format=DESKTOP_FEED_STANDARD', $retorno['token']);

        if(array_key_exists('error',$detalhes))
        {
          log_message('error', print_r($detalhes, true));
          return;
        }    

        $preview = $detalhes['data'][0]['body'];

        $html = "<table>";
        foreach($retorno['ad_creatives'] as $key => $val)
        {
          if($val != null)
          {
            $html .= "<tr>";
            $html .= "<td>" . $key . "</td>";
            $html .= "<td>" . $val . "</td>";
            $html .= "</tr>";
          }
        }
        $html .= "</table>";

        $html .= "<table>";
        foreach($retorno['adsets'] as $key => $val)
        {
          if($val != null)
          {
            $html .= "<tr>";
            $html .= "<td>" . $key . "</td>";
            $html .= "<td>" . $val . "</td>";
            $html .= "</tr>";
          }
        }
        $html .= "</table>";

        $html .= "<table>";
        if(!empty($retorno['adset_targeting']))
        {
          foreach($retorno['adset_targeting'] as $key => $val)
          {
            if($val != null)
            {
              $html .= "<tr>";
              $html .= "<td>" . $key . "</td>";
              $html .= "<td>" . $val . "</td>";
              $html .= "</tr>";
            }
          }
        }
        
        $html .= "</table>";

        $ret = array("preview" => $preview,
        "info" => $html);
  
        $js = json_encode($ret);
      }
      echo $js;
    }
    

}



