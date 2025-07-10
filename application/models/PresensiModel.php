<?php if(!defined('BASEPATH')) exit('No direct script allowed');

class PresensiModel extends CI_Model{

	function getSetting($cari) {
		return $this->db->get_where('setting', array('param' => $cari))->result_array();
	}
	function getSettingWaktu($cari) {
		return $this->db->get_where('setting_waktu_presensi', $cari)->result_array();
	}
    function getDataLapWFAUser($cari) {
        return $this->db->get_where('lap_wfa', $cari)->result_array();
    }
	function simpanAbsen($simpanAbsen) {
		$this->db->insert('user_presensi', $simpanAbsen);
		if($this->db->affected_rows() > 0) {
			return array("status" => true);
		} else {
			return array("status" => false, "message" => $this->db->error());
		}
	}
}