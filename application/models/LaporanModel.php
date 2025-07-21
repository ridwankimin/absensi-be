<?php if (!defined('BASEPATH')) exit('No direct script allowed');

class LaporanModel extends CI_Model
{
    function getDataRekap($cari)
    {
        $this->db->select('user_presensi.id_user,user.nip, DAY(tanggal) AS day,user.bagian_id,user.upt_id,
        MIN(IF(jenis_presensi = "masuk", waktu, NULL)) AS waktu_presensi_masuk
        , MAX(IF(jenis_presensi = "pulang", waktu, NULL)) AS waktu_presensi_pulang
        , MIN(IF(jenis_presensi = "masuk", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_masuk
        , MAX(IF(jenis_presensi = "pulang", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_pulang');
        $this->db->from('user_presensi');
        $this->db->join('user', 'user.id_user=user_presensi.id_user');
        $this->db->where($cari);
        $this->db->group_by('tanggal, user.id_user');
        $this->db->order_by('nip', 'asc');
        $getsub = $this->db->get_compiled_select();
        $this->db->select('id_user,nip, day,bagian_id,
					CASE
						WHEN (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") AND waktu_presensi_pulang > batas_waktu_presensi_pulang
								THEN "tam"
						WHEN (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") AND waktu_presensi_pulang < batas_waktu_presensi_pulang
								THEN "tam_psw"
						WHEN waktu_presensi_masuk < batas_waktu_presensi_masuk  AND (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00")
								THEN "tap"
						WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk  AND (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00")
								THEN "tl_tap"
						WHEN (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00")  AND (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00")
								THEN "tam_tap"
						WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk  AND (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00")
								THEN "tl_tap"
						WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk 
								AND waktu_presensi_pulang < batas_waktu_presensi_pulang
								THEN "tl_psw"
						WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk 
								THEN "tl"
						WHEN waktu_presensi_pulang < batas_waktu_presensi_pulang 
								THEN "psw"
						ELSE "tw"
					END AS status');
        $this->db->from('(' . $getsub . ') as tabel');
        $this->db->order_by('nip', 'asc');
        return $this->db->get()->result_array();
    }
    
    function getDataPerizinan($cari) {
        $this->db->query('SET SESSION sql_mode = ""');
        $this->db->select('pp.id_user,user.nip,tgl_mulai,tgl_selesai,mi.kode,mi.deskripsi,user.bagian_id,user.upt_id');
        $this->db->from('perizinan');
        $this->db->join('perizinan_petugas as pp', 'perizinan.id=pp.perizinan_id');
        $this->db->join('master_izin as mi', 'perizinan.jenis_izin=mi.id');
        $this->db->join('user', 'pp.id_user=user.id_user', 'left');
        $this->db->where($cari);
        $this->db->group_by('tgl_mulai, pp.id_user');
        $this->db->order_by('nip', 'asc');
        return $this->db->get()->result_array();
    }
}
