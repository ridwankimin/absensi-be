<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

// ini_set('memory_limit', '2048M');
// ini_set('max_execution_time', '259200');

require APPPATH . 'libraries/RestController.php';
require APPPATH . 'libraries/Format.php';

class Pegawai extends RestController
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
        $this->load->model('PegawaiModel', 'peg');
        $this->load->helper('security');
    }

    public function getBy_post() {
        $this->form_validation->set_data($this->post());
        $this->form_validation->set_rules('upt', 'upt', 'required|max_length[4]|xss_clean');
        $this->form_validation->set_rules('bagian', 'bagian', 'required|max_length[10]|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $cari = array('verified' => '1');
        if($this->post('bagian') != 'all') {
            $cari['bagian_id'] = $this->post('bagian');
        }
        if($this->post('upt') != 'all') {
            $cari['upt_id'] = $this->post('upt');
        }
        $getdata = $this->peg->getDataPegawai($cari);
        if(count($getdata) > 0) {
            $this->response([
                'status' => TRUE,
                'message' => 'Data pegawai ditemukan',
                'data' => $getdata
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Data pegawai tidak ditemukan'
            ], RESTController::HTTP_NOT_FOUND);
        }
    }
}