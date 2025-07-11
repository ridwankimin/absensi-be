<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

// ini_set('memory_limit', '2048M');
// ini_set('max_execution_time', '259200');

require APPPATH . 'libraries/RestController.php';
require APPPATH . 'libraries/Format.php';

class Presensi extends RestController
{
    function __construct()
    {
        parent::__construct();
        $this->load->library('Authorization_Token');
        $is_valid_token = $this->authorization_token->validateToken();

        if (!$is_valid_token['status']) {
            $this->response([
                'status' => FALSE,
                'message' => $is_valid_token['message']
            ], RESTController::HTTP_UNAUTHORIZED);
            return;
        }
        $this->load->model('PresensiModel', 'present');
        $this->load->model('LokasiKantorModel', 'lokasi');
        $this->load->helper('security');
    }

    public function getDistance($lat1, $long1, $lat2, $long2)
    {
        $theta = $long1 - $long2;
        $distance = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $distance = acos($distance);
        $distance = rad2deg($distance);
        $distance = $distance * 60 * 1.1515;
        $distance = $distance * 1.609344;
        return $distance; //Kilometer
    }

    public function index_post()
    {
        $this->form_validation->set_data($this->post());
        $this->form_validation->set_rules('id_user', 'user', 'required|max_length[12]|xss_clean');
        $this->form_validation->set_rules('zona', 'zona', 'max_length[4]|xss_clean');
        $this->form_validation->set_rules('jenis_presensi', 'jenis presensi', 'required|max_length[10]|xss_clean');
        $this->form_validation->set_rules('waktu_presensi_id', 'waktu presensi id', 'required|max_length[10]|xss_clean');
        $this->form_validation->set_rules('latitude', 'latitude', 'required|max_length[50]|xss_clean');
        $this->form_validation->set_rules('longitude', 'longitude', 'required|max_length[50]|xss_clean');
        $this->form_validation->set_rules('raw_lokasi', 'raw lokasi', 'max_length[1000]|xss_clean');
        $this->form_validation->set_rules('lokasi_kantor_id', 'lokasi kantor', 'max_length[100]|xss_clean');
        $this->form_validation->set_rules('bagian_id', 'Bagian', 'max_length[100]|xss_clean');
        $this->form_validation->set_rules('cek_wfo', 'wfo/wfa', 'required|max_length[3]|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }

        $zona = 'WIB';
        if ($this->post('zona')) {
            $zona = $this->post('zona');
            if ($zona == "WIB") {
                date_default_timezone_set('Asia/Jakarta');
            } else if ($zona == "WITA") {
                date_default_timezone_set('Asia/Makassar');
            } else if ($zona == "WIT") {
                date_default_timezone_set('Asia/Jayapura');
            }
        }
        if ($this->post('cek_wfo') == 'wfo') {
            if (!$this->post('lokasi_kantor_id')) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Anda belum melakukan setting lokasi kantor'
                ], RESTController::HTTP_BAD_REQUEST);
                return;
            }
            $carilokasi = array(
                'id' => $this->post('lokasi_kantor_id')
            );
            $datalokasi = $this->lokasi->getLokasi($carilokasi);
            if (count($datalokasi) == 0) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Lokasi kantor tidak ditemukan'
                ], RESTController::HTTP_BAD_REQUEST);
                return;
            }
            $dist = $this->getDistance($datalokasi[0]['lat'], $datalokasi[0]['long'], $this->post('latitude'), $this->post('longitude'));
            $radius = $this->present->getSetting('radius_nilai');
            $satuanRadius = $this->present->getSetting('radius_satuan');
            $radius = $radius[0]['value'];
            $satuanRadius = $satuanRadius[0]['value'];
            if ($satuanRadius == 'km') {
                $radius = $radius * 1000;
            }
            $dist = $dist * 1000;
            if ($radius < $dist) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Lokasi Anda diluar radius lokasi WFO yang diperbolehkan'
                ], RESTController::HTTP_BAD_REQUEST);
                return;
            }
        }
        if ($this->post('shifting') == 'Y') {
            $cariwaktu = array(
                'id_user_presensi' => $this->post('waktu_presensi_id')
            );
        } else {
            $cariwaktu = array(
                'jenis' => 'office',
                'gunakan' => 'Y'
            );
        }
        $settingwaktu = $this->present->getSettingWaktu($cariwaktu);
        if (count($settingwaktu) == 0) {
            $this->response([
                'status' => FALSE,
                'message' => 'Setting waktu tidak ditemukan'
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $tanggal = date('Y-m-d');
        if ($settingwaktu[0]['hari_pulang'] == 'hari_berikutnya') {
            $tanggal = date("Y-m-d", strtotime('-1 day', $tanggal));;
            // $tanggal = strtotime('-1 day', $tanggal);
        }
        if ($this->post('cek_wfo') == 'wfa' && $this->post('jenis_presensi') == 'pulang') {
            $carilap = array(
                'user_id' => $this->post('id_user'),
                'tanggal_lap' => $tanggal
            );
            $datalap = $this->present->getDataLapWFAUser($carilap);
            if (count($datalap) == 0) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Mohon isi laporan WFA terlebih dahulu'
                ], RESTController::HTTP_BAD_REQUEST);
                return;
            }
        }
        $batasPresensi = "";
        $waktu = date('H:i:s');
        if ($this->post('jenis_presensi') == 'masuk') {
            $batasPresensi = $settingwaktu[0]['batas_waktu_masuk'];
            if ($waktu < $settingwaktu[0]['waktu_masuk_awal'] || $waktu > $settingwaktu[0]['waktu_masuk_akhir']) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Waktu presensi masuk mulai pukul ' . $settingwaktu[0]['waktu_masuk_awal'] . ' hingga pukul ' . $settingwaktu[0]['waktu_masuk_akhir']
                ], RESTController::HTTP_BAD_REQUEST);
                return;
            }
        }
        if ($this->post('jenis_presensi') == 'pulang') {
            $batasPresensi = $settingwaktu[0]['batas_waktu_pulang'];
            if (date('D') == 'Fri' && $settingwaktu[0]['jenis'] == 'office') {
                $batasPresensi = date("H:i:s", strtotime('+30 minutes', strtotime($batasPresensi)));
            }
            if ($waktu < $settingwaktu[0]['waktu_pulang_awal'] || $waktu > $settingwaktu[0]['waktu_pulang_akhir']) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Waktu presensi pulang mulai pukul ' . $settingwaktu['waktu_pulang_awal'] . ' hingga pukul ' . $settingwaktu['waktu_pulang_akhir']
                ], RESTController::HTTP_BAD_REQUEST);
                return;
            }
        }
        $simpanAbsen = array(
            'id_user' => $this->post('id_user'),
            'tanggal' => $tanggal,
            'tanggal_real' => date('Y-m-d'),
            'waktu' => $waktu,
            'zona' => $zona,
            'jenis_presensi' => $this->post('jenis_presensi'),
            'batas_waktu_presensi' => $batasPresensi,
            'set_waktu_presensi_id' => $this->post('waktu_presensi_id'),
            'latitude' => $this->post('longitude'),
            'longitude' => $this->post('longitude'),
            'raw_lokasi' => $this->post('raw_lokasi'),
            'lokasi_kantor_id' => $this->post('lokasi_kantor_id'),
            'bagian_id' => $this->post('bagian_id'),
            'cek_wfo' => $this->post('cek_wfo'),
        );
        $simpan = $this->present->simpanAbsen($simpanAbsen);
        if($simpan['status']) {
            $this->response([
                'status' => TRUE,
                'message' => 'Absen ' . $this->post('jenis_presensi') . ' tanggal ' . $tanggal . ' berhasil disimpan. Pukul ' . $waktu . ' ' . $zona
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => $simpan['message'] ?? 'Absen gagal disimpan'
            ], RESTController::HTTP_BAD_REQUEST);
        }
    }
}
