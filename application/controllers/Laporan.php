<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

use function PHPSTORM_META\map;

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '259200');

require APPPATH . 'libraries/RestController.php';
require APPPATH . 'libraries/Format.php';

class Laporan extends RestController
{
    function __construct()
    {
        parent::__construct();
        // $this->load->library('Authorization_Token');
        // $is_valid_token = $this->authorization_token->validateToken();

        // if (!$is_valid_token['status']) {
        //     $this->response([
        //         'status' => FALSE,
        //         'message' => $is_valid_token['message']
        //     ], RESTController::HTTP_UNAUTHORIZED);
        //     return;
        // }
        $this->load->model('LaporanModel', 'lap');
        $this->load->helper('security');
    }

    public function index_post() {
        $this->form_validation->set_data($this->post());
        $this->form_validation->set_rules('upt', 'upt', 'required|max_length[4]|xss_clean');
        $this->form_validation->set_rules('bagian', 'bagian', 'required|max_length[10]|xss_clean');
        $this->form_validation->set_rules('pegawai', 'pegawai', 'required|max_length[10]|xss_clean');
        $this->form_validation->set_rules('bulan', 'bulan', 'required|max_length[2]|xss_clean');
        $this->form_validation->set_rules('tahun', 'tahun', 'required|max_length[4]|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $cari = array(
            'MONTH(tanggal)=' => $this->post('bulan'),
            'YEAR(tanggal)=' => $this->post('tahun'),
        );
        $cariizin = array(
            'MONTH(tgl_mulai)=' => $this->post('bulan'),
            'YEAR(tgl_mulai)=' => $this->post('tahun'),
        );
        if ($this->post('upt') != 'all') {
            $cari['user.upt_id'] = $this->post('upt');
            $cariizin['user.upt_id'] = $this->post('upt');
        }
        if ($this->post('bagian') != 'all') {
            if ($this->post('role') == 'adm-tu') {
                $cari['LEFT(user.bagian_id,1)='] = substr($this->post('bagian'), 0, 1);
                $cariizin['LEFT(user.bagian_id,1)='] = substr($this->post('bagian'), 0, 1);
            } else {
                $cari['user.bagian_id'] = $this->post('bagian');
                $cariizin['user.bagian_id'] = $this->post('bagian');
            }
        }
        if ($this->post('pegawai') != 'all') {
            $cari['user_presensi.id_user'] = $this->post('pegawai');
            $cariizin['pp.id_user'] = $this->post('pegawai');
        }
        $result = $this->lap->getDataLaporan($cari);
        $izin = $this->lap->getDataPerizinan($cariizin);
        foreach ($izin as $iz) {
            $begin = new \DateTime($iz['tgl_mulai']);
            $end   = new \DateTime($iz['tgl_selesai']);
            for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
                $result[] = array(
                    "unit_kerja" => $iz['unit_kerja'],
                    "nama" => $iz['nama'],
                    "nip" => $iz['nip'],
                    "tanggal" => $i->format("Y-m-d"),
                    "waktu_presensi_masuk" => "",
                    "batas_waktu_presensi_masuk" => "",
                    "waktu_presensi_pulang" => "",
                    "batas_waktu_presensi_pulang" => "",
                    "terlambat" => "",
                    "plg_sebelum" => "",
                    "jumlah_jam" => "",
                    "jenis_absen_masuk" => "",
                    "status" => $iz['status'],
                );
            }
        }
        foreach ($result as $key => $row) {
            $unit_kerja[$key] = $row['unit_kerja'];
            $nip[$key] = $row['nip'];
            $tanggal[$key] = $row['tanggal'];
        }
        array_multisort($unit_kerja, SORT_ASC, $nip, SORT_ASC, $tanggal, SORT_ASC, $result);
        if ($result) {
            $this->response([
                'status' => TRUE,
                'message' => 'Rekap laporan absen ditemukan',
                'data' => $result
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Rekap laporan absen tidak ditemukan',
            ], RESTController::HTTP_NOT_FOUND);
        }
    }

    public function rekap_post() {
        $this->form_validation->set_data($this->post());
        $this->form_validation->set_rules('upt', 'upt', 'required|max_length[4]|xss_clean');
        $this->form_validation->set_rules('bagian', 'bagian', 'required|max_length[10]|xss_clean');
        $this->form_validation->set_rules('pegawai', 'pegawai', 'required|max_length[10]|xss_clean');
        $this->form_validation->set_rules('bulan', 'bulan', 'required|max_length[2]|xss_clean');
        $this->form_validation->set_rules('tahun', 'tahun', 'required|max_length[4]|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        
        $cari = array(
            'MONTH(tanggal)=' => $this->post('bulan'),
            'YEAR(tanggal)=' => $this->post('tahun'),
        );
        $cariizin = array(
            'MONTH(tgl_mulai)=' => $this->post('bulan'),
            'YEAR(tgl_mulai)=' => $this->post('tahun'),
        );
        if($this->post('upt') != 'all') {
            $cari['user.upt_id'] = $this->post('upt');
            $cariizin['user.upt_id'] = $this->post('upt');
        }
        if($this->post('bagian') != 'all') {
            if($this->post('role') == 'adm-tu') {
                $cari['LEFT(user.bagian_id,1)='] = substr($this->post('bagian'), 0, 1);
                $cariizin['LEFT(user.bagian_id,1)='] = substr($this->post('bagian'), 0, 1);
            } else {
                $cari['user.bagian_id'] = $this->post('bagian');
                $cariizin['user.bagian_id'] = $this->post('bagian');
            }
        }
        if($this->post('pegawai') != 'all') {
            $cari['user_presensi.id_user'] = $this->post('pegawai');
            $cariizin['pp.id_user'] = $this->post('pegawai');
        }
        $result = $this->lap->getDataRekap($cari);
        $izin = $this->lap->getDataPerizinan($cariizin);
        foreach ($result as $val) {
            $presensi[$val['nip']][$val['day']] = $val['status'];
        }
        foreach ($izin as $iz) {
            $begin = new DateTime($iz['tgl_mulai']);
            $end   = new DateTime($iz['tgl_selesai']);
            for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
                $presensi[$iz['nip']][intval($i->format("d"))] = $iz['kode'];
                // $iz['day'] = $i->format("m");
                // $result['id_user'] = $iz['id_user'];
                // $result['day'] = $i->format("m");
                // $result['nip'] = $iz['nip'];
                // $result['bagian_id'] = $iz['bagian_id'];
                // $result['status'] = $iz['kode'];
                // $restIzin[] = $iz;
            }
        }
        if($presensi) {
            ksort($presensi);
            $this->response([
                'status' => TRUE,
                'message' => 'Rekap data absen ditemukan',
                // 'data' => array('absen' => $result, 'izin' => $restIzin)
                'data' => $presensi
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Rekap data absen tidak ditemukan',
            ], RESTController::HTTP_NOT_FOUND);
        }
    }
}