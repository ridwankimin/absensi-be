<?php
// defined('BASEPATH') or exit('No direct script access allowed');

class M_perizinan extends CI_Model
{
    public function insert($data)
    {
        return $this->db->insert('perizinan', $data);
    }
    public function get_by_user($nip)
    {
        $this->db->select('perizinan.*, user.nip');
        $this->db->from('perizinan');
        $this->db->join('user', 'id_user = perizinan.user_input'); // sesuaikan kalau bukan 'id'
        $this->db->where('user.nip', $nip);
        return $this->db->get()->result_array();
    }
}
