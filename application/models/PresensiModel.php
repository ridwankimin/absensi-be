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
	function getDataTanggal($cari) {
		$this->db->query('SET SESSION sql_mode = ""');
		$this->db->select('tanggal,id_user,zona,
						MIN(IF(jenis_presensi = "masuk", waktu, null)) AS presensi_masuk,
						MAX(IF(jenis_presensi = "pulang", waktu, null)) AS presensi_pulang,
						MIN(IF(jenis_presensi = "masuk", batas_waktu_presensi, null)) AS batas_presensi_masuk,
						MAX(IF(jenis_presensi = "pulang", batas_waktu_presensi, null)) AS batas_presensi_pulang,
						cekwf,lokasi_kantor_id');
		$this->db->from('user_presensi'); 
		$this->db->where($cari);
		$this->db->group_by('set_waktu_presensi_id,tanggal');
		return $this->db->get()->result_array();
	}
}