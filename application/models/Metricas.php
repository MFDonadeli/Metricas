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
        $this->db->where('status', 'ACTIVE');

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
        $this->db->order_by('bydate');
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