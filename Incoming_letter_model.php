<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Incoming_letter_model extends CI_Model
{
  
    private $searchableColumns = ['letter_number', 'sender', 'subject', 'status'];

    public function get_all($search_number = null, $status_filter = null)
    {
        if ($search_number) {
            $this->db->like('letter_number', $search_number);
        }
        if ($status_filter) {
            $this->db->where('status', $status_filter);
        }
        return $this->db->get('incoming_letters')->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where('incoming_letters', array('id' => $id))->row_array();
    }

    public function getById($id)
    {
        return $this->db->get_where('incoming_letters', array('id' => $id))->row();
    }

    public function getSelectOptions()
    {
        $this->db->select('id, letter_number, subject');
        $this->db->order_by('letter_date', 'DESC');
        return $this->db->get('incoming_letters')->result();
    }

    public function insert($data)
    {
        return $this->db->insert('incoming_letters', $data);
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('incoming_letters', $data);
    }

    public function update_status($id, $status)
    {
        $this->db->where('id', $id);
        return $this->db->update('incoming_letters', array('status' => $status));
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('incoming_letters');
    }

    public function count_all()
    {
        return $this->db->count_all('incoming_letters');
    }

    
    public function count_filtered($column, $keyword)
    {
        $this->_apply_filter($column, $keyword);
        return $this->db->count_all_results('incoming_letters');
    }

   
    public function get_datatables($column, $keyword, $start = 0, $length = 10)
    {
        $this->_apply_filter($column, $keyword);
        $this->db->order_by('letter_date', 'DESC');

        if ($length > 0) {
            $this->db->limit($length, $start);
        }

        return $this->db->get('incoming_letters')->result_array();
    }

    
    private function _apply_filter($column, $keyword)
    {
        if ($keyword !== null && $keyword !== '' && in_array($column, $this->searchableColumns, true)) {
            $this->db->like($column, $keyword);
        }
    }

    public function is_letter_number_taken($letterNumber, $excludeId = null)
    {
        $this->db->where('letter_number', $letterNumber);
        if ($excludeId) {
            $this->db->where('id !=', $excludeId);
        }
        $count = $this->db->count_all_results('incoming_letters');
        return $count > 0;
    }
}