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
        $kunci = $this->config->item('jwt_key');
        $cekemail = $this->user->getdatauser($q);
        if ($cekemail) {
            if ($cekemail[0]['status'] == 1) {
                // if ($p == $cekemail[0]['password']) {
                if (password_verify(('Ndr00' . $p . 'MukeG!l3'), $cekemail[0]['password'])) {
                    //update last login
                    $update = array('lastlogin' => date("Y-m-d H:i:s"));
                    $where = array('id' => $cekemail[0]['id']);
                    $this->user->updateUser($update, $where);

                    unset($cekemail[0]["password"]);
                    
                    $token['username'] = $u;
                    $this->load->library('Authorization_Token');
                    $date = new DateTime();
                    $token['exp'] = $date->getTimestamp() + 60 * 60 * 1; //To here is to generate token
                    
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
}
