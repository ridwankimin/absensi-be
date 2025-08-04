<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

require APPPATH . 'libraries/RestController.php';
require APPPATH . 'libraries/Format.php';

class Perizinan extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('M_perizinan', 'perizinan'); // gunakan nama model kamu
        $this->load->helper(['url', 'form']);
        $this->load->library('upload');
        $this->load->helper('security');
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
    }

    private function singkatan_izin($jenis)
    {
        $map = [
            'Dinas Luar'   => 'DL',
            'Cuti Tahunan' => 'CT',
            'Cuti Sakit'   => 'CS',
            'Cuti Besar'   => 'CB'
        ];
        return isset($map[$jenis]) ? $map[$jenis] : 'XX';
    }

    public function index_post()
    {
        $idtbl = uniqid();
        $data =
            [
                'id' => $idtbl,
                'perihal'      => $this->post('perihal'),
                'jenis_izin'   => $this->post('jenis_izin'),
                'tgl_mulai'    => $this->post('tgl_mulai'),
                'tgl_selesai'  => $this->post('tgl_selesai'),
                'p_upt'        => $this->post('p_upt'),
                'p_bagian'     => $this->post('p_bagian'),
                'user_input'   => $this->post('user_input'),
                'created_at'   => date('Y-m-d H:i:s')
            ];
        // if ($this->post('jenis_izin') == '1' && $this->post('nomor') != '') {
        //     $data['nomor'] = preg_replace('/\s+/', '', $this->post('nomor'));
        // }

        if ($this->post('jenis_izin') == 'Dinas Luar' && $this->post('nomor') != '') {
            $data['nomor'] = preg_replace('/\s+/', '', $this->post('nomor'));
        }


        // Upload file jika ada
        if (!empty($_FILES['lampiran']['name'])) {
            $jenis_izin = $this->post('jenis_izin'); // e.g. Dinas Luar
            $nip        = $this->post('nip');        // dari frontend React
            $kode_unik  = $kode_unik = str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
            $ext        = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);

            // 🔹 Buat nama file: DL_198011012005021001_ABC123.pdf
            $singkatan  = $this->singkatan_izin($jenis_izin);
            $nama_file  = $nama_file  = $singkatan . $nip . $kode_unik . '.' . $ext;
            $config['upload_path']   = './uploads/';
            $config['allowed_types'] = 'jpg|jpeg|png|pdf';
            $config['file_name']     = $nama_file;

            $this->upload->initialize($config);

            if ($this->upload->do_upload('lampiran')) {
                $uploadData = $this->upload->data();
                $data['lampiran'] = $uploadData['file_name'];
            } else {
                return $this->response([
                    'status' => false,
                    'message' => 'Upload gagal: ' . $this->upload->display_errors()
                ], 400);
            }
        }

        $insert = $this->perizinan->insert($data);

        if ($insert) {
            $this->response([
                'status' => true,
                'message' => 'Perizinan berhasil disimpan'
            ], 201);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Gagal menyimpan data'
            ], 500);
        }
    }
    public function index_options()
    {
        // tangani preflight CORS
        return $this->response(null, 200);
    }
    public function index_get()
    {
        $nip = $this->get('nip');

        if (!$nip) {
            return $this->response([
                'status' => false,
                'message' => 'NIP tidak diberikan'
            ], 400);
        }

        $data = $this->perizinan->get_by_user($nip);

        if ($data) {
            return $this->response([
                'status' => true,
                'message' => 'Data ditemukan',
                'data' => $data
            ], 200);
        } else {
            return $this->response([
                'status' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }
    }
    // public function update_post($id)
    // {
    //     $data = [
    //         'perihal'      => $this->post('perihal'),
    //         'jenis_izin'   => $this->post('jenis_izin'),
    //         'tgl_mulai'    => $this->post('tgl_mulai'),
    //         'tgl_selesai'  => $this->post('tgl_selesai'),
    //         'p_upt'        => $this->post('p_upt'),
    //         'p_bagian'     => $this->post('p_bagian'),
    //         'user_input'   => $this->post('user_input'),
    //     ];

    //     if ($this->post('jenis_izin') == 'Dinas Luar' && $this->post('nomor') != '') {
    //         $data['nomor'] = preg_replace('/\s+/', '', $this->post('nomor'));
    //     }

    //     // Handle upload baru jika ada
    //     if (isset($_FILES['lampiran']) && !empty($_FILES['lampiran']['name'])) {
    //         $jenis_izin = $this->post('jenis_izin');
    //         $nip        = $this->post('nip');
    //         $kode_unik  = str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
    //         $ext        = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);

    //         $singkatan  = $this->singkatan_izin($jenis_izin);
    //         $nama_file  = $singkatan . $nip . $kode_unik . '.' . $ext;

    //         $config['upload_path']   = './uploads/';
    //         $config['allowed_types'] = 'jpg|jpeg|png|pdf';
    //         $config['file_name']     = $nama_file;

    //         $this->upload->initialize($config);

    //         if ($this->upload->do_upload('lampiran')) {
    //             $uploadData = $this->upload->data();
    //             $data['lampiran'] = $uploadData['file_name'];
    //             $lampiran_lama = $this->post('lampiran_lama');
    //             if (!empty($lampiran_lama)) {
    //                 @unlink('./uploads/' . $lampiran_lama);
    //             }
    //         } else {
    //             return $this->response([
    //                 'status' => false,
    //                 'message' => 'Upload gagal: ' . $this->upload->display_errors()
    //             ], 400);
    //         }
    //     } else {
    //         $data['lampiran'] = $this->post('lampiran_lama');
    //     }

    //     $this->load->model('M_perizinan', 'perizinan');
    //     $updated = $this->perizinan->update($id, $data);

    //     if ($updated) {
    //         return $this->response([
    //             'status' => true,
    //             'message' => 'Data berhasil diperbarui'
    //         ], 200);
    //     } else {
    //         return $this->response([
    //             'status' => false,
    //             'message' => 'Gagal memperbarui data'
    //         ], 500);
    //     }
    // }
}
