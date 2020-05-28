<?php
class M_chat extends CI_Model {

    function getChat($id = null){
        if ($id === null){
            return $this->db->get('chat');
        }else {
            return $this->db->get_where('chat', ['id'=>$id]);
        }
    }

    function getChatWithMember($member_id, $thread) {
        return $this->db->query("SELECT m.* from member m, chat c WHERE c.thread = '$thread' AND c.member_id != '$member_id' AND c.member_id = m.id GROUP BY m.id;");
    }

    function createChat($data){
        $this->db->insert('chat', $data);
        return $this->db->insert_id();
    }

    function updateChat($id, $data){
        $this->db->update('chat', $data, ['id'=>$id]);
        return $this->db->affected_rows();
    }

    function deleteChat($id){
        $this->db->delete('chat', ['id'=>$id]);
        return $this->db->affected_rows();
    }

    function bulkSeek($ids){
        $chat_ids = implode("','", $ids);
        $this->db->query("UPDATE chat SET seen = 1 WHERE id in ('".$chat_ids."')");
        return $this->db->affected_rows();
    }

    function seekAll($member_id, $thread){
        $last = $this->db->query("SELECT c.* FROM chat c INNER JOIN(SELECT MAX(created_at) AS last FROM chat WHERE thread = '$thread' AND member_id != '$member_id') gc  ON c.created_at = gc.last;");
        $this->db->query("UPDATE chat SET seen = 1 WHERE member_id != '$member_id' AND thread = '$thread';");
        return $last;
    }
}
