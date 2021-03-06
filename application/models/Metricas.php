<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Metricas extends CI_Model{
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * checkUser
    *
    * Insere ou atualiza dados do usuário do Facebook
    *
    * @param	$data: Array com os campos a serem inseridos no banco
    * @return	- 
    */
    public function checkUser($data)
    {   
        log_message('debug', 'checkUser');

        //Tira campos que não existem no banco
        if(isset($data['oauth_provider'])) unset($data['oauth_provider']);
        if(isset($data['oauth_uid'])) unset($data['oauth_uid']);
        if(isset($data['logged_in'])) unset($data['logged_in']);
        if(isset($data['picture'])) unset($data['picture']);

        //Verifica se existe este perfil no banco
        $this->db->where('facebook_id',$data['facebook_id']);
        $result = $this->db->get('profiles');

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        //Se existir atualiza, senão insere
        if($result->num_rows() > 0)
        {
            $data['updated_time'] = date("Y-m-d H:i:s");

            $this->db->where('facebook_id', $data['facebook_id']); 
            $this->db->update('profiles', $data);

            $period = $this->get_period_data($data['facebook_id']);

            return $period;
        } 
        else
        {
            $data['updated_time'] = date("Y-m-d H:i:s");
            $data['created_time'] = date("Y-m-d H:i:s");

            $data_user = $data;

            unset($data_user['token']);
            unset($data_user['token_expiration']);
            unset($data_user['gender']);
            unset($data_user['locale']);
            unset($data_user['facebook_id']);

            $this->db->insert('users', $data_user);

            $insert_id = $this->db->insert_id();
            $data['user_id'] = $insert_id;

            $this->db->insert('profiles', $data);

            $this->saveConfig('12', '1', $data['facebook_id']);

            return null;
            
        }

        log_message('debug', 'Last Query: ' . $this->db->last_query());
    }

    /**
    * getContasDetalhes
    *
    * Traz em detalhes, as contas ativas de um perfil no Facebook
    *
    * @param	id: Id do Facebook
    * @return	
    *    false se não encontrar nenhuma informação
    *    lista de anúncios
    */
    public function getContasDetalhes($id){
        log_message('debug', 'getContas');

        $this->db->select("accounts.id, accounts.name, accounts.updated_time, accounts.account_status, 
            accounts.balance,     
            sum(if(ads.effective_status = 'ACTIVE', 1, 0)) as anuncios_ativos");
        $this->db->from("accounts");
        $this->db->join("ads","accounts.id = ads.account_id", "left");
        
        //Traz somente os anúncios realmente ativos
        $this->db->where("accounts.account_status > 0");

        $this->db->where('accounts.facebook_id',$id);

        $this->db->group_by("accounts.name");

        $this->db->order_by("account_status");
        $this->db->order_by("updated_time", "DESC");

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

    /**
    * getContas
    *
    * Traz nome e id das contas sincronizadas de um perfil do Facebook cadastrado no sistema
    *
    * @param	id: Id do Facebook
    * @return	
    *    false se não encontrar nenhum anúncio
    *    lista de anúncios
    */
    public function getContas($id){
        log_message('debug', 'getContas');

        $this->db->select("accounts.name as account_name, accounts.id as account_id");
        $this->db->from("accounts");
        $this->db->where('accounts.facebook_id',$id);
        $this->db->where('accounts.account_status > 0');

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

    /**
    * getContasInfo
    *
    * Traz nome e id das contas sincronizadas de um perfil do Facebook cadastrado no sistema
    *
    * @return	
    *    false se não encontrar nenhum anúncio
    *    lista de anúncios
    */
    public function getContasInfo(){
        log_message('debug', 'getContasInfo');

        $this->db->select("name, id, facebook_id");
        $this->db->from("accounts_info");
        $this->db->order_by("facebook_id");

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

    /**
    * apaga_conta
    *
    * Elimina a conta das contas a serem sincronizadas e exibidas
    *
    * @param	id: Id da conta
    * @return	-
    */
    public function apaga_conta($id){
        log_message('debug', 'apaga_conta');

        $this->db->set("account_status" , "0");
        $this->db->where('id',$id);
        $this->db->update("accounts");

        log_message('debug', 'Last Query: ' . $this->db->last_query());
    }
    

    /**
    * getFromConta
    *
    * Traz nome, id e status do (ad, conjunto e campanha) a partir de uma conta cadastrada
    *
    * @param	id: Id da conta
    * @param    tipo string: ad, adset, campaign
    * @return	
    *    false se não encontrar nenhuma informação
    *    lista com o nome, id e status do tipo
    */
    public function getFromConta($id, $tipo){
        log_message('debug', 'getFromConta');

        if($tipo == 'campaign')
        {
            $this->db->select("name, id, effective_status");
            $this->db->from("campaigns");
            $this->db->where('account_id',$id);
        }
        elseif($tipo == 'adset')
        {
            $this->db->select("adsets.name, adsets.id, adsets.effective_status");
            $this->db->from("adsets");
            $this->db->where("adsets.account_id", $id);
        }
        elseif($tipo == 'ad')
        {
            $this->db->select("ads.name, ads.id, ads.effective_status");
            $this->db->from("ads");
            $this->db->where("ads.account_id", $id);
        }
        

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

    /**
    * getProfileToken
    *
    * Traz o token de um perfil de um usuário do sistema
    *
    * @param	id: Id do facebook logado
    * @return	
    *    false se não encontrar nenhuma informação
    *    array com token e facebook id
    */
    public function getProfileToken($id){
        log_message('debug', 'getProfileToken');

        $this->db->select("token, facebook_id");
        $this->db->from("profiles");
        $this->db->where('facebook_id',$id);

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->row();  
        else
            return false;   
    }

    /**
    * get_from_tipo
    *
    * Lista tipos ativos a partir de um outro tipo. Serve para preencher os combos na tela de seleção
    * para mostra da planilha
    *   - Se $tipo for campaigns usa account_id para buscar as campanhas 
    *   - Se $tipo for adsets usa campaign_id para buscar conjunto
    *   - Se $tipo for ads usa adset_id para buscar anúncios
    *
    * @param	id: Id do tipo
    * @param    tipo string: campaigns, adsets, ads
    * @return	
    *    "Nenhum ativo" se não encontrar nenhum tipo ativo
    *    lista de um determinado tipo
    */
    public function get_from_tipo($id, $tipo)
    {
        log_message('debug', 'get_from_tipo');


        if($tipo == 'ads')
        {
            $this->db->select("ads.name, ads.id, ad_creatives.effective_object_story_id");
            $this->db->from('ads');
            $this->db->join("ad_creatives", "ads.id = ad_creatives.ad_id");
        }
        else
        {
            $this->db->select("name, id");
            $this->db->from($tipo);
        }
        

        switch($tipo)
        {
            case 'campaigns':
                $where = "account_id";
                break;
            case 'adsets':
                $where = "campaign_id";
                break;
            case 'ads':
                $where = "ads.adset_id";
                break;
        }
        $this->db->where($where,$id);
        //Traz somente os ativos
        $this->db->where('effective_status', 'ACTIVE');

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return "Nenhum ativo";   

    
    }

    /**
    * insertAccount
    *
    * Insere no banco de dados os dados de uma conta após ser processado dos dados obtidos
    * do Facebook
    *
    * @param	arr_account array: As contas a serem inseridas no banco
    * @return	-
    */
    public function insertAccount($arr_account)
    {
        //Se tiver insights dentro da conta, separa do array recebido.
        if(array_key_exists('insights',$arr_account))
        {
            $arr_insights = $arr_account['insights'];
            if(array_key_exists('action', $array))
            {
                $arr_insights_action = $arr_insights['action'];  
                unset($arr_insights['action']);                  
            }
            unset($arr_account['insights']);
        }

        //Insere na conta
        if(!$this->db->insert('accounts', $arr_account))
            log_message('debug', 'Erro: ' . $this->db->error()->message);

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        //Se tiver insights e actions, insere no banco
        if(isset($arr_insights))
        {
            if(!$this->db->insert('accounts_insights',$arr_insights))
                log_message('debug', 'Erro: ' . $this->db->error()->message);
            log_message('error', 'Last Query: ' . $this->db->last_query());

            if(isset($arr_insights_action))
            {
                foreach($arr_insights_action as $action)
                {
                    if(!$this->db->insert('accounts_insights_actions', $action))
                        log_message('debug', 'Erro: ' . $this->db->error()->message);

                    //log_message('debug', 'Last Query: ' . $this->db->last_query());
                }
            }
        }
        
    }

    /**
    * insertCampaign
    *
    * Insere no banco de dados os dados das campanhas após serem processado dos dados obtidos
    * do Facebook
    *
    * @param	arr_campaign array: As campanhas a serem inseridas no banco
    * @return	-
    */
    public function insertCampaign($arr_campaign)
    {
        if($arr_campaign == null)
            return; 

        //Para cada campanha no array
        foreach($arr_campaign as $array)
        {
            //Verifica se tem insights e separa do array
            if(array_key_exists('insights',$array))
            {
                $arr_insights = $array['insights'];
                if(array_key_exists('action', $arr_insights))
                {
                    $arr_insights_action = $arr_insights['action'];  
                    unset($arr_insights['action']);                  
                }
                unset($array['insights']);
            }

            //Insere no banco
            if(!$this->db->insert('campaigns', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);
            
            log_message('debug', 'Last Query: ' . $this->db->last_query());

            //Se tiver insights e actions insere no banco
            if(isset($arr_insights))
            {
                if(!$this->db->insert('campaign_insights',$arr_insights))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);
                
                log_message('debug', 'Last Query: ' . $this->db->last_query());

                //Busca última id inserido para relacionar com actions
                $insert_id = $this->db->insert_id();

                if(isset($arr_insights_action))
                {
                    foreach($arr_insights_action as $action)
                    {
                        $action['campaign_insights_id'] = $insert_id;
                        if(!$this->db->insert('campaign_insights_actions', $action))
                            log_message('debug', 'Erro: ' . $this->db->error()->message);
                        //log_message('debug', 'Last Query: ' . $this->db->last_query());
                    }
                    unset($arr_insights_action);
                }
                unset($arr_insights);
            }
        }
        
    }

    /**
    * insertInsights
    *
    * Insere no banco de dados os dados do insights após ser processado dos dados obtidos
    * do Facebook
    *
    * @param	arr_insights array: Os insights a serem inseridos no banco
    * @param    tipo string: ad, adset ou campaign. Tipo do insight
    * @param    bydate boolean: padrão false. Se o insight é geral(false) ou por dia(true)
    * @return	-
    */
    public function insertInsights($arr_insights, $tipo, $bydate = false)
    {
        foreach($arr_insights as $array)
        {
            if(array_key_exists('action', $array))
            {
                $arr_action = $array['action'];
                unset($array['action']);
            }
            
            if($bydate)
                $array['bydate'] = 1;

            log_message('debug', 'Array to Insert: ' . print_r($array, true));    
            if(!$this->db->insert($tipo.'_insights',$array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);
            
            log_message('debug', 'Last Query: ' . $this->db->last_query());
            
            $insert_id = $this->db->insert_id();

            if(isset($arr_action))
            {
                log_message('debug', 'Array to Insert: ' . print_r($arr_action, true));
                foreach($arr_action as $action)
                {
                    $action[$tipo.'_insights_id'] = $insert_id;
                    if(!$this->db->insert($tipo.'_insights_actions', $action))
                        log_message('debug', 'Erro: ' . $this->db->error()->message);
                    //log_message('debug', 'Last Query: ' . $this->db->last_query());
                }
            }
            
        }
        
    }

    /**
    * insertAdSet
    *
    * Insere no banco de dados os dados de um conjunto após ser processado dos dados obtidos
    * do Facebook
    *
    * @param	arr_adset array: Os conjuntos a serem inseridos no banco
    * @return   -
    */
    public function insertAdSet($arr_adset)
    {
        if($arr_adset == null)
            return; 
        
        //Para cada conjunto
        foreach($arr_adset as $array)
        {
            //Se tiver targeting no array, separa
            if(array_key_exists('targeting',$array))
            {
                $arr_targeting = $array['targeting'];
                unset($array['targeting']);
            }

            //Se tiver insights, separa
            if(array_key_exists('insights',$array))
            {
                $arr_insights = $array['insights'];
                if(array_key_exists('action', $arr_insights))
                {
                    $arr_insights_action = $arr_insights['action'];  
                    unset($arr_insights['action']);                  
                }
                unset($array['insights']);
            }

            //Insere no banco
            log_message('debug', 'Array to Insert: ' . print_r($array, true));
            if(!$this->db->insert('adsets', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            //Se tiver targeting. Insere
            if(isset($arr_targeting))
            {
                log_message('debug', 'Array to Insert: ' . print_r($arr_targeting, true));
                if(!$this->db->insert('adset_targeting',$arr_targeting))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());  
                unset($arr_targeting);  
            }

            //Se tiver insights e actions. Insere
            if(isset($arr_insights))
            {
                log_message('debug', 'Array to Insert: ' . print_r($arr_insights, true));

                if(!$this->db->insert('adset_insights',$arr_insights))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());

                $insert_id = $this->db->insert_id();

                if(isset($arr_insights_action))
                {
                    foreach($arr_insights_action as $action)
                    {
                        $action['adset_insights_id'] = $insert_id;
                        if(!$this->db->insert('adset_insights_actions', $action))
                            log_message('debug', 'Erro: ' . $this->db->error()->message);
                        log_message('debug', 'Last Query: ' . $this->db->last_query());
                    }
                    unset($arr_insights_action);
                }
                unset($arr_insights);
            }
        }
    }

    /**
    * insertAd
    *
    * Insere no banco de dados os dados das contas após ser processadas dos dados obtidos
    * do Facebook
    *
    * @param	arr_ad array: Os anúncios a serem inseridos no banco
    * @return	-
    */
    public function insertAd($arr_ad)
    {
        if($arr_ad == null)
            return;

        //Para cada anúncio do array
        foreach($arr_ad as $array)
        {
            //Se tiver insights. Separa.
            if(array_key_exists('insights',$array))
            {
                $arr_insights = $array['insights'];
                if(array_key_exists('action', $arr_insights))
                {
                    $arr_insights_action = $arr_insights['action'];  
                    unset($arr_insights['action']);                  
                }
                unset($array['insights']);
            }

            //Se tiver dados do crative. Separa
            if(array_key_exists('creative',$array))
            {
                $arr_creative = $array['creative'];
                unset($array['creative']);
            }

            //Insere no banco
            log_message('debug', 'Array to Insert: ' . print_r($array, true));
            if(!$this->db->insert('ads', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            //Se tiver insights e actions. Insere.
            if(isset($arr_insights))
            {
                log_message('debug', 'Array to Insert: ' . print_r($arr_insights, true));
                if(!$this->db->insert('ad_insights',$arr_insights))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());

                $insert_id = $this->db->insert_id();

                if(isset($arr_insights_action))
                {
                    foreach($arr_insights_action as $action)
                    {
                        $action['ad_insights_id'] = $insert_id;
                        if(!$this->db->insert('ad_insights_actions', $action))
                            log_message('debug', 'Erro: ' . $this->db->error()->message);

                        log_message('debug', 'Last Query: ' . $this->db->last_query());
                    }
                    unset($arr_insights_action);
                }
                unset($arr_insights);
            }

            //Se tiver creative. Insere.
            if(isset($arr_creative))
            {
                log_message('debug', 'Array to Insert: ' . print_r($arr_creative, true));
                if(!$this->db->insert('ad_creatives',$arr_creative))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());
                unset($arr_creative);
            }
        }
    }

    /**
    * insertAdSetInfo
    *
    * Insere no banco de dados os dados de um conjunto após ser processado dos dados obtidos
    * do Facebook
    *
    * @param	arr_adset array: Os conjuntos a serem inseridos no banco
    * @return   -
    */
    public function insertAdSetInfo($arr_adset)
    {
        //Para cada conjunto
        foreach($arr_adset as $array)
        {
            //Se tiver targeting no array, separa
            if(array_key_exists('targeting',$array))
            {
                $arr_targeting = $array['targeting'];
                unset($array['targeting']);
            }

            //Se tiver insights, separa
            if(array_key_exists('insights',$array))
            {
                $arr_insights = $array['insights'];
                if(array_key_exists('action', $arr_insights))
                {
                    $arr_insights_action = $arr_insights['action'];  
                    unset($arr_insights['action']);                  
                }
                unset($array['insights']);
            }

            //Insere no banco
            if(!$this->db->insert('adsets_info', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            //Se tiver targeting. Insere
            if(isset($arr_targeting))
            {
                if(!$this->db->insert('adset_targeting_info',$arr_targeting))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());  
                unset($arr_targeting);  
            }

            //Se tiver insights e actions. Insere
            if(isset($arr_insights))
            {
                if(!$this->db->insert('adset_insights_info',$arr_insights))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());

                $insert_id = $this->db->insert_id();

                if(isset($arr_insights_action))
                {
                    foreach($arr_insights_action as $action)
                    {
                        $action['adset_insights_id'] = $insert_id;
                        if(!$this->db->insert('adset_insights_actions_info', $action))
                            log_message('debug', 'Erro: ' . $this->db->error()->message);
                        log_message('debug', 'Last Query: ' . $this->db->last_query());
                    }
                    unset($arr_insights_action);
                }
                unset($arr_insights);
            }
        }
    }

    /**
    * insertAdInfo
    *
    * Insere no banco de dados os dados das contas após ser processadas dos dados obtidos
    * do Facebook
    *
    * @param	arr_ad array: Os anúncios a serem inseridos no banco
    * @return	-
    */
    public function insertAdInfo($arr_ad)
    {
        //Para cada anúncio do array
        foreach($arr_ad as $array)
        {
            //Se tiver insights. Separa.
            if(array_key_exists('insights',$array))
            {
                $arr_insights = $array['insights'];
                if(array_key_exists('action', $arr_insights))
                {
                    $arr_insights_action = $arr_insights['action'];  
                    unset($arr_insights['action']);                  
                }
                unset($array['insights']);
            }

            //Se tiver dados do crative. Separa
            if(array_key_exists('creative',$array))
            {
                $arr_creative = $array['creative'];
                unset($array['creative']);
            }

            //Insere no banco
            if(!$this->db->insert('ads_info', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            //Se tiver insights e actions. Insere.
            if(isset($arr_insights))
            {
                if(!$this->db->insert('ad_insights_info',$arr_insights))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());

                $insert_id = $this->db->insert_id();

                if(isset($arr_insights_action))
                {
                    foreach($arr_insights_action as $action)
                    {
                        $action['ad_insights_id'] = $insert_id;
                        if(!$this->db->insert('ad_insights_actions_info', $action))
                            log_message('debug', 'Erro: ' . $this->db->error()->message);

                        log_message('debug', 'Last Query: ' . $this->db->last_query());
                    }
                    unset($arr_insights_action);
                }
                unset($arr_insights);
            }

            //Se tiver creative. Insere.
            if(isset($arr_creative))
            {
                if(!$this->db->insert('ad_creatives_info',$arr_creative))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());
                unset($arr_creative);
            }
        }
    }

    /**
    * grava_custom_conversions
    *
    * Insere no banco de dados os dados das conversões personalizadas após
    * serem processados dos dados do Facebook
    *
    * @param	arr_custom_conversions array: As conversões personalizadas a serem inseridas no banco
    * @return	-
    */
    public function grava_custom_conversions($arr_custom_conversions)
    {
        foreach($arr_custom_conversions as $array)
        {
            if(!$this->db->insert('account_custom_conversion',$array))
                log_message('debug', 'Erro: ' . $this->db->error()->message); 
            log_message('debug', 'Last Query: ' . $this->db->last_query());   
        }
    }

    /**
    * getLastDateSync
    *
    * Pega no banco de dados o último dia que já está no banco. 
    *   - Se não tiver nenhuma quebra por data, pega no banco a data de criação do ad/adset/campanha
    *   - Caso exista, pega a última data, apaga do banco e retorna esta data para sincronizar
    *     a partir dela
    *
    * @param	id: Id do tipo a ser pesquisado no banco
    * @param    tipo: Tipo a ser pesquisado no banco: ad, adset ou campaign
    *
    * @return	
    *    string: data da criação do anúncio se não tiver nenhuma quebra por data
    *            última data sincronizada no banco
    */
    public function getLastDateSync($id, $tipo)
    {
        log_message('debug', 'getLastDateSync');

        //Busca da tabela <tipo>_insight as duas últimas data quebrada já sincronizada
        //Pega as duas últimas pois pode ter dado algum problema de sincronização na data
        //anterior (sincronizar perto da meia noite por exemplo)
        $this->db->select('date_start, '.$tipo.'_insights_id');
        $this->db->from($tipo.'_insights');
        $this->db->where($tipo.'_id', $id);
        $this->db->where('bydate = 1');
        $this->db->order_by($tipo.'_insights_id', 'desc');
        $this->db->limit(2);
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        //Se achou, apaga esta data de insights e actions
        if($result->num_rows() > 0)
        {
            $results = $result->result();

            $ret = "";

            foreach($results as $row)
            {
                $this->db->where($tipo.'_insights_id', $row->{$tipo.'_insights_id'});
                $this->db->delete($tipo.'_insights_actions');
    
                $this->db->where($tipo.'_insights_id', $row->{$tipo.'_insights_id'});
                $this->db->delete($tipo.'_insights');

                $ret = explode(' ', $row->date_start)[0];
            }

            return $ret;    
        }

        //Pega do tipo buscado 
        $this->db->where('id',$id);
        $result = $this->db->get($tipo.'s'); //O s serve para deixar genérico.

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        $row = $result->row();

        //Retorna data de criação
        return explode('T', $row->created_time)[0];
    }

    /**
    * getFirstDate
    *
    * Pega a data geral de um anúncio/conjunto/campanha
    * - Se não tive nenhum insight desse tipo pega da data de criação do tipo
    * - Se tive ele apaga para atualizar
    *
    * @param	id: O Id do tipo que será pesquisado
    * @param    tipo string: Qual tipo será pego: ad, adset, campaign
    * @return	
    *    data de criação do tipo ou data do insight ou false se não encontrar datas
    */
    public function getFirstDate($id, $tipo)
    {
        log_message('debug', 'getFirstDate');

        $this->db->select('date_start, '.$tipo.'_insights_id');
        $this->db->from($tipo.'_insights');
        $this->db->where($tipo.'_id', $id);
        $this->db->where('bydate is null');
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        //Se há insight. Apaga da tabela insight e seus actions.
        if($result->num_rows() > 0)
        {
            $row = $result->row();

            $this->db->where($tipo.'_insights_id', $row->{$tipo.'_insights_id'});
            $this->db->delete($tipo.'_insights_actions');

            $this->db->where($tipo.'_insights_id', $row->{$tipo.'_insights_id'});
            $this->db->delete($tipo.'_insights');

            return explode(' ', $row->date_start)[0];    
        }

        //Validate
        $this->db->where('id',$id);
        $result = $this->db->get($tipo.'s'); //o s é para deixar genérico

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
        {
            $row = $result->row();

            //Retorna a data de criação do tipo
            return explode('T', $row->created_time)[0];
        }

        return false;
    }

    /**
    * saveKpis
    *
    * Salva os kpis da planilha gerada
    *
    * @param	kpi array: Os KPIs
    * @param    id: Id do ad inserido
    */
    public function saveKpis($kpi, $id)
    {
        log_message('debug', 'saveKpis');   

        $kpi['ad_id'] = $id;

        $this->db->where('ad_id', $id);
        $result = $this->db->get('kpi_planilha');

        if($result->num_rows() > 0)
        {
            $this->db->where('ad_id', $id);
            $this->db->update('kpi_planilha', $kpi);
        }
        else
            $this->db->insert('kpi_planilha', $kpi);    
    }

    /**
    * saveGeral
    *
    * Salva a posição Geral de cada planilha gerada
    *
    * @param	colunas array: O nome das colunas do banco de dados
    * @param    dados array: Array a ser inserido
    * @param    conv_personalizada array: Array de posicoes das conversoes personalizadas
    * @param    id: Id do tipo inserido
    * @param    tipo: Tipo a ser inserido (ad, adset, campaign)
    */
    public function saveGeral($colunas, $dados, $conv_personalizada, $id, $tipo)
    {
        log_message('debug', 'saveGeral');   
        
        for($i=1; $i<count($dados); $i++)
        {
            if(strpos('Custo por', $colunas[$i]) !== false)
                continue;

            $cols[$i] = $colunas[$i];
            if(array_key_exists('conversoes', $colunas))
            {
                if(array_search($cols[$i], $colunas['conversoes']) !== false)
                {
                    foreach($colunas['conversoes'] as $key => $val)
                    {
                        if($cols[$i] == $val)
                        {
                            $cols[$i] = $key;
                            $i++;
                            $cols[$i] = $key . '_cost';
                        }
                    }
                }  
            }
            
            $cols[$i] = preg_replace("/&([a-z])[a-z]+;/i", "$1", htmlentities(trim($cols[$i])));
            $cols[$i] = preg_replace('~[%#$:]~', ' ', $cols[$i]);
            $cols[$i] = str_replace(' ', '', $cols[$i]);
        }

    
        for($i=1; $i<count($dados); $i++)
        {
            if(array_search($i, $conv_personalizada) === false)
                if($cols[$i] != '')    
                    $arr[$cols[$i]] = $dados[$i-1];
        }

        $arr['tipo_id'] = $id;
        $arr['tipo'] = $tipo;

        $this->db->where('tipo_id', $id);
        $result = $this->db->get('coluna_geral_planilha');

        if($result->num_rows() > 0)
        {
            $this->db->where('tipo_id', $id);
            $this->db->update('coluna_geral_planilha', $arr);
        }
        else
            $this->db->insert('coluna_geral_planilha', $arr);

    }

    /**
    * getTableData
    *
    * Pega os dados necessários que serão exibidos na planilha
    *
    * @param	id: O Id do tipo que será pesquisado
    * @param    tipo string: Qual tipo será pego: ad, adset, campaign
    * @param    abaixo bool: Se true traz os dados do tipo abaixo: 
    *                se Campanha traz AdSets, se AdSet traz Ads
    * @return	
    *    lista de dados (geral e por dia) do id do tipo pesquisado
    */
    public function getTableData($id, $tipo)
    {
        log_message('debug', 'getTableData. Id:' . $id);

        $user_id = $this->getuserid($fb_id);
        $filtro_periodo = $this->filtro_periodo($user_id);
            
        $this->db->select('date_start, cost_per_inline_link_click, inline_link_click_ctr, inline_link_clicks,impressions, cpm, relevance_score_score, spend, bydate, ' . $tipo . '_insights_id');
        $this->db->from($tipo.'_insights');
        $this->db->where($tipo.'_id', $id);

        if($filtro_periodo)
            $this->db->where("date_start > '" . $filtro_periodo . "'");

        $this->db->order_by('bydate', 'DESC');
        $this->db->order_by('date_start');
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->result();

    }

    /**
    * getTableDataFromTipo
    *
    * Pega os dados a serem exibidos no resumo a partir do tipo os dados abaixo:
    *   se Campanha traz AdSets, se AdSet traz Ads
    *
    * @param	id: O Id do tipo que será pesquisado
    * @param    tipo string: Qual tipo será pego: ad, adset, campaign
    * @param    fb_id: Id do Facebook logado
    *             
    * @return	
    *    lista de dados (geral e por dia) do id do tipo pesquisado
    */
    public function getTableDataFromTipo($id, $tipo, $fb_id)
    {
        log_message('debug', 'getTableDataFromTipo. Id:' . $id);

        $tipo1 = $tipo;

        $user_id = $this->getuserid($fb_id);
        $filtro_periodo = $this->filtro_periodo($user_id);
        
        if($tipo == 'account')
        {
            //Traz campanha
            $this->db->select('campaign_insights_id as id_action, 
                 "campaign" as tipo, campaigns.name, campaigns.id, campaigns.effective_status as status,
                 campaigns.objective');
            $this->db->from('campaign_insights');
            $this->db->join('campaigns', 'campaigns.id = campaign_insights.campaign_id','right');
            $this->db->where('campaigns.account_id', $id);

            $this->db->where('bydate is null');
            if($filtro_periodo) 
                $this->db->where("campaigns.updated_time > '" . $filtro_periodo . "'");

            $this->db->order_by('effective_status');
        }
        else if($tipo == 'campaign')
        {
            //Traz conjunto
            $this->db->select('cost_per_inline_link_click as cpc, inline_link_click_ctr as ctr, cpm, adset_insights_id as id_action, 
            adsets.effective_status, "adset" as tipo, adsets.name, adsets.id, adsets.effective_status as status,
            adsets.promoted_object_custom_event_type as objetivo, adsets.daily_budget, adsets.budget_remaining');
                $this->db->from('adset_insights');
                $this->db->join('adsets', 'adsets.id = adset_insights.adset_id','right');
                $this->db->where('adsets.campaign_id', $id);

                $this->db->where('bydate is null');

                if($filtro_periodo) 
                    $this->db->where("adsets.updated_time > '" . $filtro_periodo . "'");

                $this->db->order_by('effective_status');  
        }
        else if($tipo == 'adset')
        {
            $this->db->select('cost_per_inline_link_click as cpc, inline_link_click_ctr as ctr, cpm, ad_insights_id as id_action, 
            ads.effective_status as status, ad_creatives.effective_object_story_id,
             "ad" as tipo, ads.name, ads.id, relevance_score_score as relevancia, spend');
            $this->db->from('ad_insights');
            $this->db->join('ads', 'ads.id = ad_insights.ad_id');
            $this->db->join("ad_creatives", "ads.id = ad_creatives.ad_id",'right');
            $this->db->where('ads.adset_id', $id);

            if($filtro_periodo) 
                $this->db->where("ads.updated_time > '" . $filtro_periodo . "'");

            $this->db->where('bydate is null');
            $this->db->order_by('effective_status');  
        }
            
        $result = $this->db->get();

        $sql = $this->db->last_query();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() == 0)
            return false;

        return $result->result();

    }

    /**
    * getTableData
    *
    * Pega pega todas as conversões a serem mostradas na planilha
    *   - As conversões sempre começam com 'offsite_conversion.'
    *
    * @param	id: O Id do tipo que será pesquisado
    * @param    tipo string: Qual tipo será pego: ad, adset, campaign
    * @return	
    *    lista de conversões (geral e por dia) do id do tipo pesquisado
    */
    public function getTableDataActions($id, $tipo)
    {
        log_message('debug', 'getTableDataActions. Id:' . $id);
        
        $this->db->select($tipo.'_insights_id, action_type, value, cost');
        $this->db->from($tipo.'_insights_actions');
        $this->db->like('action_type', 'offsite_conversion.', 'after');
        $this->db->where($tipo.'_insights_id', $id);
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->result();
    }

    /**
    * getPossibleConversions
    *
    * Pega todas as conversões possíveis de um tipo
    *   - As vezes em um dia pode não ter todas as conversões, isso serve para 
    *     não faltar alguma conversão em algum dia na planilha
    *
    * @param	id: O Id do tipo que será pesquisado
    * @param    tipo string: Qual tipo será pego: ad, adset, campaign
    * @param    before boolean: Se vai trazer as conversões a partir de um anterior:
    *               exemplo: se true, account traz campaign, campaign traz adset, adset traz ad
    * @return	
    *    lista de todas as conversões possíveis para este id
    */
    public function getPossibleConversions($id, $tipo, $before = false)
    {
        log_message('debug', 'getPossibleConversions. Id:' . $id);

        $subtipo = $tipo;
        if($before)
        {
            if($subtipo == 'account') $tipo = 'campaign';
            else if($subtipo == 'campaign') $tipo = 'adset';
            else if($subtipo == 'adset') $tipo = 'ad'; 
        }
        
        $this->db->distinct();
        $this->db->select('action_type');
        $this->db->from($tipo.'_insights_actions');
        $this->db->join($tipo.'_insights', $tipo.'_insights.' . $tipo . '_insights_id = '
            . $tipo . '_insights_actions.' . $tipo . '_insights_id');
        $this->db->like('action_type', 'offsite_conversion.', 'after');
        $this->db->where($tipo.'_insights.' . $subtipo.'_id', $id);
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->result();
    }

    /**
    * get_custom_conversion_name
    *
    * Traz o nome da conversão personalizada através do id
    *
    * @param	id: id da conversão personalizada
    * @return	
    *    (string): Nome da conversão personalizada
    */
    public function get_custom_conversion_name($id)
    {
        log_message('debug', 'get_custom_conversion_name. Id:' . $id);
        
        $this->db->select('name');
        $this->db->from('account_custom_conversion');
        $this->db->where('id', $id);
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row()->name;    
    }

    /**
    * get_resumo
    *
    * Pega resumo do tipo para consulta rápida de todos o tipo abaixo
    * @param id: Id do tipo
    * @param tipo: Tipo
    * @param comissao: Comissao para cálculo do ROI
    * @param id_fb: id_facebook logado
    */
    public function get_resumo($id, $tipo, $comissao, $id_fb)
    {
        log_message('debug', 'get_resumo. Id:' . $id);   

        $tipos = $this->getTableDataFromTipo($id, $tipo, $id_fb);

        $dados_ret = false;

        if($tipos)
        {
            $conversions = $this->getPossibleConversions($id, $tipo);
            
            foreach($tipos as $valores)
            {
                $dados = array();
                foreach($valores as $key => $val)
                {
                    if($key == 'id_action' || $key == 'tipo')
                        continue;
                    $dados[$key] = $val;
                }

                $actions = $this->getTableDataActions($valores->id_action, $valores->tipo);
                foreach($actions as $action)    
                {
                    if($action->action_type == 'offsite_conversion.fb_pixel_custom')
                        continue;

                    $dados[$action->action_type] = $action->value;
                }
                $dados_ret[] = $dados;
            }
        }

        return $dados_ret;
    }

    /**
    * get_tags_from_ad
    *
    * Pega o url_tag configurado no anúncio
    *
    * @param	id: O Id do anúncio
    * @return   string: Nome da conversão personalizada
    */
    function get_tags_from_ad($ad_id)
    {
        log_message('debug', 'get_tags_from_ad. Id:' . $ad_id);
        
        $this->db->select('url_tags');
        $this->db->from('ad_creatives');
        $this->db->where('ad_id', $ad_id);
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row();  
      
    }

    /**
    * getuserid
    *
    * Traz o id do usuário através do id do Facebook
    *
    * @param	fb_id: Id do Facebook
    * @return	string: Id do usuário
    */
    function getuserid($fb_id)
    {
        log_message('debug', 'getuserid. Id:');

        $this->db->select("user_id");
        $this->db->from("profiles");
        $this->db->where("facebook_id", $fb_id);

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row()->user_id;
    }

    /**
    * getuserid
    *
    * Traz o id do facebook através do id do usuario
    *
    * @param	user_id: Id do Usuário
    * @return	string: Id do facebook
    */
    function getfbid($user_id)
    {
        log_message('debug', 'getuserid. Id:');

        $this->db->select("facebook_id");
        $this->db->from("profiles");
        $this->db->where("user_id", $user_id);

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row()->facebook_id;
    }

    /**
    * getAdSetFromAd
    *
    * Traz o AdSet do Ad
    *
    * @param	ad_id: Id do anúncio
    * @return	id do adset do anúncio
    */
    function getAdSetFromAd($ad_id)
    {
        log_message('debug', 'getAdSetFromAd. Id:' . $ad_id);
        
        $this->db->select('adset_id');
        $this->db->from('ads');
        $this->db->where('id', $ad_id);
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row()->adset_id;  
      
    }

    /**
    * get_info_funil
    *
    * Traz infos do funil. Estas infos são das planilhas geradas.
    *
    * @param	$id: Id do ad para trazer as infos
    * @return	
    *    lista consolidada para preenchimento da área "Métricas que estão vendendo para esse produto"
    *     na planilha de métricas ou false se não tiver dados
    */
    function get_info_funil($id)
    {
        log_message('debug', 'get_info_funil');   
        
        $this->db->select('coluna_geral_planilha.*, kpi_planilha.*');
        $this->db->from('coluna_geral_planilha');
        $this->db->join('kpi_planilha', 'kpi_planilha.ad_id = coluna_geral_planilha.tipo_id');
        $this->db->where('coluna_geral_planilha.tipo_id', $id);

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row();
    }

    /**
    * get_sum_gasto
    *
    * Traz a soma gasto dos anúncios
    *
    * @param	$id: Id do Facebook para trazer a soma
    * @param    $produto: Se false traz para todos, ou traz para o produto
    * @return	
    *    Soma
    */
    function get_sum_gasto($id, $today = false, $produto = false)
    {
        log_message('debug', 'get_sum_gasto');

        $this->db->select("sum(spend) as gasto");
        $this->db->from("ad_insights");
        $this->db->join("accounts", "ad_insights.account_id = accounts.id");

        if($today)
        {
            $this->db->where("bydate = 1");
            $this->db->where("date_start", $today);
        }
        else
            $this->db->where("bydate is null");

        $this->db->where("accounts.facebook_id", $id);

        if($produto)
        {
            $sql = $this->db->get_compiled_select();
            
            $this->db->select("ad_id");
            $this->db->from("ads_vendas");
            $this->db->where("produto", $produto);

            $sql_where = $this->db->get_compiled_select();

            $sql = $sql . " AND ad_insights.ad_id in ( " .  $sql_where . ")";
        }
        else
            $sql = $this->db->get_compiled_select();

        $results = $this->db->query($sql);

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        $this->db->reset_query();

        return $results->row()->gasto;
    }

    /**
    * get_best_ads
    *
    * Traz os anúncios com vendas cadastrados no sistema
    *
    * @param	$id: Id do Facebook para trazer os anúncios
    * @return	
    *    lista consolidada para preenchimento da área "Métricas que estão vendendo para esse produto"
    *     na planilha de métricas ou false se não tiver dados
    */
    function get_best_ads($id = false)
    {
        log_message('debug', 'get_best_ads');

        $sql = "SELECT ad_insights.ad_id, ads.name as anuncio, adsets.name as conjunto, campaigns.name as campanha, accounts.name as conta, coluna_geral_planilha.tipo_id, accounts.facebook_id,coluna_geral_planilha.roi, sum(ad_insights_actions.value) as qtde, inline_link_click_ctr as ctr, cost_per_inline_link_click as cpc,
        ad_insights.cpm, ad_insights.spend, (spend/sum(ad_insights_actions.value)) as cpv, 
        ads_vendas.produto, sum(ads_vendas.cartoes) as cartoes, sum(ads_vendas.boletos_pagos) as boletos_pagos,
        sum(ads_vendas.boletos_pagos * ads_vendas.comissao) as faturamento_boleto, sum(ads_vendas.cartoes * ads_vendas.comissao) as faturamento_cartao,
        ((((ifnull(sum(ads_vendas.boletos_pagos * comissao),0) + ifnull(sum(ads_vendas.cartoes * comissao),0))-ad_insights.spend) / ad_insights.spend) * 100) as ROI
        FROM ad_insights JOIN ad_insights_actions 
            ON ad_insights.ad_insights_id = ad_insights_actions.ad_insights_id
            JOIN ads on ads.id = ad_insights.ad_id
            JOIN adsets on ads.adset_id = adsets.id
            JOIN campaigns on ads.campaign_id = campaigns.id
            JOIN accounts on ads.account_id = accounts.id
            LEFT JOIN ads_vendas ON ads_vendas.ad_id = ad_insights.ad_id
            LEFT JOIN coluna_geral_planilha ON coluna_geral_planilha.tipo_id = ad_insights.ad_id
            WHERE bydate is NULL AND ad_insights_actions.action_type LIKE '%purchase%' ";

        if($id)
        {
            $sql .= "AND facebook_id = '" . $id . "' ";
        }

        $sql .= "GROUP BY(ad_id)
            ORDER BY qtde DESC";

        $results = $this->db->query($sql);

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($results->num_rows() > 0)
            return $results->result();
        else
            return false;
    }

    /**
    * get_info_best_ads
    *
    * Traz as informações dos anúncios com vendas cadastrados no sistema
    *
    * @param	ad_id: Id do anuncio
    * @return	
    *    lista consolidada para preenchimento da área "Métricas que estão vendendo para esse produto"
    *     na planilha de métricas ou false se não tiver dados
    */
    function get_info_best_ads($ad_id)
    {
        log_message('debug', 'get_best_ads');

        $this->db->where('ad_id', $ad_id);
        $results['ad_creatives'] = $this->db->get('ad_creatives')->row();

        $adset_id = $this->getAdSetFromAd($ad_id);
        $fb_id = $this->get_fbid_ad($ad_id);

        $results['token'] = $this->getProfileToken($fb_id)->token;

        $this->db->where('id', $adset_id);
        $results['adsets'] = $this->db->get('adsets')->row();

        $this->db->where('id', $adset_id);
        $results['adset_targeting'] = $this->db->get('adset_targeting')->row();

        return $results;
    }

    /**
    * get_fbid_ad
    *
    * Traz o ID do Facebook dono do anúncio
    *
    * @param	ad_id: Id do anuncio
    * @return	
    *    Id do Facebook dono do anúncio
    */
    function get_fbid_ad($ad_id)
    {
        log_message('debug', 'get_fbid_ad');   
        
        $this->db->select('facebook_id');
        $this->db->from('accounts');
        $this->db->join('ads', 'ads.account_id = accounts.id');
        $this->db->where('ads.id', $ad_id);

        $res = $this->db->get();

        return $res->row()->facebook_id;
    }

    /**
    * get_dados_vendendo
    *
    * Traz os dados consolidados do que está vendendo com roi positivo e métricas
    *
    * @param	produto: Nome do produto que está sendo vendido
    * @return	
    *    lista consolidada para preenchimento da área "Métricas que estão vendendo para esse produto"
    *     na planilha de métricas ou false se não tiver dados
    */
    function get_dados_vendendo($produto)
    {
        log_message('debug', 'get_dados_vendendo. Produto:' . $produto);

        if($produto == '')
            return false;

        $result = $this->db->query("SELECT avg(ctr) as ctr, avg(cpc) as cpc, avg(cpm) as cpm, avg(roi) as roi, avg(spend) as spend,
		avg(ifnull((ifnull(boletos_pagos,0) + ifnull(cartoes,0))/ifnull(cartoes,0),0)) as p_cartoes,
        avg(ifnull((ifnull(boletos_pagos,0) + ifnull(cartoes,0))/ifnull(boletos_pagos,0),0)) as p_boletos,
        avg(ifnull((ifnull(boletos_pagos,0) + ifnull(boletos_gerados,0))/ifnull(boletos_pagos,0),0)) as c_boletos,
        avg(spend/(ifnull(boletos_pagos,0) + ifnull(cartoes,0))) as cpv,
        avg(clicks/(ifnull(boletos_pagos,0) + ifnull(cartoes,0))) as clpv
        FROM (
SELECT ads_vendas.ad_id, accounts.facebook_id, spend, sum(boletos_gerados) as boletos_gerados, sum(boletos_pagos) as boletos_pagos, 
            sum(cartoes) as cartoes, sum(boletos_pagos * comissao) as faturamento_boleto, 
            sum(cartoes * comissao) as faturamento_cartao, produto, plataforma, 
            ((((ifnull(sum(boletos_pagos * comissao),0) + ifnull(sum(cartoes * comissao),0))-spend) / spend) * 100) as ROI, inline_link_click_ctr as ctr, cost_per_inline_link_click as cpc,
            cpm, clicks
FROM ads_vendas
JOIN ad_insights on ads_vendas.ad_id = ad_insights.ad_id
JOIN accounts on ad_insights.account_id = accounts.id
WHERE bydate is null
GROUP BY ads_vendas.ad_id, plataforma, produto) a
WHERE roi > 0 and produto = '" . $produto . "'" );

        if($result->num_rows()>0)
        {
            return $result->row();
        }
        else
            return false;
    }

    /**
    * dados_vendas
    *
    * Traz os dados já somados dos postbacks ligados ao anúncio, conjunto ou campanha
    *  agrupados por dia e id
    *
    * @param	id: O Id do tipo que será pesquisado
    * @param    tipo string: Qual tipo será pego: ad, adset, campaign
    * @param    $fb_id: Id do Facebook logado
    * @return	
    *    lista de dados dos boletos gerados, pagos, cartões e seus valores agrupados por data e id
    */
    function dados_vendas($id, $tipo, $fb_id)
    {
        log_message('debug', 'dados_vendas');

        $user_id = $this->getuserid($fb_id);
        $filtro_periodo = $this->filtro_periodo($user_id); 

        $this->db->select("sum(boletos_gerados) as boletos_gerados, sum(boletos_pagos) as boletos_pagos, 
            sum(cartoes) as cartoes, sum(boletos_pagos * comissao) as faturamento_boleto, 
            sum(cartoes * comissao) as faturamento_cartao, produto, plataforma, 
            substring(data,1,10) as dt, comissao, " . $tipo . "_id");

        $this->db->from("ads_vendas");
        $this->db->where($tipo . "_id",$id);

        if($filtro_periodo)
            $this->db->where("data > '" . $filtro_periodo . "'");

        $this->db->group_by(array($tipo."_id","dt"));
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->result();
    }

     /**
    * dados_vendas_geral
    *
    * Traz os dados já somados dos postbacks ligados ao anúncio, conjunto ou campanha
    *   agrupados somente por id (Serve para trazer o resultado geral do tipo)
    *
    * @param	id: O Id do tipo que será pesquisado
    * @param    tipo string: Qual tipo será pego: ad, adset, campaign
    * @param    $fb_id: Id do Facebook logado
    * @return	
    *    lista de dados dos boletos gerados, pagos, cartões e seus valores agrupados por id
    */
    function dados_vendas_geral($id, $tipo, $fb_id)
    {
        log_message('debug', 'dados_vendas_geral');

        $user_id = $this->getuserid($fb_id);
        $filtro_periodo = $this->filtro_periodo($user_id); 

        $this->db->select("sum(boletos_gerados) as boletos_gerados, sum(boletos_pagos) as boletos_pagos, 
            sum(cartoes) as cartoes, sum(boletos_pagos * comissao) as faturamento_boleto, 
            sum(cartoes * comissao) as faturamento_cartao, produto, comissao, " . $tipo . "_id");

        $this->db->from("ads_vendas");
        $this->db->where($tipo . "_id",$id);

        if($filtro_periodo)
            $this->db->where("data > '" . $filtro_periodo . "'");

        $this->db->group_by(array($tipo . "_id"));
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row();
    }

    /**
    * dados_vendas_dia
    *
    * Traz todas as vendas ocorridas em uma determinada data para um tipo
    *
    * @param	data: Data a ser pesquisada
    * @param    id: Id do tipo a ser pesquisado
    * @param    tipo string: Qual tipo será pego: ad, adset, campaign
    * @return	
    *    lista de dados dos boletos gerados, pagos, cartões e seus valores agrupados por data e id
    */
    function dados_vendas_dia($data, $id, $tipo)
    {
        log_message('debug', 'dados_vendas_dia');

        $this->db->select("boletos_gerados, boletos_pagos, cartoes, comissao, produto, 
            substring(data,1,10) as dt, src, comissao, " . $tipo . "_id, ads_vendas_id");

        $this->db->from("ads_vendas");
        $this->db->where($tipo . "_id",$id);
        $this->db->like('data', $data, 'right');
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->result();
    }

    /**
    * getCampaignFromAd
    *
    * Traz a Campanha do Ad
    *
    * @param	ad_id: Id do anúncio
    * @return	id da campanha do anúncio
    */
    function getCampaignFromAd($ad_id)
    {
        log_message('debug', 'getCampaignFromAd. Id:' . $ad_id);
        
        $this->db->select('campaign_id');
        $this->db->from('ads');
        $this->db->where('id', $ad_id);
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row()->campaign_id;  
      
    }

    /**
    * busca_vendas_tag
    *
    * Função tenta ligar a venda pelo url_tag
    *
    * @param	ad_id: Id do anúncio
    * @param    var_array: array da url_tag (src, utms...)
    * @return	lista de cartões, boletos pagos e gerados para o ad identificado
    */
    function busca_vendas_tag($ad_id,$var_array)
    {
        log_message('debug', 'busca_vendas_tag.'); 

        $retorno = false;

        $adset_id = $this->getAdSetFromAd($ad_id);
        $campaign_id = $this->getCampaignFromAd($ad_id);

        $this->db->select('ad_id, plataforma, id_plataforma');
        $this->db->from('ads_vendas');
        $this->db->where('ad_id', $ad_id);   

        $result = $this->db->get();

        if($result->num_rows() > 0)
        {
            $retorno = $this->{'busca_'.$result->row()->plataforma}($var_array);
        }
        else
        {
            $retornoh = $this->busca_hotmart($var_array);
            $retornom = $this->busca_monetizze($var_array);

            if($retornoh && $retornom)
            {
                //SRC Duplicado: Impossível distinguir
                return false;
            }
            else if($retornoh)
                $retorno = $retornoh;
            else if($retornom)
                $retorno = $retornom;
        }

        return $retorno;
    }

    /**
    * busca_monetizze
    *
    * Busca da tabela de postback monetizze, a venda através dos tags
    * @param    var_array: array da url_tag (src, utms...)
    * @return	lista de cartões, boletos pagos e gerados para o ad identificado
    */
    function busca_monetizze($var_array)
    {
        log_message('debug', 'busca_monetizze.');  

        $ret = false;

        $this->db->select("venda_data_inicio as data_compra, venda_data_finalizada as data_confirmacao,
            venda_valor_recebido as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
            'monetizze' as plataforma");
        $this->db->from("postback_monetizze");
        $this->db->like('venda_src', $var_array['src'], 'both'); 
        $this->db->where("ad_status != 'OK'");
        $this->db->where("venda_status = 'Aguardando pagamento'");
        $this->db->where("venda_forma_pagamento = 'Boleto'");
        $result = $this->db->get();

        if($result->num_rows() > 0) $ret = true;

        $retorno['boleto_impresso'] = $result->result();

        $this->db->select("venda_data_inicio as data_compra, venda_data_finalizada as data_confirmacao,
             venda_valor_recebido as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
            'monetizze' as plataforma");
        $this->db->from("postback_monetizze");
        $this->db->like('venda_src', $var_array['src'], 'both'); 
        $this->db->where("ad_status != 'OK'");
        $this->db->where("venda_status = 'Finalizada'");
        $this->db->where("venda_forma_pagamento != 'Boleto'");
        $result = $this->db->get();

        if($result->num_rows() > 0) $ret = true;

        $retorno['cartao'] = $result->result();

        $this->db->select("venda_data_inicio as data_compra, venda_data_finalizada as data_confirmacao,
            venda_valor_recebido as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
            'monetizze' as plataforma");
        $this->db->from("postback_monetizze");
        $this->db->like('venda_src', $var_array['src'], 'both'); 
        $this->db->where("ad_status != 'OK'");
        $this->db->where("venda_status = 'Finalizada'");
        $this->db->where("venda_forma_pagamento = 'Boleto'");
        $result = $this->db->get();

        if($result->num_rows() > 0) $ret = true;

        $retorno['boleto_pago'] = $result->result();

        if($ret)
            return $retorno;
        else
            return false;
    }

    /**
    * busca_hotmart
    *
    * Busca da tabela de postback hotmart, a venda através dos tags
    * @param    var_array: array da url_tag (src, utms...)
    * @return	lista de cartões, boletos pagos e gerados para o ad identificado
    */
    function busca_hotmart($var_array)
    {
        log_message('debug', 'busca_hotmart.');  

        $ret = false;

        $this->db->select("purchase_date as data_compra, confirmation_purchase_date as data_confirmacao,
		    cms_aff as comissao, prod_name as produto, postback_hotmart_id as id_plataforma,
            'hotmart' as plataforma");
        $this->db->from("postback_hotmart");
        $this->db->like('src', $var_array['src'], 'both'); 
        $this->db->where("ad_status != 'OK'");
        $this->db->where("status = 'billet_printed'");
        $result = $this->db->get();

        if($result->num_rows() > 0) $ret = true;

        $retorno['boleto_impresso'] = $result->result();

        $this->db->select("purchase_date as data_compra, confirmation_purchase_date as data_confirmacao,
		    cms_aff as comissao, prod_name as produto, postback_hotmart_id as id_plataforma,
            'hotmart' as plataforma");
        $this->db->from("postback_hotmart");
        $this->db->like('src', $var_array['src'], 'both'); 
        $this->db->where("ad_status != 'OK'");
        $this->db->where("status = 'approved'");
        $this->db->where("payment_type != 'billet'");
        $result = $this->db->get();

        if($result->num_rows() > 0) $ret = true;

        $retorno['cartao'] = $result->result();

        $this->db->select("purchase_date as data_compra, confirmation_purchase_date as data_confirmacao,
		    cms_aff as comissao, prod_name as produto, postback_hotmart_id as id_plataforma,
            'hotmart' as plataforma");
        $this->db->from("postback_hotmart");
        $this->db->like('src', $var_array['src'], 'both'); 
        $this->db->where("ad_status != 'OK'");
        $this->db->where("status = 'approved'");
        $this->db->where("payment_type = 'billet'");
        $result = $this->db->get();

        if($result->num_rows() > 0) $ret = true;

        $retorno['boleto_pago'] = $result->result();

        if($ret)
            return $retorno;
        else
            return false;
    }

    /**
    * busca_plataformas_vendas
    *
    * Traz os tokens do usuário em cada plataforma que este tem cadastro
    * @param    id: Id do usuário
    * @return	lista de tokens em cada plataforma
    */
    function busca_plataformas_vendas($id)
    {
        log_message('debug', 'busca_plataformas_vendas.');  

        $ret = false;

        $this->db->select("postback_hotmart.hottok");
        $this->db->from("postback_hotmart");
        $this->db->join("platform_users", "postback_hotmart.hottok = platform_users.token ");
        $this->db->join("platforms","platform_users.platform_id = platforms.platform_id"); 
        $this->db->where("platforms.name = 'Hotmart'");
        $this->db->where("platform_users.user_id", $id);
        $this->db->where("postback_hotmart.ad_status != 'OK'");
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0) $ret['Hotmart'] = $result->row()->hottok;

        $this->db->select("postback_monetizze.chave_unica");
        $this->db->from("postback_monetizze");
        $this->db->join("platform_users", "postback_monetizze.chave_unica = platform_users.token ");
        $this->db->join("platforms","platform_users.platform_id = platforms.platform_id"); 
        $this->db->where("platforms.name = 'Monetizze'");
        $this->db->where("platform_users.user_id", $id);
        $this->db->where("postback_monetizze.ad_status != 'OK'");
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0) $ret['Monetizze'] = $result->row()->chave_unica;

        log_message('debug', print_r($ret, true));

        return $ret;
    }

    /**
    * busca_hotmart_token
    *
    * Busca através do token, uma lista de vendas na plataforma hotmart
    * @param    id: id do Facebook logado
    * @return	array do boletos pagos, gerados e cartões na hotmart
    *           false se não achar nenhuma venda
    */
    function busca_hotmart_token($id, $produto = false, $id_diferente = false)
    {
        log_message('debug', 'busca_hotmart_token.');  

        $ret = false;

        $user_id = $this->getuserid($id);

        //Busca somente os boleto impresso e que não estão na lista de pagos
        //$result = $this->db->query(
        $query1 = "SELECT purchase_date as data_compra, confirmation_purchase_date as data_confirmacao,
		    cms_aff as comissao, prod_name as produto, postback_hotmart_id as id_plataforma,
            'hotmart' as plataforma, src, 'Boleto Impresso' as tipo, postback_hotmart.transaction FROM postback_hotmart
            join platform_users on postback_hotmart.hottok = platform_users.token
            WHERE postback_hotmart.status = 'billet_printed' AND postback_hotmart.ad_status != 'OK'
            AND platform_users.user_id ";

        $query1 .= $id_diferente ? " != " : " = ";
            
        $query1 .= "'" . $user_id . "'
            AND postback_hotmart.transaction not in (SELECT transaction FROM postback_hotmart 
                            WHERE status = 'approved'
                             AND payment_type = 'billet')";
                        
        if($produto != false)
            $query1 .= " AND prod_name = '" . $produto . "'";


        //if($result->num_rows() > 0) $ret = true;

        //$retorno['boleto_impresso'] = $result->result();

        $this->db->select("purchase_date as data_compra, confirmation_purchase_date as data_confirmacao,
		    cms_aff as comissao, prod_name as produto, postback_hotmart_id as id_plataforma,
            'hotmart' as plataforma, src, 'Cartão' as tipo, postback_hotmart.transaction");
        $this->db->from("postback_hotmart");
        $this->db->join("platform_users","postback_hotmart.hottok = platform_users.token");
        $this->db->where("postback_hotmart.ad_status != 'OK'");
        $this->db->where("postback_hotmart.status = 'approved'");
        
        if($id_diferente)
            $this->db->where("platform_users.user_id != " . $user_id);
        else
            $this->db->where("platform_users.user_id", $user_id);
            
        $this->db->where("postback_hotmart.payment_type != 'billet'");
        //$result = $this->db->get();

        $query2 = $this->db->get_compiled_select();

        if($produto != false)
            $query2 .= " AND prod_name = '" . $produto . "'";

        //if($result->num_rows() > 0) $ret = true;

        //$retorno['cartao'] = $result->result();

        $this->db->select("purchase_date as data_compra, confirmation_purchase_date as data_confirmacao,
		    cms_aff as comissao, prod_name as produto, postback_hotmart_id as id_plataforma,
            'hotmart' as plataforma, src, 'Boleto Pago' as tipo, postback_hotmart.transaction");
        $this->db->from("postback_hotmart");
        $this->db->join("platform_users","postback_hotmart.hottok = platform_users.token");
        $this->db->where("postback_hotmart.ad_status != 'OK'");
        $this->db->where("postback_hotmart.status = 'approved'");
        
        if($id_diferente)
            $this->db->where("platform_users.user_id != " . $user_id);
        else
            $this->db->where("platform_users.user_id", $user_id);

        $this->db->where("postback_hotmart.payment_type = 'billet'");
        //$result = $this->db->get();
        $query3 = $this->db->get_compiled_select();

        if($produto != false)
            $query3 .= " AND prod_name = '" . $produto . "'";

        //
        $this->db->select("purchase_date as data_compra, confirmation_purchase_date as data_confirmacao,
		    cms_aff as comissao, prod_name as produto, postback_hotmart_id as id_plataforma,
            'hotmart' as plataforma, src, 'Devolvida' as tipo, postback_hotmart.transaction");
        $this->db->from("postback_hotmart");
        $this->db->join("platform_users","postback_hotmart.hottok = platform_users.token");
        $this->db->where("postback_hotmart.ad_status != 'OK'");

        $this->db->group_start();
        $this->db->or_where("postback_hotmart.status = 'refunded'");
        $this->db->or_where("postback_hotmart.status = 'chargeback'");
        $this->db->group_end();
        
        
        if($id_diferente)
            $this->db->where("platform_users.user_id != " . $user_id);
        else
            $this->db->where("platform_users.user_id", $user_id);
        //$result = $this->db->get();

        $query4 = $this->db->get_compiled_select();

        if($produto != false)
            $query4 .= " AND prod_name = '" . $produto . "'";

        //if($result->num_rows() > 0) $ret = true;

        //$retorno['boleto_pago'] = $result->result();

        $sql = "SELECT * FROM (" . $query1 . " union " . $query2 . " union " . $query3 . " union " . $query4 .") a order by data_compra desc";

        $result = $this->db->query($sql);

        log_message('debug', 'Num_rows: ' . $result->num_rows() . ' Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();
        else
            return false;
    }

    /**
    * busca_monetizze_token
    *
    * Busca através do token, uma lista de vendas na plataforma monetizze
    * @param    id: id do facebook logado
    * @param    produto: nome do produto a ser pesquisado
    * @return	array do boletos pagos, gerados e cartões na monetizze
    *           false se não achar nenhuma venda
    */
    function busca_monetizze_token($id, $produto = false, $id_diferente = false)
    {
        log_message('debug', 'busca_monetizze_token.'); 

        $user_id = $this->getuserid($id);

        $ret = false;

        //Busca somente os boleto impresso e que não estão na lista de pagos
        //$result = $this->db->query(
        $query1 =  "SELECT venda_data_inicio as data_compra, venda_data_finalizada as data_confirmacao,
		    venda_valor_recebido as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
            'monetizze' as plataforma, venda_src as src, 'Boleto Impresso' as tipo, postback_monetizze.venda_codigo as transaction
FROM postback_monetizze join platform_users on postback_monetizze.chave_unica = platform_users.token
WHERE venda_forma_pagamento = 'Boleto' and postback_monetizze.ad_status != 'OK' 
and venda_status = 'Aguardando pagamento' and platform_users.user_id ";
        
        $query1 .= $id_diferente ? " != " : " = ";
        
        $query1 .=  " '" . $user_id . "'
and venda_codigo not in (SELECT venda_codigo FROM postback_monetizze
WHERE venda_status = 'Finalizada' and venda_forma_pagamento = 'Boleto')";

        if($produto != false)
            $query1 .= " AND produto_nome = '" . $produto . "'";

        //if($result->num_rows() > 0) $ret = true;

        //$retorno['boleto_impresso'] = $result->result();

        $this->db->select("venda_data_inicio as data_compra, venda_data_finalizada as data_confirmacao,
            venda_valor_recebido as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
            'monetizze' as plataforma, venda_src as src, 'Cartão' as tipo, postback_monetizze.venda_codigo as transaction");
        $this->db->from("postback_monetizze"); 
        $this->db->join("platform_users","postback_monetizze.chave_unica = platform_users.token");
        $this->db->where("ad_status != 'OK'");

        if($id_diferente)
            $this->db->where("platform_users.user_id != " . $user_id);
        else
            $this->db->where("platform_users.user_id", $user_id);
        
        $this->db->where("venda_status = 'Finalizada'");
        $this->db->where("venda_forma_pagamento != 'Boleto'");
        //$result = $this->db->get();
        $query2 = $this->db->get_compiled_select();

        if($produto != false)
            $query2 .= " AND produto_nome = '" . $produto . "'";

        //if($result->num_rows() > 0) $ret = true;

        //$retorno['cartao'] = $result->result();

        $this->db->select("venda_data_inicio as data_compra, venda_data_finalizada as data_confirmacao,
            venda_valor_recebido as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
            'monetizze' as plataforma, venda_src as src, 'Boleto Pago' as tipo, postback_monetizze.venda_codigo as transaction");
        $this->db->from("postback_monetizze");
        $this->db->join("platform_users","postback_monetizze.chave_unica = platform_users.token");
        $this->db->where("ad_status != 'OK'");
        
        if($id_diferente)
            $this->db->where("platform_users.user_id != " . $user_id);
        else
            $this->db->where("platform_users.user_id", $user_id);
        $this->db->where("venda_status = 'Finalizada'");
        $this->db->where("venda_forma_pagamento = 'Boleto'");
        //$result = $this->db->get();
        $query3 = $this->db->get_compiled_select();

        if($produto != false)
            $query3 .= " AND produto_nome = '" . $produto . "'";

        //

        $this->db->select("venda_data_inicio as data_compra, venda_data_finalizada as data_confirmacao,
            venda_valor_recebido as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
            'monetizze' as plataforma, venda_src as src, 'Devolvida' as tipo, postback_monetizze.venda_codigo as transaction");
        $this->db->from("postback_monetizze"); 
        $this->db->join("platform_users","postback_monetizze.chave_unica = platform_users.token");
        $this->db->where("ad_status != 'OK'");
        
        if($id_diferente)
            $this->db->where("platform_users.user_id != " . $user_id);
        else
            $this->db->where("platform_users.user_id", $user_id);
        $this->db->where("venda_status = 'Devolvida'");
        //$result = $this->db->get();
        $query4 = $this->db->get_compiled_select();

        if($produto != false)
            $query4 .= " AND produto_nome = '" . $produto . "'";

        $sql = "SELECT * FROM (" . $query1 . " union " . $query2 . " union " . $query3 . " union " . $query4 .") a order by data_compra desc";

        $result = $this->db->query($sql);

        log_message('debug', 'Num_rows: ' . $result->num_rows() . ' Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();
        else
            return false;
    }


    /**
    * get_vendas_totais
    *
    * Traz vendas no sistema para o usuário logado
    * @param    id: id do facebook logado
    * @return	Quantidade de vendas para o id logado
    */
    public function get_vendas_totais($id)
    {
        log_message('debug', 'get_vendas_totais');  
        
        $user_id = $this->getuserid($id);

        $plats = $this->busca_plataformas_vendas($user_id);
        if($plats)
            return false;
        
        $filtro_periodo = $this->filtro_periodo($user_id);

        if($filtro_periodo)
        {
            $this->db->where("date_start > '" . $filtro_periodo . "'");
        }

        $this->db->select("sum(value) as vendas");
        $this->db->from("ad_insights_actions");  
        $this->db->join("accounts", "ad_insights_actions.account_id = accounts.account_id");
        $this->db->where("action_type =  'offsite_conversion.fb_pixel_purchase'"); 
        $this->db->where("accounts.facebook_id",$id);

        $result = $this->db->get()->row()->vendas;

        if($result == null)
            $result = 0;

        return $result;

    }

    /**
    * get_vendas_plataforma
    *
    * Traz vendas no sistema
    * @param    id: id do facebook logado
    * @param    plataforma: nome da plataforma para ser consultada a venda or false para todas
    * @param    nao_confirmados: Traz as associações não confirmadas?
    * @param    produto: Se estiver preenchido traz o produto específico, senão, traz todos
    * @param    tipo: 'all' traz para todos. Especificar caso queira o tipo "Cartão", "Boleto Impresso", "Boleto Pago", "Devolvida" 
    * @param    id_diferente: false para trazer vendas do id logado true para os outros que não seja o logado
    * @return	array do boletos pagos, gerados e cartões na monetizze
    *           false se não achar nenhuma venda
    */
    public function get_vendas_plataforma($id, $plataforma, $nao_confirmados = false, $produto = false, $tipo = 'all', $id_diferente = false)
    {
        log_message('debug', 'get_vendas_plataforma');  
        
        $user_id = $this->getuserid($id);
        $plats = $this->busca_plataformas_vendas($user_id);

        $filtro_periodo = $this->filtro_periodo($user_id);

        if($filtro_periodo)
        {
            $this->db->where("data_compra > '" . $filtro_periodo . "'");
        }
        
        //Associações não confirmadas
        if($nao_confirmados)
        {
            $this->db->where("ad_status != 'OK'");    
        }

        if($produto)
        {
            $this->db->where("produto", $produto);     
        }

        if($tipo != 'all')
        {
            $this->db->where("tipo", $tipo);     
        }

        $this->db->order_by("data_compra");

        if($id_diferente)
        {
            $this->db->select("count(*) as num_vendas, sum(comissao), min(data_compra), user_id");
            $this->db->where("user_id != ", $user_id);
            $this->db->group_by("user_id");
        }
        else
        {
            $this->db->select("*");
            $this->db->where("user_id", $user_id);
        }


        if(!$plataforma)
        {
            if($plats)
            {
                $return = array();
                $sql = $this->db->get_compiled_select("#plataforma#", false);
                foreach($plats as $key => $val)
                {
                    $plataforma = strtolower($key);
                    //View
                    $sql_run = str_replace("#plataforma#", "lista_vendas_".$plataforma, $sql);
                    $result = $this->db->query($sql_run);

                    log_message('debug', 'Num_rows: ' . $result->num_rows() . ' Last Query: ' . $this->db->last_query());

                    $return = array_merge($return, $result->result());       
                }
                $this->db->reset_query();
            }
            else
            {
                $this->db->reset_query();
                return false;
            }
                
        }
        else
        {
            $plataforma = strtolower($plataforma);
            //View
            $result = $this->db->get("lista_vendas_".$plataforma);
            
            log_message('debug', 'Num_rows: ' . $result->num_rows() . ' Last Query: ' . $this->db->last_query());

            $return = $result->result();
        }
        
        return $return;
    }

    /**
    * get_ads_ativos_30_dias
    *
    * Traz uma lista de anúncios ativos nos últimos 30 dias do usuário logado
    * @param    id: Id do Facebook do usuário logado
    * @return	array com o nome do anúncio, conjunto, campanha e conta, status do anúnico e tags
    */
    public function get_ads_ativos_30_dias($id)
    {
        log_message('debug', 'get_ads_ativos_30_dias');

        $today = date('Y-m-d', strtotime("-30 days"));

        $this->db->select("ads.id, ads.name, ads.effective_status, ad_creatives.url_tags, adsets.name as conjunto, 
campaigns.name as campanha, accounts.name as conta");
        $this->db->from("ads"); 
        $this->db->join("ad_creatives","ads.id = ad_creatives.ad_id");   
        $this->db->join("adsets","ads.adset_id = adsets.id");   
        $this->db->join("campaigns","ads.campaign_id = campaigns.id");  
        $this->db->join("accounts","ads.account_id = accounts.id");  
        $this->db->where("ads.effective_status != 'DISAPPROVED'");
        $this->db->where("(ads.effective_status = 'ACTIVE' or (ads.effective_status != 'ACTIVE' and
                ads.updated_time > '" . $today . "'))");
        $this->db->where("accounts.facebook_id",$id);
        $this->db->order_by("ads.effective_status");

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->result();
    }

    /**
    * insert_ads_vendas
    *
    * Associa o ad com o postback inserindo na tabela ads_vendas
    * @param    array: Dados a serem inseridos
    * @return	-
    */
    public function insert_ads_vendas($array_insert)
    {
        log_message('debug', 'insert_ads_vendas');

        foreach($array_insert as $insert)
        {
            if(array_key_exists('id_plataforma',$insert))
            {
                $this->db->where('plataforma', $insert['plataforma']);
                $this->db->where('id_plataforma', $insert['id_plataforma']);
                $result = $this->db->get('ads_vendas');
    
                if($result->num_rows() > 0)
                    continue;
            }
            

            $this->db->insert('ads_vendas', $insert);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            if(array_key_exists('id_plataforma',$insert))
            {
                $this->db->set("ad_status","OK");
                $this->db->where("postback_" . strtolower($insert['plataforma']) . "_id", $insert['id_plataforma']);
                $this->db->update("postback_" . strtolower($insert['plataforma']));
    
                log_message('debug', 'Last Query: ' . $this->db->last_query());
            }
        }
    }

    /**
    * undo_ads_vendas
    *
    * Desassocia o ad com o postback inserindo na tabela ads_vendas
    * @param    $ads_vendas_id: Id do ad a ser desassociado
    * @return	-
    */
    public function undo_ads_vendas($ads_vendas_id)
    {
        log_message('debug', 'undo_ads_vendas');

        $this->db->where('ads_vendas_id', $ads_vendas_id);
        $result = $this->db->get('ads_vendas');

        $plataforma = $result->row()->plataforma;
        
        if(isset($result->row()->id_plataforma))
            $id_plataforma = $result->row()->id_plataforma;

        $this->db->where('ads_vendas_id', $ads_vendas_id);
        $result = $this->db->delete('ads_vendas');
            
        if(isset($id_plataforma))
        {
            $this->db->set("ad_status","0");
            $this->db->where("postback_" . strtolower($plataforma) . "_id", $id_plataforma);
            $this->db->update("postback_" . strtolower($plataforma));
        }

        log_message('debug', 'Last Query: ' . $this->db->last_query());
    }

    /**
    * getProdutoComissao
    *
    * Traz comissão, nome e data da venda do postback
    * @param    id_plataforma: id que será buscado a venda
    * @param    plataforma: nome da plataforma
    * @return	array com a comissao, nome e data da venda
    */
    public function getProdutoComissao($id_plataforma, $plataforma)
    {
        log_message('debug', 'getProdutoComissao');

        switch($plataforma)
        {
            case 'Monetizze':
                $this->db->select("venda_valor_recebido as comissao, produto_nome as produto, venda_data_inicio as data");
                $this->db->from("postback_monetizze");
                $this->db->where("postback_monetizze_id", $id_plataforma);
                break;
            case 'Hotmart':
                $this->db->select("cms_aff as comissao, prod_name as produto, purchase_date as data");
                $this->db->from("postback_hotmart");
                $this->db->where("postback_hotmart_id", $id_plataforma);
                break;
        }

        $result = $this->db->get();

        return $result->row();
    }

    /**
    * deleteToNewSync
    *
    * Apaga os dados do usuário para nova sincronização
    *   - A cada sincronização todos os dados relacionados a uma conta são apagados
    *     menos os insights por data
    * @param    id: Id da Conta cujos dados serão apagados
    * @return	-
    */
    public function deleteToNewSync($id, $completa = false)
    {
        log_message('debug', 'deleteToNewSync. Id:' . $id);
        
        //Apaga do ad_creatives
        $this->db->where('account_id', $id);
        $this->db->delete('ad_creatives');
        
        //Apaga os insights do anúncio (geral) e seus actions
        $sql = "DELETE ad_insights_actions, ad_insights FROM ad_insights_actions
            JOIN ad_insights ON ad_insights_actions.ad_insights_id = ad_insights.ad_insights_id
            WHERE ad_insights.account_id = '" . $id . "'";

        if(!$completa)
            $sql .= " AND ad_insights.bydate is NULL;";
        else
            $sql .= ";";
        
        
        $this->db->query($sql);

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 
        
        //Apaga os anúncios
        $this->db->where('account_id', $id);
        $this->db->delete('ads');

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        //Apaga os insights do conjunto (geral) e seus actions
        $sql = "DELETE adset_insights_actions, adset_insights FROM adset_insights_actions
            JOIN adset_insights ON adset_insights_actions.adset_insights_id = adset_insights.adset_insights_id
            WHERE adset_insights.account_id = '" . $id . "'";
        
        if(!$completa)
            $sql .= " AND adset_insights.bydate is NULL;";
        else
            $sql .= ";";

        $this->db->query($sql);

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        //Apaga o targeting do conjunto
        $this->db->query("DELETE adsets, adset_targeting FROM adsets
	                        JOIN adset_targeting ON adsets.id = adset_targeting.adset_id
                            WHERE adsets.account_id = '" . $id . "';");

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        //Apaga os insights da campanha (geral) e seus actions
        $sql = "DELETE campaign_insights_actions, campaign_insights FROM campaign_insights_actions
            JOIN campaign_insights ON campaign_insights_actions.campaign_insights_id = campaign_insights.campaign_insights_id
            WHERE campaign_insights.account_id = '" . $id . "'";

        if(!$completa)
            $sql .= " AND campaign_insights.bydate is NULL;";
        else
            $sql .= ";";

        $this->db->query($sql);

        log_message('debug', 'Last Query: ' . $this->db->last_query());  

        //Apaga as campanhas
        $this->db->where('account_id', $id);
        $this->db->delete('campaigns');

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        //Apaga os actions dos insights da conta
        $this->db->where('account_id', $id);
        $this->db->delete('account_insights_actions');

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        //Apaga os insights da conta
        $this->db->where('account_id', $id);
        $this->db->delete('account_insights');

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        //Apaga as conversões personalizadas
        $this->db->where('account_id', $id);
        $this->db->delete('account_custom_conversion');

        log_message('debug', 'Last Query: ' . $this->db->last_query());   

        //Apaga a conta
        $this->db->where('id', $id);
        $this->db->delete('accounts');

        log_message('debug', 'Last Query: ' . $this->db->last_query());    
    }

    /**
    * getPlataformas
    *
    * Traz lista de plataformas cadastradas
    *
    * @return	
    *    false se não encontrar nenhuma plataforma
    *    lista de plataformas
    */
    public function getPlataformas(){
        log_message('debug', 'getPlataformas');

        $this->db->select("platform_id, name, postback_url");
        $this->db->from("platforms");

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

    /**
    * getProdutos
    *
    * Traz lista de produtos cadastrados por plataforma
    *
    * @param $plataforma: Plataforma a ser pesquisada
    *
    * @return	
    *    false se não encontrar nenhum produto
    *    lista de produtos para a plataforma selecionada
    */
    public function getProdutos($plataforma){
        log_message('debug', 'getProdutos');

        $this->db->where("plataforma", $plataforma);
        $result = $this->db->get("produtos");

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

    /**
    * getProdutosByUser
    *
    * Traz lista de produtos cadastrados por plataforma
    *
    * @param $user_id: Plataforma a ser pesquisada
    *
    * @return	
    *    false se não encontrar nenhum produto
    *    lista de produtos para o usuário logado
    */
    public function getProdutosByUser($user_id){
        log_message('debug', 'getProdutos');

        $this->db->distinct();
        $this->db->select("produtos.nome, produtos.plataforma");
        $this->db->from("produtos");
        $this->db->join("postback_hotmart", "produtos.codigo = postback_hotmart.prod", "left");
        $this->db->join("platform_users", "postback_hotmart.hottok = platform_users.token");
        $this->db->where("platform_users.user_id", $user_id);

        $query1 = $this->db->get_compiled_select();


        $this->db->distinct();
        $this->db->select("produtos.nome, produtos.plataforma");
        $this->db->from("produtos");
        $this->db->join("postback_monetizze", "produtos.codigo = postback_monetizze.produto_codigo", "left");
        $this->db->join("platform_users", "postback_monetizze.chave_unica = platform_users.token");
        $this->db->where("platform_users.user_id", $user_id);

        $query2 = $this->db->get_compiled_select();

        $sql = "SELECT * FROM (" . $query1 . " union " . $query2 . ") a";      
        
        $result = $this->db->query($sql);

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

    /**
    * getUserTokens
    *
    * Traz lista de tokens do usuario
    *
    * @param $id: id do facebook logado
    * @return	
    *    false se não encontrar nenhum token
    *    lista de tokens
    */
    public function getUserTokens($id){
        log_message('debug', 'getUserTokens');

        $this->db->select("platform_users.platform_user_id, platform_users.token, platform_users.created_time, platforms.name as plataforma");
        $this->db->from("platform_users");
        $this->db->join("platforms","platform_users.platform_id = platforms.platform_id");
        $this->db->join("users","platform_users.user_id = users.user_id");
        $this->db->join("profiles","profiles.user_id = users.user_id");
        $this->db->where("profiles.facebook_id",$id);

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

    /**
    * insertToken
    *
    * Insere novo token do usuário
    *
    * @param $plataforma: id da plataforma
    * @param $token: token
    * @param $id: Facebook id logado
    * @return -
    */
    public function insertToken($plataforma, $token, $id){
        log_message('debug', 'insertToken');

        $user_id = $this->getuserid($id);

        $arr_insert = array("token" => $token,
                            "platform_id" => $plataforma,
                            "created_time" => date("Y-m-d H:i:s"),
                            "status" => 1,
                            "user_id" => $user_id);

        $this->db->insert("platform_users", $arr_insert);

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 
    }

    /**
    * deleteToken
    *
    * Apaga tokens do usuario
    * @param    array: Dados a serem apagados
    * @return	-
    */
    public function deleteToken($array_delete)
    {
        log_message('debug', 'deleteToken');

        foreach($array_delete as $delete)
        {   
            $this->db->where("platform_user_id", $delete);
            $this->db->delete('platform_users');

            log_message('debug', 'Last Query: ' . $this->db->last_query());
        }
    }

    /**
    * getConfig
    *
    * Traz configurações do usuário
    * @param    $id: id do Facebook
    * @return	Configurações
    */
    public function getConfig($id)
    {
        log_message('debug', 'getConfig');

        $user_id = $this->getuserid($id);

        $this->db->where("user_id", $user_id);
        $result = $this->db->get("config");

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row();
    }

    /**
    * filtro_periodo
    *
    * Pega o período que o usuário optou por exibir
    * @param    $id: facebook_id do usuario logado
    * @return	-
    */
    public function filtro_periodo($user_id, $fb_id = false)
    {
        log_message('debug', 'filtro_periodo');

        if($fb_id)
            $user_id = $this->getuserid($user_id);

        $ret = $this->get_period_data($user_id);

        if($ret == null || $ret == 'historico')
            return false;

        if($ret == '30d')
            $retorna = date('Y-m-d', strtotime("-30 days"));
        else if($ret == '7d')
            $retorna = date('Y-m-d', strtotime("-30 days"));
        else if($ret == 'mes')
            $retorna = date('Y-m-d', strtotime(date('Y-m-1')));

        return $retorna;
        
        
    }  

    /**
    * get_period_data
    *
    * Pega o período que o usuário optou por exibir
    * @param    $id: facebook_id do usuario logado
    * @return	-
    */
    public function get_period_data($id)
    {
        log_message('debug', 'get_period_data');   

        //Verifica se existe este perfil no banco
        $this->db->select('period_shown');
        $this->db->from('config');
        $this->db->where('user_id',$id);
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row()->period_shown;
    }

    /**
    * set_period_data
    *
    * Seta o período que o usuário optou por exibir
    * @param    $id: facebook_id do usuario logado
    * @param    $period: Período
    * @return	-
    */
    public function set_period_data($id, $period)
    {
        log_message('debug', 'get_period_data');

        $user_id = $this->getuserid($id);  
        
        $arr_data = array("period_shown" => $period);

        //Verifica se existe este perfil no banco
        $this->db->where('user_id', $user_id); 
        $this->db->update('config', $arr_data);
    }

    /**
    * saveConfig
    *
    * Salva configurações do usuário
    * @param    $sync_time: sincronização feita a cada quantidade de horas
    * @param    $postback_enabled: true or false para habilitar ou desabilitar postback
    * @param    $id: facebook_id do usuario logado
    * @return	-
    */
    public function saveConfig($sync_time, $postback_enabled, $id)
    {
        log_message('debug', 'saveConfig');

        $user_id = $this->getuserid($id);    

        //Verifica se existe este perfil no banco
        $this->db->where('user_id',$user_id);
        $result = $this->db->get('config');

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        $arr_data = array("sync_time" => $sync_time,
                        "postback_enabled" => $postback_enabled,
                        "user_id" => $user_id);

        //Se existir atualiza, senão insere
        if($result->num_rows() > 0)
        {
            $this->db->where('user_id', $user_id); 
            $this->db->update('config', $arr_data);
        } 
        else
        {
            $this->db->insert('config', $arr_data);   
        }


    }

    /**
    * saveConfigPlanilha
    *
    * Salva configurações da planilha do usuário
    * @param    $metas: Metas
    * @param    $id: facebook_id do usuario logado
    * @return	-
    */
    public function saveConfigPlanilha($metas, $id)
    {
        log_message('debug', 'saveConfigPlanilha');

        $user_id = $this->getuserid($id);    

        $this->db->where("idplanilhacampospreset", "1");
        $this->db->delete("planilhacamposmetas");

        foreach($metas as $meta)
        {
            $arr_data = array("idplanilhacampospreset" => "1",
                                "porcentagem" => $meta);

            $this->db->insert('planilhacamposmetas', $arr_data);
        }


    }

    /**
    * getConfigPlanilha
    *
    * Pega dados da configuração da Planilha
    * @param    $preset: preset definido
    * @return	-
    */
    public function getConfigPlanilha($id)
    {
        log_message('debug', 'getConfigPlanilha');

        $this->db->select('porcentagem');
        $this->db->from('planilhacamposmetas');
        $this->db->join('planilhacampospreset','planilhacampospreset.idplanilhacampospreset = planilhacamposmetas.idplanilhacampospreset');
        
        $this->db->where('planilhacampospreset.idplanilhacampospreset', $id);  

        $result = $this->db->get();

        return $result->result();

    }

    /**
    * getConfigPlanilha
    *
    * Pega ordem da Planilha
    * @param    $preset: preset definido
    * @return	-
    */
    public function getPlanilhaOrdem($id)
    {
        log_message('debug', 'getPlanilhaOrdem');

        $this->db->where('preset', $id);  
        $this->db->order_by('ordem');
        $result = $this->db->get('planilhacampos');

        return $result->result();

    }

    /**
    * salva_data_resync
    *
    * Salva data da última resincronização
    * @param    $id: id do Facebook que fez a sincronização
    * @return	-
    */
    public function salva_data_resync($id)
    {
        log_message('debug', 'salva_data_resync');

        $user_id = $this->getuserid($id);  

        $data['last_update'] = date("Y-m-d H:i:s");

        $this->db->where('user_id', $user_id);
        $this->db->update('config', $data);

        log_message('debug', 'Last Query: ' . $this->db->last_query());

    }

    /**
    * get_resync_to_do
    *
    * Traz os usuarios que já podem fazer sincronização
    * @param    $id: id do Facebook que fez a sincronização
    * @return	-
    */
    public function get_resync_to_do()
    {
        log_message('debug', 'get_resync_to_do');

        $result = $this->db->query("SELECT profiles.facebook_id as id
        FROM config
        JOIN profiles ON config.user_id = profiles.user_id
        WHERE NOW() > DATE_ADD(config.last_update, INTERVAL config.sync_time HOUR)
                OR config.last_update is NULL");
 
        return $result->result();
    }

    public function get_last_activity($id)
    {
        $this->db->select('max(event_time) as event_time');
        $this->db->from('accounts_activities');
        $this->db->where('account_id',$id);
        $result = $this->db->get();

        $row = $result->row();

        if($row->event_time != null)
        {
            $data = explode('T',$row->event_time);

            $this->db->like('event_time', $data[0], 'both');
            $this->db->where('account_id',$id);
            $this->db->delete('accounts_activities');

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            return $data[0];
        }
        else
            return false;
    }

    public function get_profiles($id=0)
    {
        log_message('debug', 'get_profiles');  

        if($id==0)
        {
            $ret = $this->db->get('profiles');
            log_message('debug', 'Last Query: ' . $this->db->last_query());
            return $ret->result();
        }
        else
        {
            $this->db->where('profile_id', $id);
            $ret = $this->db->get('profiles');
            log_message('debug', 'Last Query: ' . $this->db->last_query());
            return $ret->row();
        }   
    }

    public function insert_activity($array, $conta, $fb_id)
    {
        log_message('debug', 'insert_activity'); 

        foreach($array as $arr_insert)
        {
            $arr_insert['account_id'] = $conta;
            $arr_insert['facebook_id'] = $fb_id;

            $ret = $this->db->insert('accounts_activities', $arr_insert);

            if(!$ret)
            {
                log_message('debug', 'Erro na query: ' . $this->db->last_query());
            }
        }

    }

    public function insert_contas_info($array)
    {
        log_message('debug', 'insert_contas_info'); 

        $this->db->where('id', $array['id']);
        $result = $this->db->get('accounts_info');

        if($result->num_rows() == 0)
        {
            $ret = $this->db->insert('accounts_info', $array); 

            if(!$ret)
                log_message('debug', 'Erro: ' . $this->db->error()->message);   
        }
        else
        {
            $this->db->where('id', $array['id']);
            $ret = $this->db->update('accounts_info', $array);   

            if(!$ret)
                log_message('debug', 'Erro: ' . $this->db->error()->message);  
        }

        log_message('debug', 'Last Query: ' . $this->db->last_query());
    }

    public function get_accounts_info($user_id)
    {
        log_message('debug', 'get_accounts_info'); 

        $face_id = $this->getfbid($user_id);   

        $this->db->where('facebook_id', $face_id);
        $ret = $this->db->get('accounts_info');

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $ret->result();   
    }

    public function show_conta_activities($account)
    {
        log_message('debug', 'show_conta_activities');  

        /*
        $this->db->where('account_id', $account);
        $this->db->order_by('event_time');
        $ret = $this->db->get('accounts_activities');
        return $ret->result(); */

        $this->db->select("ads.id, ads.name, ads.created_time, ads.updated_time, accounts_activities.event_time,
 accounts_activities.event_type, accounts_activities.extra_data, 'anuncio' as tipo, accounts_activities.date_time_in_timezone,
 ads.campaign_id as campanha_id, ads.adset_id as conjunto_id, id as anuncio_id, 3 as id_tipo");
        $this->db->from("ads");
        $this->db->join("accounts_activities", "ads.id = accounts_activities.object_id");
        $this->db->where("ads.account_id", $account);

        $query1 = $this->db->get_compiled_select();

        $this->db->select("adsets.id, adsets.name, adsets.created_time, adsets.updated_time, accounts_activities.event_time,
 accounts_activities.event_type, accounts_activities.extra_data, 'conjunto' as tipo, accounts_activities.date_time_in_timezone,
 adsets.campaign_id as campanha_id, adsets.id as conjunto_id, '' as anuncio_id, 2 as id_tipo");
        $this->db->from("adsets");
        $this->db->join("accounts_activities", "adsets.id = accounts_activities.object_id");
        $this->db->where("adsets.account_id", $account);

        $query2 = $this->db->get_compiled_select();

        $this->db->select("campaigns.id, campaigns.name, campaigns.created_time, campaigns.updated_time, accounts_activities.event_time,
 accounts_activities.event_type, accounts_activities.extra_data, 'campanha' as tipo, accounts_activities.date_time_in_timezone,
 id as campanha_id, '' as conjunto_id, '' as anuncio_id, 1 as id_tipo");
        $this->db->from("campaigns");
        $this->db->join("accounts_activities", "campaigns.id = accounts_activities.object_id");
        $this->db->where("campaigns.account_id", $account);
        
        $query3 = $this->db->get_compiled_select();

        $sql = "SELECT * FROM (" . $query1 . " union " . $query2 . " union " . $query3 . ") a order by event_time, id_tipo";

        $ret = $this->db->query($sql);

        return $ret->result();
   
    }

    public function get_ad_data_preview($ad_id)
    {
        $this->db->select("ad_creatives.body, ad_creatives.object_story_spec_link_data_message,
		ad_creatives.image_url, ad_creatives.title, ad_creatives.object_story_spec_link_data_name,
        ad_creatives.object_story_spec_link_data_description, ad_creatives.call_to_action_type,
		ad_creatives.object_story_spec_link_data_link, ad_creatives.url_tags, ad_creatives.effective_object_story_id,
        adsets.attribution_spec_window_days, adsets.optimization_goal, adsets.promoted_object_custom_event_type,
        ad_insights.cost_per_inline_link_click as cpc, ad_insights.inline_link_click_ctr as ctr,
        ad_insights.cpm, ad_insights.relevance_score_score, ad_insights.spend, ad_insights.impressions,
        ad_insights.clicks, max(accounts_activities.event_time) as ultima, min(accounts_activities.event_time) as primeira,
        adset_targeting.age_max, adset_targeting.age_min, adset_targeting.device_platforms,
		adset_targeting.publisher_platforms, adset_targeting.genders,
        custom_audiences, excluded_custom_audiences, geo_locations, excluded_geo_locations,
		facebook_positions, exclusions, interests, flexible_spec_interests, behaviors, flexible_spec_behaviors");

        $this->db->from("ads"); 
        $this->db->join("adsets", "ads.adset_id = adsets.id");
        $this->db->join("adset_targeting", "adset_targeting.adset_id = adsets.id");
        $this->db->join("ad_insights", "ads.id = ad_insights.ad_id", "left");
        $this->db->join("ad_creatives", "ads.id = ad_creatives.ad_id");
        $this->db->join("accounts_activities", "ads.id = accounts_activities.object_id");

        $this->db->where("ads.id",$ad_id);

        $ret = $this->db->get();

        return $ret->row();
 
    }

    
}