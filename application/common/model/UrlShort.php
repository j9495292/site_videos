<?php

namespace app\common\model;


use think\Exception;

class UrlShort{

    protected static $type = ['w0z.cn','s1w.cn','sgo.run'];

    //闪电短网址
    public static function builder($url,$type = 1){
        $index_url = 'https://ssdwz.cn/index';
        $api_url = 'https://ssdwz.cn/api/create?createSource=1';
        $curl = Curl::exec();
        $urlType = self::$type[1];

        $reponse = $curl->get($index_url,[
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36',
                'Referer' => 'https://ssdwz.cn/'
            ]
        ])->body();

        preg_match('/<input type="hidden" name="accuid" id="accuid" value="(.*?)"\/>/',$reponse,$mac);

        if(!isset($mac[1]) || empty($mac[1])){
            throw new Exception('accuid获取失败');
        }

        $params = [
            'accuid' => $mac[1],
            'domain' => $urlType,
            'longUrl' => $url
        ];

        $request_url = $api_url.'&'.http_build_query($params);

        $response = $curl->post($request_url,[
            'data' => [],
            'headers' => [
                'Content-Type' => 'application/json;charset=UTF-8'
            ],
            'ajax' => 1,
            'referer' => 'https://ssdwz.cn/index'
        ])->body();

        $data = json_decode($response,true);

        if(empty($data) || empty($data['shortUrl'])){
            throw new Exception('shortUrl转换失败');
        }

        return 'http://'.$data['shortUrlModel'].'/'.$data['shortUrl'];
    }
    
    //6度短网址
    public static function builder_6du($url,$type){
        $api_url = 'http://api.wzk.im/urls/add';
        $params = [
            'secretkey' => '5f75a010a7ed6ed5ICAgICA899274f3c08191fagNzIzOQ',
            'lurl' => $url,
            //短网址host设置
            'host' => '',
            'format' => 'json'
        ];
        $request_url = $api_url.'?'.urldecode(http_build_query($params));
        $curl = Curl::exec();
        $response = $curl->get($request_url)->body();
        $data = json_decode($response,true);
        if($data['status'] == 1){
            return $data['message'];
        }else{
            return '';
        }
    }
    
    
    //百度短网址
    public static function builder_baidu($url,$type = 0){
        $api_url = 'https://dwz.cn/api/v3/short-urls';
        
        //百度短网址token
        $api_token = '';
        
        
        $headers = [
            'Content-Type:application/json; charset=UTF-8',
            'Dwz-Token:'.$api_token
        ];
        
        
        $bodys = [
            [
                'LongUrl' => $url,
                'TermOfValidity' => '1-year'
            ]
        ];
        
        $curl = curl_init($api_url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($bodys));
        
        $data = curl_exec($curl);
        curl_close($curl);
        
        $short_url = json_decode($data,true);
        
        // return $short_url;
        
        if($short_url['Code'] == 0){
            return isset($short_url['ShortUrls'][0]['ShortUrl']) ? $short_url['ShortUrls'][0]['ShortUrl'] : false;
        }else{
            return false;
        }
    }


    //麒麟短网址
    public static function builder_qilin($url,$type){
        if ($type == "wx") {
            $api_url1 = "http://url.pl008.top/dwz.php?type=ty&longurl=http://".urlencode($url);
            //$arr = file_get_contents($api_url);
            //$data = json_decode($arr,true);
            $arr1 = file_get_contents($api_url1);
            $res=json_decode($arr1,true);
        if (!empty($res['ae_url']) ){
        $arr1=$res['ae_url'];
    }
        return $arr1;
 //           return $arr1;
        } 
        if ($type == "qq") {
          $api_url1 = "http://api.70api.com/api/urlcn/?apiKey=f53d485c3832693dc858df0586149b3d&url=http://".urlencode($url);
            //$arr = file_get_contents($api_url);
            //$data = json_decode($arr,true);
            $arr1 = file_get_contents($api_url1);
            $res=json_decode($arr1,true);
        if (!empty($res['data']['short_url']) ){
        $arr1=$res['data']['short_url'];
    }
        return $arr1;
        }
        if ($type == "dy") {
            $api_url = "http://dwz.r1889.top/dwz.php/dwzApi/dwz?token=07dde84cf76c3a1d5b22894348c02b7f&url=http://".urlencode($url);
            $arr = file_get_contents($api_url);
            return 'https://touch.10086.cn/appregion.html?backUrl=http://'.urldecode($url);
        }

        //$username = config('web.ql_username');
        //$key = config('web.ql_key');

        //$arr = file_get_contents($api_url);
//        if($data = json_decode($arr,true)){

//        $data['code'] == '1';
//            return isset($data['short']) ? $data['short'] : false;
//        }else{
//            return false;
//        }
    }



}