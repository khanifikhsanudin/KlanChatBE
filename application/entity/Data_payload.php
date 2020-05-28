<?php
class Data_payload {

    private $judul;
    private $pesan;
    private $avatar;
    private $gambar;
    private $aksi;
    private $data;

    //aksi chat

    public function setJudul($judul) {
        $this->judul = $judul;
    }

    public function setPesan($pesan) {
        $this->pesan = $pesan;
    }

    public function setAvatar($avatar) {
        $this->avatar = $avatar;
    }

    public function setGambar($gambar) {
        $this->gambar = $gambar;
    }

    public function setAksi($aksi) {
        $this->aksi = $aksi;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function getData() {
        date_default_timezone_set('Asia/Jakarta');
        $data = [
            'judul'    => $this->judul,
            'pesan'    => $this->pesan,
            'avatar'   => $this->avatar,
            'gambar'   => $this->gambar,
            'aksi'     => $this->aksi,
            'data'     => $this->data,
            'waktu'    => date('Y-m-d G:i:s')
        ];

        $entity = array();
        foreach ($data as $key => $val) {
            if ($val != null){
                $entity['data'][$key] = $val;
            }
        }
        return $entity;
    }
}
