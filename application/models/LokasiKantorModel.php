<?php if(!defined('BASEPATH')) exit('No direct script allowed');

class LokasiKantorModel extends CI_Model{

	function getLokasi($cari) {
		return $this->db->get_where('lokasi_kantor', $cari)->result_array();
	}

    function getIdLast($upt) {
        $this->db->select('RIGHT(id,3) * 1 AS nolast');
        $this->db->from('lokasi_kantor');
        $this->db->where('upt_id', $upt);
        return $this->db->get()->row();
    }

    function simpanLokasi($simpan) {
        $this->db->insert('lokasi_kantor', $simpan);
        if($this->db->affected_rows() > 0) {
            return array(
                'status' => true,
                'message' => 'Sip'
            );
        } else {
            return array(
                'status' => false,
                'message' => $this->db->error()
            );
        }
    }
    
    function updateLokasi($simpan, $id) {
        $this->db->where('id', $id);
        $this->db->update('lokasi_kantor', $simpan);
        if($this->db->affected_rows() > 0) {
            return array(
                'status' => true,
                'message' => 'Sip'
            );
        } else {
            return array(
                'status' => false,
                'message' => $this->db->error()
            );
        }
    }
    
    function deleteLokasi($id) {
        $this->db->where('id', $id);
        $this->db->delete('lokasi_kantor');
        if($this->db->affected_rows() > 0) {
            return array(
                'status' => true,
                'message' => 'Sip'
            );
        } else {
            return array(
                'status' => false,
                'message' => $this->db->error()
            );
        }
    }
}