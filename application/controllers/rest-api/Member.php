<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Restserver\Libraries\REST_Controller;

require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/Format.php';

class Member extends REST_Controller {

    function __construct(){
        parent::__construct();
        $this->load->model('M_member', 'm_member');
    }

    function index_get($path = null){
        $id = $this->get('id');

        if ($path === 'me') {
            $member = $this->m_member->getMemberMe($id)->row_array();
        }else {
            if ($id === null){
                $member = $this->m_member->getMember()->result_array();
            }else {
                $member = $this->m_member->getMember($id)->result_array();
            }
        }

        if ($member){
            $this->response([
                'status'  => True,
                'message' => 'Data member berhasil di load.',
                'data'    => $member,
            ], REST_Controller::HTTP_OK);
        }else {
            $this->response([
                'status'  => False,
                'message' => 'data member tidak ada.'
            ], REST_Controller::HTTP_OK);
        }
    }

    function index_post($path = null){
        date_default_timezone_set('Asia/Jakarta');
        $data = [
            'id'           => $this->post('id'),
            'social_id'    => $this->post('social_id'),
            'fcm_token'    => $this->post('fcm_token'),
            'name'         => $this->post('name'),
            'email'        => $this->post('email'),
            'img'          => $this->post('img'),
            'img_color'    => $this->post('img_color'),
            'created_at'   => date("Y-m-d H:i:s"),
            'updated_at'   => date("Y-m-d H:i:s"),
            'logined_at'   => date("Y-m-d H:i:s")
        ];

        if ($path === 'insert') {
            $this->insertData($data);
        }elseif ($path === 'update') {
            $this->updateData($data);
        }elseif ($path === 'delete') {
            $this->deleteData($data['id']);
        }elseif ($path === 'logout') {
            $this->logout($data['id']);
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

        $colors = array(
            "#EF6C00",
            "#C62828",
            "#311B92",
            "#2E7D32",
            "#558B2F",
            "#303F9F",
            "#b85671"
        );

        if ($data['social_id'] != null && $data['name'] != null){
            $member = $this->m_member->getMemberSocial($data['social_id']);
            if($member->num_rows() > 0){
                unset($entity['social_id']);
                unset($entity['created_at']);
                if($this->m_member->updateMemberSocial($data['social_id'], $entity) > 0){
                    $member = $this->m_member->getMemberSocial($data['social_id']);
                    $this->response([
                        'status'   => True,
                        'message'  => 'Data member berhasil di update.',
                        'data'     => $member->row_array()
                    ], REST_Controller::HTTP_OK);   
                }else{
                    $member = $this->m_member->getMemberSocial($data['social_id']);
                    $this->response([
                        'status'   => True,
                        'message'  => 'Data member tidak di update.',
                        'data'     => $member->row_array()
                    ], REST_Controller::HTTP_OK);
                }
            }else{
                $entity['img_color'] = $colors[array_rand($colors)];
                if ($this->m_member->createMember($entity) > 0){
                    $member = $this->m_member->getMemberSocial($data['social_id']);
                    $this->response([
                        'status'   => True,
                        'message'  => 'Data member baru berhasil di tambah.',
                        'data'     => $member->row_array()
                    ], REST_Controller::HTTP_CREATED);
                }else {
                    $this->response([
                        'status'  => False,
                        'message' => 'gagal menambahkan data member baru.'
                    ], REST_Controller::HTTP_OK);
                }
            }
        }else{
            $this->response([
                'status'  => False,
                'message' => 'nilai data member tidak valid.'
            ], REST_Controller::HTTP_OK);
        }
    }

    function updateData($data) {

        $entity = array();
        foreach ($data as $key => $val) {
            if ($val != null){
                $entity[$key] = $val;
            }
        }

        if ($data['id'] != null){
            unset($entity['created_at']);
            unset($entity['logined_at']);
            if ($this->m_member->updateMember($data['id'], $entity) > 0){
                $member = $this->m_member->getMemberMe($data['id']);
                $this->response([
                    'status'   => True,
                    'message'  => 'Data member berhasil di update.',
                    'data'     => $member->row_array()
                ], REST_Controller::HTTP_OK);
            }else {
                $this->response([
                    'status'  => False,
                    'message' => 'Gagal mengupdate data member.'
                ], REST_Controller::HTTP_OK);
            }
        }else{
            $this->response([
                'status'  => False,
                'message' => 'Gagal mengupdate data member.'
            ], REST_Controller::HTTP_OK);
        }
    }

    function deleteData($id) {

        if ($id === null){
            $this->response([
                'status'  => False,
                'message' => 'tidak ada member yang dihapus.'
            ], REST_Controller::HTTP_OK);
        }else {
            if ($this->m_member->deleteMember($id) > 0){
                $this->response([
                    'status'    => True,
                    'message'   => 'Data member berhasil di hapus.'
                ], REST_Controller::HTTP_OK);
            }else {
                $this->response([
                    'status'  => False,
                    'message' => 'id member tidak ada.'
                ], REST_Controller::HTTP_OK);
            }
        }
    }

    function logout($id) {

        if ($id === null){
            $this->response([
                'status'  => False,
                'message' => 'tidak ada member yang logout.'
            ], REST_Controller::HTTP_OK);
        }else {
            date_default_timezone_set('Asia/Jakarta');
            $data = [
                'fcm_token'    => null,
                'updated_at'   => date("Y-m-d H:i:s"),
            ];
            if ($this->m_member->updateMember($id, $data) > 0){
                $this->response([
                    'status'    => True,
                    'message'   => 'Data member berhasil di logout.'
                ], REST_Controller::HTTP_OK);
            }else {
                $this->response([
                    'status'  => False,
                    'message' => 'id member tidak ada.'
                ], REST_Controller::HTTP_OK);
            }
        }
    }
}