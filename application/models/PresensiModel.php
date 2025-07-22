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
	function getDataTanggal($cari, $data) {
		$this->db->query('SET SESSION sql_mode = ""');
		$this->db->select('tanggal,tanggal_real,id_user,zona,
						MIN(IF(jenis_presensi = "masuk", waktu, null)) AS presensi_masuk,
						MAX(IF(jenis_presensi = "pulang", waktu, null)) AS presensi_pulang,
						MIN(IF(jenis_presensi = "masuk", batas_waktu_presensi, null)) AS batas_presensi_masuk,
						MAX(IF(jenis_presensi = "pulang", batas_waktu_presensi, null)) AS batas_presensi_pulang,
						cekwf,lokasi_kantor_id');
		$this->db->from('user_presensi'); 
		if($data['shifting'] == 'Y') {
			$this->db->where('id_user', $cari['id_user']);
			$this->db->group_start();
			$this->db->where('tanggal', $cari['tanggal']);			
			$this->db->or_where('tanggal', date("Y-m-d", strtotime('-1 day', $cari['tanggal'])));			
			$this->db->group_end();

			if(count($data['shift_id']) == 1) {
				$this->db->where('set_waktu_presensi_id', $data[0]['shift_id']);
			} else {
				$this->db->group_start();
				$this->db->where('set_waktu_presensi_id', $data[0]['shift_id']);
				$x = 0;
				foreach($data['shift_id'] as $shift) {
					if($x != 0) {
						$this->db->or_where('set_waktu_presensi_id', $shift);
					}
					$x++;
				}
				$this->db->group_end();
			}
		} else {
			$this->db->where($cari);
		}
		$this->db->order_by('tanggal', 'desc');
		$this->db->group_by('set_waktu_presensi_id,tanggal');
		return $this->db->get()->result_array();
	}
}