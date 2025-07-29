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
        $this->form_validation->set_rules('username','Username','required|max_length[12]|xss_clean');
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
                if (password_verify($p, $cekemail[0]['password'])) {
                    //update last login
                    $update = array('last_login' => date("Y-m-d H:i:s"));
                    $where = array('id_user' => $cekemail[0]['id_user']);
                    $this->user->updateUser($update, $where);
                    $getRole = $this->user->getUserRole($cekemail[0]['id_user']);
                    $getLokasi = $this->user->getLokasiKantor($cekemail[0]['lokasi_kantor_id']);
                    $setWaktu = [];
                    // if($cekemail[0]['shifting'] == 'Y') {
                    //     $cariWaktu = array(
                    //         'jenis' => 'shift',
                    //         'upt' => $cekemail[0]['upt_id'] ? substr($cekemail[0]['upt_id'], 0, 2) : "10"
                    //     );
                    //     $setWaktu = $this->user->getSettingWaktu($cariWaktu);
                    // } else {
                    // }
                    $cariWaktu = array(
                        'jenis' => 'office',
                        'gunakan' => 'Y'
                    );
                    $setWaktu = $this->user->getSettingWaktu($cariWaktu);
                    $output['role'] = $getRole;
                    $output['lokasi_kantor'] = $getLokasi;
                    $output['setting_waktu'] = $setWaktu;

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
                    $output['expired'] = $token['exp'];
                    // $output['expired'] = date("Y-m-d H:i:s", $token['exp']);
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
        if($insert > 0) {
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
        if($this->put('password')) {
            $input['password'] = password_hash(('Ndr00' . $this->put('password') . 'MukeG!l3'), PASSWORD_DEFAULT);
        }
        $insert = $this->user->updateUser($input, array('id' => $this->put('id')));
        if($insert > 0) {
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

    public function index_delete() {
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

    public function resetPass_post() {
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
        $this->form_validation->set_rules('id_user', 'ID', 'required|xss_clean');
        $this->form_validation->set_rules('passwordlama', 'Password Lama', 'required|xss_clean');
        $this->form_validation->set_rules('password', 'Password', 'required|xss_clean');
        $this->form_validation->set_rules('konfirmPassword', 'Konfirmasi Password', 'required|matches[password]|xss_clean');

        if ($this->form_validation->run() == FALSE) {
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], RESTController::HTTP_BAD_REQUEST);
            return;
        }
        $u = $this->post('id_user'); //Username Posted
        $p = $this->post('password'); //Pasword Posted
        $q = array('id_user' => $u); //For where query condition
        $this->load->config('jwt');
        $cekemail = $this->user->getdatauser($q);
        if ($cekemail) {
            if (password_verify($this->post('passwordlama'), $cekemail[0]['password'])) {
                $simpanpassword = array(
                    'password' => password_hash($p, PASSWORD_ARGON2ID),
                    'is_salt' => date('Y-m-d H:i:s'),
                    'last_login' => date('Y-m-d H:i:s')
                );
                $ceksimpan = $this->user->updateUser($simpanpassword, $q);
                if($ceksimpan > 0) {
                    $this->response([
                        'status' => TRUE,
                        'message' => "Berhasil simpan password baru"
                    ], RESTController::HTTP_OK);
                } else {
                    $this->response([
                        'status' => FALSE,
                        'message' => "Gagal simpan password"
                    ], RESTController::HTTP_BAD_REQUEST);
                }
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => "Password lama tidak sama"
                ], RESTController::HTTP_BAD_REQUEST);
            }
        } else {
            $this->response([
                'status' => FALSE,
                'message' => "Data tidak ditemukan"
            ], RESTController::HTTP_NOT_FOUND);
        }
    }
}
