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
        $this->db->select('perizinan.*, user.nip, user.nama');
        $this->db->from('perizinan');
        $this->db->join('user', 'user.nama = perizinan.user_input'); // GANTI sesuai nama kolom valid
        $this->db->where('user.nip', $nip);
        $this->db->order_by('perizinan.created_at', 'DESC');
        return $this->db->get()->result_array();
    }
}
