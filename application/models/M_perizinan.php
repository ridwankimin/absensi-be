<?php
// defined('BASEPATH') or exit('No direct script access allowed');

class M_perizinan extends CI_Model
{
    public function insert($data)
    {
        return $this->db->insert('perizinan', $data);
    }

    public function get_by_nip_join($nip)
    {
        $this->db->select('p.*, u.nip');
        $this->db->from('perizinan p');
        $this->db->join('user u', 'p.user_input = u.nama');
        $this->db->where('u.nip', $nip);
        $this->db->order_by('p.created_at', 'DESC');
        return $this->db->get()->result();
    }
}
