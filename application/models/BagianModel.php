<?php if (!defined('BASEPATH')) exit('No direct script allowed');

class BagianModel extends CI_Model
{
    function getDataBagian($cari)
    {
        return $this->db->get_where('master_bagian', $cari)->result_array();
    }
}