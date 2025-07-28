<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Absen extends CI_Controller {

    public function __construct() {
        parent::__construct();
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        $this->load->database();
    }

    public function getRiwayat() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        $user_id = $this->input->get('user_id');

        if (!$user_id) {
            echo json_encode([
                'success' => false,
                'message' => 'Parameter user_id wajib diisi'
            ]);
            return;
        }

        $this->db->where('id_user', $user_id);
        $this->db->order_by('tanggal', 'DESC');
        $this->db->order_by('waktu', 'ASC');
        $this->db->order_by('cekwf', 'ASC');
        $query = $this->db->get('user_presensi');

        echo json_encode([
            'success' => true,
            'data' => $query->result()
        ]);
    }
}
