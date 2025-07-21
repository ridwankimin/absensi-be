<?php if (!defined('BASEPATH')) exit('No direct script allowed');

class PegawaiModel extends CI_Model
{

    function getDataPegawai($cari)
    {
        $this->db->select('id_user,nama,nip');
        $this->db->order_by('nip', 'asc');
        return $this->db->get_where('user', $cari)->result_array();
    }
}