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
    public function getContas($id){
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
                if(!$this->db->insert('campaigns_insights',$arr_insights))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);
                
                log_message('debug', 'Last Query: ' . $this->db->last_query());

                $insert_id = $this->db->insert_id();

                if(isset($arr_insights_action))
                {
                    $action['campaigns_insights_id'] = $insert_id;
                    foreach($arr_insights_action as $action)
                    {
                        if(!$this->db->insert('campaigns_insights_actions', $action))
                            log_message('debug', 'Erro: ' . $this->db->error()->message);
                        log_message('debug', 'Last Query: ' . $this->db->last_query());
                    }
                    unset($arr_insights_action);
                }
                unset($arr_insights);
            }
        }
        
    }

    public function insertInsights($arr_insights)
    {
        foreach($arr_insights as $array)
        {
            $arr_action = $array['action'];
            unset($array['action']);

            $array['bydate'] = 1;

            if(!$this->db->insert('ad_insights',$array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);
            
            log_message('debug', 'Last Query: ' . $this->db->last_query());
            
            $insert_id = $this->db->insert_id();

            foreach($arr_action as $action)
            {
                $action['ad_insights_id'] = $insert_id;
                if(!$this->db->insert('ad_insights_actions', $action))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);
                log_message('debug', 'Last Query: ' . $this->db->last_query());
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
                if(!$this->db->insert('adsets_targeting',$arr_targeting))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());  
                unset($arr_targeting);  
            }

            if(isset($arr_insights))
            {
                if(!$this->db->insert('adsets_insights',$arr_insights))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());

                $insert_id = $this->db->insert_id();

                if(isset($arr_insights_action))
                {
                    foreach($arr_insights_action as $action)
                    {
                        $action['adsets_insights_id'] = $insert_id;
                        if(!$this->db->insert('adsets_insights_actions', $action))
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

    public function getLastDateSyncAd($id)
    {
        log_message('debug', 'getLastDateSyncAd');

        $this->db->select('date_start, ad_insights_id');
        $this->db->from('ad_insights');
        $this->db->where('ad_id', $id);
        $this->db->where('bydate = 1');
        $this->db->order_by('ad_insights_id', 'desc');
        $this->db->limit(1);
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if($result->num_rows() > 0)
        {
            $row = $result->row();

            $this->db->where('ad_insights_id', $row->ad_insights_id);
            $this->db->delete('ad_insights_actions');

            $this->db->where('ad_insights_id', $row->ad_insights_id);
            $this->db->delete('ad_insights');

            return explode(' ', $row->date_start)[0];    
        }

        //Validate
        $this->db->where('id',$id);
        $result = $this->db->get('ads');

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        $row = $result->row();

        return explode('T', $row->created_time)[0];
    }

    public function getTableData($id)
    {
        log_message('debug', 'getTableData. Id:' . $id);
        
        $this->db->select('date_start, cost_per_inline_link_click, inline_link_click_ctr, inline_link_clicks,impressions, cpm, relevance_score_score, spend, bydate, ad_insights_id');
        $this->db->from('ad_insights');
        $this->db->where('ad_id', $id);
        $this->db->order_by('bydate');
        $this->db->order_by('date_start');
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->result();

    }

    public function getTableDataActions($id)
    {
        log_message('debug', 'getTableDataActions. Id:' . $id);
        
        $this->db->select('action_type, value, cost, ad_insights_id');
        $this->db->from('ad_insights_actions');
        $this->db->like('action_type', 'offsite_conversion.', 'after');
        $this->db->where('ad_insights_id', $id);
        
        $result = $this->db->get();

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        return $result->result();
    }

    public function getPossibleConversions($id)
    {
        log_message('debug', 'getPossibleConversions. Id:' . $id);
        
        $this->db->distinct();
        $this->db->select('action_type');
        $this->db->from('ad_insights_actions');
        $this->db->like('action_type', 'offsite_conversion.', 'after');
        $this->db->where('ad_id', $id);
        
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

        $this->db->query("DELETE adsets_insights_actions, adsets_insights FROM adsets_insights_actions
	                        JOIN adsets_insights ON adsets_insights_actions.adsets_insights_id = adsets_insights.adsets_insights_id
                            WHERE adsets_insights.bydate is NULL AND adsets_insights.account_id = '" . $id . "';");

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        $this->db->query("DELETE adsets, adsets_targeting FROM adsets
	                        JOIN adsets_targeting ON adsets.id = adsets_targeting.adset_id
                            WHERE adsets.account_id = '" . $id . "';");

        log_message('debug', 'Last Query: ' . $this->db->last_query()); 

        $this->db->query("DELETE campaigns_insights_actions, campaigns_insights FROM campaigns_insights_actions
	                        JOIN campaigns_insights ON campaigns_insights_actions.campaigns_insights_id = campaigns_insights.campaigns_insights_id
                            WHERE campaigns_insights.bydate is NULL AND campaigns_insights.account_id = '" . $id . "';");

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