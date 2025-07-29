<?php if (!defined('BASEPATH')) exit('No direct script allowed');

class MasterIzinModel extends CI_Model
{
public function getMasterIzin()
{
return $this->db->get('master_izin')->result_array();
}
}