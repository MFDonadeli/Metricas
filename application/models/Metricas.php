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
    * @param	string
    * @param	string 
    * @return	boolean
    */
    public function checkUser($data)
    {   
        log_message('debug', 'checkUser');

        if(isset($data['oauth_provider'])) unset($data['oauth_provider']);
        if(isset($data['oauth_uid'])) unset($data['oauth_uid']);
        if(isset($data['logged_in'])) unset($data['logged_in']);

        //Validate
        $this->db->where('facebook_id',$data['facebook_id']);
        $result = $this->db->get('profiles');

        log_message('debug', 'Last Query: ' . $this->db->last_query());

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
    * getContas
    *
    * Trás contas de anúncio do usuário do Facebook
    *
    * @param	id
    * @param	string 
    * @return	boolean
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
        
        $this->db->where("ads.status = 'ACTIVE'");
        $this->db->where("adsets.status = 'ACTIVE'");
        $this->db->where("campaigns.status = 'ACTIVE'");
        $this->db->where("accounts.account_status = 1");

        $this->db->where('accounts.facebook_id',$id);
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

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

    public function getFromConta($id, $tipo){
        log_message('debug', 'getFromConta');

        if($tipo == 'campaign')
        {
            $this->db->select("name, id, status");
            $this->db->from("campaigns");
            $this->db->where('account_id',$id);
            $this->db->where("status = 'ACTIVE'");
        }
        elseif($tipo == 'adset')
        {
            $this->db->select("adsets.name, adsets.id, adsets.status");
            $this->db->from("adsets");
            $this->db->where("adsets.account_id", $id);
            $this->db->where("adsets.effective_status = 'ACTIVE'");
        }
        elseif($tipo == 'ad')
        {
            $this->db->select("ads.name, ads.id, ads.status");
            $this->db->from("ads");
            $this->db->where("ads.account_id", $id);
            $this->db->where("ads.effective_status = 'ACTIVE'");
        }
        

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return false;   
    }

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
        $this->db->where('effective_status', 'ACTIVE');

        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
            return $result->result();  
        else
            return "Nenhum ativo";   

    
    }


    public function insertAccount($arr_account)
    {
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

        if(!$this->db->insert('accounts', $arr_account))
            log_message('debug', 'Erro: ' . $this->db->error()->message);

        log_message('debug', 'Last Query: ' . $this->db->last_query());

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

                    log_message('debug', 'Last Query: ' . $this->db->last_query());
                }
            }
        }
        
    }

    public function insertCampaign($arr_campaign)
    {
        foreach($arr_campaign as $array)
        {
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

            if(!$this->db->insert('campaigns', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);
            
            log_message('debug', 'Last Query: ' . $this->db->last_query());

            if(isset($arr_insights))
            {
                if(!$this->db->insert('campaign_insights',$arr_insights))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);
                
                log_message('debug', 'Last Query: ' . $this->db->last_query());

                $insert_id = $this->db->insert_id();

                if(isset($arr_insights_action))
                {
                    foreach($arr_insights_action as $action)
                    {
                        $action['campaign_insights_id'] = $insert_id;
                        if(!$this->db->insert('campaign_insights_actions', $action))
                            log_message('debug', 'Erro: ' . $this->db->error()->message);
                        log_message('debug', 'Last Query: ' . $this->db->last_query());
                    }
                    unset($arr_insights_action);
                }
                unset($arr_insights);
            }
        }
        
    }

    public function insertInsights($arr_insights, $tipo)
    {
        foreach($arr_insights as $array)
        {
            if(array_key_exists('action', $array))
            {
                $arr_action = $array['action'];
                unset($array['action']);
            }
            
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
                    log_message('debug', 'Last Query: ' . $this->db->last_query());
                }
            }
            
        }
        
    }

    public function insertAdSet($arr_adset)
    {
        foreach($arr_adset as $array)
        {
            if(array_key_exists('targeting',$array))
            {
                $arr_targeting = $array['targeting'];
                unset($array['targeting']);
            }

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

            if(!$this->db->insert('adsets', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            if(isset($arr_targeting))
            {
                if(!$this->db->insert('adset_targeting',$arr_targeting))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());  
                unset($arr_targeting);  
            }

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

    public function insertAd($arr_ad)
    {
        foreach($arr_ad as $array)
        {
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

            if(array_key_exists('creative',$array))
            {
                $arr_creative = $array['creative'];
                unset($array['creative']);
            }

            if(!$this->db->insert('ads', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

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

            if(isset($arr_creative))
            {
                if(!$this->db->insert('ad_creatives',$arr_creative))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());
                unset($arr_creative);
            }
        }
    }

    public function grava_custom_conversions($arr_custom_conversions)
    {
        foreach($arr_custom_conversions as $array)
        {
            if(!$this->db->insert('account_custom_conversion',$array))
                log_message('debug', 'Erro: ' . $this->db->error()->message); 
            log_message('debug', 'Last Query: ' . $this->db->last_query());   
        }
    }

    public function getLastDateSync($id, $tipo)
    {
        log_message('debug', 'getLastDateSync');

        $this->db->select('date_start, '.$tipo.'_insights_id');
        $this->db->from($tipo.'_insights');
        $this->db->where($tipo.'_id', $id);
        $this->db->where('bydate = 1');
        $this->db->order_by($tipo.'_insights_id', 'desc');
        $this->db->limit(1);
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

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
        $result = $this->db->get($tipo.'s');

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        $row = $result->row();

        return explode('T', $row->created_time)[0];
    }

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

    function busca_hotmart_token($token)
    {
        log_message('debug', 'busca_hotmart_token.');  

        $ret = false;

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

    function busca_monetizze_token($token)
    {
        log_message('debug', 'busca_monetizze.');  

        $ret = false;

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

    public function deleteToNewSync($id)
    {
        log_message('debug', 'deleteToNewSync. Id:' . $id);
        
        $this->db->where('account_id', $id);
        $this->db->delete('ad_creatives');
        
        $this->db->query("DELETE ad_insights_actions, ad_insights FROM ad_insights_actions
	                        JOIN ad_insights ON ad_insights_actions.ad_insights_id = ad_insights.ad_insights_id
                            WHERE ad_insights.bydate is NULL AND ad_insights.account_id = '" . $id . "';");

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 
        
        $this->db->where('account_id', $id);
        $this->db->delete('ads');

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        $this->db->query("DELETE adset_insights_actions, adset_insights FROM adset_insights_actions
	                        JOIN adset_insights ON adset_insights_actions.adset_insights_id = adset_insights.adset_insights_id
                            WHERE adset_insights.bydate is NULL AND adset_insights.account_id = '" . $id . "';");

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        $this->db->query("DELETE adsets, adset_targeting FROM adsets
	                        JOIN adset_targeting ON adsets.id = adset_targeting.adset_id
                            WHERE adsets.account_id = '" . $id . "';");

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        $this->db->query("DELETE campaign_insights_actions, campaign_insights FROM campaign_insights_actions
	                        JOIN campaign_insights ON campaign_insights_actions.campaign_insights_id = campaign_insights.campaign_insights_id
                            WHERE campaign_insights.bydate is NULL AND campaign_insights.account_id = '" . $id . "';");

        log_message('debug', 'Last Query: ' . $this->db->last_query());  

        $this->db->where('account_id', $id);
        $this->db->delete('campaigns');

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        $this->db->where('account_id', $id);
        $this->db->delete('account_insights_actions');

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        $this->db->where('account_id', $id);
        $this->db->delete('account_insights');

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        $this->db->where('account_id', $id);
        $this->db->delete('account_custom_conversion');

        log_message('debug', 'Last Query: ' . $this->db->last_query());   

        $this->db->where('id', $id);
        $this->db->delete('accounts');

        log_message('debug', 'Last Query: ' . $this->db->last_query());    
    }
}