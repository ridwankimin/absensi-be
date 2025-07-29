<?php if (!defined('BASEPATH')) exit('No direct script allowed');

class M_users extends CI_Model
{

	function getdatauser($q)
	{
		return $this->db->get_where('user', $q)->result_array();
	}

	function getSettingWaktu($q)
	{
		return $this->db->get_where('setting_waktu_presensi', $q)->result_array();
	}

	function getdatauserAll()
	{
		$this->db->select('id,username,nama,roles,status,lastLogin,created');
		return $this->db->get('user')->result_array();
	}

	function getUserRole($id)
	{
		$this->db->select('role.*');
		$this->db->from('user_role');
		$this->db->join('role', 'role.id_role=user_role.id_role');
		$this->db->where('id_user', $id);
		return $this->db->get()->result_array();
	}

	function updateUser($update, $where)
	{
		$this->db->where($where);
		$this->db->update('user', $update);
		return $this->db->affected_rows();
	}

	function getLokasiKantor($lokasi)
	{
		$lokasiArr = explode(",", $lokasi);
		$this->db->where('status', '1');
		if (count($lokasiArr) == 0) {
			return [];
		} else if (count($lokasiArr) == 1) {
			$this->db->where('id', $lokasiArr[0]);
		} else {
			$this->db->group_start(); // Open bracket
			$x = 0;
			foreach ($lokasiArr as $lok) {
				if ($x == 0) {
					$this->db->where('id', $lok);
				} else {
					$this->db->or_where('id', $lok);
				}
				$x++;
			}
			$this->db->group_end(); // Close bracket
		}
		return $this->db->get('lokasi_kantor')->result_array();
	}

	function insertUser($insert)
	{
		$this->db->insert('user', $insert);
		return $this->db->affected_rows();
	}

	public function get_by_nip($nip)
	{
		return $this->db->get_where('user', ['nip' => $nip])->row();
	}

	public function update($where, $data)
	{
		return $this->db->where($where)->update('user', $data);
	}
}
