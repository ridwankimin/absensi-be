<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wfa extends CI_Controller {

    public function index() {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        $json = file_get_contents('php://input');
        $input = json_decode($json, true);

        if (!isset($input['judul']) || !isset($input['uraian'])) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        $data = [
            'user_id' => $user_id,
            'judul' => $input['judul'],
            'uraian' => $input['uraian'],
            'tanggal_lap' => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->load->database();
        $this->db->insert('lap_wfa', $data);

        echo json_encode(['success' => true, 'message' => 'Laporan berhasil disimpan']);
    }

    public function get() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");

        $user_id = $this->input->get('user_id');

        if (!$user_id) {
            echo json_encode(['success' => false, 'message' => 'user_id wajib']);
            return;
        }

        $this->load->database();
        $this->db->where('user_id', $user_id);
        $this->db->order_by('tanggal_lap', 'desc');
        $query = $this->db->get('lap_wfa');

        echo json_encode([
            'success' => true,
            'data' => $query->result()
        ]);
    }
}
