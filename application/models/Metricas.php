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
            $arr_insights_action = $arr_insights['action'];
            unset($arr_account['insights']);
            unset($arr_insights['action']);
        }

        if(!$this->db->insert('accounts', $arr_account))
            log_message('debug', 'Erro: ' . $this->db->error()->message);

        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if(isset($arr_insights))
        {
            if(!$this->db->insert('accounts_insights',$arr_insights))
                log_message('debug', 'Erro: ' . $this->db->error()->message);
            log_message('error', 'Last Query: ' . $this->db->last_query());

            foreach($arr_insights_action as $action)
            {
                if(!$this->db->insert('accounts_insights_actions', $action))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());
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
                $arr_insights_action = $arr_insights['action'];
                unset($array['insights']);
                unset($arr_insights['action']);
            }

            if(!$this->db->insert('campaigns', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);
            
            log_message('debug', 'Last Query: ' . $this->db->last_query());

            if(isset($arr_insights))
            {
                if(!$this->db->insert('campaigns_insights',$arr_insights))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);
                
                log_message('debug', 'Last Query: ' . $this->db->last_query());

                foreach($arr_insights_action as $action)
                {
                    if(!$this->db->insert('campaigns_insights_actions', $action))
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
            if(array_key_exists('insights',$array))
            {
                $arr_insights = $array['insights'];
                $arr_insights_action = $arr_insights['action'];
                unset($array['insights']);
                unset($arr_insights['action']);
            }

            if(!$this->db->insert('adsets', $array))
                log_message('debug', 'Erro: ' . $this->db->error()->message);

            log_message('debug', 'Last Query: ' . $this->db->last_query());

            if(isset($arr_insights))
            {
                if(!$this->db->insert('adsets_insights',$arr_insights))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());

                foreach($arr_insights_action as $action)
                {
                    if(!$this->db->insert('adsets_insights_actions', $action))
                        log_message('debug', 'Erro: ' . $this->db->error()->message);
                    log_message('debug', 'Last Query: ' . $this->db->last_query());
                }
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
                $arr_insights_action = $arr_insights['action'];
                unset($array['insights']);
                unset($arr_insights['action']);
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

                foreach($arr_insights_action as $action)
                {
                    if(!$this->db->insert('ad_insights_actions', $action))
                        log_message('debug', 'Erro: ' . $this->db->error()->message);

                    log_message('debug', 'Last Query: ' . $this->db->last_query());
                }
            }

            if(isset($arr_creative))
            {
                if(!$this->db->insert('ad_creatives',$arr_creative))
                    log_message('debug', 'Erro: ' . $this->db->error()->message);

                log_message('debug', 'Last Query: ' . $this->db->last_query());
            }
        }
    }
}