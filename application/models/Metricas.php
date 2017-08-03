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
        } 
        else
        {
            $data['updated_time'] = date("Y-m-d H:i:s");
            $data['created_time'] = date("Y-m-d H:i:s");

            $this->db->insert('profiles', $data);
        }

        log_message('debug', 'Last Query: ' . $this->db->last_query());
    }

    /**
    * getContasDetalhes
    *
    * Traz em detalhes, os anúncios ativos de um perfil no Facebook
    *
    * @param	id: Id do Facebook
    * @return	
    *    false se não encontrar nenhum anúncio
    *    lista de anúncios
    */
    public function getContasDetalhes($id){
        log_message('debug', 'getContas');

        $this->db->select("ads.id, ads.name as ad_name, adsets.name as ad_sets_name, campaigns.name as campaigns_name,
                accounts.name as account_name, ad_creatives.effective_object_story_id, ad_creatives.url_tags");
        $this->db->from("ads");
        $this->db->join("adsets","ads.adset_id = adsets.id");
        $this->db->join("campaigns","ads.campaign_id = campaigns.id");
        $this->db->join("accounts","ads.account_id = accounts.id");
        $this->db->join("ad_creatives","ad_creatives.ad_id = ads.id");
        
        //Traz somente os anúncios realmente ativos
        $this->db->where("ads.effective_status = 'ACTIVE'");
        $this->db->where("accounts.account_status = 1");

        $this->db->where('accounts.facebook_id',$id);
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

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

    /**
    * getFromConta
    *
    * Traz nome, id e status do (ad, conjunto e campanha) a partir de uma conta cadastrada
    *
    * @param	id: Id da conta
    * @param    tipo(string): ad, adset, campaign
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
    * @param	id: Id do usuário
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
    * @param    tipo(string): campaigns, adsets, ads
    * @return	
    *    "Nenhum ativo" se não encontrar nenhum tipo ativo
    *    lista de um determinado tipo
    */
    public function get_from_tipo($id, $tipo)
    {
        log_message('debug', 'get_from_tipo');


        $this->db->select("name, id");
        $this->db->from($tipo);

        switch($tipo)
        {
            case 'campaigns':
                $where = "account_id";
                break;
            case 'adsets':
                $where = "campaign_id";
                break;
            case 'ads':
                $where = "adset_id";
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
    * @param	arr_account(array): As contas a serem inseridas no banco
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
    * @param	arr_campaign(array): As campanhas a serem inseridas no banco
    * @return	-
    */
    public function insertCampaign($arr_campaign)
    {
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
    * @param	arr_insights(array): Os insights a serem inseridos no banco
    * @param    tipo(string): ad, adset ou campaign. Tipo do insight
    * @param    bydate(boolean): padrão false. Se o insight é geral(false) ou por dia(true)
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

            if(!$this->db->insert($tipo.'_insights',$array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);
            
            log_message('debug', 'Last Query: ' . $this->db->last_query());
            
            $insert_id = $this->db->insert_id();

            if(isset($arr_action))
            {
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
    * @param	arr_adset(array): Os conjuntos a serem inseridos no banco
    * @return   -
    */
    public function insertAdSet($arr_adset)
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
            if(!$this->db->insert('adsets', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            //Se tiver targeting. Insere
            if(isset($arr_targeting))
            {
                if(!$this->db->insert('adset_targeting',$arr_targeting))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());  
                unset($arr_targeting);  
            }

            //Se tiver insights e actions. Insere
            if(isset($arr_insights))
            {
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
    * @param	arr_ad(array): Os anúncios a serem inseridos no banco
    * @return	-
    */
    public function insertAd($arr_ad)
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
            if(!$this->db->insert('ads', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            //Se tiver insights e actions. Insere.
            if(isset($arr_insights))
            {
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
                if(!$this->db->insert('ad_creatives',$arr_creative))
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
    * @param	arr_custom_conversions(array): As conversões personalizadas a serem inseridas no banco
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

        //Busca da tabela <tipo>_insight a última data quebrada já sincronizada
        $this->db->select('date_start, '.$tipo.'_insights_id');
        $this->db->from($tipo.'_insights');
        $this->db->where($tipo.'_id', $id);
        $this->db->where('bydate = 1');
        $this->db->order_by($tipo.'_insights_id', 'desc');
        $this->db->limit(1);
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        //Se achou, apaga esta data de insights e actions
        if($result->num_rows() > 0)
        {
            $row = $result->row();

            $this->db->where($tipo.'_insights_id', $row->{$tipo.'_insights_id'});
            $this->db->delete($tipo.'_insights_actions');

            $this->db->where($tipo.'_insights_id', $row->{$tipo.'_insights_id'});
            $this->db->delete($tipo.'_insights');

            return explode(' ', $row->date_start)[0];    
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
    * @param    tipo(string): Qual tipo será pego: ad, adset, campaign
    * @return	
    *    data de criação do tipo ou data do insight 
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

        $row = $result->row();

        //Retorna a data de criação do tipo
        return explode('T', $row->created_time)[0];
    }

    /**
    * getTableData
    *
    * Pega os dados necessários que serão exibidos na planilha
    *
    * @param	id: O Id do tipo que será pesquisado
    * @param    tipo(string): Qual tipo será pego: ad, adset, campaign
    * @return	
    *    lista de dados (geral e por dia) do id do tipo pesquisado
    */
    public function getTableData($id, $tipo)
    {
        log_message('debug', 'getTableData. Id:' . $id);
        
        $this->db->select('date_start, cost_per_inline_link_click, inline_link_click_ctr, inline_link_clicks,impressions, cpm, relevance_score_score, spend, bydate, ' . $tipo . '_insights_id');
        $this->db->from($tipo.'_insights');
        $this->db->where($tipo.'_id', $id);
        $this->db->order_by('bydate', 'DESC');
        $this->db->order_by('date_start');
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->result();

    }

    /**
    * getTableData
    *
    * Pega pega todas as conversões a serem mostradas na planilha
    *   - As conversões sempre começam com 'offsite_conversion.'
    *
    * @param	id: O Id do tipo que será pesquisado
    * @param    tipo(string): Qual tipo será pego: ad, adset, campaign
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
    * @param    tipo(string): Qual tipo será pego: ad, adset, campaign
    * @return	
    *    lista de todas as conversões possíveis para este id
    */
    public function getPossibleConversions($id, $tipo)
    {
        log_message('debug', 'getPossibleConversions. Id:' . $id);
        
        $this->db->distinct();
        $this->db->select('action_type');
        $this->db->from($tipo.'_insights_actions');
        $this->db->like('action_type', 'offsite_conversion.', 'after');
        $this->db->where($tipo.'_id', $id);
        
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
    * get_tags_from_ad
    *
    * Pega o url_tag configurado no anúncio
    *
    * @param	id: O Id do anúncio
    * @return   (string): Nome da conversão personalizada
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
    * @return	(string): Id do usuário
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
    * dados_vendas
    *
    * Traz os dados já somados dos postbacks ligados ao anúncio, conjunto ou campanha
    *  agrupados por dia e id
    *
    * @param	id: O Id do tipo que será pesquisado
    * @param    tipo(string): Qual tipo será pego: ad, adset, campaign
    * @return	
    *    lista de dados dos boletos gerados, pagos, cartões e seus valores agrupados por data e id
    */
    function dados_vendas($id, $tipo)
    {
        log_message('debug', 'dados_vendas');

        $this->db->select("sum(boletos_gerados) as boletos_gerados, sum(boletos_pagos) as boletos_pagos, 
            sum(cartoes) as cartoes, sum(boletos_pagos * comissao) as faturamento_boleto, 
            sum(cartoes * comissao) as faturamento_cartao, produto,
            substring(data,1,10) as dt, " . $tipo . "_id");

        $this->db->from("ads_vendas");
        $this->db->where($tipo . "_id",$id);
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
    * @param    tipo(string): Qual tipo será pego: ad, adset, campaign
    * @return	
    *    lista de dados dos boletos gerados, pagos, cartões e seus valores agrupados por id
    */
    function dados_vendas_geral($id, $tipo)
    {
        log_message('debug', 'dados_vendas_geral');

        $this->db->select("sum(boletos_gerados) as boletos_gerados, sum(boletos_pagos) as boletos_pagos, 
            sum(cartoes) as cartoes, sum(boletos_pagos * comissao) as faturamento_boleto, 
            sum(cartoes * comissao) as faturamento_cartao, produto, " . $tipo . "_id");

        $this->db->from("ads_vendas");
        $this->db->where($tipo . "_id",$id);
        $this->db->group_by(array($tipo . "_id"));
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->row();
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
		    venda_valor as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
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
		    venda_valor as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
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
		    venda_valor as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
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

        if($result->num_rows() > 0) $ret['Hotmart'] = $result->row()->hottok;

        $this->db->select("postback_monetizze.chave_unica");
        $this->db->from("postback_monetizze");
        $this->db->join("platform_users", "postback_monetizze.chave_unica = platform_users.token ");
        $this->db->join("platforms","platform_users.platform_id = platforms.platform_id"); 
        $this->db->where("platforms.name = 'Monetizze'");
        $this->db->where("platform_users.user_id", $id);
        $this->db->where("postback_monetizze.ad_status != 'OK'");
        $result = $this->db->get();

        if($result->num_rows() > 0) $ret['Monetizze'] = $result->row()->chave_unica;

        return $ret;
    }

    /**
    * busca_hotmart_token
    *
    * Busca através do token, uma lista de vendas na plataforma hotmart
    * @param    token: token da hotmart
    * @return	array do boletos pagos, gerados e cartões na hotmart
    *           false se não achar nenhuma venda
    */
    function busca_hotmart_token($token)
    {
        log_message('debug', 'busca_hotmart_token.');  

        $ret = false;

        //Busca somente os boleto impresso e que não estão na lista de pagos
        $result = $this->db->query("SELECT purchase_date as data_compra, confirmation_purchase_date as data_confirmacao,
		    cms_aff as comissao, prod_name as produto, postback_hotmart_id as id_plataforma,
            'hotmart' as plataforma, src FROM postback_hotmart
            WHERE status = 'billet_printed' AND ad_status != 'OK'
            AND hottok = '" . $token . "'
            AND transaction not in (SELECT transaction FROM postback_hotmart 
							WHERE status = 'approved' AND payment_type = 'billet')");

        if($result->num_rows() > 0) $ret = true;

        $retorno['boleto_impresso'] = $result->result();

        $this->db->select("purchase_date as data_compra, confirmation_purchase_date as data_confirmacao,
		    cms_aff as comissao, prod_name as produto, postback_hotmart_id as id_plataforma,
            'hotmart' as plataforma, src");
        $this->db->from("postback_hotmart");
        $this->db->where("ad_status != 'OK'");
        $this->db->where("status = 'approved'");
        $this->db->where("hottok", $token);
        $this->db->where("payment_type != 'billet'");
        $result = $this->db->get();

        if($result->num_rows() > 0) $ret = true;

        $retorno['cartao'] = $result->result();

        $this->db->select("purchase_date as data_compra, confirmation_purchase_date as data_confirmacao,
		    cms_aff as comissao, prod_name as produto, postback_hotmart_id as id_plataforma,
            'hotmart' as plataforma, src");
        $this->db->from("postback_hotmart");
        $this->db->where("ad_status != 'OK'");
        $this->db->where("status = 'approved'");
        $this->db->where("hottok", $token);
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
    * busca_monetizze_token
    *
    * Busca através do token, uma lista de vendas na plataforma monetizze
    * @param    token: token da hotmart
    * @return	array do boletos pagos, gerados e cartões na monetizze
    *           false se não achar nenhuma venda
    */
    function busca_monetizze_token($token)
    {
        log_message('debug', 'busca_monetizze.');  

        $ret = false;

        //Busca somente os boleto impresso e que não estão na lista de pagos
        $result = $this->db->query("SELECT venda_data_inicio as data_compra, venda_data_finalizada as data_confirmacao,
		    venda_valor as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
            'monetizze' as plataforma, venda_src as src
FROM postback_monetizze WHERE venda_forma_pagamento = 'Boleto' and postback_monetizze.ad_status != 'OK' 
and venda_status = 'Aguardando pagamento' and chave_unica = '" . $token . "'
and venda_codigo not in (SELECT venda_codigo FROM postback_monetizze
WHERE venda_status = 'Finalizada' and venda_forma_pagamento = 'Boleto')");

        if($result->num_rows() > 0) $ret = true;

        $retorno['boleto_impresso'] = $result->result();

        $this->db->select("venda_data_inicio as data_compra, venda_data_finalizada as data_confirmacao,
		    venda_valor as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
            'monetizze' as plataforma, venda_src as src");
        $this->db->from("postback_monetizze"); 
        $this->db->where("ad_status != 'OK'");
        $this->db->where("chave_unica", $token);
        $this->db->where("venda_status = 'Finalizada'");
        $this->db->where("venda_forma_pagamento != 'Boleto'");
        $result = $this->db->get();

        if($result->num_rows() > 0) $ret = true;

        $retorno['cartao'] = $result->result();

        $this->db->select("venda_data_inicio as data_compra, venda_data_finalizada as data_confirmacao,
		    venda_valor as comissao, produto_nome as produto, postback_monetizze_id as id_plataforma,
            'monetizze' as plataforma, venda_src as src");
        $this->db->from("postback_monetizze");
        $this->db->where("ad_status != 'OK'");
        $this->db->where("chave_unica", $token);
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
        $this->db->where("ads.updated_time > '" . $today . "'");
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
            $insert->data = $insert->data_compra;
            unset($insert->data_compra);
            unset($insert->data_confirmacao);

            $this->db->insert('ads_vendas', $insert);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            $this->db->set("ad_status","OK");
            $this->db->where("postback_" . strtolower($insert['plataforma']) . "_id", $insert['id_plataforma']);
            $this->db->update("postback_" . strtolower($insert['plataforma']));

            log_message('debug', 'Last Query: ' . $this->db->last_query());
        }
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
    public function deleteToNewSync($id)
    {
        log_message('debug', 'deleteToNewSync. Id:' . $id);
        
        //Apaga do ad_creatives
        $this->db->where('account_id', $id);
        $this->db->delete('ad_creatives');
        
        //Apaga os insights do anúncio (geral) e seus actions
        $this->db->query("DELETE ad_insights_actions, ad_insights FROM ad_insights_actions
	                        JOIN ad_insights ON ad_insights_actions.ad_insights_id = ad_insights.ad_insights_id
                            WHERE ad_insights.bydate is NULL AND ad_insights.account_id = '" . $id . "';");

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 
        
        //Apaga os anúncios
        $this->db->where('account_id', $id);
        $this->db->delete('ads');

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        //Apaga os insights do conjunto (geral) e seus actions
        $this->db->query("DELETE adset_insights_actions, adset_insights FROM adset_insights_actions
	                        JOIN adset_insights ON adset_insights_actions.adset_insights_id = adset_insights.adset_insights_id
                            WHERE adset_insights.bydate is NULL AND adset_insights.account_id = '" . $id . "';");

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        //Apaga o targeting do conjunto
        $this->db->query("DELETE adsets, adset_targeting FROM adsets
	                        JOIN adset_targeting ON adsets.id = adset_targeting.adset_id
                            WHERE adsets.account_id = '" . $id . "';");

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        //Apaga os insights da campanha (geral) e seus actions
        $this->db->query("DELETE campaign_insights_actions, campaign_insights FROM campaign_insights_actions
	                        JOIN campaign_insights ON campaign_insights_actions.campaign_insights_id = campaign_insights.campaign_insights_id
                            WHERE campaign_insights.bydate is NULL AND campaign_insights.account_id = '" . $id . "';");

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
}