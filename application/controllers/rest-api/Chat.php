<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Restserver\Libraries\REST_Controller;

require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/Format.php';
require APPPATH . '/libraries/Firebase.php';

require_once APPPATH . '/entity/Data_payload.php';

class Chat extends REST_Controller {

    function __construct(){
        parent::__construct();
        $this->load->model('M_member', 'm_member');
        $this->load->model('M_chat', 'm_chat');
    }

    function index_get($path = null){
        $member_id    = $this->get('member_id');
        $thread       = $this->get('thread');
        $limit        = $this->get('limit');
        $page         = $this->get('page');
        $cursor       = $this->get('cursor');
        $fcm_token    = $this->get('fcm_token');

        if ($path === 'headmessage') {
            if ($member_id != null) {
                $head = $this->db->query("SELECT head.*, m.id AS from_id, m.name AS from_name, m.img AS from_img, m.img_color as from_img_color FROM (SELECT p1.* FROM chat p1 INNER JOIN (SELECT max(created_at) max_chat_date, thread FROM chat WHERE member_id = '$member_id' OR target_id = '$member_id' GROUP BY thread) p2 ON p1.thread = p2.thread AND p1.created_at = p2.max_chat_date WHERE member_id = '$member_id' OR target_id = '$member_id' ORDER BY p1.created_at  DESC) head, member m WHERE m.id = if(head.member_id = '$member_id', head.target_id, head.member_id)")->result_array();

                for ($i=0; $i < COUNT($head); $i++) {
                    $thread  = $head[$i]['thread'];
                    $from_id = $head[$i]['from_id'];
                    $num = $this->db->query("SELECT COUNT(*) as total from chat where thread = '$thread' and seen = 0 AND member_id = '$from_id';")->row_array();

                    $head[$i]['total_unread'] = $num['total'];
                }
                
                $this->response([
                    'status'       => True,
                    'message'      => 'Data head message berhasil di load.',
                    'data'         => $head
                ], REST_Controller::HTTP_OK);

            }else {
                $this->response([
                    'status'  => False,
                    'message' => 'Params no valid to access.'
                ], REST_Controller::HTTP_OK);
            }
        }elseif ($path === 'seen') {
            if ($thread != null && $member_id != null) {
                if ($cursor === null OR $cursor === "") {
                    $chat   = $this->db->query("SELECT * FROM chat WHERE thread = '$thread' ORDER BY UNIX_TIMESTAMP(created_at) DESC;")->result_array();
                }else {
                    $chat   = $this->db->query("SELECT * FROM chat WHERE thread = '$thread' AND created_at <= '$cursor' ORDER BY UNIX_TIMESTAMP(created_at) DESC;")->result_array();
                }

                if ($chat) {
                    $unread = $this->db->query("SELECT COUNT(*) AS total FROM chat WHERE thread = '$thread' AND member_id != '$member_id' AND seen = 0;")->row_array();

                    $last = $this->m_chat->seekAll($member_id, $thread)->row_array();
                    if ($last['seen'] === 0) {
                        $target = $this->m_chat->getChatWithMember($member_id, $thread)->row_array(); 
                        $read_helper = [
                            'thread' => $thread,
                            'last'   => $last['created_at']
                        ];
                        if ($target['fcm_token']) {
                            $firebase = new Firebase();
                            $payload  = new Data_payload();
                            $payload->setAksi('readall');
                            $payload->setData($read_helper);
            
                            $firebase->send($target['fcm_token'], $payload->getData());
                        }
                    }

                    $prev         = "";
                    $next         = "";
                    $chat_num = COUNT($chat);
                    if ($limit != null && $page != null && $page > 0) {
                        if ($page === 1) {
                            $limit = $limit + $unread['total'];
                        }

                        $chat = array_slice($chat, ($limit * ($page - 1)), $limit);
                        
                        if (($limit * $page) < $chat_num) {
                            $next = strval((int)$page + 1);
                        }
        
                        if ($page > 1 && (($limit * $page) - $chat_num) < $limit) {
                            $prev = strval((int)$page - 1);
                        }
                    }

                    for ($i=0; $i < COUNT($chat); $i++) {
                        if ($chat[$i]['member_id'] != $member_id) {
                            $chat[$i]['me'] = false;
                        }else {
                            $chat[$i]['me'] = true;
                        }
                    }

                    $this->response([
                        'status'       => True,
                        'message'      => 'Data chat berhasil di load.',
                        'prev'         => $prev,
                        'next'         => $next,
                        'data'         => array_reverse($chat)
                    ], REST_Controller::HTTP_OK);

                }else {
                    $this->response([
                        'status'  => True,
                        'message' => 'data chat tidak ada.',
                        'data'    => array()
                    ], REST_Controller::HTTP_OK);
                }
            }else {
                $this->response([
                    'status'  => False,
                    'message' => 'Params no valid to access.'
                ], REST_Controller::HTTP_OK);
            }
        }elseif ($path === 'unread') {
            if ($thread != null && $member_id != null && $cursor != null) {
                $unread = $this->db->query("SELECT * FROM  chat WHERE thread = '$thread' AND created_at > '$cursor' ORDER BY UNIX_TIMESTAMP(created_at) ASC;")->result_array();

                $ids = array();
                for ($i=0; $i < COUNT($unread); $i++) {
                    if ($unread[$i]['member_id'] != $member_id) {
                        $ids[$i] = $unread[$i]['id'];
                        $unread[$i]['me'] = false;
                    }else {
                        $unread[$i]['me'] = true;
                    }
                }

                $this->m_chat->bulkSeek($ids);

                $this->response([
                    'status'       => True,
                    'message'      => 'Data head message berhasil di load.',
                    'data'         => $unread
                ], REST_Controller::HTTP_OK);

            }else {
                $this->response([
                    'status'  => False,
                    'message' => 'Params no valid to access.'
                ], REST_Controller::HTTP_OK);
            }
        }elseif ($path === 'syncronize') {
            if ($member_id != null) {
                $head = $this->db->query("SELECT head.*, m.id AS from_id, m.name AS from_name, m.img AS from_img, m.img_color as from_img_color FROM (SELECT p1.* FROM chat p1 INNER JOIN (SELECT max(created_at) max_chat_date, thread FROM chat WHERE member_id = '$member_id' OR target_id = '$member_id' GROUP BY thread) p2 ON p1.thread = p2.thread AND p1.created_at = p2.max_chat_date WHERE member_id = '$member_id' OR target_id = '$member_id' ORDER BY p1.created_at  DESC) head, member m WHERE m.id = if(head.member_id = '$member_id', head.target_id, head.member_id)")->result_array();

                for ($i=0; $i < COUNT($head); $i++) {
                    $thread  = $head[$i]['thread'];
                    $from_id = $head[$i]['from_id'];
                    $num = $this->db->query("SELECT COUNT(*) as total from chat where thread = '$thread' and seen = 0 AND member_id = '$from_id';")->row_array();

                    $head[$i]['total_unread'] = $num['total'];
                }

                $total_notifchat = $this->db->query("SELECT COUNT(*) as total from chat where target_id = '$member_id' and seen = 0")->row_array();

                if ($fcm_token != null) {
                    $data = [
                        'fcm_token'    => $fcm_token,
                        'updated_at'   => date("Y-m-d H:i:s")
                    ];
                    $this->m_member->updateMember($member_id, $data);
                }
                
                $this->response([
                    'status'           => True,
                    'message'          => 'Data syncronize berhasil di load.',
                    'notifchat'        => $total_notifchat['total'],
                    'headmessage'      => $head,
                ], REST_Controller::HTTP_OK);

            }else {
                $this->response([
                    'status'  => False,
                    'message' => 'Params no valid to access.'
                ], REST_Controller::HTTP_OK);
            }
        }else {
            $this->response([
                'status'  => False,
                'message' => 'Need path method to access.'
            ], REST_Controller::HTTP_OK);
        }
    }

    function index_post($path = null){
        date_default_timezone_set('Asia/Jakarta');
        $data = [
            'id'           => $this->post('id'),
            'thread'       => $this->post('thread'),
            'member_id'    => $this->post('member_id'),
            'target_id'    => $this->post('target_id'),
            'text'         => $this->post('text'),
            'active'       => $this->post('active'),
            'seen'         => $this->post('seen'),
            'created_at'   => date("Y-m-d H:i:s"),
            'updated_at'   => date("Y-m-d H:i:s")
        ];

        if ($path === 'insert') {
            $this->insertData($data);
        }elseif ($path === 'readall') {
            $this->readAll($data);
        }elseif ($path === 'update') {
            $this->updateData($data);
        }elseif ($path === 'delete') {
            $this->deleteData($data['id']);
        }else {
            $this->response([
                'status'  => False,
                'message' => 'Need path method to access.'
            ], REST_Controller::HTTP_OK);
        }
    }

    function insertData($data) {

        $entity = array();
        foreach ($data as $key => $val) {
            if ($val != null){
                $entity[$key] = $val;
            }
        }

        if ($data['thread'] != null && $data['member_id'] != null && $data['text'] != null) {
            $entity['active'] = 1;
            $entity['seen']   = 0;
            $insert = $this->m_chat->createChat($entity); 
            if ($insert > 0){
                $member = $this->m_member->getMemberMe($data['member_id'])->row_array();
                $target = $this->m_member->getMemberMe($data['target_id'])->row_array();
                $chat   = $this->m_chat->getChat($insert)->row_array();

                $chat['me'] = false;
                $chat_helper = [
                    'thread'      => $data['thread'],
                    'timemillis'  => time(),
                    'chat'        => $chat
                ];
                $chat['me'] = true;

                if ($target['fcm_token']) {
                    $firebase = new Firebase();
                    $payload  = new Data_payload();
                    $payload->setJudul($member["name"]);
                    $payload->setPesan($member["name"].": ".$data['text']);
                    $payload->setAvatar($member["img"]);
                    $payload->setAksi('chat');
                    $payload->setData($chat_helper);

                    $firebase->send($target['fcm_token'], $payload->getData());
                }

                $this->response([
                    'status'     => True,
                    'message'    => 'Data chat baru berhasil di tambah.',
                    'data'       => $chat
                ], REST_Controller::HTTP_CREATED);
                
            }else {
                $this->response([
                    'status'  => False,
                    'message' => 'gagal menambahkan data chat baru.'
                ], REST_Controller::HTTP_OK);
            }
        }else {
            $this->response([
                'status'  => False,
                'message' => 'gagal menambahkan data chat baru'
            ], REST_Controller::HTTP_OK);
        }
    }

    function readAll($data) {
        if ($data['thread'] != null && $data['member_id'] != null) {
            $last = $this->m_chat->seekAll($data['member_id'], $data['thread'])->row_array()["created_at"];
            $target = $this->m_chat->getChatWithMember($data['member_id'], $data['thread'])->row_array(); 

            $read_helper = [
                'thread' => $data['thread'],
                'last'   => $last
            ];
            if ($target['fcm_token']) {
                $firebase = new Firebase();
                $payload  = new Data_payload();
                $payload->setAksi('readall');
                $payload->setData($read_helper);

                $firebase->send($target['fcm_token'], $payload->getData());
            }
            $this->response([
                'status'           => True,
                'message'          => 'Read all success.'
            ], REST_Controller::HTTP_OK);
        }else {
            $this->response([
                'status'  => False,
                'message' => 'Params no valid to access.'
            ], REST_Controller::HTTP_OK);
        }
    }

    function updateData($data) {
        $chat_id = $data['id'];

        $entity = array();
        foreach ($data as $key => $val) {
            if ($val != null){
                $entity[$key] = $val;
            }
        }

        if ($chat_id != null){
            unset($entity['created_at']);
            if ($this->m_chat->updatechat($chat_id, $entity) > 0){

                $chat = $this->m_chat->getChat($chat_id)->row_array();

                $this->response([
                    'status'   => True,
                    'message'  => 'Data chat berhasil di update.',
                    'data'     => $chat
                ], REST_Controller::HTTP_OK);
            }else {
                $this->response([
                    'status'  => False,
                    'message' => 'Gagal mengupdate data chat.'
                ], REST_Controller::HTTP_OK);
            }
        }else{
            $this->response([
                'status'  => False,
                'message' => 'Gagal mengupdate data chat.'
            ], REST_Controller::HTTP_OK);
        }

    }

    function deleteData($id) {

        if ($id === null){
            $this->response([
                'status'  => False,
                'message' => 'tidak ada chat yang dihapus.'
            ], REST_Controller::HTTP_OK);
        }else {
            if ($this->m_chat->deletechat($id) > 0){
                $this->response([
                    'status'      => True,
                    'message'     => 'Data chat berhasil di hapus.'
                ], REST_Controller::HTTP_OK);
            }else {
                $this->response([
                    'status'  => False,
                    'message' => 'id chat tidak ada.'
                ], REST_Controller::HTTP_OK);
            }
        }
    }
}
