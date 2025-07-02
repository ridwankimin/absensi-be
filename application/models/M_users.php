<?php if(!defined('BASEPATH')) exit('No direct script allowed');

class M_users extends CI_Model{

	function getdatauser($q) {
		return $this->db->get_where('user', $q)->result_array();
	}
	
	function getdatauserAll() {
		$this->db->select('id,username,nama,roles,status,lastLogin,created');
		return $this->db->get('users')->result_array();
	}
	
	function updateUser($update, $where) {
		$this->db->where($where);
		$this->db->update('users',$update);
		return $this->db->affected_rows();
	}	
	
	function insertUser($insert) {
		$this->db->insert('users', $insert);
		return $this->db->affected_rows();
	}	
}