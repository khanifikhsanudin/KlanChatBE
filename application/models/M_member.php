<?php
class M_member extends CI_Model {

    function getMember($id = null){
        if ($id === null){
            return $this->db->get('member');
        }else {
            return $this->db->query("SELECT * FROM member WHERE id != '$id';");
        }
    }

    function getMemberMe($id){
        return $this->db->get_where('member', ['id'=>$id]);
    }


    function createMember($data){
        $this->db->insert('member', $data);
        return $this->db->affected_rows();
    }

    function updateMember($id, $data){
        $this->db->update('member', $data, ['id'=>$id]);
        return $this->db->affected_rows();
    }

    function deleteMember($id){
        $this->db->delete('member', ['id'=>$id]);
        return $this->db->affected_rows();
    }

    
    function getMemberSocial($id){
        return $this->db->get_where('member', ['social_id'=>$id]);
    }

    function updateMemberSocial($id, $data){
        $this->db->update('member', $data, ['social_id'=>$id]);
        return $this->db->affected_rows();
    }
}