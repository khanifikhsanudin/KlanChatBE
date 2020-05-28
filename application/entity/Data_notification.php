<?php
class Data_notification {

    private $title;
    private $body;
    private $image;
    private $click_action;

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function setImage($image) {
        $this->image = $image;
    }

    public function setClickAction($click_action) {
        $this->click_action = $click_action;
    }

    public function getNotification() {
        date_default_timezone_set('Asia/Jakarta');
        $notification = [
            'title'         => $this->title,
            'body'          => $this->body,
            'image'         => $this->image,
            'click_action'  => $this->click_action
        ];

        $entity = array();
        foreach ($notification as $key => $val) {
            if ($val != null){
                $entity['notification'][$key] = $val;
            }
        }
        return $entity;
    }
}
