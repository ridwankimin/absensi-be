<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;

require APPPATH . 'libraries/RestController.php';
require APPPATH . 'libraries/Format.php';

class Auth extends RestController
{
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->methods['users_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['login_post']['limit'] = 10; // 100 requests per hour per user/key
        $this->methods['users_delete']['limit'] = 50; // 50 requests per hour per user/key
        $this->load->model('m_users', 'user');
        $this->load->helper('security');
    }

    public function login_post()
    {
        $this->form_validation->set_data($this->post());
        $this->form_validation->set_rules('username', 'Username', 'required|max_length[12]|xss_clean');
        $this->form_validation->set_rules('password', 'PASSWORD', 'required|xss_clean');
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $u = $this->post('username'); //Username Posted
        $p = $this->post('password'); //Pasword Posted
        $q = array('username' => $u); //For where query condition
        $this->load->config('jwt');
        $exp = $this->config->item('token_expire_time');
        $cekemail = $this->user->getdatauser($q);
        if ($cekemail) {
            if ($cekemail[0]['verified'] == 1) {
                // if ($p == $cekemail[0]['password']) {
                $salt1 = '';
                $salt2 = '';
                if ($cekemail[0]['is_salt']) {
                    $salt1 = 'Ndr00';
                    $salt2 = 'MukeG!l3';
                }
                if (password_verify(($salt1 . $p . $salt2), $cekemail[0]['password'])) {
                    //update last login
                    $update = array('last_login' => date("Y-m-d H:i:s"));
                    $where = array('id_user' => $cekemail[0]['id_user']);
                    $this->user->updateUser($update, $where);
                    $getRole = $this->user->getUserRole($cekemail[0]['id_user']);
                    $getLokasi = $this->user->getLokasiKantor($cekemail[0]['lokasi_kantor_id']);
                    $output['role'] = $getRole;
                    $output['lokasi_kantor'] = $getLokasi;

                    unset($cekemail[0]["password"]);

                    $token['username'] = $u;
                    $this->load->library('Authorization_Token');
                    $date = new DateTime();
                    // $token['exp'] = $date->getTimestamp() + 60 * 60 * 2; //To here is to generate token
                    $token['exp'] = $date->getTimestamp() + $exp; //To here is to generate token

                    $output['token'] = $this->authorization_token->generateToken($cekemail[0]);
                    // $token['iat'] = $date->getTimestamp();
                    $output['data'] = $cekemail[0];
                    // $output['token'] = JWT::encode($token, $kunci, 'HS256'); //This is the output token
                    $output['expired'] = date("Y-m-d H:i:s", $token['exp']);
                    $this->response([
                        'status' => TRUE,
                        'message' => 'Login sukses',
                        'data' => $output
                    ], RESTController::HTTP_OK);
                } else {
                    $this->response([
                        'status' => FALSE,
                        'message' => 'Wrong password'
                    ], RESTController::HTTP_BAD_REQUEST);
                }
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Akun tidak aktif'
                ], RESTController::HTTP_BAD_REQUEST);
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'User not found'
            ], RESTController::HTTP_BAD_REQUEST);
        }
    }

    public function index_post()
    {
        $this->load->library('Authorization_Token');
        $is_valid_token = $this->authorization_token->validateToken();

        if (!$is_valid_token['status']) {
            $this->response([
                'status' => FALSE,
                'message' => $is_valid_token['message']
            ], RESTController::HTTP_UNAUTHORIZED);
            return;
        }
        $this->form_validation->set_data($this->post());
        $this->form_validation->set_rules(
            'username',
            'Username',
            'required|max_length[12]|is_unique[users.username]|xss_clean',
            array(
                'required'      => 'You have not provided %s.',
                'is_unique'     => 'This %s already exists.'
            )
        );
        $this->form_validation->set_rules('nama', 'NAMA', 'required|xss_clean');
        $this->form_validation->set_rules('password', 'PASSWORD', 'required|xss_clean');
        $this->form_validation->set_rules('roles', 'ROLES', 'required|xss_clean');

        // Jalankan validasi form
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $input = array(
            'username' => $this->post('username'),
            'nama' => $this->post('nama'),
            'password' => password_hash(('Ndr00' . $this->post('password') . 'MukeG!l3'), PASSWORD_DEFAULT),
            'roles' => $this->post('roles'),
            'created' => date('Y-m-d H:i:s')
        );
        $insert = $this->user->insertUser($input);
        if ($insert > 0) {
            $this->response([
                'status' => TRUE,
                'message' => 'Sukses insert user'
            ], RESTController::HTTP_CREATED);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Gagal insert user'
            ], RESTController::HTTP_BAD_REQUEST);
        }
    }

    public function index_put()
    {
        $this->load->library('Authorization_Token');
        $is_valid_token = $this->authorization_token->validateToken();

        if (!$is_valid_token['status']) {
            $this->response([
                'status' => FALSE,
                'message' => $is_valid_token['message']
            ], RESTController::HTTP_UNAUTHORIZED);
            return;
        }
        $this->form_validation->set_data($this->put());
        $this->form_validation->set_rules('id', 'ID', 'required|xss_clean');
        $this->form_validation->set_rules('username', 'Username', 'required|xss_clean');
        $this->form_validation->set_rules('nama', 'NAMA', 'required|xss_clean');
        $this->form_validation->set_rules('password', 'PASSWORD', 'xss_clean');
        $this->form_validation->set_rules('roles', 'ROLES', 'required|xss_clean');

        // Jalankan validasi form
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal, kirimkan respon dengan pesan error
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $input = array(
            'username' => $this->put('username'),
            'nama' => $this->put('nama'),
            'roles' => $this->put('roles'),
            'created' => date('Y-m-d H:i:s')
        );
        if ($this->put('password')) {
            $input['password'] = password_hash(('Ndr00' . $this->put('password') . 'MukeG!l3'), PASSWORD_DEFAULT);
        }
        $insert = $this->user->updateUser($input, array('id' => $this->put('id')));
        if ($insert > 0) {
            $this->response([
                'status' => TRUE,
                'message' => 'Sukses update user'
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => TRUE,
                'message' => 'Data user sudah terupdate'
            ], RESTController::HTTP_OK);
        }
    }

    public function index_delete()
    {
        $this->load->library('Authorization_Token');
        $is_valid_token = $this->authorization_token->validateToken();

        if (!$is_valid_token['status']) {
            $this->response([
                'status' => FALSE,
                'message' => $is_valid_token['message']
            ], RESTController::HTTP_UNAUTHORIZED);
            return;
        }
        $this->form_validation->set_data($this->delete());
        $this->form_validation->set_rules('id', 'ID', 'required|xss_clean');

        $update = array(
            'status' => '0'
        );
        $insert = $this->user->updateUser($update, array('id' => $this->delete('id')));
        if ($insert > 0) {
            $this->response([
                'status' => TRUE,
                'message' => 'Sukses update user'
            ], RESTController::HTTP_OK);
        } else {
            $this->response([
                'status' => TRUE,
                'message' => 'Data user sudah terupdate'
            ], RESTController::HTTP_OK);
        }
    }
    public function reset_password_post()
    {
        // $inputJSON = file_get_contents('php://input');
        // $input = json_decode($inputJSON, TRUE);
        $input = json_decode(trim(file_get_contents('php://input')));

        $nip = isset($input->nip) ? $input->nip : null;
        $foto_base64 = isset($input->foto_base64) ? $input->foto_base64 : null;
        $password_lama = isset($input->password_lama) ? $input->password_lama : null;
        $password_baru = isset($input->password_baru) ? $input->password_baru : null;

        $foto_base64 = $this->post('foto_base64');

        if (!$nip) {
            return $this->response(['status' => false, 'message' => 'NIP wajib diisi'], 400);
        }

        $user = $this->db->get_where('user', ['nip' => $nip])->row();

        if (!$user) {
            return $this->response(['status' => false, 'message' => 'User tidak ditemukan'], 404);
        }

        // --- Jika ingin mengubah password ---
        if (!empty($password_lama) && !empty($password_baru)) {
            if (!password_verify($password_lama, $user->password)) {
                return $this->response(['status' => false, 'message' => 'Password lama salah'], 400);
            }

            $this->db->where('nip', $nip)->update('user', [
                'password' => password_hash($password_baru, PASSWORD_DEFAULT),
            ]);
        }

        // --- Jika ingin mengunggah foto profil ---
        if (!empty($foto_base64)) {
            // Hapus foto lama jika bukan default
            if ($user->avatar && $user->avatar !== 'images/user.png') {
                $old_path = FCPATH . $user->avatar;
                if (file_exists($old_path)) {
                    unlink($old_path);
                }
            }

            $foto_path = $this->_simpan_foto($foto_base64, $nip);
            if ($foto_path) {
                $this->db->where('nip', $nip)->update('user', ['avatar' => $foto_path]);
            }
        }

        return $this->response(['status' => true, 'message' => 'Profil berhasil diperbarui'], 200);
    }


    // Fungsi bantu simpan foto
    private function _simpan_foto($base64, $nip)
    {
        $folder = './uploads/';
        if (!file_exists($folder)) {
            mkdir($folder, 0755, true);
        }

        // Pisahkan metadata dan data base64
        $data = explode(',', $base64);
        if (count($data) !== 2) return false;

        $meta = $data[0];  // contoh: "data:image/png;base64"
        $encoded = $data[1];

        // Ambil ekstensi file dari metadata
        if (preg_match('/^data:image\/(\w+);base64$/', $meta, $matches)) {
            $ext = $matches[1];  // png / jpg / jpeg
        } else {
            return false;
        }

        // Generate nama file
        $filename = str_replace(' ', '_', 'avatar_' . $nip . '_' . time() . '.' . $ext);
        $path = $folder . $filename;

        // Decode dan simpan
        $decoded = base64_decode($encoded);
        if ($decoded === false) return false;

        file_put_contents($path, $decoded);

        return $filename;
    }
}
