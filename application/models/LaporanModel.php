<?php if (!defined('BASEPATH')) exit('No direct script allowed');

class LaporanModel extends CI_Model
{
    function getDataRekap($cari)
    {
        $this->db->select('user_presensi.id_user,user.nip, DAY(tanggal) AS day,user.bagian_id,user.upt_id,
        MIN(IF(jenis_presensi = "masuk", waktu, NULL)) AS waktu_presensi_masuk
        , MAX(IF(jenis_presensi = "pulang", waktu, NULL)) AS waktu_presensi_pulang
        , MIN(IF(jenis_presensi = "masuk", (CASE WHEN set_waktu_presensi_id IS NULL OR set_waktu_presensi_id="1" OR set_waktu_presensi_id="2" THEN true END), NULL)) AS fwamasuk
        , MAX(IF(jenis_presensi = "pulang", (CASE WHEN set_waktu_presensi_id IS NULL OR set_waktu_presensi_id="1" OR set_waktu_presensi_id="2" THEN true END), NULL)) AS fwapulang
        , MIN(IF(jenis_presensi = "masuk", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_masuk
        , MAX(IF(jenis_presensi = "pulang", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_pulang');
        $this->db->from('user_presensi');
        $this->db->join('user', 'user.id_user=user_presensi.id_user');
        $this->db->where($cari);
        $this->db->group_by('tanggal, user.id_user');
        $this->db->order_by('nip', 'asc');
        $getsub = $this->db->get_compiled_select();
        $this->db->select('id_user,nip, day,bagian_id,fwamasuk,fwapulang,
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
						WHEN (waktu_presensi_masuk > batas_waktu_presensi_masuk AND (fwamasuk = true OR fwapulang = true) AND (waktu_presensi_pulang - batas_waktu_presensi_pulang) - (waktu_presensi_masuk - batas_waktu_presensi_masuk) > 0)
								THEN "tl_f"
						WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk 
								THEN "tl"
						WHEN (waktu_presensi_pulang < batas_waktu_presensi_pulang AND (fwamasuk = true OR fwapulang = true) AND (waktu_presensi_pulang - batas_waktu_presensi_pulang) - (waktu_presensi_masuk - batas_waktu_presensi_masuk) > 0)
								THEN "psw_f"
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
        $this->db->select('mb.nama as unit_kerja,pp.id_user,user.nama,user.nip,tgl_mulai,tgl_selesai,mi.kode,mi.deskripsi as status,user.bagian_id,user.upt_id');
        $this->db->from('perizinan');
        $this->db->join('perizinan_petugas as pp', 'perizinan.id=pp.perizinan_id');
        $this->db->join('master_izin as mi', 'perizinan.jenis_izin=mi.id');
        $this->db->join('user', 'pp.id_user=user.id_user', 'left');
        $this->db->join('master_bagian as mb', 'user.bagian_id=mb.id');
        $this->db->where($cari);
        $this->db->group_by('tgl_mulai, pp.id_user');
        $this->db->order_by('nip', 'asc');
        return $this->db->get()->result_array();
    }
    
    function getDataLaporan($cari) {
        $this->db->query('SET SESSION sql_mode = ""');
        $this->db->select('user_presensi.*,user.nama,user.nip,mb.nama as unit_kerja, MIN(IF(jenis_presensi = "masuk", waktu, NULL)) AS waktu_presensi_masuk 
							, MAX(IF(jenis_presensi = "pulang", waktu, NULL)) AS waktu_presensi_pulang
							, MIN(IF(jenis_presensi = "masuk", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_masuk
							, MAX(IF(jenis_presensi = "pulang", batas_waktu_presensi, NULL)) AS batas_waktu_presensi_pulang
							, MIN(IF(jenis_presensi = "masuk", cekwf, NULL)) AS jenis_absen_masuk
							, MIN(IF(jenis_presensi = "masuk", (CASE WHEN set_waktu_presensi_id IS NULL OR set_waktu_presensi_id="1" OR set_waktu_presensi_id="2" THEN true END), NULL)) AS fwamasuk
        					, MAX(IF(jenis_presensi = "pulang", (CASE WHEN set_waktu_presensi_id IS NULL OR set_waktu_presensi_id="1" OR set_waktu_presensi_id="2" THEN true END), NULL)) AS fwapulang
							, MIN(IF(jenis_presensi = "pulang", cekwf, NULL)) AS jenis_absen_pulang');
        $this->db->from('user_presensi');
        $this->db->join('user', 'user.id_user=user_presensi.id_user');
        $this->db->join('master_bagian as mb', 'user.bagian_id=mb.id');
        $this->db->where($cari);
        $this->db->group_by('tanggal, id_user');
        // $this->db->order_by('unit_kerja,user.nip,tanggal', 'asc');
        $getsub = $this->db->get_compiled_select();
        $this->db->select('unit_kerja,nama, nip, tanggal, waktu_presensi_masuk, batas_waktu_presensi_masuk,
						waktu_presensi_pulang, batas_waktu_presensi_pulang,
						CASE
							WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk
									THEN TIMESTAMPDIFF(MINUTE,batas_waktu_presensi_masuk,waktu_presensi_masuk)
							ELSE ""
						END as terlambat,
						CASE
							WHEN waktu_presensi_pulang < batas_waktu_presensi_pulang
									THEN TIMESTAMPDIFF(MINUTE,waktu_presensi_pulang,batas_waktu_presensi_pulang)
							ELSE ""
						END as plg_sebelum,
						TIMESTAMPDIFF(MINUTE,waktu_presensi_masuk,waktu_presensi_pulang) as jumlah_jam,
						UPPER(jenis_absen_masuk) as jenis_absen_masuk,
						UPPER(jenis_absen_pulang) as jenis_absen_pulang,
						CASE
							WHEN ( (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") AND (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00") )
									THEN "Tidak absen"
							WHEN (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00") AND waktu_presensi_masuk < batas_waktu_presensi_masuk
									THEN "Tidak absen pulang"
							WHEN (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") AND waktu_presensi_pulang > batas_waktu_presensi_pulang
									THEN "Tidak absen masuk"
							WHEN (waktu_presensi_masuk IS NULL OR waktu_presensi_masuk = "" OR waktu_presensi_masuk = "00:00:00") AND waktu_presensi_pulang < batas_waktu_presensi_pulang
									THEN "Tidak absen masuk dan pulang awal"
							WHEN (waktu_presensi_pulang IS NULL OR waktu_presensi_pulang = "" OR waktu_presensi_pulang = "00:00:00") AND waktu_presensi_masuk > batas_waktu_presensi_masuk
									THEN "Terlambat masuk dan tidak absen pulang"
							WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk 
									AND waktu_presensi_pulang < batas_waktu_presensi_pulang
									THEN "Terlambat masuk dan pulang sebelum waktunya"
							WHEN (waktu_presensi_masuk > batas_waktu_presensi_masuk AND (fwamasuk = true OR fwapulang = true) AND (waktu_presensi_pulang - batas_waktu_presensi_pulang) - (waktu_presensi_masuk - batas_waktu_presensi_masuk) > 0)
									THEN "Terlambat masuk (FWA)"
							WHEN waktu_presensi_masuk > batas_waktu_presensi_masuk 
									THEN "Terlambat masuk"
							WHEN (waktu_presensi_pulang < batas_waktu_presensi_pulang AND (fwamasuk = true OR fwapulang = true) AND (waktu_presensi_pulang - batas_waktu_presensi_pulang) - (waktu_presensi_masuk - batas_waktu_presensi_masuk) > 0)
									THEN "Pulang sebelum waktunya (FWA)"
							WHEN waktu_presensi_pulang < batas_waktu_presensi_pulang 
									THEN "Pulang sebelum waktunya"
							ELSE "Tepat waktu"
						END AS status');
        $this->db->from('(' . $getsub . ') as tabel');
        // $this->db->order_by('tanggal, id_user', 'asc');
        return $this->db->get()->result_array();
    }
}
