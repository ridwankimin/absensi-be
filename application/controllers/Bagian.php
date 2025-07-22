<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

// ini_set('memory_limit', '2048M');
// ini_set('max_execution_time', '259200');

require APPPATH . 'libraries/RestController.php';
require APPPATH . 'libraries/Format.php';

class Bagian extends RestController
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
        $this->load->model('BagianModel', 'bag');
        $this->load->helper('security');
    }

    public function getBy_get()
    {
        $this->form_validation->set_data($this->get());
        $this->form_validation->set_rules('jenis', 'jenis', 'required|in_list[1000,upt]|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $cari = array('upt_id' => $this->get('jenis'));
        $getdata = $this->bag->getDataBagian($cari);
        if (count($getdata) > 0) {
            $this->response([
                'status' => TRUE,
                'message' => 'Data bagian ditemukan',
                'data' => $getdata
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Data bagian tidak ditemukan'
            ], RESTController::HTTP_NOT_FOUND);
        }
    }
}
