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
        log_message('debug', 'checkUser');

        $this->db->where('facebook_id',$id);
        $result = $this->db->get('accounts');

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

        $this->db->insert('accounts', $arr_account);
        log_message('debug', 'Last Query: ' . $this->db->last_query());

        if(isset($arr_insights))
        {
            $this->db->insert('accounts_insights',$arr_insights);
            log_message('debug', 'Last Query: ' . $this->db->last_query());

            foreach($arr_insights_action as $action)
            {
                $this->db->insert('accounts_insights_actions', $action);
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

            $this->db->insert('campaigns', $array);
            log_message('debug', 'Last Query: ' . $this->db->last_query());

            if(isset($arr_insights))
            {
                $this->db->insert('campaigns_insights',$arr_insights);
                log_message('debug', 'Last Query: ' . $this->db->last_query());

                foreach($arr_insights_action as $action)
                {
                    $this->db->insert('campaigns_insights_actions', $action);
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

            $this->db->insert('adsets', $array);
            log_message('debug', 'Last Query: ' . $this->db->last_query());

            if(isset($arr_insights))
            {
                $this->db->insert('adsets_insights',$arr_insights);
                log_message('debug', 'Last Query: ' . $this->db->last_query());

                foreach($arr_insights_action as $action)
                {
                    $this->db->insert('adsets_insights_actions', $action);
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

            $this->db->insert('ads', $array);
            log_message('debug', 'Last Query: ' . $this->db->last_query());

            if(isset($arr_insights))
            {
                $this->db->insert('ad_insights',$arr_insights);
                log_message('debug', 'Last Query: ' . $this->db->last_query());

                foreach($arr_insights_action as $action)
                {
                    $this->db->insert('ad_insights_actions', $action);
                    log_message('debug', 'Last Query: ' . $this->db->last_query());
                }
            }

            if(isset($arr_creative))
            {
                $this->db->insert('ad_creatives',$arr_creative);
                log_message('debug', 'Last Query: ' . $this->db->last_query());
            }
        }
    }
}