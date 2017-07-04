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
}