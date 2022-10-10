<?php 
   class DataNFTs extends CI_Model {
	
      function __construct() { 
         parent::__construct(); 
      } 
   
      public function insert($data) {

         return $this->db->insert("tb_datanfts", $data);
         if ($this->db->insert("tb_datanfts", $data)) { 
            return $this->db->insert_id();; 
         } else{
            return false;
         }
      } 
   
      public function delete($roll_no) { 
         if ($this->db->delete("tb_datanfts", "roll_no = ".$roll_no)) { 
            return true; 
         } 
      } 
   
      // public function update($data,$old_roll_no) { 
      //    $this->db->set($data); 
      //    $this->db->where("roll_no", $old_roll_no); 
      //    $this->db->update("stud", $data); 
      // } 
      public function getAllData(){
          $query = $this->db->get('tb_datanfts', 10);
         return $query->result();
      }
   } 
?> 