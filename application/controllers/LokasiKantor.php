<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

// ini_set('memory_limit', '2048M');
// ini_set('max_execution_time', '259200');

require APPPATH . 'libraries/RestController.php';
require APPPATH . 'libraries/Format.php';

class LokasiKantor extends RestController
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
        $this->load->model('LokasiKantorModel', 'lokasi');
        $this->load->helper('security');
    }

    public function index_get()
    {
        $this->form_validation->set_data($this->get());
        $this->form_validation->set_rules('id', 'ID', 'required|max_length[12]|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $cari = array(
            'id' => $this->get('id')
        );
        $data = $this->lokasi->getLokasi($cari);
        if($data) {
            $this->response([
                'status' => true,
                'message' => 'Data ditemukan',
                'data' => $data[0]
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => true,
                'message' => 'Data tidak ditemukan'
            ], RESTController::HTTP_NOT_FOUND);
        }
    }
    public function index_post()
    {
        $this->form_validation->set_data($this->post());
        $this->form_validation->set_rules('nama_lokasi', 'nama lokasi', 'required|max_length[100]|xss_clean');
        $this->form_validation->set_rules('upt_id', 'upt', 'required|max_length[4]|xss_clean');
        $this->form_validation->set_rules('alamat', 'alamat', 'max_length[1000]|xss_clean');
        $this->form_validation->set_rules('lat', 'latitude', 'required|max_length[50]|xss_clean');
        $this->form_validation->set_rules('long', 'longitude', 'required|max_length[50]|xss_clean');
        $this->form_validation->set_rules('user_id', 'user', 'required|max_length[12]|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $cari = array(
            'nama_lokasi' => $this->post('nama_lokasi'),
            'upt_id' => $this->post('upt_id')
        );
        $ceknama = $this->lokasi->getLokasi($cari);
        if(count($ceknama) > 0) {
            $this->response([
                'status' => FALSE,
                'message' => 'Nama lokasi ' . $this->post('nama_lokasi') . ' sudah ada'
            ], RESTController::HTTP_NOT_ACCEPTABLE);
        } else {
            $nolast = $this->lokasi->getIdLast($this->post('upt_id'));
            $id = substr($this->post('upt_id'), 0, 2) . str_pad(intval(($nolast ? $nolast->nolast : 0)) + 1, 3, "0", STR_PAD_LEFT);
            $simpan = array(
                'id' => $id,
                'nama_lokasi' => $this->post('nama_lokasi'),
                'alamat' => $this->post('alamat'),
                'upt_id' => $this->post('upt_id'),
                'lat' => $this->post('lat'),
                'long' => $this->post('long'),
                'status' => '1',
                'created_at' => date('Y-m-d H:i:s'),
                'user_id' => $this->post('user_id'),
            );
            $dbsimpan = $this->lokasi->simpanLokasi($simpan);
            if($dbsimpan['status']) {
                $this->response([
                    'status' => true,
                    'message' => 'Data lokasi berhasil diinput',
                    'data' => $simpan
                ], RESTController::HTTP_CREATED);
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => $dbsimpan['message'] ?? 'Data lokasi gagal diinput',
                ], RESTController::HTTP_INTERNAL_ERROR);
            }
        }
    }
    
    public function index_put()
    {
        $this->form_validation->set_data($this->put());
        $this->form_validation->set_rules('id', 'ID', 'required|max_length[20]|xss_clean');
        $this->form_validation->set_rules('nama_lokasi', 'nama lokasi', 'required|max_length[100]|xss_clean');
        $this->form_validation->set_rules('upt_id', 'upt', 'required|max_length[4]|xss_clean');
        $this->form_validation->set_rules('alamat', 'alamat', 'max_length[1000]|xss_clean');
        $this->form_validation->set_rules('lat', 'latitude', 'required|max_length[50]|xss_clean');
        $this->form_validation->set_rules('long', 'longitude', 'required|max_length[50]|xss_clean');
        $this->form_validation->set_rules('user_id', 'user', 'required|max_length[12]|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $simpan = array(
            'nama_lokasi' => $this->put('nama_lokasi'),
            'alamat' => $this->put('alamat'),
            'upt_id' => $this->put('upt_id'),
            'lat' => $this->put('lat'),
            'long' => $this->put('long'),
            'status' => '1',
            'creataed_at' => date('Y-m-d H:i:s'),
            'user_id' => $this->put('user_id'),
        );
        $dbsimpan = $this->lokasi->updateLokasi($simpan, $this->put('id'));
        if($dbsimpan['status']) {
            $this->response([
                'status' => true,
                'message' => 'Data lokasi berhasil diupdate',
                'data' => $this->put()
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => $dbsimpan['message'] ?? 'Data lokasi gagal diinput',
            ], RESTController::HTTP_INTERNAL_ERROR);
        }
    }

    public function index_delete()
    {
        $this->form_validation->set_data($this->delete());
        $this->form_validation->set_rules('id', 'ID', 'required|max_length[20]|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $delete = $this->lokasi->deleteLokasi($this->delete('id'));
        if($delete['status']) {
            $this->response([
                'status' => TRUE,
                'message' => 'Lokasi berhasil dihapus'
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => $delete['message'] ?? 'Lokasi gagal dihapus'
            ], RESTController::HTTP_OK);
        }
    }
}