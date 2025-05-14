<?php

namespace app\common\model;

class UrlCheck{

    protected $api = 'http://api.new.urlzt.com';

    protected $token = '';

    protected $service = '';

    public function token($val){
        $this->token = $val;
        return $this;
    }

    public function where($val){
        $types = [
            // 'qq' => '/api/qq',
            // 'dy' => '/api/dyjc',
            'wx' => '/api/vx'
        ];
        $this->service = isset($types[$val]) ? $types[$val] : '';
        return $this;
    }

    public function check($url){
        $params = [
            'token' => $this->token,
            'url' => $url,
            'format' => 'json'
        ];
        $request_url = $this->api.$this->service.'?'.urldecode(http_build_query($params));
        $response = file_get_contents($request_url);
        $data = json_decode($response,true);
        if(empty($data)){
            return false;
        }
        return $data['code'];
    }

    

}