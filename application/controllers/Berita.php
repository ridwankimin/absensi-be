<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

// ini_set('memory_limit', '2048M');
// ini_set('max_execution_time', '259200');

require APPPATH . 'libraries/RestController.php';
require APPPATH . 'libraries/Format.php';

class Berita extends RestController
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
        $this->load->model('BeritaModel', 'berber');
    }

    function index_get() {
        $radius = $this->present->getSetting('radius_nilai');
        $satuanRadius = $this->present->getSetting('radius_satuan');
        if ($radius) {
            $sett = array(
                "radius_nilai" => $radius[0]['value'],
                "radius_satuan" => $satuanRadius[0]['value']
            );
            $this->response([
                'status' => TRUE,
                'message' => 'Berhasil mendapatkan setting presensi',
                'data' => $sett
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Gagal mendapatkan setting presensi'
            ], RESTController::HTTP_INTERNAL_ERROR);
        }
    }
}