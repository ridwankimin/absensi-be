<?php
// defined('BASEPATH') or exit('No direct script access allowed');

class M_perizinan extends CI_Model
{
    public function insert($data)
    {
        return $this->db->insert('perizinan', $data);
    }
}
